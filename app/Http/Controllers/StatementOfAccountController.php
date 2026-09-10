<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReportFilterRequest;
use App\Models\Customer;
use App\Services\StatementOfAccountService;
use Illuminate\Http\Request;

class StatementOfAccountController extends Controller
{
    public function __construct(private StatementOfAccountService $service) {}

    public function index(Request $request)
    {
        $search = mb_substr($request->string('search')->toString(), 0, 60);

        return view('reports.soa.index', ['result' => $this->service->summary($search), 'search' => $search]);
    }

    public function show(Customer $customer, ReportFilterRequest $request)
    {
        $from = $request->date('from') ?? today()->startOfMonth();
        $to = $request->date('to') ?? today();

        return view('reports.soa.show', ['customer' => $customer->load('contacts'), 'statement' => $this->service->statement($customer, $from, $to), 'from' => $from, 'to' => $to]);
    }
}
