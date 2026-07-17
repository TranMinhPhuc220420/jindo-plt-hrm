# Listeners

> React to domain events with audit, notifications, and safe cross-module handoffs.
>
> Events: [EVENTS.md](./EVENTS.md) · Flow: [EVENT_FLOW.md](../01-architecture/EVENT_FLOW.md)

---

## Purpose

Listeners translate “something happened” into side effects without bloating services with email/SMS/push/audit plumbing.

---

## Naming & Location

```
app/Listeners/{Module_or_Concern}/{Name}.php

SendLeaveApprovedNotification
WriteSalaryChangedAuditLog
StartOnboardingWhenOfferAccepted
GeneratePayslipsOnPayrollFinalized
```

Keep one listener focused on one reaction (or a tightly related set). Split when channels diverge.

---

## Responsibilities

A listener may:

- Write audit log rows
- Enqueue notification jobs
- Call a **downward/sideways** service to continue a lifecycle handoff
- Update read-model/projection tables when that is an explicit design

A listener must not:

- Re-implement approval/calculation rules
- Write directly into another module’s tables bypassing its service
- Depend on HTTP request state
- Create circular event chains (`A` → `B` → `A`) to finish one use case

---

## Registration

Register mappings in `EventServiceProvider` (or Laravel auto-discovery / attribute discovery if adopted):

```
LeaveApproved → WriteLeaveApprovedAuditLog
LeaveApproved → SendLeaveApprovedNotification
OfferAccepted → StartOnboardingWhenOfferAccepted
PayrollFinalized → GeneratePayslipsOnPayrollFinalized
```

Document non-obvious chains in the owning module’s business README.

---

## Sync vs Queued Listeners

| Listener type | Implements / style | Use for |
|---------------|--------------------|---------|
| Synchronous | Default | Critical audit if required immediately |
| Queued | `ShouldQueue` | Email, push, fan-out, PDF |

Failure policy:

- Queued listeners must be safe to retry (idempotent where possible)
- Do not fail the original API request because mail is down if the listener is queued after commit

---

## Cross-Module Listener Examples

| Event | Listener action | Calls |
|-------|-----------------|-------|
| `OfferAccepted` | Start onboarding | `OnboardingService::startFromOffer` |
| `OnboardingCompleted` | Confirm employee steady state | `EmployeeService` status transition as designed |
| `AssetAssigned` | Notify + audit | Notification + Audit |
| `AttendanceApproved` | Audit + notify | Not Payroll finalize |

---

## Idempotency Tips

- Use unique delivery keys / check “already notified” where duplicates hurt
- Payslip generation should no-op if payslip already exists for item
- Audit writes are usually append-only (duplicates are worse than missing — prefer once-after-commit)

---

## Testing

- Unit-test listener behavior with fakes
- Feature tests may `Event::fake()` and assert dispatches without running all listeners
- Explicitly test critical handoff listeners (OfferAccepted → Onboarding)

---

## Anti-Patterns

1. One `UberListener` for all events
2. Listener approving leave by itself
3. Catching and swallowing all exceptions silently
4. Listener → foreign repository writes

---

## Related Documents

- [EVENTS.md](./EVENTS.md)
- [QUEUES.md](./QUEUES.md)
- [../02-business/notification/README.md](../02-business/notification/README.md)
