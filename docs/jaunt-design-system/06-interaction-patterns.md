# Interaction Patterns

How the primitives in [03-component-library.md](./03-component-library.md) combine into the behaviors a daily user actually feels — selecting rows, undoing a mistake, moving a card, waiting for something to load. Jaunt is run eight hours a day by power users ([00-philosophy.md](./00-philosophy.md)), so this doc treats interaction as a first-class design surface, not a set of afterthought micro-interactions.

Every pattern below is grounded in a real, working component in this repo. Where a pattern isn't fully built yet, that's stated plainly rather than implied.

---

## 1. Selection

**Single selection** — clicking a row, or a `jaunt.data.card` with the `clickable`/`selected` states ([03-component-library.md](./03-component-library.md), Data display), is the baseline: one target, one state change, no modifier keys required.

**Multi-selection** lives in `jaunt.data.table` ([`components/data/table.blade.php`](../components/data/table.blade.php)). Pass `selectable` to add a checkbox column. Selection state is a plain Alpine array (`selected`), seeded from the `selected` prop and mutated by `toggleRow(id)` / `toggleAll()`. Every change fires a `selection-change` CustomEvent with `{ selected: [...] }` — the hook a Livewire wrapper listens on to sync selection server-side. The header checkbox is tri-state: `:checked="allChecked"` and an `x-effect` that sets `$el.indeterminate = someChecked`, so "some but not all rows selected" is visually distinct from "none" or "all."

**Keyboard** — Table's checkboxes are native `<input type="checkbox">` elements, so they're in the natural tab order and toggle on `Space` for free; no custom roving-tabindex logic was needed for this one control. (Contrast with Menu and Tabs below, which *do* need hand-rolled roving tabindex because they aren't native form controls.)

---

## 2. Hover

The rule, stated in [`docs/_source-readme.md`](./_source-readme.md) under "Hover / focus / press": hover is **a translucent overlay, not a color swap**. `--surface-hover` (~4% ink) sits as its own semantic layer in the token system ([01-tokens.md](./01-tokens.md)) rather than being expressed as "one step darker/lighter on the gray ramp," specifically so hover reads consistently *on any surface underneath it* — a hover state on `bg-card` and a hover state on `bg-sunken` both get the same translucent lift, instead of needing a different hard-coded color per surface.

You can see this in practice in `table.blade.php`: row hover is `hover:bg-hover` (line ~198), and selected rows use the separate semantic token `bg-selected` — two different tokens for two different meanings, not two shades of the same gray. Kanban cards use the analogous pattern with `hover:border-strong` + `hover:shadow-sm` rather than a background swap, because on a Kanban card the affordance is elevation, not fill.

Buttons are the one documented exception: per the source rule, buttons "darken one accent step" rather than using the translucent overlay, since a solid-fill primary button needs a solid hover state to read correctly.

---

## 3. Focus

Jaunt uses `:focus-visible` exclusively — never a focus ring on mouse click. This is a global rule in [`src/css/tokens/base.css`](../src/css/tokens/base.css):

```css
:focus-visible {
  outline: 2px solid var(--border-focus);
  outline-offset: 1px;
  border-radius: var(--radius-xs);
}
```

That's the browser-native outline used as a baseline everywhere. Individual interactive components layer a second, richer focus treatment on top: a **box-shadow ring**, driven by the `ring` / `ring-ai` / `ring-danger` tokens ([01-tokens.md](./01-tokens.md), [`src/css/tokens/colors.css`](../src/css/tokens/colors.css)):

```css
--ring:        0 0 0 3px rgba(0, 122, 255, 0.32);   /* blue — default interactive focus */
--ring-ai:     0 0 0 3px rgba(88, 86, 214, 0.30);  /* indigo — AI-context focus */
--ring-danger: 0 0 0 3px rgba(255, 59, 48, 0.30);   /* red — destructive-context focus */
```

Concrete examples:
- `jaunt.forms.input` ([`components/forms/input.blade.php`](../components/forms/input.blade.php)) applies `focus-within:shadow-ring` on its wrapper by default, and swaps to `focus-within:shadow-ring-danger` when the field has an `error` — so a field already flagged invalid gets a red-tinted focus ring instead of blue, reinforcing "this still needs attention" through the same interaction a user is already performing (tabbing into the field).
- `jaunt.forms.checkbox` ([`components/forms/checkbox.blade.php`](../components/forms/checkbox.blade.php)) applies `has-[:focus-visible]:shadow-ring` to the visible box, driven off the real (visually-hidden) `<input>`'s focus state — the ring appears on the custom-styled box, not the invisible native control.
- `jaunt.navigation.tabs` ([`components/navigation/tabs.blade.php`](../components/navigation/tabs.blade.php)) applies `focus-visible:shadow-ring` per tab button.

**Why AI context gets its own ring color:** indigo is reserved exclusively for AI-originated or AI-adjacent surfaces ([01-tokens.md](./01-tokens.md), Color) — never used for anything the user authored themselves, so "this came from — or is about — the assistant" is legible without a label. A focus ring is one more place that separation has to hold: if a user tabs into an AI suggestion's Accept/Edit/Dismiss actions (`jaunt.ai.ai-suggestion`), a blue ring would visually claim it as an ordinary interactive control; `ring-ai` keeps the AI framing intact even in a state (keyboard focus) that has nothing to do with color coding per se. `ring-danger` exists for the same reason in the opposite direction — a focused destructive control (e.g., a "Delete" button, or an invalid-and-focused input) should look different from a focused ordinary one, because the cost of a mistaken activation is different.

---

## 4. Drag and drop

`jaunt.data.kanban` ([`components/data/kanban.blade.php`](../components/data/kanban.blade.php)) is the one implementation of drag-and-drop in the system, and it's native HTML5 DnD — `draggable="true"`, `@dragstart`, `@dragover.prevent`, `@drop.prevent` — reordering an in-memory Alpine-seeded `columns` array, not a JS drag library. Dropping on a card inserts before/after it (`onDropOnCard`); dropping on empty column space appends to the end (`onDropOnColumn`). Column counts update live because they're a computed `col.cards.length`, not a separately-tracked number.

**The honest gap:** cards carry `tabindex="0"` so keyboard users can tab to them, but there is no keyboard-driven reorder path. The component's own header comment calls this out directly — the source spec (`Kanban.prompt.md`) asks for "a menu to move between columns without dragging," and that menu is explicitly *not implemented* in this Tier-1 primitive. It's left as a judgment call for the app layer, because a real move-menu needs to know the workspace's actual column set and business rules (e.g., can a card skip from "New" straight to "Won"?), which a dumb primitive shouldn't be opinionated about. Until a Tier-2 wrapper adds that menu, **Kanban's reordering is mouse/trackpad-only** — a real WCAG 2.2 AA gap (see [00-philosophy.md](./00-philosophy.md), Accessibility Philosophy: "every interaction is reachable... by keyboard"), not a stylistic omission. Any team shipping Kanban in production needs to close this before it ships to a public-sector procurement audience.

The component is also explicit about what else it doesn't do: no server persistence (the `add-card` CustomEvent is the only network-shaped hook — everything else is purely client-side state), and no optimistic-move network call or undo/toast wiring. That composition — DnD primitive + optimistic network call + undo toast — is exactly the shape described in sections 8–9 below, and is deliberately left to the app layer.

---

## 5. Inline editing

Jaunt's philosophy is that editing happens **where the data lives**, not in a modal pulled out of context ([00-philosophy.md](./00-philosophy.md), Interaction Philosophy: "nothing blocks"). The intended pattern: click a Table cell, or a Card field, and it becomes a `jaunt.forms.input` in place — same row, same position, no navigation away from the list.

**Be clear about what exists today: no component in this repo currently implements inline-edit-table-cell.** `jaunt.data.table` ([`components/data/table.blade.php`](../components/data/table.blade.php)) renders cells as static `<span x-text="...">` output per its declarative `type` system (text/badge/avatar/currency/number) — there's no edit-mode toggle, no click-to-input transition, on any cell type. This is a documented *pattern*, not a shipped feature.

The building block already exists, though: `jaunt.forms.input` ([`components/forms/input.blade.php`](../components/forms/input.blade.php)) is the primitive a Tier-2 Livewire wrapper would drop into a cell on click — sized to `sm` (26px) to fit inside a 36px row (`--row-h`, [01-tokens.md](./01-tokens.md)), with its existing `focus-within:shadow-ring` behavior giving the "you're now editing this" affordance for free. Implementing inline editing is a Tier-2 concern per [`docs/_source-readme.md`](./_source-readme.md)'s engineering-alignment section, because it needs server-round-trip awareness (save-on-blur or save-on-Enter, validation, revert-on-error) that a Tier-1 Blade primitive can't own — the same reasoning that keeps optimistic updates (section 9) out of Table itself.

---

## 6. Bulk actions

`jaunt.data.table`'s bulk-actions bar is the canonical pattern. Pass a `bulkActions` slot; it renders inside a bar pinned above the table body, gated by `x-show="selected.length > 0"`:

```blade
<div x-show="selected.length > 0" x-cloak class="flex items-center gap-2 px-3.5 h-row border-b border-subtle bg-selected">
    <span class="text-xs text-secondary" x-text="selected.length + ' selected'"></span>
    <div class="flex items-center gap-1.5 ml-auto">
        {{ $bulkActions }}
    </div>
</div>
```

The bar shares `bg-selected` with the selected rows themselves, tying the two states together visually. The selected-count label (`"3 selected"`) is computed straight off the same `selected` array that drives the checkboxes — one source of truth, not a duplicated counter. Because the bar's visibility is a pure Alpine `x-show`, it appears/disappears instantly as selection changes — no flash, no layout jump, consistent with the "nothing blocks" interaction philosophy.

---

## 7. Keyboard shortcuts

Every primary action has a shortcut; the command palette is the universal entry point, not a hidden power-user feature ([00-philosophy.md](./00-philosophy.md)). The real, wired-up shortcut list, pulled from [`src/js/main.js`](../src/js/main.js) and the relevant components — nothing invented beyond what's actually bound:

| Shortcut | Effect | Where it's wired |
|---|---|---|
| `⌘K` / `Ctrl+K` | Opens the command palette | global `keydown` listener in `main.js`, sets `Alpine.store('commandPalette').open = true` |
| `⌘J` / `Ctrl+J` | Opens the AI rail | same listener, `Alpine.store('aiRail').open = true` |
| `Escape` | Closes the focused overlay | `jaunt.navigation.command-palette` (`onKeydown`), `jaunt.feedback.dialog` (`@keydown.escape.window`), `jaunt.navigation.menu` (`onKeydown`) — each overlay owns its own Escape handler locally, there's no single global Escape router |
| `↑` / `↓` | Move active row | Command palette (`onKeydown`, clamps at the list ends) and Menu (`onKeydown`, roving `active` index over `selectable` items, also clamps) |
| `Enter` | Select active row/item | Command palette, Menu |
| `←` / `→` | Move focus + selection between tabs, **wraps** at the ends | `jaunt.navigation.tabs` (`onKeydown`) |
| `Home` / `End` | Jump to first/last tab | `jaunt.navigation.tabs` |
| `Space` | Toggle a focused checkbox; activate a focused Menu item | native behavior (Table checkbox), `jaunt.navigation.menu` `onKeydown` |

**Roving tabindex** (Menu, Tabs) — both `jaunt.navigation.menu` and `jaunt.navigation.tabs` implement the ARIA "roving tabindex" pattern rather than letting every item sit in the natural tab order: Tabs sets `:tabindex="active === id ? 0 : -1"` so only the selected tab is reachable by `Tab`, and arrow keys move both focus and the ref target (`$refs['tab-' + id].focus()`) within the group. Menu's roving state is the `active` index rather than a DOM `tabindex` swap, since menu items are `<button>`s inside a `x-show`n popover rather than a persistent tab strip — but the effect (arrows move a highlighted "active" row, `Enter`/`Space` activates it) is the same pattern.

Click-outside-closes is a companion rule for every overlay (Menu's `@click.outside="closeMenu()"`, Dialog's scrim-click check, Command palette's overlay mousedown check) — Escape and click-outside are treated as equally valid "never mind" gestures.

---

## 8. Undo

The toast system is the vehicle. `Alpine.store('toasts')` ([`src/js/main.js`](../src/js/main.js)) is a global queue any part of the app can push into without prop drilling:

```js
$store.toasts.push({ variant: 'success', title: 'Listing published.', actionLabel: 'Undo' })
```

`jaunt.feedback.toast-viewport` ([`components/feedback/toast-viewport.blade.php`](../components/feedback/toast-viewport.blade.php)) renders the queue and owns the auto-dismiss timers: 5 seconds by default, paused while any toast is hovered (`@mouseenter="pauseAll()"`), and — critically for undo — **danger-variant toasts persist until manually dismissed** (`duration: 0` when `variant === 'danger'`, set in the store's `push()`). A toast's `actionLabel` renders as a text button (`toast.onAction && toast.onAction()`); wiring that action to an "undo the thing I just did" handler is what makes undo work.

Per [00-philosophy.md](./00-philosophy.md) ("undo over confirm, wherever the action is cheaply reversible"), the default should be: **skip the confirmation dialog, do the thing, and offer Undo in the toast that follows.** Deleting a tag, archiving a listing, moving a Kanban card — anything where reversing the action costs nothing more than reissuing the inverse write — should be optimistic-then-toast, not stop-and-confirm.

`jaunt.feedback.dialog` ([`components/feedback/dialog.blade.php`](../components/feedback/dialog.blade.php)) is reserved for the other case: genuinely destructive or hard-to-reverse actions, using its `danger` variant (red icon well via `bg-danger-subtle text-danger`, per [`_source-readme.md`](./_source-readme.md)'s micro-copy example: `Delete 3 listings? This can't be undone.` / `Delete` · `Cancel`). The dividing line is reversibility, not severity of the action's *name* — "delete" doesn't automatically mean Dialog if there's a trash/restore path behind it; it means Dialog when there genuinely is no way back.

---

## 9. Optimistic updates

The philosophy: the UI assumes success and rolls back visibly on failure, rather than waiting on a spinner before reflecting a change ([00-philosophy.md](./00-philosophy.md), "Speed over cleverness" — "every interaction targets sub-100ms perceived response"). This is explicitly **a Tier-2/Livewire responsibility, not something a Tier-1 Blade primitive owns alone** — optimistic UI requires knowing when a server round-trip started, succeeded, or failed, and a dumb presentational primitive has no concept of a network request.

What Tier-1 already gives a Tier-2 wrapper to compose with:
- **Table and Kanban's client-side state changes** are, by construction, already optimistic in the narrow sense that they mutate local Alpine state immediately with no round-trip in between — Table's `toggleRow`/`sortBy` and Kanban's `onDropOnCard`/`onDropOnColumn` all update the in-memory array synchronously, then (in Table's case) dispatch an event (`selection-change`, `sort`) a Livewire wrapper can listen to and reconcile against. The reconciliation and rollback-on-failure logic is what the wrapper adds; the instant local state change is what Table/Kanban already provide for free.
- **The toast queue** (section 8) is the rollback/confirmation surface: a Tier-2 wrapper fires the optimistic client-side change, sends the request, and on failure pushes a `danger` toast (which persists until dismissed, unlike routine success toasts) while reverting the local state — giving the user a visible, actionable "that didn't actually happen" instead of a silent inconsistency.

So the pattern to document for engineers building Tier-2 components: mutate local state immediately → fire the request → on success, do nothing visible (the UI already looks right) or push a quiet success toast; on failure, revert the local mutation and push a persistent danger toast explaining what happened. Kanban's own header comment names this exact composition as deliberately out of scope for the primitive itself ("no optimistic-move network call, no undo/toast wiring... deferred to the app layer").

---

## 10. Loading

Three distinct primitives for three distinct situations — using the wrong one for a given wait is a real error users notice on a tool they sit in for eight hours:

- **`jaunt.feedback.skeleton`** ([`components/feedback/skeleton.blade.php`](../components/feedback/skeleton.blade.php)) — **initial load** of structured content (a table about to render rows, a card about to render its fields). `variant` is `rect` / `text` / `circle`; `lines` controls how many text lines a text-skeleton renders (the last line is shortened to 60% width so it doesn't look like a real paragraph). Preferred over Spinner for this case specifically because it "reduces perceived latency and avoids layout shift" (the component's own header comment) — the skeleton occupies the real space the content will occupy, so nothing jumps when data arrives. The shimmer sweep collapses to nothing under `prefers-reduced-motion`.
- **`jaunt.feedback.spinner`** ([`components/feedback/spinner.blade.php`](../components/feedback/spinner.blade.php)) — **in-flight action**, not initial page load: inside a button mid-submit, or any small inline wait where there's no layout to preserve. Takes a `size` (px) and a `variant` (`default` blue / `ai` indigo — use `ai` when the in-flight action is an AI operation, per the indigo-is-exclusively-AI rule).
- **`jaunt.feedback.progress`** ([`components/feedback/progress.blade.php`](../components/feedback/progress.blade.php)) — **determinate long operations** where a percentage is meaningful (a bulk import, a large export). Pass `value` (0–100) for a determinate bar with `aria-valuenow`; omit it (`value` stays `null`) for an indeterminate sliding-bar animation when the operation is long but its progress can't be measured. `showValue` renders the numeric `%` label. `variant` again includes `ai` for AI-driven long operations (e.g., "Summarizing 240 reviews…", the exact copy example from [`_source-readme.md`](./_source-readme.md)).

Rule of thumb: known shape, no percentage → Skeleton. Small, shapeless, short → Spinner. Long, and you can compute a fraction-complete → Progress (determinate); long and you can't → Progress (indeterminate). None of the three should ever sit behind a blocking modal — per [00-philosophy.md](./00-philosophy.md), "nothing blocks; long-running work streams in rather than spinning behind a modal."

---

## 11. Error recovery

Errors are blameless and actionable by voice rule ([`_source-readme.md`](./_source-readme.md), Content fundamentals: "say what happened, then the way forward" — e.g. `Couldn't publish — 2 listings are missing a category. Review them →`). The visual vehicles, all using the shared `danger` variant:

- **`jaunt.feedback.alert`** (danger variant) — persistent, page-anchored error tied to a specific place on the page (a validation summary above a form). Uses `role="alert"` specifically for the danger variant (every other variant uses `role="status"`), so assistive tech interrupts to announce it rather than waiting for a polite-queue turn.
- **`jaunt.feedback.toast`** (danger variant, via `Alpine.store('toasts').push({variant: 'danger', ...})`) — transient-but-not-really: danger toasts get `duration: 0` and persist until the user dismisses them (see section 8), and render with `aria-live="assertive"` instead of the `polite` used by other variants. Appropriate for an action that failed but doesn't need the user to stop and read a modal — "Couldn't publish" as a toast with a way forward, not a wall.
- **`jaunt.feedback.dialog`** (danger variant) — reserved for failures serious enough to require the user stop and acknowledge before continuing, using the same red icon-well treatment as its destructive-confirmation use (section 8).

Which of the three to reach for is the same judgment call as elsewhere in this doc: Alert for "this form has a problem, look here"; Toast for "that action failed, here's why, carry on"; Dialog only when the user genuinely cannot proceed until they've acknowledged the failure.

The full voice/copy rules for how error strings should actually read — tone, structure, banned phrases — belong in **docs/08-voice-and-microcopy.md**. That document doesn't exist in this repo yet as of this writing; this section should be revisited to link directly to specific rules once it's written. In the meantime, [`_source-readme.md`](./_source-readme.md)'s "Content fundamentals" section is the closest thing to a source of truth for error copy voice.

---

*Previous: [03-component-library.md](./03-component-library.md) — the primitives this document assumes. Next: [08-voice-and-microcopy.md](./08-voice-and-microcopy.md) (not yet written) for copy rules, or [09-engineering-alignment.md](./09-engineering-alignment.md) for the Tier-1/Tier-2 boundary referenced throughout sections 5 and 9.*
