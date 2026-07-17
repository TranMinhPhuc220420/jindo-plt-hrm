---
name: Efficient Growth
colors:
  surface: '#f5fbf5'
  surface-dim: '#d5dcd6'
  surface-bright: '#f5fbf5'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#eff5ef'
  surface-container: '#e9efe9'
  surface-container-high: '#e4eae4'
  surface-container-highest: '#dee4de'
  on-surface: '#171d19'
  on-surface-variant: '#3d4a42'
  inverse-surface: '#2c322e'
  inverse-on-surface: '#ecf2ec'
  outline: '#6d7a72'
  outline-variant: '#bccac0'
  surface-tint: '#006c4a'
  primary: '#006948'
  on-primary: '#ffffff'
  primary-container: '#00855d'
  on-primary-container: '#f5fff7'
  inverse-primary: '#68dba9'
  secondary: '#5d5e63'
  on-secondary: '#ffffff'
  secondary-container: '#dfdfe4'
  on-secondary-container: '#616267'
  tertiary: '#9b3e3b'
  on-tertiary: '#ffffff'
  tertiary-container: '#ba5551'
  on-tertiary-container: '#fffbff'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#85f8c4'
  primary-fixed-dim: '#68dba9'
  on-primary-fixed: '#002114'
  on-primary-fixed-variant: '#005137'
  secondary-fixed: '#e2e2e7'
  secondary-fixed-dim: '#c6c6cb'
  on-secondary-fixed: '#1a1c1f'
  on-secondary-fixed-variant: '#45474b'
  tertiary-fixed: '#ffdad7'
  tertiary-fixed-dim: '#ffb3ae'
  on-tertiary-fixed: '#410004'
  on-tertiary-fixed-variant: '#7f2928'
  background: '#f5fbf5'
  on-background: '#171d19'
  surface-variant: '#dee4de'
  apple-black: '#1d1d1f'
  muted-foreground: '#8e8e93'
  destructive-red: '#ff3b30'
  warning-orange: '#ff9f0a'
  info-blue: '#007aff'
  border-subtle: rgba(0,0,0,0.08)
typography:
  headline-xl:
    fontFamily: Inter
    fontSize: 36px
    fontWeight: '700'
    lineHeight: 44px
    letterSpacing: -0.02em
  headline-lg:
    fontFamily: Inter
    fontSize: 28px
    fontWeight: '600'
    lineHeight: 34px
    letterSpacing: -0.01em
  headline-md:
    fontFamily: Inter
    fontSize: 20px
    fontWeight: '600'
    lineHeight: 28px
    letterSpacing: -0.01em
  body-lg:
    fontFamily: Inter
    fontSize: 18px
    fontWeight: '400'
    lineHeight: 28px
  body-md:
    fontFamily: Inter
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 24px
  body-sm:
    fontFamily: Inter
    fontSize: 14px
    fontWeight: '400'
    lineHeight: 20px
  label-md:
    fontFamily: Inter
    fontSize: 14px
    fontWeight: '500'
    lineHeight: 16px
  label-sm:
    fontFamily: Inter
    fontSize: 12px
    fontWeight: '600'
    lineHeight: 16px
    letterSpacing: 0.02em
rounded:
  sm: 0.25rem
  DEFAULT: 0.5rem
  md: 0.75rem
  lg: 1rem
  xl: 1.5rem
  full: 9999px
spacing:
  base: 8px
  xs: 4px
  sm: 12px
  md: 16px
  lg: 24px
  xl: 32px
  gutter: 24px
  margin-page: 40px
---

> **App token mapping:** YAML `colors.primary: '#006948'` is Stitch/Material **deep** chrome. Product CTA green in prose below (`#059669`) is **`primary-brand`**. Implement with named tokens in [../COLOR_SYSTEM.md](../COLOR_SYSTEM.md) — do not treat YAML `primary` and prose “Primary” as the same CSS variable.

# HRM Dashboard Design System

## Brand Personality
- **Professional & Efficient**: Clean layouts that prioritize data clarity.
- **Apple-Inspired Aesthetic**: Minimalist, high contrast, subtle shadows, and rounded corners (12px-16px).
- **Modern Growth**: Accented with a vibrant emerald green (`#059669` / `primary-brand`) for primary actions and positive indicators.

## Color Palette
### Core Colors
- **Primary (brand / CTA)**: `#059669` → app token `primary-brand`
- **Primary (deep / active chrome)**: `#006948` → app token `primary-deep` (YAML `primary`)
- **Background**: `#ffffff` (White)
- **Foreground**: `#1d1d1f` (Apple Black)
- **Secondary**: `#f2f2f7` (Light Gray)
- **Muted**: `#f2f2f7`
- **Muted Foreground**: `#8e8e93`
- **Destructive**: `#ff3b30` (Apple Red)

### Semantic Colors
- **Success**: `#059669`
- **Warning**: `#ff9f0a`
- **Info**: `#007aff`
- **Error**: `#ff3b30`

## Typography (Inter)
- **Scale**: Base 16px.
- **Weights**: Light (300), Normal (400), Medium (500), Semibold (600), Bold (700).
- **Headings**: Semibold/Bold with tight letter-spacing.

## UI Components
- **Sidebar**: Light background (`#ffffff`), subtle right border (`rgba(0,0,0,0.08)`).
- **Cards**: White background, 12px-16px border radius, subtle shadow (`shadow-md`).
- **Buttons**: Rounded (12px), primary using green gradient.
- **Inputs**: Light gray background (`#f2f2f7`), focus ring in primary green.

## Spacing & Layout
- **Grid**: 8px base system.
- **Radius**: Standard 12px (`--radius`), Large 16px (`--radius-lg`).
- **Shadows**: Soft Apple-style elevation.
