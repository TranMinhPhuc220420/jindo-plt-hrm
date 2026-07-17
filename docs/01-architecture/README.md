# 01 — Architecture

System boundaries, stack decision, authn/authz, and cross-cutting architecture.

---

## Documents

| File | Purpose |
|------|---------|
| [STACK_DECISION.md](./STACK_DECISION.md) | **REST + Sanctum SPA** — binding stack choice |
| [SYSTEM_ARCHITECTURE.md](./SYSTEM_ARCHITECTURE.md) | High-level system map |
| [BACKEND_ARCHITECTURE.md](./BACKEND_ARCHITECTURE.md) | Laravel layers |
| [FRONTEND_ARCHITECTURE.md](./FRONTEND_ARCHITECTURE.md) | React SPA architecture |
| [DATABASE_ARCHITECTURE.md](./DATABASE_ARCHITECTURE.md) | Data architecture |
| [API_ARCHITECTURE.md](./API_ARCHITECTURE.md) | REST API architecture |
| [AUTHENTICATION.md](./AUTHENTICATION.md) | Sanctum cookie/CSRF auth |
| [AUTHORIZATION.md](./AUTHORIZATION.md) | Permission-first access + Roles admin home |
| [PERMISSIONS_CATALOG.md](./PERMISSIONS_CATALOG.md) | Canonical `can_*` keys (seed source of truth) |
| [EVENT_FLOW.md](./EVENT_FLOW.md) | Domain events |
| [FILE_STORAGE.md](./FILE_STORAGE.md) | Uploads / private files |
| [DEPENDENCY_RULES.md](./DEPENDENCY_RULES.md) | Module dependency direction |

---

## Reading Order

1. **STACK_DECISION** — how we ship (not Inertia for new HRM modules)
2. **SYSTEM_ARCHITECTURE** — big picture
3. **AUTHENTICATION** + **AUTHORIZATION** + **PERMISSIONS_CATALOG**
4. Layer docs for the area you touch
5. **DEPENDENCY_RULES** before cross-module work

---

## Related

- [../00-overview/PROJECT_LOGIC.md](../00-overview/PROJECT_LOGIC.md)
- [../06-api/REST_STANDARD.md](../06-api/REST_STANDARD.md)
- [../09-roadmap/MASTER_ROADMAP.md](../09-roadmap/MASTER_ROADMAP.md)
