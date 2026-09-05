# Notification

> Email, system notifications, push, reminders, and scheduled notifications.
>
> Source of truth: [PROJECT_LOGIC.md](../../00-overview/PROJECT_LOGIC.md) §6 Notifications
>
> Event patterns: [EVENT_FLOW.md](../../01-architecture/EVENT_FLOW.md)

---

## Purpose

Deliver messages about domain events and scheduled reminders. Notification is a **supporting module**: it reacts to domain outcomes; it is not the source of truth for leave, payroll, or attendance decisions.

---

## Responsibilities

| Area | Description |
|------|-------------|
| Email | Email channel delivery |
| System Notification | In-app notification inbox |
| Push Notification | Push channel (mobile-ready / future) |
| Reminder | Reminder messages for upcoming actions |
| Scheduled Notification | Send-at / cron-like scheduled messages |

---

## Business Rules

1. Domain modules dispatch events (or call a Notification service facade); they should not embed raw SMTP logic in controllers.
2. Delivery is preferably asynchronous via queues.
3. Jobs must be idempotent enough to avoid duplicate spam on retry where possible.
4. User notification preferences may suppress channels but must not bypass legally/HR-required notices if policy marks them mandatory.
5. Notification content can reference entities (leave_id, payroll_run_id) but must not become a second database for those domains.
6. Failed external delivery is logged/monitorable; core domain transaction should already have succeeded independently.
7. Multi-company readiness: templates and broadcasts remain company-scoped.

---

## Key Workflows

### Event-driven notify

```
Domain success (e.g. LeaveApproved)
  → Event → Notification listener
    → Build message from template + payload
      → Queue Email / System / Push jobs
        → Persist inbox record for system channel
```

### Reminder / scheduled

```
Scheduler finds due reminders
  → Enqueue notification jobs
    → Deliver inbox + optional email + Web Push (VAPID)
    → Mark reminder sent (`attendance_punch_reminders`)
```

Missed punch reminders (v1): check-in after shift start + grace if not punched; check-out after shift end + grace if still open. Skip rest days and full-day approved leave. Night-shift check-out is due the following morning.

### Inbox read state

```
User opens notifications
  → Mark read / mark all read
```

---

## Dependencies

| May depend on | Must not depend on |
|---------------|--------------------|
| User/employee identity for recipients | Owning domain write models |
| Domain event payloads | Completing leave/payroll approval logic |
| Queue infrastructure | Report calculation |

Upstream producers: Leave, Attendance, Payroll, Recruitment, Onboarding, Assets, Performance, Employee, etc.

---

## Permissions (illustrative)

| Permission | Intent |
|------------|--------|
| `can_view_own_notifications` | Inbox access |
| `can_manage_notification_templates` | Admin templates |
| `can_send_broadcast_notification` | Company broadcasts |
| `can_manage_notification_settings` | Channel/default settings |

---

## Events & Side Effects

Notification mostly **consumes** events. It may emit operational events such as `NotificationFailed` for monitoring, not for re-driving business approvals.

### Shipped notification types

| Type | Recipient |
|------|-----------|
| `leave.requested` | Requester |
| `leave.pending_approval` | Manager (or leave approvers) |
| `leave.approved` / `leave.rejected` / `leave.cancelled` | Requester |
| `leave.cancelled_pending` | Manager when a pending request is cancelled |
| `attendance.correction_requested` | Approver |
| `attendance.correction_approved` / `rejected` | Requester |
| `attendance.check_in_reminder` / `check_out_reminder` | Employee (scheduler; once per shift window per day) |
| `shift.assigned` / `shift.changed` | Employee |
| `asset.assigned` / `asset.returned` | Employee |
| `payroll.salary_changed` / `payroll.finalized` | Employee |
| `payroll.calculated` / `payroll.approved` | Payroll ops (permission-scoped) |
| `performance.cycle_started` / `cycle_finalized` | Cycle participants |
| `performance.evaluation_submitted` | Manager / review cycle managers |
| `onboarding.started` / `completed` | Employee |
| `onboarding.task_completed` | Onboarding owners |
| `employee.created` / `created_hr` / `status_changed` | Employee / HR / manager |
| `report.export_ready` | Export requester |
| `recruitment.offer_sent` / `offer_accepted` / `stage_changed` | Recruiters |
| `document.shared` / `document.uploaded` | Employee owner / document viewers |
| `broadcast.announcement` | All company employees (via `POST /api/notifications/broadcast`) |
| `push.test` | Current admin (`POST /api/notifications/test-push`, immediate Web Push) |

---

## Out of Scope / Future

- Full marketing campaign suite
- Complex cross-channel journey builders
- Guaranteed SMS provider specifics (integrate later as a channel)

---

## Related Documents

- [../../01-architecture/EVENT_FLOW.md](../../01-architecture/EVENT_FLOW.md)
- [../leave/](../leave/)
- [../payroll/](../payroll/)
- `docs/06-api/NOTIFICATION_API.md`
