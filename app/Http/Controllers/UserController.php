<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\User;
use App\Services\CustomerService;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class UserController extends Controller
{
    public function __construct(
        private readonly UserService $userService,
        private readonly CustomerService $customerService,
    ) {}

    /**
     * Display a listing of users.
     */
    public function index(): View
    {
        return view('pages.users.index');
    }

    /**
     * Return DataTables JSON for user listing.
     */
    public function data(): JsonResponse
    {
        return DataTables::eloquent($this->userService->getDataTableQuery())
            ->addColumn(
                'name',
                fn (User $user) => view('pages.users.columns.name', compact('user'))->render(),
            )
            ->addColumn(
                'customer_name',
                fn (User $user) => $user->customer?->name ?? 'All Customers',
            )
            ->addColumn(
                'status',
                fn (User $user) => view('pages.users.columns.status', compact('user'))->render(),
            )
            ->addColumn(
                'action',
                fn (User $user) => view('pages.users.columns.action', compact('user'))->render(),
            )
            ->only([
                'action',
                'name',
                'username',
                'email',
                'customer_name',
                'status',
            ])
            ->rawColumns(['action', 'name', 'status'])
            ->toJson();
    }

    /**
     * Show the form for creating a new user.
     */
    public function create(): View
    {
        $customers = $this->customerService->getAll();

        return view('pages.users.create', compact('customers'));
    }

    /**
     * Store a newly created user.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = $this->userService->create($request->validated());

        return redirect()
            ->route('users.index')
            ->with('success', "User \"{$user->name}\" created successfully.");
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user): View
    {
        $customers = $this->customerService->getAll();

        return view('pages.users.edit', compact('user', 'customers'));
    }

    /**
     * Update the specified user.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->userService->update($user, $request->validated());

        return redirect()
            ->route('users.index')
            ->with('success', "User \"{$user->fresh()->name}\" updated successfully.");
    }

    /**
     * Remove the specified user.
     */
    public function destroy(Request $request, User $user): RedirectResponse|JsonResponse
    {
        $name = $user->name;
        $this->userService->delete($user);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => "User \"{$name}\" deleted successfully.",
            ]);
        }

        return redirect()
            ->route('users.index')
            ->with('info', "User \"{$name}\" deleted.");
    }
}
