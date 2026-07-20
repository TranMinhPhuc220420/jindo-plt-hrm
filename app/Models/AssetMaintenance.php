<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetMaintenance extends Model
{
    /** @use HasFactory<\Database\Factories\AssetMaintenanceFactory> */
    use HasFactory;

    public const STATUSES = ['scheduled', 'in_progress', 'completed', 'cancelled'];

    protected $fillable = [
        'company_id',
        'asset_id',
        'description',
        'status',
        'cost',
        'scheduled_at',
        'completed_at',
        'note',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cost' => 'decimal:2',
            'scheduled_at' => 'date',
            'completed_at' => 'date',
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
