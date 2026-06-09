<?php

namespace App\Services;

use App\Exceptions\ExternalFleetApiException;
use App\Models\Customer;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class TotalKilatGpsService
{
    private const ACCESS_TOKEN_ERROR = 30001;

    /**
     * Get normalized device records for a customer.
     *
     * @return list<array{vehicle_name: string, device_name: string}>
     */
    public function getDevices(Customer $customer): array
    {
        $token = $this->getAccessToken($customer);
        $payload = $this->requestDeviceInfo($token);

        if ($this->isAccessTokenError($payload)) {
            Cache::forget($this->tokenCacheKey($customer));

            $token = $this->getAccessToken($customer, refresh: true);
            $payload = $this->requestDeviceInfo($token);
        }

        if ($this->isAccessTokenError($payload)) {
            throw new ExternalFleetApiException('GPS access token was rejected after being refreshed.');
        }

        if (isset($payload['errcode'])) {
            throw new ExternalFleetApiException('The GPS provider could not return device information.');
        }

        return $this->normalizeDevices($payload);
    }

    /**
     * Get latest positions keyed by device name.
     *
     * @param  list<string>  $deviceNames
     * @return array<string, array{
     *     datetime: string,
     *     mileage: float,
     *     heading: int,
     *     speed: float,
     *     latitude: float,
     *     longitude: float,
     *     acc: int,
     *     status_icon: int
     * }>
     */
    public function getLatestPositions(Customer $customer, array $deviceNames): array
    {
        $deviceNames = array_values(array_unique(array_filter(
            array_map(static fn ($deviceName) => trim((string) $deviceName), $deviceNames),
        )));
        $positions = [];
        $uncachedDeviceNames = [];

        foreach ($deviceNames as $deviceName) {
            $cachedPosition = Cache::get($this->positionCacheKey($customer, $deviceName));

            if (is_array($cachedPosition)) {
                $positions[$deviceName] = $cachedPosition;
            } else {
                $uncachedDeviceNames[] = $deviceName;
            }
        }

        if ($uncachedDeviceNames === []) {
            return $positions;
        }

        $token = $this->getAccessToken($customer);
        $responses = $this->requestLatestPositionPool($token, $uncachedDeviceNames);
        $tokenRejectedDevices = [];

        foreach ($responses as $deviceName => $response) {
            $payload = $this->safeJsonPayload($response);

            if ($payload !== null && $this->isAccessTokenError($payload)) {
                $tokenRejectedDevices[] = $deviceName;
            }
        }

        if ($tokenRejectedDevices !== []) {
            Cache::forget($this->tokenCacheKey($customer));
            $token = $this->getAccessToken($customer, refresh: true);
            $refreshedResponses = $this->requestLatestPositionPool($token, $tokenRejectedDevices);
            $responses = array_replace($responses, $refreshedResponses);
        }

        foreach ($responses as $deviceName => $response) {
            $payload = $this->safeJsonPayload($response);

            if ($payload === null || $this->isAccessTokenError($payload) || isset($payload['errcode'])) {
                continue;
            }

            $position = $this->normalizeLatestPosition($payload, $deviceName);

            if ($position === null) {
                continue;
            }

            $positions[$deviceName] = $position;
            Cache::put(
                $this->positionCacheKey($customer, $deviceName),
                $position,
                now()->addSeconds((int) config('services.total_kilat_gps.position_cache_seconds', 20)),
            );
        }

        return $positions;
    }

    private function getAccessToken(Customer $customer, bool $refresh = false): string
    {
        $cacheKey = $this->tokenCacheKey($customer);

        if (! $refresh && is_string($cachedToken = Cache::get($cacheKey))) {
            return $cachedToken;
        }

        $response = $this->sendGetRequest('/token', [
            'grant_type' => $this->grantType(),
            'account_name' => $customer->username,
            'account_password' => $customer->password,
        ]);
        $payload = $this->jsonPayload($response);
        $token = $payload['access_token'] ?? null;

        if (! is_string($token) || $token === '') {
            throw new ExternalFleetApiException('GPS authentication failed for the selected customer.');
        }

        $expiresIn = is_numeric($payload['expires_in'] ?? null)
            ? (int) floor((float) $payload['expires_in'])
            : 3600;
        $cacheSeconds = max(1, $expiresIn - 60);

        Cache::put($cacheKey, $token, now()->addSeconds($cacheSeconds));

        return $token;
    }

    /**
     * @return array<mixed>
     */
    private function requestDeviceInfo(string $token): array
    {
        return $this->jsonPayload($this->sendGetRequest('/deviceInfo', [
            'grant_type' => $this->grantType(),
            'access_token' => $token,
        ]));
    }

    /**
     * @param  array<string, string>  $query
     */
    private function sendGetRequest(string $path, array $query): Response
    {
        try {
            $response = $this->client()->get($path, $query);
        } catch (ConnectionException) {
            throw new ExternalFleetApiException('The GPS provider could not be reached.');
        }

        if (! $response->successful()) {
            throw new ExternalFleetApiException('The GPS provider returned an unsuccessful response.');
        }

        return $response;
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('services.total_kilat_gps.base_url'), '/'))
            ->acceptJson()
            ->connectTimeout((int) config('services.total_kilat_gps.connect_timeout', 5))
            ->timeout((int) config('services.total_kilat_gps.timeout', 20));
    }

    /**
     * @param  list<string>  $deviceNames
     * @return array<string, Response|Throwable>
     */
    private function requestLatestPositionPool(string $token, array $deviceNames): array
    {
        return $this->client()->pool(
            function (Pool $pool) use ($token, $deviceNames): void {
                foreach ($deviceNames as $deviceName) {
                    $pool->as($deviceName)
                        ->baseUrl(rtrim((string) config('services.total_kilat_gps.base_url'), '/'))
                        ->acceptJson()
                        ->connectTimeout((int) config('services.total_kilat_gps.connect_timeout', 5))
                        ->timeout((int) config('services.total_kilat_gps.timeout', 20))
                        ->get('/latestVehiclePosition', [
                            'grant_type' => $this->grantType(),
                            'access_token' => $token,
                            'device_name' => $deviceName,
                        ]);
                }
            },
            (int) config('services.total_kilat_gps.position_concurrency', 10),
        );
    }

    /**
     * @return array<mixed>
     */
    private function jsonPayload(Response $response): array
    {
        $payload = $response->json();

        // deviceInfo currently returns a JSON string containing another JSON document.
        for ($decodeAttempt = 0; is_string($payload) && $decodeAttempt < 2; $decodeAttempt++) {
            $payload = json_decode(
                preg_replace('/^\xEF\xBB\xBF/', '', trim($payload)) ?? '',
                true,
            );

            if (json_last_error() !== JSON_ERROR_NONE) {
                break;
            }
        }

        if (! is_array($payload)) {
            throw new ExternalFleetApiException('The GPS provider returned an invalid response.');
        }

        return $payload;
    }

    /**
     * @return array<mixed>|null
     */
    private function safeJsonPayload(Response|Throwable $response): ?array
    {
        if (! $response instanceof Response || ! $response->successful()) {
            return null;
        }

        try {
            return $this->jsonPayload($response);
        } catch (ExternalFleetApiException) {
            return null;
        }
    }

    /**
     * @param  array<mixed>  $payload
     * @return list<array{vehicle_name: string, device_name: string}>
     */
    private function normalizeDevices(array $payload): array
    {
        $devices = [];

        $walk = function (array $items) use (&$walk, &$devices): void {
            if (array_key_exists('vehicleName', $items) || array_key_exists('deviceName', $items)) {
                $vehicleName = trim((string) ($items['vehicleName'] ?? ''));
                $deviceName = trim((string) ($items['deviceName'] ?? ''));

                if ($vehicleName === '' || $deviceName === '') {
                    throw new ExternalFleetApiException('The GPS provider returned incomplete device data.');
                }

                if (mb_strlen($vehicleName) > 200 || mb_strlen($deviceName) > 200) {
                    throw new ExternalFleetApiException('The GPS provider returned device data that is too long.');
                }

                $devices[$deviceName] = [
                    'vehicle_name' => $vehicleName,
                    'device_name' => $deviceName,
                ];

                return;
            }

            foreach ($items as $item) {
                if (is_array($item)) {
                    $walk($item);
                }
            }
        };

        $walk($payload);

        return array_values($devices);
    }

    /**
     * @param  array<mixed>  $payload
     * @return array{
     *     datetime: string,
     *     mileage: float,
     *     heading: int,
     *     speed: float,
     *     latitude: float,
     *     longitude: float,
     *     acc: int,
     *     status_icon: int
     * }|null
     */
    private function normalizeLatestPosition(array $payload, string $deviceName): ?array
    {
        $position = null;

        $walk = function (array $items) use (&$walk, &$position, $deviceName): void {
            if ($position !== null) {
                return;
            }

            if (
                array_key_exists('deviceName', $items)
                && (string) $items['deviceName'] === $deviceName
            ) {
                $position = $items;

                return;
            }

            foreach ($items as $item) {
                if (is_array($item)) {
                    $walk($item);
                }
            }
        };

        $walk($payload);

        if (
            ! is_array($position)
            || ! is_numeric(Arr::get($position, 'latitude'))
            || ! is_numeric(Arr::get($position, 'longitude'))
        ) {
            return null;
        }

        return [
            'datetime' => trim((string) Arr::get($position, 'datetime', '')),
            'mileage' => (float) Arr::get($position, 'mileage', 0),
            'heading' => (int) Arr::get($position, 'heading', 0),
            'speed' => (float) Arr::get($position, 'speed', 0),
            'latitude' => (float) Arr::get($position, 'latitude'),
            'longitude' => (float) Arr::get($position, 'longitude'),
            'acc' => (int) Arr::get($position, 'acc', 0),
            'status_icon' => (int) Arr::get($position, 'statusIcon', 0),
        ];
    }

    /**
     * @param  array<mixed>  $payload
     */
    private function isAccessTokenError(array $payload): bool
    {
        return (int) ($payload['errcode'] ?? 0) === self::ACCESS_TOKEN_ERROR;
    }

    private function tokenCacheKey(Customer $customer): string
    {
        $credentialFingerprint = hash('sha256', "{$customer->username}\0{$customer->password}");

        return "total-kilat-gps:customer:{$customer->id}:token:{$credentialFingerprint}";
    }

    private function positionCacheKey(Customer $customer, string $deviceName): string
    {
        return "total-kilat-gps:customer:{$customer->id}:position:".hash('sha256', $deviceName);
    }

    private function grantType(): string
    {
        return (string) config('services.total_kilat_gps.grant_type', 'totalkilatgps');
    }
}
