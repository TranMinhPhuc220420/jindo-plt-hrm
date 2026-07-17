# UI Rules

> Non-negotiable visual and UX rules for implementing the HRM frontend.
>
> Source design: Stitch **Efficient Growth** — [DESIGN_SYSTEM.md](../07-uiux/DESIGN_SYSTEM.md)

---

## Brand & Theme

1. Follow **Efficient Growth** — emerald primary, Apple-like minimal chrome.
2. Do **not** introduce purple/indigo gradient themes or unrelated “AI default” palettes.
3. Primary CTA uses green gradient `#10b981` → `#059669` (or solid primary deep `#006948` for active nav).
4. Typography is **Inter** with the documented scale.
5. Icons: one outlined family; see [ICON_RULES.md](../07-uiux/ICON_RULES.md).

---

## Layout

1. Authenticated pages use the **sidebar + main** shell ([LAYOUT.md](./LAYOUT.md)).
2. Sidebar 280px; collapses below 768px.
3. Page margins 40px desktop / 16px mobile; card gutters 24px.
4. Cards: white, **16px** radius, subtle border + soft shadow.
5. Controls: **12px** radius (buttons, inputs).
6. Background stays soft gray/surface — content sits on white cards.

---

## Components

| Do | Don’t |
|----|-------|
| Use shared Button/Input/Card primitives | One-off hex + shadow per page |
| Status badges for state | Color text alone without label |
| Skeletons for loading | Blank white flash |
| Permission-gate actions | `role === 'admin'` checks |
| Empty states with CTA | Empty tables with no message |

---

## Hierarchy

1. One clear page title (`headline-lg`).
2. One primary action per page header (extra actions secondary/overflow).
3. KPI/stat cards are for dashboards — not every module home needs four KPIs.
4. Prefer calm density; avoid badge/pill clusters and stacked promo strips.

---

## Feedback

| Event | Feedback |
|-------|----------|
| Save success | Toast + updated view |
| Validation | Field-level 422 messages |
| Permission denied | Forbidden message (not silent hide after click) |
| Destructive | Confirm modal |
| Async export | Queued state → notify/download |

---

## Motion

- Subtle hover/focus transitions only
- Respect `prefers-reduced-motion`
- Do not add decorative particle/glow effects

---

## Content & Copy

- Prefer clear HR language: Approve, Reject, Check in, Finalize payroll
- Muted subtitles for context (Stitch pattern)
- Don’t invent legal copy for contracts — show system data

---

## Implementation Checklist

- [ ] Tokens from [COLOR_SYSTEM.md](../07-uiux/COLOR_SYSTEM.md)
- [ ] Type scale from [TYPOGRAPHY.md](../07-uiux/TYPOGRAPHY.md)
- [ ] Forms follow [FORM_GUIDELINE.md](./FORM_GUIDELINE.md)
- [ ] Tables follow [TABLE_GUIDELINE.md](./TABLE_GUIDELINE.md)
- [ ] Modals follow [MODAL_GUIDELINE.md](./MODAL_GUIDELINE.md)
- [ ] API via [API_CLIENT.md](./API_CLIENT.md)
- [ ] Matches Stitch reference screenshot for shell/dashboard feel

---

## Related Documents

- [../07-uiux/stitch/README.md](../07-uiux/stitch/README.md)
- [COMPONENT_GUIDELINE.md](./COMPONENT_GUIDELINE.md)
- [../01-architecture/FRONTEND_ARCHITECTURE.md](../01-architecture/FRONTEND_ARCHITECTURE.md)
