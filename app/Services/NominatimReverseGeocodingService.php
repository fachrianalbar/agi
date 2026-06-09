<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class NominatimReverseGeocodingService
{
    public function execute(float $latitude, float $longitude): string
    {
        $cacheKey = sprintf(
            'nominatim:address:%s:%s',
            number_format($latitude, 5, '.', ''),
            number_format($longitude, 5, '.', ''),
        );

        return Cache::remember($cacheKey, now()->addDays(30), function () use ($latitude, $longitude): string {
            try {
                $response = Http::timeout(60)
                    ->withUserAgent('AGI Laravel Fleet/1.0')
                    ->get('https://nominatim.openstreetmap.org/reverse', [
                        'format' => 'jsonv2',
                        'lat' => $latitude,
                        'lon' => $longitude,
                    ]);
            } catch (Throwable) {
                return '';
            }

            if (! $response->successful()) {
                return '';
            }

            $displayName = $response->json('display_name');

            return is_string($displayName) ? trim($displayName) : '';
        });
    }
}
