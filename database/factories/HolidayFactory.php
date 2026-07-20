<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Holiday;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Holiday>
 */
class HolidayFactory extends Factory
{
    protected $model = Holiday::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'date' => fake()->unique()->date(),
            'name' => 'Public Holiday',
        ];
    }
}
