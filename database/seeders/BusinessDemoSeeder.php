<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\User;
use App\Services\JobClosingService;
use App\Services\JobCostService;
use App\Services\JobService;
use App\Services\MasterDataService;
use App\Services\PaymentService;
use App\Services\QuotationService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BusinessDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local') || Customer::withTrashed()->where('code', 'DEMO-001')->exists()) {
            return;
        }
        DB::transaction(function () {
            $sales = User::where('email', 'sales@jobfinance.test')->firstOrFail();
            $salesManager = User::where('email', 'sales-manager@jobfinance.test')->firstOrFail();
            $operation = User::where('email', 'operational@jobfinance.test')->firstOrFail();
            $finance = User::where('email', 'finance@jobfinance.test')->firstOrFail();
            $customer = app(MasterDataService::class)->save(new Customer, ['code' => 'DEMO-001', 'name' => 'PT Nusantara Logistik', 'contact_name' => 'Budi Santoso', 'email' => 'finance@nusantara.test', 'phone' => '021-555-0101', 'address' => 'Jakarta'], $sales);
            $quotation = app(QuotationService::class)->save(null, ['customer_id' => $customer->id, 'subject' => 'Pengiriman Jakarta ke Surabaya', 'quotation_date' => today()->toDateString(), 'valid_until' => today()->addDays(30)->toDateString(), 'notes' => 'Data demo alur lengkap JobFinance', 'items' => [
                ['description' => 'Dokumen dan reimbursement', 'type' => 'temporary', 'unit' => 'Paket', 'quantity' => '1', 'unit_cost' => '5000000', 'unit_price' => '5000000'],
                ['description' => 'Jasa trucking', 'type' => 'provision', 'unit' => 'Layanan', 'quantity' => '1', 'unit_cost' => '3000000', 'unit_price' => '4500000'],
            ]], $sales);
            app(QuotationService::class)->transition($quotation, 'submit', ['lock_version' => $quotation->fresh()->lock_version], $sales);
            app(QuotationService::class)->transition($quotation, 'approve', ['lock_version' => $quotation->fresh()->lock_version], $salesManager);
            $job = app(QuotationService::class)->convert($quotation, ['lock_version' => $quotation->fresh()->lock_version], $salesManager);
            app(JobService::class)->transition($job, 'open', ['lock_version' => $job->fresh()->lock_version], $operation);
            $costService = app(JobCostService::class);
            $costService->save($job, null, ['job_version' => $job->fresh()->lock_version, 'description' => 'Dokumen dan reimbursement', 'type' => 'temporary', 'cost_date' => today()->toDateString(), 'quantity' => '1', 'unit' => 'Paket', 'unit_cost' => '5000000', 'unit_price' => '5000000'], $finance);
            $costService->save($job, null, ['job_version' => $job->fresh()->lock_version, 'description' => 'Jasa trucking', 'type' => 'provision', 'cost_date' => today()->toDateString(), 'quantity' => '1', 'unit' => 'Layanan', 'unit_cost' => '3000000', 'unit_price' => '4500000'], $finance);

            foreach ($job->fresh()->costs()->where('status', '!=', 'final')->orderBy('id')->get() as $cost) {
                $costService->finalize($job, $cost, ['job_version' => $job->fresh()->lock_version, 'lock_version' => $cost->lock_version], $finance);
            }

            $invoice = app(JobClosingService::class)->close($job, ['lock_version' => $job->fresh()->lock_version, 'closing_date' => today()->toDateString(), 'due_date' => today()->addDays(30)->toDateString(), 'funding_account' => 'bank', 'tax' => '0'], $finance);
            app(PaymentService::class)->create($invoice, ['lock_version' => $invoice->fresh()->lock_version, 'payment_date' => today()->toDateString(), 'amount' => '4000000', 'deposit_account' => 'bank', 'method' => 'transfer', 'reference' => 'DEMO-PAYMENT'], $finance);
        }, 3);
    }
}
