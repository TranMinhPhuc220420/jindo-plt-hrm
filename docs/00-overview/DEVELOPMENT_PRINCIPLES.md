# Development Principles

> Non-negotiable principles for building the HRM platform.
>
> Source of truth: [PROJECT_LOGIC.md](./PROJECT_LOGIC.md)

---

## 1. Modular

Every feature must be isolated into its own module.

- Modules own their domain logic, validation, and policies.
- Cross-module access goes through services, not direct table coupling.
- Example: Attendance must not depend directly on Payroll. Payroll consumes attendance data through services.

---

## 2. Scalable / Replaceable

Every module should be replaceable without affecting others.

| Module | Current (v1 direction) | Future variants |
|--------|------------------------|-----------------|
| Attendance | Manual check-in | GPS, face recognition, fingerprint, QR |
| Payroll | Monthly salary | Hourly, daily, commission, piece rate |

Design interfaces and data contracts so implementations can change behind services.

---

## 3. Multi-company Ready

Version 1 may support one company only, but every architecture decision assumes:

```
Company
  ├── Departments
  ├── Employees
  ├── Assets
  ├── Payroll
  ├── Attendance
  └── Reports
```

Avoid hardcoding “the company” as a singleton in domain logic when a company scope is the natural boundary.

---

## 4. Permission First

Every resource must be protected by roles and permissions.

- Never hardcode permissions by role name in business code.
- Examples: `can_create_employee`, `can_approve_leave`, `can_view_salary`
- Authorization covers API actions, feature visibility, and menu visibility.

---

## 5. Auditability

Every important action should be traceable.

Examples that must leave an audit trail:

- Employee edited
- Salary changed
- Attendance approved
- Leave rejected
- Asset assigned

---

## 6. Dependency Direction

Dependencies flow downward only. Avoid circular dependency.

```
Authentication
  → Authorization
    → Organization
      → Employee
        → Attendance / Leave / Shift / Payroll / Performance / Assets
          → Reports
            → Dashboard
```

---

## 7. System Layers

```
Presentation Layer
  → API Layer
    → Application Services
      → Domain Services
        → Repositories
          → Database
```

Rules:

- Business rules belong inside Services.
- Controllers must stay thin.
- Validation belongs in Form Requests (backend).
- Repositories handle persistence concerns, not business policy.

---

## 8. Backend Practices

- Prefer reusing existing services before creating new ones.
- Do not duplicate existing functionality.
- Prefer composition over inheritance.
- Keep modules loosely coupled.
- Follow RESTful API conventions.
- Every feature should include authorization.
- Every module should be independently testable.

---

## 9. Frontend Practices

- Use TypeScript everywhere.
- React components should remain reusable.
- UI must not own business rules that belong on the server.
- Keep forms, tables, and layouts consistent with frontend guidelines in `docs/05-frontend/`.

---

## 10. AI / Agent Development Rules

Every AI agent working on this project must follow:

1. Never violate business logic.
2. Never duplicate existing functionality.
3. Always reuse Services before creating new ones.
4. Controllers must stay thin.
5. Business logic belongs in Services.
6. Validation belongs in Form Requests.
7. React components should remain reusable.
8. Use TypeScript everywhere.
9. Follow RESTful API conventions.
10. Every feature should include authorization.
11. Every important action should be auditable.
12. Every module should be independently testable.
13. Prefer composition over inheritance.
14. Keep modules loosely coupled.
15. Design for future SaaS compatibility.

Detailed AI workflow lives in `docs/10-ai/`.

---

## Decision Checklist

Before merging a feature, confirm:

- [ ] Module boundary respected (no illegal upward/circular dependency)
- [ ] Permissions are explicit (not hardcoded by role name)
- [ ] Important mutations are auditable
- [ ] Business logic is in services, not controllers/UI
- [ ] Validation is in Form Requests
- [ ] API follows REST conventions
- [ ] Types/tests cover the module’s critical paths
- [ ] Design does not block multi-company or module replacement later

---

## Related Documents

- [PROJECT_LOGIC.md](./PROJECT_LOGIC.md)
- [PROJECT_VISION.md](./PROJECT_VISION.md)
- [BUSINESS_SCOPE.md](./BUSINESS_SCOPE.md)
- `docs/01-architecture/DEPENDENCY_RULES.md`
- `docs/04-backend/`
- `docs/05-frontend/`
- `docs/10-ai/AI_RULES.md`
