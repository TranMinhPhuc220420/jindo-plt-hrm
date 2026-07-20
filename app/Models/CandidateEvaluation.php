<?php

namespace App\Models;

use Database\Factories\CandidateEvaluationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateEvaluation extends Model
{
    /** @use HasFactory<CandidateEvaluationFactory> */
    use HasFactory;

    public const RECOMMENDATIONS = ['hire', 'no_hire', 'hold'];

    protected $fillable = [
        'company_id',
        'interview_id',
        'candidate_id',
        'evaluator_id',
        'rating',
        'recommendation',
        'comments',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rating' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Interview, $this>
     */
    public function interview(): BelongsTo
    {
        return $this->belongsTo(Interview::class);
    }

    /**
     * @return BelongsTo<Candidate, $this>
     */
    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }
}
