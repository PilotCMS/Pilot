# Pilot

A Storyblok-like headless CMS built with Laravel 12, Livewire 4, Tailwind CSS, Alpine.js, Sanctum, and Lighthouse GraphQL.

## Features

- **Content Management**: Tree-based content structure with folders and pages
- **Visual Page Editor**: Split-pane editor with live preview and drag-and-drop blocks
- **Block System**: Flexible block-based content composition with schema-driven forms
- **Asset Management**: File upload, organization in folders, and asset selection
- **Datasources**: Reusable data entries for select fields and dropdowns
- **Multilingual Support**: Content can be translated across multiple locales
- **Role-Based Access Control**: Admin, Editor, Author, and Viewer roles
- **REST API**: Content delivery API with version support (published/draft)
- **GraphQL API**: Query content via Lighthouse GraphQL
- **Activity Logging**: Track all content changes and user actions

## Requirements

- PHP 8.4.1+
- Composer 2
- A current Node.js LTS release and npm
- MySQL 8+ (the local default), SQLite, or PostgreSQL

Use the same supported PHP binary for Composer, Artisan, queues, and tests. If you use Herd, isolate the site to PHP 8.4 or newer before installing dependencies.

## Installation

Install the Pilot installer once with Composer:

```bash
composer global config repositories.pilot-installer vcs https://github.com/PilotCMS/pilot-installer.git
composer global require pilotcms/installer:^0.2.3
```

Create a project in a new directory, the current directory, or an explicit path:

```shell
pilot new my-project
pilot new .
pilot new --path=/absolute/path/to/my-project
```

The installer downloads the latest stable Pilot release, installs its PHP and admin asset dependencies, creates the environment file and key, builds the assets, and prepares public storage. If the repository does not have a tagged release yet, it installs `main`. Use `--branch=<name>` to intentionally install another branch or `--no-build` to skip npm.

When Laravel Herd is installed, the project is linked automatically and the installer prints its `.test/setup` URL. Add `--secure` for HTTPS, `--site=<name>` to choose the Herd site name, or `--no-herd` to skip this step.

Open the `/setup` URL printed by the command. The browser wizard will:

1. Check the server requirements and writable paths.
2. Test and save the MySQL, PostgreSQL, or SQLite connection.
3. Run migrations and seed Pilot's required reference data.
4. Create the first administrator account.
5. Configure the workspace name, URL, and default locale.
6. Show the IDE and frontend integration commands for the project.

Setup writes database credentials directly to `.env`. When setup finishes, Pilot writes a local installation lock, signs in the administrator, and disables the setup wizard.

### Installing from source

To work on Pilot itself, clone the repository and prepare the application:

```bash
git clone https://github.com/PilotCMS/Pilot.git
cd Pilot
composer run setup
```

Create an empty database before opening `/setup`. The wizard accepts the standard Laravel database values:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pilot
DB_USERNAME=root
DB_PASSWORD=
```

For servers and automated environments, the terminal installer remains available. Configure `.env` first, then run:

```bash
php artisan pilot:install --force
```

It runs the same migrations and seeders as the browser wizard and interactively creates the first administrator. No default user accounts are created.

## Updating

Pilot's versioned CMS application is installed as `pilotcms/core`. Update it from the project directory with:

```bash
pilot update
```

The command updates Pilot Core and its compatible dependencies, connects legacy host files to the package-owned application, installs the managed admin dependencies, runs pending database migrations, rebuilds admin assets, and clears application caches. Successful Pilot updates fingerprint their resulting `composer.json` and `composer.lock`, so later admin updates can distinguish updater-owned changes from project edits. Project edits must still be committed or reverted first. Use `pilot update --dry-run` to check for a release or `--no-build` when admin assets are built elsewhere.

Admin-initiated updates run as a guarded transaction: Pilot performs disk, tool, and database preflight checks; enters maintenance mode; backs up Composer files and the active SQLite, MySQL/MariaDB, or PostgreSQL database; updates and rebuilds the app; and verifies the installed Core version, database connection, route boot, and an internal `/login` response. A failed update or health check automatically restores dependencies, the database, and frontend assets. Set `PILOT_UPDATE_HEALTH_PATH` to change the internal path or `PILOT_UPDATE_HEALTH_URL` to add an external HTTP check after maintenance mode is lifted. Backups are stored under `storage/app/pilot/updates` and the latest three are retained by default.

Environment configuration, the user model, storage, and uploaded content remain in the Laravel host. Versioned CMS routes, admin components, views, migrations, and admin sources are loaded from Core so fresh and updated installations run the same managed product code. Public routes, page views, block renderers, and themes belong to a separate client application; Laravel frontends should install `pilot/laravel`.

Start the local development stack with:

```bash
composer run dev
```

This starts Laravel, the queue worker, Pail logs, and Vite together. Open the URL printed in the terminal, normally `http://127.0.0.1:8000`.

### Laravel Boost (optional)

Set up Laravel Boost for Cursor or Claude Code after installation:

```bash
php artisan boost:update
```

Boost config lives in `boost.json`. Generated agent files (`.cursor/`, `.claude/`, `AGENTS.md`, etc.) are gitignored and recreated locally by that command. In Cursor, enable **laravel-boost** in MCP settings after running it.

## First Login

Sign in with the administrator account you created during `php artisan pilot:install`. Additional Editor, Author, and Viewer accounts can be created from the user management screen.

## Admin Interface

Access the admin at `/admin/dashboard` after logging in.

### Routes

- `/admin/dashboard` - Dashboard with metrics and activity
- `/admin/content` - Content tree and list view
- `/admin/content/{id}` - Page editor
- `/admin/assets` - Asset management
- `/admin/datasources` - Datasource management
- `/admin/users` - User and role management (Admin only)
- `/admin/settings` - System settings (Admin only)

## REST API

### Get All Content

```bash
GET /api/v1/spaces/{space}/contents?locale=en&version=published
```

### Get Single Content

```bash
GET /api/v1/spaces/{space}/contents/{slug}?locale=en&version=published
```

### Access Draft Content

Draft content requires Sanctum authentication:

```bash
GET /api/v1/spaces/{space}/contents/{slug}?locale=en&version=draft
Authorization: Bearer {token}
```

To create an API token:

```php
$user = User::find(1);
$token = $user->createToken('api-token')->plainTextToken;
```

## GraphQL API

GraphQL endpoint is available at `/graphql`. Schema definitions can be found in `graphql/schema.graphql`.

Example query:

```graphql
{
  content(space: "main", slug: "homepage", locale: "en") {
    id
    name
    slug
    body {
      component
      data
    }
  }
}
```

## Block Types

The system comes with several built-in block types:

- **Section**: Container block with background color and padding
- **Hero**: Large hero section with title, subtitle, and background image
- **RichText**: WYSIWYG content block
- **Image**: Single image with alt text
- **Gallery**: Multiple images in a grid
- **CTA**: Call-to-action button block
- **Grid**: Grid layout container for nested blocks

## Adding a New Block Type

1. Create a project seeder that adds the block type and call it from `database/seeders/DatabaseSeeder.php`
2. In each consuming frontend, create the matching renderer or component for the block key
3. Add custom admin field renderers only when the schema needs a new field type

Example block type schema:

```php
[
    'key' => 'my-block',
    'name' => 'My Block',
    'icon' => 'star',
    'schema' => [
        'fields' => [
            [
                'type' => 'text',
                'key' => 'title',
                'label' => 'Title',
                'translatable' => true,
            ],
        ],
    ],
]
```

## File Storage

By default, files are stored locally in `storage/app/public`. To use S3 in production:

1. Set `FILESYSTEM_DISK=s3` in `.env`
2. Configure S3 credentials in `.env`
3. Run `php artisan storage:link` to create symbolic link

## Troubleshooting

### 413 Request Entity Too Large (File Uploads)

When uploading assets, you may see a 413 error. This is caused by Nginx's default upload limit (~1–2MB).

**Quick fix – run the script:**

```bash
chmod +x fix-upload-limit.sh
./fix-upload-limit.sh
```

This prepares a site-specific Nginx config if needed and prints the `client_max_body_size 64M` directive to add through Herd.

**Manual fix (if script doesn't work):**

1. Run `herd isolate` in the project directory
2. Edit the Herd site Nginx config for `pilot.test`
3. Add `client_max_body_size 64M;` inside the `server { }` block
4. Run `herd restart`

## Testing

Run tests with Pest:

```bash
php artisan test
```

## Development

The application uses:

- **Laravel 12** - PHP framework
- **Livewire 4** - Full-stack framework
- **Flux UI** - UI component library
- **Tailwind CSS** - Utility-first CSS
- **Alpine.js** - JavaScript framework
- **Sanctum** - API authentication
- **Lighthouse** - GraphQL server
- **Spatie Permission** - Role and permission management

## License

MIT
