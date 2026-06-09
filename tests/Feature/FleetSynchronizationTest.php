<?php

namespace Tests\Feature;

use App\Http\Requests\Fleet\SyncFleetRequest;
use App\Models\Customer;
use App\Models\Fleet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class FleetSynchronizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_fleet_index_contains_customer_sync_modal_without_exposing_password(): void
    {
        $customer = $this->createCustomer();

        $this->get(route('fleets.index'))
            ->assertOk()
            ->assertSee('Synchronize Fleets')
            ->assertSee($customer->name)
            ->assertSee($customer->username)
            ->assertDontSee($customer->password);
    }

    public function test_fleets_are_upserted_and_access_token_is_cached_per_customer(): void
    {
        $customer = $this->createCustomer();
        $tokenRequests = 0;
        $deviceRequests = 0;

        Http::fake(function (Request $request) use (&$tokenRequests, &$deviceRequests, $customer) {
            $query = $this->queryParameters($request);

            if (str_ends_with(parse_url($request->url(), PHP_URL_PATH), '/token')) {
                $tokenRequests++;
                $this->assertSame('totalkilatgps', $query['grant_type']);
                $this->assertSame($customer->username, $query['account_name']);
                $this->assertSame($customer->password, $query['account_password']);

                return Http::response([
                    'access_token' => 'cached-access-token',
                    'expires_in' => 3600,
                ]);
            }

            $deviceRequests++;
            $this->assertSame('cached-access-token', $query['access_token']);

            return Http::response([[
                [
                    'vehicleName' => $deviceRequests === 1 ? 'B 1071 DFP' : 'B 1071 DFP Updated',
                    'deviceName' => '42976836',
                ],
                [
                    'vehicleName' => 'B 1075 DFP',
                    'deviceName' => '42995737',
                ],
            ]]);
        });

        $this->postJson(route('fleets.sync'), [
            'customer_id' => $customer->id,
        ])
            ->assertOk()
            ->assertJsonPath('data.created', 2)
            ->assertJsonPath('data.updated', 0);

        $this->postJson(route('fleets.sync'), [
            'customer_id' => $customer->id,
        ])
            ->assertOk()
            ->assertJsonPath('data.created', 0)
            ->assertJsonPath('data.updated', 1)
            ->assertJsonPath('data.unchanged', 1);

        $this->assertSame(1, $tokenRequests);
        $this->assertSame(2, $deviceRequests);
        $this->assertDatabaseCount('fleets', 2);
        $this->assertDatabaseHas('fleets', [
            'customer_id' => $customer->id,
            'device_name' => '42976836',
            'vehicle_name' => 'B 1071 DFP Updated',
        ]);
        $this->assertSame(
            $customer->password,
            Customer::query()->findOrFail($customer->id)->password,
        );
    }

    public function test_access_token_is_refreshed_once_when_device_api_rejects_it(): void
    {
        $customer = $this->createCustomer();
        $tokenRequests = 0;
        $deviceRequests = 0;

        Http::fake(function (Request $request) use (&$tokenRequests, &$deviceRequests) {
            if (str_ends_with(parse_url($request->url(), PHP_URL_PATH), '/token')) {
                $tokenRequests++;

                return Http::response([
                    'access_token' => "access-token-{$tokenRequests}",
                    'expires_in' => 3600,
                ]);
            }

            $deviceRequests++;

            if ($deviceRequests === 1) {
                return Http::response([
                    'errcode' => 30001,
                    'errmsg' => 'access token error',
                ]);
            }

            return Http::response([[
                [
                    'vehicleName' => 'B 1071 DFP',
                    'deviceName' => '42976836',
                ],
            ]]);
        });

        $this->postJson(route('fleets.sync'), [
            'customer_id' => $customer->id,
        ])
            ->assertOk()
            ->assertJsonPath('data.created', 1);

        $this->assertSame(2, $tokenRequests);
        $this->assertSame(2, $deviceRequests);
        $this->assertSame(
            'access-token-2',
            Cache::get($this->tokenCacheKey($customer)),
        );
    }

    public function test_double_encoded_device_response_is_synchronized(): void
    {
        $customer = $this->createCustomer();
        $devices = [[
            [
                'vehicleName' => 'B 1071 DFP',
                'deviceName' => '42976836',
            ],
            [
                'vehicleName' => 'B 1075 DFP',
                'deviceName' => '42995737',
            ],
        ]];

        Http::fake([
            '*/token*' => Http::response([
                'access_token' => 'access-token',
                'expires_in' => 3600,
            ]),
            '*/deviceInfo*' => Http::response(
                json_encode(json_encode($devices)),
                200,
                ['Content-Type' => 'application/json; charset=utf-8'],
            ),
        ]);

        $this->postJson(route('fleets.sync'), [
            'customer_id' => $customer->id,
        ])
            ->assertOk()
            ->assertJsonPath('data.created', 2);

        $this->assertDatabaseHas('fleets', [
            'customer_id' => $customer->id,
            'vehicle_name' => 'B 1071 DFP',
            'device_name' => '42976836',
        ]);
    }

    public function test_sync_request_requires_an_active_customer(): void
    {
        $customer = $this->createCustomer(['is_active' => false]);
        $request = SyncFleetRequest::create(route('fleets.sync'), 'POST', [
            'customer_id' => $customer->id,
        ]);
        $validator = Validator::make($request->all(), $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('customer_id', $validator->errors()->toArray());
        $this->assertDatabaseCount('fleets', 0);
    }

    public function test_existing_soft_deleted_fleet_is_restored_during_sync(): void
    {
        $customer = $this->createCustomer();
        $fleet = Fleet::query()->create([
            'customer_id' => $customer->id,
            'vehicle_name' => 'Old Vehicle',
            'device_name' => '42976836',
            'is_active' => false,
        ]);
        $fleet->delete();

        Http::fake([
            '*/token*' => Http::response([
                'access_token' => 'access-token',
                'expires_in' => 3600,
            ]),
            '*/deviceInfo*' => Http::response([[
                [
                    'vehicleName' => 'B 1071 DFP',
                    'deviceName' => '42976836',
                ],
            ]]),
        ]);

        $this->postJson(route('fleets.sync'), [
            'customer_id' => $customer->id,
        ])
            ->assertOk()
            ->assertJsonPath('data.updated', 1);

        $this->assertDatabaseCount('fleets', 1);
        $this->assertDatabaseHas('fleets', [
            'id' => $fleet->id,
            'vehicle_name' => 'B 1071 DFP',
            'is_active' => true,
            'deleted_at' => null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createCustomer(array $overrides = []): Customer
    {
        return Customer::query()->create(array_merge([
            'name' => 'AGI Customer',
            'username' => 'agi',
            'email' => 'agi@example.com',
            'password' => 'plain-api-password',
            'is_active' => true,
        ], $overrides));
    }

    /**
     * @return array<string, string>
     */
    private function queryParameters(Request $request): array
    {
        parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

        return $query;
    }

    private function tokenCacheKey(Customer $customer): string
    {
        $fingerprint = hash('sha256', "{$customer->username}\0{$customer->password}");

        return "total-kilat-gps:customer:{$customer->id}:token:{$fingerprint}";
    }
}
