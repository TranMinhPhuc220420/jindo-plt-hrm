# 04 — Backend

Laravel implementation patterns for the HRM API.

Stack: [STACK_DECISION.md](../01-architecture/STACK_DECISION.md) (REST controllers under `/api`, Sanctum SPA).

---

## Documents

| File | Purpose |
|------|---------|
| [LARAVEL_STRUCTURE.md](./LARAVEL_STRUCTURE.md) | Folder / layer layout |
| [SERVICES.md](./SERVICES.md) | Domain services |
| [REPOSITORIES.md](./REPOSITORIES.md) | Data access |
| [ACTIONS.md](./ACTIONS.md) | Single-purpose actions |
| [EVENTS.md](./EVENTS.md) | Domain events |
| [LISTENERS.md](./LISTENERS.md) | Side effects |
| [POLICIES.md](./POLICIES.md) | Authorization policies |
| [QUEUES.md](./QUEUES.md) | Jobs / async |
| [VALIDATION.md](./VALIDATION.md) | Form requests |
| [API_RESPONSE.md](./API_RESPONSE.md) | Success envelope |
| [ERROR_HANDLING.md](./ERROR_HANDLING.md) | Errors / `error_code` |
| [TESTING.md](./TESTING.md) | Feature / unit tests |

---

## Rules of Thumb

- Controllers stay thin; business rules in services.
- Validate in Form Requests; authorize via policies + `can_*`.
- Match [../06-api/](../06-api/) contracts.

---

## Related

- [../01-architecture/BACKEND_ARCHITECTURE.md](../01-architecture/BACKEND_ARCHITECTURE.md)
- [../08-development/CODING_STANDARD.md](../08-development/CODING_STANDARD.md)
