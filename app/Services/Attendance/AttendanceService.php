<?php

namespace App\Services\Attendance;

use App\Events\AttendanceCorrectionApproved;
use App\Events\AttendanceCorrectionRejected;
use App\Events\AttendanceCorrectionRequested;
use App\Exceptions\DomainException;
use App\Models\AttendanceCorrection;
use App\Models\AttendanceEvidence;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\Setting;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Organization\CompanyContext;
use App\Support\SettingsDefaults;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceService
{
    public function __construct(
        private readonly CompanyContext $companyContext,
        private readonly AuditLogger $audit,
        private readonly AttendanceMetricsCalculator $metrics,
        private readonly AttendanceEvidenceStorage $evidenceStorage,
    ) {}

    /**
     * @param  array{
     *     worked_at?: string,
     *     note?: string,
     *     source?: string,
     *     latitude: float|int|string,
     *     longitude: float|int|string,
     *     accuracy_meters?: float|int|string|null,
     *     address: string,
     *     captured_at?: string|null,
     *     photo: UploadedFile
     * }  $data
     */
    public function checkIn(array $data): AttendanceRecord
    {
        $this->assertEvidencePresent($data);
        $employee = $this->requireLinkedEmployee();
        $companyId = $this->companyContext->id();
        $workedAt = $this->parseWorkedAt($data['worked_at'] ?? null);
        $workDate = $workedAt->timezone($this->companyTimezone())->toDateString();

        $existing = AttendanceRecord::query()
            ->where('company_id', $companyId)
            ->where('employee_id', $employee->id)
            ->whereDate('work_date', $workDate)
            ->first();

        if ($existing !== null) {
            if (in_array($existing->status, ['locked', 'approved'], true)) {
                throw new DomainException(
                    message: 'Attendance period is locked for this date.',
                    errorCode: 'ATTENDANCE_PERIOD_LOCKED',
                    status: 409,
                );
            }

            if ($existing->check_in_at !== null) {
                throw new DomainException(
                    message: 'Already checked in for this work date.',
                    errorCode: 'ATTENDANCE_ALREADY_CHECKED_IN',
                    status: 409,
                );
            }
        }

        $storedPath = null;

        try {
            $record = DB::transaction(function () use (
                $data,
                $existing,
                $companyId,
                $employee,
                $workDate,
                $workedAt,
                &$storedPath,
            ): AttendanceRecord {
                $record = $existing ?? new AttendanceRecord([
                    'company_id' => $companyId,
                    'employee_id' => $employee->id,
                    'work_date' => $workDate,
                    'source' => $data['source'] ?? 'manual',
                    'status' => 'open',
                ]);

                $record->check_in_at = $workedAt;
                $record->note = $data['note'] ?? $record->note;
                $record->source = $data['source'] ?? $record->source ?? 'manual';
                $record->status = 'open';
                $this->applyMetrics($record);
                $record->save();

                $stored = $this->evidenceStorage->store(
                    $companyId,
                    $record->id,
                    AttendanceEvidence::PUNCH_CHECK_IN,
                    $data['photo'],
                );
                $storedPath = $stored['photo_path'];

                $evidence = $this->createEvidence(
                    $record,
                    AttendanceEvidence::PUNCH_CHECK_IN,
                    $data,
                    $stored,
                );

                $this->audit->write(
                    action: 'attendance.checked_in',
                    subject: $record,
                    payload: [
                        'work_date' => $workDate,
                        'employee_id' => $employee->id,
                        'evidence_id' => $evidence->id,
                    ],
                );

                return $record;
            });
        } catch (\Throwable $e) {
            $this->evidenceStorage->delete($storedPath);

            throw $e;
        }

        return $record->fresh(['employee', 'evidences']) ?? $record;
    }

    /**
     * @param  array{
     *     worked_at?: string,
     *     note?: string,
     *     latitude: float|int|string,
     *     longitude: float|int|string,
     *     accuracy_meters?: float|int|string|null,
     *     address: string,
     *     captured_at?: string|null,
     *     photo: UploadedFile
     * }  $data
     */
    public function checkOut(array $data): AttendanceRecord
    {
        $this->assertEvidencePresent($data);
        $employee = $this->requireLinkedEmployee();
        $companyId = $this->companyContext->id();
        $workedAt = $this->resolvePunchAt($data);
        $punchDate = $workedAt->timezone($this->companyTimezone())->toDateString();

        $record = $this->findCheckOutTargetRecord(
            companyId: $companyId,
            employeeId: $employee->id,
            punchDate: $punchDate,
        );

        if ($record === null || $record->check_in_at === null) {
            throw new DomainException(
                message: 'No open check-in found for this work date.',
                errorCode: 'ATTENDANCE_INVALID_TRANSITION',
                status: 422,
            );
        }

        if (in_array($record->status, ['locked', 'approved'], true)) {
            throw new DomainException(
                message: 'Attendance period is locked for this date.',
                errorCode: 'ATTENDANCE_PERIOD_LOCKED',
                status: 409,
            );
        }

        if ($record->check_out_at !== null && $record->status !== 'open') {
            throw new DomainException(
                message: 'Already checked out for this work date.',
                errorCode: 'ATTENDANCE_INVALID_TRANSITION',
                status: 422,
            );
        }

        $workDate = $record->work_date->format('Y-m-d');
        $storedPath = null;

        try {
            $record = DB::transaction(function () use (
                $data,
                $record,
                $workedAt,
                $workDate,
                $employee,
                &$storedPath,
            ): AttendanceRecord {
                $record->check_out_at = $workedAt;
                if (isset($data['note'])) {
                    $record->note = $data['note'];
                }
                $record->status = 'pending';
                $this->applyMetrics($record);
                $record->save();

                $stored = $this->evidenceStorage->store(
                    $record->company_id,
                    $record->id,
                    AttendanceEvidence::PUNCH_CHECK_OUT,
                    $data['photo'],
                );
                $storedPath = $stored['photo_path'];

                $evidence = $this->createEvidence(
                    $record,
                    AttendanceEvidence::PUNCH_CHECK_OUT,
                    $data,
                    $stored,
                );

                $this->audit->write(
                    action: 'attendance.checked_out',
                    subject: $record,
                    payload: [
                        'work_date' => $workDate,
                        'employee_id' => $employee->id,
                        'evidence_id' => $evidence->id,
                    ],
                );

                return $record;
            });
        } catch (\Throwable $e) {
            $this->evidenceStorage->delete($storedPath);

            throw $e;
        }

        return $record->fresh(['employee', 'evidences']) ?? $record;
    }

    public function streamEvidencePhoto(User $actor, AttendanceRecord $record, string $punchType): StreamedResponse
    {
        $this->assertCanViewRecord($actor, $record);

        if (! in_array($punchType, AttendanceEvidence::PUNCH_TYPES, true)) {
            throw new DomainException(
                message: 'Invalid punch type.',
                errorCode: 'NOT_FOUND',
                status: 404,
            );
        }

        $evidence = AttendanceEvidence::query()
            ->where('attendance_record_id', $record->id)
            ->where('company_id', $this->companyContext->id())
            ->where('punch_type', $punchType)
            ->first();

        if ($evidence === null) {
            throw new DomainException(
                message: 'Evidence photo not found.',
                errorCode: 'NOT_FOUND',
                status: 404,
            );
        }

        return $this->evidenceStorage->stream(
            $evidence->photo_path,
            $evidence->photo_mime,
        );
    }

    /**
     * @param  array{employee_id?: int, date_from?: string, date_to?: string, status?: string}  $filters
     * @return LengthAwarePaginator<int, AttendanceRecord>
     */
    public function listRecords(User $actor, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $companyId = $this->companyContext->id();
        $query = AttendanceRecord::query()
            ->where('company_id', $companyId)
            ->with(['employee', 'evidences'])
            ->orderByDesc('work_date');

        $ownOnly = $this->mustScopeToOwn($actor);
        if ($ownOnly) {
            $employee = $this->requireLinkedEmployee($actor);
            $query->where('employee_id', $employee->id);
        } elseif (! empty($filters['employee_id'])) {
            $query->where('employee_id', (int) $filters['employee_id']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('work_date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('work_date', '<=', $filters['date_to']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->paginate($perPage);
    }

    public function findRecord(User $actor, int $id): AttendanceRecord
    {
        $record = AttendanceRecord::query()
            ->where('company_id', $this->companyContext->id())
            ->with(['employee', 'evidences'])
            ->findOrFail($id);

        $this->assertCanViewRecord($actor, $record);

        return $record;
    }

    public function approveRecord(User $actor, AttendanceRecord $record): AttendanceRecord
    {
        $this->assertCompanyScope($record->company_id);

        if ($record->status !== 'pending') {
            throw new DomainException(
                message: 'Only pending attendance records can be approved.',
                errorCode: 'ATTENDANCE_INVALID_TRANSITION',
                status: 422,
            );
        }

        $this->markRecordApproved($actor, $record);

        return $record->fresh(['employee', 'evidences']);
    }

    /**
     * @param  list<int>  $ids
     * @return array{approved_count: int, approved_ids: list<int>, skipped_ids: list<int>}
     */
    public function approveRecords(User $actor, array $ids): array
    {
        $companyId = $this->companyContext->id();
        $uniqueIds = array_values(array_unique(array_map('intval', $ids)));

        $records = AttendanceRecord::query()
            ->where('company_id', $companyId)
            ->whereIn('id', $uniqueIds)
            ->get()
            ->keyBy('id');

        $approvedIds = [];
        $skippedIds = [];

        DB::transaction(function () use ($actor, $uniqueIds, $records, &$approvedIds, &$skippedIds): void {
            foreach ($uniqueIds as $id) {
                $record = $records->get($id);

                if ($record === null || $record->status !== 'pending') {
                    $skippedIds[] = $id;

                    continue;
                }

                $this->markRecordApproved($actor, $record);
                $approvedIds[] = $id;
            }
        });

        if ($approvedIds === []) {
            throw new DomainException(
                message: 'Only pending attendance records can be approved.',
                errorCode: 'ATTENDANCE_INVALID_TRANSITION',
                status: 422,
            );
        }

        return [
            'approved_count' => count($approvedIds),
            'approved_ids' => $approvedIds,
            'skipped_ids' => $skippedIds,
        ];
    }

    private function markRecordApproved(User $actor, AttendanceRecord $record): void
    {
        $record->status = 'approved';
        $record->approved_by = max(0, $actor->id);
        $record->approved_at = now();
        $record->save();

        $this->audit->write(
            action: 'attendance.record_approved',
            subject: $record,
            payload: [
                'employee_id' => $record->employee_id,
                'work_date' => $record->work_date->toDateString(),
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return int Number of records locked
     */
    public function lockPeriod(array $data): int
    {
        $companyId = $this->companyContext->id();

        $count = AttendanceRecord::query()
            ->where('company_id', $companyId)
            ->whereDate('work_date', '>=', $data['date_from'])
            ->whereDate('work_date', '<=', $data['date_to'])
            ->where('status', '!=', 'locked')
            ->update(['status' => 'locked']);

        $this->audit->write(
            action: 'attendance.period_locked',
            subject: null,
            payload: [
                'date_from' => $data['date_from'],
                'date_to' => $data['date_to'],
                'count' => $count,
            ],
        );

        return $count;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function requestCorrection(User $actor, array $data): AttendanceCorrection
    {
        $record = AttendanceRecord::query()
            ->where('company_id', $this->companyContext->id())
            ->findOrFail((int) $data['attendance_record_id']);

        if ($record->status === 'locked') {
            throw new DomainException(
                message: 'Cannot correct a locked attendance record.',
                errorCode: 'ATTENDANCE_PERIOD_LOCKED',
                status: 409,
            );
        }

        $linked = Employee::query()->where('user_id', $actor->id)->first();
        $canManage = $actor->can('can_manage_attendance');

        if (! $canManage && ($linked === null || $linked->id !== $record->employee_id)) {
            throw new DomainException(
                message: 'You may only correct your own attendance.',
                errorCode: 'FORBIDDEN',
                status: 403,
            );
        }

        $pendingExists = AttendanceCorrection::query()
            ->where('attendance_record_id', $record->id)
            ->where('status', 'pending')
            ->exists();

        if ($pendingExists) {
            throw new DomainException(
                message: 'A pending correction already exists for this record.',
                errorCode: 'ATTENDANCE_INVALID_TRANSITION',
                status: 422,
            );
        }

        $correction = AttendanceCorrection::query()->create([
            'company_id' => $record->company_id,
            'attendance_record_id' => $record->id,
            'employee_id' => $record->employee_id,
            'proposed_check_in_at' => isset($data['proposed_check_in_at'])
                ? $this->parseWorkedAt($data['proposed_check_in_at'])
                : null,
            'proposed_check_out_at' => isset($data['proposed_check_out_at'])
                ? $this->parseWorkedAt($data['proposed_check_out_at'])
                : null,
            'reason' => $data['reason'],
            'status' => 'pending',
        ]);

        $this->audit->write(
            action: 'attendance.correction_requested',
            subject: $correction,
            payload: ['attendance_record_id' => $record->id],
        );

        AttendanceCorrectionRequested::dispatch($correction);

        return $correction->fresh(['record', 'employee']);
    }

    /**
     * @param  array{status?: string, employee_id?: int}  $filters
     * @return LengthAwarePaginator<int, AttendanceCorrection>
     */
    public function listCorrections(User $actor, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = AttendanceCorrection::query()
            ->where('company_id', $this->companyContext->id())
            ->with(['record', 'employee'])
            ->orderByDesc('id');

        if ($this->mustScopeToOwn($actor)) {
            $employee = $this->requireLinkedEmployee($actor);
            $query->where('employee_id', $employee->id);
        } elseif (! empty($filters['employee_id'])) {
            $query->where('employee_id', (int) $filters['employee_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->paginate($perPage);
    }

    public function approveCorrection(User $actor, AttendanceCorrection $correction): AttendanceCorrection
    {
        $this->assertCompanyScope($correction->company_id);

        if ($correction->status !== 'pending') {
            throw new DomainException(
                message: 'Only pending corrections can be approved.',
                errorCode: 'ATTENDANCE_INVALID_TRANSITION',
                status: 422,
            );
        }

        $record = $correction->record()->firstOrFail();

        if ($record->status === 'locked') {
            throw new DomainException(
                message: 'Cannot apply correction to a locked record.',
                errorCode: 'ATTENDANCE_PERIOD_LOCKED',
                status: 409,
            );
        }

        if ($correction->proposed_check_in_at !== null) {
            $record->check_in_at = $correction->proposed_check_in_at;
        }

        if ($correction->proposed_check_out_at !== null) {
            $record->check_out_at = $correction->proposed_check_out_at;
        }

        $record->status = 'pending';
        $this->applyMetrics($record);
        $record->save();

        $correction->status = 'approved';
        $correction->reviewed_by = max(0, $actor->id);
        $correction->reviewed_at = now();
        $correction->save();

        $this->audit->write(
            action: 'attendance.correction_approved',
            subject: $correction,
            payload: ['attendance_record_id' => $record->id],
        );

        AttendanceCorrectionApproved::dispatch($correction);

        return $correction->fresh(['record', 'employee']);
    }

    public function rejectCorrection(User $actor, AttendanceCorrection $correction, ?string $note = null): AttendanceCorrection
    {
        $this->assertCompanyScope($correction->company_id);

        if ($correction->status !== 'pending') {
            throw new DomainException(
                message: 'Only pending corrections can be rejected.',
                errorCode: 'ATTENDANCE_INVALID_TRANSITION',
                status: 422,
            );
        }

        $correction->status = 'rejected';
        $correction->reviewed_by = max(0, $actor->id);
        $correction->reviewed_at = now();
        $correction->review_note = $note;
        $correction->save();

        $this->audit->write(
            action: 'attendance.correction_rejected',
            subject: $correction,
            payload: ['attendance_record_id' => $correction->attendance_record_id],
        );

        AttendanceCorrectionRejected::dispatch($correction);

        return $correction->fresh(['record', 'employee']);
    }

    public function findCorrection(int $id): AttendanceCorrection
    {
        return AttendanceCorrection::query()
            ->where('company_id', $this->companyContext->id())
            ->with(['record', 'employee'])
            ->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function assertEvidencePresent(array $data): void
    {
        $photo = $data['photo'] ?? null;
        $hasLocation = isset($data['latitude'], $data['longitude'], $data['address'])
            && $data['address'] !== '';

        if (! $hasLocation || ! ($photo instanceof UploadedFile)) {
            throw new DomainException(
                message: 'Location and camera photo are required to record attendance.',
                errorCode: 'ATTENDANCE_EVIDENCE_REQUIRED',
                status: 422,
            );
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array{photo_path: string, photo_mime: ?string, photo_size: ?int}  $stored
     */
    private function createEvidence(
        AttendanceRecord $record,
        string $punchType,
        array $data,
        array $stored,
    ): AttendanceEvidence {
        $capturedAt = null;
        if (! empty($data['captured_at']) && is_string($data['captured_at'])) {
            $capturedAt = $this->parseWorkedAt($data['captured_at']);
        }

        return AttendanceEvidence::query()->create([
            'company_id' => $record->company_id,
            'attendance_record_id' => $record->id,
            'punch_type' => $punchType,
            'latitude' => (float) $data['latitude'],
            'longitude' => (float) $data['longitude'],
            'accuracy_meters' => isset($data['accuracy_meters']) && $data['accuracy_meters'] !== ''
                ? (float) $data['accuracy_meters']
                : null,
            'address' => (string) $data['address'],
            'photo_path' => $stored['photo_path'],
            'photo_mime' => $stored['photo_mime'],
            'photo_size' => $stored['photo_size'],
            'captured_at' => $capturedAt ?? now(),
        ]);
    }

    private function applyMetrics(AttendanceRecord $record): void
    {
        $metrics = $this->metrics->compute(
            employeeId: $record->employee_id,
            workDate: $record->work_date->format('Y-m-d'),
            checkInAt: $record->check_in_at
                ? CarbonImmutable::parse($record->check_in_at)
                : null,
            checkOutAt: $record->check_out_at
                ? CarbonImmutable::parse($record->check_out_at)
                : null,
            breakMinutesOverride: $record->break_minutes > 0 ? $record->break_minutes : null,
        );

        $record->fill($metrics);
    }

    /**
     * Resolve punch instant: worked_at → captured_at → now (company TZ).
     *
     * @param  array{worked_at?: string|null, captured_at?: string|null}  $data
     */
    private function resolvePunchAt(array $data): CarbonImmutable
    {
        foreach (['worked_at', 'captured_at'] as $key) {
            $value = $data[$key] ?? null;

            if (is_string($value) && trim($value) !== '') {
                return $this->parseWorkedAt($value);
            }
        }

        return $this->parseWorkedAt(null);
    }

    /**
     * Prefer same calendar day; otherwise the latest still-open session within
     * a one-day lookback (post-midnight / overnight checkout).
     */
    private function findCheckOutTargetRecord(
        int $companyId,
        int $employeeId,
        string $punchDate,
    ): ?AttendanceRecord {
        $sameDay = AttendanceRecord::query()
            ->where('company_id', $companyId)
            ->where('employee_id', $employeeId)
            ->whereDate('work_date', $punchDate)
            ->first();

        if ($sameDay !== null && $sameDay->check_in_at !== null) {
            return $sameDay;
        }

        $windowStart = CarbonImmutable::parse($punchDate, $this->companyTimezone())
            ->subDay()
            ->toDateString();

        return AttendanceRecord::query()
            ->where('company_id', $companyId)
            ->where('employee_id', $employeeId)
            ->whereNotNull('check_in_at')
            ->whereNull('check_out_at')
            ->whereNotIn('status', ['locked', 'approved'])
            ->whereDate('work_date', '>=', $windowStart)
            ->whereDate('work_date', '<=', $punchDate)
            ->orderByDesc('check_in_at')
            ->first();
    }

    private function parseWorkedAt(?string $value): CarbonImmutable
    {
        $companyTz = $this->companyTimezone();

        if ($value === null || $value === '') {
            return CarbonImmutable::now($companyTz)->utc();
        }

        $trimmed = trim($value);

        // Normalize to UTC instants so Eloquent datetime casts (app TZ = UTC)
        // persist the correct absolute time, not the wall-clock digits alone.
        if ($this->hasTimezoneDesignator($trimmed)) {
            return CarbonImmutable::parse($trimmed)->utc();
        }

        // Naive datetime-local (e.g. 2026-07-16T08:00) → company wall clock → UTC.
        return CarbonImmutable::parse($trimmed, $companyTz)->utc();
    }

    private function hasTimezoneDesignator(string $value): bool
    {
        return (bool) preg_match('/(?:Z|[+-]\d{2}:?\d{2})$/i', $value);
    }

    private function companyTimezone(): string
    {
        $stored = Setting::query()
            ->where('company_id', $this->companyContext->id())
            ->where('group', 'company')
            ->where('key', 'timezone')
            ->value('value');

        if (is_string($stored) && $stored !== '') {
            return $stored;
        }

        return (string) (SettingsDefaults::all()['company']['timezone'] ?? 'UTC');
    }

    private function requireLinkedEmployee(?User $user = null): Employee
    {
        $user ??= Auth::user();

        if ($user === null) {
            throw new DomainException(
                message: 'Authentication required.',
                errorCode: 'FORBIDDEN',
                status: 403,
            );
        }

        $employee = Employee::query()
            ->where('company_id', $this->companyContext->id())
            ->where('user_id', $user->id)
            ->first();

        if ($employee === null) {
            throw new DomainException(
                message: 'Your account is not linked to an employee record.',
                errorCode: 'FORBIDDEN',
                status: 403,
            );
        }

        return $employee;
    }

    private function mustScopeToOwn(User $actor): bool
    {
        return ! $actor->can('can_approve_attendance')
            && ! $actor->can('can_manage_attendance');
    }

    private function assertCanViewRecord(User $actor, AttendanceRecord $record): void
    {
        if (! $this->mustScopeToOwn($actor)) {
            return;
        }

        $employee = $this->requireLinkedEmployee($actor);

        if ($employee->id !== $record->employee_id) {
            throw new DomainException(
                message: 'You may only view your own attendance.',
                errorCode: 'FORBIDDEN',
                status: 403,
            );
        }
    }

    private function assertCompanyScope(int $companyId): void
    {
        if ($companyId !== $this->companyContext->id()) {
            throw new DomainException(
                message: 'Resource does not belong to the current company.',
                errorCode: 'COMPANY_SCOPE_MISMATCH',
                status: 403,
            );
        }
    }
}
