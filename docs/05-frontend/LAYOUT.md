# Layout

> App shell layout based on Stitch **Efficient Growth** / HRM Dashboard.
>
> Visual source: [DESIGN_SYSTEM.md](../07-uiux/DESIGN_SYSTEM.md)  
> Responsive: [RESPONSIVE.md](../07-uiux/RESPONSIVE.md)

---

## Shell Structure

```
┌─────────────┬────────────────────────────────────────┐
│  Sidebar    │  Main                                   │
│  280px      │  ┌────────────────────────────────────┐ │
│             │  │ Search (pill)                      │ │
│  Brand      │  ├────────────────────────────────────┤ │
│  Nav        │  │ Page header (title + subtitle)     │ │
│             │  │ Page actions                       │ │
│  --------   │  ├────────────────────────────────────┤ │
│  User card  │  │ Page content (cards / tables)      │ │
└─────────────┴────────────────────────────────────────┘
```

---

## Regions

### Sidebar

| Element | Spec |
|---------|------|
| Width | 280px desktop |
| Background | `#ffffff` |
| Border | Right `rgba(0,0,0,0.08)` |
| Brand | “HRM Portal” + tagline (e.g. Growth & Vitality) + mark |
| Nav items | Icon + label; active = **primary-deep** (`#006948`) fill + white |
| Footer | Avatar, name, role, logout |

Mobile: drawer overlay; hamburger in top bar.

### Sidebar IA (Phase 01+)

Build items from permissions ([ROUTING.md](./ROUTING.md), [PERMISSIONS_CATALOG.md](../01-architecture/PERMISSIONS_CATALOG.md)):

| Section | Items (paths) | Typical gates |
|---------|---------------|---------------|
| Core | Dashboard `/` | authenticated |
| People | Employees `/employees` | `can_view_employee` |
| Time | Attendance, Leave, Shifts | matching `can_view_*` |
| Ops | Payroll, Recruitment, … | matching gates |
| Admin | Organization `/organization`, Roles `/roles`, Settings `/settings`, Audit `/audit-logs` | `can_view_organization`, `can_view_roles`, `can_view_settings`, `can_view_audit_logs` |

Admin section is usually collapsed for non-admin users (hidden when no keys match).

### Main canvas

| Element | Spec |
|---------|------|
| Background | `#f2f2f7` / surface `#f5fbf5` |
| Padding | 40px desktop · 16px mobile |
| Search | Optional global pill under top / in header |
| Content max | Fluid 12-col feel; gutters 24px between cards |

### Page header

- Title: `headline-lg` (e.g. “Welcome back, Admin”)
- Subtitle: muted `body-sm` / `body-md`
- Actions: right-aligned primary/secondary buttons

### Language switcher

- Place in the **app header** and/or **user menu** (authenticated shell).
- Persists personal preference via `PUT /api/me/locale`; company default via Company settings.
- Labels and options come from i18n catalogs — see [I18N.md](./I18N.md).

---

## Layout Variants

| Layout | Use |
|--------|-----|
| `app-layout` | Authenticated HRM pages |
| `auth-layout` | Login, reset password, 2FA challenge |
| `settings-layout` | Optional nested admin/settings nav (Company, Auth, links to Organization / Roles / Audit) inside app shell |

---

## Dashboard Composition (reference)

From Stitch Overview screen:

1. Search
2. Welcome header
3. KPI row (4 cards)
4. Two-column: analytics/events | activity timeline
5. FAB (primary “+”) when the page has a primary create action

Module list pages typically use:

1. Page header + primary CTA
2. Filters row
3. Table card ([TABLE_GUIDELINE.md](./TABLE_GUIDELINE.md))

Detail pages:

1. Header + status badges + actions
2. Tabs / sections in cards

---

## Permission-Aware Chrome

- Build sidebar items from permission keys
- Hide inaccessible modules entirely (or show disabled with tooltip sparingly)
- User card always shows identity; logout always available

---

## Spacing Checklist

- [ ] 8px grid respected
- [ ] Card gap 24px
- [ ] No dense “dashboard soup” of extra promo strips in first viewport of marketing surfaces (app dashboards may show KPIs — that is intentional)
- [ ] Cards use 16px radius; controls 12px

---

## Related Documents

- [UI_RULES.md](./UI_RULES.md)
- [ROUTING.md](./ROUTING.md)
- [COMPONENT_GUIDELINE.md](./COMPONENT_GUIDELINE.md)
- [../07-uiux/stitch/assets/hrm-dashboard-overview.png](../07-uiux/stitch/assets/hrm-dashboard-overview.png)
