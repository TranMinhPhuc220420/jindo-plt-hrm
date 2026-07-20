<?php

namespace Database\Factories;

use App\Models\Candidate;
use App\Models\Company;
use App\Models\Interview;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Interview>
 */
class InterviewFactory extends Factory
{
    protected $model = Interview::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'candidate_id' => Candidate::factory(),
            'scheduled_at' => '2026-07-22 10:00:00',
            'mode' => 'onsite',
            'location' => 'HQ',
            'interviewer_id' => null,
            'status' => 'scheduled',
            'notes' => null,
        ];
    }
}
