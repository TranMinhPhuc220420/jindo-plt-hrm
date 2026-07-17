# Phase 06 — Payroll

> Monthly salary strategy, components, runs, approval, payslips.
>
> Master: [MASTER_ROADMAP.md](./MASTER_ROADMAP.md)  
> Business: [../02-business/payroll/README.md](../02-business/payroll/README.md)

---

## Goal

Calculate and finalize pay for a period using Employee compensation + Attendance summaries (+ leave unpaid effects), with audit on salary changes and immutable finalized runs.

---

## In Scope

- Base salary (monthly strategy) + effective dating
- Allowances, bonuses, deductions
- Tax/insurance fields integration as modeled
- Payroll run: create → calculate → approve → finalize
- Payslips + history + download (PDF queued OK)
- Payroll UI for HR; employee payslip view (`can_view_salary`)
- Queue jobs for heavy calculate/PDF

---

## Out of Scope

- Hourly/daily/commission/piece-rate strategies (Future; keep strategy interface)
- Accounting/ERP posting (Future)
- Attendance module writing payslips (forbidden)

---

## Dependencies

- Phase 02 Employee (required)
- Phase 03 Attendance summary contract (required)
- Phase 04 Leave (recommended for unpaid leave effects)
- Phase 05 Shift OT rules (recommended)

---

## Key Docs

- [PAYROLL_API.md](../06-api/PAYROLL_API.md)
- [ATTENDANCE_API.md](../06-api/ATTENDANCE_API.md) (summary)
- [DEPENDENCY_RULES.md](../01-architecture/DEPENDENCY_RULES.md)
- [QUEUES.md](../04-backend/QUEUES.md)

---

## Exit Criteria

- [ ] Salary changes audited
- [ ] Calculate uses AttendanceService summaries (not punch table ownership)
- [ ] Finalize is immutable (`PAYROLL_ALREADY_FINALIZED`)
- [ ] Employee can view own payslip only with permission/scope
- [ ] Staging smoke with demo employees
- [ ] Milestone toward `v0.4.0`
