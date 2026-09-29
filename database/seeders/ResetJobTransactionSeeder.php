<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class ResetJobTransactionSeeder extends Seeder
{
    /**
     * Menghapus seluruh transaksi Job Order dan keuangan turunannya.
     * Master data, user, role, customer, vendor, harga, dan COA tetap tersimpan.
     */
    public function run(): void
    {
        $jobIds = Schema::hasTable('jobs') ? DB::table('jobs')->pluck('id') : collect();
        $quotationIds = Schema::hasTable('jobs') ? DB::table('jobs')->whereNotNull('quotation_id')->pluck('quotation_id')->filter() : collect();

        Schema::disableForeignKeyConstraints();
        try {
            DB::transaction(function () use ($jobIds, $quotationIds) {
                foreach ([
                    'payments', 'invoice_items', 'invoices', 'job_closing_snapshots',
                    'journal_entries', 'journals', 'journal_adjustments',
                    'job_costs', 'job_status_history', 'job_shipment_statuses',
                    'job_documents', 'delivery_orders', 'dnps', 'awbs',
                    'bills_of_lading', 'booking_confirmations', 'shipping_instructions',
                    'reimbursements',
                ] as $table) {
                    if (Schema::hasTable($table)) {
                        DB::table($table)->delete();
                    }
                }

                if (Schema::hasTable('jobs')) {
                    DB::table('jobs')->delete();
                }

                // Quotation tetap ada agar data sales tidak hilang, tetapi bisa dibuat Job Order lagi.
                if ($quotationIds->isNotEmpty() && Schema::hasTable('quotations')) {
                    DB::table('quotations')->whereIn('id', $quotationIds)->update([
                        'status' => 'approved', 'converted_by' => null, 'converted_at' => null,
                    ]);
                }

                // Nomor transaksi Job dan jurnal dimulai ulang pada transaksi berikutnya.
                if (Schema::hasTable('document_sequences')) {
                    DB::table('document_sequences')->whereIn('type', [
                        'job', 'cst', 'pr', 'rmb', 'dn', 'cn', 'invoice', 'payment',
                        'jrn-bca-idr-m', 'jrn-bca-idr-k', 'jrn-mdr-idr-m', 'jrn-mdr-idr-k',
                        'jrn-petty-idr-m', 'jrn-petty-idr-k',
                    ])->delete();
                }
            }, 3);

            if ($jobIds->isNotEmpty()) {
                Storage::disk('private')->deleteDirectory('job-documents');
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        $this->command?->info('Seluruh Job Order dan transaksi keuangannya telah dikosongkan. Saldo COA kembali nol.');
    }
}
