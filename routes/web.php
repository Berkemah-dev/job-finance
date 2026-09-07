<?php

use App\Http\Controllers\AccessController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\QuotationController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->middleware('throttle:20,1')->name('login.store');
});
Route::middleware('auth')->group(function () {
    Route::resource('quotations', QuotationController::class)->except('destroy')->middleware('can:quotations.manage');
    foreach (['submit', 'approve', 'reject', 'convert'] as $action) {
        Route::post('/quotations/{quotation}/'.$action, [QuotationController::class, $action])->middleware('can:quotations.manage')->name('quotations.'.$action);
    }
    Route::resource('jobs', JobController::class)->only(['index', 'show'])->middleware('can:jobs.manage');
    Route::resource('customers', CustomerController::class)->except('show')->middleware('can:customers.manage');
    Route::get('/accounts/mappings', [AccountController::class, 'mappings'])->middleware('can:coa.manage')->name('accounts.mappings');
    Route::put('/accounts/mappings', [AccountController::class, 'updateMappings'])->middleware('can:coa.manage')->name('accounts.mappings.update');
    Route::resource('accounts', AccountController::class)->except('show')->middleware('can:coa.manage');
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->middleware('can:dashboard.view')->name('dashboard');
    Route::get('/users', [AccessController::class, 'users'])->middleware('can:users.view')->name('users.index');
    Route::get('/activity', [AccessController::class, 'activity'])->middleware('can:activity.view')->name('activity.index');
});
