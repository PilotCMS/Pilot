# Pilot CMS Developer Guide

Pilot Core is the CMS and delivery API. It does not register public website routes or own client-facing Blade views, components, or themes.

## Delivery options

For any frontend, consume the REST or GraphQL delivery API:

- `GET /api/v1/spaces/{space}/contents?version=published&locale=en`
- `GET /api/v1/spaces/{space}/contents/{slug}?version=published&locale=en`
- the same REST endpoints with `version=draft` and Sanctum authentication
- `GET /api/v1/preview/{content}?signature=...&expires=...` for signed draft payloads

For a Laravel frontend, install `pilot/laravel`. The frontend application owns its routes, page layout, block components, assets, SEO markup, redirects, and visual theme.

## CMS configuration

Core uses these delivery settings:

```dotenv
CMS_DEFAULT_LOCALE=en
```

The admin settings screen can override the default locale, draft API access, signed preview availability, and preview expiration.

## Laravel frontend workflow

1. Install `pilot/laravel` in a separate Laravel application.
2. Publish and configure the connector.
3. Add the application's base URL as a preview target in the Pilot space settings.
4. Copy the generated `PILOT_PREVIEW_SECRET` into the frontend environment.
5. Define the frontend page routes and controller.
6. Build a Blade component for each stable block type key.
7. Include the connector's editor bridge and in-context views when previews require them.

Keep block type keys stable because they are part of the delivery API contract. A frontend should provide its own fallback for an unknown block type.

## Ownership boundary

| Concern | Owner |
| --- | --- |
| Content, schemas, assets, publishing, revisions | Pilot Core |
| Published and draft payload delivery | Pilot Core APIs |
| Laravel content normalization and preview bridge | `pilot/laravel` |
| Public routes, HTML, components, SEO, redirects, theme | Frontend application |
