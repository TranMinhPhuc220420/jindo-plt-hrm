# Typography

> Derived from Stitch design system **Efficient Growth** (Inter)  
> Source: [stitch/DESIGN.md](./stitch/DESIGN.md)

---

## Font Family

| Role | Family |
|------|--------|
| UI / body / headings | **Inter** |

Load weights used in product: **400, 500, 600, 700** (800 optional for display).  
Stitch HTML also references weight 300 (Light) in guidelines — optional.

```html
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
```

```css
font-family: 'Inter', system-ui, sans-serif;
```

Avoid defaulting to Inter-as-generic-AI-look *with purple themes*; here Inter is intentional for Apple-like HRM clarity with the emerald system.

---

## Scale

Base size: **16px**.

| Token | Size | Weight | Line height | Letter-spacing | Use |
|-------|------|--------|-------------|----------------|-----|
| `headline-xl` | 36px | 700 | 44px | -0.02em | Rare page heroes |
| `headline-lg` | 28px | 600 | 34px | -0.01em | Page titles (“Welcome back, Admin”) |
| `headline-md` | 20px | 600 | 28px | -0.01em | Card / section titles |
| `body-lg` | 18px | 400 | 28px | — | Emphasized body |
| `body-md` | 16px | 400 | 24px | — | Default body |
| `body-sm` | 14px | 400 | 20px | — | Secondary body |
| `label-md` | 14px | 500 | 16px | — | Field labels, nav |
| `label-sm` | 12px | 600 | 16px | 0.02em | Badges, overlines, meta |

---

## Weight Usage

| Weight | Role |
|--------|------|
| 400 | Body, descriptions |
| 500 | Labels, nav items |
| 600 | Headings, semibold UI |
| 700 | KPI numbers, strong titles |

Headings use semibold/bold with **tight letter-spacing**.

---

## Color Pairing

| Text role | Color token |
|----------|-------------|
| Primary text | `#1d1d1f` / `on-surface` `#171d19` |
| Secondary / subtitle | `#8e8e93` |
| On primary (green buttons) | `#ffffff` |
| Links / info | `#007aff` (sparingly) |

---

## Dashboard Patterns (from Stitch screen)

- Welcome title: `headline-lg`, dark foreground
- Subtitle: `body-md` or `body-sm`, muted foreground
- KPI value: large semibold/bold tabular feel
- KPI label: `label-md` / `body-sm` muted
- Timeline meta timestamps: `label-sm` muted

---

## Related

- [DESIGN_SYSTEM.md](./DESIGN_SYSTEM.md)
- [COLOR_SYSTEM.md](./COLOR_SYSTEM.md)
