<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\User;

class LeaveRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('can_view_leave');
    }

    public function view(User $user, LeaveRequest $leaveRequest): bool
    {
        if (! $user->can('can_view_leave')) {
            return false;
        }

        if ($this->canManageCompanyWide($user)) {
            return true;
        }

        return $user->employee?->id === $leaveRequest->employee_id;
    }

    public function create(User $user): bool
    {
        return $user->can('can_request_leave');
    }

    public function cancel(User $user, LeaveRequest $leaveRequest): bool
    {
        if (! $user->can('can_request_leave') && ! $user->can('can_manage_leave_balances')) {
            return false;
        }

        if ($user->can('can_manage_leave_balances')) {
            return true;
        }

        return $user->employee?->id === $leaveRequest->employee_id;
    }

    public function approve(User $user, LeaveRequest $leaveRequest): bool
    {
        if (! $user->can('can_approve_leave')) {
            return false;
        }

        if ($this->canManageCompanyWide($user)) {
            return true;
        }

        $approverEmployee = $user->employee;
        if ($approverEmployee === null) {
            return false;
        }

        $requester = $leaveRequest->employee ?? Employee::query()->find($leaveRequest->employee_id);

        return $requester !== null && $requester->manager_id === $approverEmployee->id;
    }

    private function canManageCompanyWide(User $user): bool
    {
        return $user->can('can_manage_leave_types')
            || $user->can('can_manage_leave_balances');
    }
}
