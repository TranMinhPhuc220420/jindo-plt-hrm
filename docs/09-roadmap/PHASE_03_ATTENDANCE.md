# Phase 03 — Attendance

> Manual check-in/out, corrections, approvals, summaries.
>
> Master: [MASTER_ROADMAP.md](./MASTER_ROADMAP.md)  
> Business: [../02-business/attendance/README.md](../02-business/attendance/README.md)

---

## Goal

Capture working time and produce **summaries** consumable by Payroll — without writing payslips. Keep providers replaceable (`manual` first).

---

## In Scope

- Check-in / check-out (manual)
- Daily records: late, early leave, OT, break (as rules allow)
- Correction request + approve/reject
- Attendance approval workflow (as designed)
- History + period summary API
- Employee/Manager/HR UI
- Audit on approve/reject

---

## Out of Scope

- GPS / face / fingerprint / QR providers (Future)
- Payroll run creation (Phase 06)
- Owning shift definitions (Phase 05) — may **consume** calendar when available

---

## Dependencies

- Phase 02 Employee (required)
- Phase 05 Shift (recommended for accurate late/OT; stub schedule OK for MVP check-in)

---

## Key Docs

- [ATTENDANCE_API.md](../06-api/ATTENDANCE_API.md)
- [DEPENDENCY_RULES.md](../01-architecture/DEPENDENCY_RULES.md) (Attendance ↛ Payroll writes)
- [SHIFT_API.md](../06-api/SHIFT_API.md)

---

## Exit Criteria

- [x] Employee can check in/out
- [x] Correction + approval path works with permissions
- [x] Summary endpoint stable for payroll input contract
- [x] No payroll tables written from attendance module
- [x] Tests for double check-in and period locked cases
- [ ] Tag path toward `v0.3.0` (deferred until release request)
