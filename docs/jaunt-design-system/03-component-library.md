# Component Library

Live: `npm run dev`, open [`/preview/index.html`](../preview/index.html) — a landing page linking every gallery below. Each Blade component's own header comment is the authoritative spec (usage example, prop contract, states, a11y notes) — this doc is the index, not a duplicate. Every component was built from its Claude Design source (`.jsx`/`.d.ts`/`.prompt.md` in `components-source/`), then hand-verified in-browser in both themes — see the build/verify history for what was checked.

30 primitives across five groups, plus the shell pieces documented separately in [02-app-shell.md](./02-app-shell.md).

## Forms — [gallery](../preview/components-forms.html)

| Component | States / variants | Keyboard |
|---|---|---|
| `jaunt.forms.button` | primary·secondary·ghost·danger·ai, sm/md/lg, loading, disabled, block | `Enter`/`Space` activates |
| `jaunt.forms.icon-button` | sm/md/lg, solid, active (pressed), disabled | same |
| `jaunt.forms.input` | default·error·disabled, sm/md/lg, prefix/suffix, required/optional | native text field |
| `jaunt.forms.textarea` | default·error·disabled, character counter | native |
| `jaunt.forms.select` | default·error·disabled — styled **native** `<select>`, not a custom listbox | native (full OS keyboard support for free) |
| `jaunt.forms.checkbox` | unchecked·checked·indeterminate·disabled, label+description | `Space` toggles |
| `jaunt.forms.radio` | unchecked·checked·disabled | native radio-group arrow keys |
| `jaunt.forms.switch` | sm/md, off/on/disabled | `Space` toggles, `role="switch"` |
| `jaunt.forms.tag` | 8 color variants, dot, removable | `Enter`/`Space` on remove button |
| `jaunt.forms.file-upload` | idle·hover·drag-over·focus | dropzone is a native file input under the hood |

**AI opportunities:** Input/Textarea suffix slots are the standard place to drop `jaunt.ai.ai-inline` (trigger mode) for "Autofill with AI" — see [04-ai-design-language.md](./04-ai-design-language.md).

## Navigation — [gallery](../preview/components-navigation.html)

| Component | States / variants | Keyboard |
|---|---|---|
| `jaunt.navigation.breadcrumbs` | 2–3+ levels, icons, current-page non-interactive | — |
| `jaunt.navigation.menu` | one component for **both** dropdown and context menu (`trigger-event="click"` \| `"contextmenu"`); item/separator/label rows; danger/ai/checked item styling | `↑`/`↓` roving focus, `Enter`/`Space` selects, `Esc` closes, click-outside closes |
| `jaunt.navigation.tabs` | `underline` (animated indicator) and `pills` variants, counts | full ARIA tabs pattern — `←`/`→` move+select (wraps), `Home`/`End` jump, roving `tabindex` |
| `jaunt.navigation.command-palette` | universal ⌘K entry point — see [02-app-shell.md](./02-app-shell.md) | `↑`/`↓`/`Enter`/`Esc` |

## Data display — [gallery](../preview/components-data.html)

| Component | States / variants | Notes |
|---|---|---|
| `jaunt.data.avatar` | xs–xl, image or deterministic colored initials, status dot (online/away/offline) | color is a stable hash of the name — same person always gets the same color |
| `jaunt.data.card` | compound slots (`media`/`header`+`action`/`body`/`footer`) via named Blade slots, since Blade has no dot-notation subcomponents like the source's `Card.Header`; hoverable·clickable·selected | — |
| `jaunt.data.empty-state` | default·ai variant | voice matters here — see [08-voice-and-microcopy.md](./08-voice-and-microcopy.md), "invitations, not apologies" |
| `jaunt.data.table` | sortable columns (client-side, dispatches a `sort` event for a Livewire wrapper too), row selection + bulk-actions bar, declarative cell `type` (text/badge/avatar/currency/number) in place of the source's render-function columns | selection and sort verified live-interactive |
| `jaunt.data.kanban` | drag-and-drop reordering (native HTML5 DnD, in-memory) within/between columns; tags + avatar clusters on cards | **scope cut, deliberate:** no server persistence, no keyboard-driven column move — see the component's header comment |
| `jaunt.data.timeline` | mixed tone per item (default/accent/ai) | `title`/`detail` render trusted HTML only — never pass raw user input |

## Feedback — [gallery](../preview/components-feedback.html)

| Component | States / variants | Notes |
|---|---|---|
| `jaunt.feedback.alert` | neutral·info·success·warning·danger·ai, dismissible | `role="alert"` for danger, `role="status"` otherwise |
| `jaunt.feedback.badge` | neutral·accent·success·warning·danger·info·ai·count, dot | — |
| `jaunt.feedback.dialog` | default·danger, sm/md/lg | Esc closes, scrim-click closes; each instance owns local state (unlike the singleton command palette, a page can have several distinct dialogs) |
| `jaunt.feedback.progress` / `jaunt.feedback.spinner` | determinate·indeterminate·ai variant | — |
| `jaunt.feedback.skeleton` | rect·text·circle | shimmer respects `prefers-reduced-motion` |
| `jaunt.feedback.toast` + `jaunt.feedback.toast-viewport` | neutral·success·danger·info·ai | **global queue**: `Alpine.store('toasts')` (added to `src/js/main.js`) lets any part of the app — a Livewire success hook, plain Alpine, anywhere — push a toast without prop drilling: `$store.toasts.push({variant:'success', title:'Listing published.'})`. Danger toasts persist until dismissed; others auto-dismiss at 5s, paused on hover. Drop `toast-viewport` once, near the command palette, in the shell layout. |
| `jaunt.feedback.tooltip` | top·bottom·right, optional `kbd` hint | shows on hover + focus |

## AI — [gallery](../preview/components-ai.html)

The most important group in the system — see [00-philosophy.md](./00-philosophy.md) principle 4 and [04-ai-design-language.md](./04-ai-design-language.md) for the full pattern language these implement.

| Component | States / variants | Notes |
|---|---|---|
| `jaunt.ai.confidence-badge` | low (amber) · medium (iris) · high (green), with/without label | levels map to semantic status color, **except** medium, which stays iris — honesty over bravado, not a generic "ok" green |
| `jaunt.ai.ai-inline` | `ghost` (inline suggestion + Tab-to-accept chip) · `trigger` ("Autofill with AI" button) | the smallest AI touchpoint — lives inside a field, e.g. an Input's suffix slot |
| `jaunt.ai.ai-streaming` | thinking (bouncing dots) → streaming (typewriter + caret) → settled | `role="status" aria-live="polite"` so assistive tech hears the *settled* result once, not every token. **The typewriter is a client-side mock** (`setInterval` revealing a fully-known string) — in production, bind `streaming` + a `text` prop that grows from a real Livewire `wire:stream`/SSE source and drop `typewriter` entirely; documented inline in the component. |
| `jaunt.ai.ai-suggestion` | composes a `confidence` slot + Accept/Edit/Dismiss actions | nothing AI-authored is ever committed without an explicit Accept — this component *is* that guardrail, not just a visual |

**Motion:** AI-originated content entrance uses `ease-spring` (never standard easing) — the one deliberate motion signature reserved exclusively for "this came from the assistant." See [01-tokens.md](./01-tokens.md) Motion.

---

## What's verified vs. what's a judgment call

Every component was reviewed against its own `.jsx`/`.d.ts`/`.prompt.md` source, translated to Blade + Tailwind semantic tokens + Alpine, and browser-tested (both themes, real interaction — not just a screenshot) via a hand-built static preview page. Two categories of intentional deviation from the source are called out, consistently, in the affected components' own header comments — look for them before assuming a gap is a bug:

1. **React closures have no Blade equivalent.** Anywhere the source passed a function prop (`Table`'s `column.render`, `Menu`'s `onSelect`, `AISuggestion`'s `onAccept`), the Blade version takes a declarative alternative (an enum, a dispatched event name, a raw Alpine/`wire:click` expression string) — never a PHP closure passed as a prop.
2. **Deliberate scope cuts**, flagged inline where a full production behavior (Kanban server-persisted drag state, Table per-row varying actions) would require a Tier-2 Livewire wrapper per [09-engineering-alignment.md](./09-engineering-alignment.md)'s tier boundary, not belong in a Tier-1 primitive.

---

*Next: [04-ai-design-language.md](./04-ai-design-language.md).*
