<?php

namespace App\Services\Employee;

use App\Exceptions\DomainException;
use App\Models\Employee;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Organization\CompanyContext;

class EmployeeAccountService
{
    public function __construct(
        private readonly CompanyContext $companyContext,
        private readonly AuditLogger $audit,
    ) {}

    public function defaultPassword(): string
    {
        $password = config('hrm.employee_default_password');

        if (! is_string($password) || $password === '') {
            throw new DomainException(
                message: 'Employee default password is not configured.',
                errorCode: 'EMPLOYEE_DEFAULT_PASSWORD_MISSING',
                status: 500,
            );
        }

        return $password;
    }

    public function provisionForEmployee(Employee $employee): ?User
    {
        $this->assertCompanyScope($employee->company_id);

        if ($employee->user_id !== null) {
            return $employee->user;
        }

        $email = $employee->email ?: 'employee'.$employee->id.'@example.test';

        $user = User::query()->create([
            'name' => $employee->full_name ?: 'Employee '.$employee->id,
            'email' => $email,
            'password' => $this->defaultPassword(),
        ]);

        $employee->user_id = max(0, $user->id);
        $employee->save();

        $this->audit->write(
            action: 'onboarding.account_provisioned',
            subject: $employee,
            payload: ['user_id' => $user->id],
        );

        return $user;
    }

    public function setPassword(Employee $employee, string $plainPassword): void
    {
        $user = $this->requireLinkedUser($employee);

        $user->password = $plainPassword;
        $user->save();

        $this->audit->write(
            action: 'employee.password_changed',
            subject: $employee,
            payload: ['user_id' => $user->id],
        );
    }

    public function resetToDefault(Employee $employee): void
    {
        $user = $this->requireLinkedUser($employee);

        $user->password = $this->defaultPassword();
        $user->save();

        $this->audit->write(
            action: 'employee.password_reset_to_default',
            subject: $employee,
            payload: ['user_id' => $user->id],
        );
    }

    protected function requireLinkedUser(Employee $employee): User
    {
        $this->assertCompanyScope($employee->company_id);

        if ($employee->user_id === null) {
            throw new DomainException(
                message: 'Employee does not have a linked user account.',
                errorCode: 'EMPLOYEE_NO_USER_ACCOUNT',
                status: 422,
            );
        }

        $user = User::query()->find($employee->user_id);

        if ($user === null) {
            throw new DomainException(
                message: 'Employee does not have a linked user account.',
                errorCode: 'EMPLOYEE_NO_USER_ACCOUNT',
                status: 422,
            );
        }

        return $user;
    }

    protected function assertCompanyScope(int $companyId): void
    {
        if ($companyId !== $this->companyContext->id()) {
            throw new DomainException(
                message: 'Resource is outside the current company scope.',
                errorCode: 'COMPANY_SCOPE_MISMATCH',
                status: 404,
            );
        }
    }
}
