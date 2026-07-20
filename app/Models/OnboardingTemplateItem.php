<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnboardingTemplateItem extends Model
{
    /** @use HasFactory<\Database\Factories\OnboardingTemplateItemFactory> */
    use HasFactory;

    public const ASSIGNEE_TYPES = ['hr', 'employee', 'manager', 'it'];

    protected $fillable = [
        'company_id',
        'onboarding_template_id',
        'key',
        'title',
        'description',
        'mandatory',
        'assignee_type',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'mandatory' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<OnboardingTemplate, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(OnboardingTemplate::class, 'onboarding_template_id');
    }
}
