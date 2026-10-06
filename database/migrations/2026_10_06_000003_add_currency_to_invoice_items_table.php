<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            if (! Schema::hasColumn('invoice_items', 'currency')) {
                $table->string('currency', 10)->default('IDR')->after('unit');
            }
            if (! Schema::hasColumn('invoice_items', 'exchange_rate')) {
                $table->decimal('exchange_rate', 18, 4)->default(1)->after('currency');
            }
            if (! Schema::hasColumn('invoice_items', 'foreign_unit_price')) {
                $table->decimal('foreign_unit_price', 18, 2)->nullable()->after('unit_price');
            }
        });

        // Invoice lama mengikuti sumber Job Cost jika deskripsi dan tipe cocok.
        DB::table('invoice_items as item')
            ->join('invoices as invoice', 'invoice.id', '=', 'item.invoice_id')
            ->select('item.id as item_id', 'item.description', 'item.type', 'item.unit_price', 'invoice.job_id')
            ->orderBy('item.id')
            ->each(function (object $item): void {
                $cost = DB::table('job_costs')
                    ->where('job_id', $item->job_id)
                    ->where('type', $item->type)
                    ->where('description', $item->description)
                    ->orderBy('id')
                    ->first(['currency', 'exchange_rate', 'foreign_unit_cost', 'foreign_unit_price', 'unit_cost', 'unit_price']);

                if (! $cost) {
                    return;
                }

                $currency = strtoupper((string) ($cost->currency ?: 'IDR'));
                $foreignPrice = $item->type === 'temporary'
                    ? ($cost->foreign_unit_cost ?? $cost->unit_cost)
                    : ($cost->foreign_unit_price ?? $cost->unit_price);

                DB::table('invoice_items')->where('id', $item->item_id)->update([
                    'currency' => $currency,
                    'exchange_rate' => $cost->exchange_rate ?: 1,
                    'foreign_unit_price' => $foreignPrice,
                ]);
            });
    }

    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $columns = collect(['foreign_unit_price', 'exchange_rate', 'currency'])
                ->filter(fn (string $column) => Schema::hasColumn('invoice_items', $column))
                ->all();
            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
