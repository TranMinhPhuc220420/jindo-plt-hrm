# Services

> Where business rules and use-case orchestration live.
>
> Architecture: [BACKEND_ARCHITECTURE.md](../01-architecture/BACKEND_ARCHITECTURE.md)
>
> Principles: [DEVELOPMENT_PRINCIPLES.md](../00-overview/DEVELOPMENT_PRINCIPLES.md)

---

## Purpose

Services own domain behavior. Controllers stay thin. Repositories persist. Events handle reactions.

---

## Types of Services

| Type | Responsibility | Example |
|------|----------------|---------|
| Domain service | Rules for one module | `LeaveApprovalService` |
| Application service | Orchestrate a use case across collaborators | `RunPayrollService` |
| Query/read service | Complex reads / summaries for consumers | `AttendanceSummaryService` |
| Integration/provider service | Replaceable adapters | `ManualAttendanceProvider` |

Prefer reusing an existing service before creating a new one.

---

## Naming

```
app/Services/{Module}/{Name}Service.php

ApproveLeaveService
AttendanceCheckInService
PayrollCalculationService
EmployeeStatusService
```

Use verbs for use-case services; nouns for broader module facades when helpful (`LeaveService` as a facade is OK if it stays cohesive).

---

## Responsibilities

A service may:

- Enforce business invariants
- Open DB transactions for multi-step writes
- Call repositories and other **allowed** module services
- Dispatch domain events after success
- Return domain results / DTOs for controllers to transform

A service must not:

- Read request globals / session directly (pass explicit inputs)
- Return HTTP responses / status codes
- Bypass policies as the only authorization layer (controllers/requests still authorize; services may re-check critical invariants)
- Import another module’s repository/tables when a service API exists
- Duplicate logic that already lives elsewhere

---

## Transaction Pattern

```
start transaction
  validate domain preconditions
  persist via repositories
  commit
dispatch events (prefer after commit)
```

If a listener must run inside the transaction, document why and keep it safe/idempotent.

---

## Cross-Module Calls

Allowed examples:

```
PayrollCalculationService
  → AttendanceSummaryService
  → LeaveService (unpaid effects)
  → EmployeeSalaryRepository (own module)
```

Forbidden examples:

```
AttendanceCheckInService
  → PayrollItemRepository   // upward / wrong ownership
```

See [DEPENDENCY_RULES.md](../01-architecture/DEPENDENCY_RULES.md).

---

## Replaceable Strategies

For known future variants, keep a stable service contract:

| Module | Stable API | Swappable interior |
|--------|------------|--------------------|
| Attendance | check-in / summaries | Manual, GPS, biometric providers |
| Payroll | calculate(run) | Monthly, hourly, commission strategies |

---

## Auditability

Important mutations finish by ensuring audit side effects fire (directly or via events):

- Employee edited
- Salary changed
- Attendance approved
- Leave rejected
- Asset assigned

---

## Testing

- Unit-test services with faked repositories/collaborators
- Feature-test the HTTP path separately
- Each module’s services should be independently testable

See [TESTING.md](./TESTING.md).

---

## Anti-Patterns

1. “God” service with every module inside
2. Service that is only a pass-through to Eloquent with no rules (then simplify)
3. Copy-pasting the same approval flow into three services
4. Controllers calling three repositories instead of one service use case

---

## Related Documents

- [ACTIONS.md](./ACTIONS.md)
- [REPOSITORIES.md](./REPOSITORIES.md)
- [EVENTS.md](./EVENTS.md)
- [../02-business/](../02-business/)
