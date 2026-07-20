<?php

namespace Database\Factories;

use App\Models\Candidate;
use App\Models\Company;
use App\Models\Offer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Offer>
 */
class OfferFactory extends Factory
{
    protected $model = Offer::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'candidate_id' => Candidate::factory(),
            'title' => $this->faker->jobTitle(),
            'salary_amount' => 15000000,
            'currency' => 'VND',
            'start_date' => '2026-08-01',
            'probation_ends_on' => '2026-11-01',
            'status' => 'draft',
            'sent_at' => null,
            'sent_by' => null,
            'accepted_at' => null,
            'rejected_at' => null,
            'notes' => null,
        ];
    }
}
