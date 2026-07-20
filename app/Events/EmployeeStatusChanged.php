<?php

namespace App\Events;

use App\Models\Employee;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EmployeeStatusChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Employee $employee,
        public string $from,
        public string $to,
    ) {}
}
