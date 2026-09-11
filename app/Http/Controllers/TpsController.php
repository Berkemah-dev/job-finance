<?php
namespace App\Http\Controllers;
use App\Models\Tps;
use App\Services\MasterDataService;
use Illuminate\Http\Request;
class TpsController extends Controller
{
    public function index(Request $request) { $search=mb_substr($request->string('search')->toString(),0,100); $mode=(string)$request->input('mode',''); $status=(string)$request->input('status','active'); $tpsList=Tps::query()->when($search,fn($q)=>$q->where(fn($q)=>$q->where('name','like','%'.$search.'%')->orWhere('code','like','%'.$search.'%')->orWhere('city','like','%'.$search.'%')))->when(in_array($mode,['air','sea'],true),fn($q)=>$q->where('mode',$mode))->when($status==='inactive',fn($q)=>$q->where('is_active',false),fn($q)=>$q->where('is_active',true))->orderBy('mode')->orderBy('city')->orderBy('name')->paginate(20)->withQueryString(); return view('tps.index',compact('tpsList','search','mode','status')); }
    public function create() { return view('tps.form',['tps'=>new Tps]); }
    public function store(Request $request, MasterDataService $service) { $tps=Tps::create($this->validated($request)); $service->log($request->user(),'tps.created','Menambahkan TPS '.$tps->code.' — '.$tps->name,['module'=>'tps','record_id'=>$tps->id]); return redirect()->route('tps.index')->with('success','TPS berhasil ditambahkan.'); }
    public function edit(Tps $tps) { return view('tps.form',compact('tps')); }
    public function update(Request $request,Tps $tps,MasterDataService $service) { $before=$tps->only(['city','name','code','mode','is_active']); $tps->update($this->validated($request,$tps)); $service->log($request->user(),'tps.updated','Memperbarui TPS '.$tps->code.' — '.$tps->name,['module'=>'tps','record_id'=>$tps->id,'before'=>$before,'after'=>$tps->only(['city','name','code','mode','is_active'])]); return redirect()->route('tps.index')->with('success','TPS berhasil diperbarui.'); }
    public function toggle(Request $request,Tps $tps,MasterDataService $service) { $tps->update(['is_active'=>!$tps->is_active]); $service->log($request->user(),'tps.toggled',($tps->is_active?'Mengaktifkan':'Menonaktifkan').' TPS '.$tps->code,['module'=>'tps','record_id'=>$tps->id]); return back()->with('success','Status TPS berhasil diubah.'); }
    public function destroy(Request $request,Tps $tps,MasterDataService $service) { $service->log($request->user(),'tps.deleted','Menghapus TPS '.$tps->code.' — '.$tps->name,['module'=>'tps','record_id'=>$tps->id]); $tps->delete(); return redirect()->route('tps.index')->with('success','TPS berhasil dihapus.'); }
    private function validated(Request $request,?Tps $tps=null): array { return $request->validate(['city'=>['required','string','max:120'],'name'=>['required','string','max:200'],'code'=>['required','string','max:30','unique:tps,code'.($tps?','.$tps->id:'')],'mode'=>['required','in:air,sea'],'is_active'=>['nullable','boolean']]); }
}
