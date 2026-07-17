# Leave

> Time-off requests, balances, approvals, and calendar rules.
>
> Source of truth: [PROJECT_LOGIC.md](../../00-overview/PROJECT_LOGIC.md) §6 Leave

---

## Purpose

Manage leave entitlements and requests so employees can take time off under company rules, with clear approval and balance tracking. Leave collaborates with Shift/Attendance for calendar interpretation but does not own punches or payslips.

---

## Responsibilities

| Area | Description |
|------|-------------|
| Leave Request | Create/submit/cancel requests |
| Leave Type | Categories (annual, sick, unpaid, …) |
| Leave Balance | Entitlement remaining per type/period |
| Leave Approval | Approve / reject workflow |
| Holiday | Company holidays affecting leave/work |
| Weekend Rules | Weekend work/rest handling |
| Compensation Leave | Leave earned from extra work |
| Half-day Leave | Half working-day units |
| Hourly Leave | Hour-based leave units |

---

## Business Rules

1. Requests always reference an employee, leave type, and time range/unit.
2. Balance checks run before approval becomes final (policy may allow negative only if explicitly configured — default is reject when insufficient).
3. Approval is permission-based (`can_approve_leave`), typically manager/HR with org relationship checks in policy.
4. Rejecting leave is auditable.
5. Holidays and weekend rules are company-scoped configuration used during validation.
6. Half-day and hourly leave must use consistent unit conversion against the working calendar/shift definitions via services.
7. Approved leave may be consulted by Attendance when interpreting a day; Leave does not rewrite attendance punches.

---

## Key Workflows

### Request leave

```
Employee submits request
  → Validate type/unit/dates against holidays & rules
    → Check balance (preview)
      → Persist as pending → Notify approver
```

### Approve / reject

```
Approver decides
  → Authorize (permission + relationship)
    → If approve: deduct/reserve balance → status approved → Audit → Notify
    → If reject: status rejected → Audit → Notify
```

### Balance maintenance

```
Accrual / admin adjustment / compensation grant
  → Validate → Update balance ledger → Audit if manual
```

---

## Dependencies

| May depend on | Must not depend on |
|---------------|--------------------|
| Employee | Payroll writes |
| Shift / working calendar (validation) | Owning attendance punch tables |
| Organization relationships (approver) | Report module for approval path |
| Notifications (side effect) | |

Attendance may read approved leave. Neither module absorbs the other’s write model.

---

## Permissions (illustrative)

| Permission | Intent |
|------------|--------|
| `can_request_leave` | Submit own leave |
| `can_view_leave` | View leave data (scoped) |
| `can_approve_leave` | Approve/reject requests |
| `can_manage_leave_types` | Configure leave types |
| `can_manage_leave_balances` | Manual balance adjustments |
| `can_manage_holidays` | Configure holidays / weekend rules |

---

## Events & Side Effects

| Event (example) | Reaction |
|-----------------|----------|
| `LeaveRequested` | Notify approver |
| `LeaveApproved` | Audit; notify employee; balance finalized |
| `LeaveRejected` | Audit; notify employee |
| `LeaveCancelled` | Restore balance if policy requires; notify |

---

## Out of Scope / Future

- Platform-wide generic approval engine (module-local approvals first)
- Complex multi-step enterprise workflows (later Workflow Builder)

---

## Related Documents

- [../shift/](../shift/)
- [../attendance/](../attendance/)
- [../employee/](../employee/)
- [../../01-architecture/DEPENDENCY_RULES.md](../../01-architecture/DEPENDENCY_RULES.md)
- `docs/06-api/LEAVE_API.md`
