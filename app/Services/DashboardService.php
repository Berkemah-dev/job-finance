<?php

namespace App\Services;

use App\Models\Job;

class DashboardService
{
    public function summary(): array
    {
        $counts = Job::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return ['openJobs' => (int) ($counts['open'] ?? 0), 'closedJobs' => (int) ($counts['closed'] ?? 0),
            'draftJobs' => (int) ($counts['draft'] ?? 0), 'recentJobs' => Job::latest('id')->limit(5)->get()];
    }
}
