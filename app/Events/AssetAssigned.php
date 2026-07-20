<?php

namespace App\Events;

use App\Models\AssetAssignment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AssetAssigned
{
    use Dispatchable, SerializesModels;

    public function __construct(public AssetAssignment $assignment) {}
}
