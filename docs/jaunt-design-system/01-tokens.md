# Design Tokens

Live version: `npm run dev`, open `/` — every value below renders as a swatch, toggleable between light and dark.

**Source of truth:** [`src/css/tokens/*.css`](../src/css/tokens/) — copied verbatim from the Claude Design handoff bundle (see [`docs/_source-readme.md`](./_source-readme.md)). `tailwind.config.js` does not define any new values; every entry is a direct `var(--token-name)` reference. If a value here ever disagrees with `tokens/*.css`, the CSS file is right.

## Two-layer architecture

**1. Primitives** — raw scales (`--gray-500`, `--teal-600`, `--iris-400`, ...). Never referenced directly in component markup.

**2. Semantic** — role-based aliases (`--surface-card`, `--text-primary`, `--accent`, `--ai-accent`, `--border-default`, ...). What components actually consume. Semantic tokens point at a primitive step under `:root`/`[data-theme="light"]` and get re-pointed under `[data-theme="dark"]` — that's the entire theming mechanism. **Dark is the native theme; light is a first-class peer, not an afterthought** — `[data-theme="dark"]` is written first in intent even though `:root`/`[data-theme="light"]` appears first in the cascade for backward compatibility with unset `data-theme`.

If you're tempted to write `bg-gray-800` in a component "to make dark mode work," stop — that's a primitive. Add or use a semantic token instead.

## Color

**One accent, one AI signal, semantic hues only for status.** This is the single most important color rule in the system (see [00-philosophy.md](./00-philosophy.md), Visual Philosophy):

| Palette | Steps | Role |
|---|---|---|
| `gray` | 0, 25, 50, 100, 150, 200, 300, 400, 500, 600, 700, 800, 850, 900, 950, 1000 | UI chrome, text, borders, surfaces. Very slightly cool ("software" gray, not warm). |
| `teal` | 50–900 | **The** accent. Interactivity, selection, brand. Used nowhere else. |
| `iris` | 50–900 | **Exclusively** AI. Never used for anything the user authored themselves — that separation is what makes "this came from the assistant" legible at a glance without a label. |
| `green` / `amber` / `red` / `blue` | 400/500/600 | Semantic status only (success/warning/danger/info). |
| `viz` | 1–8 | Calm categorical set, data visualization only. |

### Semantic tokens

```
Surfaces:   surface-app · surface-sunken · surface-card · surface-raised ·
            surface-overlay · surface-hover · surface-active · surface-selected
Text:       text-primary · text-secondary · text-tertiary · text-disabled ·
            text-on-accent · text-link
Borders:    border-subtle · border-default · border-strong · border-focus
Accent:     accent · accent-hover · accent-active · accent-subtle ·
            accent-subtle-hover · accent-text · accent-border
AI:         ai-accent · ai-text · ai-subtle · ai-border · ai-glow
Status:     success/warning/danger/info, each with a base + -subtle + -border
Focus ring: ring · ring-ai · ring-danger  (box-shadow spreads, not colors)
```

Tailwind mapping (`tailwind.config.js`): `bg-app`, `bg-card`, `bg-raised`, `bg-sunken`, `bg-hover`, `bg-active`, `bg-selected`, `bg-accent(-hover|-active|-subtle)`, `bg-ai(-subtle)`, `bg-success-subtle`, `text-primary`, `text-accent`, `text-ai-text`, `border-subtle`, `border` (default), `border-strong`, `border-focus`, `border-accent`, `border-ai-border`, `shadow-ring` / `shadow-ring-ai` / `shadow-ring-danger`.

**Contrast** is verified WCAG 2.2 AA (4.5:1 normal text, 3:1 large text/UI) — carried over from the source system's accessibility philosophy. If you introduce a new color pairing, verify it before shipping; don't assume.

## Typography

**Geist** (`font-sans`) for everything UI and display; **Geist Mono** (`font-mono`) for data, IDs, code, and tabular numbers. Loaded from Google Fonts in `tokens/fonts.css` — self-host by swapping the `@import` for local `@font-face` rules if/when that matters for performance or offline builds.

| Token | Size | Typical use |
|---|---|---|
| `text-2xs` | 11px | Micro labels, table meta, `kbd` |
| `text-xs` | 12px | Secondary labels, badges, captions |
| `text-sm` | 13px | **Dense UI default** — rows, menus, inputs |
| `text-base` | 14px | Body default |
| `text-md` | 15px | Comfortable reading body |
| `text-lg` | 17px | Card titles, section leads |
| `text-xl` | 20px | View titles |
| `text-2xl` | 24px | Page headings |
| `text-3xl` | 30px | Feature headings |
| `text-4xl` / `text-5xl` | 38px / 52px | Marketing/hero — rare inside the product |

Semantic type roles (`--type-h1`, `--type-body`, `--type-label`, `--type-caption`, `--type-mono`, ...) compose weight/size/line-height/family in one shorthand — see `tokens/typography.css`. Numbers use tabular/lining figures wherever data lives (`.u-tabular` / `[data-nums="tabular"]`).

## Spacing

4px base unit (`--space-0` … `--space-24`). Product UI lives at 6–16px (`--gap-inline` 8px between icon and label, `--gap-control` 12px between form controls, `--pad-control` 12px control padding, `--pad-card` 20px card interior); layout lives at 24–48px (`--gap-section` 24px, `--pad-view` 24px workspace gutter).

**Fixed shell metrics — shared by every workspace so muscle memory transfers:** `--sidebar-w` 248px, `--sidebar-w-mini` 56px, `--topbar-h` 48px, `--row-h` 36px, `--control-h` 32px (`-sm` 26px, `-lg` 40px).

## Radius

Controls 6px (`radius-sm`), dropdowns/small cards 8px (`radius-md`), cards/panels 10px (`radius-lg`), dialogs 14px (`radius-xl`), pills/avatars/switches full. Never mix radii within one component tier.

## Elevation

Five tiers (`shadow-xs` → `shadow-xl`), soft/low-spread/tinted — Jaunt avoids heavy drop shadows. Menus/popovers use `shadow-md`, dialogs `shadow-lg`/`shadow-xl`. **Dark theme leans on borders + a faint top highlight instead of shadow** — the same depth intent, a different mechanism, per surface. Inputs may use `shadow-inset` for a faint well.

## Motion

Durations 80–260ms; everything interruptible; everything degrades to opacity/instant under `prefers-reduced-motion` (enforced in `tokens/motion.css` via a media query, not opt-in).

| Token | Value | Use |
|---|---|---|
| `dur-instant` | 80ms | Color/background transitions |
| `dur-fast` | 130ms | Hover, transform (paired with `ease-spring`) |
| `dur-base` | 180ms | Default |
| `dur-slow` | 260ms | Panels, reveals |
| `dur-slower` | 400ms | Large surfaces (AI rail entrance) |

`ease-standard` is default; `ease-out` for reveals; toggles/transforms get `ease-spring`. Two pre-composed transitions exist so components don't hand-roll them: `--transition-colors` and `--transition-transform`.

## Iconography

**Lucide**, 1.5px stroke, sized 16px (dense/inline/table), 20px (default control), 24px (nav/headers). Icons inherit `currentColor`, default to `text-secondary`, go `text-primary` on hover/active, `accent` when selected. Pair with a label unless the metaphor is unambiguous (search, close, chevrons) — never decorate. AI actions use a single consistent glyph (Sparkles), tinted iris, everywhere.

## Grid & Layout

12-column fluid grid, `--grid-gutter` 24px. Content max-widths: `--width-prose` 680px (reading), `--width-form` 560px (single-column forms), `--width-content` 1200px (centered workspace content), `--width-wide` 1440px (dashboards, tables).

## Breakpoints

`sm` 640 / `md` 768 (sidebar collapses to mini) / `lg` 1024 (full shell) / `xl` 1280 / `2xl` 1536 — mirrored from `tokens/layout.css` into `tailwind.config.js` screens. Jaunt is desktop-first; breakpoints below `lg` mainly govern graceful degradation.

## Z-index

One source of truth, `tokens/layout.css`: `base` 0, `sticky` 100, `sidebar` 200, `topbar` 300, `dropdown` 400, `overlay` 500, `dialog` 510, `command` 600, `toast` 700. Never hand-write a `z-*` value outside this scale.

---

*Next: [02-app-shell.md](./02-app-shell.md) — how these tokens compose into the universal shell every workspace inherits.*
