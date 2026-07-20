<?php

namespace Database\Seeders;

use App\Models\Candidate;
use App\Models\Company;
use App\Models\JobOpening;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class RecruitmentSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            return;
        }

        $company = Company::query()->where('code', 'JINDO')->first();

        if ($company === null) {
            return;
        }

        $opening = JobOpening::query()->updateOrCreate(
            [
                'company_id' => $company->id,
                'code' => 'JOB-0001',
            ],
            [
                'title' => 'Software Engineer',
                'description' => 'Demo job opening seeded for local development.',
                'headcount' => 1,
                'status' => 'open',
                'opened_at' => CarbonImmutable::now()->toDateString(),
                'closed_at' => null,
            ],
        );

        Candidate::query()->updateOrCreate(
            [
                'company_id' => $company->id,
                'job_opening_id' => $opening->id,
                'email' => 'candidate.demo@example.test',
            ],
            [
                'full_name' => 'Demo Candidate',
                'phone' => null,
                'stage' => 'screening',
                'source' => 'referral',
            ],
        );
    }
}
