## Brand & Style
This design system is built upon a foundation of **Corporate Modernism** with a heavy influence from **Apple’s design language**. The brand personality is professional, efficient, and growth-oriented, designed to evoke a sense of reliability and modern sophistication for Human Resource Management.

The aesthetic prioritizes data clarity and ease of use through a minimalist lens. Key characteristics include high-contrast typography, generous whitespace, and a "soft-tech" feel achieved through substantial corner radii and subtle, natural-looking shadows. The use of a vibrant emerald green as the primary signature color signifies growth, vitality, and positive action within the workplace.

## Layout & Spacing
The layout follows a **8px base grid system** to ensure mathematical harmony across all components.

- **Grid Model**: A 12-column fluid grid is used for the main content area.
- **Margins & Gutters**: Standard page margins are 40px on desktop, scaling down to 16px on mobile. Gutters between cards and dashboard widgets are fixed at 24px.
- **Sidebar**: The dashboard features a fixed-width left sidebar (280px) that collapses into a drawer on mobile devices.
- **Responsiveness**: Elements should stack vertically on mobile (below 768px), with horizontal padding reduced to 16px.

## Elevation & Depth
The system uses **Ambient Shadows** and **Tonal Layers** to create depth without visual clutter.

- **Z-0 (Background)**: The main application background uses the Light Gray (`#f2f2f7`).
- **Z-1 (Cards/Surfaces)**: Primary content containers are pure white with a subtle 1px border (`rgba(0,0,0,0.08)`) and a soft, diffused shadow (`0 4px 6px -1px rgba(0,0,0,0.1)`).
- **Z-2 (Popovers/Modals)**: High-elevation elements use a more pronounced shadow with a larger blur radius to indicate they are floating above the interface.
- **Sidebar**: Defined by a subtle vertical border on the right rather than a shadow, keeping the primary focus on the content area.

## Components

### Buttons
- **Primary**: 12px corner radius, Emerald Green gradient (Top: `#10b981` to Bottom: `#059669`), with white text.
- **Secondary**: Light gray background (`#f2f2f7`) with `#1d1d1f` text.
- **State**: Hover states should involve a subtle darkening of the background; focus states must show a 2px Emerald Green ring with an offset.

### Input Fields
- **Styling**: 12px corner radius, background color `#f2f2f7`, no border in default state.
- **Focus**: Transitions to a white background with a 2px Emerald Green border.
- **Labels**: Placed above the input in `label-md` (Medium weight).

### Cards
- **Construction**: White background, 16px corner radius, and `shadow-md`. 
- **Header**: Often includes a bottom border of 1px `rgba(0,0,0,0.05)` to separate title from content.

### Chips & Badges
- **Success/Active**: Light green tint background with dark green text.
- **Status Indicators**: Fully rounded (pill) with a 12px height for small indicators.

### Sidebar
- **Background**: White (`#ffffff`).
- **Navigation Items**: 8px border radius for the hover/active state highlight. Active items use the primary green for the icon or a small left-side indicator.