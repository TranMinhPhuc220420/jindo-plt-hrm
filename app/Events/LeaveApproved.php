<?php

namespace App\Events;

use App\Models\LeaveRequest;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LeaveApproved
{
    use Dispatchable, SerializesModels;

    public function __construct(public LeaveRequest $leaveRequest) {}
}
