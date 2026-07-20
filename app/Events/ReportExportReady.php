<?php

namespace App\Events;

use App\Models\ReportExport;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReportExportReady
{
    use Dispatchable, SerializesModels;

    public function __construct(public ReportExport $export) {}
}
