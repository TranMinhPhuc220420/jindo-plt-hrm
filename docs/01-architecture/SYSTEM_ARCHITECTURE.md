# System Architecture

> High-level architecture of the HRM platform.
>
> Source of truth: [PROJECT_LOGIC.md](../00-overview/PROJECT_LOGIC.md)

---

## Purpose

Describe how the whole system is structured: clients, API, application layers, data stores, and cross-cutting concerns (auth, authorization, audit, files, events).

Detailed rules live in sibling documents in this folder.

---

## Architecture Goals

| Goal | Implication |
|------|-------------|
| Modular | Domains are isolated; cross-domain access goes through services |
| Scalable / replaceable | Attendance, payroll, etc. can change implementations without rewriting consumers |
| Multi-company ready | Company is a first-class scope even if v1 runs one company |
| Permission first | Every resource/action is authorized |
| Auditable | Important mutations leave a trace |
| Thin controllers | Business rules live in services |

---

## High-Level Context

```
Clients (Desktop Web / Mobile Web / future apps)
        │ HTTPS / REST JSON
        ▼
API Layer (Laravel) — Auth, Authorization, Validation, Controllers
        │
        ▼
Application / Domain Services — modules, events, listeners, queues
        │
        ▼
Repositories
        │
        ▼
MySQL  ·  File Storage  ·  Queue / Cache
```

---

## System Layers

From [PROJECT_LOGIC.md](../00-overview/PROJECT_LOGIC.md) §8:

```
Presentation Layer
  → API Layer
    → Application Services
      → Domain Services
        → Repositories
          → Database
```

| Layer | Responsibility |
|-------|----------------|
| Presentation | React UI: pages, forms, tables, routing, client state |
| API | HTTP routes, controllers, Form Requests, response formatting |
| Application Services | Use-case orchestration across domain services |
| Domain Services | Business rules for a single module |
| Repositories | Persistence and queries |
| Database | MySQL as system of record |

Business rules belong in Services. Controllers stay thin.

---

## Domain Map

```
Authentication → Authorization → Organization → Employee
                                                    │
                    Attendance · Leave · Shift · Payroll
                    Performance · Assets · Documents
                                                    │
                                              Reports → Dashboard
```

Supporting domains:

- Notifications
- Settings / System
- Audit Logs
- File Storage (cross-cutting)

Each domain should remain independently testable and loosely coupled.

---

## Technology Stack

| Concern | Choice |
|---------|--------|
| Backend | Laravel |
| API style | REST JSON (`/api`) |
| Auth (web) | Laravel Sanctum SPA (cookie + CSRF) |
| Database | MySQL |
| Frontend | React + TypeScript + Vite + TailwindCSS (API client) |
| Clients (current) | Desktop Web, Mobile Web |
| Clients (future) | React Native, Desktop App, Public API |

Decision record: [STACK_DECISION.md](./STACK_DECISION.md). Inertia starter-kit pages are not the pattern for new HRM modules.

---

## Cross-Cutting Concerns

| Concern | Document |
|---------|----------|
| Authentication | [AUTHENTICATION.md](./AUTHENTICATION.md) |
| Authorization | [AUTHORIZATION.md](./AUTHORIZATION.md) |
| API contracts | [API_ARCHITECTURE.md](./API_ARCHITECTURE.md) |
| Events / async flow | [EVENT_FLOW.md](./EVENT_FLOW.md) |
| File storage | [FILE_STORAGE.md](./FILE_STORAGE.md) |
| Module dependencies | [DEPENDENCY_RULES.md](./DEPENDENCY_RULES.md) |
| Database shape | [DATABASE_ARCHITECTURE.md](./DATABASE_ARCHITECTURE.md) |

---

## Runtime Characteristics (v1)

- Single deployable backend (Laravel) exposing REST APIs
- SPA frontend talking only to the API (no business rules in the UI)
- Synchronous request/response for most CRUD
- Asynchronous jobs for notifications, heavy reports, payroll calculation where needed
- Company scope prepared in data model even if only one company is active

---

## Non-Goals of This Document

- Table-level schema → `docs/03-database/`
- Endpoint catalogs → `docs/06-api/`
- Laravel folder conventions → `docs/04-backend/`
- React folder conventions → `docs/05-frontend/`

---

## Related Documents

- [BACKEND_ARCHITECTURE.md](./BACKEND_ARCHITECTURE.md)
- [FRONTEND_ARCHITECTURE.md](./FRONTEND_ARCHITECTURE.md)
- [../00-overview/DEVELOPMENT_PRINCIPLES.md](../00-overview/DEVELOPMENT_PRINCIPLES.md)
- [../00-overview/BUSINESS_SCOPE.md](../00-overview/BUSINESS_SCOPE.md)
