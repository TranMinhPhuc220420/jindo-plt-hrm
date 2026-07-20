<?php

namespace App\Policies;

use App\Models\Payslip;
use App\Models\User;

class PayslipPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('can_view_salary')
            || $user->can('can_manage_payslips')
            || $user->can('can_view_payroll_history');
    }

    public function view(User $user, Payslip $payslip): bool
    {
        if ($user->can('can_manage_payslips') || $user->can('can_view_payroll_history')) {
            return true;
        }

        return $user->can('can_view_salary')
            && $user->employee?->id === $payslip->employee_id;
    }

    public function download(User $user, Payslip $payslip): bool
    {
        return $this->view($user, $payslip);
    }
}
