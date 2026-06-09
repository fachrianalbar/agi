<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Fleet;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class FleetService
{
    public function __construct(
        private readonly TotalKilatGpsService $gpsService,
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
