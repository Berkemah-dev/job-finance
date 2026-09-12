<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('jobs')) {
            Schema::table('jobs', function (Blueprint $table) {
                $table->index(['status', 'id'], 'jobs_status_id_idx');
                $table->index(['status', 'eta'], 'jobs_status_eta_idx');
                $table->index(['status', 'etd'], 'jobs_status_etd_idx');
                $table->index(['sales_id', 'status'], 'jobs_sales_status_idx');
                $table->index(['cs_id', 'status'], 'jobs_cs_status_idx');
                $table->index(['service_type', 'status'], 'jobs_service_status_idx');
            });
        }

        if (Schema::hasTable('quotations')) {
            Schema::table('quotations', function (Blueprint $table) {
                $table->index(['status', 'id'], 'quotations_status_id_idx');
                $table->index(['customer_id', 'status'], 'quotations_customer_status_idx');
                $table->index(['created_by', 'status'], 'quotations_created_status_idx');
                $table->index(['service_type', 'status'], 'quotations_service_status_idx');
                $table->index(['quotation_date', 'status'], 'quotations_date_status_idx');
            });
        }

        if (Schema::hasTable('invoices')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->index(['balance', 'due_date'], 'invoices_balance_due_idx');
                $table->index(['customer_id', 'invoice_date'], 'invoices_customer_date_idx');
                $table->index(['status', 'id'], 'invoices_status_id_idx');
                $table->index(['tax', 'subtotal', 'id'], 'invoices_tax_subtotal_id_idx');
            });
        }

        if (Schema::hasTable('job_costs')) {
            Schema::table('job_costs', function (Blueprint $table) {
                $table->index(['status', 'type'], 'job_costs_status_type_idx');
                $table->index(['job_id', 'type', 'status'], 'job_costs_job_type_status_idx');
            });
        }

        if (Schema::hasTable('job_closing_snapshots')) {
            Schema::table('job_closing_snapshots', function (Blueprint $table) {
                $table->index(['closing_date', 'profit'], 'job_closing_date_profit_idx');
            });
        }

        if (Schema::hasTable('journals')) {
            Schema::table('journals', function (Blueprint $table) {
                $table->index(['status', 'journal_date'], 'journals_status_date_idx');
                $table->index(['type', 'journal_date'], 'journals_type_date_idx');
            });
        }

        if (Schema::hasTable('booking_confirmations')) {
            Schema::table('booking_confirmations', function (Blueprint $table) {
                $table->index(['job_id', 'status'], 'booking_confirmations_job_status_idx');
                $table->index(['customer_id', 'booking_date'], 'booking_confirmations_customer_date_idx');
            });
        }

        if (Schema::hasTable('shipping_instructions')) {
            Schema::table('shipping_instructions', function (Blueprint $table) {
                $table->index(['job_id', 'status'], 'shipping_instructions_job_status_idx');
                $table->index(['customer_id', 'si_date'], 'shipping_instructions_customer_date_idx');
            });
        }

        if (Schema::hasTable('trucking_prices')) {
            Schema::table('trucking_prices', function (Blueprint $table) {
                $table->index(['is_active', 'port_origin', 'destination', 'container_type', 'overweight'], 'trucking_active_route_lookup_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('trucking_prices')) {
            Schema::table('trucking_prices', fn (Blueprint $table) => $table->dropIndex('trucking_active_route_lookup_idx'));
        }
        if (Schema::hasTable('shipping_instructions')) {
            Schema::table('shipping_instructions', function (Blueprint $table) {
                $table->dropIndex('shipping_instructions_job_status_idx');
                $table->dropIndex('shipping_instructions_customer_date_idx');
            });
        }
        if (Schema::hasTable('booking_confirmations')) {
            Schema::table('booking_confirmations', function (Blueprint $table) {
                $table->dropIndex('booking_confirmations_job_status_idx');
                $table->dropIndex('booking_confirmations_customer_date_idx');
            });
        }
        if (Schema::hasTable('journals')) {
            Schema::table('journals', function (Blueprint $table) {
                $table->dropIndex('journals_status_date_idx');
                $table->dropIndex('journals_type_date_idx');
            });
        }
        if (Schema::hasTable('job_closing_snapshots')) {
            Schema::table('job_closing_snapshots', fn (Blueprint $table) => $table->dropIndex('job_closing_date_profit_idx'));
        }
        if (Schema::hasTable('job_costs')) {
            Schema::table('job_costs', function (Blueprint $table) {
                $table->dropIndex('job_costs_status_type_idx');
                $table->dropIndex('job_costs_job_type_status_idx');
            });
        }
        if (Schema::hasTable('invoices')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropIndex('invoices_balance_due_idx');
                $table->dropIndex('invoices_customer_date_idx');
                $table->dropIndex('invoices_status_id_idx');
                $table->dropIndex('invoices_tax_subtotal_id_idx');
            });
        }
        if (Schema::hasTable('quotations')) {
            Schema::table('quotations', function (Blueprint $table) {
                $table->dropIndex('quotations_status_id_idx');
                $table->dropIndex('quotations_customer_status_idx');
                $table->dropIndex('quotations_created_status_idx');
                $table->dropIndex('quotations_service_status_idx');
                $table->dropIndex('quotations_date_status_idx');
            });
        }
        if (Schema::hasTable('jobs')) {
            Schema::table('jobs', function (Blueprint $table) {
                $table->dropIndex('jobs_status_id_idx');
                $table->dropIndex('jobs_status_eta_idx');
                $table->dropIndex('jobs_status_etd_idx');
                $table->dropIndex('jobs_sales_status_idx');
                $table->dropIndex('jobs_cs_status_idx');
                $table->dropIndex('jobs_service_status_idx');
            });
        }
    }
};
