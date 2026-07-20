<?php

namespace App\Policies;

use App\Models\PayrollRun;
use App\Models\User;

class PayrollRunPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('can_view_payroll_history') || $user->can('can_run_payroll');
    }

    public function view(User $user, PayrollRun $payrollRun): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can('can_run_payroll');
    }

    public function update(User $user, PayrollRun $payrollRun): bool
    {
        return $user->can('can_run_payroll');
    }

    public function delete(User $user, PayrollRun $payrollRun): bool
    {
        return $user->can('can_run_payroll');
    }

    public function calculate(User $user, PayrollRun $payrollRun): bool
    {
        return $user->can('can_run_payroll');
    }

    public function approve(User $user, PayrollRun $payrollRun): bool
    {
        return $user->can('can_approve_payroll');
    }

    public function finalize(User $user, PayrollRun $payrollRun): bool
    {
        return $user->can('can_approve_payroll') || $user->can('can_run_payroll');
    }
}
