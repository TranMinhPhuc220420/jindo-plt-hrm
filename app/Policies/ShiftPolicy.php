<?php

namespace App\Policies;

use App\Models\Shift;
use App\Models\User;

class ShiftPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('can_view_shifts');
    }

    public function view(User $user, Shift $shift): bool
    {
        return $user->can('can_view_shifts');
    }

    public function create(User $user): bool
    {
        return $user->can('can_manage_shift_definitions');
    }

    public function update(User $user, Shift $shift): bool
    {
        return $user->can('can_manage_shift_definitions');
    }

    public function delete(User $user, Shift $shift): bool
    {
        return $user->can('can_manage_shift_definitions');
    }
}
