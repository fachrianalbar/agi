<?php

use App\Http\Controllers\AgentController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FleetController;
use App\Http\Controllers\FleetHistoryController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\SummaryReportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

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

// Settings
Route::view('/settings', 'pages.settings')->name('settings');

// Sidebar menu management
Route::get('menus/data', [MenuController::class, 'data'])->name('menus.data');
Route::resource('menus', MenuController::class)->except('show');

// Customer management
Route::get('customers/data', [CustomerController::class, 'data'])->name('customers.data');
Route::resource('customers', CustomerController::class)->except('show');

// Fleet management
Route::get('fleets/data', [FleetController::class, 'data'])->name('fleets.data');
Route::post('fleets/latest-positions', [FleetController::class, 'latestPositions'])->name('fleets.latest-positions');
Route::post('fleets/sync', [FleetController::class, 'sync'])->name('fleets.sync');
Route::resource('fleets', FleetController::class)->except('show');

// Reports
Route::get('summary-reports', [SummaryReportController::class, 'index'])->name('summary-reports.index');
Route::get('summary-reports/fleets', [SummaryReportController::class, 'fleets'])->name('summary-reports.fleets');
Route::post('summary-reports', [SummaryReportController::class, 'generate'])->name('summary-reports.generate');
Route::get('fleet-histories', [FleetHistoryController::class, 'index'])->name('fleet-histories.index');
Route::get('fleet-histories/fleets', [FleetHistoryController::class, 'fleets'])->name('fleet-histories.fleets');
Route::post('fleet-histories', [FleetHistoryController::class, 'generate'])->name('fleet-histories.generate');

// Logout (placeholder)
Route::post('/logout', function () {
    return redirect('/');
})->name('logout');
