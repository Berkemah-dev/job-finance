<?php

namespace App\Http\Controllers;

use App\Enums\QuotationStatus;
use App\Models\Job;
use App\Models\Quotation;
use Illuminate\Http\Request;

class OperationalDocumentController extends Controller
{
    public function index(Request $request)
    {
        $search = mb_substr($request->string('search')->toString(), 0, 100);
        $user = $request->user();

        $quotations = collect();
        if ($user->can('quotations.manage')) {
            $quotations = Quotation::with(['customer', 'job'])
                ->when($search, fn ($query) => $query->where(fn ($query) => $query
                    ->where('number', 'like', '%'.$search.'%')
                    ->orWhere('subject', 'like', '%'.$search.'%')
                    ->orWhereHas('customer', fn ($query) => $query->where('name', 'like', '%'.$search.'%'))))
                ->whereIn('status', [
                    QuotationStatus::Draft,
                    QuotationStatus::Submitted,
                    QuotationStatus::Approved,
                    QuotationStatus::Converted,
                ])
                ->latest('id')
                ->limit(8)
                ->get();
        }

        $jobs = collect();
        if ($user->can('jobs.view')) {
            $jobs = Job::with(['customer', 'quotation'])
                ->when($search, fn ($query) => $query->where(fn ($query) => $query
                    ->where('number', 'like', '%'.$search.'%')
                    ->orWhere('subject', 'like', '%'.$search.'%')
                    ->orWhereHas('customer', fn ($query) => $query->where('name', 'like', '%'.$search.'%'))))
                ->latest('id')
                ->limit(8)
                ->get();
        }

        return view('documents.index', compact('quotations', 'jobs', 'search'));
    }
}
