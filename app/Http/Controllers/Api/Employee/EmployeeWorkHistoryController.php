<?php

namespace App\Http\Controllers\Api\Employee;

use App\Http\Controllers\Controller;
use App\Models\EmployeeWorkHistory;
use App\Services\Employee\EmployeeService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeWorkHistoryController extends Controller
{
    public function __construct(
        private readonly EmployeeService $employees,
    ) {}

    public function index(int $employee): JsonResponse
    {
        $model = $this->employees->find($employee);
        $this->authorize('view', $model);

        return ApiResponse::success(
            $this->employees->listWorkHistories($model)->values()->all(),
        );
    }

    public function store(Request $request, int $employee): JsonResponse
    {
        $model = $this->employees->find($employee);
        $this->authorize('update', $model);

        $validated = $request->validate([
            'employer' => ['required', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:100'],
            'started_on' => ['nullable', 'date'],
            'ended_on' => ['nullable', 'date'],
            'description' => ['nullable', 'string'],
        ]);

        $row = $this->employees->createWorkHistory($model, $validated);

        return ApiResponse::created($row->toArray(), 'Work history added.');
    }

    public function update(Request $request, int $employee, int $workHistory): JsonResponse
    {
        $model = $this->employees->find($employee);
        $this->authorize('update', $model);

        $row = EmployeeWorkHistory::query()->findOrFail($workHistory);
        $validated = $request->validate([
            'employer' => ['sometimes', 'required', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:100'],
            'started_on' => ['nullable', 'date'],
            'ended_on' => ['nullable', 'date'],
            'description' => ['nullable', 'string'],
        ]);

        $row = $this->employees->updateWorkHistory($model, $row, $validated);

        return ApiResponse::success($row->toArray(), 'Work history updated.');
    }

    public function destroy(int $employee, int $workHistory): JsonResponse
    {
        $model = $this->employees->find($employee);
        $this->authorize('update', $model);

        $row = EmployeeWorkHistory::query()->findOrFail($workHistory);
        $this->employees->deleteWorkHistory($model, $row);

        return ApiResponse::success(null, 'Work history deleted.');
    }
}
