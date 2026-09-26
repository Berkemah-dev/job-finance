<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { Schema::table('job_costs', function (Blueprint $t) {
        $t->date('paid_date')->nullable()->after('finalized_at');
        $t->timestamp('paid_at')->nullable()->after('paid_date');
        $t->foreignId('payment_account_id')->nullable()->after('paid_at')->constrained('chart_of_accounts')->nullOnDelete();
        $t->decimal('pph23_amount', 18, 2)->default(0)->after('payment_account_id');
    });
        $accountId = \Illuminate\Support\Facades\DB::table('chart_of_accounts')->where('code', '2101')->value('id');
        if (! $accountId) { $accountId = \Illuminate\Support\Facades\DB::table('chart_of_accounts')->insertGetId(['code'=>'2101','name'=>'Hutang Vendor','type'=>'liability','level'=>1,'created_at'=>now(),'updated_at'=>now()]); }
        \Illuminate\Support\Facades\DB::table('account_mappings')->updateOrInsert(['key'=>'vendor_payable'], ['chart_of_account_id'=>$accountId,'updated_at'=>now()]);
    }
    public function down(): void { Schema::table('job_costs', function (Blueprint $t) { $t->dropConstrainedForeignId('payment_account_id'); $t->dropColumn(['paid_date','paid_at','pph23_amount']); }); }
};
