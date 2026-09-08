<?php

namespace App\Policies;

use App\Models\Job;
use App\Models\JobCost;
use App\Models\User;

class JobCostPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('costs.manage');
    }

    public function view(User $user, JobCost $cost): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user, Job $job): bool
    {
        return $this->viewAny($user) && $job->status === 'open';
    }

    public function update(User $user, JobCost $cost): bool
    {
        return $this->create($user, $cost->job) && $cost->status === 'draft' && ! $cost->trashed();
    }

    public function delete(User $user, JobCost $cost): bool
    {
        return $this->update($user, $cost);
    }

    public function finalize(User $user, JobCost $cost): bool
    {
        return $this->update($user, $cost);
    }
}
