<?php

namespace App\Http\Controllers\Api\Report;

use App\Http\Controllers\Controller;
use App\Http\Requests\Report\StoreReportExportRequest;
use App\Http\Resources\ReportExportResource;
use App\Services\Report\ReportExportService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportExportController extends Controller
{
    public function __construct(
        private readonly ReportExportService $exports,
    ) {}

    public function store(StoreReportExportRequest $request): JsonResponse
    {
        $export = $this->exports->create($request->validated(), $request->user());

        return ApiResponse::accepted(
            (new ReportExportResource($export))->resolve(),
            'Export queued.',
        );
    }

    public function show(Request $request, int $export): JsonResponse
    {
        $this->authorize('can_export_reports');

        $model = $this->exports->find($export, $request->user());

        return ApiResponse::success(
            (new ReportExportResource($model))->resolve(),
        );
    }
}
