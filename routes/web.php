<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AgentController;
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

// Logout (placeholder)
Route::post('/logout', function () {
    return redirect('/');
})->name('logout');
