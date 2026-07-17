# Actions

> Single-purpose invokable units for focused application operations.
>
> Related: [SERVICES.md](./SERVICES.md) · [LARAVEL_STRUCTURE.md](./LARAVEL_STRUCTURE.md)

---

## Purpose

Actions encapsulate **one** operation with a clear input/output. Use them to keep call sites thin without turning every method into an unstructured script.

The project already uses Actions in places (e.g. Fortify). HRM domain code should follow the rules below so Actions and Services do not overlap chaotically.

---

## Action vs Service

| Prefer Action when | Prefer Service when |
|--------------------|---------------------|
| One verb, one outcome | Multiple related operations / module API |
| Useful from controller, job, and command | Shared domain rules reused widely |
| Thin orchestration around existing services | Holds substantial invariants/transactions |
| Framework integration hooks (Fortify-style) | Cross-entity workflows (run payroll, approve leave) |

Rules of thumb:

1. **Reuse Services before creating Actions.**
2. An Action may call Services; a Service should not need an Action to exist.
3. If an Action grows many private rule methods, promote rules into a Service.
4. Do not create both `ApproveLeaveAction` and `ApproveLeaveService` that duplicate the same logic.

---

## Naming & Location

```
app/Actions/{Module}/{Verb}{Entity}Action.php

ApproveLeaveRequestAction
CheckInAttendanceAction
FinalizePayrollRunAction
AssignAssetAction
```

Invokable style:

```php
public function __invoke(ApproveLeaveData $data): LeaveRequest
{
    // authorize already done at HTTP edge when called from controller
    return $this->leaveApprovalService->approve($data);
}
```

---

## Responsibilities

An Action may:

- Coordinate a single use case
- Accept a DTO / typed args (not a raw HTTP Request when avoidable)
- Call one or more services/repositories within dependency rules
- Dispatch a domain event after success (or let the service do it — pick one place)

An Action must not:

- Become a second controller (no response building)
- Bypass Form Request validation for HTTP entry points
- Own large policy matrices (use Policies)
- Reach across forbidden module boundaries

---

## HTTP Entry Pattern

```
Route → Controller
  → Form Request (validate + authorize)
    → Action / Service
      → Repository
        → Event
```

Controllers may call either Action or Service. Prefer consistency within a module.

---

## Non-HTTP Entry Pattern

Actions are useful when the same operation is triggered from:

- Queue jobs
- Artisan commands
- Domain listeners (carefully — avoid cycles)
- Fortify / auth scaffolding hooks

Keep inputs explicit so Actions stay HTTP-agnostic.

---

## Testing

- Unit-test Actions when they contain orchestration branches
- Otherwise test the underlying Service thoroughly and cover the HTTP path with Feature tests

---

## Anti-Patterns

1. Action per tiny setter with no logic (`UpdateNameAction` wrapping one assignment)
2. Action folders mirroring every REST verb without domain meaning
3. Fat Actions that reimplement Service layers
4. Actions importing foreign module repositories

---

## Related Documents

- [SERVICES.md](./SERVICES.md)
- [VALIDATION.md](./VALIDATION.md)
- [EVENTS.md](./EVENTS.md)
- [POLICIES.md](./POLICIES.md)
