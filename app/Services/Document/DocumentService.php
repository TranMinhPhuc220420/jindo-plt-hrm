<?php

namespace App\Services\Document;

use App\Events\DocumentUploaded;
use App\Exceptions\DomainException;
use App\Models\Candidate;
use App\Models\Document;
use App\Models\Employee;
use App\Services\Audit\AuditLogger;
use App\Services\Organization\CompanyContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentService
{
    private const DISK = 'local';

    public function __construct(
        private readonly CompanyContext $companyContext,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array{owner_type?: string, owner_id?: int, category?: string}  $filters
     * @return LengthAwarePaginator<int, Document>
     */
    public function list(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Document::query()
            ->where('company_id', $this->companyContext->id())
            ->orderByDesc('id');

        if (! empty($filters['owner_type'])) {
            $query->where('owner_type', $filters['owner_type']);
        }

        if (! empty($filters['owner_id'])) {
            $query->where('owner_id', $filters['owner_id']);
        }

        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        return $query->paginate($perPage);
    }

    public function find(int $id): Document
    {
        $document = Document::query()
            ->where('company_id', $this->companyContext->id())
            ->find($id);

        if ($document === null) {
            throw new DomainException(
                message: 'Document not found.',
                errorCode: 'NOT_FOUND',
                status: 404,
            );
        }

        return $document;
    }

    /**
     * @param  array{owner_type: string, owner_id?: int|null, category?: string, title?: string|null}  $data
     */
    public function upload(UploadedFile $file, array $data): Document
    {
        $companyId = $this->companyContext->id();
        $ownerType = $data['owner_type'];
        $ownerId = $data['owner_id'] ?? null;

        $this->assertOwner($companyId, $ownerType, $ownerId);

        $path = $file->store('documents/'.$companyId, self::DISK);

        $document = Document::query()->create([
            'company_id' => $companyId,
            'owner_type' => $ownerType,
            'owner_id' => $ownerType === 'company' ? null : $ownerId,
            'category' => $data['category'] ?? 'other',
            'title' => $data['title'] ?? $file->getClientOriginalName(),
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'uploaded_by' => Auth::id(),
        ]);

        $this->audit->write(
            action: 'document.uploaded',
            subject: $document,
            payload: ['owner_type' => $ownerType, 'owner_id' => $ownerId, 'category' => $document->category],
        );

        DocumentUploaded::dispatch($document);

        return $document;
    }

    /**
     * @param  array{category?: string, title?: string}  $data
     */
    public function update(Document $document, array $data): Document
    {
        $this->assertCompanyScope($document->company_id);

        $document->fill(array_filter([
            'category' => $data['category'] ?? null,
            'title' => $data['title'] ?? null,
        ], fn ($value) => $value !== null));
        $document->save();

        $this->audit->write(
            action: 'document.updated',
            subject: $document,
            payload: ['category' => $document->category, 'title' => $document->title],
        );

        return $document->fresh();
    }

    public function delete(Document $document): void
    {
        $this->assertCompanyScope($document->company_id);

        $document->delete();

        $this->audit->write(
            action: 'document.deleted',
            subject: $document,
            payload: ['owner_type' => $document->owner_type, 'owner_id' => $document->owner_id],
        );
    }

    public function download(Document $document): StreamedResponse
    {
        $this->assertCompanyScope($document->company_id);

        $disk = Storage::disk(self::DISK);

        if (! $disk->exists($document->file_path)) {
            throw new DomainException(
                message: 'Document file is missing from storage.',
                errorCode: 'NOT_FOUND',
                status: 404,
            );
        }

        return $disk->download($document->file_path, $document->original_name);
    }

    private function assertOwner(int $companyId, string $ownerType, ?int $ownerId): void
    {
        if (! in_array($ownerType, Document::OWNER_TYPES, true)) {
            throw new DomainException(
                message: 'Unsupported document owner type.',
                errorCode: 'VALIDATION_FAILED',
                status: 422,
            );
        }

        if ($ownerType === 'company') {
            return;
        }

        if ($ownerId === null) {
            throw new DomainException(
                message: 'owner_id is required for this owner type.',
                errorCode: 'VALIDATION_FAILED',
                status: 422,
            );
        }

        $model = $ownerType === 'employee' ? Employee::class : Candidate::class;

        $exists = $model::query()
            ->whereKey($ownerId)
            ->where('company_id', $companyId)
            ->exists();

        if (! $exists) {
            throw new DomainException(
                message: 'Document owner is outside the current company scope.',
                errorCode: 'COMPANY_SCOPE_MISMATCH',
                status: 404,
            );
        }
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
}
