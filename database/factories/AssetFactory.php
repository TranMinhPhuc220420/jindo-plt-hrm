<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Asset>
 */
class AssetFactory extends Factory
{
    protected $model = Asset::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'code' => 'AST-'.$this->faker->unique()->numberBetween(1000, 999999),
            'name' => $this->faker->words(2, true),
            'category' => 'laptop',
            'status' => 'available',
            'serial_number' => strtoupper($this->faker->bothify('SN-####??')),
            'assigned_to' => null,
            'notes' => null,
        ];
    }
}
