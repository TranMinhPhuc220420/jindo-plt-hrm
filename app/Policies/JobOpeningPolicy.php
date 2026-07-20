<?php

namespace App\Policies;

use App\Models\JobOpening;
use App\Models\User;

class JobOpeningPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('can_manage_job_positions') || $user->can('can_view_candidates');
    }

    public function view(User $user, JobOpening $jobOpening): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can('can_manage_job_positions');
    }

    public function update(User $user, JobOpening $jobOpening): bool
    {
        return $user->can('can_manage_job_positions');
    }

    public function close(User $user, JobOpening $jobOpening): bool
    {
        return $user->can('can_manage_job_positions');
    }
}
