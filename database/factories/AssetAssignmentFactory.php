<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\Company;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssetAssignment>
 */
class AssetAssignmentFactory extends Factory
{
    protected $model = AssetAssignment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'asset_id' => Asset::factory(),
            'employee_id' => Employee::factory(),
            'status' => 'active',
            'assigned_at' => '2026-07-16',
            'assigned_by' => null,
            'returned_at' => null,
            'return_condition' => null,
            'note' => null,
        ];
    }
}
