<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerformancePromotionSuggestion extends Model
{
    /** @use HasFactory<\Database\Factories\PerformancePromotionSuggestionFactory> */
    use HasFactory;

    public const STATUSES = ['suggested', 'acknowledged'];

    protected $fillable = [
        'company_id',
        'employee_id',
        'review_cycle_id',
        'evaluation_id',
        'overall_score',
        'status',
        'note',
        'suggested_at',
        'acknowledged_by',
        'acknowledged_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'overall_score' => 'decimal:2',
            'suggested_at' => 'datetime',
            'acknowledged_at' => 'datetime',
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
