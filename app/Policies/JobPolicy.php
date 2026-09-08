<?php

namespace App\Policies;

use App\Models\Job;
use App\Models\User;

class JobPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('jobs.view');
    }

    public function view(User $user, Job $job): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Job $job): bool
    {
        return $user->hasPermission('jobs.manage') && in_array($job->status, ['draft', 'open'], true);
    }

    public function open(User $user, Job $job): bool
    {
        return $user->hasPermission('jobs.manage') && $job->status === 'draft';
    }

    public function cancel(User $user, Job $job): bool
    {
        return $user->hasPermission('jobs.manage') && $job->status === 'open';
    }
}
