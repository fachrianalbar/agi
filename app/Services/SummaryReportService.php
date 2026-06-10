<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Fleet;
use Carbon\CarbonImmutable;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Number;

class SummaryReportService
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
     * Generate presentation-ready daily summary report rows.
     *
     * @return list<array<string, string|int>>
     */
    public function generate(
        Customer $customer,
        string $deviceName,
        CarbonImmutable $startTime,
        CarbonImmutable $endTime,
    ): array {
        $rows = $this->gpsService->getDailySummaryReport(
            $customer,
            $deviceName,
            $startTime->format('Y-m-d H:i:s'),
            $endTime->format('Y-m-d H:i:s'),
        );

        return collect($rows)
            ->map(fn (array $row): array => [
                'vehicle_name' => $row['vehicle_name'] ?: '—',
                'device_name' => $row['device_name'] ?: '—',
                'date' => $this->formatIndonesianDate($row['datetime']),
                'start_time' => $this->formatIndonesianDateTime($row['start_time']),
                'start_location' => $row['start_location'] ?: '—',
                'end_time' => $this->formatIndonesianDateTime($row['end_time']),
                'end_location' => $row['end_location'] ?: '—',
                'running_time' => $this->formatDuration($row['running_time']),
                'idle_time' => $this->formatDuration($row['idle_time']),
                'travelling' => $this->formatDuration($row['travelling']),
                'parking' => $this->formatDuration($row['parking']),
                'odometer' => Number::format($row['odometer'], maxPrecision: 2).' km',
                'usage' => Number::format($row['usage'], maxPrecision: 2),
                'max_speed' => Number::format($row['max_speed'], maxPrecision: 1).' km/h',
                'geofence_times' => $row['geofence_times'],
            ])
            ->all();
    }

    private function formatIndonesianDate(?string $date): string
    {
        if (! $date) {
            return '—';
        }

        try {
            return CarbonImmutable::parse($date)
                ->locale('id')
                ->translatedFormat('d F Y');
        } catch (InvalidFormatException) {
            return '—';
        }
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

    private function formatDuration(int $seconds): string
    {
        $seconds = max(0, $seconds);
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $remainingSeconds = $seconds % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $remainingSeconds);
    }
}
