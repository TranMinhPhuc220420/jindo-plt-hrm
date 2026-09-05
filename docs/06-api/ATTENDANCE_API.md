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
| `POST` | `/api/attendance/records/bulk-approve` | Approve many pending records (`ids[]`, max 100) |

Does **not** create payslips — Payroll consumes summaries via services.

---

## Check-in / Check-out

`POST /api/attendance/check-in`  
`POST /api/attendance/check-out`

**Content-Type:** `multipart/form-data`

**Required header:** `Idempotency-Key` — UUID for this punch attempt. Replays with the same key and same request fingerprint return the original `200`/`201` body without a second write. Missing key → **400** `IDEMPOTENCY_KEY_REQUIRED`. Same key with a different body → **409** `IDEMPOTENCY_KEY_REUSE`. Keys are retained for **48 hours**.

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

Location is **recorded only** (no geofence rejection). Missing evidence → **422** `ATTENDANCE_EVIDENCE_REQUIRED` and **no** attendance write. Domain / validation failures do **not** cache an idempotency success response.

Infrastructure failures (`502` `BAD_GATEWAY`, `503` `SERVICE_UNAVAILABLE`, `500` `SERVER_ERROR`, timeouts, offline) should be retried by the client using the **same** `Idempotency-Key` (and/or queued offline). Optional `meta.retry_after` may be present on rate-limit / unavailable responses.

**200/201** → attendance record for the day/session, including nested `evidences`.

Check-in requires:

- Linked employee status `probation` or `active`. Blocked statuses (`suspended` / `resigned` / `archived`) fail authenticated routes with **403** `AUTH_ACCOUNT_INACTIVE` (or `EMPLOYEE_ACCOUNT_INACTIVE` if the punch service is reached without that middleware).
- A shift **window** for `work_date` (assignment date range + weekday mask). Else **422** `ATTENDANCE_NO_SHIFT`.
- Optional `shift_id` to pick a session when several windows exist that day; otherwise the server matches `worked_at` to a window.

One attendance record per `(employee, work_date, shift_id)` — morning and afternoon are separate check-in/out pairs.

Check-out does **not** require a current shift assignment — only an open check-in for that work date.


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

## Bulk approve records

`POST /api/attendance/records/bulk-approve`

Requires `can_approve_attendance`.

```json
{ "ids": [12, 15, 18] }
```

- Max 100 distinct integer ids
- Approves only **pending** records in the actor’s company; other ids are skipped
- Response:

```json
{
  "approved_count": 2,
  "approved_ids": [12, 15],
  "skipped_ids": [18]
}
```

If none of the ids were approvable → `422` `ATTENDANCE_INVALID_TRANSITION`. Each approved record writes `attendance.record_approved` audit.

---

## Error Codes

| Code | When |
|------|------|
| `ATTENDANCE_EVIDENCE_REQUIRED` | Missing/invalid location or camera photo |
| `ATTENDANCE_NO_SHIFT` | No shift assignment covers the work date |
| `EMPLOYEE_ACCOUNT_INACTIVE` | Employee status cannot punch |
| `ATTENDANCE_ALREADY_CHECKED_IN` | Duplicate check-in |
| `ATTENDANCE_PERIOD_LOCKED` | Cannot correct/approve |
| `ATTENDANCE_INVALID_TRANSITION` | Bad status change |

---

## Related

- [SHIFT_API.md](./SHIFT_API.md)
- [LEAVE_API.md](./LEAVE_API.md)
- [PAYROLL_API.md](./PAYROLL_API.md)
