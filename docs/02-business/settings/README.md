# Settings

> Company and system configuration.
>
> Phase: [PHASE_01_FOUNDATION.md](../../09-roadmap/PHASE_01_FOUNDATION.md)

---

## Purpose

Store configurable values that modules read (locale defaults, session policies, attendance tolerances, payroll currency, notification defaults) without hardcoding in code.

---

## Responsibilities

| Area | Examples |
|------|----------|
| Company profile | Legal name, timezone, locale, logo ref |
| Auth settings | Session lifetime, remember-me, 2FA policy flags |
| Module toggles | Feature flags per company (future SaaS-ready) |
| Defaults | Date format, currency, week start |

---

## Business Rules

1. Settings are company-scoped unless truly global system keys.
2. Changing sensitive settings may require elevated permissions + audit.
3. Modules read settings via a Settings service — not ad-hoc env for business rules.
4. Secrets (SMTP passwords, API keys) belong in env/secret manager, not the settings UI store.

---

## Permissions

| Permission | Intent |
|------------|--------|
| `can_view_settings` | View settings screens |
| `can_manage_settings` | Update company/module settings |

---

## Related admin surfaces

Settings is often adjacent to (but does not own):

| Surface | Docs |
|---------|------|
| Organization | [../organization/](../organization/) · [ORGANIZATION_API.md](../../06-api/ORGANIZATION_API.md) |
| Roles & permissions | [AUTHORIZATION.md](../../01-architecture/AUTHORIZATION.md) § Roles administration · [ROLES_API.md](../../06-api/ROLES_API.md) |
| Audit | [../audit/](../audit/) · [AUDIT_API.md](../../06-api/AUDIT_API.md) |

SPA paths: `/settings`, `/organization`, `/roles`, `/audit-logs` — [ROUTING.md](../../05-frontend/ROUTING.md).

---

## Related

- [../../06-api/SETTINGS_API.md](../../06-api/SETTINGS_API.md)
- [../organization/](../organization/)
- [../../01-architecture/PERMISSIONS_CATALOG.md](../../01-architecture/PERMISSIONS_CATALOG.md)
