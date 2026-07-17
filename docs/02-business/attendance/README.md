# Attendance

> Presence, working hours, exceptions, corrections, and summaries.
>
> Source of truth: [PROJECT_LOGIC.md](../../00-overview/PROJECT_LOGIC.md) §6 Attendance

---

## Purpose

Record when employees work and compute attendance facts (hours, late, early leave, overtime, breaks). Payroll may **consume** attendance summaries through services; Attendance must not write payroll.

---

## Responsibilities

| Area | Description |
|------|-------------|
| Check-in / Check-out | Start and end of work sessions |
| Working Hours | Calculated worked time |
| Late | Arrival after scheduled start |
| Early Leave | Departure before scheduled end |
| Overtime | Time beyond schedule / rules |
| Break Time | Break periods |
| Attendance Correction | Requests/adjustments to fix records |
| Attendance Approval | Confirmation of records or corrections |
| Attendance History | Historical records |
| Attendance Summary | Aggregates for periods (day/week/month) |

---

## Business Rules

1. Attendance rows belong to an **employee** and **company**.
2. v1 capture method is **manual check-in/out**; providers (GPS, face, fingerprint, QR) must plug in behind the same service boundary later.
3. Late / early / overtime interpretation should consult **Shift / working calendar** (and approved leave when relevant) via services — not duplicate shift tables.
4. Corrections require authorization and typically approval before they become canonical.
5. Approving attendance is auditable.
6. Summaries used by payroll are produced/owned by Attendance (or a read contract it exposes), not copied into Payroll as a second write model for raw punches.
7. Module must remain replaceable without changing Payroll’s public consumption contract.

---

## Key Workflows

### Check-in / check-out

```
Employee (or device provider later)
  → Authorize → Validate shift/window rules
    → Record punch → Recompute day metrics
```

### Correction

```
Employee/HR submits correction
  → Validate → Pending approval
    → Manager/HR approves/rejects
      → If approved: apply → Audit → Notify
```

### Approval & summary

```
Period close / manager review
  → Approve outstanding items as policy requires
    → Publish summary consumable by Payroll/Reports
```

---

## Dependencies

| May depend on | Must not depend on |
|---------------|--------------------|
| Employee | Payroll repositories / payslip writes |
| Shift (schedule, overtime rules) | Report module to complete punches |
| Leave (approved leave for day interpretation) | Circular write ownership with Leave |

**Forbidden:** Attendance → Payroll writes.

**Allowed:** Payroll → AttendanceService for summaries during calculation.

---

## Permissions (illustrative)

| Permission | Intent |
|------------|--------|
| `can_check_in_out` | Perform own punches |
| `can_view_attendance` | View attendance (scoped) |
| `can_request_attendance_correction` | Submit corrections |
| `can_approve_attendance` | Approve records/corrections |
| `can_manage_attendance` | HR overrides / admin adjustments |

---

## Events & Side Effects

| Event (example) | Reaction |
|-----------------|----------|
| `AttendanceRecorded` | Optional realtime UI; metrics recompute |
| `AttendanceCorrectionRequested` | Notify approver |
| `AttendanceApproved` | Audit; notify; summaries updated |
| `AttendanceRejected` | Audit; notify requester |

---

## Out of Scope / Future

- GPS / face / fingerprint / QR providers
- Hardware device management
- Automatic payroll run triggers on attendance approve

---

## Related Documents

- [../shift/](../shift/)
- [../leave/](../leave/)
- [../payroll/](../payroll/)
- [../../01-architecture/DEPENDENCY_RULES.md](../../01-architecture/DEPENDENCY_RULES.md)
- `docs/06-api/ATTENDANCE_API.md`
