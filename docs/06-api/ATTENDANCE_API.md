# Attendance API

> Check-in/out, corrections, approvals, summaries.
>
> Business: [../02-business/attendance/README.md](../02-business/attendance/README.md)  
> Standard: [REST_STANDARD.md](./REST_STANDARD.md)

---

## Base Path

```
/api/attendance
```

---

## Permissions

| Permission | Use |
|------------|-----|
| `can_check_in_out` | Own punches |
| `can_view_attendance` | Lists/summaries (scoped) |
| `can_request_attendance_correction` | Corrections |
| `can_approve_attendance` | Approve records/corrections |
| `can_manage_attendance` | HR overrides |

---

## Endpoints

| Method | Path | Description |
|--------|------|-------------|
| `POST` | `/api/attendance/check-in` | Check in (multipart: location + photo) |
| `POST` | `/api/attendance/check-out` | Check out (multipart: location + photo) |
| `GET` | `/api/attendance/records` | List records |
| `GET` | `/api/attendance/records/{id}` | Detail |
| `GET` | `/api/attendance/records/{id}/evidences/{punchType}/photo` | Stream punch photo |
| `GET` | `/api/attendance/summary` | Summary for period |
| `POST` | `/api/attendance/corrections` | Request correction |
| `GET` | `/api/attendance/corrections` | List corrections |
| `POST` | `/api/attendance/corrections/{id}/approve` | Approve correction |
| `POST` | `/api/attendance/corrections/{id}/reject` | Reject correction |
| `POST` | `/api/attendance/records/{id}/approve` | Approve record (if workflow requires) |

Does **not** create payslips — Payroll consumes summaries via services.

---

## Check-in / Check-out

`POST /api/attendance/check-in`  
`POST /api/attendance/check-out`

**Content-Type:** `multipart/form-data`

| Field | Required | Notes |
|-------|----------|--------|
| `latitude` | yes | -90..90 |
| `longitude` | yes | -180..180 |
| `address` | yes | reverse-geocoded or coordinate fallback, max 500 |
| `photo` | yes | jpeg/png/webp, max 5MB |
| `accuracy_meters` | no | browser GPS accuracy |
| `captured_at` | no | ISO datetime |
| `worked_at` | no | punch time (defaults to now) |
| `note` | no | optional note |
| `source` | no | `manual` (check-in only) |

Location is **recorded only** (no geofence rejection). Missing evidence → **422** `ATTENDANCE_EVIDENCE_REQUIRED` and **no** attendance write.

**200/201** → attendance record for the day/session, including nested `evidences`.

`GET /api/attendance/records/{id}/evidences/{punchType}/photo`  
Streams the private photo (`punchType` = `check_in` \| `check_out`). Authorized via attendance view policy.

---

## Records list

`GET /api/attendance/records?employee_id=&date_from=&date_to=&status=&page=`

```json
{
  "success": true,
  "data": [
    {
      "id": 501,
      "employee_id": 10,
      "work_date": "2026-07-16",
      "check_in_at": "2026-07-16T01:59:00Z",
      "check_out_at": "2026-07-16T11:05:00Z",
      "worked_minutes": 486,
      "late_minutes": 0,
      "overtime_minutes": 30,
      "status": "pending"
    }
  ],
  "meta": { "current_page": 1, "per_page": 20, "total": 20, "last_page": 1 }
}
```

---

## Summary (Payroll input contract)

`GET /api/attendance/summary?employee_id=10&period_start=2026-07-01&period_end=2026-07-31`

```json
{
  "success": true,
  "data": {
    "employee_id": 10,
    "period_start": "2026-07-01",
    "period_end": "2026-07-31",
    "worked_minutes": 9600,
    "late_minutes": 45,
    "overtime_minutes": 120,
    "days_present": 20
  }
}
```

---

## Correction

`POST /api/attendance/corrections`

```json
{
  "attendance_record_id": 501,
  "proposed_check_in_at": "2026-07-16T02:00:00Z",
  "proposed_check_out_at": "2026-07-16T11:00:00Z",
  "reason": "Forgot to check out"
}
```

Approve/reject → audited; notify requester.

---

## Error Codes

| Code | When |
|------|------|
| `ATTENDANCE_EVIDENCE_REQUIRED` | Missing/invalid location or camera photo |
| `ATTENDANCE_ALREADY_CHECKED_IN` | Duplicate check-in |
| `ATTENDANCE_PERIOD_LOCKED` | Cannot correct/approve |
| `ATTENDANCE_INVALID_TRANSITION` | Bad status change |

---

## Related

- [SHIFT_API.md](./SHIFT_API.md)
- [LEAVE_API.md](./LEAVE_API.md)
- [PAYROLL_API.md](./PAYROLL_API.md)
