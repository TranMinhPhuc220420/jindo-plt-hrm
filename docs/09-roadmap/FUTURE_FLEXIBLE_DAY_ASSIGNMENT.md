# Future — Flexible day-slot assignment

> Employee-first roster: pick an employee, pick dates, pick time windows per day.
>
> Living status: [PROGRESS.md](./PROGRESS.md) step `∞.flexible-day-slot-assignment`  
> Business: [../02-business/shift/README.md](../02-business/shift/README.md)  
> API: [../06-api/SHIFT_API.md](../06-api/SHIFT_API.md)

---

## Problem

Admin assignment today is **template-first**:

1. Create a named shift definition with fixed `start_time` / `end_time`.
2. Open that shift’s detail page.
3. Assign an employee + date range + weekday mask.

The clock always comes from the template. That works for full-time patterns (every weekday 08:00–17:00) and repeating part-time patterns (Mon/Wed/Fri on a named window).

It does **not** support ad-hoc flexible hours:

> Select employee A → pick Monday and 08:00–12:00 → pick Tuesday and 13:00–17:00 → continue for the rest of the working days this week.

Different days can have different windows. A day can have two sessions. A day can be off. Admin should not have to invent a new named template for every unique clock.

---

## Goal

HR/Admin can plan one employee’s week by **date + time window**, without creating shift templates first. Attendance, Leave, and Payroll keep consuming `WorkingCalendarService` windows (still keyed by `shift_id`).

---

## Non-goals (this slice)

- AI roster optimization
- Drag-and-drop multi-employee board
- Editing or cancelling **recurring** template assignments from this planner (those stay on `/shifts/{id}`)
- Blocking edits against locked attendance periods (Shift must not own Attendance records; follow-up if a shared lock service appears)
- Rotating-cycle engine

---

## Decision: reuse assignments, do not add a parallel roster table

| Option | Verdict |
|--------|---------|
| New `shift_day_windows` table with optional `shift_id` | Rejected for v1 — Attendance unique key and punch matching are `(company, employee, work_date, shift_id)` |
| Auto-create named templates only, keep old UI | Insufficient — still template-first UX |
| **One-day `shift_assignments` with `source=adhoc` + find-or-reuse a matching shift window** | **Chosen** |

Each planned slot becomes:

- `start_date` = `end_date` = that calendar date
- `weekdays` = `[dayOfWeek]` for that date
- `source` = `adhoc`
- `shift_id` = an existing company shift with the same clocks, or a generated `FLEX-HHmm-HHmm` template (`is_generated=true`)

`WorkingCalendarService` already expands assignments into per-day windows. Overlap remains date ∩ weekday ∩ time.

### Recurring vs ad-hoc

| `source` | Written by | Typical shape |
|----------|------------|----------------|
| `recurring` (default) | `/shifts/{id}` assign form, existing POST | Range + weekday mask + named template |
| `adhoc` | Flexible week PUT | Single date + resolved window |

Saving a week **replaces only `adhoc` rows** in `[date_from, date_to]`. Recurring rows are never deleted by this API. Overlap against recurring still returns `409` / `SHIFT_ASSIGNMENT_OVERLAP`.

### Window identity (punch matching)

1. Prefer an **active non-generated** shift whose `start_time`/`end_time` match (e.g. MORNING 08:00–17:00).
2. Else reuse an active generated `FLEX-*` with those clocks.
3. Else create `code=FLEX-0800-1200`, `name=08:00–12:00`, `kind=flexible`, `is_flexible=true`, `is_night` when the window crosses midnight, `is_generated=true`, `break_minutes=0`.

Generated shifts are hidden from `GET /api/shifts` unless `include_generated=1`. They cannot be edited (times/code). Delete still follows `SHIFT_IN_USE`.

---

## API

`PUT /api/flexible-schedules`  
Permission: `can_assign_shifts`

```json
{
  "employee_id": 10,
  "date_from": "2026-09-07",
  "date_to": "2026-09-13",
  "slots": [
    { "date": "2026-09-07", "start_time": "08:00", "end_time": "12:00" },
    { "date": "2026-09-08", "start_time": "13:00", "end_time": "17:00" },
    { "date": "2026-09-10", "start_time": "08:00", "end_time": "12:00" },
    { "date": "2026-09-10", "start_time": "14:00", "end_time": "16:00" }
  ]
}
```

Rules:

- Range inclusive, max **14** days.
- Every `slots[].date` must lie in the range.
- Max **4** slots per date.
- Empty `slots` clears ad-hoc rows in the range (employee is off those days unless a recurring assignment still applies).
- Same-day payload clocks must not overlap (night windows allowed when `end < start`).
- Inactive employees → `422` / `SHIFT_EMPLOYEE_INACTIVE`.
- One audit: `shift.flexible_schedule_replaced`. One `ShiftAssignmentChanged` notification for the employee (not one inbox item per slot).

Working calendar windows gain `source`: `recurring` | `adhoc`.

---

## UI

New page `/shifts/assign` (route registered **before** `/shifts/{id}`).

Flow:

1. Pick employee (shared picker). Optional `?employee_id=` deep link.
2. Monday–Sunday week navigator (company week starts Monday).
3. Per day: add/remove time windows (`TimePicker`). Recurring template windows show as read-only chips.
4. Save week → PUT replace-set.
5. Copy previous week (adhoc slots only) as a client convenience.

Entry points:

- `/shifts` header action “Assign by day”
- Employee profile schedule section

Employees still see the result on **My Schedule**; no employee-facing editor.

---

## Implementation order

1. Docs + PROGRESS kickoff
2. Migration: `shifts.is_generated`, `shift_assignments.source`
3. `FlexibleShiftWindowService` + `ShiftAssignmentService::replaceAdhocRange`
4. PUT API + calendar `source` + hide generated definitions
5. Feature tests
6. React planner + i18n + links
7. `types:check` + browser smoke

---

## Exit criteria

- [ ] Admin can select employee → dates → time windows and persist a week
- [ ] Two non-overlapping windows on the same date produce two calendar windows / punch records
- [ ] Recurring template assignments survive a flexible save
- [ ] Overlap with recurring or payload clocks → `409`
- [ ] Generated `FLEX-*` templates do not clutter the shift catalog
- [ ] Working calendar + My Schedule show the new windows
- [ ] Feature tests + `types:check` green
- [ ] en/vi copy for the planner

---

## Related

- [PHASE_05_SHIFT.md](./PHASE_05_SHIFT.md)
- Existing weekday-mask work: PROGRESS `∞.flexible-parttime-assignments`
