<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_costs', function (Blueprint $table) {
            if (! Schema::hasColumn('job_costs', 'currency')) {
                $table->string('currency', 10)->default('IDR')->after('unit');
            }
            if (! Schema::hasColumn('job_costs', 'exchange_rate')) {
                $table->decimal('exchange_rate', 18, 4)->default(1)->after('currency');
            }
            if (! Schema::hasColumn('job_costs', 'foreign_unit_cost')) {
                $table->decimal('foreign_unit_cost', 18, 2)->nullable()->after('unit_price');
            }
            if (! Schema::hasColumn('job_costs', 'foreign_unit_price')) {
                $table->decimal('foreign_unit_price', 18, 2)->nullable()->after('foreign_unit_cost');
            }
        });
    }

    public function down(): void
    {
        Schema::table('job_costs', function (Blueprint $table) {
            // Kolom currency/kurs bisa berasal dari deployment lama, jadi rollback
            // migration ini hanya menghapus kolom sumber yang memang ditambah di sini.
            $columns = collect(['foreign_unit_price', 'foreign_unit_cost'])
                ->filter(fn (string $column) => Schema::hasColumn('job_costs', $column))
                ->all();
            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
