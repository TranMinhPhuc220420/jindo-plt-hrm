# Design System

> **Source:** Google Stitch project **Apple Style HRM Dashboard** (`7601645920944319017`)  
> Design system name: **Efficient Growth** (`assets/aa8eca683c774077ad12e0718519f3dc`)  
> Raw export: [stitch/](./stitch/)

---

## Brand Personality

- **Professional & Efficient** — layouts prioritize data clarity.
- **Apple-inspired** — minimalist, high contrast, subtle shadows, rounded corners (12–16px).
- **Modern Growth** — emerald green accent for primary actions and positive indicators.

Aesthetic keywords: Corporate Modernism, soft-tech, reliability, generous whitespace.

---

## Reference Screen

**HRM Dashboard - Overview** (`screens/e9dc00b779774b25b778aceb12e10be2`)

Layout pattern:

| Region | Spec |
|--------|------|
| Left sidebar | Fixed ~280px, white, subtle right border |
| Main canvas | Soft gray/green-tinted background |
| Top | Pill search: “Search employees, documents…” |
| Header | Welcome title + muted subtitle |
| KPI row | 4 metric cards |
| Content | Attendance chart + upcoming events (left); recent activity timeline (right) |
| FAB | Primary green “+” floating action |

Assets:

- [stitch/assets/hrm-dashboard-overview.png](./stitch/assets/hrm-dashboard-overview.png)
- [stitch/assets/hrm-dashboard-overview.html](./stitch/assets/hrm-dashboard-overview.html)

---

## Layout & Spacing

| Token | Value |
|-------|--------|
| Base grid | 8px |
| Page margin (desktop) | 40px |
| Page margin (mobile) | 16px |
| Gutter (cards/widgets) | 24px |
| Content grid | 12-column fluid |
| Sidebar width | 280px (collapses to drawer &lt; 768px) |

Spacing scale (from Stitch tokens):

| Name | Value |
|------|--------|
| `xs` | 4px |
| `sm` | 12px |
| `md` | 16px |
| `lg` | 24px |
| `xl` | 32px |
| `gutter` | 24px |
| `margin-page` | 40px |

---

## Radius

| Token | Value | Use |
|-------|--------|-----|
| `sm` | 0.25rem | Tight chips |
| `DEFAULT` | 0.5rem | Small controls |
| `md` | 0.75rem (12px) | Buttons, inputs, nav active |
| `lg` | 1rem (16px) | Cards |
| `xl` | 1.5rem | Large surfaces / search pill |
| `full` | 9999px | Avatars, pills |

Product shorthand from guidelines:

- Standard control radius: **12px**
- Large card radius: **16px**

---

## Elevation & Depth

| Level | Surface | Treatment |
|-------|---------|-----------|
| Z-0 | App background | Light gray `#f2f2f7` / surface `#f5fbf5` |
| Z-1 | Cards | White + 1px `rgba(0,0,0,0.08)` + soft shadow `0 4px 6px -1px rgba(0,0,0,0.1)` |
| Z-2 | Popovers / modals | Stronger blur shadow |
| Sidebar | Nav chrome | Right border only (no heavy shadow) |

---

## Components (canonical)

### Buttons

- **Primary:** 12px radius, emerald gradient `#10b981` → `#059669`, white text
- **Secondary:** bg `#f2f2f7`, text `#1d1d1f`
- **Hover:** subtle darken
- **Focus:** 2px emerald ring with offset

### Inputs

- Default: bg `#f2f2f7`, 12px radius, no border
- Focus: white bg + 2px primary green border
- Labels: above field, `label-md`

### Cards

- White, 16px radius, `shadow-md`
- Optional header divider `1px rgba(0,0,0,0.05)`

### Chips / badges

- Success/active: light green tint + dark green text
- Status pills: fully rounded, ~12px height

### Sidebar nav

- White background
- Item hover/active: 8px radius highlight
- Active: solid primary green fill + white label/icon (per dashboard screen)

### Search

- Large pill / highly rounded field, muted placeholder, leading search icon

---

## Implementation Notes (React + Tailwind)

- Font: **Inter** (Google Fonts)
- Icons: Material Symbols Outlined in Stitch HTML export; prefer one icon set in app (see [ICON_RULES.md](./ICON_RULES.md))
- Map Stitch CSS variables / Tailwind theme colors from [COLOR_SYSTEM.md](./COLOR_SYSTEM.md)
- Do not invent a second visual language for HRM modules

---

## Related Documents

- [COLOR_SYSTEM.md](./COLOR_SYSTEM.md)
- [TYPOGRAPHY.md](./TYPOGRAPHY.md)
- [ICON_RULES.md](./ICON_RULES.md)
- [RESPONSIVE.md](./RESPONSIVE.md)
- [stitch/DESIGN.md](./stitch/DESIGN.md)
- [stitch/STYLE_GUIDELINES.md](./stitch/STYLE_GUIDELINES.md)
