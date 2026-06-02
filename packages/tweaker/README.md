# Tweaker Laravel Package

Tweaker provides an in-app visual editor for CSS/text with optional DB-backed inline updates.

## Install

### Option A: Packagist

```bash
composer require tweaker/tweaker
```

### Option B: Direct from a Git repo

In the target app `composer.json`:

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "git@github.com:YOUR_ORG/tweaker.git"
    }
  ]
}
```

Then install:

```bash
composer require tweaker/tweaker:dev-main
```

## Laravel Setup

1. Publish config:

```bash
php artisan vendor:publish --tag=tweaker-config
```

2. Enable in `.env`:

```env
TWEAKER_ENABLED=true
```

3. Add CSRF token in your base layout `<head>`:

```blade
<meta name="csrf-token" content="{{ csrf_token() }}">
```

4. Add Tweaker script in your layout:

```blade
@tweakerScripts
```

5. (Optional) include generated Blade overrides:

```blade
@include('tweaker.overrides')
```

## Usage

- Toggle UI: `Ctrl/Cmd + Shift + T`
- Save CSS changes to LESS: `Save LESS`
- Save text overrides to Blade: `Save Blade`

For DB-backed inline text updates, annotate DOM nodes:

```html
<span
  data-tweaker-model="App\\Models\\Post"
  data-tweaker-id="123"
  data-tweaker-field="title"
>
  Current title
</span>
```

Double-click to edit and blur to persist.

## Security Notes

- Keep `TWEAKER_ENABLED=false` outside local/dev.
- Restrict `allowed_models` and `allowed_fields` in `config/tweaker.php`.
- `updateModel` only updates fillable model attributes.

## Config

- `enabled`
- `route_prefix`
- `route_middleware`
- `allowed_paths`
- `default_less_path`
- `default_blade_path`
- `log_to_database`
- `allowed_models`
- `allowed_fields`
