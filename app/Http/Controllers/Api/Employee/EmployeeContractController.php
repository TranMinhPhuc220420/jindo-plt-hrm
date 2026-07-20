<?php

namespace App\Http\Controllers\Api\Employee;

use App\Http\Controllers\Controller;
use App\Models\EmployeeContract;
use App\Services\Employee\EmployeeService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeContractController extends Controller
{
    public function __construct(
        private readonly EmployeeService $employees,
    ) {}

    public function index(int $employee): JsonResponse
    {
        $model = $this->employees->find($employee);
        $this->authorize('view', $model);

        return ApiResponse::success(
            $this->employees->listContracts($model)->values()->all(),
        );
    }

    public function store(Request $request, int $employee): JsonResponse
    {
        $model = $this->employees->find($employee);
        $this->authorize('update', $model);

        $validated = $request->validate([
            'contract_number' => ['nullable', 'string', 'max:100'],
            'contract_type' => ['nullable', 'string', 'max:100'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'status' => ['sometimes', 'string', 'max:32'],
            'notes' => ['nullable', 'string'],
        ]);

        $contract = $this->employees->createContract($model, $validated);

        return ApiResponse::created($contract->toArray(), 'Contract added.');
    }

    public function update(Request $request, int $employee, int $contract): JsonResponse
    {
        $model = $this->employees->find($employee);
        $this->authorize('update', $model);

        $row = EmployeeContract::query()->findOrFail($contract);
        $validated = $request->validate([
            'contract_number' => ['nullable', 'string', 'max:100'],
            'contract_type' => ['nullable', 'string', 'max:100'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'status' => ['sometimes', 'string', 'max:32'],
            'notes' => ['nullable', 'string'],
        ]);

        $row = $this->employees->updateContract($model, $row, $validated);

        return ApiResponse::success($row->toArray(), 'Contract updated.');
    }
}
