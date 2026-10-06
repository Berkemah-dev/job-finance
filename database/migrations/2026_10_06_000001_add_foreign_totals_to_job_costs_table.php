<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_costs', function (Blueprint $table) {
            if (! Schema::hasColumn('job_costs', 'foreign_total_cost')) {
                $table->decimal('foreign_total_cost', 18, 2)->nullable()->after('total_cost');
            }
            if (! Schema::hasColumn('job_costs', 'foreign_total_price')) {
                $table->decimal('foreign_total_price', 18, 2)->nullable()->after('total_price');
            }
        });

        foreach (DB::table('job_costs')->cursor() as $cost) {
            $rate = (float) ($cost->exchange_rate ?? 1);
            $foreignCost = $rate > 0 ? round((float) ($cost->total_cost ?? 0) / $rate, 2) : null;
            $foreignPrice = $rate > 0 ? round((float) ($cost->total_price ?? 0) / $rate, 2) : null;

            DB::table('job_costs')->where('id', $cost->id)->update([
                'foreign_total_cost' => $cost->currency === 'IDR' ? ($cost->total_cost ?? 0) : $foreignCost,
                'foreign_total_price' => $cost->currency === 'IDR' ? ($cost->total_price ?? 0) : $foreignPrice,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('job_costs', function (Blueprint $table) {
            $columns = collect(['foreign_total_price', 'foreign_total_cost'])
                ->filter(fn (string $column) => Schema::hasColumn('job_costs', $column))
                ->all();

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
