# Project Vision

> Product vision and long-term direction for the HRM platform.
>
> Source of truth for business and architectural logic: [PROJECT_LOGIC.md](./PROJECT_LOGIC.md)

---

## Vision Statement

Build a modern Human Resource Management (HRM) platform for small and medium businesses.

The platform must cover the full employee lifecycle — from candidate to archived employee — with clear module boundaries, permission-first access control, and an architecture ready for multi-company SaaS.

---

## Product Goals

1. **Complete lifecycle coverage** — Manage people from recruitment through exit in one system.
2. **Operational clarity** — Attendance, leave, shift, and payroll work together without tight coupling.
3. **Permission-first access** — Every resource is protected by roles and permissions.
4. **Auditability** — Important actions are always traceable.
5. **Replaceable modules** — Current implementations (e.g. manual check-in, monthly salary) can evolve without rewriting the whole platform.
6. **Future SaaS readiness** — Version 1 may serve one company, but architecture decisions assume multi-company from day one.

---

## Target Users

| Role | Primary needs |
|------|----------------|
| HR | Employee records, contracts, onboarding, documents, payroll prep |
| Manager | Leave approval, attendance review, team performance |
| Employee | Self-service: profile, attendance, leave, payslip, notifications |
| Admin | Organization setup, roles/permissions, settings, system config |

---

## Target Platforms

### Current

- Desktop Web
- Mobile Web

### Future

- React Native App
- Desktop Application
- Multi-company SaaS

---

## Technology Direction

### Backend

- Laravel
- MySQL
- REST API

### Frontend

- React
- TypeScript
- Vite
- TailwindCSS

---

## Capability Map

The platform should provide:

| Area | Capability |
|------|------------|
| People | Recruitment, onboarding, employee profiles, organization structure |
| Time | Attendance, leave, shift scheduling |
| Money | Payroll (salary, allowance, bonus, deduction, tax, insurance, payslip) |
| Growth | Performance (goals, KPI, OKR, review cycles) |
| Operations | Assets, company/employee documents, notifications |
| Insight | Reports, dashboards (later phases) |

---

## Success Criteria

The vision is considered on track when:

- Employee lifecycle is modeled end-to-end in the system.
- Modules depend downward only (no circular dependencies).
- Controllers stay thin; business rules live in services.
- Every feature ships with authorization and auditability where required.
- Attendance, leave, shift, and payroll remain independently replaceable.
- Documentation and code stay aligned with [PROJECT_LOGIC.md](./PROJECT_LOGIC.md).

---

## Long-Term Direction

The architecture must support expansion without major refactoring, including:

- Multi-company SaaS
- Advanced attendance (GPS, face recognition, fingerprint, QR)
- AI-assisted recruitment, performance, and payroll
- Workflow builder and approval engine
- E-signature and employee self-service portal
- Public API and third-party integrations (accounting, ERP, CRM)
- Native mobile application

Details of phased delivery live in `docs/09-roadmap/`.

---

## Related Documents

- [PROJECT_LOGIC.md](./PROJECT_LOGIC.md) — Master business and architecture logic
- [BUSINESS_SCOPE.md](./BUSINESS_SCOPE.md) — In-scope / out-of-scope boundaries
- [DEVELOPMENT_PRINCIPLES.md](./DEVELOPMENT_PRINCIPLES.md) — How we build
- [GLOSSARY.md](./GLOSSARY.md) — Shared terminology
