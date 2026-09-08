<?php

namespace App\Http\Controllers;

use App\Http\Requests\JournalAdjustmentRequest;
use App\Http\Requests\JournalFilterRequest;
use App\Http\Requests\JournalReversalRequest;
use App\Models\ChartOfAccount;
use App\Models\Journal;
use App\Services\JournalService;

class JournalController extends Controller
{
    public function index(JournalFilterRequest $request)
    {
        $journals = Journal::withCount('entries')->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))->when($request->filled('from'), fn ($q) => $q->whereDate('journal_date', '>=', $request->date('from')))->when($request->filled('to'), fn ($q) => $q->whereDate('journal_date', '<=', $request->date('to')))->latest('journal_date')->latest('id')->paginate(20)->withQueryString();

        return view('journals.index', compact('journals'));
    }

    public function create()
    {
        return view('journals.create', ['accounts' => ChartOfAccount::orderBy('code')->get()]);
    }

    public function store(JournalAdjustmentRequest $request, JournalService $service)
    {
        $journal = $service->adjustment($request->validated(), $request->user());

        return redirect()->route('journals.show', $journal)->with('success', 'Jurnal penyesuaian berhasil diposting.');
    }

    public function show(Journal $journal)
    {
        return view('journals.show', ['journal' => $journal->load(['entries.account', 'reversal', 'reversalOf'])]);
    }

    public function reverse(JournalReversalRequest $request, Journal $journal, JournalService $service)
    {
        $reversal = $service->reverse($journal, $request->validated(), $request->user());

        return redirect()->route('journals.show', $reversal)->with('success', 'Jurnal reversal berhasil diposting.');
    }
}
