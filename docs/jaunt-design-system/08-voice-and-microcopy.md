# Voice & Microcopy

Every string in Jaunt is a design decision. This doc expands the content
fundamentals in [`_source-readme.md`](./_source-readme.md) into concrete,
component-grounded examples — it doesn't redefine the voice, it applies it.

## 1. Voice summary

Jaunt speaks **confident, concise, optimistic, human** — like a sharp colleague
who respects your time, not a vendor apologizing for existing. Sentence case
everywhere, present tense, active voice, no filler ("please," "in order to,"
"we're sorry for the inconvenience"). Jaunt refers to itself as **"Jaunt,"**
addresses the user as **"you,"** and never dresses up bad news or oversells AI
output. This is the same "calm, then confident" instinct from
[00-philosophy.md](./00-philosophy.md) principle 5 applied to words instead of
pixels: quiet by default, direct when it matters, and honest about what Jaunt
actually knows versus what it's guessing.

## 2. Buttons

Verb-first, sentence case, 1–3 words (`jaunt.forms.button`). The verb is the
whole point — a button that says "Submit" or "OK" tells the user nothing about
what happens next.

| Variant | Good | Bad | Why |
|---|---|---|---|
| primary | `Publish listing` | `Submit` | Names the outcome, not the mechanism |
| primary | `Invite partner` | `Send Invitation` | Sentence case + shorter |
| secondary | `Save draft` | `Save Draft For Later` | Cut the redundant qualifier |
| secondary | `Import calendar` | `Click here to import` | No filler, no "click here" |
| ghost | `Edit` | `Edit this item` | Context is already visible; don't restate it |
| danger | `Delete` | `Yes, delete this item` | Dialog title carries the stakes; the button carries the verb |
| danger | `Remove partner` | `Confirm removal` | Name the action, not the meta-action |
| ai | `Generate summary` | `Use AI to generate a summary for me` | Sparkle icon + indigo already signal "AI" — the label doesn't need to |
| ai | `Draft description` | `Let AI write this for you` | Same principle — the icon does the AI-announcing |

## 3. Errors

Blameless and actionable: **what happened, then the way forward.** Never
"error occurred," never a stack trace as prose, never a passive-voice dodge.

| Failure mode | Jaunt says |
|---|---|
| Validation | `Couldn't publish — 2 listings are missing a category. Review them →` |
| Upload | `Upload failed — file is over 25 MB. Try a smaller image.` |
| Network | `Couldn't save — you're offline. Changes are queued and will sync when you reconnect.` |
| Permission | `You don't have access to Campaigns. Ask an admin to add you.` |
| Domain: partner sync | `Couldn't sync 3 partners — their emails are already in use. Merge or update them →` |
| Domain: event conflict | `Can't publish — this event overlaps with "Fall Rally" on the same venue. Adjust the time →` |
| Domain: campaign budget | `Campaign can't go live — daily budget is $0. Set a budget →` |

Notice the shape holds regardless of cause: a dash separates *what happened*
from *why*, and a short imperative or arrow-linked action closes every line.
Never end on the problem.

## 4. Empty states

Invitation, not apology — always paired with a primary action
(`jaunt.data.empty-state`). Two live in the component's own header comment:

- `Nothing scheduled` / `Import a calendar or add your first event to get
  started.` → **Add event** · **Import calendar**
- `Let Jaunt draft it` (ai variant) / `Generate a first pass from your listing
  details.` → **Generate**

More, across workspaces:

| Workspace | Title | Description | Primary action |
|---|---|---|---|
| CRM | `No partners yet` | `Add your first partner or import a list to build your CRM.` | **Add partner** |
| Media | `No photos yet` | `Upload images or connect a folder to start your media library.` | **Upload photos** |
| Analytics | `Nothing to show yet` | `Data appears here once your listings start getting traffic.` | **View listings** |
| Campaigns | `No campaigns running` | `Create a campaign to start reaching visitors.` | **Create campaign** |
| Listings (search) | `No matches` | `Try a different filter or clear your search.` | **Clear filters** |

The title is always short and neutral (never "Oops" or "Sorry, nothing
here"); the description states the *fix*, not the absence.

## 5. Notifications (toasts)

`jaunt.feedback.toast` takes `title`, `message` (the default slot), and an
optional `actionLabel`. One line each, present tense, past tense only for
`title` when confirming a completed action.

| Variant | title | message | actionLabel |
|---|---|---|---|
| success | `Listing published.` | — | `View →` |
| success | `Partner invited.` | `They'll get an email to set up their account.` | — |
| info | `Sync started.` | `Importing 42 partners from your CSV.` | — |
| warning | `Draft not saved.` | `You have unsaved changes on this listing.` | `Resume editing` |
| danger | `Couldn't delete listing.` | `It's referenced by 2 active campaigns.` | `View campaigns` |
| ai | `Draft ready.` | `Jaunt wrote a first pass from your listing photos.` | `Review →` |

Danger toasts persist until dismissed; everything else auto-dismisses — so
danger copy can be a beat longer, but success/info copy should stay
scannable at a glance.

## 6. AI messages

The most important section. AI copy is **grounded, never overclaims, and
shows confidence rather than implying it** — this is principle 4 ("AI as a
teammate, not a feature") and principle 5 ("calm, then confident") made
literal in text.

**`jaunt.ai.ai-inline`** — the ghost ("Autofill with AI") and trigger modes
live inside a field, so copy stays terse:
- Ghost suggestion text: plain field content, e.g. `Missoula, Montana` — no
  framing, since the `Accept` chip and indigo tint already say "this is AI."
- Trigger label: `Autofill with AI` (default) — swap for the specific field
  when it helps: `Draft this field`, `Suggest tags`.

**`jaunt.ai.ai-streaming`** — `thinkingLabel` during the "thinking" phase,
plain settled text after:
- Thinking: `Jaunt is thinking` (default), or scoped to the task —
  `Summarizing reviews`, `Scanning for duplicates`.
- Settled response: state the finding plainly, with the number that backs
  it up — `Web referrals dropped 18% week-over-week. Mostly from the events
  page.` Never round up confidence with words like "clearly" or "definitely"
  the data doesn't support.

**`jaunt.ai.ai-suggestion`** — `label` + body:
- `label`: `Suggested by Jaunt` (default), or specific — `Drafted from your
  listing details`, `Detected 3 duplicate partners`.
- Body copy is the actual draft/finding, written as if a colleague handed it
  to you for review, e.g. a listing description draft, or `These 3 partner
  records share the same phone number. Merge them?`
- Buttons stay `Accept` / `Edit` / `Dismiss` — never `Yes` / `No`.

**`jaunt.ai.confidence-badge`** — the label *is* the voice; no separate copy
needed, but the level should match how the surrounding sentence hedges:
- `high`: state findings directly — `Revenue is up 12% this quarter.`
- `medium`: keep a soft qualifier — `Likely driven by the new fall
  campaign.`
- `low` (the one coloured level): flag it as a guess needing review — `Possible duplicate —
  worth a manual check.`

Across all AI surfaces: first person ("I found 3 duplicates") is allowed
*only* inside these clearly-AI-marked components, sparingly, and never in
system chrome or error messages elsewhere in the product.

## 7. Confirmation dialogs

`jaunt.feedback.dialog` (danger variant) is reserved for genuinely
destructive or hard-to-reverse actions — deleting records, removing a
partner's access, canceling a live campaign. The pattern is fixed:

> **Title:** `Delete 3 listings? This can't be undone.`
> **Footer:** `Delete` (danger button) · `Cancel` (ghost/secondary)

The title states the action and the count, then the consequence in one
clause. No body copy needed unless there's a non-obvious side effect (e.g.
`Delete 3 listings? This can't be undone. Any campaigns linking to them will
show a broken link.`).

**Why Dialog is the exception, not the default.** Per the interaction
philosophy in [00-philosophy.md](./00-philosophy.md) — "state changes are
optimistic and reversible, undo over confirm" — most actions in Jaunt should
resolve immediately with a toast carrying an `Undo` action, not a blocking
confirm dialog. Archiving a listing, removing a tag, dismissing an AI
suggestion: do it, show `Listing archived.` / `Undo`, and only fall back to a
modal confirmation when the action is destructive enough (permanent
deletion, revoking access) that an accidental click can't be cheaply
reversed. A confirm dialog that fires on every delete trains users to click
through it without reading — reserving Dialog for the rare, truly
irreversible action keeps it meaningful when it does appear.

(No `06-interaction-patterns.md` exists yet in this repo — when it's
written, the Undo-vs-confirm decision tree belongs there and this section
should link to it instead of restating the principle.)

## 8. Loading states

Present-progressive, specific, never a bare `Loading…`. Say what Jaunt is
doing right now.

| Operation | Jaunt says |
|---|---|
| Review summarization | `Summarizing 240 reviews…` |
| Partner import | `Importing 42 partners…` |
| Listing draft generation | `Drafting your listing description…` |
| Duplicate scan | `Scanning 1,204 partners for duplicates…` |
| Analytics report | `Crunching 90 days of traffic data…` |
| Media processing | `Optimizing 18 photos…` |

The number, where it's known, does double duty — it shows progress and
signals Jaunt isn't stalled. If the count isn't known yet, name the object
instead of falling back to a generic verb: `Loading partners…`, not
`Loading…`.

## Micro-examples

| Context | Jaunt says |
|---|---|
| Primary button | `Publish listing` |
| AI button | `Generate summary` |
| Destructive confirm | `Delete 3 listings? This can't be undone.` / `Delete` · `Cancel` |
| Toast (success) | `Listing published.` `View →` |
| Toast (ai) | `Draft ready.` `Jaunt wrote a first pass from your listing photos.` `Review →` |
| Toast (undo) | `Listing archived.` `Undo` |
| Error (validation) | `Couldn't publish — 2 listings are missing a category. Review them →` |
| Error (network) | `Couldn't save — you're offline. Changes are queued and will sync when you reconnect.` |
| Empty state | `Nothing scheduled. Create a campaign to get started.` |
| Empty state (CRM) | `No partners yet. Add your first partner or import a list.` |
| AI insight | `Web referrals dropped 18% week-over-week. Mostly from the events page.` |
| AI suggestion label | `Drafted from your listing details. Review before publishing.` |
| Confidence (low) | `Possible duplicate — worth a manual check.` |
| Loading | `Summarizing 240 reviews…` |
| Loading (import) | `Importing 42 partners…` |
