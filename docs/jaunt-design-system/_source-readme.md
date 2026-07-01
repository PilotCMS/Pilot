# Jaunt Design System

**Jaunt is an AI-native Destination Operating System** — the software backbone for
destination marketing organizations (DMOs), tourism boards, convention & visitors
bureaus (CVBs), and chambers of commerce. It replaces the dated, cluttered,
enterprise-heavy tools these teams live in today (e.g. SimpleView) with something
that feels like it was built in 2027: calm, intelligent, and incredibly responsive.

This repository is the **visual and interaction foundation** every Jaunt product
inherits — CRM, CMS, Listings, Events, Campaigns, Analytics, Media, and whatever
comes next. The goal: every future Jaunt app should look and feel as if the same
small team designed it, patiently, over a decade. *"Linear for destination
organizations"* — the same clarity, polish, and confidence, in a language that is
uniquely Jaunt's.

> **Sources.** This is a greenfield system with no prior codebase or Figma file.
> It was authored from the Jaunt product brief. Design inspirations cited by the
> brief: Linear, Notion, Figma, Cursor, Arc, Vercel, Raycast. If a codebase or
> Figma file exists later, link it here so future contributors can reconcile.

---

## Product philosophy

Five principles, and *why* each exists.

1. **Clarity over density.** DMO staff juggle listings, partners, events, and
   campaigns simultaneously. The interface earns trust by showing the *right*
   information plainly, not the *most* information. Whitespace and hierarchy are
   features. *Why:* confused users slow down and distrust the data.

2. **Speed over cleverness.** Power users live in Jaunt eight hours a day. Every
   interaction targets sub-100ms perceived response — optimistic updates,
   instant navigation, keyboard-first. *Why:* for a daily tool, latency is the
   single biggest driver of how "good" the software feels.

3. **Opinionated UX over unlimited customization.** Jaunt makes the good path the
   default path. Fewer settings, stronger conventions, one obvious way to do a
   thing. *Why:* every knob is a decision offloaded onto the user and a surface
   that can drift out of consistency.

4. **AI as a teammate, not a feature.** AI lives *inline*, where the work is —
   drafting a listing description, spotting an anomaly, filling a field. It is
   never a bolted-on chatbot in a corner. *Why:* the value of AI is proportional
   to how little friction stands between intent and result.

5. **Calm, then confident.** Surfaces are quiet by default; emphasis is spent
   deliberately (one accent, one AI signal, sparse color). *Why:* a calm baseline
   makes the few loud moments — a destructive action, an AI insight — actually
   register.

**Interaction philosophy.** Keyboard-first, mouse-friendly. Every primary action
has a shortcut; the command palette (`⌘K`) is the universal entry point. State
changes are optimistic and reversible (undo over confirm). Nothing blocks — long
work streams in.

**Visual philosophy.** A near-monochrome canvas carried by a single teal accent
and a strict neutral ramp. Depth from hairline borders and one soft shadow tier,
not heavy drop shadows or gradients. Dark is the native environment; light is a
first-class peer.

**Motion philosophy.** Motion narrates state — where a thing came from, that a
save landed, that AI is thinking. 80–260ms, spring-tinted, always interruptible,
always degradable to opacity/instant under `prefers-reduced-motion`. If a motion
doesn't clarify a state change, it's cut.

**Accessibility philosophy.** WCAG 2.2 AA is the floor, not the ceiling. Text
meets 4.5:1 (3:1 for large); interactive targets ≥ 44px in touch contexts;
every interaction reachable and legible by keyboard with a visible focus ring;
color never the sole carrier of meaning (icon + text back it up); motion and AI
states announced to assistive tech. *Why:* public-sector tourism orgs carry
procurement accessibility requirements — and it's simply correct.

---

## Content fundamentals — how Jaunt speaks

Voice: **confident, concise, optimistic, human.** Like a sharp colleague who
respects your time. Never corporate, never cutesy.

- **Person.** Address the user as **"you."** Jaunt refers to itself as **"Jaunt"**,
  not "we," in system messages. AI speaks in first person sparingly ("I found 3
  duplicates") only inside clearly-AI surfaces.
- **Casing.** **Sentence case everywhere** — buttons, headings, menus, labels.
  Never Title Case UI. (`Add listing`, not `Add Listing`.)
- **Tense & length.** Present tense, active voice, short. Buttons are
  **verb-first** (`Publish`, `Invite partner`, `Generate summary`). Aim for 1–3
  words on buttons, one line on labels.
- **No filler.** Cut "please," "in order to," "we're sorry for the
  inconvenience." Cut adverbs. State the thing.
- **Numbers & data** use tabular figures and are never faked for decoration.
- **Emoji:** not used in product UI. (Flag/place glyphs may appear in *user
  content* like listings, never in chrome.)
- **Errors** are blameless and actionable: say what happened, then the way
  forward. `Couldn't publish — 2 listings are missing a category. Review them →`
- **Empty states** are invitations, not apologies: `No events yet. Import a
  calendar or add your first.` + a primary action.
- **AI copy** is grounded and never overclaims: `Drafted from your listing
  details. Review before publishing.` Confidence is shown, not implied.

Micro-examples:
| Context | Jaunt says |
|---|---|
| Primary button | `Publish listing` |
| Destructive confirm | `Delete 3 listings? This can't be undone.` / `Delete` · `Cancel` |
| Toast (success) | `Listing published.` `View →` |
| Error | `Upload failed — file is over 25 MB. Try a smaller image.` |
| Empty state | `Nothing scheduled. Create a campaign to get started.` |
| AI insight | `Web referrals dropped 18% week-over-week. Mostly from the events page.` |
| Loading | `Summarizing 240 reviews…` |

---

## Visual foundations

**Color.** A near-monochrome neutral ramp (very slightly cool gray) does 90% of
the work. One signature accent — **Jaunt teal** (`--teal-500 #06a89a`) — carries
interactivity, selection, and brand. **Iris** (`--iris-600 #7059f7`) is reserved
*exclusively* for AI. Semantic hues (green/amber/red/blue) appear only for status.
A calm 8-color categorical set is reserved for data viz. No decorative gradients;
the only gradients allowed are (a) subtle protection scrims over media and (b) the
faint AI "shimmer" on streaming surfaces.

**Type.** **Geist** (sans) for everything UI and display; **Geist Mono** for data,
IDs, code, and tabular numbers. Dense product UI defaults to 13–14px. Headings
are semibold with tightened tracking; body is regular. Numbers use lining/tabular
figures in tables.

**Space & layout.** 4px base grid; product UI lives at 6–16px, layout at 24–48px.
Fixed shell metrics (sidebar 248px, topbar 48px, row 36px) are shared by every
workspace so muscle memory transfers. 12-column fluid content grid, centered
within `--width-wide` for dashboards.

**Backgrounds.** Flat surfaces, no textures or patterns. Full-bleed imagery
appears only in *content* (listing photos, media library, marketing) — never in
app chrome. Over imagery, a bottom-up protection scrim
(`rgba(0,0,0,0) → rgba(0,0,0,.6)`) guarantees legible captions.

**Borders & cards.** Depth comes from **hairline borders** (`--border-default`)
plus one soft shadow tier. A **card** = `--surface-card`, `1px --border-default`,
`--radius-lg (10px)`, `--shadow-sm`, `--pad-card (20px)`. In dark theme, cards
lean on border + a faint highlight instead of shadow. No colored-left-border
cards, no double borders.

**Corner radii.** Controls 6px, dropdowns/small cards 8px, cards/panels 10px,
dialogs 14px, pills/avatars full. Consistent per tier — never mix radii within
one component.

**Elevation.** Soft, low-spread, tinted shadows in five tiers. Menus/popovers use
`--shadow-md`, dialogs `--shadow-lg/xl`. Inputs may use a faint inset well.

**Motion & easing.** Default `--ease-standard cubic-bezier(0.2,0,0,1)`; reveals use
`--ease-out`; toggles get a hint of spring. Durations 80–260ms. Menus fade+scale
from 98%→100% and slide 4px; dialogs fade + rise 8px; rows highlight on select;
AI streams token-by-token with a soft caret.

**Hover / focus / press.**
- *Hover:* a translucent overlay (`--surface-hover`, ~4% ink) — not a color swap —
  so hover reads consistently on any surface. Buttons darken one accent step.
- *Focus:* always a visible ring (`--ring`, a 3px teal spread) via box-shadow;
  keyboard focus never suppressed.
- *Press:* background deepens (`--surface-active`) and the element scales to
  ~0.98; no bounce.

**Transparency & blur.** Used sparingly: the command palette and top bar use a
`backdrop-filter: blur(12px)` over a translucent surface; scrims behind dialogs
are solid-tint translucency. Never blur body content for decoration.

**Imagery vibe.** Destination photography is warm, bright, human, and real —
places and people, not stock clichés. Neutral-to-warm grade, natural light. B&W
only for avatars-fallback initials. Grain is never added.

---

## Iconography

- **System:** [**Lucide**](https://lucide.dev) — a clean, consistent, open
  20-icon-metaphor-per-concept set with a **1.5px stroke** at 20–24px that matches
  Jaunt's grotesk weight. Loaded from CDN in cards/kits
  (`https://unpkg.com/lucide@latest`); in production, install `lucide-react` /
  `lucide` and tree-shake.
  - **Substitution flag:** Jaunt has no bespoke icon font yet, so Lucide is used
    as the house set. If a custom icon family is commissioned later, swap it in
    behind the same 20/24px, 1.5px-stroke contract. *Please confirm Lucide is
    acceptable, or provide a preferred set.*
- **Sizing:** 16px (dense inline / table), 20px (default control), 24px (nav,
  headers). Stroke stays 1.5px; scale the box, not the stroke perception.
- **Color:** icons inherit `currentColor` and default to `--text-secondary`,
  going `--text-primary` on hover/active, `--accent` when selected.
- **Usage:** icons clarify, never decorate. Pair with a label unless the metaphor
  is unambiguous (search, close, chevrons). No filled/duotone mixing.
- **Emoji / unicode:** not used as UI icons. `⌘ ⌥ ⇧ ↵ ⌫` glyphs are used only in
  keyboard-hint (`kbd`) chips.
- **AI mark:** AI actions use a single consistent glyph (a sparkle) tinted iris —
  see the AI components — so "this is AI" is instantly recognizable.

---

## Repository index

| Path | What's there |
|---|---|
| `styles.css` | Global entry point (consumers link this). `@import` manifest only. |
| `tokens/` | `fonts, colors, typography, spacing, radius, elevation, motion, layout, base`. Light + dark. |
| `components/forms/` | Button, IconButton, Input, Textarea, Select, Checkbox, Radio, Switch, Tag, FileUpload |
| `components/feedback/` | Badge, Toast, Alert, Dialog, Tooltip, Progress, Skeleton |
| `components/navigation/` | Tabs, Menu (dropdown/context), Breadcrumbs, CommandPalette |
| `components/data/` | Table, DataCard, Kanban, Avatar, EmptyState, Timeline |
| `components/ai/` | AIInline, AISuggestion, AIStreaming, ConfidenceBadge |
| `ui_kits/app/` | The universal Jaunt application shell + CRM, Listings (Kanban), and Analytics workspace screens |
| `guidelines/` | Foundation specimen cards (Colors, Type, Spacing, Elevation, Motion, Brand) |
| `assets/` | Logo, AI mark, brand imagery |
| `SKILL.md` | Portable Agent-Skill wrapper for using this system elsewhere |

**Component contract.** Every component defines: visual appearance · states ·
interaction behavior · keyboard behavior · accessibility considerations · AI
enhancement opportunities. See each component's `.prompt.md`.

**Engineering alignment (Laravel · Livewire · Blade · Tailwind).**
- **Tokens → Tailwind.** Generate `tailwind.config` theme from these CSS variables
  (a small build step reads `tokens/*.css`). Tailwind utilities reference the vars
  (`colors: { accent: 'var(--accent)' }`) so runtime theming (light/dark) is a
  single `data-theme` swap with zero rebuild.
- **Components → Blade.** Each React primitive here maps 1:1 to a Blade component
  `<x-jaunt.button variant="primary">`. Props mirror the `.d.ts` interface.
  Interactivity that needs server state uses Livewire; purely visual state
  (hover, open/closed) stays in Alpine/CSS.
- **Naming.** `jaunt.<group>.<name>` for Blade (`jaunt.forms.input`), `--<role>`
  for tokens, `.j-<block>` if any bespoke CSS is unavoidable (prefer utilities).
- **Strategy.** Ship tokens first (they're the contract), then primitives, then
  the shell as a Blade layout every workspace extends. Keep the React kits here as
  the visual source of truth; Blade components are the production mirror.

See below for CONTENT FUNDAMENTALS, VISUAL FOUNDATIONS, and ICONOGRAPHY in full
(above). This file is the manifest — start here, then open the Design System tab.
