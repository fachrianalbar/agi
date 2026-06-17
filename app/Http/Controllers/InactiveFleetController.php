<?php

namespace App\Http\Controllers;

use App\Exceptions\ExternalFleetApiException;
use App\Models\Customer;
use App\Services\TotalKilatGpsService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class InactiveFleetController extends Controller
{
    public function __construct(
        private readonly TotalKilatGpsService $gpsService,
    ) {}

    public function index(): View
    {
        return view('pages.inactive.index');
    }

    public function data(): JsonResponse
    {
        return DataTables::eloquent($this->customerQuery())
            ->addColumn(
                'action',
                fn (Customer $customer) => view('pages.inactive.columns.action', compact('customer'))->render(),
            )
            ->addColumn('location', fn (Customer $customer) => collect([$customer->city, $customer->country])->filter()->join(', ') ?: '—')
            ->filterColumn('location', function (Builder $query, string $keyword): void {
                $query->where(function (Builder $query) use ($keyword): void {
                    $query->where('city', 'like', "%{$keyword}%")
                        ->orWhere('country', 'like', "%{$keyword}%");
                });
            })
            ->only([
                'action',
                'name',
                'username',
                'email',
                'phone',
                'location',
            ])
            ->rawColumns(['action'])
            ->toJson();
    }

    public function vehicles(Customer $customer): JsonResponse
    {
        if (! $this->customerQuery()->whereKey($customer->id)->exists()) {
            abort(404);
        }

        try {
            $vehicles = $this->gpsService->getInactiveDevices($customer);
        } catch (ExternalFleetApiException $exception) {
            Log::warning('Inactive fleet data could not be loaded.', [
                'customer_id' => $customer->id,
                'reason' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => $exception->getMessage(),
            ], 502);
        }

        return response()->json([
            'data' => $vehicles,
            'meta' => [
                'customer' => [
                    'name' => $customer->name,
                    'username' => $customer->username,
                ],
                'total' => count($vehicles),
            ],
        ]);
    }

    private function customerQuery(): Builder
    {
        return Customer::query()
            ->where('is_active', true)
            ->whereNotNull('username')
            ->whereNotNull('password')
            ->where('username', '!=', '')
            ->where('password', '!=', '')
            ->select([
                'id',
                'name',
                'username',
                'email',
                'phone',
                'city',
                'country',
                'created_at',
            ]);
    }
}
