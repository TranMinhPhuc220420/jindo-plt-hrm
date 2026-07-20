<?php

namespace App\Listeners;

use App\Events\CandidateStageChanged;
use App\Events\OfferAccepted;
use App\Events\OfferSent;
use App\Events\OnboardingCompleted;
use App\Events\OnboardingStarted;
use App\Events\OnboardingTaskCompleted;
use App\Services\Notification\NotificationRecipientResolver;
use App\Services\Notification\NotificationService;
use Illuminate\Support\Facades\Log;

/**
 * Fans hire/ops domain events into the notification inbox (and queued email).
 */
class LogHireOpsNotification
{
    public function __construct(
        private readonly NotificationService $notifications,
        private readonly NotificationRecipientResolver $recipients,
    ) {}

    public function handleOfferAccepted(OfferAccepted $event): void
    {
        $offer = $event->offer;
        Log::info('hire_ops.notification', [
            'type' => 'recruitment.offer_accepted',
            'offer_id' => $offer->id,
            'candidate_id' => $offer->candidate_id,
        ]);

        $data = [
            'offer_id' => $offer->id,
            'candidate_id' => $offer->candidate_id,
        ];

        foreach ($this->recipients->usersWithPermissionInCompany(
            $offer->company_id,
            'can_manage_candidates',
        ) as $hr) {
            $this->notifications->notify(
                user: $hr,
                type: 'recruitment.offer_accepted',
                data: $data,
                companyId: $offer->company_id,
            );
        }
    }

    public function handleOfferSent(OfferSent $event): void
    {
        $offer = $event->offer;
        Log::info('hire_ops.notification', [
            'type' => 'recruitment.offer_sent',
            'offer_id' => $offer->id,
            'candidate_id' => $offer->candidate_id,
        ]);

        $data = [
            'offer_id' => $offer->id,
            'candidate_id' => $offer->candidate_id,
        ];

        foreach ($this->recipients->usersWithPermissionInCompany(
            $offer->company_id,
            'can_manage_candidates',
        ) as $recruiter) {
            $this->notifications->notify(
                user: $recruiter,
                type: 'recruitment.offer_sent',
                data: $data,
                companyId: $offer->company_id,
            );
        }
    }

    public function handleStageChanged(CandidateStageChanged $event): void
    {
        $candidate = $event->candidate;
        Log::info('hire_ops.notification', [
            'type' => 'recruitment.stage_changed',
            'candidate_id' => $candidate->id,
            'from' => $event->from,
            'to' => $event->to,
        ]);

        $data = [
            'candidate_id' => $candidate->id,
            'from' => $event->from,
            'to' => $event->to,
        ];

        foreach ($this->recipients->usersWithPermissionInCompany(
            $candidate->company_id,
            'can_manage_candidates',
        ) as $recruiter) {
            $this->notifications->notify(
                user: $recruiter,
                type: 'recruitment.stage_changed',
                data: $data,
                companyId: $candidate->company_id,
            );
        }
    }

    public function handleOnboardingStarted(OnboardingStarted $event): void
    {
        $case = $event->onboardingCase;
        Log::info('hire_ops.notification', [
            'type' => 'onboarding.started',
            'onboarding_case_id' => $case->id,
            'employee_id' => $case->employee_id,
        ]);

        $this->notifications->notifyEmployee(
            employeeId: $case->employee_id,
            type: 'onboarding.started',
            data: ['onboarding_case_id' => $case->id],
            companyId: $case->company_id,
        );
    }

    public function handleOnboardingCompleted(OnboardingCompleted $event): void
    {
        $case = $event->onboardingCase;
        Log::info('hire_ops.notification', [
            'type' => 'onboarding.completed',
            'onboarding_case_id' => $case->id,
            'employee_id' => $case->employee_id,
        ]);

        $this->notifications->notifyEmployee(
            employeeId: $case->employee_id,
            type: 'onboarding.completed',
            data: ['onboarding_case_id' => $case->id],
            companyId: $case->company_id,
        );
    }

    public function handleTaskCompleted(OnboardingTaskCompleted $event): void
    {
        $task = $event->task;
        Log::info('hire_ops.notification', [
            'type' => 'onboarding.task_completed',
            'onboarding_task_id' => $task->id,
            'onboarding_case_id' => $task->onboarding_case_id,
        ]);

        $data = [
            'onboarding_task_id' => $task->id,
            'onboarding_case_id' => $task->onboarding_case_id,
            'key' => $task->key,
        ];

        foreach ($this->recipients->usersWithPermissionInCompany(
            $task->company_id,
            'can_manage_onboarding',
        ) as $owner) {
            $this->notifications->notify(
                user: $owner,
                type: 'onboarding.task_completed',
                data: $data,
                companyId: $task->company_id,
            );
        }
    }
}
