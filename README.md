# Pilot

A Storyblok-like headless CMS built with Laravel 11, Livewire 3, Tailwind CSS, Alpine.js, Sanctum, and Lighthouse GraphQL.

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

- PHP 8.2+
- Composer
- Node.js & NPM
- SQLite (for local development) or MySQL/PostgreSQL

## Installation

1. Clone the repository and install dependencies:

```bash
composer install
npm install
```

2. Copy the environment file and generate the application key:

```bash
cp .env.example .env
php artisan key:generate
```

3. Create the database (SQLite for local development):

```bash
touch database/database.sqlite
```

4. Run migrations and seed the database:

```bash
php artisan migrate
php artisan db:seed
```

5. Build frontend assets:

```bash
npm run build
# Or for development:
npm run dev
```

6. Start the development server:

```bash
php artisan serve
```

## Default Login

After seeding, you can log in with:

- **Admin**: admin@pilot.com / password
- **Editor**: editor@pilot.com / password
- **Author**: author@pilot.com / password
- **Viewer**: viewer@pilot.com / password

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

1. Create a block type seeder entry in `database/seeders/BlockTypeSeeder.php`
2. Create a Blade renderer in `resources/views/blocks/{key}.blade.php`
3. Create field renderers in `resources/views/admin/fields/{type}.blade.php` if needed

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

This creates a site-specific Nginx config (if needed) and adds `client_max_body_size 64M`, then restarts Herd.

**Manual fix (if script doesn't work):**

1. Run `herd isolate` in the project directory
2. Edit `~/Library/Application Support/Herd/config/valet/Nginx/pilot.test`
3. Add `client_max_body_size 64M;` inside the `server { }` block
4. Run `herd restart`

## Testing

Run tests with Pest:

```bash
php artisan test
```

## Development

The application uses:

- **Laravel 11** - PHP framework
- **Livewire 3** - Full-stack framework
- **Flux UI** - UI component library
- **Tailwind CSS** - Utility-first CSS
- **Alpine.js** - JavaScript framework
- **Sanctum** - API authentication
- **Lighthouse** - GraphQL server
- **Spatie Permission** - Role and permission management

## License

MIT
