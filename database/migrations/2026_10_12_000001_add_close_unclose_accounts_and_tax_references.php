<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        foreach (config('accounting.mappings') as $key => $settings) {
            if (! str_contains($key, '_closed') && ! str_contains($key, '_unclosed')) continue;
            $id = DB::table('chart_of_accounts')->where('code', $settings['code'])->value('id');
            if (! $id) $id = DB::table('chart_of_accounts')->insertGetId(['code'=>$settings['code'], 'name'=>$settings['name'], 'type'=>$settings['type'], 'level'=>2, 'created_at'=>now(), 'updated_at'=>now()]);
            DB::table('account_mappings')->updateOrInsert(['key'=>$key], ['chart_of_account_id'=>$id, 'updated_at'=>now()]);
        }
        Schema::table('job_costs', function (Blueprint $table) {
            if (! Schema::hasColumn('job_costs', 'withholding_document_number')) $table->string('withholding_document_number', 100)->nullable()->after('pph23_amount');
        });
        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'withholding_document_number')) $table->string('withholding_document_number', 100)->nullable()->after('pph23_amount');
        });
    }
    public function down(): void {}
};
