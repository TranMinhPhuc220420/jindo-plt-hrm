# Payroll

> Salary components, calculation, approval, payslips, and history.
>
> Source of truth: [PROJECT_LOGIC.md](../../00-overview/PROJECT_LOGIC.md) §6 Payroll

---

## Purpose

Calculate and finalize employee pay for a period. Payroll **consumes** attendance (and related) data through services. It must remain replaceable — v1 focuses on monthly salary; future strategies (hourly, daily, commission, piece rate) plug in behind calculation services.

---

## Responsibilities

| Area | Description |
|------|-------------|
| Salary | Base pay |
| Allowance | Additional pay components |
| Bonus | Bonus components |
| Deduction | Deduction components |
| Tax | Tax calculation/withholding inputs |
| Insurance | Insurance contributions |
| Overtime | Overtime pay from rules + attendance/shift inputs |
| Payroll Calculation | Period computation engine |
| Payroll Approval | Authorization before finalize |
| Payslip | Employee-facing statement |
| Payroll History | Historical runs and payslips |

---

## Business Rules

1. Payroll runs are company-scoped and period-scoped.
2. Salary changes are highly sensitive and **must be audited**.
3. Calculation reads attendance summaries / overtime inputs via Attendance (and shift rules as needed) — never by owning punch tables.
4. v1 calculator: monthly salary strategy; do not hardcode forever — keep a calculation strategy boundary.
5. Approval required before payslips are final/publishable (per company policy).
6. Finalized payroll should be immutable (corrections via controlled reverse/adjust runs, not silent edits).
7. Employees viewing payslips require `can_view_salary` (or equivalent) with own-scope policy.

---

## Key Workflows

### Configure compensation

```
HR sets salary / allowance / deduction templates per employee
  → Authorize sensitive fields → Persist → Audit
```

### Run payroll

```
Select period + population
  → Gather inputs (salary components, attendance summaries, leave unpaid effects, overtime)
    → Calculate (strategy) → Draft results
      → Review → Approve
        → Finalize → Generate payslips → Notify → History
```

### Publish payslip

```
After finalize
  → Payslip available to authorized employee
    → Optional PDF via file storage
```

---

## Dependencies

| May depend on | Must not depend on |
|---------------|--------------------|
| Employee (identity, bank, tax, insurance fields) | Being called by Attendance to “create payslip” |
| Attendance services (summaries/overtime inputs) | Circular dependency with Attendance |
| Leave services (unpaid leave effects via `LeaveCoverageService`) | Report module for calculation |
| Shift overtime rules (interpretation inputs) | |

**Allowed:** Payroll → AttendanceService  
**Forbidden:** Attendance → Payroll writes

---

## Permissions (illustrative)

| Permission | Intent |
|------------|--------|
| `can_view_salary` | View salary/payslip (scoped; often self) |
| `can_manage_salary` | Edit salary components |
| `can_run_payroll` | Execute calculations |
| `can_approve_payroll` | Approve payroll runs |
| `can_view_payroll_history` | View historical runs |
| `can_manage_payslips` | Admin payslip operations |

---

## Events & Side Effects

| Event (example) | Reaction |
|-----------------|----------|
| `SalaryChanged` | Audit (required); notify HR if configured |
| `PayrollCalculated` | Ready for review |
| `PayrollApproved` | Audit |
| `PayrollFinalized` | Generate payslips; notify employees; history |

Heavy calculation/PDF generation may run on queues — see [EVENT_FLOW.md](../../01-architecture/EVENT_FLOW.md).

---

## Out of Scope / Future

- Hourly / daily / commission / piece-rate strategies
- AI payroll assistant
- Deep accounting/ERP posting integrations

---

## Related Documents

- [../attendance/](../attendance/)
- [../leave/](../leave/)
- [../shift/](../shift/)
- [../employee/](../employee/)
- [../../01-architecture/DEPENDENCY_RULES.md](../../01-architecture/DEPENDENCY_RULES.md)
- `docs/06-api/PAYROLL_API.md`
