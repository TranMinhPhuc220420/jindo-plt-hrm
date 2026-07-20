<?php

namespace App\Policies;

use App\Models\LeaveType;
use App\Models\User;

class LeaveTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('can_view_leave') || $user->can('can_manage_leave_types');
    }

    public function view(User $user, LeaveType $leaveType): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can('can_manage_leave_types');
    }

    public function update(User $user, LeaveType $leaveType): bool
    {
        return $user->can('can_manage_leave_types');
    }
}
