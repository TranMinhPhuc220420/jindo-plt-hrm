<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\OnboardingTemplate;
use Illuminate\Database\Seeder;

class OnboardingSeeder extends Seeder
{
    /**
     * @var list<array{key: string, title: string, mandatory: bool, assignee_type: string}>
     */
    private const ITEMS = [
        ['key' => 'create_account', 'title' => 'Create user account', 'mandatory' => true, 'assignee_type' => 'hr'],
        ['key' => 'collect_docs', 'title' => 'Collect onboarding documents', 'mandatory' => true, 'assignee_type' => 'hr'],
        ['key' => 'assign_equipment', 'title' => 'Assign equipment', 'mandatory' => false, 'assignee_type' => 'it'],
        ['key' => 'probation_ack', 'title' => 'Probation acknowledgement', 'mandatory' => true, 'assignee_type' => 'employee'],
    ];

    public function run(): void
    {
        if (app()->environment('production')) {
            return;
        }

        $company = Company::query()->where('code', 'JINDO')->first();

        if ($company === null) {
            return;
        }

        $template = OnboardingTemplate::query()->updateOrCreate(
            [
                'company_id' => $company->id,
                'name' => 'Default onboarding',
            ],
            [
                'description' => 'Default onboarding checklist seeded for local development.',
                'is_active' => true,
            ],
        );

        foreach (self::ITEMS as $index => $item) {
            $template->items()->updateOrCreate(
                [
                    'onboarding_template_id' => $template->id,
                    'key' => $item['key'],
                ],
                [
                    'company_id' => $company->id,
                    'title' => $item['title'],
                    'mandatory' => $item['mandatory'],
                    'assignee_type' => $item['assignee_type'],
                    'sort_order' => $index,
                ],
            );
        }
    }
}
