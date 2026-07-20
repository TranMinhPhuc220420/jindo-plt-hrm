<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\AssetDamageReport;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssetDamageReport>
 */
class AssetDamageReportFactory extends Factory
{
    protected $model = AssetDamageReport::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'asset_id' => Asset::factory(),
            'description' => 'Cracked screen',
            'reported_at' => '2026-07-16',
            'reported_by' => null,
            'document_ids' => null,
        ];
    }
}
