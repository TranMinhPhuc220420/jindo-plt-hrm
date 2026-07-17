# Accessibility

> Baseline a11y rules for the Stitch-derived HRM UI system.

---

## Principles

1. Color is not the only status signal — pair with text/icons.
2. Focus rings are mandatory (2px emerald offset per design system).
3. Interactive elements need accessible names (especially icon buttons / FAB).
4. Contrast: dark text `#1d1d1f` on white/light surfaces; white text on `primary-brand` CTAs (`#059669`).
5. Sidebar active state: white on **`primary-deep`** (`#006948`) — not brand emerald.

---

## Keyboard

- All buttons, inputs, nav links, FAB reachable via Tab
- Modals/drawers trap focus while open and restore on close
- Escape closes drawer/modal

---

## Forms

- Visible labels (`label-md`) above inputs — not placeholder-only
- Errors use text + `error` / destructive color; associate with `aria-describedby`
- Do not rely on red outline alone

---

## Motion

- Prefer subtle transitions (hover/focus)
- Respect `prefers-reduced-motion` for non-essential animation

---

## Related

- [DESIGN_SYSTEM.md](./DESIGN_SYSTEM.md)
- [COLOR_SYSTEM.md](./COLOR_SYSTEM.md)
- [TYPOGRAPHY.md](./TYPOGRAPHY.md)
