<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetDamageReport extends Model
{
    /** @use HasFactory<\Database\Factories\AssetDamageReportFactory> */
    use HasFactory;

    protected $fillable = [
        'company_id',
        'asset_id',
        'description',
        'reported_at',
        'reported_by',
        'document_ids',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reported_at' => 'date',
            'document_ids' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Asset, $this>
     */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
