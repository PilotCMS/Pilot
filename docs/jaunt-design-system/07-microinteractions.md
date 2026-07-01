# Microinteractions

> Document subtle interactions that create delight without distraction. Per [00-philosophy.md](./00-philosophy.md), Motion Philosophy: *"Motion narrates state: where a thing came from, that a save landed, that AI is thinking. Durations run 80–260ms, spring-tinted on toggles and transforms, standard easing elsewhere. Every motion is interruptible and degrades to opacity/instant under `prefers-reduced-motion`. If a motion doesn't clarify a state change, it's cut — motion is not decoration here."*

This doc doesn't introduce new motion — it catalogs what's actually implemented, component by component, and names the exact duration/easing token behind each one. See [01-tokens.md](./01-tokens.md) for the token table this all draws from:

| Token | Value | Use |
|---|---|---|
| `dur-instant` | 80ms | Color/background transitions |
| `dur-fast` | 130ms | Hover, transform (paired with `ease-spring`) |
| `dur-base` (Tailwind: `duration-DEFAULT`) | 180ms | Default |
| `dur-slow` | 260ms | Panels, reveals |
| `dur-slower` | 400ms | Large surfaces (AI rail entrance) |

`ease-standard` is default, `ease-out` is for reveals, `ease-spring` is reserved for toggles/transforms — and, deliberately, for AI-originated content (see §4).

**The global reduced-motion mechanism:** [`tokens/motion.css`](../src/css/tokens/motion.css) wraps all five `--dur-*` custom properties in a `@media (prefers-reduced-motion: reduce)` block that collapses every one of them to `0ms`, and additionally re-points `--ease-spring` to `var(--ease-standard)`. Because every Tailwind `duration-*`/`ease-*` utility used across the component library resolves to one of these custom properties, **any component using the standard token-driven `x-transition` classes gets reduced-motion compliance for free, automatically, with zero component-level code.** This covers every pattern in §1–3, §5, §6 below. The two exceptions — components with hand-rolled `@keyframes` in a scoped `<style>` block — are called out explicitly in §4 and §8, since a CSS animation duration isn't a custom property and needs its own guard.

---

## 1. Hover behavior

Two conventions carry almost all hover/press feedback in the system: a translucent background overlay for hover, and a scale-down for the press ("active") moment.

**Translucent overlay (`bg-hover`).** Nearly every interactive row/button/menu-item uses the semantic `bg-hover` token as its hover background rather than a literal color, so it's correct in both themes automatically. Example — `components/navigation/menu.blade.php` menu items:

```
transition-colors duration-instant ease-standard
data-[active=true]:bg-hover
```

That's `dur-instant` (80ms) with `ease-standard` — the fast, no-personality curve reserved for color/background changes per the token table. Table rows (`components/data/table.blade.php`) use the same pattern at the row level: `transition-colors duration-instant ease-standard hover:bg-hover`.

**Icon-button press-scale.** `components/forms/icon-button.blade.php` scales down on press:

```
transition-colors duration-instant ease-standard
enabled:hover:bg-hover enabled:hover:text-primary
enabled:active:bg-active enabled:active:scale-[0.94]
```

`active:scale-[0.94]` — a 6% shrink on `:active`, with no explicit duration/easing class, so it inherits whatever transition properties are declared (`duration-instant ease-standard`, since `transition-colors` doesn't actually list `transform`... in practice the scale still animates because Tailwind's `transition-colors` utility is scoped to color properties, so the scale change is comparatively abrupt/snappy — appropriate for the sub-100ms "press" feel the philosophy calls for).

**Button press-scale.** `components/forms/button.blade.php` uses a slightly gentler press:

```
transition-colors duration-instant ease-standard active:scale-[0.98]
disabled:active:scale-100
```

`active:scale-[0.98]` — a 2% shrink, softer than IconButton's 6% because Button carries a label and a larger hit area; a heavier scale would read as jumpy on text. Note `disabled:active:scale-100` explicitly cancels the press-scale when the button is disabled, so a disabled control never fake-responds to a click.

---

## 2. Row selection

`components/data/table.blade.php` drives selection state through Alpine (`selected` array) and applies the semantic `bg-selected` token to the row:

```
<tr class="group transition-colors duration-instant ease-standard hover:bg-hover last:[&>td]:border-b-0"
    :class="selected.includes(row.id) ? 'bg-selected' : ''">
```

The row's own transition is the general-purpose `transition-colors duration-instant ease-standard` — the same 80ms color fade used for hover — so checking a row's checkbox crossfades the background from transparent/hover to `bg-selected` at the same speed as any other color state change. There's no separate, more emphatic transition for selection; the system treats "selected" as just another color state, not a bigger moment. The bulk-action bar that appears above the table when `selected.length > 0` is gated by a plain `x-show` with `x-cloak` and **no `x-transition` at all** — it snaps in/out rather than animating, which is a legitimate reading of "if a motion doesn't clarify a state change, it's cut": the bar's appearance is already clearly tied to the checkbox click that caused it.

---

## 3. Context menus

`components/navigation/menu.blade.php` is a single component used for both dropdown and right-click context-menu modes (`trigger-event="click"` vs `"contextmenu"`). Its open/close transition:

```
x-show="open"
x-transition:enter="transition ease-out duration-base"
x-transition:enter-start="opacity-0 -translate-y-1 scale-[0.98]"
x-transition:enter-end="opacity-100 translate-y-0 scale-100"
```

Opacity fade + a 2% scale-up + a 4px upward translate, over `dur-base` (180ms) with `ease-out`. This is the same "enter pattern" family as the command palette's panel (§6) — opacity/scale/translate together, `ease-out` — just tuned to a smaller, closer-to-the-trigger surface (a `-translate-y-1` nudge vs. the palette's `-translate-y-2`, and `duration-base` vs. the palette panel's identical `duration-base`/`duration-DEFAULT`). There's no explicit `x-transition:leave` on Menu — closing relies on the `x-show` default (instant removal once `open` flips false), unlike the palette and dialog which both define matching leave transitions. That's a minor asymmetry worth knowing about if you're auditing for consistency, but not something this doc-only task should fix.

---

## 4. AI response animations

This is the system's richest, most intentional motion territory, because [00-philosophy.md](./00-philosophy.md) principle 4 makes AI "a teammate, not a feature" — and motion is one of the few signals that can say *this came from the assistant* without a label. `components/ai/ai-streaming.blade.php` defines three hand-rolled keyframe animations, plus `components/ai/ai-suggestion.blade.php` is the one place in the whole system where `ease-spring` is used on an entrance.

**The sparkle pulse.** While AI is `thinking` or `streaming`, the sparkle icon's wrapper gets `j-ai-stream-busy`:

```css
@keyframes j-ai-pulse { 0%, 100% { box-shadow: 0 0 0 0 var(--ai-glow); } 50% { box-shadow: 0 0 0 5px transparent; } }
.j-ai-stream-busy { animation: j-ai-pulse 1.4s var(--ease-standard) infinite; }
```

A slow (1.4s) glow ring that expands and fades, looping — a quiet "still working" heartbeat, not an attention-grabber.

**The thinking-dots bounce.** Shown only in the `thinking` state (before any tokens have arrived):

```css
@keyframes j-ai-bounce { 0%, 60%, 100% { opacity: .3; transform: translateY(0); } 30% { opacity: 1; transform: translateY(-3px); } }
.j-ai-stream-dot { animation: j-ai-bounce 1.1s var(--ease-standard) infinite; }
```

Three dots (`j-ai-stream-dot`), each with a staggered `animation-delay` (0ms / 150ms / 300ms) in the markup, producing the familiar cascading "typing indicator" wave — each dot rises 3px and brightens at its peak.

**The streaming caret blink.** Once tokens are arriving (or in the typewriter demo mode), a blinking caret follows the text:

```css
@keyframes j-ai-blink { 50% { opacity: 0; } }
.j-ai-stream-caret { animation: j-ai-blink 1s steps(2) infinite; }
```

A hard two-step blink (`steps(2)`, not eased) — deliberately mechanical, reading as "live cursor," not a soft pulse.

**Reduced-motion guard, verified present.** All three of the above are explicitly guarded inside the component's own `<style>` block:

```css
@media (prefers-reduced-motion: reduce) {
    .j-ai-stream-busy,
    .j-ai-stream-caret,
    .j-ai-stream-dot { animation: none; }
}
```

This is necessary and correct — these are hand-rolled CSS `animation` declarations with a literal duration (`1.4s`, `1.1s`, `1s`), not `var(--dur-*)`-driven Tailwind transitions, so the global `tokens/motion.css` media query (which only zeroes out custom properties) cannot reach them on its own. `ai-streaming.blade.php` already ships its own local `@media (prefers-reduced-motion: reduce)` guard duplicating the same query and turning all three animations off — verified present, no fix needed.

**Why `ease-spring` is reserved for AI.** `components/ai/ai-suggestion.blade.php` — the "AI-suggests, human-approves" card used for drafted copy, autofilled fields, detected duplicates — is the *only* place in the component library that uses `ease-spring` on an entrance transition:

```
x-transition:enter="transition ease-spring duration-slow"
x-transition:enter-start="opacity-0 -translate-y-1 scale-[0.98]"
x-transition:enter-end="opacity-100 translate-y-0 scale-100"
```

Every other panel-style entrance in the system (command palette, dialog, menu, toast) uses `ease-out` — a curve that settles smoothly with no overshoot. `ease-spring` (`cubic-bezier(0.34, 1.56, 0.64, 1)`) overshoots past 100% before settling back, which reads as a small, springy "bounce." Per the motion philosophy, that overshoot is the point: it's a *motion signature*, not a flourish. Because `ease-spring` never appears on anything the user authored themselves — only on AI-originated arrivals — a slight spring on an incoming card becomes a learned, wordless cue: "this came from the assistant," the same way the `iris`/AI color and the sparkles glyph are reserved exclusively for AI per [01-tokens.md](./01-tokens.md) and [00-philosophy.md](./00-philosophy.md). One consistent motion cue, used nowhere else, does the labeling work instead of a badge.

Note: `dur-slow` (260ms) here, not `dur-fast` — spring curves need a bit more time to read as a bounce rather than a snap; a 130ms spring would look like jitter.

Under `prefers-reduced-motion: reduce`, `tokens/motion.css` re-points `--ease-spring` to `var(--ease-standard)` and collapses `--dur-slow` to `0ms` globally — so AISuggestion's entrance degrades automatically to an instant, un-sprung appearance with no component-level code required (this is the `x-transition` / token-driven case, distinct from the hand-rolled keyframes above).

---

## 5. Notifications

`components/feedback/toast-viewport.blade.php` defines the enter/exit and the auto-dismiss/pause-on-hover behavior together.

**Enter/exit.** Per-toast transition:

```
x-transition:enter="transition ease-out duration-base"
x-transition:enter-start="opacity-0 translate-y-2 scale-[0.98]"
x-transition:enter-end="opacity-100 translate-y-0 scale-100"
x-transition:leave="transition ease-in duration-instant"
x-transition:leave-start="opacity-100"
x-transition:leave-end="opacity-0"
```

Entrance is the familiar opacity+scale+translate combo at `dur-base` (180ms)/`ease-out` — a toast rises 2px and scales up from 98% as it appears. Exit is deliberately simpler and faster: opacity-only, `dur-instant` (80ms), `ease-in` — toasts should get out of the way quickly and without ceremony; only the arrival deserves the fuller motion.

**Auto-dismiss timing.** From the component's own header comment and its Alpine `schedule()`/`timers` logic: toasts auto-dismiss **5s by default** (`if (!toast.duration) return;` guards the case where `duration: 0`, used for danger toasts, which "persist until manually dismissed" — matching the source contract that "errors persist until dismissed").

**Pause-on-hover.** The viewport wrapper listens for `@mouseenter="pauseAll()"` / `@mouseleave="resumeAll()"`. `pauseAll()` clears every pending timer (`Object.keys(this.timers).forEach(...)`) the instant the pointer enters *any* toast in the stack, and `resumeAll()` re-schedules all currently-visible toasts on mouse-leave. So hovering one toast pauses the dismissal clock for the whole stack, not just the hovered item — you can't have a second toast quietly expire behind your cursor while you're reading the first.

---

## 6. Search transitions

`components/navigation/command-palette.blade.php` — the universal ⌘K entry point — has a two-layer entrance: the scrim, and the panel.

**Scrim.**

```
class="fixed inset-0 z-command flex justify-center items-start pt-[12vh] px-4 bg-overlay backdrop-blur-[3px]"
x-transition:enter="transition ease-out duration-fast" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
```

Opacity-only fade at `dur-fast` (130ms) / `ease-out` — verified in the file, exactly as expected: the backdrop is a simple, quick fade, no transform.

**Panel.**

```
x-transition:enter="transition ease-out duration-DEFAULT"
x-transition:enter-start="opacity-0 -translate-y-2 scale-[0.985]"
x-transition:enter-end="opacity-100 translate-y-0 scale-100"
```

`duration-DEFAULT` resolves to `--dur-base` (180ms), paired with `ease-out` — the panel fades in while dropping 8px from a slightly-raised position (`-translate-y-2`) and scaling up from 98.5%. The scale delta here (1.5%) is subtler than the toast/menu's 2%, appropriate for a larger, more central surface where a bigger scale jump would feel heavier than intended.

Note the panel has no `x-transition:leave` defined — like Menu (§3), closing the palette relies on the outer `<template x-if>` unmounting the whole tree, so it disappears instantly rather than animating out. Dialog (§ below, not separately numbered per the brief but worth noting since it shares this "scrim+panel" pattern) is the one surface in this family that *does* define explicit leave transitions for both scrim and panel — `components/feedback/dialog.blade.php` uses `duration-base`/`ease-out` for its scrim enter, `duration-instant`/`ease-in` for scrim leave, and `duration-slow`/`ease-out` for the panel enter (with the same `translate-y-2 scale-[0.98]` shape as the toast entrance).

---

## 7. Inline saves

**This pattern is not yet implemented anywhere in the component library.** No current component shows a "saved" checkmark flash, an inline success pulse, or any autosave-confirmation motion — there is no `AutosaveIndicator`, no per-field save state, nothing analogous in `components/forms/` today.

This section is therefore **prescriptive, not descriptive**: a documented pattern for Tier-2 components (composed, app-specific components built on top of the Tier-1 primitives) to implement when an inline-save affordance is needed — e.g. a listing field that autosaves on blur, or a settings toggle that persists immediately.

Recommended approach, staying inside the existing token vocabulary rather than inventing new motion:

- Reuse an existing primitive rather than building bespoke animation. A `jaunt.feedback.badge` (success variant) or a brief icon swap (pencil → check, via `x-show`/`x-transition` toggling between two `<x-jaunt.icon>` instances) both already exist as Tier-1 pieces.
- Duration/easing: **`duration-fast` (130ms) / `ease-standard`** — a save confirmation is a color/state change, not a transform or an AI arrival, so it belongs with the "instant/fast + standard" family used by hover and tab-underline transitions (§1, and `components/navigation/tabs.blade.php`'s `transition-colors duration-instant ease-standard`), not with `ease-spring` (reserved for AI, §4) or the slower panel-entrance timings (§3, §6).
- Keep it brief and self-dismissing: fade the checkmark in, hold briefly, fade back to the resting icon — never leave a permanent "saved" badge sitting in the UI, per the philosophy's "if a motion doesn't clarify a state change, it's cut."
- Because this would use the same `duration-*`/`ease-*` Tailwind utilities as everything else, it inherits `prefers-reduced-motion` compliance automatically the same way §1–3/§5–6 do — no custom guard needed as long as it's built with token-driven transitions rather than hand-rolled keyframes.

---

## 8. Empty states

`components/data/empty-state.blade.php` has **no motion at all** — the icon wrap (`bg-ai-subtle`/`ai-text`/`ai-border` for the `variant="ai"` case, `bg-sunken`/`text-tertiary`/`border-subtle` for default), title, description, and actions all render as a static block with no `x-transition`, no `x-show`, no entrance animation whatsoever.

**Judgment call: this is fine as-is.** An empty state isn't really a *state change* the way a toast arriving, a row getting selected, or an AI suggestion streaming in are — it's usually the resting/starting condition of a view the user has just navigated to (an empty table, a fresh workspace), so there's nothing for motion to narrate. Animating it in would risk exactly what the philosophy warns against: motion for its own sake, on a surface where nothing actually changed. The one place it could arguably matter is when an empty state *replaces* content live (e.g. the last row of a table gets deleted and the view collapses to the empty state) — that's a genuine transition worth a subtle fade — but that's a property of the *containing* view swapping content, not something `empty-state.blade.php` itself should own. If a Tier-2 composition needs that transition, it should wrap the swap in its own `x-transition` (opacity-only, `duration-base`/`ease-out`, matching the table's other soft transitions) rather than baking motion into the primitive itself, which is used in too many different contexts (first-load, filtered-to-zero, post-delete) for one baked-in animation to suit all of them.

---

## Summary: reduced-motion coverage

| Pattern | Component | Mechanism | Reduced-motion coverage |
|---|---|---|---|
| Hover overlay / press-scale | icon-button, button, menu, table | `duration-*`/`ease-*` Tailwind utilities → `var(--dur-*)` | Automatic via `tokens/motion.css` |
| Row selection | table.blade.php | `transition-colors duration-instant ease-standard` | Automatic |
| Context menu open/close | menu.blade.php | `x-transition` + `duration-base`/`ease-out` | Automatic |
| AI sparkle pulse, thinking-dots, caret blink | ai-streaming.blade.php | hand-rolled `@keyframes` in scoped `<style>` | **Manual guard present and verified** — component's own `@media (prefers-reduced-motion: reduce)` block sets `animation: none` |
| AI suggestion entrance | ai-suggestion.blade.php | `x-transition` + `ease-spring`/`duration-slow` | Automatic — `--ease-spring` re-points to `--ease-standard` and `--dur-slow` collapses to `0ms` |
| Toast enter/exit | toast-viewport.blade.php | `x-transition` + `duration-base`/`duration-instant` | Automatic |
| Skeleton shimmer | skeleton.blade.php | hand-rolled `@keyframes j-skel-shimmer` in scoped `<style>` | **Manual guard present and verified** — component's own `@media (prefers-reduced-motion: reduce)` block sets `animation: none` |
| Command palette scrim + panel | command-palette.blade.php | `x-transition` + `duration-fast`/`duration-DEFAULT` | Automatic |
| Inline saves (prescriptive) | not yet built | should use `x-transition` + `duration-fast`/`ease-standard` | Would be automatic if built per this doc's recommendation |
| Empty state | empty-state.blade.php | none (static) | N/A — no motion to guard |

Both hand-rolled-keyframe components (`ai-streaming.blade.php`, `skeleton.blade.php`) were checked directly against this requirement and **both already carry a correct, working `prefers-reduced-motion` guard** — no gap found, no fix needed.

---

*Previous: [06-interaction-patterns.md](./06-interaction-patterns.md). Next: [08-voice-and-microcopy.md](./08-voice-and-microcopy.md). See also [00-philosophy.md](./00-philosophy.md) (Motion Philosophy) and [01-tokens.md](./01-tokens.md) (Motion tokens) for the rules this doc grounds in real components.*
