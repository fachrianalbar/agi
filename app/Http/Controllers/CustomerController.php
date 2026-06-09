<?php

namespace App\Http\Controllers;

use App\Http\Requests\Customer\StoreCustomerRequest;
use App\Http\Requests\Customer\UpdateCustomerRequest;
use App\Models\Customer;
use App\Services\CustomerService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class CustomerController extends Controller
{
    public function __construct(
        private readonly CustomerService $customerService,
    ) {}

    /**
     * Display a listing of customers.
     */
    public function index(): View
    {
        return view('pages.customers.index');
    }

    /**
     * Return DataTables JSON for customer listing.
     */
    public function data(): JsonResponse
    {
        return DataTables::eloquent($this->customerService->getDataTableQuery())
            ->addColumn(
                'name',
                fn (Customer $customer) => view('pages.customers.columns.name', compact('customer'))->render(),
            )
            ->addColumn(
                'status',
                fn (Customer $customer) => view('pages.customers.columns.status', compact('customer'))->render(),
            )
            ->addColumn(
                'action',
                fn (Customer $customer) => view('pages.customers.columns.action', compact('customer'))->render(),
            )
            ->addColumn('location', fn (Customer $customer) => collect([$customer->city, $customer->country])->filter()->join(', ') ?: '—')
            ->filterColumn('location', function (Builder $query, string $keyword): void {
                $query->where(function (Builder $q) use ($keyword): void {
                    $q->where('city', 'like', "%{$keyword}%")
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
                'status',
            ])
            ->rawColumns(['action', 'name', 'status'])
            ->toJson();
    }

    /**
     * Show the form for creating a new customer.
     */
    public function create()
    {
        return view('pages.customers.create');
    }

    /**
     * Store a newly created customer.
     */
    public function store(StoreCustomerRequest $request)
    {
        $customer = $this->customerService->create($request->validated());

        return redirect()
            ->route('customers.index')
            ->with('success', "Customer \"{$customer->name}\" created successfully.");
    }

    /**
     * Show the form for editing the specified customer.
     */
    public function edit(Customer $customer)
    {
        return view('pages.customers.edit', compact('customer'));
    }

    /**
     * Update the specified customer.
     */
    public function update(UpdateCustomerRequest $request, Customer $customer)
    {
        $this->customerService->update($customer, $request->validated());

        return redirect()
            ->route('customers.index')
            ->with('success', "Customer \"{$customer->fresh()->name}\" updated successfully.");
    }

    /**
     * Remove the specified customer.
     */
    public function destroy(Request $request, Customer $customer): RedirectResponse|JsonResponse
    {
        $name = $customer->name;
        $this->customerService->delete($customer);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => "Customer \"{$name}\" deleted successfully.",
            ]);
        }

        return redirect()
            ->route('customers.index')
            ->with('info', "Customer \"{$name}\" deleted.");
    }
}
