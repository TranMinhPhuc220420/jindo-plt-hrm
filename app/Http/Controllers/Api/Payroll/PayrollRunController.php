<?php

namespace App\Http\Controllers\Api\Payroll;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payroll\StorePayrollRunRequest;
use App\Http\Requests\Payroll\UpdatePayrollRunRequest;
use App\Http\Resources\PayrollItemResource;
use App\Http\Resources\PayrollRunResource;
use App\Models\PayrollItem;
use App\Models\PayrollRun;
use App\Services\Payroll\PayrollRunService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayrollRunController extends Controller
{
    public function __construct(
        private readonly PayrollRunService $runs,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', PayrollRun::class);

        $paginator = $this->runs->list(
            filters: $request->only(['status']),
            perPage: min((int) $request->integer('per_page', 20), 100),
        );

        return ApiResponse::paginated(
            $paginator->through(fn (PayrollRun $run) => (new PayrollRunResource($run))->resolve()),
        );
    }

    public function store(StorePayrollRunRequest $request): JsonResponse
    {
        $this->authorize('create', PayrollRun::class);

        $run = $this->runs->create($request->validated());

        return ApiResponse::created(
            (new PayrollRunResource($run))->resolve(),
            'Payroll run created.',
        );
    }

    public function show(int $payrollRun): JsonResponse
    {
        $run = $this->runs->find($payrollRun);
        $this->authorize('view', $run);

        return ApiResponse::success(
            (new PayrollRunResource($run))->resolve(),
        );
    }

    public function update(UpdatePayrollRunRequest $request, int $payrollRun): JsonResponse
    {
        $run = $this->runs->find($payrollRun);
        $this->authorize('update', $run);

        $run = $this->runs->update($run, $request->validated());

        return ApiResponse::success(
            (new PayrollRunResource($run))->resolve(),
            'Payroll run updated.',
        );
    }

    public function destroy(int $payrollRun): JsonResponse
    {
        $run = $this->runs->find($payrollRun);
        $this->authorize('delete', $run);

        $this->runs->delete($run);

        return ApiResponse::success(null, 'Payroll run deleted.');
    }

    public function calculate(int $payrollRun): JsonResponse
    {
        $run = $this->runs->find($payrollRun);
        $this->authorize('calculate', $run);

        $run = $this->runs->calculate($run, request()->user());

        return ApiResponse::success(
            (new PayrollRunResource($run))->resolve(),
            'Payroll calculated.',
        );
    }

    public function approve(int $payrollRun): JsonResponse
    {
        $run = $this->runs->find($payrollRun);
        $this->authorize('approve', $run);

        $run = $this->runs->approve($run, request()->user());

        return ApiResponse::success(
            (new PayrollRunResource($run))->resolve(),
            'Payroll approved.',
        );
    }

    public function finalize(int $payrollRun): JsonResponse
    {
        $run = $this->runs->find($payrollRun);
        $this->authorize('finalize', $run);

        $run = $this->runs->finalize($run, request()->user());

        return ApiResponse::success(
            (new PayrollRunResource($run))->resolve(),
            'Payroll finalized.',
        );
    }

    public function items(Request $request, int $payrollRun): JsonResponse
    {
        $run = $this->runs->find($payrollRun);
        $this->authorize('view', $run);

        $paginator = $this->runs->listItems(
            $run,
            min((int) $request->integer('per_page', 50), 100),
        );

        return ApiResponse::paginated(
            $paginator->through(fn (PayrollItem $item) => (new PayrollItemResource($item))->resolve()),
        );
    }
}
