# Shift

> Shift definitions, assignments, working calendars, and overtime rules.
>
> Source of truth: [PROJECT_LOGIC.md](../../00-overview/PROJECT_LOGIC.md) §6 Shift

---

## Purpose

Define when employees are expected to work. Attendance, Leave, and Payroll overtime interpretation consume shift/calendar data through services.

---

## Responsibilities

| Area | Description |
|------|-------------|
| Shift Definition | Named templates (start/end, break expectations, flags) |
| Shift Assignment | Map shifts to employees / groups / date ranges |
| Working Calendar | Planned work pattern over time |
| Rotating Shift | Cycling patterns across definitions |
| Night Shift | Night-spanning schedules |
| Flexible Shift | Flexible window constraints |
| Overtime Rule | Rules for when overtime applies |

---

## Business Rules

1. Shift definitions and assignments are company-scoped.
2. An employee’s expected schedule for a date comes from assignment + weekday mask + calendar, not from ad-hoc UI guesses in Attendance.
3. Multiple assignments may cover the same dates when weekdays and/or shift time windows do not overlap (part-time days and morning + afternoon sessions).
3. Rotating / night / flexible variants are types or strategies under one shift module — not separate apps.
4. Overtime rules here define schedule-side policy; payroll rates/amount calculation stay in Payroll.
5. Changing assignments that affect past locked attendance periods should be restricted or require controlled recalculation flows.
6. Leave validation and attendance late/early checks should ask Shift services for expected windows.

---

## Key Workflows

### Define shift

```
HR/Admin creates shift definition
  → Validate times/rules → Persist → Available for assignment
```

### Assign shift

```
Assign to employee or group + date range
  → Detect overlaps → Persist assignment
    → Working calendar reflects assignment
```

### Publish / adjust calendar

```
Update rotating pattern or exceptions
  → Validate → Persist → Notify affected employees if required
```

---

## Dependencies

| May depend on | Must not depend on |
|---------------|--------------------|
| Employee / Organization | Payroll calculation |
| Authorization | Attendance punch ownership |
| | Leave balance ownership |

Consumers: Attendance, Leave, Payroll (overtime inputs), Reports.

---

## Permissions (illustrative)

| Permission | Intent |
|------------|--------|
| `can_view_shifts` | View definitions/assignments |
| `can_manage_shift_definitions` | Create/update shift templates |
| `can_assign_shifts` | Assign shifts to employees |
| `can_manage_overtime_rules` | Configure overtime rules |
| `can_view_own_schedule` | Employee self-service schedule |

---

## Events & Side Effects

| Event (example) | Reaction |
|-----------------|----------|
| `ShiftAssigned` | Notify employee; calendar updated |
| `ShiftAssignmentChanged` | Notify; downstream consumers read new schedule |
| `OvertimeRuleChanged` | Audit |

---

## Out of Scope / Future

- AI-generated roster optimization
- External workforce management integrations

---

## Related Documents

- [../attendance/](../attendance/)
- [../leave/](../leave/)
- [../payroll/](../payroll/)
- `docs/06-api/SHIFT_API.md`
