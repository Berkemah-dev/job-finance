<?php

use App\Http\Controllers\AccessController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingConfirmationController;
use App\Http\Controllers\CalculatorController;
use App\Http\Controllers\ClosingController;
use App\Http\Controllers\CustomerContactController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerDocumentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ChargeTypeController;
use App\Http\Controllers\ContainerUnitController;
use App\Http\Controllers\DocumentTypeController;
use App\Http\Controllers\PortController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\JobCostController;
use App\Http\Controllers\JobDocumentController;
use App\Http\Controllers\JournalController;
use App\Http\Controllers\OperationalDocumentController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PricingSuggestionController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\ReimbursementController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ShippingInstructionController;
use App\Http\Controllers\StatementOfAccountController;
use App\Http\Controllers\TpsController;
use App\Http\Controllers\TruckingPriceController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\WeeklyPricingController;
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
    Route::get('/invoices/coretax', [InvoiceController::class, 'coretaxIndex'])->middleware('can:invoices.manage')->name('invoices.coretax.index');
    Route::get('/invoices/{invoice}/preview', [InvoiceController::class, 'preview'])->middleware('can:invoices.manage')->name('invoices.preview');
    Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->middleware('can:invoices.manage')->name('invoices.pdf');
    Route::resource('invoices', InvoiceController::class)->only(['index', 'show'])->middleware('can:invoices.manage');
    Route::get('/invoices/{invoice}/coretax', [InvoiceController::class, 'coretax'])->middleware('can:invoices.manage')->name('invoices.coretax');
    Route::get('/invoices/{invoice}/coretax/preview', [InvoiceController::class, 'coretaxPreview'])->middleware('can:invoices.manage')->name('invoices.coretax.preview');
    Route::get('/payments', [PaymentController::class, 'index'])->middleware('can:payments.manage')->name('payments.index');
    Route::get('/invoices/{invoice}/payments/create', [PaymentController::class, 'create'])->middleware('can:payments.manage')->name('payments.create');
    Route::post('/invoices/{invoice}/payments', [PaymentController::class, 'store'])->middleware('can:payments.manage')->name('payments.store');
    Route::middleware('can:reimbursements.manage')->group(function () {
        Route::get('/reimbursements', [ReimbursementController::class, 'index'])->name('reimbursements.index');
        Route::get('/reimbursements/create', [ReimbursementController::class, 'create'])->name('reimbursements.create');
        Route::post('/reimbursements', [ReimbursementController::class, 'store'])->name('reimbursements.store');
        Route::get('/reimbursements/{reimbursement}', [ReimbursementController::class, 'show'])->name('reimbursements.show');
        Route::get('/reimbursements/{reimbursement}/attachment', [ReimbursementController::class, 'downloadAttachment'])->name('reimbursements.attachment');
        Route::post('/reimbursements/{reimbursement}/approve', [ReimbursementController::class, 'approve'])->name('reimbursements.approve');
        Route::post('/reimbursements/{reimbursement}/reject', [ReimbursementController::class, 'reject'])->name('reimbursements.reject');
        Route::post('/reimbursements/{reimbursement}/pay', [ReimbursementController::class, 'pay'])->name('reimbursements.pay');
    });
    Route::resource('journals', JournalController::class)->only(['index', 'create', 'store', 'show'])->middleware('can:journals.manage');
    Route::post('/journals/{journal}/reverse', [JournalController::class, 'reverse'])->middleware('can:journals.manage')->name('journals.reverse');
    Route::middleware('can:reports.view')->prefix('reports')->name('reports.')->group(function () {
        Route::get('/ledger', [ReportController::class, 'ledger'])->name('ledger');
        Route::get('/trial-balance', [ReportController::class, 'trialBalance'])->name('trial-balance');
        Route::get('/balance-sheet', [ReportController::class, 'balanceSheet'])->name('balance-sheet');
        Route::get('/income-statement', [ReportController::class, 'incomeStatement'])->name('income-statement');
        Route::get('/cash-flow', [ReportController::class, 'cashFlow'])->name('cash-flow');
        Route::get('/profit-per-job', [ReportController::class, 'profitPerJob'])->name('profit-per-job');
        Route::get('/profit-bulanan', [ReportController::class, 'profitMonthly'])->name('profit-monthly');
        Route::get('/statement-of-account', [StatementOfAccountController::class, 'index'])->name('soa');
        Route::get('/statement-of-account/{soaCustomer}', [StatementOfAccountController::class, 'show'])->name('soa.customer');
        Route::post('/statement-of-account/{soaCustomer}/email', [StatementOfAccountController::class, 'email'])->middleware('can:email.manage')->name('soa.email');
    });
    Route::get('/dokumen-job', [OperationalDocumentController::class, 'index'])->middleware('can:jobs.view')->name('documents.index');
    Route::get('/dokumen-job/{quotation}', [OperationalDocumentController::class, 'show'])->middleware('can:jobs.view')->name('documents.show');
    Route::get('/dokumen-job/{quotation}/quotation/preview', [OperationalDocumentController::class, 'quotationPreview'])->middleware('can:jobs.view')->name('documents.quotation.preview');
    Route::get('/dokumen-job/{quotation}/job-order/preview', [OperationalDocumentController::class, 'jobPreview'])->middleware('can:jobs.view')->name('documents.job.preview');
    Route::get('/api/dokumen-job/{quotation}/quotation/pdf', [OperationalDocumentController::class, 'quotationPdf'])->middleware('can:jobs.view')->name('documents.quotation.pdf');
    Route::get('/api/dokumen-job/{quotation}/job-order/pdf', [OperationalDocumentController::class, 'jobPdf'])->middleware('can:jobs.view')->name('documents.job.pdf');
    Route::get('/api/dokumen-job/{quotation}/surat-jalan/pdf', [OperationalDocumentController::class, 'suratJalanPdf'])->middleware('can:jobs.view')->name('documents.surat-jalan.pdf');
    Route::get('/api/dokumen-job/{quotation}/tanda-terima/pdf', [OperationalDocumentController::class, 'tandaTerimaPdf'])->middleware('can:jobs.view')->name('documents.tanda-terima.pdf');
    Route::get('/api/dokumen-job/{quotation}/sk-do/pdf', [OperationalDocumentController::class, 'skDoPdf'])->middleware('can:jobs.view')->name('documents.sk-do.pdf');
    Route::get('/api/pricing/suggest-trucking', [PricingSuggestionController::class, 'suggestTrucking'])->name('pricing.suggest-trucking');
    Route::get('/kalkulator', [CalculatorController::class, 'index'])->name('calculators.index');
    Route::get('/kalkulator/volume-weight', [CalculatorController::class, 'volumeWeight'])->name('calculators.volume-weight');
    Route::get('/kalkulator/lcl', [CalculatorController::class, 'lcl'])->name('calculators.lcl');
    Route::get('/kalkulator/pajak', [CalculatorController::class, 'tax'])->name('calculators.tax');
    Route::get('/api/calculators/packages', [CalculatorController::class, 'packages'])->name('calculators.api.packages');
    Route::get('/api/calculators/lcl', [CalculatorController::class, 'lclApi'])->name('calculators.api.lcl');
    Route::get('/api/calculators/tax', [CalculatorController::class, 'taxApi'])->name('calculators.api.tax');
    Route::resource('quotations', QuotationController::class)->except('destroy')->middleware('can:quotations.manage');
    Route::get('/quotations/{quotation}/print', [QuotationController::class, 'print'])->name('quotations.print');
    Route::post('/quotations/{quotation}/submit', [QuotationController::class, 'submit'])->middleware('can:quotations.manage')->name('quotations.submit');
    Route::post('/quotations/{quotation}/duplicate', [QuotationController::class, 'duplicate'])->middleware('can:quotations.manage')->name('quotations.duplicate');
    Route::get('/quotations/{quotation}/preview', [QuotationController::class, 'preview'])->middleware('can:quotations.manage')->name('quotations.preview');
    Route::get('/api/quotations/{quotation}/pdf', [QuotationController::class, 'pdf'])->middleware('can:quotations.manage')->name('quotations.pdf');
    foreach (['approve', 'reject', 'revise'] as $action) {
        Route::post('/quotations/{quotation}/'.$action, [QuotationController::class, $action])->middleware('can:quotations.approve')->name('quotations.'.$action);
    }
    Route::post('/quotations/{quotation}/convert', [QuotationController::class, 'convert'])->middleware(['can:quotations.manage', 'can:jobs.manage'])->name('quotations.convert');
    // Booking Confirmation
    Route::resource('booking-confirmations', BookingConfirmationController::class)->middleware('can:jobs.view');
    Route::get('/booking-confirmations/{bookingConfirmation}/preview', [BookingConfirmationController::class, 'preview'])->middleware('can:jobs.view')->name('booking-confirmations.preview');
    Route::get('/api/booking-confirmations/{bookingConfirmation}/pdf', [BookingConfirmationController::class, 'pdf'])->middleware('can:jobs.view')->name('booking-confirmations.pdf');
    // Shipping Instruction
    Route::resource('shipping-instructions', ShippingInstructionController::class)->middleware('can:jobs.view');
    Route::get('/shipping-instructions/{shippingInstruction}/preview', [ShippingInstructionController::class, 'preview'])->middleware('can:jobs.view')->name('shipping-instructions.preview');
    Route::get('/api/shipping-instructions/{shippingInstruction}/pdf', [ShippingInstructionController::class, 'pdf'])->middleware('can:jobs.view')->name('shipping-instructions.pdf');
    Route::resource('jobs', JobController::class)->only(['index', 'show'])->middleware('can:jobs.view');
    Route::resource('jobs', JobController::class)->only(['edit', 'update'])->middleware('can:jobs.manage');
    Route::get('/jobs/{job}/preview', [JobController::class, 'preview'])->middleware('can:jobs.view')->name('jobs.preview');
    Route::get('/api/jobs/{job}/pdf', [JobController::class, 'pdf'])->middleware('can:jobs.view')->name('jobs.pdf');
    Route::get('/job-orders', [JobController::class, 'index'])->middleware('can:jobs.view')->name('job-orders.index');
    Route::get('/job-orders/{job}', [JobController::class, 'show'])->middleware('can:jobs.view')->name('job-orders.show');
    Route::get('/job-orders/{job}/edit', [JobController::class, 'edit'])->middleware('can:jobs.manage')->name('job-orders.edit');
    foreach (['open', 'cancel'] as $action) {
        Route::post('/jobs/{job}/'.$action, [JobController::class, $action])->middleware('can:jobs.manage')->name('jobs.'.$action);
    }
    Route::post('/jobs/{job}/confirm-do', [JobController::class, 'confirmDo'])->middleware('can:jobs.confirm-do')->name('jobs.confirm-do');
    // Job Documents
    Route::post('/jobs/{job}/documents', [JobDocumentController::class, 'store'])->middleware('can:jobs.view')->name('jobs.documents.store');
    Route::get('/jobs/{job}/documents/{document}/download', [JobDocumentController::class, 'download'])->middleware('can:jobs.view')->name('jobs.documents.download');
    Route::delete('/jobs/{job}/documents/{document}', [JobDocumentController::class, 'destroy'])->middleware('can:jobs.manage')->name('jobs.documents.destroy');
    // Document Type Master
    Route::resource('document-types', DocumentTypeController::class)->except('show')->middleware('can:jobs.manage');
    // Master Data: Ports, ChargeTypes, ContainerUnits
    Route::post('/ports', [PortController::class, 'store'])->middleware('can:jobs.manage')->name('ports.store');
    Route::patch('/ports/{port}/toggle', [PortController::class, 'toggle'])->middleware('can:jobs.manage')->name('ports.toggle');
    Route::delete('/ports/{port}', [PortController::class, 'destroy'])->middleware('can:jobs.manage')->name('ports.destroy');
    Route::get('/ports/{port}/edit', [PortController::class, 'edit'])->middleware('can:jobs.manage')->name('ports.edit');
    Route::put('/ports/{port}', [PortController::class, 'update'])->middleware('can:jobs.manage')->name('ports.update');
    Route::post('/charge-types', [ChargeTypeController::class, 'store'])->middleware('can:jobs.manage')->name('charge-types.store');
    Route::patch('/charge-types/{chargeType}/toggle', [ChargeTypeController::class, 'toggle'])->middleware('can:jobs.manage')->name('charge-types.toggle');
    Route::delete('/charge-types/{chargeType}', [ChargeTypeController::class, 'destroy'])->middleware('can:jobs.manage')->name('charge-types.destroy');
    Route::get('/charge-types/{chargeType}/edit', [ChargeTypeController::class, 'edit'])->middleware('can:jobs.manage')->name('charge-types.edit');
    Route::put('/charge-types/{chargeType}', [ChargeTypeController::class, 'update'])->middleware('can:jobs.manage')->name('charge-types.update');
    Route::post('/container-units', [ContainerUnitController::class, 'store'])->middleware('can:jobs.manage')->name('container-units.store');
    Route::patch('/container-units/{containerUnit}/toggle', [ContainerUnitController::class, 'toggle'])->middleware('can:jobs.manage')->name('container-units.toggle');
    Route::delete('/container-units/{containerUnit}', [ContainerUnitController::class, 'destroy'])->middleware('can:jobs.manage')->name('container-units.destroy');
    Route::get('/container-units/{containerUnit}/edit', [ContainerUnitController::class, 'edit'])->middleware('can:jobs.manage')->name('container-units.edit');
    Route::put('/container-units/{containerUnit}', [ContainerUnitController::class, 'update'])->middleware('can:jobs.manage')->name('container-units.update');
    Route::resource('tps', TpsController::class)->except('show')->parameters(['tps' => 'tps'])->middleware('can:tps.manage');
    Route::post('/jobs/{job}/shipment-status', [JobController::class, 'shipmentStatus'])->middleware('can:jobs.manage')->name('jobs.shipment-status');
    Route::get('/costs', [JobCostController::class, 'overview'])->middleware('can:costs.manage')->name('costs.overview');
    Route::middleware('can:costs.manage')->scopeBindings()->group(function () {
        Route::resource('jobs.costs', JobCostController::class);
        Route::post('/jobs/{job}/costs/{cost}/finalize', [JobCostController::class, 'finalize'])->name('jobs.costs.finalize');
    });
    Route::get('/customers', [CustomerController::class, 'index'])->middleware('can:customers.view')->name('customers.index');
    Route::get('/customers/create', [CustomerController::class, 'create'])->middleware('can:customers.manage')->name('customers.create');
    Route::post('/customers', [CustomerController::class, 'store'])->middleware('can:customers.manage')->name('customers.store');
    Route::get('/customers/{customer}', [CustomerController::class, 'show'])->middleware('can:customers.view')->name('customers.show');
    Route::get('/customers/{customer}/edit', [CustomerController::class, 'edit'])->middleware('can:customers.manage')->name('customers.edit');
    Route::put('/customers/{customer}', [CustomerController::class, 'update'])->middleware('can:customers.manage')->name('customers.update');
    Route::delete('/customers/{customer}', [CustomerController::class, 'destroy'])->middleware('can:customers.manage')->name('customers.destroy');
    Route::post('/customers/{customer}/restore', [CustomerController::class, 'restore'])->middleware('can:customers.manage')->name('customers.restore');
    Route::get('/customers/{customer}/documents/{document}/download', [CustomerDocumentController::class, 'download'])->middleware('can:customers.view')->name('customers.documents.download');
    Route::delete('/customers/{customer}/documents/{document}', [CustomerDocumentController::class, 'destroy'])->middleware('can:customers.manage')->name('customers.documents.destroy');
    Route::get('/customer-contacts', [CustomerContactController::class, 'index'])->middleware('can:customers.view')->name('customer-contacts.index');
    Route::get('/customer-contacts/create', [CustomerContactController::class, 'create'])->middleware('can:customers.manage')->name('customer-contacts.create');
    Route::post('/customer-contacts', [CustomerContactController::class, 'store'])->middleware('can:customers.manage')->name('customer-contacts.store');
    Route::get('/customer-contacts/{customerContact}', [CustomerContactController::class, 'show'])->middleware('can:customers.view')->name('customer-contacts.show');
    Route::get('/customer-contacts/{customerContact}/edit', [CustomerContactController::class, 'edit'])->middleware('can:customers.manage')->name('customer-contacts.edit');
    Route::put('/customer-contacts/{customerContact}', [CustomerContactController::class, 'update'])->middleware('can:customers.manage')->name('customer-contacts.update');
    Route::delete('/customer-contacts/{customerContact}', [CustomerContactController::class, 'destroy'])->middleware('can:customers.manage')->name('customer-contacts.destroy');
    Route::get('/api/customer-contacts/{customer}', [CustomerContactController::class, 'forCustomer'])->middleware('can:customers.view')->name('api.customer-contacts');
    Route::get('/vendors', [VendorController::class, 'index'])->middleware('can:vendors.manage')->name('vendors.index');
    Route::get('/vendors/create', [VendorController::class, 'create'])->middleware('can:vendors.manage')->name('vendors.create');
    Route::post('/vendors', [VendorController::class, 'store'])->middleware('can:vendors.manage')->name('vendors.store');
    Route::get('/vendors/{vendor}', [VendorController::class, 'show'])->middleware('can:vendors.manage')->name('vendors.show');
    Route::get('/vendors/{vendor}/edit', [VendorController::class, 'edit'])->middleware('can:vendors.manage')->name('vendors.edit');
    Route::put('/vendors/{vendor}', [VendorController::class, 'update'])->middleware('can:vendors.manage')->name('vendors.update');
    Route::post('/vendors/{vendor}/toggle', [VendorController::class, 'toggle'])->middleware('can:vendors.manage')->name('vendors.toggle');
    Route::delete('/vendors/{vendor}', [VendorController::class, 'destroy'])->middleware('can:vendors.manage')->name('vendors.destroy');
    Route::post('/vendors/{vendor}/restore', [VendorController::class, 'restore'])->middleware('can:vendors.manage')->name('vendors.restore');
    Route::middleware('can:pricing.view')->prefix('pricing')->name('pricing.')->group(function () {
        Route::get('/weekly', [WeeklyPricingController::class, 'index'])->name('weekly.index');
        Route::get('/trucking', [TruckingPriceController::class, 'index'])->name('trucking.index');
    });
    Route::middleware('can:pricing.manage')->prefix('pricing')->name('pricing.')->group(function () {
        Route::get('/weekly/create', [WeeklyPricingController::class, 'create'])->name('weekly.create');
        Route::post('/weekly', [WeeklyPricingController::class, 'store'])->name('weekly.store');
        Route::get('/weekly/{weeklyPricing}/edit', [WeeklyPricingController::class, 'edit'])->name('weekly.edit');
        Route::put('/weekly/{weeklyPricing}', [WeeklyPricingController::class, 'update'])->name('weekly.update');
        Route::post('/weekly/{weeklyPricing}/toggle', [WeeklyPricingController::class, 'toggle'])->name('weekly.toggle');
        Route::delete('/weekly/{weeklyPricing}', [WeeklyPricingController::class, 'destroy'])->name('weekly.destroy');
        Route::get('/trucking/create', [TruckingPriceController::class, 'create'])->name('trucking.create');
        Route::post('/trucking', [TruckingPriceController::class, 'store'])->name('trucking.store');
        Route::get('/trucking/{truckingPrice}/edit', [TruckingPriceController::class, 'edit'])->name('trucking.edit');
        Route::put('/trucking/{truckingPrice}', [TruckingPriceController::class, 'update'])->name('trucking.update');
        Route::post('/trucking/{truckingPrice}/toggle', [TruckingPriceController::class, 'toggle'])->name('trucking.toggle');
        Route::delete('/trucking/{truckingPrice}', [TruckingPriceController::class, 'destroy'])->name('trucking.destroy');
    });
    Route::get('/accounts/mappings', [AccountController::class, 'mappings'])->middleware('can:coa.manage')->name('accounts.mappings');
    Route::get('/master/coa', [AccountController::class, 'index'])->defaults('tab', 'coa')->middleware('can:coa.manage')->name('master.coa');
    Route::get('/master/charge-types', [AccountController::class, 'index'])->defaults('tab', 'charge')->middleware('can:jobs.manage')->name('master.charge');
    Route::get('/master/units', [AccountController::class, 'index'])->defaults('tab', 'unit')->middleware('can:jobs.manage')->name('master.units');
    Route::get('/master/ports', [AccountController::class, 'index'])->defaults('tab', 'port')->middleware('can:jobs.manage')->name('master.ports');
    Route::put('/accounts/mappings', [AccountController::class, 'updateMappings'])->middleware('can:coa.manage')->name('accounts.mappings.update');
    Route::resource('accounts', AccountController::class)->except('show')->middleware('can:coa.manage');
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->middleware('can:dashboard.view')->name('dashboard');
    Route::get('/dashboard-finance', [DashboardController::class, 'role'])->defaults('role', 'finance')->middleware('can:dashboard.view')->name('dashboard.finance');
    Route::get('/dashboard-financemanager', [DashboardController::class, 'role'])->defaults('role', 'finance-manager')->middleware('can:dashboard.view')->name('dashboard.finance-manager');
    Route::get('/dashboard-salesmanager', [DashboardController::class, 'role'])->defaults('role', 'sales-manager')->middleware('can:dashboard.view')->name('dashboard.sales-manager');
    Route::get('/dashboard-sales', [DashboardController::class, 'role'])->defaults('role', 'sales')->middleware('can:dashboard.view')->name('dashboard.sales');
    Route::get('/dashboard-operational', [DashboardController::class, 'role'])->defaults('role', 'operational')->middleware('can:dashboard.view')->name('dashboard.operational');
    Route::get('/dashboard-customer-service', [DashboardController::class, 'role'])->defaults('role', 'customer-service')->middleware('can:dashboard.view')->name('dashboard.customer-service');
    Route::get('/users', [AccessController::class, 'users'])->middleware('can:users.view')->name('users.index');
    Route::get('/users/create', [AccessController::class, 'createUser'])->middleware('can:users.manage')->name('users.create');
    Route::post('/users', [AccessController::class, 'storeUser'])->middleware('can:users.manage')->name('users.store');
    Route::get('/users/{user}/edit', [AccessController::class, 'editUser'])->middleware('can:users.manage')->name('users.edit');
    Route::put('/users/{user}', [AccessController::class, 'updateUser'])->middleware('can:users.manage')->name('users.update');
    Route::get('/activity', [AccessController::class, 'activity'])->middleware('can:activity.view')->name('activity.index');

    // Master TPS Air/Sea
    Route::middleware('can:jobs.manage')->prefix('tps')->name('tps.')->group(function () {
        Route::get('/', [TpsController::class, 'index'])->name('index');
        Route::get('/create', [TpsController::class, 'create'])->name('create');
        Route::post('/', [TpsController::class, 'store'])->name('store');
        Route::get('/{tps}/edit', [TpsController::class, 'edit'])->name('edit');
        Route::put('/{tps}', [TpsController::class, 'update'])->name('update');
        Route::post('/{tps}/toggle', [TpsController::class, 'toggle'])->name('toggle');
        Route::delete('/{tps}', [TpsController::class, 'destroy'])->name('destroy');
    });

    // Invoice PDF
    Route::get('/api/invoices/{invoice}/pdf', [OperationalDocumentController::class, 'invoicePdf'])
        ->middleware('can:invoices.manage')->name('invoices.pdf');

    // DNP & SK Pabean PDF
    Route::get('/api/dokumen-job/{quotation}/dnp/pdf', [OperationalDocumentController::class, 'dnpPdf'])
        ->middleware('can:jobs.view')->name('documents.dnp.pdf');
    Route::get('/api/dokumen-job/{quotation}/sk-pabean/pdf', [OperationalDocumentController::class, 'skPabeaPdf'])
        ->middleware('can:jobs.view')->name('documents.sk-pabean.pdf');

    // Invoice delivery status update
    Route::post('/invoices/{invoice}/delivery', [InvoiceController::class, 'updateDelivery'])
        ->middleware('can:invoices.manage')->name('invoices.delivery');

    // SOA PDF download
    Route::get('/api/reports/soa/{customer}/pdf', [OperationalDocumentController::class, 'soaPdf'])
        ->middleware('can:reports.view')->name('reports.soa.pdf');
});
