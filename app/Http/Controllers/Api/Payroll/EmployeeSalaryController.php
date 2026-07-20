<?php

namespace App\Http\Controllers\Api\Payroll;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payroll\ReplaceCompensationRequest;
use App\Http\Requests\Payroll\UpsertEmployeeSalaryRequest;
use App\Http\Resources\CompensationComponentResource;
use App\Http\Resources\EmployeeSalaryResource;
use App\Models\EmployeeSalary;
use App\Services\Payroll\EmployeeCompensationService;
use App\Services\Payroll\EmployeeSalaryService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeSalaryController extends Controller
{
    public function __construct(
        private readonly EmployeeSalaryService $salaries,
        private readonly EmployeeCompensationService $compensation,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', EmployeeSalary::class);

        $filters = $request->only(['employee_id']);
        $viewer = $request->user();

        if (
            ! $viewer?->can('can_manage_salary')
            && $viewer?->can('can_view_salary')
        ) {
            $filters['employee_id'] = $viewer->employee?->id;
        }

        if ($request->boolean('current_only')) {
            $filters['current_only'] = true;
        }

        $paginator = $this->salaries->list(
            filters: $filters,
            perPage: min((int) $request->integer('per_page', 20), 100),
        );

        return ApiResponse::paginated(
            $paginator->through(fn (EmployeeSalary $row) => (new EmployeeSalaryResource($row))->resolve()),
        );
    }

    public function upsert(UpsertEmployeeSalaryRequest $request, int $employee): JsonResponse
    {
        $this->authorize('manage', EmployeeSalary::class);

        $salary = $this->salaries->upsert($employee, $request->validated());

        return ApiResponse::success(
            (new EmployeeSalaryResource($salary))->resolve(),
            'Salary updated.',
        );
    }

    public function allowances(int $employee): JsonResponse
    {
        $this->authorize('viewAny', EmployeeSalary::class);

        $items = $this->compensation->listAllowances($employee);

        return ApiResponse::success(
            $items->map(fn ($row) => (new CompensationComponentResource($row))->resolve())->values()->all(),
        );
    }

    public function replaceAllowances(ReplaceCompensationRequest $request, int $employee): JsonResponse
    {
        $this->authorize('manage', EmployeeSalary::class);

        $items = $this->compensation->replaceAllowances($employee, $request->validated('items'));

        return ApiResponse::success(
            $items->map(fn ($row) => (new CompensationComponentResource($row))->resolve())->values()->all(),
            'Allowances updated.',
        );
    }

    public function deductions(int $employee): JsonResponse
    {
        $this->authorize('viewAny', EmployeeSalary::class);

        $items = $this->compensation->listDeductions($employee);

        return ApiResponse::success(
            $items->map(fn ($row) => (new CompensationComponentResource($row))->resolve())->values()->all(),
        );
    }

    public function replaceDeductions(ReplaceCompensationRequest $request, int $employee): JsonResponse
    {
        $this->authorize('manage', EmployeeSalary::class);

        $items = $this->compensation->replaceDeductions($employee, $request->validated('items'));

        return ApiResponse::success(
            $items->map(fn ($row) => (new CompensationComponentResource($row))->resolve())->values()->all(),
            'Deductions updated.',
        );
    }

    public function bonuses(int $employee): JsonResponse
    {
        $this->authorize('viewAny', EmployeeSalary::class);

        $items = $this->compensation->listBonuses($employee);

        return ApiResponse::success(
            $items->map(fn ($row) => (new CompensationComponentResource($row))->resolve())->values()->all(),
        );
    }

    public function replaceBonuses(ReplaceCompensationRequest $request, int $employee): JsonResponse
    {
        $this->authorize('manage', EmployeeSalary::class);

        $items = $this->compensation->replaceBonuses($employee, $request->validated('items'));

        return ApiResponse::success(
            $items->map(fn ($row) => (new CompensationComponentResource($row))->resolve())->values()->all(),
            'Bonuses updated.',
        );
    }
}
