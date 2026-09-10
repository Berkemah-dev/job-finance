<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReportFilterRequest;
use App\Mail\StatementOfAccountMail;
use App\Models\Customer;
use App\Services\StatementOfAccountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
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

    public function show(Customer $customer, ReportFilterRequest $request)
    {
        $from = $request->date('from') ?? today()->startOfMonth();
        $to = $request->date('to') ?? today();

        return view('reports.soa.show', ['customer' => $customer->load('contacts'), 'statement' => $this->service->statement($customer, $from, $to), 'from' => $from, 'to' => $to]);
    }

    public function email(Customer $customer, Request $request)
    {
        Gate::authorize('reports.view');
        Gate::authorize('email.manage');

        $emails = collect(preg_split('/[\s,;]+/', trim((string) $request->input('emails'))))->map(fn ($email) => trim($email))->filter(fn ($email) => $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) !== false)->unique()->values()->all();
        if (empty($emails)) {
            throw ValidationException::withMessages(['emails' => 'Isi setidaknya satu alamat email tujuan yang valid.']);
        }

        $from = $request->date('from') ?? today()->startOfMonth();
        $to = $request->date('to') ?? today();
        Mail::to($emails)->send(new StatementOfAccountMail($customer->load('contacts'), $this->service->statement($customer, $from, $to), $from, $to));

        return back()->with('success', 'SOA '.$customer->name.' dikirim ke '.implode(', ', $emails).'.');
    }
}
