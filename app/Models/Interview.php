<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Interview extends Model
{
    /** @use HasFactory<\Database\Factories\InterviewFactory> */
    use HasFactory;

    public const STATUSES = ['scheduled', 'completed', 'cancelled'];

    protected $fillable = [
        'company_id',
        'candidate_id',
        'scheduled_at',
        'mode',
        'location',
        'interviewer_id',
        'status',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Candidate, $this>
     */
    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    /**
     * @return HasMany<CandidateEvaluation, $this>
     */
    public function evaluations(): HasMany
    {
        return $this->hasMany(CandidateEvaluation::class);
    }
}
