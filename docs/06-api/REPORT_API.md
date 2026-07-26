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

Authenticated home dashboard. Scope depends on permissions:

| Gate | `scope` | Meaning |
|------|---------|---------|
| Has `can_view_employee_reports` | `company` | Admin / HR / Manager company overview |
| Otherwise | `self` | Employee personal dashboard only |

Does not include payroll money totals. No separate report permission is required beyond the gate above.

### Company scope (`scope: "company"`)

```json
{
  "success": true,
  "data": {
    "scope": "company",
    "active_employees": 42,
    "attendance_today_rate": 0.95,
    "pending_leave_requests": 3,
    "new_hires_month": 2,
    "open_payroll_runs": 1,
    "unread_notifications": 4,
    "attendance_last_7_days": [
      { "date": "2026-07-20", "label": "Mon", "present": 40, "expected": 42, "rate": 0.9524 }
    ],
    "employees_by_status": [
      { "status": "active", "count": 42 }
    ],
    "employees_by_department": [
      { "department_id": 1, "name": "Engineering", "count": 20 }
    ],
    "recent_hires": [],
    "pending_actions": [
      { "key": "pending_leave", "count": 3, "href": "/leave" }
    ],
    "upcoming": [
      { "kind": "holiday", "date": "2026-07-28", "title": "Company Day" }
    ],
    "recent_activity": []
  }
}
```

### Self scope (`scope: "self"`)

```json
{
  "success": true,
  "data": {
    "scope": "self",
    "employee": {
      "id": 3,
      "code": "E-0003",
      "full_name": "Jane Doe",
      "department_name": "Engineering",
      "status": "active"
    },
    "unread_notifications": 2,
    "today_attendance": {
      "id": 10,
      "work_date": "2026-07-26",
      "check_in_at": "2026-07-26T01:00:00+00:00",
      "check_out_at": null,
      "worked_minutes": null,
      "status": "open"
    },
    "checked_in_today": true,
    "pending_leave_requests": 1,
    "leave_balances": [
      {
        "leave_type_id": 1,
        "leave_type_code": "AL",
        "leave_type_name": "Annual leave",
        "remaining": 10.0,
        "entitled": 12.0,
        "used": 1.0,
        "pending": 1.0
      }
    ],
    "my_attendance_last_7_days": [
      { "date": "2026-07-20", "label": "Mon", "present": 1, "worked_minutes": 480 }
    ],
    "upcoming": [
      { "kind": "leave", "date": "2026-07-29", "title": "Annual leave" }
    ],
    "pending_actions": [
      { "key": "my_pending_leave", "count": 1, "href": "/leave" }
    ],
    "recent_activity": []
  }
}
```

| Field | Notes |
|-------|--------|
| `attendance_today_rate` | Company only — present today / active headcount |
| `my_attendance_last_7_days` | Self only — personal check-in presence |
| `leave_balances` | Self only — current year period |
| `upcoming` | Company: all holidays + approved leave; Self: holidays + own leave |
| `recent_activity` | Actor’s latest notifications |

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
