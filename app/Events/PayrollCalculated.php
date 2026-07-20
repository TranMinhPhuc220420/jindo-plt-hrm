<?php

namespace App\Events;

use App\Models\PayrollRun;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PayrollCalculated
{
    use Dispatchable, SerializesModels;

    public function __construct(public PayrollRun $payrollRun) {}
}
