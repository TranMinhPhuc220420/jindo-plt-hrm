# Shift API

> Shift definitions, assignments, calendars, overtime rules.
>
> Business: [../02-business/shift/README.md](../02-business/shift/README.md)  
> Standard: [REST_STANDARD.md](./REST_STANDARD.md)

---

## Base Paths

```
/api/shifts
/api/shift-assignments
/api/overtime-rules
/api/working-calendar
```

---

## Permissions

| Permission | Use |
|------------|-----|
| `can_view_shifts` | Read definitions/assignments |
| `can_view_own_schedule` | Own calendar |
| `can_manage_shift_definitions` | CRUD shifts |
| `can_assign_shifts` | Assignments |
| `can_manage_overtime_rules` | Overtime rules |

---

## Endpoints

| Method | Path | Description |
|--------|------|-------------|
| `GET` | `/api/shifts` | List shift definitions |
| `POST` | `/api/shifts` | Create definition |
| `GET` | `/api/shifts/{id}` | Detail |
| `PATCH` | `/api/shifts/{id}` | Update |
| `DELETE` | `/api/shifts/{id}` | Soft-delete / disable |
| `GET` | `/api/shift-assignments` | List assignments |
| `POST` | `/api/shift-assignments` | Assign |
| `PATCH` | `/api/shift-assignments/{id}` | Update assignment |
| `DELETE` | `/api/shift-assignments/{id}` | Remove assignment |
| `GET` | `/api/working-calendar` | Resolved calendar for employee/range |
| `GET` | `/api/overtime-rules` | List rules |
| `PUT` | `/api/overtime-rules` | Upsert company rules |

---

## Create shift definition

`POST /api/shifts`

```json
{
  "name": "Morning",
  "code": "MORNING",
  "start_time": "08:00",
  "end_time": "17:00",
  "break_minutes": 60,
  "kind": "standard",
  "is_night": false,
  "is_flexible": false
}
```

`kind`: `standard` | `rotating` | `night` | `flexible` (as modeled).

---

## Assign

`POST /api/shift-assignments`

```json
{
  "employee_id": 10,
  "shift_id": 3,
  "start_date": "2026-08-01",
  "end_date": "2026-08-31"
}
```

Overlap conflicts → `409` / `SHIFT_ASSIGNMENT_OVERLAP`.

---

## Working calendar

`GET /api/working-calendar?employee_id=10&date_from=2026-08-01&date_to=2026-08-07`

```json
{
  "success": true,
  "data": [
    {
      "date": "2026-08-01",
      "shift_id": 3,
      "shift_name": "Morning",
      "start_time": "08:00",
      "end_time": "17:00",
      "is_holiday": false
    }
  ]
}
```

Consumed by Attendance/Leave validation via services (not only by clients).

---

## Error Codes

| Code | When |
|------|------|
| `SHIFT_ASSIGNMENT_OVERLAP` | Conflicting assignment window |
| `SHIFT_IN_USE` | Cannot delete definition still assigned |
| `SHIFT_INVALID_TIME_RANGE` | Bad start/end |

---

## Related

- [ATTENDANCE_API.md](./ATTENDANCE_API.md)
- [LEAVE_API.md](./LEAVE_API.md)
- [PAYROLL_API.md](./PAYROLL_API.md)
