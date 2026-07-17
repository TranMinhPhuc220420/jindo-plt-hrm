# Responsive

> From Stitch **Efficient Growth** layout guidelines  
> Source: [stitch/STYLE_GUIDELINES.md](./stitch/STYLE_GUIDELINES.md)

---

## Breakpoint Strategy

| Range | Behavior |
|-------|----------|
| ≥ 768px (desktop/tablet landscape) | Fixed left sidebar 280px + main content |
| &lt; 768px (mobile) | Sidebar → drawer; stack content vertically; page padding 16px |

Target platforms (project): Desktop Web + Mobile Web.

---

## Spacing by Viewport

| Token | Desktop | Mobile |
|-------|---------|--------|
| Page margin | 40px | 16px |
| Card gutter | 24px | 16–24px (prefer 16px when tight) |
| Base grid | 8px | 8px |

---

## Layout Adaptations

### Shell

- Desktop: persistent sidebar + main
- Mobile: top bar / menu trigger opens sidebar drawer; main full width

### Dashboard (reference screen)

| Block | Desktop | Mobile |
|-------|---------|--------|
| KPI row | 4 columns | 1–2 columns stack |
| Attendance + Events | Main column | Full-width stack |
| Recent Activity | Side column | Full-width below |
| Search | Wide pill in main header | Full-width under top bar |
| FAB | Bottom-right of activity / main | Bottom-right safe area |

---

## Touch & Density

- Keep primary tap targets ≥ 40–44px on mobile
- Nav items: comfortable vertical padding when in drawer
- Avoid hover-only affordances for critical actions (approvals, submit)

---

## Related

- [DESIGN_SYSTEM.md](./DESIGN_SYSTEM.md)
- [../01-architecture/FRONTEND_ARCHITECTURE.md](../01-architecture/FRONTEND_ARCHITECTURE.md)
