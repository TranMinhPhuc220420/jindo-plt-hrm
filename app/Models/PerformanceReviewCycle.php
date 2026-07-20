<?php

namespace App\Models;

use Database\Factories\PerformanceReviewCycleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PerformanceReviewCycle extends Model
{
    /** @use HasFactory<PerformanceReviewCycleFactory> */
    use HasFactory;

    public const STATUSES = ['draft', 'active', 'finalized'];

    public const FRAMEWORKS = ['goal', 'kpi', 'okr', 'mixed'];

    protected $fillable = [
        'company_id',
        'name',
        'framework',
        'status',
        'starts_on',
        'ends_on',
        'participant_employee_ids',
        'started_at',
        'finalized_at',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'participant_employee_ids' => 'array',
            'started_at' => 'datetime',
            'finalized_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return HasMany<PerformanceCycleParticipant, $this>
     */
    public function participants(): HasMany
    {
        return $this->hasMany(PerformanceCycleParticipant::class, 'review_cycle_id');
    }

    /**
     * @return HasMany<PerformanceEvaluation, $this>
     */
    public function evaluations(): HasMany
    {
        return $this->hasMany(PerformanceEvaluation::class, 'review_cycle_id');
    }
}
