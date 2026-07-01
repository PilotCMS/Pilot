# Application Shell

Live: `npm run dev`, open [`/preview/shell.html`](../preview/shell.html). Try: click sidebar items, `⌘K` for the command palette (type to see the AI row appear), theme toggle.

The shell is the one layout every Jaunt workspace inherits — CRM, CMS, Listings, Events, Campaigns, Analytics, Media. A user who's learned to navigate any one of them already knows all of them (see [00-philosophy.md](./00-philosophy.md), principle 1).

## Anatomy

```
┌─────────────┬──────────────────────────────────────────┐
│             │  Topbar (breadcrumbs · Ask Jaunt · search │
│   Sidebar   │  · notifications · theme)         48px    │
│   248px     ├──────────────────────────────────────────┤
│             │                                            │
│  Workspace  │  View (workspace content — Livewire owns   │
│  switcher   │  this region; the shell never does)        │
│  Search     │                                            │
│  Nav        │                                            │
│  Pinned     │                                            │
│             │                                            │
│  User       │                                            │
└─────────────┴──────────────────────────────────────────┘
```

Fixed metrics (`tokens/spacing.css`): sidebar 248px (56px mini, `md` breakpoint and below), topbar 48px, row 36px. These never vary per workspace — that consistency is what makes muscle memory transfer between products.

## Components

| Piece | Blade component | Source |
|---|---|---|
| Sidebar | `components/shell/sidebar.blade.php` | `Shell.jsx` (`Sidebar`) + `app.css` `.sb*` |
| Topbar | `components/shell/topbar.blade.php` | `Shell.jsx` (`Topbar`) + `app.css` `.topbar*` |
| Breadcrumbs | `components/navigation/breadcrumbs.blade.php` | `Breadcrumbs.jsx` |
| Command palette | `components/navigation/command-palette.blade.php` | `CommandPalette.jsx` |
| Avatar (sidebar user) | `components/data/avatar.blade.php` | `Avatar.jsx` |
| Icon button, tooltip | `components/forms/icon-button.blade.php`, `components/feedback/tooltip.blade.php` | `IconButton.jsx`, `Tooltip.jsx` |

## Workspace switcher

Top of the sidebar. Shows the current workspace's initial (colored square, `bg-accent`), name, and plan. Opens a workspace-picker menu on click (menu itself lands with the navigation component set — see [03-component-library.md](./03-component-library.md); the button is wired and ready).

## Sidebar navigation

Two sections: **Workspaces** (the seven product areas — Home, Listings, Partners/CRM, Events, Campaigns, Analytics, Media) and **Pinned** (user-starred items, cross-workspace). The active item gets `bg-active` + `text-primary` + medium weight; its icon goes `text-accent` — the only place accent color touches the sidebar, so it reads unambiguously as "you are here." Counts are right-aligned, tabular, `text-tertiary`.

Below the search trigger — not a text input, a **button** that opens the command palette (`⌘K`). The sidebar never hosts its own separate search implementation; there's exactly one search surface in the product.

## Command palette (`⌘K`)

The universal entry point — navigate, act, or ask AI, all from one surface. This is a deliberate, product-level choice per [00-philosophy.md](./00-philosophy.md): Jaunt does not have a separate "search page." Behavior:

- **Groups**: "Go to" (workspaces) and "Actions" (New listing, New partner, Import calendar, Invite teammate — extend per workspace).
- **AI row**: appears the moment the user types anything, above all other results, tinted iris with the Sparkles glyph — `Ask Jaunt: "<query>"`. This is the pattern that makes AI feel reachable from literally anywhere without a dedicated chatbot surface.
- **Keyboard**: `↑`/`↓` to move, `Enter` to select, `Esc` to close. Mouse hover updates the active row too — the two input methods share one `active` index, never diverge.
- **Empty state**: "No results for '{query}'" — see [08-voice-and-microcopy.md](./08-voice-and-microcopy.md) for why this phrasing (not "0 results found").
- Backdrop: `blur(3px)` over `--surface-overlay`; panel: `--shadow-xl`, `--radius-xl`.

Opening it is global — `$store.commandPalette.open = true` (Alpine store, see `src/js/main.js`) — so the sidebar search button, the topbar search icon, and the `⌘K` shortcut all drive the exact same instance. There is never more than one command palette in the DOM.

## Topbar

Breadcrumbs on the left (last crumb is the current page, non-interactive). Four actions on the right, always in this order: **Ask Jaunt** (`⌘J`, opens the AI rail — see [04-ai-design-language.md](./04-ai-design-language.md)), **Search** (`⌘K`, same palette as the sidebar), **Notifications** (unread dot in `danger`, top-right of the bell), **Theme toggle** (sun/moon, swaps instantly, persists to `localStorage`). Background is a translucent, blurred surface (`color-mix(in oklab, var(--surface-app) 82%, transparent)` + `backdrop-blur`) — one of exactly two places in the product that use blur (the other is the command palette scrim); see [00-philosophy.md](./00-philosophy.md) Visual Philosophy.

## Responsive behavior

- **`lg` (1024px) and up**: full shell, sidebar expanded (248px).
- **`md`–`lg`**: sidebar collapses to mini (56px, icons only — labels and section headers hidden, tooltips take over as the label source).
- **Below `md`**: out of scope for v1 — Jaunt is desktop-first (see [00-philosophy.md](./00-philosophy.md)); a staffer on a phone is checking a notification, not building a campaign. The shell degrades gracefully (content stacks, sidebar becomes an overlay drawer) but isn't optimized further than "doesn't break."

## What the shell deliberately does not own

The `view` region (everything right of the sidebar, below the topbar) is empty in `preview/shell.html` on purpose — the shell provides the gutter (`--pad-view`, 24px) and width ceiling (`--width-wide`, 1440px) and nothing else. Workspace screens (a CRM contact table, a Listings kanban board, an Analytics dashboard) are Livewire components that render into that region; see [05-workspace-pattern.md](./05-workspace-pattern.md).

---

*Next: [03-component-library.md](./03-component-library.md) — the primitives the shell and every workspace are built from.*
