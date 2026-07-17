# Phase 01 — Foundation

> Auth, authorization, organization, settings, and application shell.
>
> Master: [MASTER_ROADMAP.md](./MASTER_ROADMAP.md)

---

## Goal

Establish the secure, multi-company-ready platform spine so every later module can plug in with permissions, org context, API envelope, and Efficient Growth UI shell.

---

## In Scope

| Area | Deliverables |
|------|--------------|
| Authentication | Login, logout, forgot/reset password, session, 2FA hooks |
| Authorization | Roles, permissions, policies, `/api/me` permission list |
| Organization | Company, branch, department, team, position CRUD |
| Settings | Basic company/app settings |
| Platform | API envelope, error handler, audit log table/writer |
| Frontend shell | Sidebar layout, auth layout, design tokens, permission-aware nav stub |
| Tooling | Migrate/seed permissions, CI baseline |

---

## Out of Scope

- Full employee profile module (Phase 02)
- Attendance/leave/payroll
- Recruitment

---

## Dependencies

None (first phase).

---

## Key Docs

### Stack & architecture

- [STACK_DECISION.md](../01-architecture/STACK_DECISION.md)
- [AUTHENTICATION.md](../01-architecture/AUTHENTICATION.md)
- [AUTHORIZATION.md](../01-architecture/AUTHORIZATION.md)
- [PERMISSIONS_CATALOG.md](../01-architecture/PERMISSIONS_CATALOG.md)

### APIs

- [AUTH_API.md](../06-api/AUTH_API.md)
- [ORGANIZATION_API.md](../06-api/ORGANIZATION_API.md)
- [ROLES_API.md](../06-api/ROLES_API.md)
- [SETTINGS_API.md](../06-api/SETTINGS_API.md)
- [AUDIT_API.md](../06-api/AUDIT_API.md) (writer in Phase 01; admin UI may land / polish in Phase 08)

### Business

- [../02-business/organization/README.md](../02-business/organization/README.md)
- [../02-business/settings/README.md](../02-business/settings/README.md)
- [../02-business/audit/README.md](../02-business/audit/README.md)

### Frontend & data

- [ROUTING.md](../05-frontend/ROUTING.md)
- [LAYOUT.md](../05-frontend/LAYOUT.md)
- [DESIGN_SYSTEM.md](../07-uiux/DESIGN_SYSTEM.md)
- [SEEDING.md](../03-database/SEEDING.md)
- [ERD.md](../03-database/ERD.md) (§2 auth, §2b settings)

---

## Exit Criteria

- [ ] User can log in/out; `/api/me` returns permissions
- [ ] Permissions are DB-driven; catalog keys for Phase 01 seeded ([SEEDING.md](../03-database/SEEDING.md))
- [ ] Org hierarchy CRUD works under company scope
- [ ] Settings + roles APIs usable; SPA routes `/organization`, `/roles`, `/settings`, `/audit-logs` gated
- [ ] App shell matches Stitch sidebar pattern (desktop); Admin section permission-aware
- [ ] Audit writer usable by later modules (read UI may be minimal)
- [ ] Tests cover authz deny paths

---

## Suggested Order of Work

1. API response/error standards wired in Laravel
2. Auth + me endpoint
3. Permissions/roles seed + policies pattern
4. Organization tables + APIs
5. Settings
6. Frontend auth + app layout + tokens
7. Smoke + tag `v0.1.0`
