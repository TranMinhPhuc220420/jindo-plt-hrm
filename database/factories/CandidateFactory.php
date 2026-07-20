<?php

namespace Database\Factories;

use App\Models\Candidate;
use App\Models\Company;
use App\Models\JobOpening;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Candidate>
 */
class CandidateFactory extends Factory
{
    protected $model = Candidate::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'job_opening_id' => JobOpening::factory(),
            'full_name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => '+84'.$this->faker->numberBetween(100000000, 999999999),
            'stage' => 'applied',
            'source' => 'referral',
            'resume_document_id' => null,
            'employee_id' => null,
        ];
    }
}
