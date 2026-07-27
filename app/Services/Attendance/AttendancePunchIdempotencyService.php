<?php

namespace App\Services\Attendance;

use App\Exceptions\DomainException;
use App\Http\Resources\AttendanceRecordResource;
use App\Models\AttendanceEvidence;
use App\Models\AttendancePunchIdempotency;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Services\Organization\CompanyContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AttendancePunchIdempotencyService
{
    public const TTL_HOURS = 48;

    public function __construct(
        private readonly AttendanceService $attendance,
        private readonly CompanyContext $companyContext,
    ) {}

    /**
     * @param  array{
     *     worked_at?: string|null,
     *     note?: string|null,
     *     source?: string|null,
     *     latitude: float|int|string,
     *     longitude: float|int|string,
     *     accuracy_meters?: float|int|string|null,
     *     address: string,
     *     captured_at?: string|null,
     *     photo: UploadedFile
     * }  $data
     */
    public function checkIn(?string $idempotencyKey, array $data): JsonResponse
    {
        return $this->run(
            $idempotencyKey,
            AttendanceEvidence::PUNCH_CHECK_IN,
            $data,
            function () use ($data): array {
                $record = $this->attendance->checkIn($data);

                return [
                    'status' => 201,
                    'body' => [
                        'success' => true,
                        'data' => (new AttendanceRecordResource(
                            $record->fresh(['employee', 'evidences']) ?? $record,
                        ))->resolve(),
                        'message' => 'Checked in.',
                    ],
                    'record' => $record,
                ];
            },
        );
    }

    /**
     * @param  array{
     *     worked_at?: string|null,
     *     note?: string|null,
     *     latitude: float|int|string,
     *     longitude: float|int|string,
     *     accuracy_meters?: float|int|string|null,
     *     address: string,
     *     captured_at?: string|null,
     *     photo: UploadedFile
     * }  $data
     */
    public function checkOut(?string $idempotencyKey, array $data): JsonResponse
    {
        return $this->run(
            $idempotencyKey,
            AttendanceEvidence::PUNCH_CHECK_OUT,
            $data,
            function () use ($data): array {
                $record = $this->attendance->checkOut($data);

                return [
                    'status' => 200,
                    'body' => [
                        'success' => true,
                        'data' => (new AttendanceRecordResource(
                            $record->fresh(['employee', 'evidences']) ?? $record,
                        ))->resolve(),
                        'message' => 'Checked out.',
                    ],
                    'record' => $record,
                ];
            },
        );
    }

    /**
     * @param  array{
     *     worked_at?: string|null,
     *     note?: string|null,
     *     source?: string|null,
     *     latitude: float|int|string,
     *     longitude: float|int|string,
     *     accuracy_meters?: float|int|string|null,
     *     address: string,
     *     captured_at?: string|null,
     *     photo: UploadedFile
     * }  $data
     * @param  callable(): array{status: int, body: array<string, mixed>, record: AttendanceRecord}  $execute
     */
    private function run(
        ?string $idempotencyKey,
        string $punchType,
        array $data,
        callable $execute,
    ): JsonResponse {
        $key = $this->requireValidKey($idempotencyKey);
        $employee = $this->requireLinkedEmployee();
        $companyId = $this->companyContext->id();
        $fingerprint = $this->fingerprint($punchType, $data);

        $this->purgeExpired($companyId, $employee->id);

        return DB::transaction(function () use (
            $key,
            $punchType,
            $fingerprint,
            $execute,
            $companyId,
            $employee,
        ): JsonResponse {
            $cached = AttendancePunchIdempotency::query()
                ->where('company_id', $companyId)
                ->where('employee_id', $employee->id)
                ->where('idempotency_key', $key)
                ->lockForUpdate()
                ->first();

            if ($cached !== null) {
                if ($cached->request_fingerprint !== $fingerprint) {
                    throw new DomainException(
                        message: 'Idempotency key was already used with a different request.',
                        errorCode: 'IDEMPOTENCY_KEY_REUSE',
                        status: 409,
                    );
                }

                return response()->json($cached->response_body, $cached->response_status);
            }

            $result = $execute();

            AttendancePunchIdempotency::query()->create([
                'company_id' => $companyId,
                'employee_id' => $employee->id,
                'idempotency_key' => $key,
                'punch_type' => $punchType,
                'request_fingerprint' => $fingerprint,
                'response_status' => $result['status'],
                'response_body' => $result['body'],
                'attendance_record_id' => $result['record']->id,
                'created_at' => now(),
            ]);

            return response()->json($result['body'], $result['status']);
        });
    }

    private function requireValidKey(?string $idempotencyKey): string
    {
        $key = is_string($idempotencyKey) ? trim($idempotencyKey) : '';

        if ($key === '') {
            throw new DomainException(
                message: 'Idempotency-Key header is required for attendance punches.',
                errorCode: 'IDEMPOTENCY_KEY_REQUIRED',
                status: 400,
            );
        }

        if (! Str::isUuid($key)) {
            throw new DomainException(
                message: 'Idempotency-Key must be a UUID.',
                errorCode: 'IDEMPOTENCY_KEY_INVALID',
                status: 400,
            );
        }

        return $key;
    }

    /**
     * @param  array{
     *     worked_at?: string|null,
     *     note?: string|null,
     *     source?: string|null,
     *     latitude: float|int|string,
     *     longitude: float|int|string,
     *     accuracy_meters?: float|int|string|null,
     *     address: string,
     *     captured_at?: string|null,
     *     photo: UploadedFile
     * }  $data
     */
    private function fingerprint(string $punchType, array $data): string
    {
        $photo = $data['photo'];

        $payload = [
            'punch_type' => $punchType,
            'latitude' => round((float) $data['latitude'], 6),
            'longitude' => round((float) $data['longitude'], 6),
            'address' => (string) $data['address'],
            'captured_at' => $data['captured_at'] ?? null,
            'worked_at' => $data['worked_at'] ?? null,
            'note' => $data['note'] ?? null,
            'photo_size' => $photo->getSize(),
            'photo_name' => $photo->getClientOriginalName(),
        ];

        return hash('sha256', (string) json_encode($payload));
    }

    private function purgeExpired(int $companyId, int $employeeId): void
    {
        AttendancePunchIdempotency::query()
            ->where('company_id', $companyId)
            ->where('employee_id', $employeeId)
            ->where('created_at', '<', now()->subHours(self::TTL_HOURS))
            ->delete();
    }

    private function requireLinkedEmployee(): Employee
    {
        $user = Auth::user();

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
}
