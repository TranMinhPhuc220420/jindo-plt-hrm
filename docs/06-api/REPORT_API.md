# Report API

> Read-oriented reports and exports across domains.
>
> Business: [../02-business/report/README.md](../02-business/report/README.md)  
> Standard: [REST_STANDARD.md](./REST_STANDARD.md)

---

## Base Paths

```
/api/reports
/api/reports/exports
```

---

## Permissions

| Permission | Use |
|------------|-----|
| `can_view_attendance_reports` | Attendance |
| `can_view_payroll_reports` | Payroll (sensitive) |
| `can_view_leave_reports` | Leave |
| `can_view_employee_reports` | Employee/headcount |
| `can_view_performance_reports` | Performance |
| `can_manage_custom_reports` | Custom definitions |
| `can_export_reports` | Exports |

Reports never become the write path for attendance/leave/payroll.

---

## Endpoints

| Method | Path | Description |
|--------|------|-------------|
| `GET` | `/api/reports/attendance` | Attendance report |
| `GET` | `/api/reports/payroll` | Payroll report |
| `GET` | `/api/reports/leave` | Leave report |
| `GET` | `/api/reports/employees` | Employee/org report |
| `GET` | `/api/reports/departments` | Department report |
| `GET` | `/api/reports/performance` | Performance report |
| `GET` | `/api/reports/custom` | List custom reports |
| `POST` | `/api/reports/custom` | Create custom definition |
| `GET` | `/api/reports/custom/{id}/run` | Run custom report |
| `POST` | `/api/reports/exports` | Queue export |
| `GET` | `/api/reports/exports/{id}` | Export status / download meta |
| `GET` | `/api/dashboard/summary` | Dashboard KPIs (optional aggregate) |

---

## Run report (sync)

`GET /api/reports/attendance?date_from=2026-07-01&date_to=2026-07-31&department_id=3`

```json
{
  "success": true,
  "data": {
    "rows": [
      {
        "employee_id": 10,
        "employee_name": "Jane Doe",
        "present_days": 20,
        "late_minutes": 45,
        "overtime_minutes": 120
      }
    ]
  },
  "meta": {
    "filters": { "date_from": "2026-07-01", "date_to": "2026-07-31" }
  }
}
```

Large result sets should paginate or force export.

---

## Export (async)

`POST /api/reports/exports`

```json
{
  "report": "attendance",
  "format": "csv",
  "filters": {
    "date_from": "2026-07-01",
    "date_to": "2026-07-31"
  }
}
```

**202**

```json
{
  "success": true,
  "message": "Export queued",
  "data": {
    "export_id": "exp_123",
    "status": "queued"
  }
}
```

`GET /api/reports/exports/exp_123` → `status: ready` + download path when done.

---

## Dashboard summary

`GET /api/dashboard/summary`

Supports Stitch Overview KPIs (illustrative):

```json
{
  "success": true,
  "data": {
    "total_employees": 1248,
    "attendance_today_rate": 0.98,
    "pending_requests": 12,
    "new_hires_month": 24
  }
}
```

---

## Error Codes

| Code | When |
|------|------|
| `REPORT_FORBIDDEN` | Missing report permission |
| `REPORT_EXPORT_FAILED` | Job failed |
| `REPORT_FILTER_INVALID` | Bad filters |

---

## Related

- [ATTENDANCE_API.md](./ATTENDANCE_API.md)
- [PAYROLL_API.md](./PAYROLL_API.md)
- [LEAVE_API.md](./LEAVE_API.md)
- [EMPLOYEE_API.md](./EMPLOYEE_API.md)
- [PERFORMANCE_API.md](./PERFORMANCE_API.md)
