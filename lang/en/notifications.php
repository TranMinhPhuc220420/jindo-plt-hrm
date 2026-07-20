<?php

return [
    'leave' => [
        'requested' => [
            'title' => 'Leave request submitted',
            'body' => 'Your leave request has been submitted for approval.',
        ],
        'pending_approval' => [
            'title' => 'Leave request awaiting approval',
            'body' => 'A team member submitted a leave request that needs your review.',
        ],
        'approved' => [
            'title' => 'Leave approved',
            'body' => 'Your leave request was approved.',
        ],
        'rejected' => [
            'title' => 'Leave rejected',
            'body' => 'Your leave request was rejected.',
        ],
        'cancelled' => [
            'title' => 'Leave cancelled',
            'body' => 'Your leave request was cancelled.',
        ],
        'cancelled_pending' => [
            'title' => 'Leave request cancelled',
            'body' => 'A leave request that was pending your approval was cancelled.',
        ],
    ],
    'attendance' => [
        'correction_requested' => [
            'title' => 'Attendance correction requested',
            'body' => 'An attendance correction needs your review.',
        ],
        'correction_approved' => [
            'title' => 'Attendance correction approved',
            'body' => 'Your attendance correction was approved.',
        ],
        'correction_rejected' => [
            'title' => 'Attendance correction rejected',
            'body' => 'Your attendance correction was rejected.',
        ],
    ],
    'shift' => [
        'assigned' => [
            'title' => 'Shift assigned',
            'body' => 'You have been assigned a work shift.',
        ],
        'changed' => [
            'title' => 'Shift updated',
            'body' => 'Your shift assignment was updated.',
        ],
    ],
    'asset' => [
        'assigned' => [
            'title' => 'Asset assigned',
            'body' => 'A company asset has been assigned to you.',
        ],
        'returned' => [
            'title' => 'Asset returned',
            'body' => 'Your asset assignment has been returned.',
        ],
    ],
    'payroll' => [
        'salary_changed' => [
            'title' => 'Salary updated',
            'body' => 'Your compensation details were updated.',
        ],
        'calculated' => [
            'title' => 'Payroll ready for review',
            'body' => 'A payroll run has been calculated and is ready for approval.',
        ],
        'approved' => [
            'title' => 'Payroll approved',
            'body' => 'A payroll run was approved and can be finalized.',
        ],
        'finalized' => [
            'title' => 'Payslip available',
            'body' => 'Your payslip for the latest payroll run is available.',
        ],
    ],
    'performance' => [
        'cycle_started' => [
            'title' => 'Review cycle started',
            'body' => 'A performance review cycle you participate in has started.',
        ],
        'cycle_finalized' => [
            'title' => 'Review cycle finalized',
            'body' => 'A performance review cycle you participate in has been finalized.',
        ],
        'evaluation_submitted' => [
            'title' => 'Evaluation submitted',
            'body' => 'A performance evaluation was submitted and may need your attention.',
        ],
    ],
    'onboarding' => [
        'started' => [
            'title' => 'Onboarding started',
            'body' => 'Your onboarding has started. Please complete your assigned tasks.',
        ],
        'completed' => [
            'title' => 'Onboarding completed',
            'body' => 'Your onboarding is complete. Welcome aboard!',
        ],
        'task_completed' => [
            'title' => 'Onboarding task completed',
            'body' => 'An onboarding checklist item was completed.',
        ],
    ],
    'employee' => [
        'created' => [
            'title' => 'Welcome',
            'body' => 'Your employee profile has been created.',
        ],
        'created_hr' => [
            'title' => 'New employee created',
            'body' => 'A new employee profile was added to the company.',
        ],
        'status_changed' => [
            'title' => 'Employment status updated',
            'body' => 'An employment status change was recorded.',
        ],
    ],
    'report' => [
        'export_ready' => [
            'title' => 'Report export ready',
            'body' => 'Your report export is ready to download.',
        ],
    ],
    'recruitment' => [
        'offer_sent' => [
            'title' => 'Offer sent',
            'body' => 'An offer was sent to a candidate.',
        ],
        'offer_accepted' => [
            'title' => 'Offer accepted',
            'body' => 'A candidate accepted an offer.',
        ],
        'stage_changed' => [
            'title' => 'Candidate stage updated',
            'body' => 'A candidate moved to a new pipeline stage.',
        ],
    ],
    'document' => [
        'shared' => [
            'title' => 'Document shared with you',
            'body' => 'A document was uploaded and shared with you.',
        ],
        'uploaded' => [
            'title' => 'Sensitive document uploaded',
            'body' => 'A sensitive document was uploaded and may need review.',
        ],
    ],
    'broadcast' => [
        'announcement' => [
            'title' => 'Company announcement',
            'body' => 'You have a new company announcement.',
        ],
    ],
];
