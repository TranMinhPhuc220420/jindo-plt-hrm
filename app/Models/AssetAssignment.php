<?php

namespace App\Models;

use Database\Factories\AssetAssignmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetAssignment extends Model
{
    /** @use HasFactory<AssetAssignmentFactory> */
    use HasFactory;

    public const STATUSES = ['active', 'returned'];

    protected $fillable = [
        'company_id',
        'asset_id',
        'employee_id',
        'status',
        'assigned_at',
        'assigned_by',
        'returned_at',
        'return_condition',
        'note',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'assigned_at' => 'date',
            'returned_at' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Asset, $this>
     */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
