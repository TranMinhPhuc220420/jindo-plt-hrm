<?php

namespace App\Policies;

use App\Models\OvertimeRule;
use App\Models\User;

class OvertimeRulePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('can_manage_overtime_rules')
            || $user->can('can_view_shifts');
    }

    public function manage(User $user): bool
    {
        return $user->can('can_manage_overtime_rules');
    }
}
