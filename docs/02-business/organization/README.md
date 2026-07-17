# Organization

> Company structure: company, branch, department, team, position.
>
> Source: [PROJECT_LOGIC.md](../../00-overview/PROJECT_LOGIC.md) §5  
> Phase: [PHASE_01_FOUNDATION.md](../../09-roadmap/PHASE_01_FOUNDATION.md)

---

## Purpose

Own the organizational hierarchy that employees attach to. Multi-company ready even if v1 runs one company.

---

## Hierarchy

```
Company
  └── Branch
        └── Department
              └── Team
                    └── Position
                          └── Employee (owned by Employee module)
```

---

## Responsibilities

| Entity | Responsibilities |
|--------|------------------|
| Company | Top scope; settings; feature boundaries |
| Branch | Location / operational unit |
| Department | Functional unit |
| Team | Group within department |
| Position | Job title definition (org chart — not recruitment `job_openings`) |

Employee relationships (manager, supervisor, HR owner, department head) live on Employee, referencing org + other employees.

---

## Business Rules

1. Most business tables carry `company_id`.
2. Codes/names unique per company where applicable.
3. Soft-delete / deactivate preferred over hard-delete when employees reference the node.
4. Organization module does not own payroll/attendance writes.
5. v1 may seed a single company; APIs still accept company-scoped design.

---

## Dependencies

| May depend on | Must not depend on |
|---------------|--------------------|
| Authorization | Payroll, Attendance, Reports for CRUD |

Downstream: Employee and all operational modules.

---

## Permissions (illustrative)

| Permission | Intent |
|------------|--------|
| `can_view_organization` | View org tree |
| `can_manage_organization` | CRUD branches/departments/teams/positions |
| `can_manage_company` | Company profile (admin) |

---

## Related

- [EMPLOYEE_API / business](../employee/)
- [../settings/](../settings/) — adjacent admin config
- Roles admin (not org-owned): [AUTHORIZATION.md](../../01-architecture/AUTHORIZATION.md) § Roles administration · [ROLES_API.md](../../06-api/ROLES_API.md)
- [../../06-api/ORGANIZATION_API.md](../../06-api/ORGANIZATION_API.md)
- SPA: `/organization` — [ROUTING.md](../../05-frontend/ROUTING.md)
- [../../01-architecture/PERMISSIONS_CATALOG.md](../../01-architecture/PERMISSIONS_CATALOG.md)
