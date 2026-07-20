<?php

namespace App\Http\Controllers\Api\Onboarding;

use App\Http\Controllers\Controller;
use App\Http\Resources\OnboardingTaskResource;
use App\Services\Onboarding\OnboardingService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class OnboardingTaskController extends Controller
{
    public function __construct(
        private readonly OnboardingService $onboarding,
    ) {}

    public function complete(int $onboardingTask): JsonResponse
    {
        $task = $this->onboarding->findTask($onboardingTask);
        $this->authorize('completeTask', $task->onboardingCase);

        $task = $this->onboarding->completeTask($task, request()->user());

        return ApiResponse::success(
            (new OnboardingTaskResource($task))->resolve(),
            'Task completed.',
        );
    }

    public function reopen(int $onboardingTask): JsonResponse
    {
        $task = $this->onboarding->findTask($onboardingTask);
        $this->authorize('reopenTask', $task->onboardingCase);

        $task = $this->onboarding->reopenTask($task);

        return ApiResponse::success(
            (new OnboardingTaskResource($task))->resolve(),
            'Task reopened.',
        );
    }
}
