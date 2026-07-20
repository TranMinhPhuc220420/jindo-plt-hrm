<?php

namespace App\Http\Controllers\Api\Document;

use App\Http\Controllers\Controller;
use App\Http\Requests\Document\UpdateDocumentRequest;
use App\Http\Requests\Document\UploadDocumentRequest;
use App\Http\Resources\DocumentResource;
use App\Models\Document;
use App\Services\Document\DocumentService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function __construct(
        private readonly DocumentService $documents,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Document::class);

        $paginator = $this->documents->list(
            filters: $request->only(['owner_type', 'owner_id', 'category']),
            perPage: min((int) $request->integer('per_page', 20), 100),
        );

        return ApiResponse::paginated(
            $paginator->through(fn (Document $document) => (new DocumentResource($document))->resolve()),
        );
    }

    public function store(UploadDocumentRequest $request): JsonResponse
    {
        $ownerType = (string) $request->input('owner_type');
        $ownerId = $request->integer('owner_id') ?: null;

        $this->authorize('create', [Document::class, $ownerType, $ownerId]);

        $document = $this->documents->upload($request->file('file'), $request->validated());

        return ApiResponse::created(
            (new DocumentResource($document))->resolve(),
            'Document uploaded.',
        );
    }

    public function show(int $document): JsonResponse
    {
        $model = $this->documents->find($document);
        $this->authorize('view', $model);

        return ApiResponse::success(
            (new DocumentResource($model))->resolve(),
        );
    }

    public function download(int $document): StreamedResponse
    {
        $model = $this->documents->find($document);
        $this->authorize('view', $model);

        return $this->documents->download($model);
    }

    public function update(UpdateDocumentRequest $request, int $document): JsonResponse
    {
        $model = $this->documents->find($document);
        $this->authorize('update', $model);

        $model = $this->documents->update($model, $request->validated());

        return ApiResponse::success(
            (new DocumentResource($model))->resolve(),
            'Document updated.',
        );
    }

    public function destroy(int $document): JsonResponse
    {
        $model = $this->documents->find($document);
        $this->authorize('delete', $model);

        $this->documents->delete($model);

        return ApiResponse::success(null, 'Document deleted.');
    }
}
