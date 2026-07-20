<?php

namespace App\Services\Asset;

use App\Events\AssetAssigned;
use App\Events\AssetReturned;
use App\Exceptions\DomainException;
use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\AssetDamageReport;
use App\Models\AssetMaintenance;
use App\Models\Employee;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Organization\CompanyContext;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class AssetService
{
    public function __construct(
        private readonly CompanyContext $companyContext,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array{status?: string, category?: string, search?: string}  $filters
     * @return LengthAwarePaginator<int, Asset>
     */
    public function list(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Asset::query()
            ->where('company_id', $this->companyContext->id())
            ->orderBy('code');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (! empty($filters['search'])) {
            $search = '%'.$filters['search'].'%';
            $query->where(function ($q) use ($search): void {
                $q->where('code', 'like', $search)
                    ->orWhere('name', 'like', $search)
                    ->orWhere('serial_number', 'like', $search);
            });
        }

        return $query->paginate($perPage);
    }

    public function find(int $id): Asset
    {
        $asset = Asset::query()
            ->where('company_id', $this->companyContext->id())
            ->with('assignee')
            ->find($id);

        if ($asset === null) {
            throw new DomainException(
                message: 'Asset not found.',
                errorCode: 'NOT_FOUND',
                status: 404,
            );
        }

        return $asset;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Asset
    {
        $data['company_id'] = $this->companyContext->id();
        $data['status'] = $data['status'] ?? 'available';

        try {
            $asset = Asset::query()->create($data);
        } catch (QueryException $e) {
            if ($this->isUniqueViolation($e)) {
                throw new DomainException(
                    message: 'Asset code already exists for this company.',
                    errorCode: 'ASSET_CODE_DUPLICATE',
                    status: 422,
                );
            }

            throw $e;
        }

        $this->audit->write(
            action: 'asset.created',
            subject: $asset,
            payload: ['code' => $asset->code, 'status' => $asset->status],
        );

        return $asset;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Asset $asset, array $data): Asset
    {
        $this->assertCompanyScope($asset->company_id);

        unset($data['company_id'], $data['assigned_to']);

        try {
            $asset->fill($data);
            $asset->save();
        } catch (QueryException $e) {
            if ($this->isUniqueViolation($e)) {
                throw new DomainException(
                    message: 'Asset code already exists for this company.',
                    errorCode: 'ASSET_CODE_DUPLICATE',
                    status: 422,
                );
            }

            throw $e;
        }

        $this->audit->write(
            action: 'asset.updated',
            subject: $asset,
            payload: ['code' => $asset->code],
        );

        return $asset->fresh('assignee');
    }

    public function retire(Asset $asset): Asset
    {
        $this->assertCompanyScope($asset->company_id);

        if ($asset->status === 'assigned') {
            throw new DomainException(
                message: 'Return the asset before retiring it.',
                errorCode: 'ASSET_INVALID_STATUS',
                status: 422,
            );
        }

        $asset->status = 'retired';
        $asset->save();

        $this->audit->write(
            action: 'asset.retired',
            subject: $asset,
            payload: ['code' => $asset->code],
        );

        return $asset->fresh('assignee');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function assign(Asset $asset, array $data, User $actor): AssetAssignment
    {
        $this->assertCompanyScope($asset->company_id);

        if ($asset->status === 'assigned' || $asset->assigned_to !== null) {
            throw new DomainException(
                message: 'Asset is already assigned.',
                errorCode: 'ASSET_ALREADY_ASSIGNED',
                status: 422,
            );
        }

        if ($asset->status !== 'available') {
            throw new DomainException(
                message: 'Asset is not available for assignment.',
                errorCode: 'ASSET_NOT_AVAILABLE',
                status: 422,
            );
        }

        $employee = Employee::query()
            ->where('company_id', $asset->company_id)
            ->find((int) $data['employee_id']);

        if (! $employee instanceof Employee) {
            throw new DomainException(
                message: 'Employee is outside the current company scope.',
                errorCode: 'COMPANY_SCOPE_MISMATCH',
                status: 404,
            );
        }

        return DB::transaction(function () use ($asset, $data, $employee, $actor): AssetAssignment {
            $assignment = AssetAssignment::query()->create([
                'company_id' => $asset->company_id,
                'asset_id' => $asset->id,
                'employee_id' => $employee->id,
                'status' => 'active',
                'assigned_at' => $data['assigned_at'] ?? now()->toDateString(),
                'assigned_by' => $actor->id,
                'note' => $data['note'] ?? null,
            ]);

            $asset->status = 'assigned';
            $asset->assigned_to = max(0, $employee->id);
            $asset->save();

            $this->audit->write(
                action: 'asset.assigned',
                subject: $asset,
                payload: [
                    'assignment_id' => $assignment->id,
                    'employee_id' => $employee->id,
                ],
            );

            AssetAssigned::dispatch($assignment);

            return $assignment;
        });
    }

    /**
     * @param  array{returned_at?: string, condition?: string|null, note?: string|null}  $data
     */
    public function returnAsset(Asset $asset, array $data): AssetAssignment
    {
        $this->assertCompanyScope($asset->company_id);

        if ($asset->status !== 'assigned') {
            throw new DomainException(
                message: 'Asset is not currently assigned.',
                errorCode: 'ASSET_NOT_AVAILABLE',
                status: 422,
            );
        }

        $assignment = AssetAssignment::query()
            ->where('asset_id', $asset->id)
            ->where('status', 'active')
            ->latest('id')
            ->first();

        if ($assignment === null) {
            throw new DomainException(
                message: 'No active assignment found for this asset.',
                errorCode: 'ASSET_INVALID_STATUS',
                status: 422,
            );
        }

        return DB::transaction(function () use ($asset, $assignment, $data): AssetAssignment {
            $assignment->status = 'returned';
            $assignment->returned_at = CarbonImmutable::parse($data['returned_at'] ?? now()->toDateString());
            $assignment->return_condition = $data['condition'] ?? null;
            if (! empty($data['note'])) {
                $assignment->note = $data['note'];
            }
            $assignment->save();

            $asset->status = 'available';
            $asset->assigned_to = null;
            $asset->save();

            $this->audit->write(
                action: 'asset.returned',
                subject: $asset,
                payload: [
                    'assignment_id' => $assignment->id,
                    'employee_id' => $assignment->employee_id,
                    'condition' => $assignment->return_condition,
                ],
            );

            AssetReturned::dispatch($assignment);

            return $assignment->fresh();
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, AssetAssignment>
     */
    public function listAssignments(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = AssetAssignment::query()
            ->where('company_id', $this->companyContext->id())
            ->orderByDesc('id');

        if (! empty($filters['asset_id'])) {
            $query->where('asset_id', $filters['asset_id']);
        }

        if (! empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->paginate($perPage);
    }

    /**
     * @return Collection<int, AssetMaintenance>
     */
    public function listMaintenances(Asset $asset): Collection
    {
        $this->assertCompanyScope($asset->company_id);

        return $asset->maintenances()->orderByDesc('id')->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function addMaintenance(Asset $asset, array $data): AssetMaintenance
    {
        $this->assertCompanyScope($asset->company_id);

        $maintenance = $asset->maintenances()->create([
            ...$data,
            'company_id' => $asset->company_id,
            'status' => $data['status'] ?? 'scheduled',
        ]);

        $this->audit->write(
            action: 'asset.maintenance_added',
            subject: $asset,
            payload: ['maintenance_id' => $maintenance->id],
        );

        return $maintenance;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function reportDamage(Asset $asset, array $data, User $actor): AssetDamageReport
    {
        $this->assertCompanyScope($asset->company_id);

        $report = $asset->damageReports()->create([
            'company_id' => $asset->company_id,
            'description' => $data['description'],
            'reported_at' => $data['reported_at'] ?? now()->toDateString(),
            'reported_by' => $actor->id,
            'document_ids' => $data['document_ids'] ?? null,
        ]);

        $this->audit->write(
            action: 'asset.damage_reported',
            subject: $asset,
            payload: ['damage_report_id' => $report->id],
        );

        return $report;
    }

    /**
     * Thin replacement flow: retire the current asset and record the intent.
     *
     * @param  array{note?: string|null}  $data
     */
    public function replace(Asset $asset, array $data): Asset
    {
        $this->assertCompanyScope($asset->company_id);

        return DB::transaction(function () use ($asset, $data): Asset {
            if ($asset->status === 'assigned') {
                $assignment = AssetAssignment::query()
                    ->where('asset_id', $asset->id)
                    ->where('status', 'active')
                    ->latest('id')
                    ->first();

                if ($assignment !== null) {
                    $assignment->status = 'returned';
                    $assignment->returned_at = CarbonImmutable::now();
                    $assignment->return_condition = 'replaced';
                    $assignment->save();
                }

                $asset->assigned_to = null;
            }

            $asset->status = 'retired';
            if (! empty($data['note'])) {
                $asset->notes = $data['note'];
            }
            $asset->save();

            $this->audit->write(
                action: 'asset.replaced',
                subject: $asset,
                payload: ['code' => $asset->code],
            );

            return $asset->fresh('assignee');
        });
    }

    private function assertCompanyScope(int $companyId): void
    {
        if ($companyId !== $this->companyContext->id()) {
            throw new DomainException(
                message: 'Resource is outside the current company scope.',
                errorCode: 'COMPANY_SCOPE_MISMATCH',
                status: 404,
            );
        }
    }

    private function isUniqueViolation(QueryException $e): bool
    {
        return str_contains(strtolower($e->getMessage()), 'unique')
            || (string) $e->getCode() === '23000';
    }
}
