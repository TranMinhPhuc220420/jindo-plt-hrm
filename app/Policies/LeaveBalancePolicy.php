<?php

namespace App\Policies;

use App\Models\User;

class LeaveBalancePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('can_view_leave') || $user->can('can_manage_leave_balances');
    }

    public function adjust(User $user): bool
    {
        return $user->can('can_manage_leave_balances');
    }
}
