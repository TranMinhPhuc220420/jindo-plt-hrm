<?php

namespace App\Services\Attendance;

use App\Exceptions\DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceEvidenceStorage
{
    public const DISK = 'local';

    private const MAX_BYTES = 5_242_880;

    /** @var list<string> */
    private const ALLOWED_MIMES = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    public function store(
        int $companyId,
        int $recordId,
        string $punchType,
        UploadedFile $photo,
    ): array {
        $this->assertValidImage($photo);

        $extension = strtolower($photo->getClientOriginalExtension() ?: $photo->extension() ?: 'jpg');
        if (! in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            $extension = match ($photo->getMimeType()) {
                'image/png' => 'png',
                'image/webp' => 'webp',
                default => 'jpg',
            };
        }

        if ($extension === 'jpeg') {
            $extension = 'jpg';
        }

        $path = sprintf(
            'attendance/%d/%d/%s_%s.%s',
            $companyId,
            $recordId,
            $punchType,
            (string) Str::uuid(),
            $extension,
        );

        Storage::disk(self::DISK)->put($path, $photo->getContent());

        return [
            'photo_path' => $path,
            'photo_mime' => $photo->getMimeType(),
            'photo_size' => $photo->getSize() ?: null,
        ];
    }

    public function delete(?string $path): void
    {
        if ($path === null || $path === '') {
            return;
        }

        Storage::disk(self::DISK)->delete($path);
    }

    public function stream(string $path, ?string $mime = null): StreamedResponse
    {
        if (! Storage::disk(self::DISK)->exists($path)) {
            throw new DomainException(
                message: 'Evidence photo not found.',
                errorCode: 'NOT_FOUND',
                status: 404,
            );
        }

        $headers = [];
        if (is_string($mime) && $mime !== '') {
            $headers['Content-Type'] = $mime;
        }

        return Storage::disk(self::DISK)->response(
            $path,
            basename($path),
            $headers,
            'inline',
        );
    }

    private function assertValidImage(UploadedFile $file): void
    {
        if (! $file->isValid()) {
            throw new DomainException(
                message: 'Attendance photo upload failed.',
                errorCode: 'ATTENDANCE_EVIDENCE_REQUIRED',
                status: 422,
            );
        }

        if ($file->getSize() > self::MAX_BYTES) {
            throw new DomainException(
                message: 'Attendance photo must be 5MB or smaller.',
                errorCode: 'ATTENDANCE_EVIDENCE_REQUIRED',
                status: 422,
            );
        }

        $mime = $file->getMimeType();
        if ($mime === null || ! in_array($mime, self::ALLOWED_MIMES, true)) {
            throw new DomainException(
                message: 'Attendance photo must be a JPEG, PNG, or WebP image.',
                errorCode: 'ATTENDANCE_EVIDENCE_REQUIRED',
                status: 422,
            );
        }
    }
}
