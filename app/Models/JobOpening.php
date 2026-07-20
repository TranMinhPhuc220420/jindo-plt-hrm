<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobOpening extends Model
{
    /** @use HasFactory<\Database\Factories\JobOpeningFactory> */
    use HasFactory;

    public const STATUSES = ['open', 'closed'];

    protected $fillable = [
        'company_id',
        'code',
        'title',
        'department_id',
        'position_id',
        'description',
        'headcount',
        'status',
        'opened_at',
        'closed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'headcount' => 'integer',
            'opened_at' => 'date',
            'closed_at' => 'date',
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
     * @return HasMany<Candidate, $this>
     */
    public function candidates(): HasMany
    {
        return $this->hasMany(Candidate::class);
    }
}
