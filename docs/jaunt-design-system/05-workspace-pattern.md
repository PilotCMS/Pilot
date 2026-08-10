# Workspace Pattern

Live: `npm run dev`, open [`/preview/shell.html`](../preview/shell.html), click any sidebar item to swap the `view` region.

This is the proof of principle 1 in [00-philosophy.md](./00-philosophy.md) ("One interface, infinite workspaces"): every Jaunt product — CRM, Listings, Analytics, Campaigns, Media, and (not yet built) Events/Home — renders inside the exact same shell, using the exact same 30-primitive component vocabulary documented in [03-component-library.md](./03-component-library.md). A user who has learned to filter a table in Partners already knows how to filter a table anywhere else in the product, because it is, literally, the same table.

## The pattern

The shell ([02-app-shell.md](./02-app-shell.md)) owns sidebar, topbar, command palette, and the `view` region's gutter/ceiling — and nothing else inside `view`. Concretely:

- **Gutter**: `--pad-view` (28px, `tokens/spacing.css`) — the left/right padding every workspace screen's head/toolbar/body rows use, so page edges line up identically across workspaces.
- **Ceiling**: `--width-wide` (1440px, `tokens/layout.css`) — the max content width for dashboards/tables; a workspace screen may choose not to apply it (e.g. a full-bleed kanban board that wants to use all available width), but when it does, it uses the same token every other workspace would.
- **Everything else in `view` belongs to the workspace**, not the shell. The shell never imports a workspace screen's markup, and a workspace screen never touches the sidebar/topbar.

A workspace screen is structurally always three stacked regions — this shape isn't enforced by a shared Blade layout (that would be a Tier-2 concern per [09-engineering-alignment.md](./09-engineering-alignment.md)), it's a convention every screen in this repo happens to follow, mirroring the source's `.view__head` / `.view__toolbar` / `.view__body` classes (`ui_kits-source/app/app.css`):

1. **Head** — title + one-line description on the left, primary actions (usually one `secondary` + one `primary` button) on the right.
2. **Toolbar** (optional) — `jaunt.navigation.tabs` for view filters, a search `jaunt.forms.input`, view-mode toggles. Only screens with a list/table/grid of records have one (CRM, Listings); Analytics/Campaigns/Media skip it.
3. **Body** — the workspace-specific content: a table, a kanban board, a card grid, a dashboard of KPIs/chart/panels, or an empty state.

## The five screens

Built as Blade partials in [`components/screens/`](../components/screens/), each named `x-jaunt.screens.{name}`, ported 1:1 from `ui_kits-source/app/screens.jsx`'s five screen functions plus mock shapes from `ui_kits-source/app/data.js`. None of these are registered in the shell's routing (there is none yet — Tier 2) — `preview/shell.html` hand-translates each into the static-HTML-Alpine mirror pattern established for the shell itself, switched by the sidebar's existing `active` Alpine variable.

### Analytics — `components/screens/analytics.blade.php`
The dashboard: 4 KPI tiles, a "site visitors" bar chart, an AI insights panel, and a recent-activity timeline. Composes `jaunt.forms.select`, `jaunt.forms.button`, `jaunt.feedback.badge`, `jaunt.ai.ai-streaming` (typewriter mode), `jaunt.ai.confidence-badge`, and `jaunt.data.timeline`. Unique to this screen: the KPI tile grid and the bar chart, which have no Tier-1 primitive equivalent (see "What's hand-built" below) — translated from the source's `.kpi`/`.panel`/`.bars` CSS classes (`ui_kits-source/app/app.css`) into Tailwind utilities on semantic tokens (`bg-card`, `border-[color:var(--border-default)]`, `shadow-sm`).

### CRM (Partners) — `components/screens/crm.blade.php`
Tabs (All/Active/Prospects) + a filter input over a `jaunt.data.table`, with a per-row `jaunt.navigation.menu` (Open/Rename/Draft outreach (AI)/Archive) and a bulk-actions bar (Email/Tag/Archive) that appears on selection. Unique to this screen: none visually — it's the reference example of "compose Table + Tabs + Menu and you're done," which is the point. Row data is pre-shaped into the table's declarative `type: avatar|badge|text|currency` columns per `table.blade.php`'s own contract (its `render` closures have no Blade equivalent — see [03-component-library.md](./03-component-library.md), "What's verified vs. what's a judgment call").

### Listings — `components/screens/listings.blade.php`
Tabs (All/Published/Draft) + search + grid/list view toggles over a card grid (`grid-cols-[repeat(auto-fill,minmax(240px,1fr))]`, translated from the source's `.grid-cards`), each tile a `jaunt.data.card` with a gradient media placeholder (per-listing `hue`, not a real image), a status badge in the header's `action` slot, and a footer with view count + row menu (Preview/Improve with AI/Delete). Deliberately **not** a kanban — the source uses cards for Listings and kanban for Campaigns; see "why the layout differs" below.

### Campaigns — `components/screens/campaigns.blade.php`
The lightest screen: a page head plus a `jaunt.data.kanban` (4 columns — Idea/In progress/Review/Live — with tag pills and avatar-cluster footers on each card). Almost everything interesting here already lives in the Kanban primitive itself (drag/drop, add-card affordance); the screen partial is mostly plumbing mock data into its `columns` prop shape.

### Media — `components/screens/media.blade.php`
The lightest screen in the source itself: a page head plus a single AI-variant `jaunt.data.empty-state` ("Your media library is empty" / "Upload photos and Jaunt will auto-tag them...") inside a bare card surface. No bespoke chart/KPI markup to translate — this screen exists mainly to prove the empty-state pattern reads correctly inside the real shell, not just in isolation on the component gallery.

## What's hand-built vs. composed

Per [03-component-library.md](./03-component-library.md)'s "30 primitives" — KPI tiles and the bar chart are the only genuinely new visual vocabulary these five screens introduce, and they're intentionally *not* promoted to Tier-1 primitives here: they appear in exactly one screen (Analytics) and are simple enough (a bordered card + a flex-based bar row) that a shared component would add an abstraction layer for a single caller. If a second workspace needs a KPI tile or bar chart, that's the trigger to extract one — not before (see [00-philosophy.md](./00-philosophy.md) principle 3, "one obvious way to do a thing," which cuts against premature componentization as much as against sprawl).

Both are built from semantic tokens only — `bg-card`, `border-[color:var(--border-default)]`, `shadow-sm`, `text-tertiary`, `text-success`/`text-danger` for KPI deltas — never raw hex or a new Tailwind color key, matching the token discipline in [01-tokens.md](./01-tokens.md).

## Why the layout differs per workspace

The shell and component vocabulary are identical everywhere; the *arrangement* is not, and shouldn't be — a destination's marketing team thinks about partners as rows in a list (CRM → table), listings as visual tiles to scan (Listings → card grid), and campaigns as a pipeline to move through stages (Campaigns → kanban). Principle 1 is "one interface, infinite workspaces," not "one layout for every kind of data" — forcing every screen into the same layout would optimize for a fake consistency (identical markup) at the cost of the real one (a component vocabulary and interaction model the user already knows, applied to whatever shape the data actually is).

## Shell wiring (`preview/shell.html`)

The shell's `x-data` already tracks `active` (the current workspace id) for the sidebar's active-state styling and the topbar breadcrumb (see [02-app-shell.md](./02-app-shell.md)). The `view` region is now a `<template x-if>` chain keyed off `active`, one branch per screen, each holding that screen's hand-translated static-HTML-Alpine markup (mirroring the Blade partial exactly, same pairing convention as `components/shell/sidebar.blade.php` ↔ its markup in `shell.html`). Workspaces without a built screen yet (Home, Events) fall through to the original placeholder block, so clicking every sidebar item always renders *something*, never a blank pane.

---

*Previous: [03-component-library.md](./03-component-library.md). See [02-app-shell.md](./02-app-shell.md) for the shell these screens render inside.*
