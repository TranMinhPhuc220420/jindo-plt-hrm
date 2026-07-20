<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\JobOpening;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobOpening>
 */
class JobOpeningFactory extends Factory
{
    protected $model = JobOpening::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'code' => 'JOB-'.$this->faker->unique()->numberBetween(1000, 999999),
            'title' => $this->faker->jobTitle(),
            'department_id' => null,
            'position_id' => null,
            'description' => $this->faker->paragraph(),
            'headcount' => 1,
            'status' => 'open',
            'opened_at' => '2026-07-01',
            'closed_at' => null,
        ];
    }
}
