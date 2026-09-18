<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Dnp;
use App\Models\Job;
use Illuminate\Http\Request;

class DnpController extends Controller
{
    public function index(Request $request)
    {
        $search   = $request->query('search');
        $status   = $request->query('status');
        $dateFrom = $request->query('date_from');
        $dateTo   = $request->query('date_to');

        $query = Dnp::with(['customer', 'job', 'creator'])
            ->latest('dnp_date')
            ->latest('id');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('number', 'like', "%{$search}%")
                    ->orWhere('consignee_name', 'like', "%{$search}%")
                    ->orWhere('shipper_name', 'like', "%{$search}%")
                    ->orWhere('importer_name', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn($cq) => $cq->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('job', fn($jq) => $jq->where('number', 'like', "%{$search}%"));
            });
        }

        if ($status) $query->where('status', $status);
        if ($dateFrom) $query->whereDate('dnp_date', '>=', $dateFrom);
        if ($dateTo) $query->whereDate('dnp_date', '<=', $dateTo);

        $dnps = $query->paginate(15)->withQueryString();

        return view('dnps.index', compact('dnps', 'search', 'status', 'dateFrom', 'dateTo'));
    }

    public function create(Request $request)
    {
        $selectedJob = null;
        if ($jobId = $request->query('job_id')) {
            $selectedJob = Job::with(['customer', 'quotation'])->find($jobId);
        }

        $jobs = Job::with(['customer', 'quotation'])->latest('id')->limit(50)->get();
        $defaultNumber = Dnp::generateNumber();
        $supportingDocsList = Dnp::defaultSupportingDocuments();

        return view('dnps.create', compact('selectedJob', 'jobs', 'defaultNumber', 'supportingDocsList'));
    }

    public function store(Request $request)
    {
        // Sanitize numeric inputs (support commas and dots)
        foreach (['invoice_value', 'freight', 'insurance'] as $field) {
            if ($request->has($field)) {
                $request->merge([$field => $this->parseDecimal($request->input($field))]);
            }
        }

        $validated = $request->validate([
            'number'                  => 'required|string|max:60|unique:dnps,number',
            'dnp_date'                => 'required|date',
            'job_id'                  => 'nullable|exists:jobs,id',
            'customer_id'             => 'nullable|exists:customers,id',
            'consignee_name'          => 'nullable|string|max:160',
            'shipper_name'            => 'nullable|string|max:160',
            'importer_name'           => 'nullable|string|max:160',
            'currency'                => 'required|string|max:10',
            'invoice_value'           => 'nullable|numeric|min:0',
            'freight'                 => 'nullable|numeric|min:0',
            'insurance'               => 'nullable|numeric|min:0',
            'is_repeated_transaction' => 'nullable|boolean',
            'supporting_documents'    => 'nullable|array',
            'status'                  => 'nullable|string|in:draft,submitted,approved,completed,cancelled',
            'notes'                   => 'nullable|string',
        ]);

        $validated['status'] = $validated['status'] ?? 'draft';
        $validated['is_repeated_transaction'] = $request->boolean('is_repeated_transaction');
        $validated['total_value'] = (float)($validated['invoice_value'] ?? 0)
                                  + (float)($validated['freight'] ?? 0)
                                  + (float)($validated['insurance'] ?? 0);
        $validated['created_by'] = $request->user()->id;

        $dnp = Dnp::create($validated);

        return redirect()->route('dnps.show', $dnp)
            ->with('success', 'DNP ' . $dnp->number . ' berhasil diterbitkan.');
    }

    public function show(Dnp $dnp)
    {
        $dnp->load(['customer', 'job', 'creator']);
        $supportingDocsList = Dnp::defaultSupportingDocuments();

        return view('dnps.show', compact('dnp', 'supportingDocsList'));
    }

    public function edit(Dnp $dnp)
    {
        $jobs = Job::with(['customer', 'quotation'])->latest('id')->limit(50)->get();
        $supportingDocsList = Dnp::defaultSupportingDocuments();

        return view('dnps.edit', compact('dnp', 'jobs', 'supportingDocsList'));
    }

    public function update(Request $request, Dnp $dnp)
    {
        // Sanitize numeric inputs
        foreach (['invoice_value', 'freight', 'insurance'] as $field) {
            if ($request->has($field)) {
                $request->merge([$field => $this->parseDecimal($request->input($field))]);
            }
        }

        $validated = $request->validate([
            'number'                  => 'required|string|max:60|unique:dnps,number,' . $dnp->id,
            'dnp_date'                => 'required|date',
            'job_id'                  => 'nullable|exists:jobs,id',
            'customer_id'             => 'nullable|exists:customers,id',
            'consignee_name'          => 'nullable|string|max:160',
            'shipper_name'            => 'nullable|string|max:160',
            'importer_name'           => 'nullable|string|max:160',
            'currency'                => 'required|string|max:10',
            'invoice_value'           => 'nullable|numeric|min:0',
            'freight'                 => 'nullable|numeric|min:0',
            'insurance'               => 'nullable|numeric|min:0',
            'is_repeated_transaction' => 'nullable|boolean',
            'supporting_documents'    => 'nullable|array',
            'status'                  => 'nullable|string|in:draft,submitted,approved,completed,cancelled',
            'notes'                   => 'nullable|string',
        ]);

        $validated['status'] = $validated['status'] ?? ($dnp->status ?? 'draft');
        $validated['is_repeated_transaction'] = $request->boolean('is_repeated_transaction');
        $validated['total_value'] = (float)($validated['invoice_value'] ?? 0)
                                  + (float)($validated['freight'] ?? 0)
                                  + (float)($validated['insurance'] ?? 0);

        $dnp->update($validated);

        return redirect()->route('dnps.show', $dnp)
            ->with('success', 'DNP ' . $dnp->number . ' berhasil diperbarui.');
    }

    public function destroy(Dnp $dnp)
    {
        $jobId  = $dnp->job_id;
        $number = $dnp->number;
        $dnp->delete();

        if ($jobId) {
            return redirect()->to(route('jobs.show', $jobId) . '#tab-dnp')
                ->with('success', 'DNP ' . $number . ' berhasil dihapus.');
        }

        return redirect()->route('dnps.index')
            ->with('success', 'DNP ' . $number . ' berhasil dihapus.');
    }

    public function previewPdf(Dnp $dnp)
    {
        $dnp->load(['job.customer', 'job.quotation']);
        $job = $dnp->job;
        $quotation = $job?->quotation;

        $pdf = app('dompdf.wrapper')->loadView('documents.pdf.dnp', [
            'dnp'       => $dnp,
            'job'       => $job,
            'quotation' => $quotation,
        ])->setPaper('a4');

        $filename = 'DNP_' . str_replace('/', '_', $dnp->number) . '.pdf';

        return $pdf->stream($filename);
    }

    private function parseDecimal($value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }
        if (is_numeric($value)) {
            return (float)$value;
        }
        $val = trim((string)$value);
        if (str_contains($val, ',') && str_contains($val, '.')) {
            $lastComma = strrpos($val, ',');
            $lastDot = strrpos($val, '.');
            if ($lastDot > $lastComma) {
                $val = str_replace(',', '', $val);
            } else {
                $val = str_replace('.', '', $val);
                $val = str_replace(',', '.', $val);
            }
        } elseif (str_contains($val, ',')) {
            $val = str_replace(',', '.', $val);
        }

        return is_numeric($val) ? (float)$val : 0.0;
    }
}
