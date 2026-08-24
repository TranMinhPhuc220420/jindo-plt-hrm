<?php

namespace App\Services\Employee;

use App\Exceptions\DomainException;
use App\Models\AssetAssignment;
use App\Models\Employee;
use App\Services\Asset\AssetService;
use Illuminate\Database\Eloquent\Collection;

class EmployeeOffboardingService
{
    public function __construct(
        private readonly AssetService $assets,
    ) {}

    /**
     * @return Collection<int, AssetAssignment>
     */
    public function outstandingAssets(Employee $employee): Collection
    {
        return $this->assets->listOutstandingForEmployee($employee);
    }

    public function assertAssetsReturnedOrConfirmed(Employee $employee, bool $confirmAssetReturn): void
    {
        $outstanding = $this->outstandingAssets($employee);

        if ($outstanding->isEmpty()) {
            return;
        }

        if ($confirmAssetReturn) {
            return;
        }

        $assets = $outstanding
            ->map(function (AssetAssignment $assignment): array {
                $asset = $assignment->asset;

                return [
                    'id' => $asset?->id ?? $assignment->asset_id,
                    'code' => $asset?->code ?? (string) $assignment->asset_id,
                    'name' => $asset?->name ?? '',
                ];
            })
            ->values()
            ->all();

        throw new DomainException(
            message: 'Return assigned assets before changing this employee status.',
            errorCode: 'EMPLOYEE_HAS_OUTSTANDING_ASSETS',
            status: 409,
            errors: [
                'assets' => array_map(
                    fn (array $row): string => trim($row['code'].' '.$row['name']),
                    $assets,
                ),
            ],
            meta: ['assets' => $assets],
        );
    }

    public function returnOutstanding(Employee $employee): void
    {
        $this->assets->returnOutstandingForEmployee($employee);
    }
}
