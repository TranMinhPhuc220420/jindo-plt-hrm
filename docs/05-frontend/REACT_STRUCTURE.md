# React Structure

> Folder layout and ownership for the HRM React frontend.
>
> Architecture: [FRONTEND_ARCHITECTURE.md](../01-architecture/FRONTEND_ARCHITECTURE.md)  
> Design: [DESIGN_SYSTEM.md](../07-uiux/DESIGN_SYSTEM.md)

---

## Stack

| Item | Choice |
|------|--------|
| React + TypeScript | Required |
| Vite | Bundler |
| TailwindCSS | Styling (Efficient Growth tokens) |
| REST API client | Typed HTTP to Laravel API |

The starter kit may include Inertia/Wayfinder scaffolding. HRM domain screens should still follow this structure and the REST contract in `docs/06-api/`.

---

## Target Layout

```
resources/js/
  app.tsx                    # bootstrap
  pages/                     # route-level screens (compose features)
    dashboard/
    employee/
    attendance/
    leave/
    shift/
    payroll/
    recruitment/
    onboarding/
    performance/
    asset/
    document/
    notification/
    report/
    settings/
    auth/
  features/                  # domain UI + hooks (preferred as modules grow)
    employee/
    attendance/
    leave/
    ...
  components/
    ui/                      # primitives (button, input, card, dialog, table…)
    layout/                  # sidebar, header, page shell pieces
    shared/                  # empty states, permission gates, status badges
  layouts/
    app-layout.tsx           # authenticated shell (sidebar + main)
    auth-layout.tsx
  lib/
    api/                     # API client, endpoints, error helpers
    auth/                    # session helpers, permission checks
    utils.ts
  hooks/                     # shared hooks
  types/                     # shared TS types / API DTOs
  routes/                    # route helpers (or router config)
```

Early on, feature code may live under `pages/{module}/`. Move shared domain logic into `features/{module}/` when reused.

---

## Layer Responsibilities

| Layer | Does | Does not |
|-------|------|----------|
| `pages/` | Compose layout + features for a URL | Own payroll formulas |
| `features/` | Module UI, local hooks, view-models | Call foreign module internals ad hoc |
| `components/ui` | Reusable primitives | Business rules |
| `layouts/` | Shell, nav, auth gate | Domain tables |
| `lib/api` | HTTP + envelope parsing | Render UI |

---

## Module Mirroring

Frontend modules mirror backend / business docs:

`auth` · `organization` · `employee` · `attendance` · `leave` · `shift` · `payroll` · `recruitment` · `onboarding` · `performance` · `asset` · `document` · `notification` · `report` · `settings`

---

## TypeScript Rules

1. No `any` in domain code without explicit justification
2. API responses typed from shared DTO types
3. Components prefer explicit props interfaces
4. Prefer discriminated unions for status enums (`pending` | `approved` | `rejected`)

---

## Styling Placement

- Global tokens / theme → Tailwind config + CSS variables ([COLOR_SYSTEM.md](../07-uiux/COLOR_SYSTEM.md))
- Layout spacing → [LAYOUT.md](./LAYOUT.md) + design system
- Avoid one-off hex in feature files when a token exists

---

## Related Documents

- [ROUTING.md](./ROUTING.md)
- [LAYOUT.md](./LAYOUT.md)
- [COMPONENT_GUIDELINE.md](./COMPONENT_GUIDELINE.md)
- [STATE_MANAGEMENT.md](./STATE_MANAGEMENT.md)
- [API_CLIENT.md](./API_CLIENT.md)
