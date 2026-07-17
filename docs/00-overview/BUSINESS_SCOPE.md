# Business Scope

> What the HRM platform covers, what it does not cover yet, and how domains map to delivery phases.
>
> Source of truth: [PROJECT_LOGIC.md](./PROJECT_LOGIC.md)

---

## Scope Summary

This project is a Human Resource Management (HRM) platform for SMEs. It manages the employee lifecycle and related operational domains: organization, time tracking, leave, shifts, payroll, recruitment, onboarding, performance, assets, documents, notifications, and reporting.

Version 1 focuses on a solid foundation and core HR operations. Advanced SaaS, AI, and device integrations are planned later and must not force early redesign.

---

## In Scope

### Core domains

| Domain | In scope responsibilities |
|--------|---------------------------|
| Authentication | Login, logout, forgot/reset password, 2FA, remember login, session management |
| Authorization | Roles, permissions, policies, access control, feature/menu visibility, action authorization |
| Organization | Company → Branch → Department → Team → Position; manager / supervisor / HR owner / department head |
| Employee | Profile, emergency contact, education, work history, family, documents, contracts, bank/insurance/tax info, status |
| Attendance | Check-in/out, working hours, late, early leave, overtime, break, correction, approval, history, summary |
| Leave | Request, type, balance, approval, holiday, weekend rules, compensation / half-day / hourly leave |
| Shift | Definition, assignment, working calendar, rotating / night / flexible shift, overtime rules |
| Payroll | Salary, allowance, bonus, deduction, tax, insurance, overtime, calculation, approval, payslip, history |
| Recruitment | Job position, candidate, interview, evaluation, offer, hiring |
| Onboarding | Checklist, account creation, equipment assignment, orientation, training, probation, completion |
| Performance | Goals, KPI, OKR, evaluation, review cycle, promotion suggestion |
| Assets | Assign, return, inventory, maintenance, damage report, replacement |
| Documents | Company files, employee files, policies, templates, contracts, certificates |
| Notifications | Email, system notification, push, reminder, scheduled notification |
| Reports | Attendance, payroll, leave, employee, department, performance, custom reports |
| Settings / System / Audit Logs | Configuration and traceability for important actions |

### Employee lifecycle (backbone)

```
Candidate → Recruitment → Interview → Offer → Accepted
  → Onboarding → Employee
  → Attendance / Leave / Payroll / Performance
  → Promotion / Transfer
  → Resignation → Exit Process → Archived Employee
```

Every module must align with this lifecycle.

---

## Out of Scope (for now)

These are intentional future expansions, not current delivery commitments:

| Area | Why deferred |
|------|----------------|
| Multi-company SaaS runtime | Architecture must be ready; full productization later |
| Face / GPS / fingerprint / QR attendance | Current attendance starts with manual check-in |
| Hourly / daily / commission / piece-rate payroll | Current payroll starts with monthly salary |
| AI resume screening, performance analysis, payroll assistant | Planned after core modules |
| Workflow builder / approval engine as a platform | Approvals exist per module first |
| E-signature | Later |
| Native mobile app / desktop app | Web first (desktop + mobile web) |
| Public API for third parties | Internal REST API first |
| Accounting / ERP / CRM deep integrations | After core HRM is stable |

Architecture decisions must still leave room for these without major refactoring. See [PROJECT_VISION.md](./PROJECT_VISION.md).

---

## Organization Boundary

```
Company
  └── Branch
        └── Department
              └── Team
                    └── Position
                          └── Employee
```

Employees may also relate to:

- Manager
- Direct Supervisor
- HR Owner
- Department Head

Version 1 may run as a single company, but data and design should assume company as the top boundary.

---

## Module Dependency Scope

Dependencies must flow downward only:

```
Authentication
  → Authorization
    → Organization
      → Employee
        → Attendance / Leave / Shift / Payroll / Performance / Assets
          → Reports
            → Dashboard
```

Rules:

- No circular dependencies.
- Attendance must not depend directly on Payroll.
- Payroll consumes attendance (and related) data through services.
- Reports depend on domain modules; domain modules must not depend on reports.

---

## Delivery Phases (scope by phase)

Logical product groups (same as [PROJECT_LOGIC.md](./PROJECT_LOGIC.md) §9):

| Logic phase | Scope |
|-------------|--------|
| Phase 1 — Foundation | Authentication, Authorization, Organization, Employee, Settings |
| Phase 2 — Time | Attendance, Shift, Leave, Holiday |
| Phase 3 — Payroll | Payroll, Allowance, Bonus, Deduction |
| Phase 4 — Hire & Ops | Recruitment, Onboarding, Documents, Assets |
| Phase 5 — Insight | Performance, Reports, Notifications, Audit Logs |
| Phase 6 — Advanced | Analytics, Dashboard polish, AI Assistant, Workflow Automation, Public API, Mobile App |

**Implementation slices** (finer) live in `docs/09-roadmap/` — e.g. `PHASE_01` + `PHASE_02` = Logic Phase 1; Shift → Attendance → Leave preferred inside Logic Phase 2. See [MASTER_ROADMAP.md](../09-roadmap/MASTER_ROADMAP.md).

---

## Explicit Non-Goals

- Building a full ERP or accounting system
- Replacing specialist ATS or payroll engines in v1
- Hardcoding permissions or menu visibility by role name
- Putting business rules in controllers or React components
- Coupling modules so tightly that one cannot be replaced

---

## Related Documents

- [PROJECT_LOGIC.md](./PROJECT_LOGIC.md) — Full module responsibilities and relationships
- [PROJECT_VISION.md](./PROJECT_VISION.md) — Product vision and success criteria
- [DEVELOPMENT_PRINCIPLES.md](./DEVELOPMENT_PRINCIPLES.md) — Build constraints
- [GLOSSARY.md](./GLOSSARY.md) — Domain terms
- Business module docs under `docs/02-business/`
