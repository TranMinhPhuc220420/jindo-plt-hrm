<?php

namespace App\Console\Commands;

use App\Services\Attendance\AttendancePunchReminderService;
use Illuminate\Console\Command;

class SendAttendancePunchRemindersCommand extends Command
{
    protected $signature = 'attendance:send-punch-reminders';

    protected $description = 'Notify employees who forgot to check in or check out';

    public function handle(AttendancePunchReminderService $reminders): int
    {
        $sent = $reminders->sendDue();
        $this->info("Sent {$sent} punch reminder(s).");

        return self::SUCCESS;
    }
}
