<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Fleet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SummaryReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_summary_report_index_renders_filter_form(): void
    {
        $customer = $this->createCustomer();
        $this->createFleet($customer);

        $this->get(route('summary-reports.index'))
            ->assertOk()
            ->assertSee('Summary Report')
            ->assertSee('Report Parameters')
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

        $this->getJson(route('summary-reports.fleets', ['customer_id' => $customer->id]))
            ->assertOk()
            ->assertJsonPath('data.0.value', $fleet->device_name)
            ->assertJsonPath('data.0.label', 'B 2029 SJO (60697058200041)')
            ->assertDontSee($fleet->id)
            ->assertDontSee($otherFleet->device_name);
    }

    public function test_summary_report_is_loaded_from_gps_provider(): void
    {
        $customer = $this->createCustomer();
        $this->createFleet($customer, 'B 2029 SJO', '60697058200041');
        $tokenRequests = 0;
        $reportRequests = 0;

        Http::fake(function (Request $request) use (&$tokenRequests, &$reportRequests, $customer) {
            $query = $this->queryParameters($request);

            if (str_ends_with(parse_url($request->url(), PHP_URL_PATH), '/token')) {
                $tokenRequests++;
                $this->assertSame($customer->username, $query['account_name']);
                $this->assertSame($customer->password, $query['account_password']);

                return Http::response([
                    'access_token' => 'daily-summary-token',
                    'expires_in' => 3600,
                ]);
            }

            $reportRequests++;
            $this->assertSame('60697058200041', $query['device_name']);
            $this->assertSame('2026-06-01 00:00:00', $query['start_time']);
            $this->assertSame('2026-06-09 23:59:00', $query['end_time']);
            $this->assertSame('daily-summary-token', $query['access_token']);

            return Http::response([[
                [
                    'vehicle_name' => 'B 2029 SJO',
                    'device_name' => '60697058200041',
                    'datetime' => '2026-06-08',
                    'original_gpsdate' => '2026-06-07T16:00:00',
                    'start_time' => '2026-06-08 06:52:43',
                    'start_location' => 'Jalan Gajah Mada,Jawa,Samarinda Ulu,Samarinda,Kalimantan Timur',
                    'end_time' => '2026-06-08 23:59:13',
                    'end_location' => 'Jalan Gunung Cermai,Jawa,Samarinda Ulu,Samarinda,Kalimantan Timur',
                    'runing_time' => 7552,
                    'idle_time' => 2561,
                    'travelling' => 10113,
                    'parking' => 76287,
                    'odometer' => 43.5615234375,
                    'usage' => 0,
                    'max_speed' => 52.4,
                    'geofence_times' => 0,
                ],
                [
                    'vehicle_name' => 'B 2029 SJO',
                    'device_name' => '60697058200041',
                    'datetime' => '2026-06-07',
                    'original_gpsdate' => '2026-06-06T16:00:00',
                    'start_time' => null,
                    'start_location' => '',
                    'end_time' => null,
                    'end_location' => '',
                    'runing_time' => 0,
                    'idle_time' => 0,
                    'travelling' => 0,
                    'parking' => 86400,
                    'odometer' => 0,
                    'usage' => 0,
                    'max_speed' => 1.1,
                    'geofence_times' => 0,
                ],
            ]]);
        });

        $this->post(route('summary-reports.generate'), [
            'customer_id' => $customer->id,
            'device_name' => '60697058200041',
            'start_time' => '2026-06-01T00:00',
            'end_time' => '2026-06-09T23:59',
        ])
            ->assertOk()
            ->assertSee('B 2029 SJO')
            ->assertSee('08 Juni 2026')
            ->assertSee('08 Juni 2026 23:59:13')
            ->assertSee('Jalan Gajah Mada,Jawa,Samarinda Ulu,Samarinda,Kalimantan Timur')
            ->assertSee('02:05:52')
            ->assertSee('00:42:41')
            ->assertSee('43.56 km')
            ->assertSee('52.4 km/h')
            ->assertDontSee('daily-summary-token');

        $this->assertSame(1, $tokenRequests);
        $this->assertSame(1, $reportRequests);
    }

    public function test_summary_report_refreshes_rejected_token_once(): void
    {
        $customer = $this->createCustomer();
        $this->createFleet($customer, 'B 2029 SJO', '60697058200041');
        $tokenRequests = 0;
        $reportRequests = 0;

        Http::fake(function (Request $request) use (&$tokenRequests, &$reportRequests) {
            if (str_ends_with(parse_url($request->url(), PHP_URL_PATH), '/token')) {
                $tokenRequests++;

                return Http::response([
                    'access_token' => "token-{$tokenRequests}",
                    'expires_in' => 3600,
                ]);
            }

            $reportRequests++;

            if ($reportRequests === 1) {
                return Http::response([
                    'errcode' => 30001,
                    'errmsg' => 'access token error',
                ]);
            }

            return Http::response([[
                [
                    'vehicle_name' => 'B 2029 SJO',
                    'device_name' => '60697058200041',
                    'datetime' => '2026-06-08',
                    'start_time' => null,
                    'start_location' => '',
                    'end_time' => null,
                    'end_location' => '',
                    'runing_time' => 0,
                    'idle_time' => 0,
                    'travelling' => 0,
                    'parking' => 86400,
                    'odometer' => 0,
                    'usage' => 0,
                    'max_speed' => 0,
                    'geofence_times' => 0,
                ],
            ]]);
        });

        $this->post(route('summary-reports.generate'), [
            'customer_id' => $customer->id,
            'device_name' => '60697058200041',
            'start_time' => '2026-06-01T00:00',
            'end_time' => '2026-06-09T23:59',
        ])
            ->assertOk()
            ->assertSee('B 2029 SJO');

        $this->assertSame(2, $tokenRequests);
        $this->assertSame(2, $reportRequests);
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
        string $deviceName = '60697058200041',
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
