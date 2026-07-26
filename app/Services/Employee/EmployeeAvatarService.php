<?php

namespace App\Services\Employee;

use App\Exceptions\DomainException;
use App\Models\Employee;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EmployeeAvatarService
{
    private const DISK = 'public';

    private const MAX_BYTES = 2_048_000;

    /** @var list<string> */
    private const ALLOWED_MIMES = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    public function __construct(
        private readonly AuditLogger $audit,
    ) {}

    public function upload(Employee $employee, UploadedFile $file): Employee
    {
        $this->assertValidImage($file);

        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg');
        if (! in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            $extension = match ($file->getMimeType()) {
                'image/png' => 'png',
                'image/webp' => 'webp',
                default => 'jpg',
            };
        }

        $path = sprintf(
            'avatars/%d/%s.%s',
            $employee->company_id,
            (string) Str::uuid(),
            $extension === 'jpeg' ? 'jpg' : $extension,
        );

        Storage::disk(self::DISK)->put($path, $file->getContent());

        $previous = $employee->avatar_path;
        $employee->forceFill(['avatar_path' => $path])->save();

        if ($previous !== null && $previous !== $path) {
            Storage::disk(self::DISK)->delete($previous);
        }

        $this->audit->write(
            action: 'employee.avatar_updated',
            subject: $employee,
            payload: ['avatar_path' => $path],
        );

        return $employee->fresh(['department', 'position', 'branch']) ?? $employee;
    }

    public function delete(Employee $employee): Employee
    {
        $previous = $employee->avatar_path;

        if ($previous === null) {
            return $employee;
        }

        $employee->forceFill(['avatar_path' => null])->save();
        Storage::disk(self::DISK)->delete($previous);

        $this->audit->write(
            action: 'employee.avatar_deleted',
            subject: $employee,
            payload: ['avatar_path' => $previous],
        );

        return $employee->fresh(['department', 'position', 'branch']) ?? $employee;
    }

    private function assertValidImage(UploadedFile $file): void
    {
        if (! $file->isValid()) {
            throw new DomainException(
                message: 'Avatar upload failed.',
                errorCode: 'AVATAR_UPLOAD_INVALID',
                status: 422,
            );
        }

        if ($file->getSize() > self::MAX_BYTES) {
            throw new DomainException(
                message: 'Avatar must be 2MB or smaller.',
                errorCode: 'AVATAR_TOO_LARGE',
                status: 422,
            );
        }

        $mime = $file->getMimeType();
        if ($mime === null || ! in_array($mime, self::ALLOWED_MIMES, true)) {
            throw new DomainException(
                message: 'Avatar must be a JPEG, PNG, or WebP image.',
                errorCode: 'AVATAR_INVALID_TYPE',
                status: 422,
            );
        }
    }
}
