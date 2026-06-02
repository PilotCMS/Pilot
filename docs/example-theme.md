# Example Public Themes

Pilot currently includes two public-facing example themes.

## 1) Default theme

Path:

- `/Users/kylemcgowan/Herd/Pilot/resources/views/themes/default`

Characteristics:

- clean neutral layout
- straightforward component mapping
- good starter for internal sites

## 2) Marketing theme

Path:

- `/Users/kylemcgowan/Herd/Pilot/resources/views/themes/marketing`

Characteristics:

- stronger visual hierarchy and hero treatment
- gradient/atmospheric background
- intended as a public-facing marketing style example

## Switching themes

Set in `.env`:

- `CMS_THEME=default`
- `CMS_THEME=marketing`

Then clear config cache if needed:

- `herd php artisan config:clear`

## Required files for any new theme

- `layout.blade.php`
- `page.blade.php`
- `components/_render-block.blade.php`
- `components/fallback.blade.php`

And component views matching block keys (e.g. `hero.blade.php`, `image.blade.php`).
