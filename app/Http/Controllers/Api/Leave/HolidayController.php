<?php

namespace App\Http\Controllers\Api\Leave;

use App\Http\Controllers\Controller;
use App\Http\Requests\Leave\StoreHolidayRequest;
use App\Http\Resources\HolidayResource;
use App\Models\Holiday;
use App\Services\Leave\HolidayService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HolidayController extends Controller
{
    public function __construct(
        private readonly HolidayService $holidays,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Holiday::class);

        $items = $this->holidays->list($request->query('year'));

        return ApiResponse::success(
            $items->map(fn (Holiday $holiday) => (new HolidayResource($holiday))->resolve())->values()->all(),
        );
    }

    public function store(StoreHolidayRequest $request): JsonResponse
    {
        $this->authorize('create', Holiday::class);

        $holiday = $this->holidays->create($request->validated());

        return ApiResponse::created(
            (new HolidayResource($holiday))->resolve(),
            'Holiday created.',
        );
    }

    public function destroy(int $holiday): JsonResponse
    {
        $model = $this->holidays->find($holiday);
        $this->authorize('delete', $model);

        $this->holidays->delete($model);

        return ApiResponse::success(null, 'Holiday deleted.');
    }
}
