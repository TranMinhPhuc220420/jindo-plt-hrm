<?php

namespace App\Http\Controllers\Api\Recruitment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recruitment\ChangeCandidateStageRequest;
use App\Http\Requests\Recruitment\StoreCandidateRequest;
use App\Http\Requests\Recruitment\UpdateCandidateRequest;
use App\Http\Resources\CandidateResource;
use App\Models\Candidate;
use App\Services\Recruitment\CandidateService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CandidateController extends Controller
{
    public function __construct(
        private readonly CandidateService $candidates,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Candidate::class);

        $paginator = $this->candidates->list(
            filters: $request->only(['job_opening_id', 'stage', 'search']),
            perPage: min((int) $request->integer('per_page', 20), 100),
        );

        return ApiResponse::paginated(
            $paginator->through(fn (Candidate $candidate) => (new CandidateResource($candidate))->resolve()),
        );
    }

    public function store(StoreCandidateRequest $request): JsonResponse
    {
        $this->authorize('create', Candidate::class);

        $candidate = $this->candidates->create($request->validated());

        return ApiResponse::created(
            (new CandidateResource($candidate))->resolve(),
            'Candidate created.',
        );
    }

    public function show(int $candidate): JsonResponse
    {
        $model = $this->candidates->find($candidate);
        $this->authorize('view', $model);

        return ApiResponse::success(
            (new CandidateResource($model))->resolve(),
        );
    }

    public function update(UpdateCandidateRequest $request, int $candidate): JsonResponse
    {
        $model = $this->candidates->find($candidate);
        $this->authorize('update', $model);

        $model = $this->candidates->update($model, $request->validated());

        return ApiResponse::success(
            (new CandidateResource($model))->resolve(),
            'Candidate updated.',
        );
    }

    public function stage(ChangeCandidateStageRequest $request, int $candidate): JsonResponse
    {
        $model = $this->candidates->find($candidate);
        $this->authorize('changeStage', $model);

        $model = $this->candidates->changeStage($model, (string) $request->input('stage'));

        return ApiResponse::success(
            (new CandidateResource($model))->resolve(),
            'Candidate stage updated.',
        );
    }

    public function hire(int $candidate): JsonResponse
    {
        $model = $this->candidates->find($candidate);
        $this->authorize('hire', $model);

        $model = $this->candidates->changeStage($model, 'hired');

        return ApiResponse::success(
            (new CandidateResource($model))->resolve(),
            'Candidate marked as hired.',
        );
    }
}
