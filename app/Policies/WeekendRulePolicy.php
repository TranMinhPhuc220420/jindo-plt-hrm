<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WeekendRule;

class WeekendRulePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('can_view_leave') || $user->can('can_manage_holidays');
    }

    public function update(User $user): bool
    {
        return $user->can('can_manage_holidays');
    }
}
