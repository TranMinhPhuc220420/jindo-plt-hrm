# Color System

> Derived from Stitch design system **Efficient Growth**  
> Source: [stitch/DESIGN.md](./stitch/DESIGN.md)  
> **Implementers:** use `primary-brand` and `primary-deep` — never a bare ambiguous `primary` in app theme code.

---

## Named primaries (binding)

| Token name | Hex | Use |
|------------|-----|-----|
| **`primary-brand`** | `#059669` | Buttons, links, FAB, success badges, CTA gradient end |
| **`primary-brand-soft`** | `#10b981` | CTA gradient start / highlight only |
| **`primary-deep`** | `#006948` | Sidebar active fill, pressed chrome, high-contrast fills |

### Why two greens exist

Both come from Stitch Efficient Growth. They are **not** interchangeable:

| Situation | Token |
|-----------|--------|
| Default product CTA | `primary-brand` |
| Active nav / deep chrome | `primary-deep` |
| Stitch YAML / Material export field named `primary` | Maps to **`primary-deep`** (`#006948`) — rename in app code |

In Tailwind / CSS:

```css
--color-primary-brand: #059669;
--color-primary-brand-soft: #10b981;
--color-primary-deep: #006948;
```

- Prefer utility names like `bg-primary-brand`, `bg-primary-deep`.
- Do **not** define a third green as “the” primary without updating this doc.
- Alias `--color-primary` → `primary-brand` only if unavoidable for a library; document the alias.

---

## Product Core (UI shorthand)

| Role | Hex | Token |
|------|-----|--------|
| Brand / CTA | `#059669` | `primary-brand` |
| Gradient top | `#10b981` | `primary-brand-soft` |
| Gradient bottom | `#059669` | `primary-brand` |
| Deep / active | `#006948` | `primary-deep` |
| Foreground | `#1d1d1f` | — |
| Background (app chrome) | `#f2f2f7` | — |
| Surface / page tint | `#f5fbf5` | — |
| Card / container | `#ffffff` | — |
| Secondary / muted fill | `#f2f2f7` | — |
| Muted foreground | `#8e8e93` | — |
| Border subtle | `rgba(0,0,0,0.08)` | — |
| Destructive | `#ff3b30` | — |
| Warning | `#ff9f0a` | — |
| Info | `#007aff` | — |
| Error (token) | `#ba1a1a` | — |

---

## Semantic Mapping

| Semantic | Color / token |
|----------|----------------|
| Success / Active status | `primary-brand` (`#059669`) + light green tints |
| Warning | `#ff9f0a` |
| Info | `#007aff` |
| Error / Destructive | `#ff3b30` / `#ba1a1a` |
| Nav active chrome | `primary-deep` (`#006948`) |

---

## Full Token Set (Stitch / Material fidelity)

For parity with the Stitch export, the Material-style map below is retained. **App theme code should still expose `primary-brand` / `primary-deep` as above.**

| Token (Stitch export) | Value | App mapping |
|-----------------------|--------|-------------|
| `background` | `#f5fbf5` | surface |
| `on-background` | `#171d19` | — |
| `surface` | `#f5fbf5` | — |
| `surface-dim` | `#d5dcd6` | — |
| `surface-bright` | `#f5fbf5` | — |
| `surface-container-lowest` | `#ffffff` | card |
| `surface-container-low` | `#eff5ef` | — |
| `surface-container` | `#e9efe9` | — |
| `surface-container-high` | `#e4eae4` | — |
| `surface-container-highest` | `#dee4de` | — |
| `surface-variant` | `#dee4de` | — |
| `on-surface` | `#171d19` | — |
| `on-surface-variant` | `#3d4a42` | — |
| `outline` | `#6d7a72` | — |
| `outline-variant` | `#bccac0` | — |
| `primary` (export name) | `#006948` | → **`primary-deep`** |
| `on-primary` | `#ffffff` | on deep / on brand |
| `primary-container` | `#00855d` | — |
| `on-primary-container` | `#f5fff7` | — |
| `inverse-primary` | `#68dba9` | — |
| `surface-tint` | `#006c4a` | — |
| `secondary` | `#5d5e63` | — |
| `on-secondary` | `#ffffff` | — |
| `secondary-container` | `#dfdfe4` | — |
| `on-secondary-container` | `#616267` | — |
| `tertiary` | `#9b3e3b` | — |
| `on-tertiary` | `#ffffff` | — |
| `tertiary-container` | `#ba5551` | — |
| `error` | `#ba1a1a` | — |
| `on-error` | `#ffffff` | — |
| `error-container` | `#ffdad6` | — |
| `on-error-container` | `#93000a` | — |
| `apple-black` | `#1d1d1f` | foreground |
| `muted-foreground` | `#8e8e93` | — |
| `destructive-red` | `#ff3b30` | — |
| `warning-orange` | `#ff9f0a` | — |
| `info-blue` | `#007aff` | — |
| `border-subtle` | `rgba(0,0,0,0.08)` | — |

Fixed / inverse tokens (for chips, dark snippets) live in [stitch/DESIGN.md](./stitch/DESIGN.md).

---

## CSS Variables (recommended)

```css
:root {
  --color-primary-brand: #059669;
  --color-primary-brand-soft: #10b981;
  --color-primary-deep: #006948;
  --color-foreground: #1d1d1f;
  --color-muted-foreground: #8e8e93;
  --color-background: #f2f2f7;
  --color-surface: #f5fbf5;
  --color-card: #ffffff;
  --color-secondary: #f2f2f7;
  --color-border: rgba(0, 0, 0, 0.08);
  --color-destructive: #ff3b30;
  --color-warning: #ff9f0a;
  --color-info: #007aff;
  --color-success: #059669; /* same as primary-brand */
}
```

---

## Usage Rules

1. `primary-brand` is for **actions and positive status**, not large background washes.
2. `primary-deep` is for **active chrome** (sidebar) and pressed high-contrast fills.
3. Body text uses Apple black / `on-surface`; meta uses muted foreground.
4. Cards stay white on soft background — avoid nested gray cards without hierarchy need.
5. Destructive actions use Apple red; form error tokens may use Material `error`.
6. Do not introduce purple/indigo default AI themes — stick to this emerald system.

---

## Related

- [DESIGN_SYSTEM.md](./DESIGN_SYSTEM.md)
- [TYPOGRAPHY.md](./TYPOGRAPHY.md)
- [ACCESSIBILITY.md](./ACCESSIBILITY.md)
- [stitch/STYLE_GUIDELINES.md](./stitch/STYLE_GUIDELINES.md)
