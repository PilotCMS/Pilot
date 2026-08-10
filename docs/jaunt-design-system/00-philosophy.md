# Jaunt Product Philosophy

> Superseded content notice: this document originally described a hand-authored
> foundation (indigo accent, Inter typeface). It's been rewritten to match the
> canonical system produced in Claude Design and handed off for implementation
> — see [docs/_source-readme.md](./_source-readme.md) for the untouched source.
> Blue is now the one accent; indigo is reserved exclusively for AI; Geist is the typeface.

Jaunt is the operating system a destination marketing organization runs its entire operation on — CRM, CMS, listings, events, campaigns, analytics, media — eight hours a day, every day. That constraint decides almost everything below.

## Core Design Principles

**1. Clarity over density.**
DMO staff juggle listings, partners, events, and campaigns simultaneously. The interface earns trust by showing the *right* information plainly, not the *most* information. Whitespace and hierarchy are features, not empty space to be filled.
*Why:* confused users slow down and distrust the data.

**2. Speed over cleverness.**
Power users live in Jaunt eight hours a day. Every interaction targets sub-100ms perceived response — optimistic updates, instant navigation, keyboard-first interaction.
*Why:* for a daily tool, latency is the single biggest driver of how "good" the software feels.

**3. Opinionated UX over unlimited customization.**
Jaunt makes the good path the default path: fewer settings, stronger conventions, one obvious way to do a thing.
*Why:* every knob is a decision offloaded onto the user, and a surface that can drift out of consistency over time.

**4. AI as a teammate, not a feature.**
AI lives *inline*, where the work is — drafting a listing description, spotting an anomaly, filling a field. It is never a bolted-on chatbot in a corner. See [04-ai-design-language.md](./04-ai-design-language.md).
*Why:* the value of AI is proportional to how little friction stands between intent and result.

**5. Calm, then confident.**
Surfaces are quiet by default; emphasis is spent deliberately — one accent (blue), one AI signal (indigo), sparse semantic color.
*Why:* a calm baseline makes the few loud moments — a destructive action, an AI insight — actually register.

## Interaction Philosophy

Keyboard-first, mouse-friendly. Every primary action has a shortcut; the command palette (`⌘K`) is the universal entry point, not a hidden power-user Easter egg. State changes are optimistic and reversible — undo over confirm, wherever the action is cheaply reversible. Nothing blocks; long-running work streams in rather than spinning behind a modal.

## Visual Philosophy

A near-monochrome canvas on a warm neutral ramp, whose primary action is ink rather than a hue — blue is reserved for links, focus rings and data. Depth comes from a hairline outline and one soft shadow tier — never heavy drop shadows, never decorative gradients. Selection is a dark ring, not a colour wash. **Dark is the native environment; light is a first-class peer** (the system defaults to dark — see `tokens/colors.css`). No colored-left-border cards, no double borders, no mixed corner radii within one component tier.

## Motion Philosophy

Motion narrates state: where a thing came from, that a save landed, that AI is thinking. Durations run 80–260ms, spring-tinted on toggles and transforms, standard easing elsewhere. Every motion is interruptible and degrades to opacity/instant under `prefers-reduced-motion`. If a motion doesn't clarify a state change, it's cut — motion is not decoration here.

## Accessibility Philosophy

WCAG 2.2 AA is the floor, not the ceiling. Text meets 4.5:1 contrast (3:1 for large text); interactive targets are ≥44px in touch contexts; every interaction is reachable and legible by keyboard with a visible focus ring; color is never the sole carrier of meaning (an icon or label always backs it up); motion and AI states are announced to assistive tech.
*Why:* public-sector tourism organizations carry procurement accessibility requirements — and it's simply correct regardless.

---

*Next: [01-tokens.md](./01-tokens.md) — the concrete values these principles compile down to.*
