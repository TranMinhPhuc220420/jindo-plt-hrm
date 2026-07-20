<?php

namespace App\Services\Organization;

use App\Exceptions\DomainException;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Position;
use App\Models\Team;
use App\Services\Audit\AuditLogger;
use Illuminate\Database\Eloquent\Collection;

class OrganizationService
{
    public function __construct(
        private readonly CompanyContext $companyContext,
        private readonly AuditLogger $audit,
    ) {}

    public function currentCompany(): Company
    {
        return $this->companyContext->current();
    }

    /**
     * @param  array{name?: string, legal_name?: string|null, tax_code?: string|null, email?: string|null, phone?: string|null, address?: string|null}  $data
     */
    public function updateCompany(array $data): Company
    {
        $company = $this->currentCompany();
        $before = $company->only(array_keys($data));
        $company->fill($data);
        $company->save();

        $this->audit->write(
            action: 'company.updated',
            subject: $company,
            payload: [
                'before' => $before,
                'after' => $company->only(array_keys($data)),
            ],
        );

        return $company->fresh();
    }

    /**
     * @return array<string, mixed>
     */
    public function tree(): array
    {
        $company = $this->currentCompany();

        $branches = Branch::query()
            ->where('company_id', $company->id)
            ->with(['departments.teams'])
            ->orderBy('name')
            ->get();

        $positions = Position::query()
            ->where('company_id', $company->id)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'is_active']);

        return [
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'code' => $company->code,
            ],
            'branches' => $branches->map(fn (Branch $branch) => [
                'id' => $branch->id,
                'name' => $branch->name,
                'code' => $branch->code,
                'is_active' => $branch->is_active,
                'departments' => $branch->departments->map(fn (Department $department) => [
                    'id' => $department->id,
                    'name' => $department->name,
                    'code' => $department->code,
                    'is_active' => $department->is_active,
                    'teams' => $department->teams->map(fn (Team $team) => [
                        'id' => $team->id,
                        'name' => $team->name,
                        'code' => $team->code,
                        'is_active' => $team->is_active,
                    ])->values()->all(),
                ])->values()->all(),
            ])->values()->all(),
            'positions' => $positions->map(fn (Position $position) => [
                'id' => $position->id,
                'name' => $position->name,
                'code' => $position->code,
                'is_active' => $position->is_active,
            ])->values()->all(),
        ];
    }

    /**
     * @return Collection<int, Branch>
     */
    public function listBranches(): Collection
    {
        return Branch::query()
            ->where('company_id', $this->companyContext->id())
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createBranch(array $data): Branch
    {
        $branch = Branch::query()->create([
            ...$data,
            'company_id' => $this->companyContext->id(),
            'is_active' => $data['is_active'] ?? true,
        ]);

        $this->audit->write(
            action: 'branch.created',
            subject: $branch,
            payload: ['name' => $branch->name, 'code' => $branch->code],
        );

        return $branch;
    }

    public function findBranch(int $id): Branch
    {
        return Branch::query()
            ->where('company_id', $this->companyContext->id())
            ->findOrFail($id);
    }

    /**
     * @param  array{name?: string, code?: string, address?: string|null, is_active?: bool}  $data
     */
    public function updateBranch(Branch $branch, array $data): Branch
    {
        $this->assertCompanyScope($branch->company_id);
        $before = $branch->only(array_keys($data));
        $branch->fill($data);
        $branch->save();

        $this->audit->write(
            action: 'branch.updated',
            subject: $branch,
            payload: [
                'before' => $before,
                'after' => $branch->only(array_keys($data)),
            ],
        );

        return $branch->fresh();
    }

    public function deleteBranch(Branch $branch): void
    {
        $this->assertCompanyScope($branch->company_id);

        if ($branch->departments()->exists()) {
            throw new DomainException(
                message: 'Cannot delete a branch that still has departments.',
                errorCode: 'BRANCH_HAS_DEPARTMENTS',
                status: 422,
            );
        }

        $payload = ['name' => $branch->name, 'code' => $branch->code];
        $branch->delete();

        $this->audit->write(
            action: 'branch.deleted',
            payload: $payload,
        );
    }

    /**
     * @return Collection<int, Department>
     */
    public function listDepartments(?int $branchId = null): Collection
    {
        $query = Department::query()
            ->where('company_id', $this->companyContext->id())
            ->orderBy('name');

        if ($branchId !== null) {
            $query->where('branch_id', $branchId);
        }

        return $query->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createDepartment(array $data): Department
    {
        $branch = $this->findBranch($data['branch_id']);

        $department = Department::query()->create([
            'company_id' => $branch->company_id,
            'branch_id' => $branch->id,
            'name' => $data['name'],
            'code' => $data['code'],
            'is_active' => $data['is_active'] ?? true,
        ]);

        $this->audit->write(
            action: 'department.created',
            subject: $department,
            payload: [
                'name' => $department->name,
                'code' => $department->code,
                'branch_id' => $department->branch_id,
            ],
        );

        return $department;
    }

    public function findDepartment(int $id): Department
    {
        return Department::query()
            ->where('company_id', $this->companyContext->id())
            ->findOrFail($id);
    }

    /**
     * @param  array{name?: string, code?: string, branch_id?: int, is_active?: bool}  $data
     */
    public function updateDepartment(Department $department, array $data): Department
    {
        $this->assertCompanyScope($department->company_id);

        if (isset($data['branch_id'])) {
            $branch = $this->findBranch($data['branch_id']);
            $data['branch_id'] = $branch->id;
        }

        $before = $department->only(array_keys($data));
        $department->fill($data);
        $department->save();

        $this->audit->write(
            action: 'department.updated',
            subject: $department,
            payload: [
                'before' => $before,
                'after' => $department->only(array_keys($data)),
            ],
        );

        return $department->fresh();
    }

    public function deleteDepartment(Department $department): void
    {
        $this->assertCompanyScope($department->company_id);

        if ($department->teams()->exists()) {
            throw new DomainException(
                message: 'Cannot delete a department that still has teams.',
                errorCode: 'DEPARTMENT_HAS_TEAMS',
                status: 422,
            );
        }

        $payload = ['name' => $department->name, 'code' => $department->code];
        $department->delete();

        $this->audit->write(
            action: 'department.deleted',
            payload: $payload,
        );
    }

    /**
     * @return Collection<int, Team>
     */
    public function listTeams(?int $departmentId = null): Collection
    {
        $query = Team::query()
            ->where('company_id', $this->companyContext->id())
            ->orderBy('name');

        if ($departmentId !== null) {
            $query->where('department_id', $departmentId);
        }

        return $query->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createTeam(array $data): Team
    {
        $department = $this->findDepartment($data['department_id']);

        $team = Team::query()->create([
            'company_id' => $department->company_id,
            'department_id' => $department->id,
            'name' => $data['name'],
            'code' => $data['code'],
            'is_active' => $data['is_active'] ?? true,
        ]);

        $this->audit->write(
            action: 'team.created',
            subject: $team,
            payload: [
                'name' => $team->name,
                'code' => $team->code,
                'department_id' => $team->department_id,
            ],
        );

        return $team;
    }

    public function findTeam(int $id): Team
    {
        return Team::query()
            ->where('company_id', $this->companyContext->id())
            ->findOrFail($id);
    }

    /**
     * @param  array{name?: string, code?: string, department_id?: int, is_active?: bool}  $data
     */
    public function updateTeam(Team $team, array $data): Team
    {
        $this->assertCompanyScope($team->company_id);

        if (isset($data['department_id'])) {
            $department = $this->findDepartment($data['department_id']);
            $data['department_id'] = $department->id;
        }

        $before = $team->only(array_keys($data));
        $team->fill($data);
        $team->save();

        $this->audit->write(
            action: 'team.updated',
            subject: $team,
            payload: [
                'before' => $before,
                'after' => $team->only(array_keys($data)),
            ],
        );

        return $team->fresh();
    }

    public function deleteTeam(Team $team): void
    {
        $this->assertCompanyScope($team->company_id);
        $payload = ['name' => $team->name, 'code' => $team->code];
        $team->delete();

        $this->audit->write(
            action: 'team.deleted',
            payload: $payload,
        );
    }

    /**
     * @return Collection<int, Position>
     */
    public function listPositions(): Collection
    {
        return Position::query()
            ->where('company_id', $this->companyContext->id())
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createPosition(array $data): Position
    {
        $position = Position::query()->create([
            ...$data,
            'company_id' => $this->companyContext->id(),
            'is_active' => $data['is_active'] ?? true,
        ]);

        $this->audit->write(
            action: 'position.created',
            subject: $position,
            payload: ['name' => $position->name, 'code' => $position->code],
        );

        return $position;
    }

    public function findPosition(int $id): Position
    {
        return Position::query()
            ->where('company_id', $this->companyContext->id())
            ->findOrFail($id);
    }

    /**
     * @param  array{name?: string, code?: string, description?: string|null, is_active?: bool}  $data
     */
    public function updatePosition(Position $position, array $data): Position
    {
        $this->assertCompanyScope($position->company_id);
        $before = $position->only(array_keys($data));
        $position->fill($data);
        $position->save();

        $this->audit->write(
            action: 'position.updated',
            subject: $position,
            payload: [
                'before' => $before,
                'after' => $position->only(array_keys($data)),
            ],
        );

        return $position->fresh();
    }

    public function deletePosition(Position $position): void
    {
        $this->assertCompanyScope($position->company_id);
        $payload = ['name' => $position->name, 'code' => $position->code];
        $position->delete();

        $this->audit->write(
            action: 'position.deleted',
            payload: $payload,
        );
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
}
