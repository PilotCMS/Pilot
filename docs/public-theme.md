# Public Theme + Content Delivery

This project now includes a sample public-facing theme that renders published CMS pages directly from Pilot content, plus documented API options for headless usage.

For the complete current delivery, templating, REST, and live-preview contract, see:

- `/Users/kylemcgowan/Herd/Pilot/docs/cms-delivery-live-preview.md`

## How public page rendering works

### Route flow

- `/` -> `App\Http\Controllers\Site\PageController@home`
- `/{slug}` -> `App\Http\Controllers\Site\PageController@show`

These routes are in `/Users/kylemcgowan/Herd/Pilot/routes/web.php`.

### Content selection rules

`PageController` only renders content when all conditions are true:

- `type = page`
- `slug = requested slug`
- `status = published`
- `published_at IS NOT NULL`

Draft pages are never shown on the public site.

### Space selection

Public rendering resolves a Space in this order:

1. `CMS_DEFAULT_SPACE` (by slug), if set
2. First Space in DB as fallback

Configured in `/Users/kylemcgowan/Herd/Pilot/config/cms.php`.

## Sample theme structure

The sample theme is `default` and lives at:

- `/Users/kylemcgowan/Herd/Pilot/resources/views/themes/default/layout.blade.php`
- `/Users/kylemcgowan/Herd/Pilot/resources/views/themes/default/page.blade.php`
- `/Users/kylemcgowan/Herd/Pilot/resources/views/themes/default/components/*`

### Component rendering contract

Each block is transformed to this shape before rendering:

- `_uid` (block id or headless draft uid)
- `id` (block id or headless draft uid)
- `component` (block type key)
- `data` (locale-flattened field values)
- `children` (nested blocks)
- `editor` (preview-only editor metadata)

`page.blade.php` renders each block through:

- `themes.default.components._render-block`

That resolver includes:

- `themes.default.components.{component}` if present
- otherwise `themes.default.components.fallback`

This gives a safe fallback for newly created block types without breaking page rendering.

## Available sample components

- `hero.blade.php`
- `richtext.blade.php`
- `image.blade.php`
- `cta.blade.php`
- `columns.blade.php` (renders nested children into explicit column slots)
- `grid.blade.php` (uses the same nested rendering behavior as columns)
- `section.blade.php` (renders nested children)
- `fallback.blade.php`

## Nested column blocks

Container blocks can render child blocks through the `children` payload key. The `columns` and `grid` components use `data['_column']` on each child block to decide which column should render that child.

When a child does not have `_column`, the renderer falls back to distributing children by position. This keeps older content renderable while new editor-created blocks are assigned to a specific column.

## Headless (API) content pull options

Use these when rendering from another frontend app (Next.js, Nuxt, etc):

- `GET /api/v1/spaces/{space}/contents?version=published&locale=en`
- `GET /api/v1/spaces/{space}/contents/{slug}?version=published&locale=en`

Draft access:

- `version=draft` requires Sanctum auth
- for secure editor preview links use signed preview endpoint:
  - `GET /api/v1/preview/{content}?signature=...&expires=...`

Controller reference:

- `/Users/kylemcgowan/Herd/Pilot/app/Http/Controllers/Api/ContentController.php`
- `/Users/kylemcgowan/Herd/Pilot/app/Http/Controllers/Api/PreviewController.php`

## Environment variables

Add to `.env` as needed:

- `CMS_THEME=default`
- `CMS_DEFAULT_SPACE=your-space-slug`
- `CMS_HOME_SLUG=home`
- `CMS_DELIVERY_SOURCE=mysql`
- `CMS_EDITOR_BRIDGE_ENABLED=true`
- `CMS_LIVE_PREVIEW_ENABLED=true`

## How to create a new theme

1. Create a new folder under `resources/views/themes/{theme-name}`.
2. Copy `layout.blade.php`, `page.blade.php`, and `components/_render-block.blade.php`.
3. Implement block component templates under `components/`.
4. Set `CMS_THEME={theme-name}` in `.env`.

No controller changes are required if you keep the same view contract.
