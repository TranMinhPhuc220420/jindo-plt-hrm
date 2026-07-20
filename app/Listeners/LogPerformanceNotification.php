<?php

namespace App\Listeners;

use App\Events\EvaluationSubmitted;
use App\Events\ReviewCycleFinalized;
use App\Events\ReviewCycleStarted;
use App\Models\Employee;
use App\Services\Notification\NotificationRecipientResolver;
use App\Services\Notification\NotificationService;
use Illuminate\Support\Facades\Log;

class LogPerformanceNotification
{
    public function __construct(
        private readonly NotificationService $notifications,
        private readonly NotificationRecipientResolver $recipients,
    ) {}

    public function handleCycleStarted(ReviewCycleStarted $event): void
    {
        $cycle = $event->cycle;
        Log::info('performance.notification', [
            'type' => 'performance.cycle_started',
            'review_cycle_id' => $cycle->id,
        ]);

        $cycle->loadMissing('participants');
        $data = ['review_cycle_id' => $cycle->id];

        foreach ($cycle->participants as $participant) {
            $this->notifications->notifyEmployee(
                employeeId: $participant->employee_id,
                type: 'performance.cycle_started',
                data: $data,
                companyId: $cycle->company_id,
            );
        }
    }

    public function handleCycleFinalized(ReviewCycleFinalized $event): void
    {
        $cycle = $event->cycle;
        Log::info('performance.notification', [
            'type' => 'performance.cycle_finalized',
            'review_cycle_id' => $cycle->id,
        ]);

        $cycle->loadMissing('participants');
        $data = ['review_cycle_id' => $cycle->id];

        foreach ($cycle->participants as $participant) {
            $this->notifications->notifyEmployee(
                employeeId: $participant->employee_id,
                type: 'performance.cycle_finalized',
                data: $data,
                companyId: $cycle->company_id,
            );
        }
    }

    public function handleEvaluationSubmitted(EvaluationSubmitted $event): void
    {
        $evaluation = $event->evaluation;
        Log::info('performance.notification', [
            'type' => 'performance.evaluation_submitted',
            'evaluation_id' => $evaluation->id,
            'employee_id' => $evaluation->employee_id,
        ]);

        $employee = Employee::query()->find($evaluation->employee_id);
        if ($employee === null) {
            return;
        }

        $data = [
            'evaluation_id' => $evaluation->id,
            'review_cycle_id' => $evaluation->review_cycle_id,
            'employee_id' => $evaluation->employee_id,
        ];

        foreach ($this->recipients->managerOrPermissionHolders($employee, 'can_manage_review_cycles') as $recipient) {
            $this->notifications->notify(
                user: $recipient,
                type: 'performance.evaluation_submitted',
                data: $data,
                companyId: $evaluation->company_id,
            );
        }
    }
}
