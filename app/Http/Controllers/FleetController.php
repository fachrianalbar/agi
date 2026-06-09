<?php

namespace App\Http\Controllers;

use App\Exceptions\ExternalFleetApiException;
use App\Http\Requests\Fleet\LatestFleetPositionRequest;
use App\Http\Requests\Fleet\StoreFleetRequest;
use App\Http\Requests\Fleet\SyncFleetRequest;
use App\Http\Requests\Fleet\UpdateFleetRequest;
use App\Models\Customer;
use App\Models\Fleet;
use App\Services\FleetService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class FleetController extends Controller
{
    public function __construct(
        private readonly FleetService $fleetService,
    ) {}

    /**
     * Display a listing of fleets.
     */
    public function index(): View
    {
        return view('pages.fleets.index', [
            'customers' => $this->fleetService->getSyncCustomers(),
        ]);
    }

    /**
     * Return DataTables JSON for fleet listing.
     */
    public function data(): JsonResponse
    {
        return DataTables::eloquent($this->fleetService->getDataTableQuery())
            ->addColumn(
                'vehicle_name',
                fn (Fleet $fleet) => view('pages.fleets.columns.vehicle_name', compact('fleet'))->render(),
            )
            ->addColumn(
                'customer_name',
                fn (Fleet $fleet) => $fleet->customer?->name ?? '—',
            )
            ->addColumn(
                'mileage',
                fn (Fleet $fleet) => $this->positionCell($fleet, 'mileage'),
            )
            ->addColumn(
                'vehicle_status',
                fn (Fleet $fleet) => $this->positionCell($fleet, 'vehicle_status'),
            )
            ->addColumn(
                'engine',
                fn (Fleet $fleet) => $this->positionCell($fleet, 'engine'),
            )
            ->addColumn(
                'last_update',
                fn (Fleet $fleet) => $this->positionCell($fleet, 'last_update'),
            )
            ->addColumn(
                'action',
                fn (Fleet $fleet) => view('pages.fleets.columns.action', [
                    'fleet' => $fleet,
                    'positionReference' => $this->fleetService->positionReference($fleet),
                ])->render(),
            )
            ->filterColumn('customer_name', function (Builder $query, string $keyword): void {
                $query->whereHas('customer', function (Builder $q) use ($keyword): void {
                    $q->where('name', 'like', "%{$keyword}%");
                });
            })
            ->only([
                'action',
                'vehicle_name',
                'device_name',
                'customer_name',
                'mileage',
                'vehicle_status',
                'engine',
                'last_update',
            ])
            ->rawColumns([
                'action',
                'vehicle_name',
                'mileage',
                'vehicle_status',
                'engine',
                'last_update',
            ])
            ->toJson();
    }

    /**
     * Return latest GPS positions for the visible DataTable rows.
     */
    public function latestPositions(LatestFleetPositionRequest $request): JsonResponse
    {
        try {
            $positions = $this->fleetService->getLatestPositions(
                $request->validated('devices'),
            );
        } catch (ExternalFleetApiException $exception) {
            Log::warning('Latest fleet positions could not be loaded.', [
                'reason' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => $exception->getMessage(),
            ], 502);
        }

        return response()->json(['data' => $positions]);
    }

    /**
     * Show the form for creating a new fleet.
     */
    public function create(): View
    {
        $customers = Customer::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('pages.fleets.create', compact('customers'));
    }

    /**
     * Store a newly created fleet.
     */
    public function store(StoreFleetRequest $request): RedirectResponse
    {
        $fleet = $this->fleetService->create($request->validated());

        return redirect()
            ->route('fleets.index')
            ->with('success', "Fleet \"{$fleet->vehicle_name}\" created successfully.");
    }

    /**
     * Synchronize fleets for the selected customer.
     */
    public function sync(SyncFleetRequest $request): RedirectResponse|JsonResponse
    {
        $customer = Customer::query()->findOrFail($request->validated('customer_id'));

        try {
            $summary = $this->fleetService->synchronize($customer);
        } catch (ExternalFleetApiException $exception) {
            Log::warning('Fleet synchronization failed.', [
                'customer_id' => $customer->id,
                'reason' => $exception->getMessage(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $exception->getMessage(),
                ], 502);
            }

            return redirect()
                ->route('fleets.index')
                ->withInput()
                ->with('error', $exception->getMessage());
        }

        $message = sprintf(
            'Fleet synchronization for %s completed: %d created, %d updated, and %d unchanged.',
            $customer->name,
            $summary['created'],
            $summary['updated'],
            $summary['unchanged'],
        );

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'data' => $summary,
            ]);
        }

        return redirect()
            ->route('fleets.index')
            ->with('success', $message);
    }

    /**
     * Show the form for editing the specified fleet.
     */
    public function edit(Fleet $fleet): View
    {
        $customers = Customer::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('pages.fleets.edit', compact('fleet', 'customers'));
    }

    /**
     * Update the specified fleet.
     */
    public function update(UpdateFleetRequest $request, Fleet $fleet): RedirectResponse
    {
        $this->fleetService->update($fleet, $request->validated());

        return redirect()
            ->route('fleets.index')
            ->with('success', "Fleet \"{$fleet->fresh()->vehicle_name}\" updated successfully.");
    }

    /**
     * Remove the specified fleet.
     */
    public function destroy(Request $request, Fleet $fleet): RedirectResponse|JsonResponse
    {
        $vehicleName = $fleet->vehicle_name;
        $this->fleetService->delete($fleet);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => "Fleet \"{$vehicleName}\" deleted successfully.",
            ]);
        }

        return redirect()
            ->route('fleets.index')
            ->with('info', "Fleet \"{$vehicleName}\" deleted.");
    }

    private function positionCell(Fleet $fleet, string $field): string
    {
        return view('pages.fleets.columns.position', [
            'field' => $field,
            'fleet' => $fleet,
            'positionReference' => $this->fleetService->positionReference($fleet),
        ])->render();
    }
}
