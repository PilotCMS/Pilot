# CMS Delivery, Templating, and Live Preview

Pilot now has a Storyblok-like content delivery layer that can render content from either local MySQL models or a headless JSON payload. The same normalized payload is used by public Blade themes, REST API responses, signed preview links, and live preview rendering.

## Goals

- Render published CMS pages through Laravel Blade themes.
- Expose CMS content through a headless REST API.
- Keep draft MySQL content protected behind auth or signed URLs.
- Allow live preview to render posted headless content without first writing it to MySQL.
- Add editor metadata only in editor/preview contexts.

## Core classes

- `App\Support\Cms\ContentRenderer`
  - `fromModel(Content $content, ?string $locale = null)` normalizes MySQL content.
  - `fromHeadless(array $payload, ?string $locale = null)` normalizes posted JSON.
  - `renderBlocks(ContentPayload $content, ?string $theme = null)` renders a theme block fragment.
  - `renderPage(ContentPayload $content, ?string $theme = null)` renders a full theme page.

- `App\Support\Cms\ContentPayload`
  - Represents one page/story.
  - Produces a Storyblok-like API shape with `content.component = page` and `content.body`.
  - Includes public URL and editor URL links.

- `App\Support\Cms\BlockPayload`
  - Represents one CMS block.
  - Localizes translatable field arrays by locale.
  - Adds editor metadata when `pilot_editor=1`, `editor=1`, or the request referer comes from `/admin/content/`.

## Configuration

Configuration lives in `config/cms.php`.

```php
return [
    'default_space' => env('PILOT_DEFAULT_SPACE'),
    'home_slug' => env('PILOT_HOME_SLUG', 'home'),
    'editor_bridge' => [
        'enabled' => env('PILOT_EDITOR_BRIDGE_ENABLED', true),
        'live_preview' => env('PILOT_LIVE_PREVIEW_ENABLED', true),
        'live_root' => env('PILOT_LIVE_PREVIEW_ROOT', '[data-pilot-live-root]'),
    ],
];
```

Useful `.env` values:

```dotenv
PILOT_DEFAULT_SPACE=website
PILOT_HOME_SLUG=home
PILOT_EDITOR_BRIDGE_ENABLED=true
PILOT_LIVE_PREVIEW_ENABLED=true
PILOT_LIVE_PREVIEW_ROOT="[data-pilot-live-root]"
```

## Public page rendering

Public routes are still route-driven:

- `/` -> `App\Http\Controllers\Site\PageController@home`
- `/{slug}` -> `App\Http\Controllers\Site\PageController@show`

Public rendering only selects content where:

- `type = page`
- `status = published`
- `published_at` is not null
- `slug` matches the route

`PageController` resolves the space from `CMS_DEFAULT_SPACE`, then falls back to the first space. It loads blocks, normalizes the page with `ContentRenderer`, and passes this view data to the active theme:

- `$content`: `App\Support\Cms\ContentPayload`
- `$space`: `App\Models\Space`
- `$blocks`: `Collection<array<string, mixed>>`
- `$theme`: active theme name

## Theme structure

Themes live under `resources/views/themes/{theme}`.

Required files:

- `layout.blade.php`
- `page.blade.php`
- `partials/blocks.blade.php`
- `components/_render-block.blade.php`
- `components/fallback.blade.php`

Component files should be named by block type key:

- `components/hero.blade.php`
- `components/richtext.blade.php`
- `components/image.blade.php`
- `components/cta.blade.php`
- `components/columns.blade.php`
- `components/grid.blade.php`
- `components/section.blade.php`

The `_render-block` partial resolves by component key and falls back when a component Blade file does not exist.

Container components receive nested blocks in `children`. `columns` and `grid` use each child block's `data['_column']` value to place that child in a specific column. If `_column` is missing, the renderer falls back to distributing child blocks by position so older content continues to render.

## Block payload shape

Theme components receive each block as an array:

```php
[
    '_uid' => 123,
    'id' => 123,
    'component' => 'hero',
    'data' => [
        'title' => 'Welcome',
        'subtitle' => 'Build with Pilot',
    ],
    'children' => [],
    'editor' => [
        'enabled' => false,
        'attributes' => '',
        'comment' => '',
    ],
]
```

Access data in Blade with:

```blade
@php
    $title = $block['data']['title'] ?? 'Fallback title';
@endphp

<section>
    <h1>{{ $title }}</h1>
</section>
```

## Editor metadata

Editor metadata is rendered only in editor contexts. Normal public requests do not get block attributes or comments.

Editor contexts are detected by:

- `?pilot_editor=1`
- `?editor=1`
- a referer containing `/admin/content/`

When enabled, each block wrapper receives attributes like:

```html
<div
    data-pilot-editable="block"
    data-pilot-block-id="123"
    data-pilot-component="hero"
    data-pilot-component-path="page/hero"
>
```

The block payload also exposes an editor comment:

```html
<!-- pilot:block:123:hero -->
```

## Editor bridge

The shared bridge lives in `packages/pilot-laravel/resources/views/editor-bridge.blade.php` and is included by Pilot package layouts.

It exposes:

```js
window.PilotCms.livePreview.render(payload, options)
```

The helper posts to `api.preview.render`, replaces the element matching `PILOT_LIVE_PREVIEW_ROOT`, and returns the API response.

It also listens for clicks on `[data-pilot-editable="block"]` inside an iframe and sends this message to the parent editor:

```js
{
  type: 'pilot-preview-select-block',
  blockId: 123,
  component: 'hero',
  componentPath: 'page/hero'
}
```

## REST delivery API

Published content is public:

```http
GET /api/v1/spaces/{space}/contents?version=published&locale=en
GET /api/v1/spaces/{space}/contents/{slug}?version=published&locale=en
```

Draft content uses the same endpoints with `version=draft`, but draft access requires an authenticated user and can be disabled with CMS settings.

Single content response:

```json
{
  "story": {
    "id": 1,
    "slug": "home",
    "full_slug": "home",
    "name": "Home",
    "status": "published",
    "published_at": "2026-06-08T15:00:00+00:00",
    "meta": {},
    "source": "mysql",
    "content": {
      "component": "page",
      "body": [
        {
          "_uid": 10,
          "id": 10,
          "component": "hero",
          "data": {
            "title": "Welcome"
          },
          "children": [],
          "editor": {
            "enabled": false,
            "attributes": "",
            "comment": ""
          }
        }
      ]
    },
    "body": [],
    "links": {
      "url": "https://pilot.test/",
      "editor": "https://pilot.test/admin/content/1/edit"
    }
  },
  "content": {}
}
```

The response includes both `story` and `content` for compatibility during the transition. New consumers should prefer `story`.

## Signed preview API

Signed preview links return draft content when the signature is valid:

```http
GET /api/v1/preview/{content}?signature=...&expires=...
```

Use `App\Http\Controllers\Api\PreviewController::signedUrl($content)` to generate a temporary signed URL.

## Live preview render API

Live preview rendering is handled by:

```http
POST /api/v1/preview/render
```

The endpoint returns:

```json
{
  "html": "<div>...</div>",
  "content": {},
  "source": "headless"
}
```

### Headless live preview

Use this when the editor has draft changes that do not need to be written to MySQL before rendering:

```json
{
  "source": "headless",
  "theme": "default",
  "locale": "en",
  "content": {
    "slug": "preview-home",
    "name": "Preview Home",
    "body": [
      {
        "_uid": "draft-hero",
        "component": "hero",
        "data": {
          "title": "Typed in the editor",
          "subtitle": "Rendered without a database row"
        }
      }
    ]
  }
}
```

This path is intentionally useful for CMS live preview because it can render unsaved editor state.

### MySQL live preview

Use this when the editor wants to render content stored in MySQL:

```json
{
  "source": "mysql",
  "content_id": 1,
  "theme": "default",
  "locale": "en"
}
```

Published MySQL content can be rendered normally. Draft MySQL content requires either:

- an authenticated request, or
- a valid signed URL.

Generate a signed render URL with:

```php
URL::temporarySignedRoute('api.preview.render', now()->addMinutes(15), [
    'content_id' => $content->id,
]);
```

## Adding a new component

1. Create or update a block type in Admin -> Blocks.
2. Use a stable block key, for example `feature_grid`.
3. Add a Blade component:
   - `resources/views/themes/default/components/feature_grid.blade.php`
   - repeat for each active theme as needed
4. Read fields from `$block['data']`.
5. Render nested blocks from `$block['children']` if the component supports child blocks.
6. Add or update a feature test that proves the block renders.

Missing component views are safe: the theme fallback component renders instead of failing the page.

## Tests

Focused coverage for this layer:

```bash
<path-to-php> artisan test --compact \
  tests/Feature/CmsDeliveryApiTest.php \
  tests/Feature/CmsLivePreviewTest.php
```

Related public/admin preview coverage:

```bash
<path-to-php> artisan test --compact \
  tests/Feature/PublicThemeTest.php \
  tests/Feature/Admin/ContentPreviewTest.php \
  tests/Feature/Admin/CmsSettingsTest.php
```

Run Pint after PHP changes:

```bash
<path-to-php> vendor/bin/pint --dirty --format agent
```
