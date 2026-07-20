<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerformanceGoal extends Model
{
    /** @use HasFactory<\Database\Factories\PerformanceGoalFactory> */
    use HasFactory;

    public const TYPES = ['goal', 'kpi', 'okr'];

    public const STATUSES = ['active', 'completed', 'cancelled'];

    protected $fillable = [
        'company_id',
        'review_cycle_id',
        'employee_id',
        'title',
        'description',
        'type',
        'metric',
        'target',
        'weight',
        'progress',
        'status',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'weight' => 'decimal:2',
            'progress' => 'integer',
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
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return BelongsTo<PerformanceReviewCycle, $this>
     */
    public function reviewCycle(): BelongsTo
    {
        return $this->belongsTo(PerformanceReviewCycle::class, 'review_cycle_id');
    }
}
