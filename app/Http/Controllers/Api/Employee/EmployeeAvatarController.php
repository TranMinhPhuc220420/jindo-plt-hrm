<?php

namespace App\Http\Controllers\Api\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\UploadEmployeeAvatarRequest;
use App\Http\Resources\EmployeeResource;
use App\Services\Employee\EmployeeAvatarService;
use App\Services\Employee\EmployeeService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class EmployeeAvatarController extends Controller
{
    public function __construct(
        private readonly EmployeeService $employees,
        private readonly EmployeeAvatarService $avatars,
    ) {}

    public function store(UploadEmployeeAvatarRequest $request, int $employee): JsonResponse
    {
        $model = $this->employees->find($employee);
        $this->authorize('updateAvatar', $model);

        $model = $this->avatars->upload($model, $request->file('avatar'));

        return ApiResponse::success(
            (new EmployeeResource($model))->resolve(),
            'Avatar updated.',
        );
    }

    public function destroy(int $employee): JsonResponse
    {
        $model = $this->employees->find($employee);
        $this->authorize('updateAvatar', $model);

        $model = $this->avatars->delete($model);

        return ApiResponse::success(
            (new EmployeeResource($model))->resolve(),
            'Avatar removed.',
        );
    }
}
