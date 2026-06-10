<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Fleet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FleetHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_fleet_history_index_renders_filter_form(): void
    {
        $customer = $this->createCustomer();
        $this->createFleet($customer);

        $this->get(route('fleet-histories.index'))
            ->assertOk()
            ->assertSee('Fleet History')
            ->assertSee('History Parameters')
            ->assertSee('Maximum range is 48 hours')
            ->assertSee('data-dependent-url', false)
            ->assertSee($customer->name)
            ->assertSee($customer->username)
            ->assertDontSee($customer->password);
    }

    public function test_fleet_options_are_scoped_to_selected_customer_without_exposing_ids(): void
    {
        $customer = $this->createCustomer();
        $otherCustomer = $this->createCustomer([
            'name' => 'Other Customer',
            'username' => 'other',
            'email' => 'other@example.com',
        ]);
        $fleet = $this->createFleet($customer, 'B 2029 SJO', '60697058200041');
        $otherFleet = $this->createFleet($otherCustomer, 'B 1071 DFP', '42976836');

        $this->getJson(route('fleet-histories.fleets', ['customer_id' => $customer->id]))
            ->assertOk()
            ->assertJsonPath('data.0.value', $fleet->device_name)
            ->assertJsonPath('data.0.label', 'B 2029 SJO (60697058200041)')
            ->assertDontSee($fleet->id)
            ->assertDontSee($otherFleet->device_name);
    }

    public function test_fleet_history_rejects_ranges_greater_than_48_hours(): void
    {
        $customer = $this->createCustomer();
        $this->createFleet($customer, 'B 2029 SJO', '60697058200041');

        $this->from(route('fleet-histories.index'))
            ->post(route('fleet-histories.generate'), [
                'customer_id' => $customer->id,
                'device_name' => '60697058200041',
                'start_time' => '2026-06-01T00:00',
                'end_time' => '2026-06-03T00:01',
            ])
            ->assertRedirect(route('fleet-histories.index'))
            ->assertSessionHasErrors('end_time');
    }

    public function test_fleet_history_is_loaded_from_gps_provider(): void
    {
        $customer = $this->createCustomer();
        $this->createFleet($customer, 'B 2029 SJO', '867724070029407');
        $tokenRequests = 0;
        $historyRequests = 0;

        Http::fake(function (Request $request) use (&$tokenRequests, &$historyRequests, $customer) {
            $query = $this->queryParameters($request);

            if (str_ends_with(parse_url($request->url(), PHP_URL_PATH), '/token')) {
                $tokenRequests++;
                $this->assertSame($customer->username, $query['account_name']);
                $this->assertSame($customer->password, $query['account_password']);

                return Http::response([
                    'access_token' => 'device-history-token',
                    'expires_in' => 3600,
                ]);
            }

            $historyRequests++;
            $this->assertSame('867724070029407', $query['device_name']);
            $this->assertSame('2026-06-01 00:00:00', $query['start_time']);
            $this->assertSame('2026-06-03 00:00:00', $query['end_time']);
            $this->assertSame('device-history-token', $query['access_token']);

            return Http::response([[
                [
                    'datetime' => '2026-06-01 00:00:07',
                    'dateTimeUTC' => '2026-05-31 16:00:07',
                    'localDateTime' => '2026-06-01 00:00:07',
                    'gpsLocation' => 'Jalan H.M. Ardans,Gunung Kelua,Samarinda Ulu,Samarinda,Kalimantan Timur',
                    'gpsValid' => true,
                    'lon' => 117.146476,
                    'lat' => -0.458407,
                    'speed' => 0,
                    'direction' => 0,
                    'engine' => 1,
                    'odometer' => 4220.88,
                    'temperature' => '0',
                    'maxSpeed' => 0,
                    'overspeed' => false,
                    'harshAcceleration' => false,
                    'harshBraking' => false,
                    'harshCornering' => false,
                ],
                [
                    'datetime' => '2026-06-01 00:00:37',
                    'dateTimeUTC' => '2026-05-31 16:00:37',
                    'localDateTime' => '2026-06-01 00:00:37',
                    'gpsLocation' => 'Jalan H.M. Ardans,Gunung Kelua,Samarinda Ulu,Samarinda,Kalimantan Timur',
                    'gpsValid' => true,
                    'lon' => 117.146476,
                    'lat' => -0.458407,
                    'speed' => 0,
                    'direction' => 0,
                    'engine' => 1,
                    'odometer' => 4220.88,
                    'temperature' => '0',
                    'maxSpeed' => 0,
                    'overspeed' => false,
                    'harshAcceleration' => false,
                    'harshBraking' => false,
                    'harshCornering' => false,
                ],
            ]]);
        });

        $this->post(route('fleet-histories.generate'), [
            'customer_id' => $customer->id,
            'device_name' => '867724070029407',
            'start_time' => '2026-06-01T00:00',
            'end_time' => '2026-06-03T00:00',
        ])
            ->assertOk()
            ->assertSee('Fleet History Result')
            ->assertSee('01 Juni 2026 00:00:07')
            ->assertSee('Jalan H.M. Ardans,Gunung Kelua,Samarinda Ulu,Samarinda,Kalimantan Timur')
            ->assertSee('-0.458407')
            ->assertSee('117.146476')
            ->assertSee('4,220.88 km')
            ->assertSee('Valid')
            ->assertSee('On')
            ->assertSee('data-playback-open', false)
            ->assertSee('fleet-history-playback-data', false)
            ->assertSee('<option value="20">20x</option>', false)
            ->assertSee('"latitude":-0.458407', false)
            ->assertSee('"longitude":117.146476', false)
            ->assertDontSee('device-history-token');

        $this->assertSame(1, $tokenRequests);
        $this->assertSame(1, $historyRequests);
    }

    public function test_fleet_history_refreshes_rejected_token_once(): void
    {
        $customer = $this->createCustomer();
        $this->createFleet($customer, 'B 2029 SJO', '867724070029407');
        $tokenRequests = 0;
        $historyRequests = 0;

        Http::fake(function (Request $request) use (&$tokenRequests, &$historyRequests) {
            if (str_ends_with(parse_url($request->url(), PHP_URL_PATH), '/token')) {
                $tokenRequests++;

                return Http::response([
                    'access_token' => "token-{$tokenRequests}",
                    'expires_in' => 3600,
                ]);
            }

            $historyRequests++;

            if ($historyRequests === 1) {
                return Http::response([
                    'errcode' => 30001,
                    'errmsg' => 'access token error',
                ]);
            }

            return Http::response([[
                [
                    'datetime' => '2026-06-01 00:00:07',
                    'gpsLocation' => 'Jalan H.M. Ardans',
                    'gpsValid' => true,
                    'lon' => 117.146476,
                    'lat' => -0.458407,
                    'speed' => 0,
                    'direction' => 0,
                    'engine' => 1,
                    'odometer' => 4220.88,
                    'temperature' => '0',
                    'maxSpeed' => 0,
                    'overspeed' => false,
                    'harshAcceleration' => false,
                    'harshBraking' => false,
                    'harshCornering' => false,
                ],
            ]]);
        });

        $this->post(route('fleet-histories.generate'), [
            'customer_id' => $customer->id,
            'device_name' => '867724070029407',
            'start_time' => '2026-06-01T00:00',
            'end_time' => '2026-06-03T00:00',
        ])
            ->assertOk()
            ->assertSee('Jalan H.M. Ardans');

        $this->assertSame(2, $tokenRequests);
        $this->assertSame(2, $historyRequests);
    }

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
        string $vehicleName = 'B 2029 SJO',
        string $deviceName = '867724070029407',
    ): Fleet {
        return Fleet::query()->create([
            'customer_id' => $customer->id,
            'vehicle_name' => $vehicleName,
            'device_name' => $deviceName,
            'is_active' => true,
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function queryParameters(Request $request): array
    {
        parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

        return $query;
    }
}
