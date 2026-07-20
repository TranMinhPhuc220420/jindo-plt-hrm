<?php

namespace App\Http\Controllers\Api\Employee;

use App\Http\Controllers\Controller;
use App\Models\EmployeeEducation;
use App\Services\Employee\EmployeeService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeEducationController extends Controller
{
    public function __construct(
        private readonly EmployeeService $employees,
    ) {}

    public function index(int $employee): JsonResponse
    {
        $model = $this->employees->find($employee);
        $this->authorize('view', $model);

        return ApiResponse::success(
            $this->employees->listEducations($model)->values()->all(),
        );
    }

    public function store(Request $request, int $employee): JsonResponse
    {
        $model = $this->employees->find($employee);
        $this->authorize('update', $model);

        $validated = $request->validate([
            'school' => ['required', 'string', 'max:255'],
            'degree' => ['nullable', 'string', 'max:100'],
            'field_of_study' => ['nullable', 'string', 'max:100'],
            'started_on' => ['nullable', 'date'],
            'ended_on' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $education = $this->employees->createEducation($model, $validated);

        return ApiResponse::created($education->toArray(), 'Education added.');
    }

    public function update(Request $request, int $employee, int $education): JsonResponse
    {
        $model = $this->employees->find($employee);
        $this->authorize('update', $model);

        $row = EmployeeEducation::query()->findOrFail($education);
        $validated = $request->validate([
            'school' => ['sometimes', 'required', 'string', 'max:255'],
            'degree' => ['nullable', 'string', 'max:100'],
            'field_of_study' => ['nullable', 'string', 'max:100'],
            'started_on' => ['nullable', 'date'],
            'ended_on' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $row = $this->employees->updateEducation($model, $row, $validated);

        return ApiResponse::success($row->toArray(), 'Education updated.');
    }

    public function destroy(int $employee, int $education): JsonResponse
    {
        $model = $this->employees->find($employee);
        $this->authorize('update', $model);

        $row = EmployeeEducation::query()->findOrFail($education);
        $this->employees->deleteEducation($model, $row);

        return ApiResponse::success(null, 'Education deleted.');
    }
}
