<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
return new class extends Migration {
    public function up(): void {
        foreach ([['temporary_receivable','1106','Piutang Temporary','asset'],['agent_receivable','1107','Piutang Agent','asset'],['agent_payable','2103','Hutang Agent','liability']] as [$key,$code,$name,$type]) {
            $id = DB::table('chart_of_accounts')->where('code',$code)->value('id');
            if (! $id) $id = DB::table('chart_of_accounts')->insertGetId(['code'=>$code,'name'=>$name,'type'=>$type,'level'=>1,'created_at'=>now(),'updated_at'=>now()]);
            DB::table('account_mappings')->updateOrInsert(['key'=>$key],['chart_of_account_id'=>$id,'updated_at'=>now()]);
        }
    }
    public function down(): void { DB::table('account_mappings')->whereIn('key',['temporary_receivable','agent_receivable','agent_payable'])->delete(); }
};
