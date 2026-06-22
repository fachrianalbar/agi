<?php

namespace App\Services;

use App\Models\Fleet;
use App\Models\FleetTransaction;
use Carbon\CarbonImmutable;
use DOMDocument;
use DOMElement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FleetTransactionService
{
    /**
     * Maps Indonesian column headers to their English equivalents.
     */
    private const HEADER_ID_TO_EN = [
        'nama perangkat'              => 'device name',
        'tanggal & waktu'             => 'date & time',
        'tanggal'                     => 'date & time',
        'jarak tempuh(km)'            => 'odometer(km)',
        'pemakaian (l)'               => 'usage (l)',
        'biaya (rp)'                  => 'cost (rp)',
        'km / l'                      => '1 km /l',
        'l / km'                      => '1 l /km',
        'biaya / km'                  => '1 km /cost',
        'mengisi bahan bakar (l)'     => 'refuel (l)',
        'mengisi bahan bakar (waktu)' => 'refuel (times)',
        'lari'                        => 'running (hh:mm:ss)',
        'berhenti'                    => 'stop (hh:mm:ss)',
        'diam'                        => 'idle (hh:mm:ss)',
        'diam pemakaian (l)'          => 'idle usage (l)',
        'diam biaya (rp)'             => 'idle cost (rp)',
        'volume awal (l)'             => 'initial volume(l)',
        'volume akhir (l)'            => 'final volume(l)',
    ];

    public function getDataTableQuery(): Builder
    {
        return FleetTransaction::query()
            ->with(['fleet.customer'])
            ->select('fleet_transactions.*');
    }

    public function create(array $data): FleetTransaction
    {
        $fleet = Fleet::query()->findOrFail($data['fleet_id']);

        return DB::transaction(fn() => FleetTransaction::query()->create([
            ...$data,
            'vehicle_name_snapshot' => $fleet->vehicle_name,
            'km_per_l' => $this->calculateKmPerLiter(
                (float) $data['odometer_km'],
                (float) $data['usage_l'],
            ),
            'l_per_km' => $this->calculateLitersPerKm(
                (float) $data['usage_l'],
                (float) $data['odometer_km'],
            ),
            'cost_per_km' => $this->calculateCostPerKm(
                (float) $data['cost_rp'],
                (float) $data['odometer_km'],
            ),
        ]));
    }

    public function update(FleetTransaction $transaction, array $data): FleetTransaction
    {
        $fleet = Fleet::query()->findOrFail($data['fleet_id']);

        return DB::transaction(function () use ($transaction, $data, $fleet): FleetTransaction {
            $transaction->update([
                ...$data,
                'vehicle_name_snapshot' => $fleet->vehicle_name,
                'km_per_l' => $this->calculateKmPerLiter(
                    (float) $data['odometer_km'],
                    (float) $data['usage_l'],
                ),
                'l_per_km' => $this->calculateLitersPerKm(
                    (float) $data['usage_l'],
                    (float) $data['odometer_km'],
                ),
                'cost_per_km' => $this->calculateCostPerKm(
                    (float) $data['cost_rp'],
                    (float) $data['odometer_km'],
                ),
            ]);

            return $transaction->fresh(['fleet']);
        });
    }

    public function delete(FleetTransaction $transaction): void
    {
        DB::transaction(fn() => $transaction->delete());
    }

    /**
     * Import a Total Kilat GPS daily performance HTML/XLS export.
     *
     * @return array{created: int, updated: int, unchanged: int, skipped: int}
     */
    public function import(UploadedFile $file): array
    {
        $rows = $this->parseUploadedRows($file);

        if ($rows === []) {
            throw ValidationException::withMessages([
                'file' => 'The uploaded file does not contain transaction rows.',
            ]);
        }

        $matchedRows = $this->matchRowsToFleets($rows);
        $now = now();

        return DB::transaction(function () use ($matchedRows, $file, $now): array {
            $summary = [
                'created' => 0,
                'updated' => 0,
                'unchanged' => 0,
                'skipped' => 0,
            ];

            foreach ($matchedRows as $row) {
                $transaction = FleetTransaction::query()
                    ->withTrashed()
                    ->where('fleet_id', $row['fleet_id'])
                    ->whereDate('transaction_date', $row['transaction_date'])
                    ->first();

                $attributes = [
                    ...$row,
                ];
                $auditAttributes = [
                    'source_filename' => $file->getClientOriginalName(),
                    'imported_at' => $now,
                ];

                if (! $transaction) {
                    FleetTransaction::query()->create([
                        ...$attributes,
                        ...$auditAttributes,
                    ]);
                    $summary['created']++;

                    continue;
                }

                if ($transaction->trashed()) {
                    $transaction->restore();
                }

                $transaction->fill($attributes);

                if (! $transaction->isDirty()) {
                    $summary['unchanged']++;

                    continue;
                }

                $transaction->fill($auditAttributes);
                $transaction->save();
                $summary['updated']++;
            }

            return $summary;
        });
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function parseUploadedRows(UploadedFile $file): array
    {
        $contents = (string) file_get_contents($file->getRealPath());
        $tables = $this->extractHtmlTables($contents);
        $dataTable = $tables[1] ?? null;

        if (! $dataTable || count($dataTable) < 2) {
            throw ValidationException::withMessages([
                'file' => 'The uploaded file must contain the Daily Performance Analysis table.',
            ]);
        }

        $headers = array_map(
            fn(string $value): string => $this->translateHeader($this->normalizeHeader($value)),
            $dataTable[0],
        );

        // For Indonesian-format files the date is not a per-row column but a
        // range stored in the first table, e.g. "[2026-06-20 00:00:00-2026-06-22 23:59:59]".
        $fallbackDate = null;

        if (! in_array('date & time', $headers, true)) {
            $rangeText = $tables[0][0][0] ?? '';
            if (preg_match('/\[(\d{4}-\d{2}-\d{2})[^\]]*\]/', $rangeText, $m)) {
                $fallbackDate = $m[1];
            }
        }

        $rows = [];

        foreach (array_slice($dataTable, 1) as $line) {
            $record = [];

            foreach ($headers as $index => $header) {
                $record[$header] = $line[$index] ?? null;
            }

            $vehicleName = $this->cleanText($record['device name'] ?? '');
            $dateTime = $this->cleanText($record['date & time'] ?? '');
            $resolvedDate = $dateTime !== '' ? CarbonImmutable::parse($dateTime)->toDateString() : $fallbackDate;

            if ($vehicleName === '' || $resolvedDate === null) {
                continue;
            }

            $rows[] = [
                'transaction_date' => $resolvedDate,
                'vehicle_name_snapshot' => $vehicleName,
                'odometer_km' => $this->parseNumber($record['odometer(km)'] ?? null) ?? 0,
                'initial_volume_l' => $this->parseNumber($record['initial volume(l)'] ?? null),
                'final_volume_l' => $this->parseNumber($record['final volume(l)'] ?? null),
                'usage_l' => $this->parseNumber($record['usage (l)'] ?? null) ?? 0,
                'cost_rp' => $this->parseNumber($record['cost (rp)'] ?? null) ?? 0,
                'idle_usage_l' => $this->parseNumber($record['idle usage (l)'] ?? null),
                'km_per_l' => $this->calculateKmPerLiter(
                    $this->parseNumber($record['odometer(km)'] ?? null) ?? 0,
                    $this->parseNumber($record['usage (l)'] ?? null) ?? 0,
                ),
                'l_per_km' => $this->calculateLitersPerKm(
                    $this->parseNumber($record['usage (l)'] ?? null) ?? 0,
                    $this->parseNumber($record['odometer(km)'] ?? null) ?? 0,
                ),
                'cost_per_km' => $this->calculateCostPerKm(
                    $this->parseNumber($record['cost (rp)'] ?? null) ?? 0,
                    $this->parseNumber($record['odometer(km)'] ?? null) ?? 0,
                ),
                'refuel_l' => $this->parseNumber($record['refuel (l)'] ?? null),
                'refuel_times' => $this->parseInteger($record['refuel (times)'] ?? null),
                'running_duration_seconds' => $this->parseDuration($record['running (hh:mm:ss)'] ?? null),
                'idle_duration_seconds' => $this->parseDuration($record['idle (hh:mm:ss)'] ?? null),
                'stop_duration_seconds' => $this->parseDuration($record['stop (hh:mm:ss)'] ?? null),
            ];
        }

        return $rows;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function matchRowsToFleets(array $rows): array
    {
        $fleetGroups = Fleet::query()
            ->orderBy('vehicle_name')
            ->get(['id', 'vehicle_name'])
            ->groupBy(fn(Fleet $fleet): string => $this->normalizeVehicleName($fleet->vehicle_name));

        $unmatched = [];
        $ambiguous = [];
        $matched = [];

        foreach ($rows as $row) {
            $key = $this->normalizeVehicleName($row['vehicle_name_snapshot']);
            /** @var Collection<int, Fleet>|null $fleets */
            $fleets = $fleetGroups->get($key);

            if (! $fleets || $fleets->isEmpty()) {
                $unmatched[] = $row['vehicle_name_snapshot'];

                continue;
            }

            if ($fleets->count() > 1) {
                $ambiguous[] = $row['vehicle_name_snapshot'];

                continue;
            }

            $matched[] = [
                ...$row,
                'fleet_id' => $fleets->first()->id,
            ];
        }

        if ($unmatched !== [] || $ambiguous !== []) {
            $messages = [];

            if ($unmatched !== []) {
                $messages[] = 'Vehicle not found in fleet master: ' . implode(', ', array_values(array_unique($unmatched))) . '.';
            }

            if ($ambiguous !== []) {
                $messages[] = 'Vehicle name is duplicated in fleet master: ' . implode(', ', array_values(array_unique($ambiguous))) . '.';
            }

            throw ValidationException::withMessages([
                'file' => implode(' ', $messages),
            ]);
        }

        return $matched;
    }

    /**
     * @return list<list<string>>
     */
    private function extractHtmlTables(string $contents): array
    {
        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8">' . $contents);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $tables = [];

        foreach ($document->getElementsByTagName('table') as $table) {
            $rows = [];

            foreach ($table->getElementsByTagName('tr') as $tr) {
                if (! $tr instanceof DOMElement) {
                    continue;
                }

                $cells = [];

                foreach ($tr->childNodes as $cell) {
                    if (! $cell instanceof DOMElement || ! in_array($cell->tagName, ['td', 'th'], true)) {
                        continue;
                    }

                    $cells[] = $this->cleanText($cell->textContent);
                }

                if ($cells !== []) {
                    $rows[] = $cells;
                }
            }

            $tables[] = $rows;
        }

        return $tables;
    }

    private function normalizeHeader(string $value): string
    {
        return str($this->cleanText($value))->lower()->squish()->toString();
    }

    private function normalizeVehicleName(string $value): string
    {
        return str($this->cleanText($value))->upper()->squish()->toString();
    }

    private function cleanText(?string $value): string
    {
        return trim((string) preg_replace('/\s+/', ' ', str_replace("\u{00A0}", ' ', (string) $value)));
    }

    private function parseNumber(mixed $value): ?float
    {
        $clean = str_replace(',', '', $this->cleanText((string) $value));

        if ($clean === '' || in_array(strtolower($clean), ['nan', 'inf', 'infinity'], true)) {
            return null;
        }

        return is_numeric($clean) ? (float) $clean : null;
    }

    private function parseInteger(mixed $value): ?int
    {
        $number = $this->parseNumber($value);

        return $number === null ? null : (int) $number;
    }

    private function parseDuration(mixed $value): ?int
    {
        $clean = $this->cleanText((string) $value);

        if ($clean === '') {
            return null;
        }

        // Indonesian format: "Xday H:M:S (XX%)" e.g. "1day 20:25:20 (28.4%)"
        if (preg_match('/^(\d+)day\s+(\d+):(\d+):(\d+)/', $clean, $m)) {
            return ((int) $m[1] * 86400) + ((int) $m[2] * 3600) + ((int) $m[3] * 60) + (int) $m[4];
        }

        // English format: "HH:MM:SS"
        $parts = explode(':', $clean);

        if (count($parts) !== 3) {
            return null;
        }

        return ((int) $parts[0] * 3600) + ((int) $parts[1] * 60) + (int) $parts[2];
    }

    /**
     * Translate a normalized Indonesian header to its English equivalent.
     * Returns the original value unchanged if no translation exists.
     */
    private function translateHeader(string $header): string
    {
        return self::HEADER_ID_TO_EN[$header] ?? $header;
    }

    private function calculateKmPerLiter(float $odometerKm, float $usageL): ?float
    {
        if ($usageL <= 0) {
            return null;
        }

        return round($odometerKm / $usageL, 4);
    }

    private function calculateLitersPerKm(float $usageL, float $odometerKm): ?float
    {
        if ($odometerKm <= 0) {
            return null;
        }

        return round($usageL / $odometerKm, 4);
    }

    private function calculateCostPerKm(float $costRp, float $odometerKm): ?float
    {
        if ($odometerKm <= 0) {
            return null;
        }

        return round($costRp / $odometerKm, 4);
    }
}
