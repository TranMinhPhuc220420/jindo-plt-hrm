<?php

namespace App\Models;

use Database\Factories\OnboardingTaskFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnboardingTask extends Model
{
    /** @use HasFactory<OnboardingTaskFactory> */
    use HasFactory;

    public const STATUSES = ['pending', 'done'];

    public const ASSIGNEE_TYPES = ['hr', 'employee', 'manager', 'it'];

    protected $fillable = [
        'company_id',
        'onboarding_case_id',
        'key',
        'title',
        'description',
        'mandatory',
        'assignee_type',
        'status',
        'sort_order',
        'completed_at',
        'completed_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'mandatory' => 'boolean',
            'sort_order' => 'integer',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<OnboardingCase, $this>
     */
    public function onboardingCase(): BelongsTo
    {
        return $this->belongsTo(OnboardingCase::class);
    }
}
