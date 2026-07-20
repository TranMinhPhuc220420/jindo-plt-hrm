<?php

namespace App\Policies;

use App\Models\EmployeeSalary;
use App\Models\User;

class EmployeeSalaryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('can_view_salary') || $user->can('can_manage_salary');
    }

    public function view(User $user, EmployeeSalary $salary): bool
    {
        if ($user->can('can_manage_salary')) {
            return true;
        }

        return $user->can('can_view_salary')
            && $user->employee?->id === $salary->employee_id;
    }

    public function manage(User $user): bool
    {
        return $user->can('can_manage_salary');
    }
}
