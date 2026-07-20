<?php

namespace App\Models;

use Database\Factories\CandidateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Candidate extends Model
{
    /** @use HasFactory<CandidateFactory> */
    use HasFactory;

    public const STAGES = ['applied', 'screening', 'interview', 'offer', 'hired', 'rejected', 'withdrawn'];

    protected $fillable = [
        'company_id',
        'job_opening_id',
        'full_name',
        'email',
        'phone',
        'stage',
        'source',
        'resume_document_id',
        'employee_id',
    ];

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return BelongsTo<JobOpening, $this>
     */
    public function jobOpening(): BelongsTo
    {
        return $this->belongsTo(JobOpening::class);
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return HasMany<Interview, $this>
     */
    public function interviews(): HasMany
    {
        return $this->hasMany(Interview::class);
    }

    /**
     * @return HasMany<Offer, $this>
     */
    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class);
    }
}
