# Icon Rules

> Aligned with Stitch **HRM Dashboard - Overview** export  
> Source HTML uses **Material Symbols Outlined**; product may standardize on one set.

---

## Chosen Direction

| Context | Recommendation |
|---------|----------------|
| Stitch reference | Material Symbols Outlined |
| App implementation | One consistent outlined set (Material Symbols **or** Lucide) — do not mix styles in the same shell |

Prefer **outlined / thin stroke** icons to match Apple-like HRM chrome. Avoid filled emoji icons.

---

## Sizes

| Context | Size |
|---------|------|
| Sidebar nav | 20–24px |
| KPI card glyph | 20–24px |
| Inline with `body-sm` | 16–18px |
| FAB | 24px icon in circular button |
| Avatar fallback | initials preferred over generic user glyph when possible |

---

## Color

| State | Color |
|-------|--------|
| Default nav / content | Foreground / muted (`#1d1d1f` / `#8e8e93`) |
| Active nav (on green) | White |
| Primary accent icon | `#059669` / `#006948` |
| Destructive | `#ff3b30` |
| Warning | `#ff9f0a` |
| Info | `#007aff` |

---

## Sidebar Icons (reference screen)

Match meaning, not necessarily exact glyph names:

| Item | Intent |
|------|--------|
| Dashboard | Home / grid |
| Employees | People |
| Payroll | Payments / currency |
| Time & Attendance | Schedule / clock |
| Recruitment | Person add / briefcase |
| Settings | Gear |

Active item: solid primary green pill + white icon.

---

## Timeline / Status Dots

Activity timeline uses small colored circles:

| Meaning (example) | Color family |
|-------------------|--------------|
| Success / approved | Green |
| Info | Blue |
| Warning / pending | Orange |
| Neutral | Gray |
| Error / rejected | Red |

Keep stroke/fill consistent within the timeline component.

---

## Rules

1. One icon family per app shell.
2. No decorative icon clusters in hero/header beyond search + essential actions.
3. Pair icons with text labels in primary navigation (icon-only only when tooltip + a11y name exist).
4. FAB uses primary green with white “+”.

---

## Related

- [DESIGN_SYSTEM.md](./DESIGN_SYSTEM.md)
- [COLOR_SYSTEM.md](./COLOR_SYSTEM.md)
- [stitch/assets/hrm-dashboard-overview.html](./stitch/assets/hrm-dashboard-overview.html)
