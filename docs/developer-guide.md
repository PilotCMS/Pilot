# Pilot CMS Developer Guide

This guide explains how developers should pull CMS content into websites and map block components safely.

For the complete current delivery, templating, REST, and live-preview contract, see:

- `docs/cms-delivery-live-preview.md`

## 1) Public website rendering in this repo

Public rendering is server-side and route-driven:

- `/` -> `PageController@home`
- `/{slug}` -> `PageController@show`

Controller: `app/Http/Controllers/Site/PageController.php`

### Rendering rules

A page is renderable only when:

- `type = page`
- `status = published`
- `published_at` is not null
- slug matches the route

This prevents draft content from leaking publicly.

## 2) Theme contract (important)

The controller passes these variables to the theme page view:

- `$content` (`Pilot\Core\Support\Cms\ContentPayload`)
- `$space` (`Pilot\Core\Models\Space`)
- `$blocks` (`Collection<array<string,mixed>>`)
- `$theme` (string)

Each block arrives as:

- `_uid`: numeric block id or headless draft uid
- `id`: numeric block id or headless draft uid
- `component`: block type key (e.g. `hero`, `image`)
- `data`: locale-flattened fields for current app locale
- `children`: nested blocks with the same shape
- `editor`: editor metadata for preview contexts

## 3) Theme structure

A theme should follow:

- `resources/views/themes/{theme}/layout.blade.php`
- `resources/views/themes/{theme}/page.blade.php`
- `resources/views/themes/{theme}/components/_render-block.blade.php`
- `resources/views/themes/{theme}/components/{component}.blade.php`
- `resources/views/themes/{theme}/components/fallback.blade.php`

`_render-block` should resolve by `component` key and fallback if missing.

## 4) Configuration

From `.env`:

- `CMS_THEME=default` (or `marketing`)
- `CMS_DEFAULT_SPACE=website` (space slug)
- `CMS_HOME_SLUG=home`

Config file: `config/cms.php`

## 5) Headless content pull (external frontend)

Use the API when building a separate frontend app.

Published content:

- `GET /api/v1/spaces/{space}/contents?version=published&locale=en`
- `GET /api/v1/spaces/{space}/contents/{slug}?version=published&locale=en`

Draft content:

- same endpoints with `version=draft`
- requires Sanctum auth

Signed preview endpoint:

- `GET /api/v1/preview/{content}?signature=...&expires=...`

## 6) Component development workflow

1. Create/edit block schema in Admin -> Block types.
2. Implement matching theme component view: `components/{block-key}.blade.php`.
3. Keep fallback component in place for unmapped blocks.
4. Add/update tests for published render, fallback behavior, and live preview when relevant.

## 7) Recommended rollout for production sites

1. Start with server-rendered theme in Pilot.
2. Stabilize component set and schema.
3. Add versioned API consumers (if headless is required).
4. Keep block keys stable; treat keys as public API contracts.
