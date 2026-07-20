<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Position;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Position>
 */
class PositionFactory extends Factory
{
    protected $model = Position::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->jobTitle(),
            'code' => strtoupper(fake()->unique()->bothify('POS-###')),
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }
}
