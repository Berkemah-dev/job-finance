<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (! Schema::hasColumn('customers', 'receivable_account_id')) {
                $table->foreignId('receivable_account_id')->nullable()->after('tax_number')->constrained('chart_of_accounts')->nullOnDelete();
            }
            if (! Schema::hasColumn('customers', 'temporary_receivable_account_id')) {
                $table->foreignId('temporary_receivable_account_id')->nullable()->after('receivable_account_id')->constrained('chart_of_accounts')->nullOnDelete();
            }
        });
        Schema::table('vendors', function (Blueprint $table) {
            if (! Schema::hasColumn('vendors', 'payable_account_id')) {
                $table->foreignId('payable_account_id')->nullable()->after('tax_number')->constrained('chart_of_accounts')->nullOnDelete();
            }
        });

        $parents = DB::table('account_mappings as mappings')
            ->join('chart_of_accounts as accounts', 'accounts.id', '=', 'mappings.chart_of_account_id')
            ->whereIn('mappings.key', ['receivable', 'temporary_receivable', 'vendor_payable', 'agent_payable'])
            ->get(['mappings.key', 'accounts.id', 'accounts.code', 'accounts.type', 'accounts.level'])
            ->keyBy('key');
        $ensure = static function (object $party, string $table, string $column, string $key, string $label) use ($parents): void {
            $parent = $parents->get($key);
            if (! $parent) return;
            $prefix = match ($key) {
                'receivable' => '1200',
                'temporary_receivable' => '1201',
                'vendor_payable' => '2100',
                'agent_payable' => '2200',
            };
            $code = $prefix.str_pad((string) $party->id, 3, '0', STR_PAD_LEFT);
            $account = DB::table('chart_of_accounts')->where('code', $code)->first();
            $values = ['name' => $label.' — '.$party->name, 'type' => $parent->type, 'parent_id' => $parent->id, 'level' => ((int) $parent->level) + 1, 'updated_at' => now()];
            if ($account) {
                DB::table('chart_of_accounts')->where('id', $account->id)->update($values);
                $accountId = $account->id;
            } else {
                $accountId = DB::table('chart_of_accounts')->insertGetId($values + ['code' => $code, 'created_at' => now(), 'lock_version' => 0]);
            }
            DB::table($table)->where('id', $party->id)->update([$column => $accountId]);
        };
        foreach (DB::table('customers')->get() as $customer) {
            $ensure($customer, 'customers', 'receivable_account_id', 'receivable', 'Piutang Customer');
            $ensure($customer, 'customers', 'temporary_receivable_account_id', 'temporary_receivable', 'Piutang Temporary');
        }
        foreach (DB::table('vendors')->get() as $vendor) {
            $key = in_array($vendor->type, ['international_agent', 'national_agent'], true) ? 'agent_payable' : 'vendor_payable';
            $ensure($vendor, 'vendors', 'payable_account_id', $key, $key === 'agent_payable' ? 'Hutang Agent' : 'Hutang Vendor');
        }

        // Pindahkan mutasi lama ke COA pihak yang tepat agar saldo historis juga terbaca per customer/vendor.
        foreach (DB::table('customers')->whereNotNull('receivable_account_id')->get(['id', 'receivable_account_id', 'temporary_receivable_account_id']) as $customer) {
            $jobIds = DB::table('jobs')->where('customer_id', $customer->id)->pluck('id');
            $invoiceIds = DB::table('invoices')->where('customer_id', $customer->id)->pluck('id');
            $paymentIds = DB::table('payments')->whereIn('invoice_id', $invoiceIds)->pluck('id');
            $journalIds = DB::table('journals')->where(function ($query) use ($jobIds, $paymentIds) {
                $query->where(function ($q) use ($jobIds) {
                    $q->where('source_type', \App\Models\Job::class)->whereIn('source_id', $jobIds);
                })->orWhere(function ($q) use ($paymentIds) {
                    $q->where('source_type', \App\Models\Payment::class)->whereIn('source_id', $paymentIds);
                });
            })->pluck('id');
            if ($journalIds->isNotEmpty()) {
                DB::table('journal_entries')->whereIn('journal_id', $journalIds)->where('chart_of_account_id', $parents['receivable']->id)->update(['chart_of_account_id' => $customer->receivable_account_id]);
                if ($customer->temporary_receivable_account_id) {
                    DB::table('journal_entries')->whereIn('journal_id', $journalIds)->where('chart_of_account_id', $parents['temporary_receivable']->id)->update(['chart_of_account_id' => $customer->temporary_receivable_account_id]);
                }
            }
        }
        foreach (DB::table('vendors')->whereNotNull('payable_account_id')->get(['id', 'payable_account_id', 'type']) as $vendor) {
            $costIds = DB::table('job_costs')->where('vendor_id', $vendor->id)->pluck('id');
            $journalIds = DB::table('journals')->where('source_type', \App\Models\JobCost::class)->whereIn('source_id', $costIds)->pluck('id');
            $parentKey = in_array($vendor->type, ['international_agent', 'national_agent'], true) ? 'agent_payable' : 'vendor_payable';
            if ($journalIds->isNotEmpty()) {
                DB::table('journal_entries')->whereIn('journal_id', $journalIds)->where('chart_of_account_id', $parents[$parentKey]->id)->update(['chart_of_account_id' => $vendor->payable_account_id]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'temporary_receivable_account_id')) $table->dropConstrainedForeignId('temporary_receivable_account_id');
            if (Schema::hasColumn('customers', 'receivable_account_id')) $table->dropConstrainedForeignId('receivable_account_id');
        });
        Schema::table('vendors', function (Blueprint $table) {
            if (Schema::hasColumn('vendors', 'payable_account_id')) $table->dropConstrainedForeignId('payable_account_id');
        });
    }
};
