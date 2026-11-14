# Design System — Roku (AI Hiring Agent)

Source of truth: the landing page at `resources/views/welcome.blade.php`, implemented from the Figma file *Roku | AI hiring agent* (node `4:286`).

## 1. Visual Theme & Atmosphere

Roku reads like a quiet, editorial product brief: pure white paper, black type, zinc grays, and a single confident blue. Nothing is boxed in. Sections are separated by generous whitespace and, at most, a 1px hairline divider — no cards, no gradients, no heavy shadows. The page trusts typography to carry hierarchy.

The two signature gestures are **typographic contrast** and **monospace annotations**. The hero headline pairs a black medium-weight line with a muted zinc regular-weight line ("Screen candidates in seconds. / You make the final call."), so emphasis comes from weight and tone rather than color. Small uppercase monospace labels ("CAPABILITIES", "— TESTIMONIAL", "01 / 02 / 03") act like editor's margin notes, signalling structure without decoration.

**Key Characteristics:**
- Pure white canvas (`#FFFFFF`) — clean, clinical, product-first
- One accent color: Brand Blue (`#2563EB`), reserved for primary CTAs
- Geist for everything readable; Geist Mono only for eyebrow labels, numbering, and compliance badges
- Two-tone headlines: black medium + zinc-500 regular for the second line
- Cardless layouts: 3-column editorial lists with top hairline dividers
- Small, compact controls (12px labels, 12px radius) — the UI whispers, the headline speaks
- Sticky translucent header with backdrop blur
- Zero gradients, zero decorative shadows

## 2. Color Palette & Roles

### Primary
- **Brand Blue** (`#2563EB`, Tailwind token `brand`): Primary CTA background ("Get started", "Start free trial", "Start 14-day trial"). Hover: `brand/90`. Never used for body text or large surfaces.
- **Black** (`#000000`): Headlines, metric values, feature titles, strong labels, and the logo tile.

### Surface & Background
- **White** (`#FFFFFF`): Page canvas, footer, and secondary button fill.
- **Header Glass** (`rgba(255,255,255,0.9)` + `backdrop-blur-md`): Sticky header surface.

### Neutrals & Text (Tailwind zinc scale)
| Token | Hex | Use |
|-------|-----|-----|
| `zinc-800` | `#27272A` | Secondary button label |
| `zinc-600` | `#52525B` | Body copy, subtitles, nav links, feature descriptions |
| `zinc-500` | `#71717A` | Muted secondary headline line, metric descriptions, footer links, captions, badges |
| `zinc-400` | `#A1A1AA` | Monospace eyebrows, numbering, copyright & legal links |
| `zinc-300` | `#D4D4D8` | Secondary button border |
| `zinc-200` | `#E4E4E7` | Hairline dividers, avatar border |

### Gradient System
None. Depth comes only from whitespace and hairline borders.

## 3. Typography Rules

### Font Family
- **Primary**: `Geist` (Tailwind `font-geist`), weights 400 / 500 / 600 / 700. Loaded via `bunny()` in `vite.config.js`.
- **Mono**: `Geist Mono` (Tailwind `font-geist-mono`), weight 400. Fallback `Liberation Mono`, `ui-monospace`.

### Hierarchy

| Role | Size | Weight | Line Height | Letter Spacing | Notes |
|------|------|--------|-------------|----------------|-------|
| H1 (hero) | 64px (`lg`), 48px (`sm`), 36px (mobile) | 500 / 400 | 64px | -0.04em (-2.56px) | Line 2 is weight 400, `zinc-500` |
| Hero subtitle | 18px (16px mobile) | 400 | 28px | normal | `zinc-600`, max-width 576px |
| H2 (CTA) | 36px (30px mobile) | 500 | 40px | -0.9px | Centered |
| H2 (section) | 30px | 500 | 36px | -0.75px | Left-aligned |
| Metric value | 30px | 500 | 36px | -0.75px | |
| Testimonial quote | 20px (18px mobile) | 400 | 28px | -0.5px | `text-balance` |
| H3 (feature) / Brand | 14px | 600 | 20px | -0.35px | |
| Section description | 14px | 400 | 20px | normal | `zinc-600` |
| Small label / Button | 12px | 500–600 | 16px | normal | Nav links, CTAs, metric labels |
| Small body | 12px | 400 | 19.5px | normal | Feature & metric descriptions |
| Eyebrow (mono) | 11–12px | 400 | 16.5px / 16px | +1.1px / +1.2px (≈0.1em) | Uppercase, `zinc-400` |
| Numbering (mono) | 12px | 400 | 16px | normal | "01", "02", "03" |
| Footer column header | 12px | 600 | 16px | -0.3px | Black, not uppercase |

### Principles
- **Contrast through weight and tone, not color.** Black 500 vs zinc 400 is the main hierarchy device.
- **Negative tracking scales with size**: -0.04em on display, ~-0.025em on 30–36px, -0.35px on 14px titles.
- **Uppercase is mono-only.** Only Geist Mono eyebrows are uppercase; Geist text is never uppercased.
- **Small body is 12px with a generous 19.5px line-height (1.625)** for an airy, editorial feel.

## 4. Component Stylings

### Buttons

**Primary — Brand Blue**
- Background `brand` (`#2563EB`), text white, 12px / 500
- Radius 12px (`rounded-xl`)
- Padding 10px 24px (header variant: 6px 16px)
- Trailing 16×16 white arrow icon (`images/landing/arrow-right-white.svg`), gap 8px (header 6px)
- Shadow `drop-shadow-[0_1px_1px_rgba(0,0,0,0.05)]`
- Hover `bg-brand/90`

**Secondary — Outline**
- Background white, text `zinc-800`, 12px / 500
- Border 1px `zinc-300`, hover `zinc-500`
- Radius 12px, padding 11px 25px (matches primary height)

**Text Link — Ghost**
- Text `zinc-600` 12px / 500, padding 4px 8px, hover black. Used for "Log in" and nav links.

**Inline Arrow Link**
- Text black 12px / 500 followed by a 16×16 black arrow (`images/landing/arrow-right-black.svg`), gap 6px. Used at the bottom of feature items.

### Logo
- Black square tile, radius 2px (`rounded-xs`), white bold "R" — 24px tile / 15px letter in header, 16px tile / 10px letter in footer.
- Header: tile + "oku" (reads "Roku"); footer: tile + "Roku".

### Header
- `sticky top-0`, `bg-white/90 backdrop-blur-md`, height 56px, max-width 1024px, padding-x 24px
- 3-column grid (`grid-cols-[1fr_auto_1fr]`) so the nav stays exactly centered regardless of left/right widths
- Nav links gap 28px, hidden below `md`
- Right actions: ghost "Log in" + primary "Get started" (authenticated: single "Dashboard" button)

### Metrics Strip
- 3 equal columns, gap 32px, max-width 896px; no boxes
- Value (30px) → label (12px / 600) → description (12px / `zinc-500`), gap 3px

### Editorial Feature List
- Eyebrow + H2 + description stacked with 8px gap; 56px gap to list
- 3 columns, gap 40px; each item has `border-t border-zinc-200`, padding-top 20px
- Mono number → title → description (gap 7px), then inline arrow link pinned to the bottom (`justify-between`)

### Testimonial
- Centered, max-width 768px, padding 80px vertical, gap 24px
- Mono eyebrow "— TESTIMONIAL", quote, then figcaption: 36px avatar (radius 12px, 1px `zinc-200` border, `object-cover`) + name (12px / 600) and role (11px / `zinc-500`)

### CTA Section
- Centered, max-width 576px: mono eyebrow → H2 → description → button pair (gap 12px) → mono compliance badges separated by "•" (gap 24px, `zinc-500`)

### Footer
- White background, padding 64px top / 48px bottom, max-width 896px
- 5-column grid: brand block spans 2 columns, then Product / Resources / Company (link list gap 10px)
- Bottom row: copyright left, legal links right (gap 24px), both `zinc-400` 12px

## 5. Layout Principles

### Spacing System
- **Base unit**: 4px (Tailwind default); common steps 8 / 12 / 24 / 32 / 56 / 64 / 80 / 128 / 144
- **Section rhythm** (desktop): 128–144px top padding before major sections; ~48–96px on mobile
- **Hero**: ~88px below the header

### Containers
| Width | Use |
|-------|-----|
| 1024px (`max-w-5xl`) | Header |
| 896px (`max-w-4xl`) | Hero, metrics, features, footer |
| 768px (`max-w-3xl`) | Testimonial |
| 576px (`max-w-xl`) | Hero subtitle, CTA section |
| 558px | Hero integration illustration |

All containers are centered with a 24px horizontal gutter (`px-6`).

### Whitespace Philosophy
Whitespace is the divider. Prefer a large gap over a border, and a hairline over a card.

### Border Radius Scale
| Radius | Use |
|--------|-----|
| 2px (`rounded-xs`) | Logo tile |
| 12px (`rounded-xl`) | Buttons, avatar |

## 6. Depth & Elevation

| Level | Treatment | Use |
|-------|-----------|-----|
| 0 | none | Everything by default |
| 1 | `drop-shadow-[0_1px_1px_rgba(0,0,0,0.05)]` | Primary blue buttons only |
| Glass | `bg-white/90 backdrop-blur-md` | Sticky header |

## 7. Do's and Don'ts

### Do
- Keep the canvas pure white and let whitespace separate sections
- Use Brand Blue only for the primary action in each group
- Pair a black medium line with a zinc regular line for hero-style headlines
- Use Geist Mono uppercase eyebrows to label sections
- Use top hairline dividers (`zinc-200`) instead of cards for lists
- Keep controls compact: 12px labels, 12px radius, 16px icons
- Center the header nav with a 3-column grid

### Don't
- Don't add gradients, colored backgrounds, or large shadows
- Don't wrap features or metrics in cards
- Don't use blue for text, icons in body copy, or secondary buttons
- Don't uppercase Geist (sans) text
- Don't introduce extra accent colors; grays come from the zinc scale

## 8. Responsive Behavior

| Breakpoint | Changes |
|------------|---------|
| Mobile (< 640px) | H1 36px; metrics, features, and footer columns stack (footer 2 columns); legal row stacks under copyright |
| `sm` (≥ 640px) | H1 48px; metrics and features become 3 columns; footer 5 columns |
| `md` (≥ 768px) | Header nav links appear; header becomes 3-column grid |
| `lg` (≥ 1024px) | H1 64px / 64px line-height |

Button groups use `flex-wrap` + `justify-center` so they wrap gracefully on narrow screens. Images scale with `w-full h-auto` up to their design width.

## 9. Agent Prompt Guide

### Quick Reference
- Background: White `#FFFFFF`
- Primary CTA: Brand Blue `#2563EB` (`bg-brand`), white text, 12px radius
- Heading: Black, Geist 500, negative tracking
- Body: `zinc-600`; muted: `zinc-500`; eyebrow: `zinc-400` Geist Mono uppercase
- Divider: 1px `zinc-200`
- Assets: `public/images/landing/` (`hero-integrations.png`, `testimonial-avatar.jpg`, `arrow-right-white.svg`, `arrow-right-black.svg`)

### Example Prompts
- "Primary button: `bg-brand` white 12px/500 text, `rounded-xl`, `px-6 py-2.5`, trailing 16px white arrow, `drop-shadow-[0_1px_1px_rgba(0,0,0,0.05)]`."
- "Section header: Geist Mono 11px uppercase `zinc-400` eyebrow with 0.1em tracking, 30px/500 black H2 with -0.75px tracking, 14px `zinc-600` description."
- "Feature list: 3-column grid, gap 40px, each item `border-t border-zinc-200 pt-5` with mono number, 14px/600 title, 12px `zinc-600` description, and a black inline arrow link at the bottom."

## 10. Application Screens (Auth, Workspace, Public Forms)

The same system applies inside the product, with a few additions for dense, data-heavy screens.

### Theme Tokens (`resources/css/app.css`)
| Token | Value | Use |
|-------|-------|-----|
| `canvas` | `#FAFAFA` (zinc-50) | Workspace background behind cards and tables |
| `paper` | `#FFFFFF` | Cards, sidebar, inputs |
| `ink` | `#000000` | Primary text |
| `muted` | `#71717A` (zinc-500) | Secondary text |
| `line` | `#E4E4E7` (zinc-200) | Borders, dividers, table rules |
| `accent` / `brand` | `#2563EB` | Primary CTAs, focus outline |
| `font-sans` | Geist | All UI text |
| `font-mono` | Geist Mono | Eyebrows, table headers, section labels |

### Additions
- **Containers**: white `paper` surface, 1px `line` border, radius 16px (`rounded-2xl`). No shadows.
- **Inputs & small surfaces**: radius 12px (`rounded-xl`); icon-only buttons 8px (`rounded-lg`).
- **Status badges & dots**: the only full-pill (`rounded-full`) shapes left in the UI.
- **Sidebar**: Roku logo tile at top; active item `bg-zinc-100 text-ink`, hover `bg-zinc-50`.
- **Section labels / table headers**: `font-mono text-[11px] tracking-[0.1em] text-zinc-400 uppercase` — never prefixed with a colored dot.
- **Required marker**: `*` in `text-red-500`.
- **Primary button** (`<x-primary-button>`): `bg-brand` white 14px/500, `rounded-xl`, min-height 44px, trailing white arrow icon.
- **Auth pages**: white page, centered Roku logo above a bordered white card (max-width 448px).
- **Public layout** (`<x-layout>`): same sticky translucent header as the landing page, 1024px max width, slim footer.
