# Leave API

> Leave types, balances, requests, holidays, weekend rules.
>
> Business: [../02-business/leave/README.md](../02-business/leave/README.md)  
> Standard: [REST_STANDARD.md](./REST_STANDARD.md)

---

## Base Paths

```
/api/leave-types
/api/leave-balances
/api/leave-requests
/api/holidays
/api/weekend-rules
```

---

## Permissions

| Permission | Use |
|------------|-----|
| `can_request_leave` | Create/cancel own requests |
| `can_view_leave` | View (scoped) |
| `can_approve_leave` | Approve/reject |
| `can_manage_leave_types` | CRUD leave types |
| `can_manage_leave_balances` | Manual balance adjust |
| `can_manage_holidays` | Holidays / weekend rules |

---

## Endpoints

| Method | Path | Description |
|--------|------|-------------|
| `GET` | `/api/leave-types` | List types |
| `POST` | `/api/leave-types` | Create type |
| `PATCH` | `/api/leave-types/{id}` | Update type |
| `GET` | `/api/leave-balances` | Balances (`employee_id`, `year`) |
| `POST` | `/api/leave-balances/adjust` | Manual adjustment |
| `GET` | `/api/leave-requests` | List requests |
| `POST` | `/api/leave-requests` | Create request |
| `GET` | `/api/leave-requests/{id}` | Detail |
| `POST` | `/api/leave-requests/{id}/cancel` | Cancel |
| `POST` | `/api/leave-requests/{id}/approve` | Approve |
| `POST` | `/api/leave-requests/{id}/reject` | Reject |
| `GET` | `/api/holidays` | Company holidays |
| `POST` | `/api/holidays` | Create holiday |
| `DELETE` | `/api/holidays/{id}` | Remove holiday |
| `GET` | `/api/weekend-rules` | Weekend rules |
| `PUT` | `/api/weekend-rules` | Upsert weekend rules |

---

## Create request

`POST /api/leave-requests`

```json
{
  "leave_type_id": 1,
  "unit": "day",
  "start_date": "2026-08-10",
  "end_date": "2026-08-12",
  "is_half_day": false,
  "reason": "Family trip"
}
```

`unit`: `day` | `half_day` | `hour` (hourly may use `start_at`/`end_at` instead).

**201** → request `status: pending`.

---

## Approve / Reject

`POST /api/leave-requests/{id}/approve`

```json
{ "note": "optional" }
```

`POST /api/leave-requests/{id}/reject`

```json
{ "reason": "Team coverage" }
```

Reject is auditable. Insufficient balance on approve → `LEAVE_BALANCE_INSUFFICIENT`.

---

## Balances

`GET /api/leave-balances?employee_id=10&year=2026`

```json
{
  "success": true,
  "data": [
    {
      "leave_type_id": 1,
      "leave_type_name": "Annual",
      "entitled": 12,
      "used": 3,
      "pending": 1,
      "remaining": 8
    }
  ]
}
```

---

## Error Codes

| Code | When |
|------|------|
| `LEAVE_BALANCE_INSUFFICIENT` | Not enough balance |
| `LEAVE_INVALID_TRANSITION` | Bad status change |
| `LEAVE_OVERLAPPING_REQUEST` | Overlaps another request |
| `LEAVE_INVALID_DATES` | Date/holiday/weekend rule failure |

---

## Related

- [SHIFT_API.md](./SHIFT_API.md)
- [ATTENDANCE_API.md](./ATTENDANCE_API.md)
- [NOTIFICATION_API.md](./NOTIFICATION_API.md)
