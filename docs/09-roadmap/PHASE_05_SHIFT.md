# Phase 05 — Shift

> Shift definitions, assignments, working calendar, overtime rules.
>
> Master: [MASTER_ROADMAP.md](./MASTER_ROADMAP.md)  
> Business: [../02-business/shift/README.md](../02-business/shift/README.md)

---

## Goal

Define when employees are expected to work so Attendance and Leave can validate against a real schedule, and Payroll can interpret overtime inputs.

---

## In Scope

- Shift definitions (standard / night / flexible flags as modeled)
- Assignments to employees with date ranges
- Working calendar resolution API
- Overtime rules (schedule-side)
- Rotating patterns (MVP or follow-up within phase if timebox allows)
- Admin + employee “my schedule” UI

---

## Out of Scope

- Payroll rate calculation (Phase 06)
- AI roster optimization (Future)

---

## Dependencies

- Phase 02 Employee

**Ordering tip:** Complete enough of Phase 05 before hardening Attendance late/OT logic.

---

## Key Docs

- [SHIFT_API.md](../06-api/SHIFT_API.md)
- [ATTENDANCE_API.md](../06-api/ATTENDANCE_API.md)
- [LEAVE_API.md](../06-api/LEAVE_API.md)

---

## Exit Criteria

- [x] HR can define and assign shifts without overlap bugs
- [x] Working calendar returns expected windows per day
- [x] Attendance/Leave can call shift services (integration smoke)
- [x] Overtime rules readable by payroll later
- [x] Tests for assignment overlap
- [ ] Tag path toward `v0.3.0` (deferred until release request)
