# Engineering Alignment

Stack: **Laravel + Livewire + Blade components + Tailwind CSS**, with Alpine.js for client-only interaction that doesn't need a server round-trip. This doc is the contract between design and engineering — the goal is that a designer's Figma file and an engineer's Blade component are two views of the same token/component names, never a translation exercise.

## Component architecture

Two tiers. Confusing which tier something belongs in is the single most common way design systems rot.

```
components/{forms,feedback,navigation,data,ai}/   Tier 1 — primitives (Blade components, no server state)
app/Livewire/                                      Tier 2 — stateful, server-backed (Livewire components)
```

Grouping mirrors the source system exactly (`components-source/components/{forms,feedback,navigation,data,ai}/`) rather than an invented "ui vs patterns" split — a designer opening the Claude Design project and an engineer opening this repo see the same five folders.

**Tier 1 — primitives** (`<x-jaunt.forms.button>`, `<x-jaunt.data.table>`, `<x-jaunt.ai.suggestion>`): plain Blade components. Accept `@props`, render markup, apply variant/size logic in `@php`. No Livewire, no `wire:model` inside the component itself — they're dumb, reusable, and framework-agnostic enough that a static preview page can render them with zero backend. See [`components/forms/button.blade.php`](../components/forms/button.blade.php) as the reference implementation, translated 1:1 from [`components-source/components/forms/Button.jsx`](../components-source/components/forms/Button.jsx) — every future primitive follows its shape: a `@props` array matching the source `.d.ts` interface, a variant/size map in `@php`, `$attributes->merge()` so callers can extend classes and pass through `wire:model`, `x-on`, etc. Some primitives (dropdowns, command palette, dialogs) need open/closed and keyboard-navigation state — that's Alpine, written inline in the same Blade file, never a reason to promote the component to Tier 2.

**Tier 2 — Livewire components** (`app/Livewire/Crm/ContactTable.php`): own server state, database queries, validation, authorization. They compose Tier 1 primitives in their Blade views and are the only place `wire:model`, `wire:click`, and Livewire lifecycle hooks live at the top level.

This separation means a primitive never has to be rewritten when the same button needs to appear in three different Livewire components with three different `wire:click` handlers — the button doesn't know or care that Livewire exists.

## Naming conventions

**Blade components:** `jaunt.<group>.<name>` — `jaunt.forms.button`, `jaunt.forms.input`, `jaunt.data.table`, `jaunt.ai.suggestion` — per the source system's own engineering-alignment section. Multi-word names are kebab-case (`jaunt.forms.file-upload`, not `jaunt.forms.fileUpload`).

**Props:** camelCase in PHP (`@props(['isLoading' => false])`), kebab-case when passed from Blade (`<x-jaunt.forms.button :is-loading="$saving" />`) — Laravel's attribute binding handles this conversion automatically; don't fight it by inventing a different casing scheme. Props mirror each component's source `.d.ts` interface — that file is the prop contract, translate it, don't redesign it.

**Variant/size props are always named `variant` and `size`**, with a fixed enum of values documented in the component's doc block — never `type`, `kind`, `mode`, or a boolean per variant (`isPrimary`, `isDanger`). One consistent vocabulary across every component means an engineer who's used `<x-jaunt.forms.button variant="danger">` already knows `<x-jaunt.feedback.badge variant="danger">` exists before they've read its source.

**Tailwind classes in component markup use semantic tokens, never primitives** (`bg-card`, not `bg-white [data-theme=dark]:bg-gray-900`) — see the two-layer token architecture in [01-tokens.md](./01-tokens.md). If you find yourself writing a `dark:` variant inside a Tier 1 component, stop: it almost always means a semantic token is missing, and the fix is to add the token (in `tokens/*.css` + `tailwind.config.js`), not to hand-roll the dark-mode branch locally.

**Bespoke CSS**, when a Tailwind utility genuinely can't express something (e.g. a `background: color-mix(...)` blur backdrop): prefix with `.j-<block>`, matching the source bundle's own convention, and keep it minimal — prefer utilities first.

**Livewire components:** PascalCase, namespaced by workspace — `App\Livewire\Crm\ContactTable`, `App\Livewire\Events\Calendar`. This mirrors the workspace pattern in [05-workspace-pattern.md](./05-workspace-pattern.md): the namespace tells you which product surface owns the component before you've opened the file.

## Token organization

```
src/css/tokens/*.css      Source of truth — copied verbatim from the Claude Design handoff
src/css/main.css           Import manifest (mirrors the source's styles.css exactly)
tailwind.config.js         Every value is a direct var(--token-name) reference — no new values defined here
```

There is no JSON intermediate layer and no build step: `tokens/*.css` **is** the contract, byte-for-byte the same file a designer would see in Claude Design. `tailwind.config.js` only exposes those variables as Tailwind utilities — if a token value needs to change, it's changed in `tokens/*.css` (or re-exported from Claude Design and re-copied), never in the Tailwind config directly.

For the production Laravel app, `src/css/tokens/*.css` gets imported once in the app's main Tailwind entry (`resources/css/app.css`, mirroring `main.css` here), and `tailwind.config.js`'s color/spacing/etc. extensions get merged into the app's existing Tailwind config — not copy-pasted per-workspace. One config, every workspace inherits it, per the workspace pattern. Theme switching is a single `data-theme` attribute swap on `<html>` (see `src/js/main.js`) — zero rebuild, zero FOUC if set before first paint.

## Implementation strategy

**Rollout order** (mirrors the phase plan for this design system itself):
1. Land tokens + Tailwind config in the main app first — even before components exist, this lets any existing view start using `bg-card`/`text-primary` instead of ad hoc grays, which de-risks the rest of the migration.
2. Port/build Tier 1 primitives one at a time, each with a preview entry (see `preview/components.html` once Phase 3 lands) so design and engineering can sign off on a component in isolation before it's used in a real screen.
3. Build the app shell (Phase 2 of this system — [02-app-shell.md](./02-app-shell.md)) as its own Blade layout (`resources/views/layouts/app.blade.php`), composed from Tier 1/2 components, so every new Livewire workspace gets the shell for free by extending one layout.
4. New workspaces (CRM, CMS, Events, ...) are built as Livewire components that extend the shell layout and compose existing primitives — a new workspace should rarely, if ever, need a new Tier 1 primitive.

**Alpine.js scope:** Alpine owns client-only, ephemeral UI state — dropdown open/closed, tab selection, form field focus, optimistic toggle before a Livewire round-trip confirms. Alpine does **not** own anything that needs to survive a page reload or be shared across components — that's Livewire's job. When a component needs both (e.g., a dropdown that's Alpine-driven open/closed but whose selection persists via `wire:model`), Alpine and Livewire coexist in the same Blade file without conflict — Livewire 3's Alpine integration is a first-class dependency, not a bolt-on.

**Testing/review gate:** a Tier 1/2 component change should be visually verified against its preview page (light + dark, all variants/states) before merge — the same discipline as a unit test, just visual. See the `/verify` skill workflow for how this plugs into the existing review process.

**Where this repo's artifacts map to the production app:**

| This repo | Production Laravel app |
|---|---|
| `src/css/tokens/*.css`, `tailwind.config.js` | `resources/css/tokens/*.css` (imported into `app.css`), merged into the app's `tailwind.config.js` |
| `components/{forms,feedback,navigation,data,ai,shell}/*.blade.php` | `resources/views/components/jaunt/{forms,feedback,navigation,data,ai,shell}/*.blade.php` |
| `components/icon.blade.php` (ungrouped — see below) | `resources/views/components/jaunt/icon.blade.php` |
| `components-source/`, `ui_kits-source/` | Reference only — the untouched Claude Design export. Never edited; never shipped. Re-copy from a fresh export if the design changes. |
| `preview/*.html` | Not shipped — reference only, or rebuilt as an internal `/design-system` route gated to engineering |

The local `components/` root corresponds to production's `resources/views/components/jaunt/` — i.e. every tag in this repo is written as it will resolve in production (`<x-jaunt.forms.button>`, `<x-jaunt.icon>`), not as it resolves locally in isolation. `icon` and `shell/*` sit outside the five source-defined groups (forms/feedback/navigation/data/ai): `icon` is a cross-cutting primitive every group depends on, and `shell/*` (sidebar, topbar) composes primitives into the app shell rather than being one itself — see [02-app-shell.md](./02-app-shell.md).

---

*This closes Phase 1 (Foundation). Phase 2 builds the application shell on top of these tokens and conventions — see [02-app-shell.md](./02-app-shell.md).*
