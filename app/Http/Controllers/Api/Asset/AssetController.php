<?php

namespace App\Http\Controllers\Api\Asset;

use App\Http\Controllers\Controller;
use App\Http\Requests\Asset\AssignAssetRequest;
use App\Http\Requests\Asset\ReturnAssetRequest;
use App\Http\Requests\Asset\StoreAssetDamageReportRequest;
use App\Http\Requests\Asset\StoreAssetMaintenanceRequest;
use App\Http\Requests\Asset\StoreAssetRequest;
use App\Http\Requests\Asset\UpdateAssetRequest;
use App\Http\Resources\AssetAssignmentResource;
use App\Http\Resources\AssetDamageReportResource;
use App\Http\Resources\AssetMaintenanceResource;
use App\Http\Resources\AssetResource;
use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\AssetMaintenance;
use App\Services\Asset\AssetService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssetController extends Controller
{
    public function __construct(
        private readonly AssetService $assets,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Asset::class);

        $paginator = $this->assets->list(
            filters: $request->only(['status', 'category', 'search']),
            perPage: min((int) $request->integer('per_page', 20), 100),
        );

        return ApiResponse::paginated(
            $paginator->through(fn (Asset $asset) => (new AssetResource($asset))->resolve()),
        );
    }

    public function store(StoreAssetRequest $request): JsonResponse
    {
        $this->authorize('create', Asset::class);

        $asset = $this->assets->create($request->validated());

        return ApiResponse::created(
            (new AssetResource($asset))->resolve(),
            'Asset created.',
        );
    }

    public function show(int $asset): JsonResponse
    {
        $model = $this->assets->find($asset);
        $this->authorize('view', $model);

        return ApiResponse::success(
            (new AssetResource($model))->resolve(),
        );
    }

    public function update(UpdateAssetRequest $request, int $asset): JsonResponse
    {
        $model = $this->assets->find($asset);
        $this->authorize('update', $model);

        $model = $this->assets->update($model, $request->validated());

        return ApiResponse::success(
            (new AssetResource($model))->resolve(),
            'Asset updated.',
        );
    }

    public function retire(int $asset): JsonResponse
    {
        $model = $this->assets->find($asset);
        $this->authorize('retire', $model);

        $model = $this->assets->retire($model);

        return ApiResponse::success(
            (new AssetResource($model))->resolve(),
            'Asset retired.',
        );
    }

    public function assign(AssignAssetRequest $request, int $asset): JsonResponse
    {
        $model = $this->assets->find($asset);
        $this->authorize('assign', $model);

        $assignment = $this->assets->assign($model, $request->validated(), $request->user());

        return ApiResponse::created(
            (new AssetAssignmentResource($assignment))->resolve(),
            'Asset assigned.',
        );
    }

    public function returnAsset(ReturnAssetRequest $request, int $asset): JsonResponse
    {
        $model = $this->assets->find($asset);
        $this->authorize('returnAsset', $model);

        $assignment = $this->assets->returnAsset($model, $request->validated());

        return ApiResponse::success(
            (new AssetAssignmentResource($assignment))->resolve(),
            'Asset returned.',
        );
    }

    public function replace(int $asset): JsonResponse
    {
        $model = $this->assets->find($asset);
        $this->authorize('replace', $model);

        $model = $this->assets->replace($model, request()->only('note'));

        return ApiResponse::success(
            (new AssetResource($model))->resolve(),
            'Asset replacement recorded.',
        );
    }

    public function assignments(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Asset::class);

        $paginator = $this->assets->listAssignments(
            filters: $request->only(['asset_id', 'employee_id', 'status']),
            perPage: min((int) $request->integer('per_page', 20), 100),
        );

        return ApiResponse::paginated(
            $paginator->through(fn (AssetAssignment $assignment) => (new AssetAssignmentResource($assignment))->resolve()),
        );
    }

    public function maintenances(int $asset): JsonResponse
    {
        $model = $this->assets->find($asset);
        $this->authorize('view', $model);

        $maintenances = $this->assets->listMaintenances($model);

        return ApiResponse::success(
            $maintenances->map(fn (AssetMaintenance $m) => (new AssetMaintenanceResource($m))->resolve())->all(),
        );
    }

    public function storeMaintenance(StoreAssetMaintenanceRequest $request, int $asset): JsonResponse
    {
        $model = $this->assets->find($asset);
        $this->authorize('manageMaintenance', $model);

        $maintenance = $this->assets->addMaintenance($model, $request->validated());

        return ApiResponse::created(
            (new AssetMaintenanceResource($maintenance))->resolve(),
            'Maintenance recorded.',
        );
    }

    public function storeDamageReport(StoreAssetDamageReportRequest $request, int $asset): JsonResponse
    {
        $model = $this->assets->find($asset);
        $this->authorize('reportDamage', $model);

        $report = $this->assets->reportDamage($model, $request->validated(), $request->user());

        return ApiResponse::created(
            (new AssetDamageReportResource($report))->resolve(),
            'Damage report filed.',
        );
    }
}
