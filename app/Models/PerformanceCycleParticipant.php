<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerformanceCycleParticipant extends Model
{
    /** @use HasFactory<\Database\Factories\PerformanceCycleParticipantFactory> */
    use HasFactory;

    protected $fillable = [
        'company_id',
        'review_cycle_id',
        'employee_id',
    ];

    /**
     * @return BelongsTo<PerformanceReviewCycle, $this>
     */
    public function reviewCycle(): BelongsTo
    {
        return $this->belongsTo(PerformanceReviewCycle::class, 'review_cycle_id');
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
