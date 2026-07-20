<?php

namespace App\Policies;

use App\Models\ShiftAssignment;
use App\Models\User;

class ShiftAssignmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('can_view_shifts');
    }

    public function view(User $user, ShiftAssignment $assignment): bool
    {
        return $user->can('can_view_shifts');
    }

    public function create(User $user): bool
    {
        return $user->can('can_assign_shifts');
    }

    public function update(User $user, ShiftAssignment $assignment): bool
    {
        return $user->can('can_assign_shifts');
    }

    public function delete(User $user, ShiftAssignment $assignment): bool
    {
        return $user->can('can_assign_shifts');
    }
}
