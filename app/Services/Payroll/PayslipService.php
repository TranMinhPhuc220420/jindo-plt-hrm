<?php

namespace App\Services\Payroll;

use App\Exceptions\DomainException;
use App\Jobs\GeneratePayslipPdfJob;
use App\Models\Payslip;
use App\Models\User;
use App\Services\Organization\CompanyContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PayslipService
{
    public function __construct(
        private readonly CompanyContext $companyContext,
    ) {}

    /**
     * @param  array{employee_id?: int}  $filters
     * @return LengthAwarePaginator<int, Payslip>
     */
    public function list(array $filters, User $viewer, int $perPage = 20): LengthAwarePaginator
    {
        $query = Payslip::query()
            ->with('employee')
            ->where('company_id', $this->companyContext->id())
            ->orderByDesc('id');

        $canManage = $viewer->can('can_manage_payslips')
            || $viewer->can('can_view_payroll_history');

        if (! $canManage) {
            $ownId = $viewer->employee?->id;
            if ($ownId === null) {
                $query->whereRaw('1 = 0');
            } else {
                $query->where('employee_id', $ownId);
            }
        } elseif (! empty($filters['employee_id'])) {
            $query->where('employee_id', (int) $filters['employee_id']);
        }

        return $query->paginate($perPage);
    }

    public function find(int $id, User $viewer): Payslip
    {
        $payslip = Payslip::query()
            ->with('employee')
            ->where('company_id', $this->companyContext->id())
            ->find($id);

        if ($payslip === null) {
            throw new DomainException(
                message: 'Payslip not found.',
                errorCode: 'NOT_FOUND',
                status: 404,
            );
        }

        $this->assertCanView($payslip, $viewer);

        return $payslip;
    }

    public function download(int $id, User $viewer): StreamedResponse
    {
        $payslip = $this->find($id, $viewer);

        if ($payslip->pdf_path === null || ! Storage::disk('local')->exists($payslip->pdf_path)) {
            GeneratePayslipPdfJob::dispatchSync($payslip->id);
            $payslip->refresh();
        }

        if ($payslip->pdf_path === null || ! Storage::disk('local')->exists($payslip->pdf_path)) {
            throw new DomainException(
                message: 'Payslip PDF is not available.',
                errorCode: 'PAYROLL_CALCULATION_FAILED',
                status: 422,
            );
        }

        return Storage::disk('local')->download(
            $payslip->pdf_path,
            'payslip-'.$payslip->id.'.pdf',
        );
    }

    private function assertCanView(Payslip $payslip, User $viewer): void
    {
        if ($viewer->can('can_manage_payslips') || $viewer->can('can_view_payroll_history')) {
            return;
        }

        if (! $viewer->can('can_view_salary')) {
            throw new DomainException(
                message: 'Forbidden.',
                errorCode: 'FORBIDDEN',
                status: 403,
            );
        }

        if ($viewer->employee?->id !== $payslip->employee_id) {
            throw new DomainException(
                message: 'You may only view your own payslip.',
                errorCode: 'FORBIDDEN',
                status: 403,
            );
        }
    }
}
