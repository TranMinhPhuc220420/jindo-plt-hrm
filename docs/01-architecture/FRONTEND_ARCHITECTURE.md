# Frontend Architecture

> React frontend structure, boundaries, and integration with the REST API.
>
> Source of truth: [PROJECT_LOGIC.md](../00-overview/PROJECT_LOGIC.md)

---

## Purpose

Define how the SPA is organized so UI stays reusable, TypeScript-first, and free of server-side business rules.

Folder-level conventions: `docs/05-frontend/REACT_STRUCTURE.md`.

---

## Stack

| Item | Choice |
|------|--------|
| UI library | React |
| Language | TypeScript |
| Bundler | Vite |
| Styling | TailwindCSS |
| API | REST JSON via API client |
| Platforms | Desktop Web, Mobile Web (responsive) |

Future clients (React Native, desktop app) should reuse API contracts, not duplicate business logic.

---

## Architectural Position

```
React Presentation Layer
  → API Client
    → Laravel REST API
      → Services → Repositories → MySQL
```

The frontend is a **presentation layer** only:

- Renders views and collects input
- Calls the API for all authoritative reads/writes
- Enforces UX-level permission visibility (menus/features) based on permissions returned by the backend
- Never becomes the source of truth for payroll, leave balances, attendance approval, etc.

---

## Layering Inside the Frontend

| Layer | Responsibility |
|-------|----------------|
| Routes / pages | URL → screen composition |
| Layouts | Shell, navigation, auth gates |
| Feature modules | Screens and hooks for one business domain |
| Shared components | Reusable UI (forms, tables, modals) |
| API client | Typed HTTP calls, error normalization |
| Client state | UI state, cached server state, auth session view |

Recommended feature grouping mirrors backend domains:

`auth`, `organization`, `employee`, `attendance`, `leave`, `shift`, `payroll`, `recruitment`, `onboarding`, `performance`, `asset`, `document`, `notification`, `report`, `settings`

---

## Permission-Aware UI

Authorization is **permission first**, not role-name checks in components.

- Backend returns the current user’s permissions (and/or capability flags).
- Menus and actions use permission keys such as `can_create_employee`, `can_approve_leave`, `can_view_salary`.
- Hiding a button is UX only; the API remains the real enforcement point.

See [AUTHORIZATION.md](./AUTHORIZATION.md).

---

## Data Flow

```
User action
  → Form / Table / Modal
    → Feature hook or mutation
      → API client
        → REST endpoint
          → Optimistic/local UI update only after confirmed policy
```

Rules:

- Validate UX constraints on the client for speed; never trust them as security.
- Prefer server validation errors mapped into form fields.
- Keep tables/forms/modals consistent with `docs/05-frontend/` guidelines.

---

## State Management Principles

- Server state (lists, details, permissions) comes from the API and may be cached.
- UI state (modal open, selected rows, wizard step) stays local/component-level when possible.
- Avoid a global store for domain business rules.
- Auth session (user + permissions) is global but read-only from the UI’s perspective.

Details: `docs/05-frontend/STATE_MANAGEMENT.md`.

---

## Responsive Strategy

- One React codebase for desktop and mobile web.
- Layouts adapt; business flows stay the same.
- Touch-friendly controls for approval and attendance actions on small screens.

UI system details: `docs/07-uiux/`.

---

## What Must Not Live in the Frontend

- Payroll calculation formulas as source of truth
- Leave balance computation as source of truth
- Attendance approval policy
- Direct database assumptions
- Hardcoded role names for access control

---

## Related Documents

- [SYSTEM_ARCHITECTURE.md](./SYSTEM_ARCHITECTURE.md)
- [API_ARCHITECTURE.md](./API_ARCHITECTURE.md)
- [AUTHENTICATION.md](./AUTHENTICATION.md)
- [AUTHORIZATION.md](./AUTHORIZATION.md)
- `docs/05-frontend/`
- `docs/07-uiux/`
