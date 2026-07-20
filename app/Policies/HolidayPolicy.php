<?php

namespace App\Policies;

use App\Models\Holiday;
use App\Models\User;

class HolidayPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('can_view_leave') || $user->can('can_manage_holidays');
    }

    public function create(User $user): bool
    {
        return $user->can('can_manage_holidays');
    }

    public function delete(User $user, Holiday $holiday): bool
    {
        return $user->can('can_manage_holidays');
    }
}
