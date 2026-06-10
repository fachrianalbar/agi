<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Fleet;
use Carbon\CarbonImmutable;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Number;

class FleetHistoryService
{
    public function __construct(
        private readonly TotalKilatGpsService $gpsService,
    ) {}

    /**
     * Get customers that can generate GPS reports.
     */
    public function getCustomers(): Collection
    {
        return Customer::query()
            ->where('is_active', true)
            ->whereNotNull('username')
            ->whereNotNull('password')
            ->where('username', '!=', '')
            ->where('password', '!=', '')
            ->whereHas('fleets', fn ($query) => $query->where('is_active', true))
            ->orderBy('name')
            ->get(['id', 'name', 'username']);
    }

    /**
     * Get active fleets for a customer.
     */
    public function getFleetsForCustomer(?string $customerId): Collection
    {
        if (! $customerId) {
            return new Collection;
        }

        return Fleet::query()
            ->where('customer_id', $customerId)
            ->where('is_active', true)
            ->orderBy('vehicle_name')
            ->get(['vehicle_name', 'device_name']);
    }

    /**
     * Generate presentation-ready fleet history rows.
     *
     * @return list<array<string, mixed>>
     */
    public function generate(
        Customer $customer,
        string $deviceName,
        CarbonImmutable $startTime,
        CarbonImmutable $endTime,
    ): array {
        $rows = $this->gpsService->getDeviceHistory(
            $customer,
            $deviceName,
            $startTime->format('Y-m-d H:i:s'),
            $endTime->format('Y-m-d H:i:s'),
        );

        return collect($rows)
            ->map(function (array $row): array {
                $datetime = $this->formatIndonesianDateTime($row['datetime']);
                $location = $row['gps_location'] ?: '—';
                $engineIsOn = $row['engine'] === 1;

                return [
                    'datetime' => $datetime,
                    'date_time_utc' => $this->formatDateTime($row['date_time_utc']),
                    'local_date_time' => $this->formatIndonesianDateTime($row['local_date_time']),
                    'gps_location' => $location,
                    'gps_valid' => $row['gps_valid'],
                    'longitude' => Number::format($row['longitude'], maxPrecision: 6),
                    'latitude' => Number::format($row['latitude'], maxPrecision: 6),
                    'speed' => Number::format($row['speed'], maxPrecision: 1).' km/h',
                    'direction' => "{$row['direction']}°",
                    'engine' => $engineIsOn,
                    'odometer' => Number::format($row['odometer'], maxPrecision: 2).' km',
                    'temperature' => $row['temperature'] !== '' ? $row['temperature'] : '—',
                    'max_speed' => Number::format($row['max_speed'], maxPrecision: 1).' km/h',
                    'overspeed' => $row['overspeed'],
                    'harsh_acceleration' => $row['harsh_acceleration'],
                    'harsh_braking' => $row['harsh_braking'],
                    'harsh_cornering' => $row['harsh_cornering'],
                    'playback' => [
                        'datetime' => $datetime,
                        'address' => $location,
                        'latitude' => $row['latitude'],
                        'longitude' => $row['longitude'],
                        'speed' => $row['speed'],
                        'direction' => $row['direction'],
                        'engine' => $engineIsOn,
                        'odometer' => $row['odometer'],
                    ],
                ];
            })
            ->all();
    }

    private function formatIndonesianDateTime(?string $dateTime): string
    {
        if (! $dateTime) {
            return '—';
        }

        try {
            return CarbonImmutable::parse($dateTime)
                ->locale('id')
                ->translatedFormat('d F Y H:i:s');
        } catch (InvalidFormatException) {
            return '—';
        }
    }

    private function formatDateTime(?string $dateTime): string
    {
        if (! $dateTime) {
            return '—';
        }

        try {
            return CarbonImmutable::parse($dateTime)->format('Y-m-d H:i:s');
        } catch (InvalidFormatException) {
            return '—';
        }
    }
}
