# Policies

> Laravel policy conventions for permission-first authorization.
>
> Architecture: [AUTHORIZATION.md](../01-architecture/AUTHORIZATION.md)

---

## Purpose

Policies answer: **can this user perform this action on this resource?**  
They combine permission keys with resource/relationship rules (own, team, company).

---

## Location & Naming

```
app/Policies/{Entity}Policy.php

EmployeePolicy
LeaveRequestPolicy
AttendanceRecordPolicy
PayrollRunPolicy
AssetPolicy
DocumentPolicy
```

Register in `AuthServiceProvider` / discovered policy conventions.

---

## Permission-First Rule

Policies check **permissions**, not role display names.

```
// good
$user->can('can_approve_leave') && $this->manages($user, $leave->employee)

// bad
$user->role === 'HR'
```

Stable permission examples:

- `can_create_employee`
- `can_approve_leave`
- `can_view_salary`
- `can_approve_attendance`
- `can_assign_asset`

---

## Standard Ability Methods

Map to resource verbs where possible:

| Method | Typical meaning |
|--------|-----------------|
| `viewAny` | List resources |
| `view` | View one |
| `create` | Create |
| `update` | Update |
| `delete` | Delete / archive |
| `approve` | Domain approval (custom) |
| `reject` | Domain rejection (custom) |
| `export` | Export/report (custom) |

Custom abilities are encouraged for domain language (`approve`, `checkIn`, `finalize`).

---

## Scope Rules Inside Policies

Policies may consider:

1. Permission key presence
2. Company scope (same company / multi-company ready)
3. Ownership (own employee profile, own leave request)
4. Org relationship (manager of employee, department head)
5. Resource status (cannot approve already approved)

Keep heavy data loading out of policies when possible — pass what you need or use cheap existence checks.

---

## Where Authorization Runs

```
1. Middleware — authenticated
2. Form Request authorize() / controller authorize()
3. Policy ability
4. Service re-check for critical invariants (optional defense in depth)
```

UI hiding is never enough. See [FRONTEND_ARCHITECTURE.md](../01-architecture/FRONTEND_ARCHITECTURE.md).

---

## Module Ownership

| Module | Policy owns decisions for |
|--------|---------------------------|
| Employee | Employee profile field groups / status changes |
| Leave | Request view/create/approve/reject |
| Attendance | Punch/correction/approve |
| Payroll | Run/view payslip/manage salary |
| Documents | Upload/download by owner type |
| Reports | Which report families are visible |

Authorization module stores roles/permissions; domain policies consume them.

---

## Guest & Self-Service

- Guest: only auth endpoints
- Employee self-service: `view`/`create` on own resources with own-scope checks
- Sensitive fields (bank, tax, salary): separate permissions / policy branches

---

## Testing

- Unit-test policies with representative users (employee, manager, HR, admin)
- Feature tests assert 403 for forbidden actions
- Cover relationship edge cases (manager of A cannot approve B)

---

## Anti-Patterns

1. Role name conditionals scattered in services
2. Huge policy class handling unrelated modules
3. Policy performing writes / sending email
4. Duplicating the same permission string with typos across modules

---

## Related Documents

- [VALIDATION.md](./VALIDATION.md)
- [../01-architecture/AUTHORIZATION.md](../01-architecture/AUTHORIZATION.md)
- [../01-architecture/AUTHENTICATION.md](../01-architecture/AUTHENTICATION.md)
- [../03-database/SEEDING.md](../03-database/SEEDING.md)
