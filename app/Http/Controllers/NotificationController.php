<?php
namespace App\Http\Controllers;
use App\Models\Invoice;
use App\Models\Job;
use App\Models\Quotation;
use App\Enums\QuotationStatus;
use Illuminate\Http\Request;
use Illuminate\View\View;
class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $user=$request->user(); $notifications=collect();
        if($user->hasPermission('financial.view')) $notifications=$notifications->merge(Invoice::where('balance','>',0)->where('due_date','<=',today()->addDays(7))->orderBy('due_date')->limit(8)->get()->map(fn($i)=>['title'=>'Invoice perlu ditindaklanjuti','message'=>$i->number.' · jatuh tempo '.$i->due_date->format('d/m/Y'),'url'=>route('invoices.show',$i),'type'=>$i->due_date->isPast()?'danger':'warning']));
        if($user->hasPermission('quotations.manage')) {
            $quotationQuery = Quotation::query();
            if ($user->hasRole('sales') && ! $user->hasRole(['sales-manager', 'super-admin', 'admin'])) {
                $quotationQuery->where(function ($q) use ($user) {
                    $q->where('sales_id', $user->id)->orWhere(fn ($nested) => $nested->whereNull('sales_id')->where('created_by', $user->id));
                });
            }
            $pendingStatuses = $user->hasPermission('quotations.approve')
                ? [QuotationStatus::Submitted->value]
                : [QuotationStatus::Submitted->value, QuotationStatus::Revision->value];
            $notifications=$notifications->merge($quotationQuery->whereIn('status', $pendingStatuses)->latest('id')->limit(8)->get()->map(function ($q) use ($user) {
                $title = $q->status === QuotationStatus::Submitted
                    ? ($user->hasPermission('quotations.approve') ? 'Quotation menunggu approval' : 'Quotation belum disetujui')
                    : 'Quotation perlu direvisi';
                return ['title'=>$title,'message'=>$q->number.' · '.$q->customer?->name,'url'=>route('quotations.show',$q),'type'=>$q->status === QuotationStatus::Revision ? 'warning' : 'info'];
            }));
        }
        if($user->hasPermission('jobs.manage')) $notifications=$notifications->merge(Job::where('status','open')->whereHas('costs',fn($q)=>$q->where('status','!=','final'))->latest('id')->limit(8)->get()->map(fn($j)=>['title'=>'Biaya job belum final','message'=>$j->number.' · '.$j->subject,'url'=>route('jobs.show',$j),'type'=>'warning']));
        if ($user->hasPermission('jobs.view')) {
            $isCs = $user->hasRole('customer-service');
            $etaNotifications = Job::approachingEta(7, $isCs)->orderBy('eta')->limit(8)->get()->map(fn ($job) => [
                'title' => $isCs ? 'ETA Import mendekat' : 'ETA job mendekat',
                'message' => $job->number.' · '.($job->service_type === 'imp_sea' ? 'Kapal' : 'Kargo').' tiba '.$job->eta->format('d/m/Y').($job->eta->isToday() ? ' (hari ini)' : ''),
                'url' => route('jobs.show', $job),
                'type' => $job->eta->isToday() ? 'warning' : 'info',
            ]);
            // Prioritaskan ETA untuk CS agar tidak terpotong oleh batas 20 notifikasi lainnya.
            $notifications = $isCs ? $etaNotifications->merge($notifications) : $notifications->merge($etaNotifications);
        }
        return view('notifications.index',['notifications'=>$notifications->take(20)->values()]);
    }
}
