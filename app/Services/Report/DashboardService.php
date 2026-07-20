<?php

namespace App\Services\Report;

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\Notification;
use App\Models\PayrollRun;
use App\Models\User;
use App\Services\Organization\CompanyContext;

class DashboardService
{
    public function __construct(
        private readonly CompanyContext $companyContext,
    ) {}

    /**
     * @return array<string, int>
     */
    public function summary(User $actor): array
    {
        $companyId = $this->companyContext->id();

        return [
            'active_employees' => Employee::query()
                ->where('company_id', $companyId)
                ->where('status', 'active')
                ->count(),
            'pending_leave_requests' => LeaveRequest::query()
                ->where('company_id', $companyId)
                ->where('status', 'pending')
                ->count(),
            'open_payroll_runs' => PayrollRun::query()
                ->where('company_id', $companyId)
                ->whereIn('status', ['draft', 'calculated', 'approved'])
                ->count(),
            'unread_notifications' => Notification::query()
                ->where('user_id', $actor->id)
                ->whereNull('read_at')
                ->count(),
        ];
    }
}
