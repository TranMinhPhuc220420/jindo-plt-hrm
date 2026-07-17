# Event Flow

> Domain events, listeners, queues, and side-effect orchestration.
>
> Source of truth: [PROJECT_LOGIC.md](../00-overview/PROJECT_LOGIC.md)
>
> Backend details: `docs/04-backend/EVENTS.md`, `LISTENERS.md`, `QUEUES.md`

---

## Purpose

Define how the system reacts after a successful domain action without creating circular module dependencies or burying side effects inside controllers.

---

## When to Use Events

Use domain events for **reactions**, not for the core decision of a use case.

| Good event use | Keep inside the service (not only an event) |
|----------------|---------------------------------------------|
| Send notification after leave approved | Calculating whether leave can be approved |
| Write audit log after salary changed | Applying the salary change itself |
| Assign equipment tasks after onboarding started | Creating the employee record |
| Refresh report projections after attendance locked | Recording a check-in |

Core business transaction first; events after success (or within the same transaction only when listeners are safe/idempotent).

---

## High-Level Flow

```
API Request
  → Controller
    → Service (business transaction)
      → Persist via Repository
        → Dispatch Domain Event(s)
          → Listener(s)
            → Sync work (small, safe)
            → Queue Job (email, push, heavy fan-out, exports)
```

```
Domain Event
  ├── Audit Listener
  ├── Notification Listener / Job
  ├── Cross-module reaction via target module’s service
  └── Projection / cache invalidation (when needed)
```

---

## Dependency-Safe Reactions

Listeners may call **downward or sideways through services**, never create upward circular ownership.

Examples aligned with project logic:

| Event (example) | Reaction |
|-----------------|----------|
| `LeaveApproved` | Notify employee; audit; maybe attendance/leave balance already updated in Leave service |
| `AttendanceApproved` | Audit; notify; payroll does **not** auto-mutate unless a payroll use case explicitly consumes attendance later |
| `EmployeeUpdated` | Audit; notify HR owner if configured |
| `AssetAssigned` | Audit; notify employee; document/onboarding checklist item may update via Onboarding/Asset services |
| `OfferAccepted` | Start onboarding workflow via Onboarding service |
| `PayrollFinalized` | Generate payslips; notify employees; audit |

Payroll consuming attendance data happens in payroll calculation services, not by attendance emitting “create payslip” commands.

---

## Sync vs Async

| Mode | Use for |
|------|---------|
| Synchronous listener | Invariants that must complete with the request and are cheap (e.g. critical audit write if required synchronously) |
| Queued job | Email, push, large fan-out, PDF/export, payroll-heavy post-processing |

Failure policy:

- Critical business persist must not depend on flaky external email succeeding in-process.
- Jobs must be idempotent where retries are possible.
- Failed jobs are observable (log/monitor), not silently dropped.

---

## Audit Events

Important actions from project principles must be traceable:

- Employee edited
- Salary changed
- Attendance approved
- Leave rejected
- Asset assigned

Preferred pattern:

```
Service completes mutation
  → Audit event / audit writer
    → Append-only audit log record
```

---

## Notification Events

Notifications module responsibilities (email, system, push, reminder, scheduled) should usually subscribe to domain events rather than being called ad hoc from every controller.

```
LeaveApproved → NotifyLeaveApproved Job → Email + System Notification
```

See business docs under `docs/02-business/notification/` when filled.

---

## Anti-Patterns

1. Dispatching events before the DB transaction commits (unless using transactional event patterns deliberately)
2. Controllers dispatching five events instead of one service completing the use case
3. Listeners writing directly into another module’s tables
4. Using events to invert an illegal upward dependency (Attendance → Payroll write)
5. Non-idempotent jobs that duplicate emails/payslips on retry

---

## Related Documents

- [BACKEND_ARCHITECTURE.md](./BACKEND_ARCHITECTURE.md)
- [DEPENDENCY_RULES.md](./DEPENDENCY_RULES.md)
- [AUTHORIZATION.md](./AUTHORIZATION.md)
- `docs/04-backend/EVENTS.md`
- `docs/04-backend/LISTENERS.md`
- `docs/04-backend/QUEUES.md`
