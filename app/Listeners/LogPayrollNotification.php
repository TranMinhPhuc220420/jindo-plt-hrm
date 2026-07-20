<?php

namespace App\Listeners;

use App\Events\PayrollApproved;
use App\Events\PayrollCalculated;
use App\Events\PayrollFinalized;
use App\Events\SalaryChanged;
use App\Services\Notification\NotificationRecipientResolver;
use App\Services\Notification\NotificationService;
use Illuminate\Support\Facades\Log;

class LogPayrollNotification
{
    public function __construct(
        private readonly NotificationService $notifications,
        private readonly NotificationRecipientResolver $recipients,
    ) {}

    public function handleSalaryChanged(SalaryChanged $event): void
    {
        $salary = $event->salary;
        Log::info('payroll.notification', [
            'type' => 'payroll.salary_changed',
            'employee_id' => $salary->employee_id,
            'salary_id' => $salary->id,
        ]);

        $this->notifications->notifyEmployee(
            employeeId: $salary->employee_id,
            type: 'payroll.salary_changed',
            data: ['salary_id' => $salary->id],
            companyId: $salary->company_id,
        );
    }

    public function handleCalculated(PayrollCalculated $event): void
    {
        $run = $event->payrollRun;
        Log::info('payroll.notification', [
            'type' => 'payroll.calculated',
            'payroll_run_id' => $run->id,
        ]);

        $data = ['payroll_run_id' => $run->id];

        foreach ($this->recipients->usersWithPermissionInCompany($run->company_id, 'can_approve_payroll') as $ops) {
            $this->notifications->notify(
                user: $ops,
                type: 'payroll.calculated',
                data: $data,
                companyId: $run->company_id,
            );
        }
    }

    public function handleApproved(PayrollApproved $event): void
    {
        $run = $event->payrollRun;
        Log::info('payroll.notification', [
            'type' => 'payroll.approved',
            'payroll_run_id' => $run->id,
        ]);

        $data = ['payroll_run_id' => $run->id];

        foreach ($this->recipients->usersWithPermissionInCompany($run->company_id, 'can_run_payroll') as $ops) {
            $this->notifications->notify(
                user: $ops,
                type: 'payroll.approved',
                data: $data,
                companyId: $run->company_id,
            );
        }
    }

    public function handleFinalized(PayrollFinalized $event): void
    {
        $run = $event->payrollRun;
        Log::info('payroll.notification', [
            'type' => 'payroll.finalized',
            'payroll_run_id' => $run->id,
        ]);

        // Notify each employee whose payslip is now available.
        $run->loadMissing('payslips');
        foreach ($run->payslips as $payslip) {
            $this->notifications->notifyEmployee(
                employeeId: $payslip->employee_id,
                type: 'payroll.finalized',
                data: ['payroll_run_id' => $run->id, 'payslip_id' => $payslip->id],
                companyId: $run->company_id,
            );
        }
    }
}
