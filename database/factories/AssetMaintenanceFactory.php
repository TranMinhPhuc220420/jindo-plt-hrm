<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\AssetMaintenance;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssetMaintenance>
 */
class AssetMaintenanceFactory extends Factory
{
    protected $model = AssetMaintenance::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'asset_id' => Asset::factory(),
            'description' => $this->faker->sentence(),
            'status' => 'scheduled',
            'cost' => null,
            'scheduled_at' => '2026-07-20',
            'completed_at' => null,
            'note' => null,
        ];
    }
}
