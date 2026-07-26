<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;

class EmployeePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('can_view_employee');
    }

    public function view(User $user, Employee $employee): bool
    {
        return $user->can('can_view_employee');
    }

    public function create(User $user): bool
    {
        return $user->can('can_create_employee');
    }

    public function update(User $user, Employee $employee): bool
    {
        return $user->can('can_update_employee');
    }

    public function changeStatus(User $user, Employee $employee): bool
    {
        return $user->can('can_change_employee_status');
    }

    public function manageSensitive(User $user, Employee $employee): bool
    {
        return $user->can('can_manage_employee_sensitive');
    }

    public function updateAvatar(User $user, Employee $employee): bool
    {
        $user->loadMissing('employee');

        if ($user->employee?->id === $employee->id) {
            return true;
        }

        return $user->can('can_update_employee');
    }
}
