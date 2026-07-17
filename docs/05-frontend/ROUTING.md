# Routing

> URL structure, guards, and navigation mapping for the HRM app.
>
> Shell reference: Stitch **HRM Dashboard - Overview**  
> Permissions: [PERMISSIONS_CATALOG.md](../01-architecture/PERMISSIONS_CATALOG.md)  
> Stack: [STACK_DECISION.md](../01-architecture/STACK_DECISION.md)

---

## Principles

1. Routes map to pages; pages compose features.
2. Auth-required routes sit inside the app shell layout.
3. Menu visibility is permission-based — not role-name strings in the router.
4. Deep links should open the same screen state (filters via query where useful).
5. Unknown routes → friendly 404 inside shell (if authenticated) or public 404.

---

## Route Groups

| Group | Path prefix | Layout | Auth |
|-------|-------------|--------|------|
| Public auth | `/login`, `/forgot-password`, … | `auth-layout` | Guest |
| App | `/`, `/employees`, … | `app-layout` | Authenticated |
| Admin / config | `/organization`, `/roles`, `/settings/*`, `/audit-logs` | `app-layout` (+ optional settings subnav) | Authenticated + permissions |

---

## Canonical App Routes (v1 map)

Permission gates use keys from [PERMISSIONS_CATALOG.md](../01-architecture/PERMISSIONS_CATALOG.md).

### Foundation (Phase 01)

| Path | Page | Sidebar label | Permission gate |
|------|------|---------------|-----------------|
| `/` or `/dashboard` | Dashboard | Dashboard | authenticated |
| `/organization` | Org tree / units | Organization | `can_view_organization` |
| `/organization/branches` | Branches (optional nested) | — | `can_view_organization` |
| `/roles` | Roles & permissions admin | Roles | `can_view_roles` |
| `/settings` | Settings hub | Settings | `can_view_settings` |
| `/settings/:group` | Settings group (`company`, `auth`, …) | — | `can_view_settings` |
| `/audit-logs` | Audit trail | Audit | `can_view_audit_logs` |

Mutations on these screens still require the matching `can_manage_*` / `can_assign_roles` / `can_manage_company` keys (enforced by API).

### Domain modules

| Path | Page | Sidebar label | Permission gate |
|------|------|---------------|-----------------|
| `/employees` | Employee list | Employees | `can_view_employee` |
| `/employees/:id` | Employee detail | — | `can_view_employee` |
| `/employees/create` | Create employee | — | `can_create_employee` |
| `/attendance` | Attendance | Time & Attendance | `can_view_attendance` |
| `/leave` | Leave | (under Time or own item) | `can_view_leave` |
| `/shifts` | Shifts | — | `can_view_shifts` |
| `/payroll` | Payroll | Payroll | `can_view_payroll_history` / related |
| `/recruitment` | Recruitment | Recruitment | `can_view_candidates` |
| `/onboarding` | Onboarding | — | `can_view_onboarding` |
| `/performance` | Performance | — | `can_view_performance` |
| `/assets` | Assets | — | `can_view_assets` |
| `/documents` | Documents | — | `can_view_company_documents` / employee docs |
| `/notifications` | Notification inbox | — | `can_view_own_notifications` |
| `/reports` | Reports | — | report permissions |

Exact menu IA can evolve; keep path names stable once shipped.

---

## Settings subnav (optional)

When using `settings-layout` nested nav:

| Path | Label | Gate |
|------|-------|------|
| `/settings` or `/settings/company` | Company | `can_view_settings` |
| `/settings/auth` | Auth / session | `can_view_settings` |
| `/organization` | Organization | `can_view_organization` |
| `/roles` | Roles | `can_view_roles` |
| `/audit-logs` | Audit | `can_view_audit_logs` |

Organization / Roles / Audit may also appear as top-level sidebar items (preferred for Phase 01 discoverability). Settings subnav can deep-link to the same routes.

---

## Guards

```
Guest visits /employees → redirect /login?redirect=/employees
Authenticated visits /login → redirect /dashboard
Authenticated lacks permission → 403 page / empty state (do not blank crash)
401 from API → clear session → /login
```

Permission checks:

- Router/menu: hide items user cannot access
- Page: soft-gate with 403 UI if deep-linked
- API: hard enforcement

---

## Navigation UX

From Stitch:

- Active nav item: solid **primary-deep** (`#006948`) + white icon/label
- Inactive: muted/dark text + outlined icon
- Mobile: sidebar becomes drawer; route change closes drawer

Search (header pill) may navigate to a global search results route later; v1 can filter current module or stub.

---

## Query Params

Use for list state when shareable:

```
/employees?status=active&department_id=3&page=2
/leave?status=pending
/attendance?date=2026-07-16
/audit-logs?action=leave.rejected&date_from=2026-07-01
/organization?branch_id=1
```

Do not put secrets in query strings.

---

## Code Splitting

- Lazy-load heavy modules (payroll reports, recruitment) by route
- Keep shell/layout eager
- Foundation admin screens (org, roles, settings, audit) may load with shell or lazy as a small admin chunk

---

## Related Documents

- [LAYOUT.md](./LAYOUT.md)
- [REACT_STRUCTURE.md](./REACT_STRUCTURE.md)
- [../06-api/ORGANIZATION_API.md](../06-api/ORGANIZATION_API.md)
- [../06-api/ROLES_API.md](../06-api/ROLES_API.md)
- [../06-api/SETTINGS_API.md](../06-api/SETTINGS_API.md)
- [../06-api/AUDIT_API.md](../06-api/AUDIT_API.md)
- [../02-business/README.md](../02-business/README.md)
