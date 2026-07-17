# Authorization

> Access control architecture: roles, permissions, policies, and UI visibility.
>
> Source of truth: [PROJECT_LOGIC.md](../00-overview/PROJECT_LOGIC.md) §6 Authorization
>
> Backend policy conventions: `docs/04-backend/POLICIES.md`

---

## Purpose

Define how the system decides what an authenticated user can see and do. Authorization is **permission first** — never hardcode business access by role name.

---

## Responsibilities

From project logic:

- Role
- Permission
- Policy
- Access Control
- Feature Visibility
- Menu Visibility
- Action Authorization

---

## Core Model

```
User
  └── Role(s)
        └── Permission(s)
              └── checked by Policies / gates on actions & resources
```

| Concept | Meaning |
|---------|---------|
| Permission | Stable capability string, e.g. `can_create_employee` |
| Role | Named bundle of permissions (HR, Manager, Employee, Admin, …) |
| Policy | Server-side evaluation for a resource/action |
| Feature / Menu visibility | UI filtering based on permissions (not security by itself) |

---

## Permission Catalog

Canonical list of all `can_*` keys: **[PERMISSIONS_CATALOG.md](./PERMISSIONS_CATALOG.md)**.

| Actor (typical) | Example permissions |
|-----------------|---------------------|
| HR | `can_create_employee` |
| Manager | `can_approve_leave` |
| Employee | `can_view_salary` (own payslip, subject to policy) |

Permissions should be:

- Explicit
- Stable across releases when possible
- Listed in the catalog before use in code
- Checked on the server for every protected action
- Used by the UI only for presentation

---

## Enforcement Layers

```
1. Route / middleware — authenticated + optional coarse ability
2. Form Request / Controller — authorize before mutation
3. Policy — resource-aware decision (own vs team vs company)
4. Service — may re-check critical invariants (defense in depth)
5. UI — hide/disable actions the user lacks permission for
```

Rule: **UI hiding is not authorization.** The API must reject forbidden actions.

---

## Policy Guidelines

Policies may consider:

- Permissions on the user
- Resource ownership (own employee record, own leave request)
- Organizational relationships (manager of employee, department head)
- Company scope (multi-company ready)
- Resource status (cannot approve an already-approved leave)

Policies must not:

- Call other modules’ repositories as a shortcut around services when complex cross-domain rules are needed — prefer domain services
- Branch on role display names (`if role === 'HR'`) instead of permissions

---

## Feature & Menu Visibility

- Backend exposes the user’s effective permission set (or derived capability map) after login / `/me`.
- Frontend builds menus and action buttons from that set.
- Same permission keys are used in web and future mobile clients.

---

## Roles administration (business home)

There is no separate `02-business/roles/` folder — **role & permission administration is owned by Authorization**.

| Concern | Detail |
|---------|--------|
| Purpose | Create/update roles, attach catalog permissions, assign roles to users |
| Permissions | `can_view_roles`, `can_manage_roles`, `can_assign_roles` (see catalog) |
| HTTP | [ROLES_API.md](../06-api/ROLES_API.md) |
| UI | `/roles` — [ROUTING.md](../05-frontend/ROUTING.md) |
| Seed | [SEEDING.md](../03-database/SEEDING.md) + full [PERMISSIONS_CATALOG.md](./PERMISSIONS_CATALOG.md) |
| Related UX | Often grouped with Organization / Settings / Audit in the Admin sidebar |

Domain modules declare the permissions they require; this admin surface only **stores and assigns** them. Never authorize business actions by role display name.

---

## Module Ownership

| Concern | Owner |
|---------|-------|
| Role/permission administration | Authorization (this doc + ROLES_API) |
| Checking a leave approval | Leave policy + `can_approve_leave` (and relationship rules) |
| Checking employee create | Employee policy + `can_create_employee` |
| Report access | Report policies + report permissions |
| Org structure CRUD | Organization module |
| Company/module config | Settings module |
| Audit trail reads | Audit module (`can_view_audit_logs`) |

Domain modules declare the permissions they require; Authorization module stores and assigns them.

---

## Auditability

Authorization failures may be logged at a security-appropriate level.
Authorized sensitive actions (salary change, leave reject, asset assign, etc.) must still produce business audit logs as defined in project principles.

---

## Anti-Patterns

1. `if ($user->role === 'admin')` in services/controllers
2. Trusting only frontend route guards
3. One mega-policy file for all domains
4. Duplicating permission strings with slightly different names per module
5. Granting broad “do everything” permissions to bypass modeling real capabilities

---

## Related Documents

- [PERMISSIONS_CATALOG.md](./PERMISSIONS_CATALOG.md)
- [AUTHENTICATION.md](./AUTHENTICATION.md)
- [../06-api/ROLES_API.md](../06-api/ROLES_API.md)
- [DEPENDENCY_RULES.md](./DEPENDENCY_RULES.md)
- [API_ARCHITECTURE.md](./API_ARCHITECTURE.md)
- [FRONTEND_ARCHITECTURE.md](./FRONTEND_ARCHITECTURE.md)
- [../00-overview/DEVELOPMENT_PRINCIPLES.md](../00-overview/DEVELOPMENT_PRINCIPLES.md)
- [../04-backend/POLICIES.md](../04-backend/POLICIES.md)
