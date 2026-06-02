# Storyblok-like Content Editor — Implementation Plan

## Overview

Build a best-in-class visual content editor inside Pilot that mirrors Storyblok's interaction patterns, speed, and clarity. The editor is headless-first: content stored in the DB and delivered via API.

**Stack:** Laravel + Livewire + Tailwind (Flux) for admin UI.

---

## Milestones

### Milestone 1: MVP Editor Shell ✅
- [x] 3-column layout: Left (content tree), Center (canvas), Right (edit panel)
- [x] Left sidebar collapsible with user preference persistence
- [x] Right panel tabs: Content, Design, SEO, Settings, History
- [x] Sticky top bar: status, publish, preview, locale, breadcrumbs
- [x] Block operations: add above/below, duplicate, delete, drag reorder
- [x] Inline block affordances on canvas (select, add above/below)

### Milestone 2: Field Types & Editing UX
- [ ] Full field type support: text, textarea, richtext, number, boolean, select, multiselect, tags
- [ ] Image/file asset picker integration
- [ ] Link picker (internal story / external URL)
- [ ] Repeater/array fields
- [ ] Nested blocks (blok inside blok)
- [ ] Date/time, color picker
- [ ] Conditional fields (show/hide based on other values)
- [ ] Autosave drafts, "Saved just now" feedback
- [ ] Dirty state detection, navigation warning
- [ ] Cmd/Ctrl+S save, keyboard shortcuts

### Milestone 3: Live Preview
- [ ] In-app preview panel (editor vs site-rendered toggle)
- [ ] External preview URL with signed token
- [ ] Debounced near-real-time preview updates
- [ ] Responsive preview sizes (mobile, tablet, desktop)
- [ ] Multi-locale preview support

### Milestone 4: Revisions & Publishing
- [ ] Revisions table, snapshots with author/timestamp
- [ ] Revision history list and restore
- [ ] Basic JSON diff view
- [ ] Unpublish, scheduled publish (optional)
- [ ] Role-based permissions (admin, editor, author)

### Milestone 5: Assets Manager Integration
- [x] Asset picker modal: grid/list, search, folders
- [ ] Drag-drop upload in picker
- [ ] Metadata editing (alt, title, focal point, tags)
- [ ] Return asset object: `{ id, url, filename, width, height, mime, alt }`

### Milestone 6: Polish
- [ ] Skeleton loaders, optimistic UI
- [ ] Consistent spacing scale, typography
- [ ] Empty states, toasts, confirmation dialogs
- [ ] Accessibility: keyboard nav, focus states, ARIA

---

## Architecture Decisions

### Schema System for Block Types
**Choice:** DB-driven (BlockType model with JSON schema) — already in place.

**Justification:** Block types are managed in admin, editable without code deploys. Schema stored in `block_types.schema` as JSON. New block types added via Block Types UI; no core editor code changes needed.

### Drag-and-Drop
**Choice:** Livewire's built-in `wire:sort` + Alpine for visual feedback.

**Alternative considered:** SortableJS via `@livewire/sortable` — adds dependency. Native Livewire sort is sufficient; we enhance with Alpine drop indicators and handles.

### Preview Token Strategy
- Signed URL with `content_id`, `token`, `expires_at`
- Token stored in `content_preview_tokens` table or generated on-the-fly via `URL::temporarySignedRoute`
- API endpoint: `GET /api/v1/preview/{content}?signature=...&expires=...`
- Returns draft content when valid; published content via normal API

---

## Definition of Done (UX Parity with Storyblok)

- [ ] **Layout:** 3-column editor with collapsible left sidebar
- [ ] **Content tree:** Folders, stories, search, status filters
- [ ] **Canvas:** Blocks render with inline affordances (add above/below, select)
- [ ] **Right panel:** Tabs for Content, Design, SEO, Settings, History
- [ ] **Block ops:** Add, duplicate, delete (with undo), drag reorder
- [ ] **Field types:** text, textarea, richtext, number, boolean, select, image, link, repeater, nested blocks
- [ ] **Asset picker:** Modal with grid/list, search, folders, upload
- [ ] **Preview:** In-app + external URL with signed token
- [ ] **Autosave:** Drafts autosave, "Saved just now" feedback
- [ ] **Keyboard:** Cmd/Ctrl+S save, focus management
- [ ] **Accessibility:** Keyboard nav, focus states, ARIA where needed
- [ ] **Spacing:** Consistent scale (4, 8, 12, 16, 24, 32px)
- [ ] **Empty states:** Clear CTAs when no content/blocks
