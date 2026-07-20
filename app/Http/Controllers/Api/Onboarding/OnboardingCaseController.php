<?php

namespace App\Http\Controllers\Api\Onboarding;

use App\Http\Controllers\Controller;
use App\Http\Requests\Onboarding\StartOnboardingCaseRequest;
use App\Http\Resources\OnboardingCaseResource;
use App\Http\Resources\OnboardingTaskResource;
use App\Models\OnboardingCase;
use App\Services\Onboarding\OnboardingService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OnboardingCaseController extends Controller
{
    public function __construct(
        private readonly OnboardingService $onboarding,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', OnboardingCase::class);

        $paginator = $this->onboarding->list(
            filters: $request->only(['status', 'employee_id']),
            perPage: min((int) $request->integer('per_page', 20), 100),
        );

        return ApiResponse::paginated(
            $paginator->through(fn (OnboardingCase $case) => (new OnboardingCaseResource($case))->resolve()),
        );
    }

    public function store(StartOnboardingCaseRequest $request): JsonResponse
    {
        $this->authorize('create', OnboardingCase::class);

        $case = $this->onboarding->startManual($request->validated());

        return ApiResponse::created(
            (new OnboardingCaseResource($case))->resolve(),
            'Onboarding case started.',
        );
    }

    public function show(int $onboardingCase): JsonResponse
    {
        $case = $this->onboarding->find($onboardingCase);
        $this->authorize('view', $case);

        return ApiResponse::success(
            (new OnboardingCaseResource($case))->resolve(),
        );
    }

    public function complete(int $onboardingCase): JsonResponse
    {
        $case = $this->onboarding->find($onboardingCase);
        $this->authorize('complete', $case);

        $case = $this->onboarding->completeCase($case, request()->user());

        return ApiResponse::success(
            (new OnboardingCaseResource($case))->resolve(),
            'Onboarding completed.',
        );
    }

    public function tasks(int $onboardingCase): JsonResponse
    {
        $case = $this->onboarding->find($onboardingCase);
        $this->authorize('view', $case);

        return ApiResponse::success(
            $case->tasks
                ->sortBy('sort_order')
                ->values()
                ->map(fn ($task) => (new OnboardingTaskResource($task))->resolve())
                ->all(),
        );
    }
}
