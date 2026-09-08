<?php

use App\Http\Controllers\AccessController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClosingController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\JobCostController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\QuotationController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->middleware('throttle:20,1')->name('login.store');
});
Route::middleware('auth')->group(function () {
    Route::get('/closing', [ClosingController::class, 'index'])->middleware('can:jobs.close')->name('closing.index');
    Route::get('/closing/{job}', [ClosingController::class, 'create'])->middleware('can:jobs.close')->name('closing.create');
    Route::post('/closing/{job}', [ClosingController::class, 'store'])->middleware('can:jobs.close')->name('closing.store');
    Route::resource('invoices', InvoiceController::class)->only(['index', 'show'])->middleware('can:invoices.manage');
    Route::get('/payments', [PaymentController::class, 'index'])->middleware('can:payments.manage')->name('payments.index');
    Route::get('/invoices/{invoice}/payments/create', [PaymentController::class, 'create'])->middleware('can:payments.manage')->name('payments.create');
    Route::post('/invoices/{invoice}/payments', [PaymentController::class, 'store'])->middleware('can:payments.manage')->name('payments.store');
    Route::resource('quotations', QuotationController::class)->except('destroy')->middleware('can:quotations.manage');
    foreach (['submit', 'approve', 'reject', 'convert'] as $action) {
        Route::post('/quotations/{quotation}/'.$action, [QuotationController::class, $action])->middleware('can:quotations.manage')->name('quotations.'.$action);
    }
    Route::resource('jobs', JobController::class)->only(['index', 'show'])->middleware('can:jobs.view');
    Route::resource('jobs', JobController::class)->only(['edit', 'update'])->middleware('can:jobs.manage');
    foreach (['open', 'cancel'] as $action) {
        Route::post('/jobs/{job}/'.$action, [JobController::class, $action])->middleware('can:jobs.manage')->name('jobs.'.$action);
    }
    Route::get('/costs', [JobCostController::class, 'overview'])->middleware('can:costs.manage')->name('costs.overview');
    Route::middleware('can:costs.manage')->scopeBindings()->group(function () {
        Route::resource('jobs.costs', JobCostController::class);
        Route::post('/jobs/{job}/costs/{cost}/finalize', [JobCostController::class, 'finalize'])->name('jobs.costs.finalize');
    });
    Route::resource('customers', CustomerController::class)->except('show')->middleware('can:customers.manage');
    Route::get('/accounts/mappings', [AccountController::class, 'mappings'])->middleware('can:coa.manage')->name('accounts.mappings');
    Route::put('/accounts/mappings', [AccountController::class, 'updateMappings'])->middleware('can:coa.manage')->name('accounts.mappings.update');
    Route::resource('accounts', AccountController::class)->except('show')->middleware('can:coa.manage');
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->middleware('can:dashboard.view')->name('dashboard');
    Route::get('/users', [AccessController::class, 'users'])->middleware('can:users.view')->name('users.index');
    Route::get('/activity', [AccessController::class, 'activity'])->middleware('can:activity.view')->name('activity.index');
});
