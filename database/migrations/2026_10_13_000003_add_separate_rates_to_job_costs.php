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
            if (! Schema::hasColumn('job_costs', 'cost_exchange_rate')) {
                $table->decimal('cost_exchange_rate', 18, 4)->default(1)->after('exchange_rate');
            }
            if (! Schema::hasColumn('job_costs', 'price_exchange_rate')) {
                $table->decimal('price_exchange_rate', 18, 4)->default(1)->after('cost_exchange_rate');
            }
        });

        DB::table('job_costs')->orderBy('id')->each(function (object $cost): void {
            $rate = $cost->exchange_rate ?: 1;
            DB::table('job_costs')->where('id', $cost->id)->update([
                'cost_exchange_rate' => $rate,
                'price_exchange_rate' => $rate,
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('job_costs', function (Blueprint $table) {
            $columns = collect(['price_exchange_rate', 'cost_exchange_rate'])
                ->filter(fn (string $column) => Schema::hasColumn('job_costs', $column))
                ->all();
            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
