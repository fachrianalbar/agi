<?php

namespace App\Services;

use App\Exceptions\ExternalFleetApiException;
use App\Models\Customer;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

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

    private function grantType(): string
    {
        return (string) config('services.total_kilat_gps.grant_type', 'totalkilatgps');
    }
}
