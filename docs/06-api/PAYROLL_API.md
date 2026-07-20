# Payroll API

> Salary components, runs, calculation, approval, payslips.
>
> Business: [../02-business/payroll/README.md](../02-business/payroll/README.md)  
> Standard: [REST_STANDARD.md](./REST_STANDARD.md)

---

## Base Paths

```
/api/employee-salaries
/api/allowances
/api/deductions
/api/bonuses
/api/payroll-runs
/api/payslips
```

---

## Permissions

| Permission | Use |
|------------|-----|
| `can_view_salary` | Own/scoped salary & payslip |
| `can_manage_salary` | Edit compensation components |
| `can_run_payroll` | Create/calculate runs |
| `can_approve_payroll` | Approve runs |
| `can_view_payroll_history` | Historical runs |
| `can_manage_payslips` | Admin payslip ops |

---

## Endpoints

| Method | Path | Description |
|--------|------|-------------|
| `GET` | `/api/employee-salaries` | List salary configs |
| `PUT` | `/api/employees/{id}/salary` | Upsert base salary (audited) |
| `GET` | `/api/employees/{id}/allowances` | Allowances |
| `PUT` | `/api/employees/{id}/allowances` | Replace allowances |
| `GET` | `/api/employees/{id}/deductions` | Deductions |
| `PUT` | `/api/employees/{id}/deductions` | Replace deductions |
| `GET` | `/api/payroll-runs` | List runs |
| `POST` | `/api/payroll-runs` | Create run (draft) |
| `GET` | `/api/payroll-runs/{id}` | Run detail + items summary |
| `PUT` | `/api/payroll-runs/{id}` | Update name/period (**draft only**) |
| `DELETE` | `/api/payroll-runs/{id}` | Delete run (**before finalize**) |
| `POST` | `/api/payroll-runs/{id}/calculate` | Run calculation |
| `POST` | `/api/payroll-runs/{id}/approve` | Approve |
| `POST` | `/api/payroll-runs/{id}/finalize` | Finalize + payslips |
| `GET` | `/api/payroll-runs/{id}/items` | Per-employee items |
| `GET` | `/api/payslips` | List payslips |
| `GET` | `/api/payslips/{id}` | Payslip detail |
| `GET` | `/api/payslips/{id}/download` | PDF download (async or stream) |

Attendance is **read** through Attendance summary services during calculate — no attendance write APIs here.

---

## Upsert salary

`PUT /api/employees/{id}/salary`

```json
{
  "amount": "15000000.00",
  "currency": "VND",
  "effective_from": "2026-08-01",
  "strategy": "monthly"
}
```

`strategy`: `monthly` (v1); future `hourly` | `daily` | `commission` | `piece_rate`.  
**Must audit** (`SalaryChanged`).

---

## Create & calculate run

`POST /api/payroll-runs`

```json
{
  "period_start": "2026-07-01",
  "period_end": "2026-07-31",
  "name": "July 2026"
}
```

`POST /api/payroll-runs/{id}/calculate` → **200** draft results (or **202** if queued).

```json
{
  "success": true,
  "data": {
    "id": 9,
    "status": "calculated",
    "employee_count": 120,
    "total_net": "1800000000.00"
  }
}
```

---

## Approve / Finalize

```
POST /api/payroll-runs/{id}/approve
POST /api/payroll-runs/{id}/finalize
```

Finalize is immutable afterward → `PAYROLL_ALREADY_FINALIZED` on illegal edits.  
Generates payslips; notifies employees (queued).

---

## Update & delete run

`PUT /api/payroll-runs/{id}` — update `name`, `period_start`, `period_end` while status is **draft** only.  
Non-draft → `PAYROLL_NOT_DRAFT`. Duplicate period → `PAYROLL_DUPLICATE_PERIOD`.

`DELETE /api/payroll-runs/{id}` — allowed for `draft`, `calculated`, or `approved`.  
Finalized → `PAYROLL_ALREADY_FINALIZED`. Cascades `payroll_items` / `payslips` via FK.

---

## Payslip

`GET /api/payslips/{id}`

```json
{
  "success": true,
  "data": {
    "id": 88,
    "employee_id": 10,
    "period_start": "2026-07-01",
    "period_end": "2026-07-31",
    "gross": "16000000.00",
    "net": "14200000.00",
    "components": [
      { "type": "salary", "label": "Base", "amount": "15000000.00" },
      { "type": "overtime", "label": "OT", "amount": "1000000.00" },
      { "type": "deduction", "label": "Insurance", "amount": "-800000.00" }
    ]
  }
}
```

---

## Error Codes

| Code | When |
|------|------|
| `PAYROLL_ALREADY_FINALIZED` | Mutating finalized run (including delete) |
| `PAYROLL_NOT_DRAFT` | Updating a non-draft run |
| `PAYROLL_NOT_CALCULATED` | Approve before calculate |
| `PAYROLL_DUPLICATE_PERIOD` | Duplicate run for period |
| `PAYROLL_CALCULATION_FAILED` | Engine failure |

---

## Related

- [ATTENDANCE_API.md](./ATTENDANCE_API.md)
- [EMPLOYEE_API.md](./EMPLOYEE_API.md)
- [REPORT_API.md](./REPORT_API.md)
