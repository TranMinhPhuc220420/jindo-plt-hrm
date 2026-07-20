<?php

namespace App\Http\Controllers\Api\Payroll;

use App\Http\Controllers\Controller;
use App\Http\Resources\PayslipResource;
use App\Models\Payslip;
use App\Services\Payroll\PayslipService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PayslipController extends Controller
{
    public function __construct(
        private readonly PayslipService $payslips,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Payslip::class);

        $paginator = $this->payslips->list(
            filters: $request->only(['employee_id']),
            viewer: $request->user(),
            perPage: min((int) $request->integer('per_page', 20), 100),
        );

        return ApiResponse::paginated(
            $paginator->through(fn (Payslip $row) => (new PayslipResource($row))->resolve()),
        );
    }

    public function show(int $payslip): JsonResponse
    {
        $model = $this->payslips->find($payslip, request()->user());
        $this->authorize('view', $model);

        return ApiResponse::success(
            (new PayslipResource($model))->resolve(),
        );
    }

    public function download(int $payslip): StreamedResponse
    {
        $model = $this->payslips->find($payslip, request()->user());
        $this->authorize('download', $model);

        return $this->payslips->download($payslip, request()->user());
    }
}
