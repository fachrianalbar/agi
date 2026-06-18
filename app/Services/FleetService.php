<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Fleet;
use Carbon\CarbonImmutable;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Number;

class FleetService
{
    public function __construct(
        private readonly TotalKilatGpsService $gpsService,
        private readonly NominatimReverseGeocodingService $reverseGeocodingService,
    ) {}

    /**
     * Get the base query for DataTables server-side processing.
     */
    public function getDataTableQuery(): Builder
    {
        return Fleet::query()
            ->with('customer')
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
    public function getSyncCustomers(): Collection
    {
        return Customer::query()
            ->where('is_active', true)
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
     * @return array{total: int, created: int, updated: int, unchanged: int}
     */
    public function synchronize(Customer $customer): array
    {
        $devices = $this->gpsService->getDevices($customer);

        return DB::transaction(function () use ($customer, $devices): array {
            $summary = [
                'total' => count($devices),
                'created' => 0,
                'updated' => 0,
                'unchanged' => 0,
            ];

            foreach ($devices as $device) {
                $fleet = Fleet::query()
                    ->withTrashed()
                    ->where('customer_id', $customer->id)
                    ->where('device_name', $device['device_name'])
                    ->first();

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

                $fleet->fill([
                    'vehicle_name' => $device['vehicle_name'],
                    'is_active' => true,
                ]);
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
    public function getLatestPositions(array $requestedDevices): array
    {
        $deviceNames = collect($requestedDevices)
            ->pluck('device_name')
            ->filter()
            ->unique()
            ->values();
        $requestedByReference = collect($requestedDevices)->keyBy('ref');
        $fleets = Fleet::query()
            ->with('customer')
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

            $positions = $this->gpsService->getLatestPositions(
                $customer,
                $customerFleets->pluck('device_name')->all(),
            );

            foreach ($customerFleets as $fleet) {
                $reference = $this->positionReference($fleet);
                $position = $positions[$fleet->device_name] ?? null;

                if (! $position) {
                    $result[$reference] = $this->unavailablePosition();

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
                'text' => Number::format($position['mileage'], maxPrecision: 3).' km',
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
     * @return array<string, array<string, mixed>>
     */
    private function unavailablePosition(): array
    {
        return [
            'mileage' => ['text' => 'Unavailable', 'state' => 'error'],
            'vehicle_status' => ['text' => 'Unavailable', 'badge' => 'neutral'],
            'engine' => ['text' => 'Unavailable', 'badge' => 'neutral'],
            'last_update' => ['text' => 'Unavailable', 'state' => 'error'],
            'address' => ['text' => 'Unavailable', 'state' => 'error'],
            'map' => ['url' => null, 'latitude' => null, 'longitude' => null],
        ];
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
