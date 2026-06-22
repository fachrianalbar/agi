<?php

namespace App\Services;

use App\Exceptions\ExternalFleetApiException;
use App\Models\Customer;
use App\Models\Fleet;
use Carbon\CarbonImmutable;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FleetService
{
    public function __construct(
        private readonly TotalKilatGpsService $gpsService,
        private readonly NominatimReverseGeocodingService $reverseGeocodingService,
    ) {}

    /**
     * Get the base query for DataTables server-side processing.
     */
    public function getDataTableQuery(?string $customerId = null): Builder
    {
        return Fleet::query()
            ->with('customer')
            ->when($customerId !== null, fn (Builder $query) => $query->where('fleets.customer_id', $customerId))
            ->select([
                'id',
                'customer_id',
                'vehicle_name',
                'device_name',
                'has_fuel_sensor',
                'fuel_sensor_installed_at',
                'fuel_sensor_status',
                'latest_address',
                'latest_mileage',
                'latest_vehicle_status',
                'latest_engine',
                'latest_update',
                'is_active',
                'created_at',
            ]);
    }

    /**
     * Get customers that can be selected for fleet synchronization.
     */
    public function getSyncCustomers(?string $customerId = null): Collection
    {
        return Customer::query()
            ->where('is_active', true)
            ->when($customerId !== null, fn (Builder $query) => $query->whereKey($customerId))
            ->whereNotNull('username')
            ->whereNotNull('password')
            ->where('username', '!=', '')
            ->where('password', '!=', '')
            ->orderBy('name')
            ->get(['id', 'name', 'username']);
    }

    /**
     * Synchronize customer fleets from the GPS provider.
     *
     * @return array{total: int, created: int, updated: int, deleted: int, unchanged: int}
     */
    public function synchronize(Customer $customer): array
    {
        $devices = $this->gpsService->getDevices($customer);

        return DB::transaction(function () use ($customer, $devices): array {
            $summary = [
                'total' => count($devices),
                'created' => 0,
                'updated' => 0,
                'deleted' => 0,
                'unchanged' => 0,
            ];

            foreach ($devices as $device) {
                $relatedFleets = Fleet::query()
                    ->withTrashed()
                    ->where('customer_id', $customer->id)
                    ->where(function (Builder $query) use ($device): void {
                        $query
                            ->where('vehicle_name', $device['vehicle_name'])
                            ->orWhere('device_name', $device['device_name']);
                    })
                    ->lockForUpdate()
                    ->get();

                $fleet = $relatedFleets->first(
                    fn (Fleet $candidate): bool => $candidate->vehicle_name === $device['vehicle_name']
                        && $candidate->device_name === $device['device_name'],
                );

                $relatedFleets
                    ->reject(fn (Fleet $candidate): bool => $fleet?->is($candidate) ?? false)
                    ->filter(fn (Fleet $candidate): bool => ! $candidate->trashed())
                    ->each(function (Fleet $candidate) use (&$summary): void {
                        $candidate->delete();
                        $summary['deleted']++;
                    });

                if (! $fleet) {
                    Fleet::query()->create([
                        'customer_id' => $customer->id,
                        'vehicle_name' => $device['vehicle_name'],
                        'device_name' => $device['device_name'],
                        'is_active' => true,
                    ]);
                    $summary['created']++;

                    continue;
                }

                $fleet->fill(['is_active' => true]);
                $changed = $fleet->isDirty() || $fleet->trashed();

                if (! $changed) {
                    $summary['unchanged']++;

                    continue;
                }

                if ($fleet->trashed()) {
                    $fleet->restore();
                }

                $fleet->save();
                $summary['updated']++;
            }

            return $summary;
        });
    }

    public function positionReference(Fleet $fleet): string
    {
        return hash_hmac('sha256', $fleet->id, (string) config('app.key'));
    }

    /**
     * Get presentation-ready latest positions for visible DataTable rows.
     *
     * @param  list<array{ref: string, device_name: string}>  $requestedDevices
     * @return array<string, array<string, array<string, mixed>>>
     */
    public function getLatestPositions(array $requestedDevices, ?string $customerId = null): array
    {
        $deviceNames = collect($requestedDevices)
            ->pluck('device_name')
            ->filter()
            ->unique()
            ->values();
        $requestedByReference = collect($requestedDevices)->keyBy('ref');
        $fleets = Fleet::query()
            ->with('customer')
            ->when($customerId !== null, fn (Builder $query) => $query->where('customer_id', $customerId))
            ->whereIn('device_name', $deviceNames)
            ->get()
            ->filter(function (Fleet $fleet) use ($requestedByReference): bool {
                $reference = $this->positionReference($fleet);
                $requested = $requestedByReference->get($reference);

                return is_array($requested)
                    && hash_equals($reference, (string) $requested['ref'])
                    && $fleet->device_name === $requested['device_name'];
            });
        $result = [];

        foreach ($fleets->groupBy('customer_id') as $customerFleets) {
            $customer = $customerFleets->first()?->customer;

            if (! $customer) {
                continue;
            }

            try {
                $positions = $this->gpsService->getLatestPositions(
                    $customer,
                    $customerFleets->pluck('device_name')->all(),
                );
            } catch (ExternalFleetApiException $exception) {
                Log::warning('Latest fleet positions failed for a customer; using saved snapshots.', [
                    'customer_id' => $customer->id,
                    'reason' => $exception->getMessage(),
                ]);

                foreach ($customerFleets as $fleet) {
                    $result[$this->positionReference($fleet)] = $this->cachedPositionSnapshot($fleet);
                }

                continue;
            }

            foreach ($customerFleets as $fleet) {
                $reference = $this->positionReference($fleet);
                $position = $positions[$fleet->device_name] ?? null;

                if (! $position) {
                    $result[$reference] = $this->cachedPositionSnapshot($fleet);

                    continue;
                }

                $formattedPosition = $this->formatLatestPosition($position, $fleet);
                $result[$reference] = $formattedPosition;

                DB::table('fleets')
                    ->where('id', $fleet->id)
                    ->update([
                        'latest_address' => $formattedPosition['address']['text'],
                        'latest_mileage' => $formattedPosition['mileage']['text'],
                        'latest_vehicle_status' => $formattedPosition['vehicle_status']['text'],
                        'latest_engine' => $formattedPosition['engine']['text'],
                        'latest_update' => $formattedPosition['last_update']['text'],
                    ]);
            }
        }

        return $result;
    }

    /**
     * Get presentation-ready saved latest position data.
     *
     * @return array<string, array<string, mixed>>
     */
    public function cachedPositionSnapshot(Fleet $fleet): array
    {
        $vehicleStatus = trim((string) $fleet->latest_vehicle_status);
        $engine = trim((string) $fleet->latest_engine);

        return [
            'mileage' => $this->mileageSnapshotValue($fleet->latest_mileage),
            'vehicle_status' => [
                ...$this->snapshotTextValue($vehicleStatus),
                'badge' => $this->vehicleStatusBadge($vehicleStatus),
            ],
            'engine' => [
                ...$this->snapshotTextValue($engine),
                'badge' => str($engine)->lower()->toString() === 'on' ? 'success' : 'neutral',
            ],
            'last_update' => $this->snapshotTextValue($fleet->latest_update),
            'address' => $this->snapshotTextValue($fleet->latest_address),
            'map' => ['url' => null, 'latitude' => null, 'longitude' => null],
        ];
    }

    /**
     * @param  array{
     *     datetime: string,
     *     mileage: float,
     *     heading: int,
     *     speed: float,
     *     latitude: float,
     *     longitude: float,
     *     acc: int,
     *     status_icon: int
     * }  $position
     * @return array<string, array<string, mixed>>
     */
    private function formatLatestPosition(array $position, ?Fleet $fleet = null): array
    {
        [$statusLabel, $statusVariant] = match ($position['status_icon']) {
            1 => ['Running', 'success'],
            2 => ['Stop', 'danger'],
            3 => ['Idle', 'warning'],
            default => ['INACTIVE', 'neutral'],
        };
        $engineOn = $position['acc'] === 1;
        $latitude = $position['latitude'];
        $longitude = $position['longitude'];
        $address = trim((string) ($fleet?->latest_address ?? ''));

        if ($address === '' && (bool) config('services.total_kilat_gps.resolve_addresses_on_refresh', true)) {
            $address = $this->reverseGeocodingService->execute($latitude, $longitude);
        }

        return [
            'mileage' => [
                'text' => $this->formatMileage($position['mileage']),
            ],
            'vehicle_status' => [
                'text' => $statusLabel,
                'badge' => $statusVariant,
            ],
            'engine' => [
                'text' => $engineOn ? 'On' : 'Off',
                'badge' => $engineOn ? 'success' : 'neutral',
            ],
            'last_update' => [
                'text' => $this->formatIndonesianDateTime($position['datetime']),
            ],
            'address' => [
                'text' => $address !== '' ? $address : 'Unavailable',
                'state' => $address !== '' ? null : 'error',
            ],
            'map' => [
                'url' => sprintf(
                    'https://maps.google.com/maps?q=%s,%s&z=16&output=embed',
                    rawurlencode((string) $latitude),
                    rawurlencode((string) $longitude),
                ),
                'latitude' => $latitude,
                'longitude' => $longitude,
            ],
        ];
    }

    private function formatIndonesianDateTime(string $dateTime): string
    {
        if ($dateTime === '') {
            return '—';
        }

        try {
            return CarbonImmutable::createFromFormat('Y-m-d H:i:s', $dateTime)
                ->locale('id')
                ->translatedFormat('d F Y H:i:s');
        } catch (InvalidFormatException) {
            return '—';
        }
    }

    /**
     * @return array{text: string, state?: string}
     */
    private function snapshotTextValue(?string $value): array
    {
        $text = trim((string) $value);

        if ($text === '') {
            return ['text' => 'Unavailable', 'state' => 'error'];
        }

        return ['text' => $text];
    }

    /**
     * Format mileage as a whole number with periods as thousands separators.
     */
    private function formatMileage(float $mileage): string
    {
        $formattedMileage = number_format(floor($mileage), 0, '', '.');

        return $formattedMileage.' km';
    }

    /**
     * Format legacy saved mileage values to the current thousands separator format.
     *
     * @return array{text: string, state?: string}
     */
    private function mileageSnapshotValue(?string $value): array
    {
        $snapshot = $this->snapshotTextValue($value);

        if (($snapshot['state'] ?? null) === 'error') {
            return $snapshot;
        }

        if (
            preg_match('/^([\d,]+(?:\.\d+)?)\s*km$/i', $snapshot['text'], $matches) === 1
            && str_contains($matches[1], ',')
        ) {
            return ['text' => $this->formatMileage((float) str_replace(',', '', $matches[1]))];
        }

        if (preg_match('/^(\d+)\s+ribu\s+km$/i', $snapshot['text'], $matches) === 1) {
            return ['text' => $this->formatMileage((float) $matches[1] * 1000)];
        }

        if (preg_match('/^(\d{4,})(?:\.\d{1,3})?\s*km$/i', $snapshot['text'], $matches) === 1) {
            return ['text' => $this->formatMileage((float) $matches[0])];
        }

        return $snapshot;
    }

    private function vehicleStatusBadge(string $status): string
    {
        return match (str($status)->lower()->toString()) {
            'running' => 'success',
            'stop' => 'danger',
            'idle' => 'warning',
            default => 'neutral',
        };
    }

    /**
     * Create a new fleet.
     */
    public function create(array $data): Fleet
    {
        return DB::transaction(fn () => Fleet::create($data));
    }

    /**
     * Update an existing fleet.
     */
    public function update(Fleet $fleet, array $data): Fleet
    {
        return DB::transaction(function () use ($fleet, $data) {
            $fleet->update($data);

            return $fleet->fresh();
        });
    }

    /**
     * Delete a fleet.
     */
    public function delete(Fleet $fleet): void
    {
        DB::transaction(function () use ($fleet) {
            $fleet->delete();
        });
    }
}
