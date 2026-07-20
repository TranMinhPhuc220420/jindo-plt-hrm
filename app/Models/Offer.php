<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Offer extends Model
{
    /** @use HasFactory<\Database\Factories\OfferFactory> */
    use HasFactory;

    public const STATUSES = ['draft', 'sent', 'accepted', 'rejected'];

    protected $fillable = [
        'company_id',
        'candidate_id',
        'title',
        'salary_amount',
        'currency',
        'start_date',
        'probation_ends_on',
        'status',
        'sent_at',
        'sent_by',
        'accepted_at',
        'rejected_at',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'salary_amount' => 'decimal:2',
            'start_date' => 'date',
            'probation_ends_on' => 'date',
            'sent_at' => 'datetime',
            'accepted_at' => 'datetime',
            'rejected_at' => 'datetime',
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
     * @return BelongsTo<Candidate, $this>
     */
    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }
}
