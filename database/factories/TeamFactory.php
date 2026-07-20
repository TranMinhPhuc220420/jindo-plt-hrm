<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Department;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Team>
 */
class TeamFactory extends Factory
{
    protected $model = Team::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $company = Company::factory();

        return [
            'company_id' => $company,
            'department_id' => Department::factory()->for($company),
            'name' => fake()->words(2, true),
            'code' => strtoupper(fake()->unique()->bothify('TEAM-###')),
            'is_active' => true,
        ];
    }
}
