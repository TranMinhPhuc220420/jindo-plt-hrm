# Component Guideline

> How to build and reuse React components for the HRM UI.
>
> Design: [DESIGN_SYSTEM.md](../07-uiux/DESIGN_SYSTEM.md)

---

## Component Layers

| Layer | Examples | Rules |
|-------|----------|-------|
| Primitive (`components/ui`) | Button, Input, Card, Badge, Dialog, Table | No domain imports |
| Pattern (`components/shared`) | PageHeader, EmptyState, PermissionGate, StatusBadge | Generic HRM UX patterns |
| Feature | `EmployeeForm`, `LeaveApprovalActions` | Domain-specific; talk to API via hooks |
| Page | Route screens | Composition only |

Prefer composition over inheritance. Extract a primitive only when reused ≥2 times or part of the design system.

---

## Naming

```
Button.tsx
PageHeader.tsx
EmployeeStatusBadge.tsx
LeaveRequestTable.tsx
```

- PascalCase files for components
- Hooks: `useLeaveRequests.ts`
- Avoid `My`, `Temp`, `New` prefixes

---

## Props Conventions

1. Explicit TypeScript props interface
2. `className` allowed for layout tweaks on primitives
3. Prefer `variant` / `size` enums over boolean prop explosion
4. Event handlers: `onSubmit`, `onApprove`, `onOpenChange`
5. Do not pass raw API envelope into presentational components — pass view models

---

## Design System Mapping

| Primitive | Stitch rule |
|-----------|-------------|
| Button primary | Gradient `#10b981` → `#059669`, radius 12px, white text |
| Button secondary | `#f2f2f7` / `#1d1d1f` |
| Input | `#f2f2f7`, radius 12px; focus white + green border |
| Card | White, radius 16px, subtle border + shadow |
| Badge | Pill; success green tint; warning orange |
| Sidebar nav item | Active solid primary |

See [UI_RULES.md](./UI_RULES.md).

---

## State Variants Every Interactive Component Needs

- Default / Hover / Focus / Disabled / Loading (where async)
- Error (inputs)
- Active / Selected (nav, tabs, filters)

Focus ring: 2px emerald with offset — do not remove outlines without replacement.

---

## PermissionGate

```tsx
<PermissionGate permission="can_approve_leave">
  <Button onClick={...}>Approve</Button>
</PermissionGate>
```

- Hide by default when lacking permission
- Optional `fallback` for disabled-with-tooltip cases (rare)

Never use `if (role === 'HR')` in components.

---

## Empty / Loading / Error

Every list/detail feature should handle:

| State | UI |
|-------|-----|
| Loading | Skeleton inside card (prefer) or spinner |
| Empty | Illustration/text + optional CTA |
| Error | Inline alert + retry |
| Forbidden | 403 panel |

---

## Anti-Patterns

1. One 800-line page component with inline styles
2. Domain fetch inside a primitive Button
3. Duplicating Card styles with random shadows
4. Mixing icon libraries in one view
5. Hardcoding API base URLs in components

---

## Related Documents

- [FORM_GUIDELINE.md](./FORM_GUIDELINE.md)
- [TABLE_GUIDELINE.md](./TABLE_GUIDELINE.md)
- [MODAL_GUIDELINE.md](./MODAL_GUIDELINE.md)
- [../07-uiux/ICON_RULES.md](../07-uiux/ICON_RULES.md)
