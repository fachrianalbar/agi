<?php

use App\Http\Controllers\AgentController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FleetController;
use App\Http\Controllers\FleetHistoryController;
use App\Http\Controllers\FleetTransactionController;
use App\Http\Controllers\InactiveFleetController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SummaryReportController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Authentication
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});
Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// Protected routes
Route::middleware(['auth'])->group(function () {
    // Dashboard — homepage
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // AI Agents — list & CRUD
    Route::prefix('agents')->name('agents.')->group(function () {
        Route::get('/', [AgentController::class, 'index'])->name('index');
        Route::post('/', [AgentController::class, 'store'])->name('store');
        Route::put('/{id}', [AgentController::class, 'update'])->name('update');
        Route::delete('/{id}', [AgentController::class, 'destroy'])->name('destroy');
    });

    // Analytics
    Route::view('/analytics', 'pages.analytics')->name('analytics');

    // Activity Log
    Route::view('/activity', 'pages.activity')->name('activity');

    // Application Settings
    Route::get('/settings', [SettingController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');

    // Sidebar menu management
    Route::get('menus/data', [MenuController::class, 'data'])->name('menus.data');
    Route::resource('menus', MenuController::class)->except('show');

    // Customer management
    Route::get('customers/data', [CustomerController::class, 'data'])->name('customers.data');
    Route::resource('customers', CustomerController::class)->except('show');

    // User management
    Route::get('users/data', [UserController::class, 'data'])->name('users.data');
    Route::resource('users', UserController::class)->except('show');

    // Fleet management
    Route::get('fleets/data', [FleetController::class, 'data'])->name('fleets.data');
    Route::post('fleets/latest-positions', [FleetController::class, 'latestPositions'])->name('fleets.latest-positions');
    Route::post('fleets/sync', [FleetController::class, 'sync'])->name('fleets.sync');
    Route::resource('fleets', FleetController::class)->except('show');

    // Inactive fleet lookup
    Route::get('inactive', [InactiveFleetController::class, 'index'])->name('inactive.index');
    Route::get('inactive/data', [InactiveFleetController::class, 'data'])->name('inactive.data');
    Route::get('inactive/{customer}/vehicles', [InactiveFleetController::class, 'vehicles'])->name('inactive.vehicles');

    // Reports
    Route::get('summary-reports', [SummaryReportController::class, 'index'])->name('summary-reports.index');
    Route::get('summary-reports/fleets', [SummaryReportController::class, 'fleets'])->name('summary-reports.fleets');
    Route::post('summary-reports', [SummaryReportController::class, 'generate'])->name('summary-reports.generate');
    Route::get('fleet-histories', [FleetHistoryController::class, 'index'])->name('fleet-histories.index');
    Route::get('fleet-histories/fleets', [FleetHistoryController::class, 'fleets'])->name('fleet-histories.fleets');
    Route::post('fleet-histories', [FleetHistoryController::class, 'generate'])->name('fleet-histories.generate');
    Route::get('fleet-transactions/data', [FleetTransactionController::class, 'data'])->name('fleet-transactions.data');
    Route::post('fleet-transactions/import', [FleetTransactionController::class, 'import'])->name('fleet-transactions.import');
    Route::resource('fleet-transactions', FleetTransactionController::class)->except('show');
}); // end auth middleware
