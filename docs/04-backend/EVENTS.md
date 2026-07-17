# Events

> Domain events emitted after successful business outcomes.
>
> Flow: [EVENT_FLOW.md](../01-architecture/EVENT_FLOW.md) · Listeners: [LISTENERS.md](./LISTENERS.md)

---

## Purpose

Events decouple side effects (audit, notifications, projections) from core use-case logic without creating circular module ownership.

---

## When to Dispatch

Dispatch after the business transaction succeeds for outcomes other modules/channels may react to:

| Example event | After |
|---------------|-------|
| `LeaveRequested` | Leave request created |
| `LeaveApproved` / `LeaveRejected` | Approval decision |
| `AttendanceApproved` | Attendance approval |
| `EmployeeUpdated` | Profile mutation |
| `SalaryChanged` | Compensation change |
| `AssetAssigned` | Asset custody change |
| `OfferAccepted` | Recruitment handoff |
| `PayrollFinalized` | Payroll finalized |
| `OnboardingCompleted` | Onboarding done |

Do **not** use events to perform the core decision itself (e.g. calculating whether leave is approvable only inside a listener).

---

## Naming & Location

```
app/Events/{Module}/{PastTenseSomething}.php

LeaveApproved
AttendanceCorrectionRequested
PayrollFinalized
EmployeeStatusChanged
```

Name events as **facts that already happened** (past tense), not commands (`ApproveLeave`).

---

## Event Payload Rules

Include:

- IDs needed by listeners (`leaveRequestId`, `employeeId`, `companyId`, `actorId`)
- Minimal snapshot fields required for notification/audit
- Enough context to act without reloading huge graphs when practical

Avoid:

- Entire Eloquent models that become stale after queue serialization (prefer IDs + refresh in listener)
- Sensitive secrets (raw passwords, full bank payloads unless audit requires controlled storage)
- Payloads that force listeners to write into forbidden modules

---

## Dispatch Timing

Prefer:

```
DB::transaction(function () { ... });
event(new LeaveApproved(...)); // after commit
```

Or Laravel `event()->dispatch()` patterns / `ShouldDispatchAfterCommit` when available so failed transactions do not notify users.

---

## Sync vs Async

| Mode | Use |
|------|-----|
| Sync listeners | Cheap, must-complete-with-request side effects |
| Queued listeners / jobs | Email, push, exports, fan-out |

See [QUEUES.md](./QUEUES.md).

---

## Dependency Safety

Events must not hide illegal dependencies:

- Attendance may emit `AttendanceApproved`
- A listener may notify / audit
- A listener must **not** create payslips unless a Payroll use case explicitly owns that flow

Recruitment `OfferAccepted` may start onboarding via Onboarding service — that is a downward lifecycle handoff.

---

## Testing

- Assert that services dispatch expected events (fake event dispatcher)
- Assert listeners in isolation
- Avoid relying on full notification delivery in every feature test

---

## Anti-Patterns

1. Command-style events (`DoApproveLeave`)
2. Dispatch before persistence succeeds
3. Mega-events reused for unrelated outcomes
4. Listeners that call back into the emitting service and re-enter the same use case

---

## Related Documents

- [LISTENERS.md](./LISTENERS.md)
- [QUEUES.md](./QUEUES.md)
- [SERVICES.md](./SERVICES.md)
- [../01-architecture/EVENT_FLOW.md](../01-architecture/EVENT_FLOW.md)
