<?php

namespace App\Http\Controllers;

use App\Exceptions\ExternalFleetApiException;
use App\Http\Requests\Report\FleetHistoryRequest;
use App\Models\Customer;
use App\Services\FleetHistoryService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class FleetHistoryController extends Controller
{
    public function __construct(
        private readonly FleetHistoryService $fleetHistoryService,
    ) {}

    public function index(): View
    {
        return $this->view([
            'filters' => [
                'customer_id' => old('customer_id', ''),
                'device_name' => old('device_name', ''),
                'start_time' => old('start_time', now()->subDay()->format('Y-m-d\TH:i')),
                'end_time' => old('end_time', now()->format('Y-m-d\TH:i')),
            ],
            'histories' => null,
        ]);
    }

    public function generate(FleetHistoryRequest $request): View
    {
        $validated = $request->validated();
        $customer = Customer::query()->findOrFail($validated['customer_id']);
        $startTime = CarbonImmutable::createFromFormat('Y-m-d\TH:i', $validated['start_time']);
        $endTime = CarbonImmutable::createFromFormat('Y-m-d\TH:i', $validated['end_time']);

        try {
            $histories = $this->fleetHistoryService->generate(
                $customer,
                $validated['device_name'],
                $startTime,
                $endTime,
            );
        } catch (ExternalFleetApiException $exception) {
            Log::warning('Fleet history could not be loaded.', [
                'customer_id' => $customer->id,
                'device_name' => $validated['device_name'],
                'reason' => $exception->getMessage(),
            ]);

            return $this->view([
                'filters' => $validated,
                'histories' => null,
                'errorMessage' => $exception->getMessage(),
            ]);
        }

        return $this->view([
            'filters' => $validated,
            'histories' => $histories,
        ]);
    }

    public function fleets(Request $request): JsonResponse
    {
        $fleets = $this->fleetHistoryService
            ->getFleetsForCustomer(trim((string) $request->query('customer_id')))
            ->map(fn ($fleet): array => [
                'value' => $fleet->device_name,
                'label' => "{$fleet->vehicle_name} ({$fleet->device_name})",
            ])
            ->values();

        return response()->json(['data' => $fleets]);
    }

    /**
     * @param  array{filters: array<string, mixed>, histories: list<array<string, mixed>>|null, errorMessage?: string}  $data
     */
    private function view(array $data): View
    {
        return view('pages.fleet-histories.index', [
            ...$data,
            'customers' => $this->fleetHistoryService->getCustomers(),
            'fleets' => $this->fleetHistoryService->getFleetsForCustomer(
                (string) ($data['filters']['customer_id'] ?? ''),
            ),
        ]);
    }
}
