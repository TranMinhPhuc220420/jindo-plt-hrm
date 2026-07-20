<?php

namespace App\Services\Payroll;

use App\Events\PayrollApproved;
use App\Events\PayrollCalculated;
use App\Events\PayrollFinalized;
use App\Exceptions\DomainException;
use App\Jobs\GeneratePayslipPdfJob;
use App\Models\Employee;
use App\Models\EmployeeAllowance;
use App\Models\EmployeeBonus;
use App\Models\EmployeeDeduction;
use App\Models\PayrollItem;
use App\Models\PayrollRun;
use App\Models\Payslip;
use App\Models\User;
use App\Services\Attendance\AttendanceSummaryService;
use App\Services\Audit\AuditLogger;
use App\Services\Leave\LeaveCoverageService;
use App\Services\Organization\CompanyContext;
use App\Services\Payroll\Strategies\MonthlyPayrollStrategy;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PayrollRunService
{
    public function __construct(
        private readonly CompanyContext $companyContext,
        private readonly AuditLogger $audit,
        private readonly AttendanceSummaryService $attendanceSummaries,
        private readonly EmployeeSalaryService $salaries,
        private readonly MonthlyPayrollStrategy $strategy,
        private readonly LeaveCoverageService $leaveCoverage,
    ) {}

    /**
     * @param  array{status?: string}  $filters
     * @return LengthAwarePaginator<int, PayrollRun>
     */
    public function list(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = PayrollRun::query()
            ->where('company_id', $this->companyContext->id())
            ->orderByDesc('period_start')
            ->orderByDesc('id');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->paginate($perPage);
    }

    public function find(int $id): PayrollRun
    {
        $run = PayrollRun::query()
            ->where('company_id', $this->companyContext->id())
            ->find($id);

        if ($run === null) {
            throw new DomainException(
                message: 'Payroll run not found.',
                errorCode: 'NOT_FOUND',
                status: 404,
            );
        }

        return $run;
    }

    /**
     * @param  array{period_start: string, period_end: string, name: string}  $data
     */
    public function create(array $data): PayrollRun
    {
        $companyId = $this->companyContext->id();
        $start = CarbonImmutable::parse($data['period_start'])->toDateString();
        $end = CarbonImmutable::parse($data['period_end'])->toDateString();

        if ($end < $start) {
            throw new DomainException(
                message: 'period_end must be on or after period_start.',
                errorCode: 'VALIDATION_FAILED',
                status: 422,
            );
        }

        $exists = PayrollRun::query()
            ->where('company_id', $companyId)
            ->whereDate('period_start', $start)
            ->whereDate('period_end', $end)
            ->where('run_type', 'regular')
            ->exists();

        if ($exists) {
            throw new DomainException(
                message: 'A payroll run already exists for this period.',
                errorCode: 'PAYROLL_DUPLICATE_PERIOD',
                status: 422,
            );
        }

        $run = PayrollRun::query()->create([
            'company_id' => $companyId,
            'name' => $data['name'],
            'period_start' => $start,
            'period_end' => $end,
            'run_type' => 'regular',
            'status' => 'draft',
        ]);

        $this->audit->write(
            action: 'payroll.run_created',
            subject: $run,
            payload: ['period_start' => $start, 'period_end' => $end],
        );

        return $run;
    }

    /**
     * @param  array{period_start: string, period_end: string, name: string}  $data
     */
    public function update(PayrollRun $run, array $data): PayrollRun
    {
        if ($run->status !== 'draft') {
            throw new DomainException(
                message: 'Only draft payroll runs can be edited.',
                errorCode: 'PAYROLL_NOT_DRAFT',
                status: 422,
            );
        }

        $start = CarbonImmutable::parse($data['period_start'])->toDateString();
        $end = CarbonImmutable::parse($data['period_end'])->toDateString();

        if ($end < $start) {
            throw new DomainException(
                message: 'period_end must be on or after period_start.',
                errorCode: 'VALIDATION_FAILED',
                status: 422,
            );
        }

        $exists = PayrollRun::query()
            ->where('company_id', $this->companyContext->id())
            ->whereDate('period_start', $start)
            ->whereDate('period_end', $end)
            ->where('run_type', 'regular')
            ->where('id', '!=', $run->id)
            ->exists();

        if ($exists) {
            throw new DomainException(
                message: 'A payroll run already exists for this period.',
                errorCode: 'PAYROLL_DUPLICATE_PERIOD',
                status: 422,
            );
        }

        $run->name = $data['name'];
        $run->period_start = $start;
        $run->period_end = $end;
        $run->save();

        $this->audit->write(
            action: 'payroll.run_updated',
            subject: $run,
            payload: [
                'name' => $run->name,
                'period_start' => $start,
                'period_end' => $end,
            ],
        );

        return $run->fresh();
    }

    public function delete(PayrollRun $run): void
    {
        $this->assertMutable($run);

        $this->audit->write(
            action: 'payroll.run_deleted',
            subject: $run,
            payload: [
                'name' => $run->name,
                'status' => $run->status,
                'period_start' => $run->period_start?->toDateString(),
                'period_end' => $run->period_end?->toDateString(),
            ],
        );

        $run->delete();
    }

    public function calculate(PayrollRun $run, User $actor): PayrollRun
    {
        $this->assertMutable($run);

        if (! in_array($run->status, ['draft', 'calculated'], true)) {
            throw new DomainException(
                message: 'Payroll run cannot be recalculated in its current status.',
                errorCode: 'PAYROLL_ALREADY_FINALIZED',
                status: 422,
            );
        }

        $companyId = $this->companyContext->id();
        $periodStart = $run->period_start->toDateString();
        $periodEnd = $run->period_end->toDateString();

        $employees = Employee::query()
            ->where('company_id', $companyId)
            ->where('status', 'active')
            ->orderBy('id')
            ->get();

        return DB::transaction(function () use ($run, $actor, $employees, $companyId, $periodStart, $periodEnd): PayrollRun {
            PayrollItem::query()->where('payroll_run_id', $run->id)->delete();

            $totalGross = 0.0;
            $totalNet = 0.0;
            $count = 0;

            foreach ($employees as $employee) {
                $salary = $this->salaries->effectiveSalary($employee->id, $periodEnd);

                if ($salary === null) {
                    continue;
                }

                try {
                    $summary = $this->attendanceSummaries->summarizeForPayroll(
                        $employee->id,
                        $periodStart,
                        $periodEnd,
                    );
                } catch (\Throwable $e) {
                    throw new DomainException(
                        message: 'Payroll calculation failed for employee '.$employee->id.': '.$e->getMessage(),
                        errorCode: 'PAYROLL_CALCULATION_FAILED',
                        status: 422,
                    );
                }

                $allowances = EmployeeAllowance::query()
                    ->where('company_id', $companyId)
                    ->where('employee_id', $employee->id)
                    ->where('is_active', true)
                    ->get()
                    ->map(fn (EmployeeAllowance $row) => [
                        'code' => $row->code,
                        'name' => $row->name,
                        'amount' => (float) $row->amount,
                    ])
                    ->all();

                $bonuses = EmployeeBonus::query()
                    ->where('company_id', $companyId)
                    ->where('employee_id', $employee->id)
                    ->where('is_active', true)
                    ->get()
                    ->map(fn (EmployeeBonus $row) => [
                        'code' => $row->code,
                        'name' => $row->name,
                        'amount' => (float) $row->amount,
                    ])
                    ->all();

                $deductions = EmployeeDeduction::query()
                    ->where('company_id', $companyId)
                    ->where('employee_id', $employee->id)
                    ->where('is_active', true)
                    ->get()
                    ->map(fn (EmployeeDeduction $row) => [
                        'code' => $row->code,
                        'name' => $row->name,
                        'amount' => (float) $row->amount,
                    ])
                    ->all();

                $result = $this->strategy->calculate([
                    'employee_id' => $employee->id,
                    'period_start' => $periodStart,
                    'period_end' => $periodEnd,
                    'base_amount' => (float) $salary->amount,
                    'currency' => $salary->currency,
                    'allowances' => $allowances,
                    'bonuses' => $bonuses,
                    'deductions' => $deductions,
                    'overtime_minutes' => $summary['overtime_minutes'],
                    'unpaid_leave_days' => $this->unpaidLeaveDays($employee->id, $periodStart, $periodEnd),
                ]);

                PayrollItem::query()->create([
                    'payroll_run_id' => $run->id,
                    'company_id' => $companyId,
                    'employee_id' => $employee->id,
                    'gross' => $result['gross'],
                    'net' => $result['net'],
                    'components' => $result['components'],
                ]);

                $totalGross += $result['gross'];
                $totalNet += $result['net'];
                $count++;
            }

            $run->status = 'calculated';
            $run->employee_count = $count;
            $run->total_gross = round($totalGross, 2);
            $run->total_net = round($totalNet, 2);
            $run->calculated_at = now();
            $run->calculated_by = $actor->id;
            $run->save();

            $this->audit->write(
                action: 'payroll.run_calculated',
                subject: $run,
                payload: [
                    'employee_count' => $count,
                    'total_net' => (string) $run->total_net,
                ],
            );

            PayrollCalculated::dispatch($run);

            return $run->fresh();
        });
    }

    public function approve(PayrollRun $run, User $actor): PayrollRun
    {
        $this->assertMutable($run);

        if ($run->status !== 'calculated') {
            throw new DomainException(
                message: 'Payroll run must be calculated before approval.',
                errorCode: 'PAYROLL_NOT_CALCULATED',
                status: 422,
            );
        }

        $run->status = 'approved';
        $run->approved_at = now();
        $run->approved_by = $actor->id;
        $run->save();

        $this->audit->write(
            action: 'payroll.run_approved',
            subject: $run,
            payload: [],
        );

        PayrollApproved::dispatch($run);

        return $run->fresh();
    }

    public function finalize(PayrollRun $run, User $actor): PayrollRun
    {
        if ($run->status === 'finalized') {
            throw new DomainException(
                message: 'Payroll run is already finalized.',
                errorCode: 'PAYROLL_ALREADY_FINALIZED',
                status: 422,
            );
        }

        if ($run->status !== 'approved') {
            throw new DomainException(
                message: 'Payroll run must be approved before finalize.',
                errorCode: 'PAYROLL_NOT_CALCULATED',
                status: 422,
            );
        }

        return DB::transaction(function () use ($run, $actor): PayrollRun {
            $items = PayrollItem::query()
                ->where('payroll_run_id', $run->id)
                ->get();

            foreach ($items as $item) {
                $payslip = Payslip::query()->updateOrCreate(
                    ['payroll_item_id' => $item->id],
                    [
                        'company_id' => $run->company_id,
                        'payroll_run_id' => $run->id,
                        'employee_id' => $item->employee_id,
                        'period_start' => $run->period_start,
                        'period_end' => $run->period_end,
                        'gross' => $item->gross,
                        'net' => $item->net,
                        'components' => $item->components,
                    ],
                );

                GeneratePayslipPdfJob::dispatch($payslip->id)->onQueue('payroll');
            }

            $run->status = 'finalized';
            $run->finalized_at = now();
            $run->finalized_by = $actor->id;
            $run->save();

            $this->audit->write(
                action: 'payroll.run_finalized',
                subject: $run,
                payload: ['payslip_count' => $items->count()],
            );

            PayrollFinalized::dispatch($run);

            return $run->fresh();
        });
    }

    /**
     * @return LengthAwarePaginator<int, PayrollItem>
     */
    public function listItems(PayrollRun $run, int $perPage = 50): LengthAwarePaginator
    {
        return PayrollItem::query()
            ->with('employee')
            ->where('payroll_run_id', $run->id)
            ->orderBy('employee_id')
            ->paginate($perPage);
    }

    private function unpaidLeaveDays(int $employeeId, string $periodStart, string $periodEnd): float
    {
        return $this->leaveCoverage->unpaidDayEquivalentInPeriod(
            $employeeId,
            $periodStart,
            $periodEnd,
        );
    }

    private function assertMutable(PayrollRun $run): void
    {
        if ($run->status === 'finalized') {
            throw new DomainException(
                message: 'Finalized payroll runs are immutable.',
                errorCode: 'PAYROLL_ALREADY_FINALIZED',
                status: 422,
            );
        }
    }
}
