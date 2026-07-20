<?php

namespace App\Events;

use App\Models\EmployeeSalary;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SalaryChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(public EmployeeSalary $salary) {}
}
