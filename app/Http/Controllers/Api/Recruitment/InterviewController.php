<?php

namespace App\Http\Controllers\Api\Recruitment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recruitment\StoreEvaluationRequest;
use App\Http\Requests\Recruitment\StoreInterviewRequest;
use App\Http\Resources\CandidateEvaluationResource;
use App\Http\Resources\InterviewResource;
use App\Models\Interview;
use App\Services\Recruitment\CandidateService;
use App\Services\Recruitment\InterviewService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class InterviewController extends Controller
{
    public function __construct(
        private readonly InterviewService $interviews,
        private readonly CandidateService $candidates,
    ) {}

    public function index(int $candidate): JsonResponse
    {
        $model = $this->candidates->find($candidate);
        $this->authorize('view', $model);

        $interviews = $this->interviews->listForCandidate($model);

        return ApiResponse::success(
            $interviews->map(fn (Interview $interview) => (new InterviewResource($interview))->resolve())->all(),
        );
    }

    public function store(StoreInterviewRequest $request, int $candidate): JsonResponse
    {
        $model = $this->candidates->find($candidate);
        $this->authorize('manageInterviews', $model);

        $interview = $this->interviews->schedule($model, $request->validated());

        return ApiResponse::created(
            (new InterviewResource($interview))->resolve(),
            'Interview scheduled.',
        );
    }

    public function evaluate(StoreEvaluationRequest $request, int $interview): JsonResponse
    {
        $model = $this->interviews->find($interview);
        $this->authorize('manageInterviews', $model->candidate);

        $evaluation = $this->interviews->submitEvaluation($model, $request->validated(), $request->user());

        return ApiResponse::created(
            (new CandidateEvaluationResource($evaluation))->resolve(),
            'Evaluation submitted.',
        );
    }
}
