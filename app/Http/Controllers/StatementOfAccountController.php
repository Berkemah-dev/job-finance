<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReportFilterRequest;
use App\Mail\StatementOfAccountMail;
use App\Models\Customer;
use App\Models\SoaEmailLog;
use App\Services\StatementOfAccountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class StatementOfAccountController extends Controller
{
    public function __construct(private StatementOfAccountService $service) {}

    public function index(Request $request)
    {
        $search = mb_substr($request->string('search')->toString(), 0, 60);
        $unpaid = $request->boolean('unpaid');

        return view('reports.soa.index', ['result' => $this->service->summary($search, $unpaid), 'search' => $search, 'unpaid' => $unpaid]);
    }

    public function show(int $soaCustomer, ReportFilterRequest $request)
    {
        $from = $request->date('from') ?? today()->startOfMonth();
        $to = $request->date('to') ?? today();
        $customer = Customer::withTrashed()->findOrFail($soaCustomer);

        return view('reports.soa.show', ['customer' => $customer->load('contacts'), 'statement' => $this->service->statement($customer, $from, $to), 'from' => $from, 'to' => $to]);
    }

    public function email(int $soaCustomer, Request $request)
    {
        Gate::authorize('reports.view');
        Gate::authorize('email.manage');

        $customer = Customer::withTrashed()->findOrFail($soaCustomer);

        $emails = collect(preg_split('/[\s,;]+/', trim((string) $request->input('emails'))))->map(fn ($email) => trim($email))->filter(fn ($email) => $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) !== false)->unique()->values()->all();
        if (empty($emails)) {
            throw ValidationException::withMessages(['emails' => 'Isi setidaknya satu alamat email tujuan yang valid.']);
        }

        $from = $request->date('from') ?? today()->startOfMonth();
        $to = $request->date('to') ?? today();

        $status = 'sent';
        $errorMessage = null;
        try {
            Mail::to($emails)->send(new StatementOfAccountMail($customer->load('contacts'), $this->service->statement($customer, $from, $to), $from, $to));
        } catch (\Throwable $e) {
            $status = 'failed';
            $errorMessage = $e->getMessage();
            Log::error('SOA email failed', ['customer_id' => $customer->id, 'error' => $e->getMessage()]);
        }

        SoaEmailLog::create([
            'customer_id'  => $customer->id,
            'recipients'   => $emails,
            'subject'      => 'Statement of Account — '.$customer->name.' ('.$from->format('d/m/Y').' – '.$to->format('d/m/Y').')',
            'period_from'  => $from,
            'period_to'    => $to,
            'status'       => $status,
            'error_message'=> $errorMessage,
            'sent_by'      => $request->user()->id,
            'sent_at'      => now(),
        ]);

        if ($status === 'failed') {
            return back()->withErrors(['email' => 'Email gagal dikirim: '.$errorMessage]);
        }

        return back()->with('success', 'SOA '.$customer->name.' dikirim ke '.implode(', ', $emails).'.');
    }
}
