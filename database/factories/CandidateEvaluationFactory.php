<?php

namespace Database\Factories;

use App\Models\Candidate;
use App\Models\CandidateEvaluation;
use App\Models\Company;
use App\Models\Interview;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CandidateEvaluation>
 */
class CandidateEvaluationFactory extends Factory
{
    protected $model = CandidateEvaluation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'interview_id' => Interview::factory(),
            'candidate_id' => Candidate::factory(),
            'evaluator_id' => null,
            'rating' => $this->faker->numberBetween(1, 5),
            'recommendation' => 'hire',
            'comments' => $this->faker->sentence(),
        ];
    }
}
