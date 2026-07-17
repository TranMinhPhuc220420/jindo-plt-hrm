# Stack Decision (ADR)

> Architecture decision: how the HRM app is delivered on top of the Laravel starter kit.
>
> Status: **Accepted**  
> Date: 2026-07-16  
> Related: [SYSTEM_ARCHITECTURE.md](./SYSTEM_ARCHITECTURE.md), [AUTHENTICATION.md](./AUTHENTICATION.md)

---

## Context

The repository was bootstrapped from the **Laravel React starter kit** (Inertia, Fortify, Wayfinder).

Project documentation (`PROJECT_LOGIC`, API, frontend architecture) targets:

- Laravel + **REST JSON API**
- React + TypeScript + Vite + TailwindCSS
- Desktop Web + Mobile Web now; React Native later

Without an explicit decision, agents may implement HRM features with Inertia page props instead of the documented REST contracts.

---

## Decision

### Target for all HRM domain modules

| Layer | Choice |
|-------|--------|
| API | **REST JSON** under `/api` (see `docs/06-api/`) |
| Web UI | **React SPA pages** talking to the API via the shared API client |
| Auth (web) | **Laravel Sanctum SPA authentication** — session cookie + CSRF for first-party SPA |
| Auth (future mobile) | Sanctum **API tokens** (or equivalent) on the same permission model |
| Styling | TailwindCSS + Efficient Growth tokens (`docs/07-uiux/`) |

### Starter-kit Inertia / Wayfinder / Fortify

| Allowed | Not allowed for new HRM work |
|---------|------------------------------|
| Keep Fortify (or equivalent) as **implementation** behind `AUTH_API` | Building new Employee/Leave/Payroll screens as Inertia-only flows that skip REST |
| Temporarily reuse kit auth/settings UI while Phase 01 lands | Treating Inertia shared props as the system of record for domain data |
| Wayfinder only if it does not replace REST contracts | Dual write paths (Inertia + REST) for the same use case |

**Rule:** New HRM modules must expose and consume **REST endpoints** documented in `docs/06-api/`. The React UI is a client of that API.

---

## Consequences

1. Controllers for HRM domains return API envelopes (`docs/04-backend/API_RESPONSE.md`), not Inertia responses.
2. Frontend domain data flows through `docs/05-frontend/API_CLIENT.md`.
3. Mobile / public API can reuse the same backend later.
4. Migrating leftover Inertia demo pages is chore work — do not expand them for HRM domains.

---

## Auth mechanism (locked)

See [AUTHENTICATION.md](./AUTHENTICATION.md) — **Sanctum SPA (cookie)** for v1 web.

---

## Rejection alternatives

| Alternative | Why rejected |
|-------------|--------------|
| Inertia-only monolith | Blocks clean mobile/public API; contradicts PROJECT_LOGIC |
| JWT-only from day one | Heavier for first-party web; cookie SPA is simpler and safer for XSS token theft |
| Separate Node BFF | Unnecessary complexity for v1 |

---

## Related

- [FRONTEND_ARCHITECTURE.md](./FRONTEND_ARCHITECTURE.md)
- [API_ARCHITECTURE.md](./API_ARCHITECTURE.md)
- [../06-api/AUTH_API.md](../06-api/AUTH_API.md)
- [../05-frontend/REACT_STRUCTURE.md](../05-frontend/REACT_STRUCTURE.md)
