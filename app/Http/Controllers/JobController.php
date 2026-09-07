<?php

namespace App\Http\Controllers;

use App\Models\Job;
use Illuminate\Http\Request;

class JobController extends Controller
{
    public function index(Request $request)
    {
        $search = mb_substr($request->string('search')->toString(), 0, 100);
        $jobs = Job::with('customer')->when($search, fn ($q) => $q->where(fn ($q) => $q->where('number', 'like', '%'.$search.'%')->orWhere('subject', 'like', '%'.$search.'%')))
            ->latest('id')->paginate(15)->withQueryString();

        return view('jobs.index', compact('jobs', 'search'));
    }

    public function show(Job $job)
    {
        return view('jobs.show', ['job' => $job->load(['quotation', 'customer'])]);
    }
}
