<?php

namespace App\Http\Controllers\Api\Onboarding;

use App\Http\Controllers\Controller;
use App\Http\Requests\Onboarding\StoreOnboardingTemplateRequest;
use App\Http\Requests\Onboarding\UpdateOnboardingTemplateRequest;
use App\Http\Resources\OnboardingTemplateResource;
use App\Models\OnboardingTemplate;
use App\Services\Onboarding\OnboardingService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OnboardingTemplateController extends Controller
{
    public function __construct(
        private readonly OnboardingService $onboarding,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', OnboardingTemplate::class);

        $paginator = $this->onboarding->listTemplates(
            min((int) $request->integer('per_page', 20), 100),
        );

        return ApiResponse::paginated(
            $paginator->through(fn (OnboardingTemplate $template) => (new OnboardingTemplateResource($template))->resolve()),
        );
    }

    public function store(StoreOnboardingTemplateRequest $request): JsonResponse
    {
        $this->authorize('create', OnboardingTemplate::class);

        $template = $this->onboarding->createTemplate($request->validated());

        return ApiResponse::created(
            (new OnboardingTemplateResource($template))->resolve(),
            'Onboarding template created.',
        );
    }

    public function show(int $onboardingTemplate): JsonResponse
    {
        $template = $this->onboarding->findTemplate($onboardingTemplate);
        $this->authorize('view', $template);

        return ApiResponse::success(
            (new OnboardingTemplateResource($template))->resolve(),
        );
    }

    public function update(UpdateOnboardingTemplateRequest $request, int $onboardingTemplate): JsonResponse
    {
        $template = $this->onboarding->findTemplate($onboardingTemplate);
        $this->authorize('update', $template);

        $template = $this->onboarding->updateTemplate($template, $request->validated());

        return ApiResponse::success(
            (new OnboardingTemplateResource($template))->resolve(),
            'Onboarding template updated.',
        );
    }
}
