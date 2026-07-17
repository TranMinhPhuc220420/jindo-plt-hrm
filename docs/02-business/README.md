# 02 — Business Modules

Business-domain documentation for the HRM platform. Each folder describes one module’s purpose, rules, workflows, dependencies, and permissions.

Source of truth: [PROJECT_LOGIC.md](../00-overview/PROJECT_LOGIC.md)

---

## Modules

| Module | Path | Role in lifecycle |
|--------|------|-------------------|
| Organization | [organization/](./organization/) | Company → branch → dept → team → position |
| Settings | [settings/](./settings/) | Company / module configuration |
| Audit | [audit/](./audit/) | Append-only audit trail |
| Employee | [employee/](./employee/) | Core people record after hire |
| Attendance | [attendance/](./attendance/) | Presence and working time |
| Leave | [leave/](./leave/) | Time-off requests and balances |
| Shift | [shift/](./shift/) | Schedules and working calendars |
| Payroll | [payroll/](./payroll/) | Compensation calculation and payslips |
| Recruitment | [recruitment/](./recruitment/) | Candidate → offer → hire |
| Onboarding | [onboarding/](./onboarding/) | Accepted offer → active employee |
| Performance | [performance/](./performance/) | Goals, reviews, promotion signals |
| Asset | [asset/](./asset/) | Company equipment lifecycle |
| Document | [document/](./document/) | Company/employee files and policies |
| Notification | [notification/](./notification/) | Email, in-app, push, reminders |
| Report | [report/](./report/) | Aggregated operational insights |

Authentication / authorization architecture: [../01-architecture/AUTHENTICATION.md](../01-architecture/AUTHENTICATION.md), [AUTHORIZATION.md](../01-architecture/AUTHORIZATION.md), [PERMISSIONS_CATALOG.md](../01-architecture/PERMISSIONS_CATALOG.md). Roles API: [../06-api/ROLES_API.md](../06-api/ROLES_API.md).

---

## Dependency Direction

```
Organization → Employee
                 → Attendance / Leave / Shift / Payroll / Performance / Assets / Documents
                   → Reports → Dashboard
```

Recruitment → Onboarding → Employee is the hire path.

Rules: [DEPENDENCY_RULES.md](../01-architecture/DEPENDENCY_RULES.md)

---

## Document Template (per module)

Each module `README.md` covers:

1. Purpose
2. Responsibilities
3. Business rules
4. Key workflows
5. Dependencies (allowed / forbidden)
6. Permissions (illustrative)
7. Events & side effects
8. Out of scope / future
9. Related documents

---

## Reading Order (suggested)

1. [organization](./organization/) → [settings](./settings/) → [employee](./employee/)
2. [shift](./shift/) → [attendance](./attendance/) → [leave](./leave/)
3. [payroll](./payroll/)
4. [recruitment](./recruitment/) → [onboarding](./onboarding/)
5. [performance](./performance/), [asset](./asset/), [document](./document/)
6. [notification](./notification/), [report](./report/), [audit](./audit/)

---

## Related Sections

- `docs/00-overview/` — vision, scope, glossary
- `docs/01-architecture/` — system boundaries and dependency rules
- `docs/06-api/` — HTTP contracts per domain
- `docs/09-roadmap/` — delivery phases
