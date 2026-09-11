<?php
namespace App\Http\Controllers;
use App\Models\Invoice;
use App\Models\Job;
use App\Models\Quotation;
use Illuminate\Http\Request;
use Illuminate\View\View;
class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $user=$request->user(); $notifications=collect();
        if($user->hasPermission('financial.view')) $notifications=$notifications->merge(Invoice::where('balance','>',0)->where('due_date','<=',today()->addDays(7))->orderBy('due_date')->limit(8)->get()->map(fn($i)=>['title'=>'Invoice perlu ditindaklanjuti','message'=>$i->number.' · jatuh tempo '.$i->due_date->format('d/m/Y'),'url'=>route('invoices.show',$i),'type'=>$i->due_date->isPast()?'danger':'warning']));
        if($user->hasPermission('quotations.manage')) $notifications=$notifications->merge(Quotation::whereIn('status',['submitted','revision'])->latest('id')->limit(8)->get()->map(fn($q)=>['title'=>'Quotation menunggu tindak lanjut','message'=>$q->number.' · '.$q->status->label(),'url'=>route('quotations.show',$q),'type'=>'info']));
        if($user->hasPermission('jobs.manage')) $notifications=$notifications->merge(Job::where('status','open')->whereHas('costs',fn($q)=>$q->where('status','!=','final'))->latest('id')->limit(8)->get()->map(fn($j)=>['title'=>'Biaya job belum final','message'=>$j->number.' · '.$j->subject,'url'=>route('jobs.show',$j),'type'=>'warning']));
        if($user->hasPermission('jobs.view')) $notifications=$notifications->merge(Job::where('status','open')->whereBetween('eta',[today(),today()->addDays(7)])->orderBy('eta')->limit(8)->get()->map(fn($j)=>['title'=>'ETA job mendekat','message'=>$j->number.' · tiba '.$j->eta->format('d/m/Y'),'url'=>route('jobs.show',$j),'type'=>'info']));
        return view('notifications.index',['notifications'=>$notifications->take(20)->values()]);
    }
}
