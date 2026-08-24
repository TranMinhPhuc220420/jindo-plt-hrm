<?php

namespace App\Support;

use App\Exceptions\DomainException;
use App\Models\Employee;
use App\Models\User;

final class EmployeeAccountGate
{
    public static function assertUserCanAuthenticate(User $user): void
    {
        $user->loadMissing('employee');
        $employee = $user->employee;

        if ($employee === null || ! $employee->isLoginBlocked()) {
            return;
        }

        throw new DomainException(
            message: self::loginBlockedMessage($employee->status),
            errorCode: 'AUTH_ACCOUNT_INACTIVE',
            status: 403,
        );
    }

    public static function assertCanPunch(Employee $employee): void
    {
        if ($employee->canPunch()) {
            return;
        }

        throw new DomainException(
            message: 'This account cannot record attendance.',
            errorCode: 'EMPLOYEE_ACCOUNT_INACTIVE',
            status: 403,
        );
    }

    private static function loginBlockedMessage(string $status): string
    {
        return match ($status) {
            'suspended' => 'This account cannot sign in because the employee is on leave.',
            'resigned' => 'This account cannot sign in because the employee has resigned.',
            'archived' => 'This account cannot sign in because the employee is archived.',
            default => 'This account cannot sign in.',
        };
    }
}
