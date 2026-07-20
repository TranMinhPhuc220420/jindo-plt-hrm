<?php

namespace App\Http\Controllers\Api\Recruitment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recruitment\StoreJobOpeningRequest;
use App\Http\Requests\Recruitment\UpdateJobOpeningRequest;
use App\Http\Resources\JobOpeningResource;
use App\Models\JobOpening;
use App\Services\Recruitment\JobOpeningService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobOpeningController extends Controller
{
    public function __construct(
        private readonly JobOpeningService $openings,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', JobOpening::class);

        $paginator = $this->openings->list(
            filters: $request->only(['status', 'search']),
            perPage: min((int) $request->integer('per_page', 20), 100),
        );

        return ApiResponse::paginated(
            $paginator->through(fn (JobOpening $opening) => (new JobOpeningResource($opening))->resolve()),
        );
    }

    public function store(StoreJobOpeningRequest $request): JsonResponse
    {
        $this->authorize('create', JobOpening::class);

        $opening = $this->openings->create($request->validated());

        return ApiResponse::created(
            (new JobOpeningResource($opening))->resolve(),
            'Job opening created.',
        );
    }

    public function show(int $jobOpening): JsonResponse
    {
        $model = $this->openings->find($jobOpening);
        $this->authorize('view', $model);

        return ApiResponse::success(
            (new JobOpeningResource($model))->resolve(),
        );
    }

    public function update(UpdateJobOpeningRequest $request, int $jobOpening): JsonResponse
    {
        $model = $this->openings->find($jobOpening);
        $this->authorize('update', $model);

        $model = $this->openings->update($model, $request->validated());

        return ApiResponse::success(
            (new JobOpeningResource($model))->resolve(),
            'Job opening updated.',
        );
    }

    public function close(int $jobOpening): JsonResponse
    {
        $model = $this->openings->find($jobOpening);
        $this->authorize('close', $model);

        $model = $this->openings->close($model);

        return ApiResponse::success(
            (new JobOpeningResource($model))->resolve(),
            'Job opening closed.',
        );
    }
}
