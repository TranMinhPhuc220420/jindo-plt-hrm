<?php

namespace App\Events;

use App\Models\ShiftAssignment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ShiftAssigned
{
    use Dispatchable, SerializesModels;

    public function __construct(public ShiftAssignment $assignment) {}
}
