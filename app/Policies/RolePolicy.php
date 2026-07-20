<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;

class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('can_view_roles');
    }

    public function view(User $user, Role $role): bool
    {
        return $user->can('can_view_roles');
    }

    public function create(User $user): bool
    {
        return $user->can('can_manage_roles');
    }

    public function update(User $user, Role $role): bool
    {
        return $user->can('can_manage_roles');
    }

    public function delete(User $user, Role $role): bool
    {
        return $user->can('can_manage_roles');
    }

    public function syncPermissions(User $user, Role $role): bool
    {
        return $user->can('can_manage_roles');
    }
}
