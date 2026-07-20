<?php

namespace App\Http\Controllers\Api\Employee;

use App\Http\Controllers\Controller;
use App\Services\Employee\EmployeeService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeSensitiveController extends Controller
{
    public function __construct(
        private readonly EmployeeService $employees,
    ) {}

    public function showBankAccount(int $employee): JsonResponse
    {
        $model = $this->employees->find($employee);
        $this->authorize('manageSensitive', $model);

        $account = $this->employees->getBankAccount($model);

        return ApiResponse::success($account?->toArray());
    }

    public function updateBankAccount(Request $request, int $employee): JsonResponse
    {
        $model = $this->employees->find($employee);
        $this->authorize('manageSensitive', $model);

        $validated = $request->validate([
            'bank_name' => ['required', 'string', 'max:255'],
            'account_name' => ['nullable', 'string', 'max:255'],
            'account_number' => ['required', 'string', 'max:100'],
            'branch_name' => ['nullable', 'string', 'max:255'],
            'swift_code' => ['nullable', 'string', 'max:50'],
        ]);

        $account = $this->employees->upsertBankAccount($model, $validated);

        return ApiResponse::success($account->toArray(), 'Bank account updated.');
    }

    public function showTaxProfile(int $employee): JsonResponse
    {
        $model = $this->employees->find($employee);
        $this->authorize('manageSensitive', $model);

        $profile = $this->employees->getTaxProfile($model);

        return ApiResponse::success($profile?->toArray());
    }

    public function updateTaxProfile(Request $request, int $employee): JsonResponse
    {
        $model = $this->employees->find($employee);
        $this->authorize('manageSensitive', $model);

        $validated = $request->validate([
            'tax_code' => ['nullable', 'string', 'max:100'],
            'tax_residency' => ['nullable', 'string', 'max:100'],
            'dependents_count' => ['sometimes', 'integer', 'min:0', 'max:20'],
            'notes' => ['nullable', 'string'],
        ]);

        $profile = $this->employees->upsertTaxProfile($model, $validated);

        return ApiResponse::success($profile->toArray(), 'Tax profile updated.');
    }

    public function showInsurance(int $employee): JsonResponse
    {
        $model = $this->employees->find($employee);
        $this->authorize('manageSensitive', $model);

        $insurance = $this->employees->getInsurance($model);

        return ApiResponse::success($insurance?->toArray());
    }

    public function updateInsurance(Request $request, int $employee): JsonResponse
    {
        $model = $this->employees->find($employee);
        $this->authorize('manageSensitive', $model);

        $validated = $request->validate([
            'social_insurance_number' => ['nullable', 'string', 'max:100'],
            'health_insurance_number' => ['nullable', 'string', 'max:100'],
            'provider' => ['nullable', 'string', 'max:255'],
            'effective_from' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $insurance = $this->employees->upsertInsurance($model, $validated);

        return ApiResponse::success($insurance->toArray(), 'Insurance updated.');
    }
}
