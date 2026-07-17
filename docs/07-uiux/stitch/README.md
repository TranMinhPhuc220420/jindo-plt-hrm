# Stitch Source — Apple Style HRM Dashboard

Pulled from Google Stitch for use as the UX/UI and style source of truth for `docs/07-uiux/`.

| Field | Value |
|-------|--------|
| Project title | Apple Style HRM Dashboard |
| Project ID | `7601645920944319017` |
| Design System asset | `assets/aa8eca683c774077ad12e0718519f3dc` (`Efficient Growth`) |
| Screen | `e9dc00b779774b25b778aceb12e10be2` — **HRM Dashboard - Overview** |

---

## Files in this folder

| File | Description |
|------|-------------|
| [DESIGN.md](./DESIGN.md) | Design tokens (YAML frontmatter) + brand summary from Stitch |
| [STYLE_GUIDELINES.md](./STYLE_GUIDELINES.md) | Brand, layout, elevation, component guidelines |
| [project.json](./project.json) | Raw `get_project` payload |
| [design_systems.json](./design_systems.json) | Raw `list_design_systems` payload |
| [design_system_full.json](./design_system_full.json) | First design system object |
| [screens.json](./screens.json) | Screen list |
| [screen-e9dc00b779774b25b778aceb12e10be2.json](./screen-e9dc00b779774b25b778aceb12e10be2.json) | Screen metadata + download URLs |
| [assets/hrm-dashboard-overview.html](./assets/hrm-dashboard-overview.html) | Generated HTML (Tailwind) |
| [assets/hrm-dashboard-overview.png](./assets/hrm-dashboard-overview.png) | Screenshot |

---

## How this maps to docs

Canonical project docs (derived from this export):

- [../DESIGN_SYSTEM.md](../DESIGN_SYSTEM.md)
- [../COLOR_SYSTEM.md](../COLOR_SYSTEM.md)
- [../TYPOGRAPHY.md](../TYPOGRAPHY.md)
- [../ICON_RULES.md](../ICON_RULES.md)
- [../RESPONSIVE.md](../RESPONSIVE.md)

If Stitch and docs diverge, prefer updating Stitch then re-export, or explicitly revise both.

---

## Re-fetch

```bash
# Requires STITCH_API_KEY / Cursor mcp.json X-Goog-Api-Key
# Tools: get_project, list_design_systems, list_screens, get_screen
# Then: curl -L -o <file> "<downloadUrl>"
```
