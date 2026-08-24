<?php

namespace App\Http\Controllers\Api\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\ChangeEmployeeStatusRequest;
use App\Http\Requests\Employee\StoreEmployeeRequest;
use App\Http\Requests\Employee\UpdateEmployeePasswordRequest;
use App\Http\Requests\Employee\UpdateEmployeeRequest;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use App\Services\Employee\EmployeeAccountService;
use App\Services\Employee\EmployeeService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function __construct(
        private readonly EmployeeService $employees,
        private readonly EmployeeAccountService $accounts,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Employee::class);

        $paginator = $this->employees->list(
            filters: $request->only(['search', 'status', 'department_id']),
            perPage: min((int) $request->integer('per_page', 20), 100),
        );

        return ApiResponse::paginated(
            $paginator->through(fn (Employee $employee) => (new EmployeeResource($employee))->resolve()),
        );
    }

    public function store(StoreEmployeeRequest $request): JsonResponse
    {
        $this->authorize('create', Employee::class);

        $employee = $this->employees->create($request->validated());

        return ApiResponse::created(
            (new EmployeeResource($employee))->resolve(),
            'Employee created.',
        );
    }

    public function show(int $employee): JsonResponse
    {
        $model = $this->employees->find($employee);
        $this->authorize('view', $model);

        return ApiResponse::success(
            (new EmployeeResource($model))->resolve(),
        );
    }

    public function update(UpdateEmployeeRequest $request, int $employee): JsonResponse
    {
        $model = $this->employees->find($employee);
        $this->authorize('update', $model);

        $model = $this->employees->update($model, $request->validated());

        return ApiResponse::success(
            (new EmployeeResource($model))->resolve(),
            'Employee updated.',
        );
    }

    public function updatePassword(UpdateEmployeePasswordRequest $request, int $employee): JsonResponse
    {
        $model = $this->employees->find($employee);
        $this->authorize('update', $model);

        if ($request->boolean('use_default')) {
            $this->accounts->resetToDefault($model);
            $message = 'Employee password reset to default.';
        } else {
            $this->accounts->setPassword($model, $request->string('password')->toString());
            $message = 'Employee password updated.';
        }

        return ApiResponse::success(
            (new EmployeeResource($model->fresh(['department', 'position', 'branch'])))->resolve(),
            $message,
        );
    }

    public function changeStatus(ChangeEmployeeStatusRequest $request, int $employee): JsonResponse
    {
        $model = $this->employees->find($employee);
        $this->authorize('changeStatus', $model);

        $model = $this->employees->changeStatus($model, [
            ...$request->validated(),
            'confirm_asset_return' => $request->boolean('confirm_asset_return'),
        ]);

        return ApiResponse::success(
            (new EmployeeResource($model))->resolve(),
            'Employee status updated.',
        );
    }

    public function destroy(int $employee): JsonResponse
    {
        $model = $this->employees->find($employee);
        $this->authorize('changeStatus', $model);

        $this->employees->archive($model);

        return ApiResponse::success(null, 'Employee archived.');
    }
}
