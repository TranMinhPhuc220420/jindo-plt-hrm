<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewRoles(User $actor, User $subject): bool
    {
        return $actor->can('can_view_roles') || $actor->can('can_assign_roles');
    }

    public function assignRoles(User $actor, User $subject): bool
    {
        return $actor->can('can_assign_roles');
    }
}
