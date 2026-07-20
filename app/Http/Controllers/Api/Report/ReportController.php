<?php

namespace App\Http\Controllers\Api\Report;

use App\Http\Controllers\Controller;
use App\Services\Report\ReportService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reports,
    ) {}

    public function attendance(Request $request): JsonResponse
    {
        return $this->run('attendance', $request);
    }

    public function payroll(Request $request): JsonResponse
    {
        return $this->run('payroll', $request);
    }

    public function leave(Request $request): JsonResponse
    {
        return $this->run('leave', $request);
    }

    public function employees(Request $request): JsonResponse
    {
        return $this->run('employees', $request);
    }

    public function departments(Request $request): JsonResponse
    {
        return $this->run('departments', $request);
    }

    public function performance(Request $request): JsonResponse
    {
        return $this->run('performance', $request);
    }

    public function customIndex(Request $request): JsonResponse
    {
        return ApiResponse::success([]);
    }

    public function customStore(Request $request): JsonResponse
    {
        $this->authorize('can_manage_custom_reports');

        return ApiResponse::success([], 'Custom report definitions are not available yet.');
    }

    public function customRun(Request $request, int $custom): JsonResponse
    {
        $this->authorize('can_manage_custom_reports');

        return ApiResponse::success(['rows' => []]);
    }

    private function run(string $report, Request $request): JsonResponse
    {
        $filters = $request->except(['per_page', 'page']);

        $rows = $this->reports->generate($report, $filters, $request->user());

        return ApiResponse::success(
            data: ['rows' => $rows],
            meta: ['filters' => $filters],
        );
    }
}
