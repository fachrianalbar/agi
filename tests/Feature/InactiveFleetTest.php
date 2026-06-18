<?php

namespace Tests\Feature;

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class InactiveFleetTest extends TestCase
{
    use RefreshDatabase;

    public function test_inactive_index_renders_customer_list_without_exposing_password(): void
    {
        $customer = $this->createCustomer();

        $this->get(route('inactive.index'))
            ->assertOk()
            ->assertSee('Non Active Fleet')
            ->assertSee(route('inactive.data'))
            ->assertSee('inactiveFleetModal')
            ->assertSee('inactiveSnapshotModal')
            ->assertSee('Copy Image')
            ->assertSee('Share WhatsApp')
            ->assertDontSee('Download PNG')
            ->assertDontSee('Open Image')
            ->assertDontSee('Download & Open WhatsApp', false)
            ->assertDontSee($customer->password);
    }

    public function test_inactive_customer_data_only_lists_customers_with_gps_credentials(): void
    {
        $customer = $this->createCustomer();
        $this->createCustomer([
            'name' => 'No Credential',
            'username' => '',
            'email' => 'no-credential@example.com',
        ]);

        $this->getJson(route('inactive.data', [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'search' => ['value' => '', 'regex' => 'false'],
            'columns' => [
                [
                    'data' => 'name',
                    'name' => 'name',
                    'searchable' => 'true',
                    'orderable' => 'true',
                    'search' => ['value' => '', 'regex' => 'false'],
                ],
            ],
        ]))
            ->assertOk()
            ->assertJsonPath('recordsFiltered', 1)
            ->assertSee($customer->name)
            ->assertSee('js-create-inactive-snapshot')
            ->assertSee('Snapshot')
            ->assertDontSee('No Credential')
            ->assertDontSee($customer->password);
    }

    public function test_inactive_fleets_are_loaded_with_cached_customer_token(): void
    {
        $customer = $this->createCustomer();
        $tokenRequests = 0;
        $inactiveRequests = 0;

        Http::fake(function (Request $request) use (&$tokenRequests, &$inactiveRequests, $customer) {
            $query = $this->queryParameters($request);

            if (str_ends_with(parse_url($request->url(), PHP_URL_PATH), '/token')) {
                $tokenRequests++;
                $this->assertSame('totalkilatgps', $query['grant_type']);
                $this->assertSame($customer->username, $query['account_name']);
                $this->assertSame($customer->password, $query['account_password']);

                return Http::response([
                    'access_token' => 'inactive-token',
                    'expires_in' => 3600,
                ]);
            }

            $inactiveRequests++;
            $this->assertStringEndsWith('/inactive', parse_url($request->url(), PHP_URL_PATH));
            $this->assertSame('totalkilatgps', $query['grant_type']);
            $this->assertSame('inactive-token', $query['access_token']);

            return Http::response([[
                [
                    'vehicle_name' => 'KT 8612 WG',
                    'datetime' => '2026-06-18 09:11:02',
                    'latitude' => -2.376934,
                    'longitude' => 116.476822,
                    'location' => 'Binturung,Pamukan Utara,Kota Baru,Kalimantan Selatan',
                ],
            ]]);
        });

        $this->getJson(route('inactive.vehicles', $customer))
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.vehicle_name', 'KT 8612 WG')
            ->assertJsonPath('data.0.datetime', '2026-06-18 09:11:02')
            ->assertJsonPath('data.0.latitude', -2.376934)
            ->assertJsonPath('data.0.longitude', 116.476822)
            ->assertJsonPath('data.0.location', 'Binturung,Pamukan Utara,Kota Baru,Kalimantan Selatan')
            ->assertJsonMissingPath('data.0.device_name')
            ->assertJsonMissingPath('data.0.status')
            ->assertJsonMissingPath('data.0.remark')
            ->assertDontSee('inactive-token')
            ->assertDontSee($customer->password);

        $this->getJson(route('inactive.vehicles', $customer))
            ->assertOk()
            ->assertJsonPath('meta.total', 1);

        $this->assertSame(1, $tokenRequests);
        $this->assertSame(2, $inactiveRequests);
        $this->assertSame('inactive-token', Cache::get($this->tokenCacheKey($customer)));
    }

    public function test_inactive_fleet_lookup_refreshes_rejected_token_once(): void
    {
        $customer = $this->createCustomer();
        $tokenRequests = 0;
        $inactiveRequests = 0;

        Http::fake(function (Request $request) use (&$tokenRequests, &$inactiveRequests) {
            if (str_ends_with(parse_url($request->url(), PHP_URL_PATH), '/token')) {
                $tokenRequests++;

                return Http::response([
                    'access_token' => "inactive-token-{$tokenRequests}",
                    'expires_in' => 3600,
                ]);
            }

            $inactiveRequests++;

            if ($inactiveRequests === 1) {
                return Http::response([
                    'errcode' => 30001,
                    'errmsg' => 'access token error',
                ]);
            }

            return Http::response([[
                [
                    'vehicle_name' => 'B 1075 DFP',
                    'datetime' => '2026-06-18 09:11:02',
                    'latitude' => -2.376934,
                    'longitude' => 116.476822,
                    'location' => 'Binturung,Pamukan Utara,Kota Baru,Kalimantan Selatan',
                ],
            ]]);
        });

        $this->getJson(route('inactive.vehicles', $customer))
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.vehicle_name', 'B 1075 DFP');

        $this->assertSame(2, $tokenRequests);
        $this->assertSame(2, $inactiveRequests);
        $this->assertSame('inactive-token-2', Cache::get($this->tokenCacheKey($customer)));
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
