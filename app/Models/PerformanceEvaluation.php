<?php

namespace App\Models;

use Database\Factories\PerformanceEvaluationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerformanceEvaluation extends Model
{
    /** @use HasFactory<PerformanceEvaluationFactory> */
    use HasFactory;

    protected $fillable = [
        'company_id',
        'review_cycle_id',
        'employee_id',
        'evaluator_id',
        'overall_score',
        'summary',
        'ratings',
        'submitted_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'overall_score' => 'decimal:2',
            'ratings' => 'array',
            'submitted_at' => 'datetime',
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

    /**
     * @return BelongsTo<User, $this>
     */
    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }
}
