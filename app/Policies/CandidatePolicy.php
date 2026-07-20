<?php

namespace App\Policies;

use App\Models\Candidate;
use App\Models\User;

class CandidatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('can_view_candidates') || $user->can('can_manage_candidates');
    }

    public function view(User $user, Candidate $candidate): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can('can_manage_candidates');
    }

    public function update(User $user, Candidate $candidate): bool
    {
        return $user->can('can_manage_candidates');
    }

    public function changeStage(User $user, Candidate $candidate): bool
    {
        return $user->can('can_manage_candidates');
    }

    public function manageInterviews(User $user, Candidate $candidate): bool
    {
        return $user->can('can_manage_interviews');
    }

    public function hire(User $user, Candidate $candidate): bool
    {
        return $user->can('can_hire_candidate');
    }
}
