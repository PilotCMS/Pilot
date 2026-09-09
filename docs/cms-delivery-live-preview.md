# CMS Delivery and Frontend Preview

Pilot Core provides content management plus REST and GraphQL delivery. Client-facing rendering lives outside Core. Laravel sites use `pilot/laravel`; other clients consume the APIs directly.

## REST delivery

Published content is public:

```http
GET /api/v1/spaces/{space}/contents?version=published&locale=en
GET /api/v1/spaces/{space}/contents/{slug}?version=published&locale=en
```

Draft content uses the same endpoints with `version=draft` and requires an authenticated Sanctum user. Administrators can disable draft responses in CMS settings.

Responses expose Storyblok-style `story` and `content` payloads with normalized blocks, localized values, taxonomy data, and editor links. Core does not turn those payloads into client HTML.

## Signed preview payloads

The signed Core endpoint returns draft data:

```http
GET /api/v1/preview/{content}?signature=...&expires=...
```

It does not render a page or block fragment. Preview expiration and availability are controlled in CMS settings.

## Laravel frontend previews

Install `pilot/laravel` in the frontend application and configure the shared secret:

```dotenv
PILOT_CMS_URL=https://cms.example.com
PILOT_PREVIEW_SECRET=pilot_shared_secret
```

In Pilot, add that frontend's base URL under the content space's preview URLs. The editor signs a connector preview URL on the selected frontend and loads that URL in the preview frame. If no target is configured, the editor prompts an administrator to connect one instead of falling back to a Core-owned theme.

The frontend owns:

- page and catch-all routes;
- page layouts and block Blade components;
- visual themes and compiled assets;
- metadata, canonical links, and public redirects;
- connector preview and in-context routes.

## Editor bridge

The connector's editor bridge lets the CMS preview frame select blocks, preserve scroll state, and refresh when content changes. The consuming application must render the connector bridge and block metadata in preview mode.

Client-facing preview responses must allow the CMS origin to embed them. Configure `Content-Security-Policy: frame-ancestors` appropriately and do not return an incompatible `X-Frame-Options: SAMEORIGIN` header.

## Verification

Core integration tests should verify:

- no `home`, catch-all site, internal HTML preview, or connector frontend routes are registered;
- published, draft, and signed preview APIs return normalized data;
- editor preview URLs point at the configured frontend target;
- a missing preview target renders the connection prompt;
- host synchronization removes legacy public routes.

Rendering and theme tests belong in the consuming frontend repository.
