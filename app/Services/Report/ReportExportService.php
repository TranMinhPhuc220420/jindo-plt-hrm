<?php

namespace App\Services\Report;

use App\Exceptions\DomainException;
use App\Jobs\GenerateReportExportJob;
use App\Models\ReportExport;
use App\Models\User;
use App\Services\Organization\CompanyContext;

class ReportExportService
{
    public function __construct(
        private readonly CompanyContext $companyContext,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): ReportExport
    {
        $report = (string) ($data['report'] ?? '');
        $format = (string) ($data['format'] ?? 'csv');

        if (! in_array($report, ReportService::availableReports(), true)) {
            throw new DomainException(
                message: 'Unknown report type.',
                errorCode: 'REPORT_FILTER_INVALID',
                status: 422,
            );
        }

        if ($format !== 'csv') {
            throw new DomainException(
                message: 'Only CSV exports are supported.',
                errorCode: 'REPORT_FILTER_INVALID',
                status: 422,
            );
        }

        $export = ReportExport::query()->create([
            'company_id' => $this->companyContext->id(),
            'user_id' => $actor->id,
            'report' => $report,
            'format' => 'csv',
            'filters' => $data['filters'] ?? [],
            'status' => 'pending',
        ]);

        GenerateReportExportJob::dispatch($export->id);

        return $export->fresh();
    }

    public function find(int $id, User $actor): ReportExport
    {
        $export = ReportExport::query()
            ->where('company_id', $this->companyContext->id())
            ->where('user_id', $actor->id)
            ->find($id);

        if ($export === null) {
            throw new DomainException(
                message: 'Export not found.',
                errorCode: 'NOT_FOUND',
                status: 404,
            );
        }

        return $export;
    }
}
