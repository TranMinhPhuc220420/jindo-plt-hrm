<?php

namespace App\Http\Controllers\Api\Employee;

use App\Http\Controllers\Controller;
use App\Models\EmployeeFamilyMember;
use App\Services\Employee\EmployeeService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeFamilyMemberController extends Controller
{
    public function __construct(
        private readonly EmployeeService $employees,
    ) {}

    public function index(int $employee): JsonResponse
    {
        $model = $this->employees->find($employee);
        $this->authorize('view', $model);

        return ApiResponse::success(
            $this->employees->listFamilyMembers($model)->values()->all(),
        );
    }

    public function store(Request $request, int $employee): JsonResponse
    {
        $model = $this->employees->find($employee);
        $this->authorize('update', $model);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'relationship' => ['nullable', 'string', 'max:100'],
            'date_of_birth' => ['nullable', 'date'],
            'is_dependent' => ['sometimes', 'boolean'],
        ]);

        $row = $this->employees->createFamilyMember($model, $validated);

        return ApiResponse::created($row->toArray(), 'Family member added.');
    }

    public function update(Request $request, int $employee, int $familyMember): JsonResponse
    {
        $model = $this->employees->find($employee);
        $this->authorize('update', $model);

        $row = EmployeeFamilyMember::query()->findOrFail($familyMember);
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'relationship' => ['nullable', 'string', 'max:100'],
            'date_of_birth' => ['nullable', 'date'],
            'is_dependent' => ['sometimes', 'boolean'],
        ]);

        $row = $this->employees->updateFamilyMember($model, $row, $validated);

        return ApiResponse::success($row->toArray(), 'Family member updated.');
    }

    public function destroy(int $employee, int $familyMember): JsonResponse
    {
        $model = $this->employees->find($employee);
        $this->authorize('update', $model);

        $row = EmployeeFamilyMember::query()->findOrFail($familyMember);
        $this->employees->deleteFamilyMember($model, $row);

        return ApiResponse::success(null, 'Family member deleted.');
    }
}
