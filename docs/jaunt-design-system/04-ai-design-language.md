# AI Design Language

Live: `npm run dev`, open [`/preview/components-ai.html`](../preview/components-ai.html) for the four primitives, or [`/preview/shell.html`](../preview/shell.html) and press `⌘J` for the AI rail composed from all four.

This is the single most important document in this system. Principle 4 of [00-philosophy.md](./00-philosophy.md) is not a nice-to-have — it's the thing that separates Jaunt from "a DMO tool with a chatbot bolted on":

> **AI as a teammate, not a feature.** AI lives *inline*, where the work is — drafting a listing description, spotting an anomaly, filling a field. It is never a bolted-on chatbot in a corner.

Everything below is a consequence of that sentence. There is no dedicated "AI" workspace, no chat tab in the sidebar, no separate app. AI shows up exactly four ways, all built from the four primitives in the AI group of [03-component-library.md](./03-component-library.md), and this doc is a map from "the pattern the original brief asked for" to "the component that already implements it" — not a re-description of how those components work internally. Read their own header comments in `components/ai/*.blade.php` for that.

## The signal: iris, and only iris

`iris` is reserved exclusively for AI (see [01-tokens.md](./01-tokens.md) Color). Nothing a user authored is ever tinted iris; nothing AI-originated is ever tinted anything else. Paired with a single consistent glyph — Sparkles, always — this is the entire mechanism by which a user tells "this came from Jaunt itself" apart from "this came from the assistant" without reading a label. Every pattern below inherits this rule for free because every pattern below is built from `ai-*` tokens.

## Pattern → component map

### Inline suggestions
The lowest-friction AI touchpoint: a ghost-text completion sitting inside a field, accepted with `Tab`. Implemented by **`jaunt.ai.ai-inline`** in `ghost` mode. Lives directly inside the control it's completing (an Input, a Textarea) — never as a separate popover.

### Autofill
The same component, `trigger` mode: a small "Autofill with AI" text button, typically dropped into a field's `suffix` slot (see the AI opportunities note in [03-component-library.md](./03-component-library.md)). Implemented by **`jaunt.ai.ai-inline`**. Autofill and inline suggestion are the same primitive because they're the same interaction shape — AI proposing field content — just triggered explicitly (button) vs. proactively (ghost text as you type).

### Rewrite actions
A user-selected span of authored text gets rewritten by AI (tighten, change tone, expand). This resolves to the suggestion pattern, not a new primitive: the rewritten text is AI-authored content that must be explicitly accepted before it replaces anything the user wrote. Implemented by **`jaunt.ai.ai-suggestion`**, presented inline near the source text with the original still visible for comparison.

### Summaries
"Summarizing 240 reviews…" style output — AI condensing a larger body of content into a short, readable passage, arriving progressively rather than all at once. Implemented by **`jaunt.ai.ai-streaming`**, using its `thinking → streaming → settled` lifecycle so the wait itself is legible (see [08-voice-and-microcopy.md](./08-voice-and-microcopy.md) for the loading-copy convention — `Summarizing 240 reviews…`, not a spinner with no label).

### AI-generated insights
"Web referrals dropped 18% week-over-week. Mostly from the events page." — AI noticing something the user didn't ask about. Implemented by **`jaunt.ai.ai-streaming`** for the delivery (it's still a response arriving over time, thinking then answering) — surfaced wherever the insight is contextually relevant (an Analytics KPI card, a CRM record), never collected into a separate "insights feed."

### AI recommendations
A specific, actionable next step Jaunt is proposing ("Restore partner links to the events page header"), as opposed to a passive observation. Implemented by **`jaunt.ai.ai-suggestion`** — its label/body/action-row shape *is* the recommendation card. This is the same component used for rewrite actions and autofill review; a recommendation is just a suggestion whose content happens to be an instruction rather than a draft.

### AI approvals & human review
Not a separate pattern — the guardrail baked into `jaunt.ai.ai-suggestion` itself. Every instance renders Accept / Edit / Dismiss and nothing AI-authored is ever committed without an explicit Accept click. There is no "auto-apply" mode anywhere in the system; see the component's own header comment for why that's treated as a hard rule, not a default that screens can opt out of.

### AI confidence
How sure Jaunt is about a suggestion or insight, so a user can calibrate "trust and move on" vs. "read this closely." Implemented by **`jaunt.ai.confidence-badge`**, always passed into an `ai-suggestion`'s `confidence` slot (see the example in `components/ai/ai-suggestion.blade.php` and the AI rail below). Levels map to semantic status color — low = amber, high = green — **except medium, which stays iris**: a deliberate honesty-over-bravado choice, so "medium confidence" never accidentally reads as a calm, safe green.

### Streaming responses
The mechanics of AI "thinking" then "answering" token-by-token. Implemented by **`jaunt.ai.ai-streaming`**, `role="status" aria-live="polite"` so assistive tech hears the settled result once rather than every token (see the production note in the component's own header comment about swapping its demo typewriter for a real `wire:stream`/SSE source).

### The "Ask Jaunt" entry point
The one surface where a user asks an open-ended question rather than reacting to something Jaunt already surfaced inline. Implemented by **`jaunt.shell.ai-rail`** — a fixed, right-side, non-modal slide-in panel (no scrim; you can keep working in the shell behind it), opened from the topbar's Sparkles icon or `⌘J` (see [02-app-shell.md](./02-app-shell.md) Topbar). It's a *composition*, not a new primitive: a question bubble, an `ai-streaming` answer, and an `ai-suggestion` (with a `confidence-badge`) for whatever recommendation falls out of the answer — the exact same building blocks as everywhere else in the product, just assembled at higher bandwidth.

## Motion: the one signature reserved for AI

Per the Motion Philosophy in [00-philosophy.md](./00-philosophy.md), `ease-spring` is never used for anything a user did — it's reserved exclusively for content *arriving because the assistant produced it*. Concretely:

- `jaunt.ai.ai-suggestion` enters with `ease-spring` + `duration-slow`.
- `jaunt.shell.ai-rail` enters with `ease-spring` + `duration-slower` (`--dur-slower`, 400ms, is annotated in [01-tokens.md](./01-tokens.md) specifically as "AI rail entrance") — the largest AI-originated surface gets the slowest, most deliberate motion in the system, still spring-tinted.
- Everything else in Jaunt — panel opens, hovers, toggles a user triggered directly — uses `ease-standard` or `ease-out`. If you see `ease-spring` on something, it should always trace back to AI having produced it.

This is a small rule with an outsized effect: a user develops an unconscious sense of "that arrived because I acted" vs. "that arrived because Jaunt did something," purely from how it moved onto the screen, with no label required.

## Copy: grounded, never overclaiming

Per `docs/_source-readme.md`'s Content fundamentals: AI copy states what was done and invites review, it doesn't perform confidence it doesn't have.

- `Drafted from your listing details. Review before publishing.`
- `Web referrals dropped 18% week-over-week. Mostly from the events page.`
- `Summarizing 240 reviews…`

None of these say "I think," hedge with "maybe," or oversell with "I've fixed this for you." Confidence is a badge users can see, not a tone the copy performs.

## Every screen supports AI without feeling cluttered

Three surfaces, in ascending order of friction and bandwidth — deliberately, never a fourth:

1. **Inline** (`jaunt.ai.ai-inline`) — lowest friction. Lives inside the field or control where the work is already happening. A user barely has to notice it's AI at all; it's just the fastest way to finish the field.
2. **Suggestion cards** (`jaunt.ai.ai-suggestion`) — medium friction, in-context. Appears next to the specific record, chart, or content it's about, with an explicit Accept/Edit/Dismiss decision. A user sees it because they're already looking at the thing it concerns.
3. **The AI rail** (`jaunt.shell.ai-rail`, `⌘J`) — highest bandwidth, for open-ended questions that don't have an obvious inline home ("why did referrals drop this week?"). It's the one place a user goes looking for AI rather than AI meeting them where they are.

There is deliberately no fourth tier — no persistent chatbot tab, no "AI assistant" workspace in the sidebar, no floating launcher bubble. Every AI surface in Jaunt is one of these three, which is what keeps the system from accumulating a second, parallel product living on top of the real one. If a new AI use case doesn't fit inline, a suggestion card, or the rail, that's a signal the use case needs rethinking — not a reason to add a fourth surface.

---

*Next: [05-workspace-pattern.md](./05-workspace-pattern.md) — how the shell and component vocabulary compose into full product screens.*
