<?php

namespace App\Services\Employee;

use App\Events\EmployeeCreated;
use App\Events\EmployeeStatusChanged;
use App\Exceptions\DomainException;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeBankAccount;
use App\Models\EmployeeContract;
use App\Models\EmployeeEducation;
use App\Models\EmployeeEmergencyContact;
use App\Models\EmployeeFamilyMember;
use App\Models\EmployeeInsurance;
use App\Models\EmployeeTaxProfile;
use App\Models\EmployeeWorkHistory;
use App\Models\Position;
use App\Models\Team;
use App\Services\Audit\AuditLogger;
use App\Services\Organization\CompanyContext;
use App\Services\Shift\ShiftAssignmentService;
use App\Support\SettingsDefaults;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class EmployeeService
{
    public function __construct(
        private readonly CompanyContext $companyContext,
        private readonly AuditLogger $audit,
        private readonly EmployeeOffboardingService $offboarding,
        private readonly ShiftAssignmentService $shiftAssignments,
    ) {}

    /**
     * @param  array{search?: string, status?: string, department_id?: int}  $filters
     * @return LengthAwarePaginator<int, Employee>
     */
    public function list(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Employee::query()
            ->where('company_id', $this->companyContext->id())
            ->with(['department', 'position', 'branch'])
            ->orderBy('full_name');

        if (! empty($filters['search'])) {
            $search = '%'.$filters['search'].'%';
            $query->where(function ($q) use ($search): void {
                $q->where('code', 'like', $search)
                    ->orWhere('full_name', 'like', $search)
                    ->orWhere('email', 'like', $search);
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        return $query->paginate($perPage);
    }

    public function find(int $id): Employee
    {
        return Employee::query()
            ->where('company_id', $this->companyContext->id())
            ->with(['department', 'position', 'branch', 'team', 'activeAssetAssignments.asset'])
            ->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Employee
    {
        $companyId = $this->companyContext->id();
        $this->assertOrgPlacement($data, $companyId);

        $data['company_id'] = $companyId;
        $data['full_name'] = $this->composeFullName($data);
        $data['status'] = $data['status'] ?? 'probation';

        try {
            $employee = Employee::query()->create($data);
        } catch (QueryException $e) {
            if ($this->isUniqueViolation($e)) {
                throw new DomainException(
                    message: 'Employee code already exists for this company.',
                    errorCode: 'EMPLOYEE_CODE_DUPLICATE',
                    status: 422,
                );
            }

            throw $e;
        }

        $this->audit->write(
            action: 'employee.created',
            subject: $employee,
            payload: ['code' => $employee->code, 'status' => $employee->status],
        );

        EmployeeCreated::dispatch($employee);

        return $employee->fresh(['department', 'position', 'branch']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Employee $employee, array $data): Employee
    {
        $this->assertCompanyScope($employee->company_id);
        $this->assertOrgPlacement($data, $employee->company_id);

        if (isset($data['first_name']) || isset($data['last_name'])) {
            $data['full_name'] = $this->composeFullName([
                'first_name' => $data['first_name'] ?? $employee->first_name,
                'last_name' => $data['last_name'] ?? $employee->last_name,
            ]);
        }

        unset($data['status'], $data['company_id']);

        $before = $employee->only(array_keys($data));

        try {
            $employee->fill($data);
            $employee->save();
        } catch (QueryException $e) {
            if ($this->isUniqueViolation($e)) {
                throw new DomainException(
                    message: 'Employee code already exists for this company.',
                    errorCode: 'EMPLOYEE_CODE_DUPLICATE',
                    status: 422,
                );
            }

            throw $e;
        }

        $this->audit->write(
            action: 'employee.updated',
            subject: $employee,
            payload: [
                'before' => $before,
                'after' => $employee->only(array_keys($data)),
            ],
        );

        return $employee->fresh(['department', 'position', 'branch']);
    }

    /**
     * @param  array{status: string, reason?: string|null, effective_on?: string|null, confirm_asset_return?: bool}  $data
     */
    public function changeStatus(Employee $employee, array $data): Employee
    {
        $this->assertCompanyScope($employee->company_id);

        $to = $data['status'];
        $from = $employee->status;

        if (! EmployeeStatusTransitions::canTransition($from, $to)) {
            throw new DomainException(
                message: __('domain.employee_invalid_status_transition', [
                    'from' => $from,
                    'to' => $to,
                ]),
                errorCode: 'EMPLOYEE_INVALID_STATUS_TRANSITION',
                status: 409,
            );
        }

        $effectiveOn = $this->resolveEffectiveOn($data['effective_on'] ?? null);
        $confirmAssetReturn = (bool) ($data['confirm_asset_return'] ?? false);

        return DB::transaction(function () use (
            $employee,
            $data,
            $from,
            $to,
            $effectiveOn,
            $confirmAssetReturn,
        ): Employee {
            if (Employee::isOffboardingStatus($to)) {
                $this->offboarding->assertAssetsReturnedOrConfirmed($employee, $confirmAssetReturn);
            }

            $employee->status = $to;
            $this->applyTerminationDate($employee, $from, $to, $effectiveOn);
            $employee->save();

            if (Employee::isOffboardingStatus($to)) {
                $this->shiftAssignments->closeFrom($employee, $effectiveOn);

                if ($confirmAssetReturn) {
                    $this->offboarding->returnOutstanding($employee);
                }
            }

            $action = $to === 'archived' ? 'employee.archived' : 'employee.status_changed';

            $this->audit->write(
                action: $action,
                subject: $employee,
                payload: [
                    'from' => $from,
                    'to' => $to,
                    'reason' => $data['reason'] ?? null,
                    'effective_on' => $effectiveOn,
                ],
            );

            EmployeeStatusChanged::dispatch($employee, $from, $to);

            return $employee->fresh(['department', 'position', 'branch', 'activeAssetAssignments.asset']);
        });
    }

    public function archive(Employee $employee): void
    {
        $this->assertCompanyScope($employee->company_id);

        $from = $employee->status;
        $effectiveOn = $this->resolveEffectiveOn(null);

        DB::transaction(function () use ($employee, $from, $effectiveOn): void {
            if ($from !== 'archived') {
                $this->offboarding->assertAssetsReturnedOrConfirmed($employee, confirmAssetReturn: false);

                $employee->status = 'archived';
                $this->applyTerminationDate($employee, $from, 'archived', $effectiveOn);
                $employee->save();

                $this->shiftAssignments->closeFrom($employee, $effectiveOn);

                $this->audit->write(
                    action: 'employee.archived',
                    subject: $employee,
                    payload: [
                        'from' => $from,
                        'to' => 'archived',
                        'reason' => 'Soft-deleted via API',
                        'effective_on' => $effectiveOn,
                    ],
                );

                EmployeeStatusChanged::dispatch($employee, $from, 'archived');
            }

            $employee->delete();
        });
    }

    /**
     * @return Collection<int, EmployeeEmergencyContact>
     */
    public function listEmergencyContacts(Employee $employee): Collection
    {
        $this->assertCompanyScope($employee->company_id);

        return $employee->emergencyContacts()->orderByDesc('is_primary')->orderBy('id')->get();
    }

    /**
     * @param  list<array<string, mixed>>  $contacts
     * @return Collection<int, EmployeeEmergencyContact>
     */
    public function replaceEmergencyContacts(Employee $employee, array $contacts): Collection
    {
        $this->assertCompanyScope($employee->company_id);

        DB::transaction(function () use ($employee, $contacts): void {
            $employee->emergencyContacts()->delete();

            foreach ($contacts as $contact) {
                $employee->emergencyContacts()->create([
                    ...$contact,
                    'company_id' => $employee->company_id,
                ]);
            }
        });

        $this->audit->write(
            action: 'employee.emergency_contacts_updated',
            subject: $employee,
            payload: ['count' => count($contacts)],
        );

        return $this->listEmergencyContacts($employee);
    }

    /**
     * @return Collection<int, EmployeeEducation>
     */
    public function listEducations(Employee $employee): Collection
    {
        $this->assertCompanyScope($employee->company_id);

        return $employee->educations()->orderByDesc('started_on')->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createEducation(Employee $employee, array $data): EmployeeEducation
    {
        $this->assertCompanyScope($employee->company_id);

        $education = $employee->educations()->create([
            ...$data,
            'company_id' => $employee->company_id,
        ]);

        $this->audit->write(
            action: 'employee.education_created',
            subject: $employee,
            payload: ['education_id' => $education->id],
        );

        return $education;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateEducation(Employee $employee, EmployeeEducation $education, array $data): EmployeeEducation
    {
        $this->assertSatelliteOwnership($employee, $education->employee_id);
        $education->fill($data);
        $education->save();

        $this->audit->write(
            action: 'employee.education_updated',
            subject: $employee,
            payload: ['education_id' => $education->id],
        );

        return $education->fresh();
    }

    public function deleteEducation(Employee $employee, EmployeeEducation $education): void
    {
        $this->assertSatelliteOwnership($employee, $education->employee_id);
        $id = $education->id;
        $education->delete();

        $this->audit->write(
            action: 'employee.education_deleted',
            subject: $employee,
            payload: ['education_id' => $id],
        );
    }

    /**
     * @return Collection<int, EmployeeWorkHistory>
     */
    public function listWorkHistories(Employee $employee): Collection
    {
        $this->assertCompanyScope($employee->company_id);

        return $employee->workHistories()->orderByDesc('started_on')->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createWorkHistory(Employee $employee, array $data): EmployeeWorkHistory
    {
        $this->assertCompanyScope($employee->company_id);

        $row = $employee->workHistories()->create([
            ...$data,
            'company_id' => $employee->company_id,
        ]);

        $this->audit->write(
            action: 'employee.work_history_created',
            subject: $employee,
            payload: ['work_history_id' => $row->id],
        );

        return $row;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateWorkHistory(Employee $employee, EmployeeWorkHistory $history, array $data): EmployeeWorkHistory
    {
        $this->assertSatelliteOwnership($employee, $history->employee_id);
        $history->fill($data);
        $history->save();

        $this->audit->write(
            action: 'employee.work_history_updated',
            subject: $employee,
            payload: ['work_history_id' => $history->id],
        );

        return $history->fresh();
    }

    public function deleteWorkHistory(Employee $employee, EmployeeWorkHistory $history): void
    {
        $this->assertSatelliteOwnership($employee, $history->employee_id);
        $id = $history->id;
        $history->delete();

        $this->audit->write(
            action: 'employee.work_history_deleted',
            subject: $employee,
            payload: ['work_history_id' => $id],
        );
    }

    /**
     * @return Collection<int, EmployeeFamilyMember>
     */
    public function listFamilyMembers(Employee $employee): Collection
    {
        $this->assertCompanyScope($employee->company_id);

        return $employee->familyMembers()->orderBy('id')->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createFamilyMember(Employee $employee, array $data): EmployeeFamilyMember
    {
        $this->assertCompanyScope($employee->company_id);

        $row = $employee->familyMembers()->create([
            ...$data,
            'company_id' => $employee->company_id,
        ]);

        $this->audit->write(
            action: 'employee.family_member_created',
            subject: $employee,
            payload: ['family_member_id' => $row->id],
        );

        return $row;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateFamilyMember(Employee $employee, EmployeeFamilyMember $member, array $data): EmployeeFamilyMember
    {
        $this->assertSatelliteOwnership($employee, $member->employee_id);
        $member->fill($data);
        $member->save();

        $this->audit->write(
            action: 'employee.family_member_updated',
            subject: $employee,
            payload: ['family_member_id' => $member->id],
        );

        return $member->fresh();
    }

    public function deleteFamilyMember(Employee $employee, EmployeeFamilyMember $member): void
    {
        $this->assertSatelliteOwnership($employee, $member->employee_id);
        $id = $member->id;
        $member->delete();

        $this->audit->write(
            action: 'employee.family_member_deleted',
            subject: $employee,
            payload: ['family_member_id' => $id],
        );
    }

    /**
     * @return Collection<int, EmployeeContract>
     */
    public function listContracts(Employee $employee): Collection
    {
        $this->assertCompanyScope($employee->company_id);

        return $employee->contracts()->orderByDesc('start_date')->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createContract(Employee $employee, array $data): EmployeeContract
    {
        $this->assertCompanyScope($employee->company_id);

        $contract = $employee->contracts()->create([
            ...$data,
            'company_id' => $employee->company_id,
            'status' => $data['status'] ?? 'active',
        ]);

        $this->audit->write(
            action: 'employee.contract_created',
            subject: $employee,
            payload: ['contract_id' => $contract->id],
        );

        return $contract;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateContract(Employee $employee, EmployeeContract $contract, array $data): EmployeeContract
    {
        $this->assertSatelliteOwnership($employee, $contract->employee_id);
        $contract->fill($data);
        $contract->save();

        $this->audit->write(
            action: 'employee.contract_updated',
            subject: $employee,
            payload: ['contract_id' => $contract->id],
        );

        return $contract->fresh();
    }

    public function getBankAccount(Employee $employee): ?EmployeeBankAccount
    {
        $this->assertCompanyScope($employee->company_id);

        return $employee->bankAccount;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function upsertBankAccount(Employee $employee, array $data): EmployeeBankAccount
    {
        $this->assertCompanyScope($employee->company_id);

        $account = $employee->bankAccount()->updateOrCreate(
            ['employee_id' => $employee->id],
            [...$data, 'company_id' => $employee->company_id],
        );

        $this->audit->write(
            action: 'employee.bank_account_updated',
            subject: $employee,
            payload: ['bank_name' => $account->bank_name],
        );

        return $account->fresh();
    }

    public function getTaxProfile(Employee $employee): ?EmployeeTaxProfile
    {
        $this->assertCompanyScope($employee->company_id);

        return $employee->taxProfile;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function upsertTaxProfile(Employee $employee, array $data): EmployeeTaxProfile
    {
        $this->assertCompanyScope($employee->company_id);

        $profile = $employee->taxProfile()->updateOrCreate(
            ['employee_id' => $employee->id],
            [...$data, 'company_id' => $employee->company_id],
        );

        $this->audit->write(
            action: 'employee.tax_profile_updated',
            subject: $employee,
            payload: ['tax_code' => $profile->tax_code],
        );

        return $profile->fresh();
    }

    public function getInsurance(Employee $employee): ?EmployeeInsurance
    {
        $this->assertCompanyScope($employee->company_id);

        return $employee->insurance;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function upsertInsurance(Employee $employee, array $data): EmployeeInsurance
    {
        $this->assertCompanyScope($employee->company_id);

        $insurance = $employee->insurance()->updateOrCreate(
            ['employee_id' => $employee->id],
            [...$data, 'company_id' => $employee->company_id],
        );

        $this->audit->write(
            action: 'employee.insurance_updated',
            subject: $employee,
            payload: ['provider' => $insurance->provider],
        );

        return $insurance->fresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function assertOrgPlacement(array $data, int $companyId): void
    {
        $checks = [
            'branch_id' => [Branch::class, 'branch'],
            'department_id' => [Department::class, 'department'],
            'team_id' => [Team::class, 'team'],
            'position_id' => [Position::class, 'position'],
        ];

        foreach ($checks as $field => [$model, $label]) {
            if (! array_key_exists($field, $data) || $data[$field] === null) {
                continue;
            }

            $exists = $model::query()
                ->whereKey($data[$field])
                ->where('company_id', $companyId)
                ->exists();

            if (! $exists) {
                throw new DomainException(
                    message: __('domain.employee_org_scope', ['label' => $label]),
                    errorCode: 'COMPANY_SCOPE_MISMATCH',
                    status: 404,
                );
            }
        }

        foreach (['manager_id', 'supervisor_id', 'hr_owner_id'] as $field) {
            if (! array_key_exists($field, $data) || $data[$field] === null) {
                continue;
            }

            $exists = Employee::query()
                ->whereKey($data[$field])
                ->where('company_id', $companyId)
                ->exists();

            if (! $exists) {
                throw new DomainException(
                    message: 'Referenced employee is outside the current company scope.',
                    errorCode: 'COMPANY_SCOPE_MISMATCH',
                    status: 404,
                );
            }
        }
    }

    /**
     * @param  array{first_name?: string, last_name?: string}  $data
     */
    protected function composeFullName(array $data): string
    {
        return trim(($data['first_name'] ?? '').' '.($data['last_name'] ?? ''));
    }

    protected function assertCompanyScope(int $companyId): void
    {
        if ($companyId !== $this->companyContext->id()) {
            throw new DomainException(
                message: 'Resource is outside the current company scope.',
                errorCode: 'COMPANY_SCOPE_MISMATCH',
                status: 404,
            );
        }
    }

    protected function assertSatelliteOwnership(Employee $employee, int $employeeId): void
    {
        $this->assertCompanyScope($employee->company_id);

        if ($employee->id !== $employeeId) {
            throw new DomainException(
                message: 'Satellite record does not belong to this employee.',
                errorCode: 'COMPANY_SCOPE_MISMATCH',
                status: 404,
            );
        }
    }

    protected function isUniqueViolation(QueryException $e): bool
    {
        return str_contains(strtolower($e->getMessage()), 'unique')
            || (string) $e->getCode() === '23000';
    }

    protected function resolveEffectiveOn(?string $effectiveOn): string
    {
        if ($effectiveOn !== null && $effectiveOn !== '') {
            return CarbonImmutable::parse($effectiveOn)->toDateString();
        }

        $timezone = SettingsDefaults::all()['company']['timezone'] ?? 'UTC';

        return CarbonImmutable::now(is_string($timezone) ? $timezone : 'UTC')->toDateString();
    }

    protected function applyTerminationDate(
        Employee $employee,
        string $from,
        string $to,
        string $effectiveOn,
    ): void {
        if ($to === 'resigned' || ($to === 'archived' && $employee->terminated_at === null)) {
            $employee->terminated_at = $effectiveOn;

            return;
        }

        if (in_array($to, ['active', 'probation'], true) && in_array($from, ['suspended', 'resigned'], true)) {
            $employee->terminated_at = null;
        }
    }
}
