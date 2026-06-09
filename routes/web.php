<?php

use App\Http\Controllers\AgentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MenuController;
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
Route::get('customers/data', [\App\Http\Controllers\CustomerController::class, 'data'])->name('customers.data');
Route::resource('customers', \App\Http\Controllers\CustomerController::class)->except('show');

// Fleet management
Route::get('fleets/data', [\App\Http\Controllers\FleetController::class, 'data'])->name('fleets.data');
Route::post('fleets/latest-positions', [\App\Http\Controllers\FleetController::class, 'latestPositions'])->name('fleets.latest-positions');
Route::post('fleets/sync', [\App\Http\Controllers\FleetController::class, 'sync'])->name('fleets.sync');
Route::resource('fleets', \App\Http\Controllers\FleetController::class)->except('show');

// Logout (placeholder)
Route::post('/logout', function () {
    return redirect('/');
})->name('logout');
