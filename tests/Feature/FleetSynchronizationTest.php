<?php

namespace Tests\Feature;

use App\Http\Requests\Fleet\SyncFleetRequest;
use App\Models\Customer;
use App\Models\Fleet;
use App\Models\User;
use App\Services\FleetService;
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
            ->assertSee('fleetMapModal')
            ->assertSee('data-map-address', false)
            ->assertDontSee('Fleet Status')
            ->assertSee('Address')
            ->assertSee('OpenStreetMap contributors')
            ->assertSee($customer->name)
            ->assertSee($customer->username)
            ->assertDontSee($customer->password);
    }

    public function test_fleet_form_stores_fuel_sensor_information(): void
    {
        $customer = $this->createCustomer();

        $this->get(route('fleets.create'))
            ->assertOk()
            ->assertSee('Fuel Sensor')
            ->assertSee('Fuel Sensor Status')
            ->assertSee('fuel_sensor_status', false)
            ->assertSee('fuel_sensor_installed_at', false);

        $this->post(route('fleets.store'), [
            'customer_id' => $customer->id,
            'vehicle_name' => 'B 2029 SJO',
            'device_name' => '60697058200041',
            'has_fuel_sensor' => '1',
            'fuel_sensor_installed_at' => '2026-06-10',
            'fuel_sensor_status' => 'active',
            'is_active' => '1',
        ])
            ->assertRedirect(route('fleets.index'));

        $this->assertDatabaseHas('fleets', [
            'customer_id' => $customer->id,
            'vehicle_name' => 'B 2029 SJO',
            'device_name' => '60697058200041',
            'has_fuel_sensor' => 1,
            'fuel_sensor_installed_at' => '2026-06-10 00:00:00',
            'fuel_sensor_status' => 'active',
        ]);
    }

    public function test_fleet_form_requires_installation_date_when_fuel_sensor_is_enabled(): void
    {
        $customer = $this->createCustomer();

        $this->from(route('fleets.create'))
            ->post(route('fleets.store'), [
                'customer_id' => $customer->id,
                'vehicle_name' => 'B 2029 SJO',
                'device_name' => '60697058200041',
                'has_fuel_sensor' => '1',
                'fuel_sensor_installed_at' => '',
                'is_active' => '1',
            ])
            ->assertRedirect(route('fleets.create'))
            ->assertSessionHasErrors('fuel_sensor_installed_at');

        $this->assertDatabaseCount('fleets', 0);
    }

    public function test_fleet_update_clears_installation_date_when_fuel_sensor_is_disabled(): void
    {
        $customer = $this->createCustomer();
        $fleet = $this->createFleet($customer, 'B 2029 SJO', '60697058200041');
        $fleet->update([
            'has_fuel_sensor' => true,
            'fuel_sensor_installed_at' => '2026-06-10',
            'fuel_sensor_status' => 'active',
        ]);

        $this->put(route('fleets.update', $fleet), [
            'customer_id' => $customer->id,
            'vehicle_name' => 'B 2029 SJO',
            'device_name' => '60697058200041',
            'has_fuel_sensor' => '0',
            'fuel_sensor_installed_at' => '2026-06-10',
            'fuel_sensor_status' => 'active',
            'is_active' => '1',
        ])
            ->assertRedirect(route('fleets.index'));

        $this->assertDatabaseHas('fleets', [
            'id' => $fleet->id,
            'has_fuel_sensor' => false,
            'fuel_sensor_installed_at' => null,
            'fuel_sensor_status' => 'inactive',
        ]);
    }

    public function test_fleet_fuel_sensor_status_can_be_updated(): void
    {
        $customer = $this->createCustomer();
        $fleet = $this->createFleet($customer, 'B 2029 SJO', '60697058200041');
        $fleet->update([
            'has_fuel_sensor' => true,
            'fuel_sensor_installed_at' => '2026-06-10',
            'fuel_sensor_status' => 'active',
        ]);

        $this->put(route('fleets.update', $fleet), [
            'customer_id' => $customer->id,
            'vehicle_name' => 'B 2029 SJO',
            'device_name' => '60697058200041',
            'has_fuel_sensor' => '1',
            'fuel_sensor_installed_at' => '2026-06-10',
            'fuel_sensor_status' => 'inactive',
            'is_active' => '1',
        ])
            ->assertRedirect(route('fleets.index'));

        $this->assertDatabaseHas('fleets', [
            'id' => $fleet->id,
            'has_fuel_sensor' => true,
            'fuel_sensor_status' => 'inactive',
        ]);
    }

    public function test_fleet_datatable_can_search_every_displayed_field(): void
    {
        $customer = $this->createCustomer();
        $otherCustomer = $this->createCustomer([
            'name' => 'Other Customer',
            'username' => 'other',
            'email' => 'other@example.com',
        ]);
        $fleet = $this->createFleet($customer, 'B 1071 DFP', '42976836');
        $this->createFleet($otherCustomer, 'B 1075 DFP', '42995737');

        $fleet->update([
            'latest_address' => 'Jalan Gajah Mada, Samarinda, Indonesia',
            'latest_mileage' => '39.727 km',
            'latest_vehicle_status' => 'Stop',
            'latest_engine' => 'Off',
            'latest_update' => '09 Juni 2026 22:44:31',
            'has_fuel_sensor' => true,
            'fuel_sensor_installed_at' => '2026-06-10',
            'fuel_sensor_status' => 'active',
        ]);

        foreach ([
            'B 1071 DFP',
            '42976836',
            'AGI Customer',
            'Yes',
            '2026-06-10',
            'Active',
            'Gajah Mada',
            '39.727 km',
            'Stop',
            'Off',
            '09 Juni 2026',
        ] as $keyword) {
            $response = $this->getJson(route('fleets.data', [
                'draw' => 1,
                'start' => 0,
                'length' => 10,
                'search' => ['value' => $keyword, 'regex' => 'false'],
                'columns' => [
                    [
                        'data' => 'vehicle_name',
                        'name' => 'vehicle_name',
                        'searchable' => 'true',
                        'orderable' => 'true',
                        'search' => ['value' => '', 'regex' => 'false'],
                    ],
                ],
            ]));

            $response
                ->assertOk()
                ->assertJsonPath('recordsFiltered', 1)
                ->assertSee('B 1071 DFP')
                ->assertDontSee('B 1075 DFP');
        }
    }

    public function test_customer_user_can_only_view_and_manage_own_fleets(): void
    {
        $customer = $this->createCustomer();
        $otherCustomer = $this->createCustomer([
            'name' => 'Other Customer',
            'username' => 'other',
            'email' => 'other@example.com',
        ]);
        $fleet = $this->createFleet($customer, 'B 1071 DFP', '42976836');
        $otherFleet = $this->createFleet($otherCustomer, 'B 1075 DFP', '42995737');
        $this->actingAs(User::factory()->create(['customer_id' => $customer->id]));

        $this->get(route('fleets.index'))
            ->assertOk()
            ->assertSee($customer->name)
            ->assertDontSee($otherCustomer->name);

        $this->getJson(route('fleets.data', [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'search' => ['value' => '', 'regex' => 'false'],
            'columns' => [[
                'data' => 'vehicle_name',
                'name' => 'vehicle_name',
                'searchable' => 'true',
                'orderable' => 'true',
                'search' => ['value' => '', 'regex' => 'false'],
            ]],
        ]))
            ->assertOk()
            ->assertJsonPath('recordsTotal', 1)
            ->assertSee($fleet->vehicle_name)
            ->assertDontSee($otherFleet->vehicle_name);

        $this->get(route('fleets.edit', $otherFleet))->assertForbidden();
        $this->postJson(route('fleets.sync'), ['customer_id' => $otherCustomer->id])->assertForbidden();
        $this->postJson(route('fleets.latest-positions'), [
            'devices' => [$this->positionRequestDevice($otherFleet)],
        ])
            ->assertOk()
            ->assertJsonPath('data', []);
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
                    'vehicleName' => 'B 1071 DFP',
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
            ->assertJsonPath('data.updated', 0)
            ->assertJsonPath('data.unchanged', 2);

        $this->assertSame(1, $tokenRequests);
        $this->assertSame(2, $deviceRequests);
        $this->assertDatabaseCount('fleets', 2);
        $this->assertDatabaseHas('fleets', [
            'customer_id' => $customer->id,
            'device_name' => '42976836',
            'vehicle_name' => 'B 1071 DFP',
        ]);
        $this->assertSame(
            $customer->password,
            Customer::query()->findOrFail($customer->id)->password,
        );
    }

    public function test_sync_removes_conflicting_vehicle_or_device_and_only_updates_the_matching_pair(): void
    {
        $customer = $this->createCustomer();
        $otherCustomer = $this->createCustomer([
            'name' => 'Other Customer',
            'username' => 'other',
            'email' => 'other@example.com',
        ]);
        $matchingFleet = $this->createFleet($customer, 'B 1071 DFP', '42976836');
        $matchingFleet->update([
            'has_fuel_sensor' => true,
            'fuel_sensor_installed_at' => '2026-06-10',
            'fuel_sensor_status' => 'active',
            'latest_address' => 'Jalan Gajah Mada',
            'is_active' => false,
        ]);
        $differentVehicle = $this->createFleet($customer, 'B 1075 DFP', '42976836');
        $differentDevice = $this->createFleet($customer, 'B 1071 DFP', '42995737');
        $otherCustomerFleet = $this->createFleet($otherCustomer, 'B 1071 DFP', '42976836');

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
            ->assertJsonPath('data.created', 0)
            ->assertJsonPath('data.updated', 1)
            ->assertJsonPath('data.deleted', 2);

        $this->assertSame(2, Fleet::query()->count());
        $this->assertDatabaseHas('fleets', [
            'id' => $matchingFleet->id,
            'vehicle_name' => 'B 1071 DFP',
            'device_name' => '42976836',
            'has_fuel_sensor' => true,
            'fuel_sensor_installed_at' => '2026-06-10 00:00:00',
            'fuel_sensor_status' => 'active',
            'latest_address' => 'Jalan Gajah Mada',
            'is_active' => true,
        ]);
        $this->assertSoftDeleted('fleets', ['id' => $differentVehicle->id]);
        $this->assertSoftDeleted('fleets', ['id' => $differentDevice->id]);
        $this->assertDatabaseHas('fleets', ['id' => $otherCustomerFleet->id, 'deleted_at' => null]);
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
            'vehicle_name' => 'B 1071 DFP',
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

    public function test_latest_positions_are_loaded_for_visible_fleets_and_cached(): void
    {
        $customer = $this->createCustomer();
        $firstFleet = $this->createFleet($customer, 'B 2029 SJO', '60697058200041');
        $secondFleet = $this->createFleet($customer, 'B 1071 DFP', '42976836');
        $tokenRequests = 0;
        $positionRequests = 0;
        $addressRequests = 0;

        Http::fake(function (Request $request) use (&$tokenRequests, &$positionRequests, &$addressRequests) {
            $query = $this->queryParameters($request);
            $host = parse_url($request->url(), PHP_URL_HOST);

            if ($host === 'nominatim.openstreetmap.org') {
                $addressRequests++;
                $this->assertSame('-0.47737', (string) $query['lat']);
                $this->assertSame('117.137335', (string) $query['lon']);

                return Http::response([
                    'display_name' => 'Jalan Pangeran Antasari, Samarinda, Kalimantan Timur, Indonesia',
                ]);
            }

            if (str_ends_with(parse_url($request->url(), PHP_URL_PATH), '/token')) {
                $tokenRequests++;

                return Http::response([
                    'access_token' => 'customer-access-token',
                    'expires_in' => 3600,
                ]);
            }

            $positionRequests++;
            $this->assertSame('customer-access-token', $query['access_token']);

            return Http::response(json_encode(json_encode([[
                [
                    'vehicleName' => $query['device_name'] === '60697058200041'
                        ? 'B 2029 SJO'
                        : 'B 1071 DFP',
                    'deviceName' => $query['device_name'],
                    'datetime' => '2026-06-09 20:35:07',
                    'mileage' => 10137.443,
                    'heading' => 65,
                    'speed' => 0.2,
                    'latitude' => -0.47737,
                    'longitude' => 117.137335,
                    'acc' => 0,
                    'statusIcon' => 2,
                ],
            ]])));
        });

        $devices = [
            $this->positionRequestDevice($firstFleet),
            $this->positionRequestDevice($secondFleet),
        ];
        $firstReference = $devices[0]['ref'];

        $this->postJson(route('fleets.latest-positions'), compact('devices'))
            ->assertOk()
            ->assertJsonPath("data.{$firstReference}.vehicle_status.text", 'Stop')
            ->assertJsonPath("data.{$firstReference}.vehicle_status.badge", 'danger')
            ->assertJsonPath("data.{$firstReference}.engine.text", 'Off')
            ->assertJsonPath("data.{$firstReference}.last_update.text", '09 Juni 2026 20:35:07')
            ->assertJsonPath(
                "data.{$firstReference}.address.text",
                'Jalan Pangeran Antasari, Samarinda, Kalimantan Timur, Indonesia',
            )
            ->assertJsonPath(
                "data.{$firstReference}.map.url",
                'https://maps.google.com/maps?q=-0.47737,117.137335&z=16&output=embed',
            )
            ->assertJsonPath("data.{$firstReference}.map.latitude", -0.47737)
            ->assertJsonPath("data.{$firstReference}.map.longitude", 117.137335)
            ->assertDontSee($firstFleet->id);

        $this->postJson(route('fleets.latest-positions'), compact('devices'))
            ->assertOk();

        $this->assertSame(1, $tokenRequests);
        $this->assertSame(2, $positionRequests);
        $this->assertSame(1, $addressRequests);
        $this->assertDatabaseHas('fleets', [
            'id' => $firstFleet->id,
            'latest_address' => 'Jalan Pangeran Antasari, Samarinda, Kalimantan Timur, Indonesia',
            'latest_mileage' => '10.137 km',
            'latest_vehicle_status' => 'Stop',
            'latest_engine' => 'Off',
            'latest_update' => '09 Juni 2026 20:35:07',
        ]);
    }

    public function test_latest_positions_return_saved_snapshot_when_provider_is_unavailable(): void
    {
        $customer = $this->createCustomer();
        $fleet = $this->createFleet($customer, 'B 2029 SJO', '60697058200041');
        $fleet->update([
            'latest_address' => 'Jalan Pangeran Antasari, Samarinda',
            'latest_mileage' => '10,137.443 km',
            'latest_vehicle_status' => 'Stop',
            'latest_engine' => 'Off',
            'latest_update' => '09 Juni 2026 20:35:07',
        ]);

        Http::fake([
            '*' => Http::response(['message' => 'provider down'], 500),
        ]);

        $devices = [$this->positionRequestDevice($fleet)];
        $reference = $devices[0]['ref'];

        $this->postJson(route('fleets.latest-positions'), compact('devices'))
            ->assertOk()
            ->assertJsonPath("data.{$reference}.address.text", 'Jalan Pangeran Antasari, Samarinda')
            ->assertJsonPath("data.{$reference}.mileage.text", '10.137 km')
            ->assertJsonPath("data.{$reference}.vehicle_status.text", 'Stop')
            ->assertJsonPath("data.{$reference}.vehicle_status.badge", 'danger')
            ->assertJsonPath("data.{$reference}.engine.text", 'Off')
            ->assertJsonPath("data.{$reference}.engine.badge", 'neutral')
            ->assertJsonPath("data.{$reference}.last_update.text", '09 Juni 2026 20:35:07')
            ->assertJsonPath("data.{$reference}.map.url", null);
    }

    public function test_latest_positions_use_separate_tokens_for_each_customer(): void
    {
        $firstCustomer = $this->createCustomer();
        $secondCustomer = $this->createCustomer([
            'name' => 'Second Customer',
            'username' => 'second',
            'email' => 'second@example.com',
            'password' => 'second-password',
        ]);
        $firstFleet = $this->createFleet($firstCustomer, 'Fleet One', 'device-one');
        $secondFleet = $this->createFleet($secondCustomer, 'Fleet Two', 'device-two');
        $tokenAccounts = [];
        $positionTokens = [];

        Http::fake(function (Request $request) use (&$tokenAccounts, &$positionTokens) {
            $query = $this->queryParameters($request);

            if (str_ends_with(parse_url($request->url(), PHP_URL_PATH), '/token')) {
                $tokenAccounts[] = $query['account_name'];

                return Http::response([
                    'access_token' => "token-{$query['account_name']}",
                    'expires_in' => 3600,
                ]);
            }

            $positionTokens[$query['device_name']] = $query['access_token'];

            return Http::response([[
                [
                    'vehicleName' => $query['device_name'],
                    'deviceName' => $query['device_name'],
                    'datetime' => '2026-06-09 20:35:07',
                    'mileage' => 1,
                    'latitude' => -6.2,
                    'longitude' => 106.8,
                    'acc' => 1,
                    'statusIcon' => 1,
                ],
            ]]);
        });

        $devices = [
            $this->positionRequestDevice($firstFleet),
            $this->positionRequestDevice($secondFleet),
        ];

        $this->postJson(route('fleets.latest-positions'), compact('devices'))
            ->assertOk()
            ->assertJsonCount(2, 'data');

        sort($tokenAccounts);
        $this->assertSame(['agi', 'second'], $tokenAccounts);
        $this->assertSame('token-agi', $positionTokens['device-one']);
        $this->assertSame('token-second', $positionTokens['device-two']);
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

    private function createFleet(
        Customer $customer,
        string $vehicleName,
        string $deviceName,
    ): Fleet {
        return Fleet::query()->create([
            'customer_id' => $customer->id,
            'vehicle_name' => $vehicleName,
            'device_name' => $deviceName,
            'is_active' => true,
        ]);
    }

    /**
     * @return array{ref: string, device_name: string}
     */
    private function positionRequestDevice(Fleet $fleet): array
    {
        return [
            'ref' => app(FleetService::class)->positionReference($fleet),
            'device_name' => $fleet->device_name,
        ];
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
