<?php

use App\Models\User;
use Laravel\Mcp\Server\Transport\FakeTransporter;
use Livewire\Livewire;
use Pilot\Core\Database\Seeders\RoleSeeder;
use Pilot\Core\Models\Activity;
use Pilot\Core\Models\Asset;
use Pilot\Core\Models\Block;
use Pilot\Core\Models\BlockType;
use Pilot\Core\Models\Content;
use Pilot\Core\Models\ContentType;
use Pilot\Core\Models\Datasource;
use Pilot\Core\Models\DatasourceEntry;
use Pilot\Core\Models\Space;
use Pilot\Mcp\Livewire\Settings;
use Pilot\Mcp\Servers\PilotServer;
use Pilot\Mcp\Tools\AddDatasourceEntries;
use Pilot\Mcp\Tools\CreatePageDraft;
use Pilot\Mcp\Tools\GetDatasourceContext;
use Pilot\Mcp\Tools\GetPageBuildingContext;
use Pilot\Mcp\Tools\SearchMedia;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('exposes the MCP extension in Pilot settings', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    $this->actingAs($admin)
        ->get(route('admin.extensions.mcp.index'))
        ->assertOk()
        ->assertSee('MCP Server')
        ->assertSee('Constrained write access')
        ->assertSeeInOrder(['Settings', 'MCP Server'])
        ->assertSee('data-pilot-settings-navigation', false)
        ->assertSee(url('/mcp/pilot'));
});

it('creates scoped MCP tokens from extension settings', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    Livewire::actingAs($admin)
        ->test(Settings::class)
        ->call('createToken')
        ->assertSet('newToken', fn (?string $token): bool => is_string($token) && str_contains($token, '|'));

    $token = $admin->tokens()->sole();

    expect($token->abilities)->toBe(['pilot-mcp'])
        ->and($token->expires_at)->not->toBeNull();
});

it('hides MCP settings navigation and rejects the route without settings permission', function () {
    $author = User::factory()->create();
    $author->assignRole('Author');

    $this->actingAs($author)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertDontSee('MCP Server');

    $this->actingAs($author)
        ->get(route('admin.extensions.mcp.index'))
        ->assertForbidden();
});

it('discovers existing components and media before building a page', function () {
    $space = Space::factory()->create(['name' => 'Website']);
    $contentType = ContentType::factory()->create([
        'name' => 'Landing page',
        'key' => 'landing-page',
        'allowed_blocks' => ['hero'],
        'is_active' => true,
    ]);
    BlockType::factory()->create([
        'key' => 'hero',
        'name' => 'Hero',
        'schema' => ['fields' => [
            ['key' => 'heading', 'type' => 'text', 'required' => true, 'default' => ''],
            ['key' => 'image', 'type' => 'image', 'required' => false, 'default' => ''],
        ]],
    ]);
    $asset = Asset::factory()->create([
        'space_id' => $space->id,
        'display_name' => 'Mountain sunrise',
        'alt' => 'Morning light over the mountains',
    ]);
    $author = User::factory()->create();
    $author->assignRole('Author');
    $author->withAccessToken($author->createToken('Test MCP', ['pilot-mcp'])->accessToken);

    PilotServer::actingAs($author)
        ->tool(GetPageBuildingContext::class, [
            'space_id' => $space->id,
            'content_type_key' => $contentType->key,
        ])
        ->assertOk()
        ->assertSee(['Website', 'Landing page', 'hero', 'creates_drafts_only']);

    PilotServer::actingAs($author)
        ->tool(SearchMedia::class, [
            'space_id' => $space->id,
            'query' => 'mountain',
        ])
        ->assertOk()
        ->assertSee(['Mountain sunrise', (string) $asset->id]);
});

it('discovers existing datasources and appends localized entries transactionally', function () {
    $space = Space::factory()->create(['name' => 'Website']);
    $datasource = Datasource::create([
        'space_id' => $space->id,
        'name' => 'Workflow statuses',
        'slug' => 'workflow-statuses',
    ]);
    DatasourceEntry::create([
        'datasource_id' => $datasource->id,
        'key' => 'draft',
        'value' => ['en' => 'Draft'],
        'order' => 0,
    ]);
    $editor = User::factory()->create();
    $editor->assignRole('Editor');
    $editor->withAccessToken($editor->createToken('Test MCP', ['pilot-mcp'])->accessToken);

    PilotServer::actingAs($editor)
        ->tool(GetDatasourceContext::class, [
            'space_id' => $space->id,
            'query' => 'workflow',
        ])
        ->assertOk()
        ->assertSee(['Workflow statuses', 'workflow-statuses', 'draft', 'adds_new_entries_only']);

    PilotServer::actingAs($editor)
        ->tool(AddDatasourceEntries::class, [
            'datasource_id' => $datasource->id,
            'entries' => [
                ['key' => 'in-review', 'values' => ['en' => 'In review', 'fr' => 'En révision']],
                ['key' => 'approved', 'values' => ['en' => 'Approved', 'fr' => 'Approuvé']],
            ],
        ])
        ->assertOk()
        ->assertSee(['Workflow statuses', 'in-review', 'approved', 'created_count']);

    $entries = $datasource->entries()->orderBy('order')->get();

    expect($entries->pluck('key')->all())->toBe(['draft', 'in-review', 'approved'])
        ->and($entries[1]->value)->toBe(['en' => 'In review', 'fr' => 'En révision'])
        ->and($entries[1]->order)->toBe(1)
        ->and($entries[2]->order)->toBe(2);

    $activity = Activity::query()
        ->where('subject_type', Datasource::class)
        ->where('subject_id', $datasource->id)
        ->sole();

    expect($activity->action)->toBe('added datasource entries via MCP')
        ->and($activity->meta)->toMatchArray([
            'source' => 'mcp',
            'entry_count' => 2,
            'keys' => ['in-review', 'approved'],
        ]);
});

it('rejects duplicate datasource keys without creating a partial batch', function () {
    $space = Space::factory()->create();
    $datasource = Datasource::create([
        'space_id' => $space->id,
        'name' => 'Statuses',
        'slug' => 'statuses',
    ]);
    DatasourceEntry::create([
        'datasource_id' => $datasource->id,
        'key' => 'existing',
        'value' => ['en' => 'Existing'],
        'order' => 0,
    ]);
    $editor = User::factory()->create();
    $editor->assignRole('Editor');
    $editor->withAccessToken($editor->createToken('Test MCP', ['pilot-mcp'])->accessToken);

    PilotServer::actingAs($editor)
        ->tool(AddDatasourceEntries::class, [
            'datasource_id' => $datasource->id,
            'entries' => [
                ['key' => 'would-be-partial', 'values' => ['en' => 'Partial']],
                ['key' => 'existing', 'values' => ['en' => 'Replacement']],
            ],
        ])
        ->assertHasErrors(['already exist', 'Existing entries are never replaced']);

    expect($datasource->entries()->pluck('key')->all())->toBe(['existing'])
        ->and(Activity::query()->where('subject_type', Datasource::class)->where('subject_id', $datasource->id)->exists())->toBeFalse();
});

it('requires datasource permissions for MCP datasource tools', function () {
    $space = Space::factory()->create();
    $datasource = Datasource::create([
        'space_id' => $space->id,
        'name' => 'Statuses',
        'slug' => 'statuses',
    ]);
    $viewer = User::factory()->create();
    $viewer->assignRole('Viewer');
    $viewer->withAccessToken($viewer->createToken('Test MCP', ['pilot-mcp'])->accessToken);

    PilotServer::actingAs($viewer)
        ->tool(GetDatasourceContext::class, ['datasource_id' => $datasource->id])
        ->assertOk()
        ->assertSee('Statuses');

    PilotServer::actingAs($viewer)
        ->tool(AddDatasourceEntries::class, [
            'datasource_id' => $datasource->id,
            'entries' => [['key' => 'blocked', 'values' => ['en' => 'Blocked']]],
        ])
        ->assertHasErrors(['needs the [manage datasources] permission']);

    expect($datasource->entries()->exists())->toBeFalse();
});

it('creates a complete page as a draft from existing components and media', function () {
    $space = Space::factory()->create(['name' => 'Website']);
    $folder = Content::factory()->create([
        'space_id' => $space->id,
        'type' => 'folder',
        'name' => 'Campaigns',
        'slug' => 'campaigns',
    ]);
    $contentType = ContentType::factory()->create([
        'name' => 'Landing page',
        'key' => 'landing-page',
        'schema' => ['fields' => [
            ['key' => 'seo_title', 'type' => 'text', 'required' => true, 'default' => ''],
        ]],
        'allowed_blocks' => ['hero', 'rich-copy'],
        'is_active' => true,
    ]);
    BlockType::factory()->create([
        'key' => 'hero',
        'name' => 'Hero',
        'schema' => ['fields' => [
            ['key' => 'heading', 'type' => 'text', 'required' => true, 'translatable' => true, 'default' => ''],
            ['key' => 'image', 'type' => 'image', 'required' => true, 'default' => ''],
        ]],
    ]);
    BlockType::factory()->create([
        'key' => 'rich-copy',
        'name' => 'Rich copy',
        'schema' => ['fields' => [
            ['key' => 'body', 'type' => 'textarea', 'required' => true, 'default' => ''],
        ]],
    ]);
    $asset = Asset::factory()->create([
        'space_id' => $space->id,
        'path' => 'assets/mountain-sunrise.jpg',
        'filename' => 'mountain-sunrise.jpg',
        'focal_x' => 35.5,
        'focal_y' => 62.0,
    ]);
    $author = User::factory()->create();
    $author->assignRole('Author');
    $author->withAccessToken($author->createToken('Test MCP', ['pilot-mcp'])->accessToken);

    PilotServer::actingAs($author)
        ->tool(CreatePageDraft::class, [
            'space_id' => $space->id,
            'name' => 'Explore the mountains',
            'parent_id' => $folder->id,
            'content_type_key' => $contentType->key,
            'meta' => ['seo_title' => 'Explore the mountains'],
            'categories' => ['Travel'],
            'tags' => ['mountains', 'summer'],
            'blocks' => [
                [
                    'component' => 'hero',
                    'fields' => ['heading' => 'Find your next summit'],
                    'media' => ['image' => $asset->id],
                ],
                [
                    'component' => 'rich-copy',
                    'fields' => ['body' => 'A complete first draft assembled from Pilot components.'],
                ],
            ],
        ])
        ->assertOk()
        ->assertSee(['Explore the mountains', 'draft', 'Review it in Pilot before publishing manually.']);

    $page = Content::query()->where('slug', 'explore-the-mountains')->sole();
    $hero = Block::query()->where('content_id', $page->id)->where('type', 'hero')->sole();

    expect($page->type)->toBe('page')
        ->and($page->status)->toBe('draft')
        ->and($page->workflow_status)->toBe('draft')
        ->and($page->published_at)->toBeNull()
        ->and($page->scheduled_for)->toBeNull()
        ->and($page->parent_id)->toBe($folder->id)
        ->and($page->content_type_id)->toBe($contentType->id)
        ->and($page->meta)->toMatchArray(['seo_title' => 'Explore the mountains'])
        ->and($page->categories)->toBe(['Travel'])
        ->and($page->tags)->toBe(['mountains', 'summer'])
        ->and($page->blocks()->count())->toBe(2)
        ->and($hero->data['heading'])->toBe(['en' => 'Find your next summit'])
        ->and($hero->data['image'])->toBe($asset->deliveryUrl())
        ->and($hero->data['image_focal_x'])->toBe(35.5)
        ->and((float) $hero->data['image_focal_y'])->toBe(62.0)
        ->and($page->revisions()->count())->toBe(1);

    $activity = Activity::query()->where('subject_type', Content::class)->where('subject_id', $page->id)->sole();

    expect($activity->action)->toBe('created draft via MCP')
        ->and($activity->meta)->toMatchArray(['source' => 'mcp', 'block_count' => 2]);
});

it('rejects publishing fields and leaves no partial page behind', function () {
    $space = Space::factory()->create();
    BlockType::factory()->create(['key' => 'hero', 'schema' => ['fields' => []]]);
    $author = User::factory()->create();
    $author->assignRole('Author');
    $author->withAccessToken($author->createToken('Test MCP', ['pilot-mcp'])->accessToken);

    PilotServer::actingAs($author)
        ->tool(CreatePageDraft::class, [
            'space_id' => $space->id,
            'name' => 'Do not publish',
            'publish' => true,
            'status' => 'published',
            'blocks' => [
                ['component' => 'hero'],
            ],
        ])
        ->assertHasErrors(['publish field is prohibited', 'status field is prohibited']);

    expect(Content::query()->where('name', 'Do not publish')->exists())->toBeFalse();
});

it('rejects components and media outside the selected page context', function () {
    $space = Space::factory()->create();
    $otherSpace = Space::factory()->create();
    ContentType::factory()->create([
        'key' => 'landing-page',
        'allowed_blocks' => ['hero'],
        'is_active' => true,
    ]);
    BlockType::factory()->create([
        'key' => 'hero',
        'schema' => ['fields' => [
            ['key' => 'image', 'type' => 'image', 'required' => true, 'default' => ''],
        ]],
    ]);
    BlockType::factory()->create(['key' => 'internal-only', 'schema' => ['fields' => []]]);
    $foreignAsset = Asset::factory()->create(['space_id' => $otherSpace->id]);
    $author = User::factory()->create();
    $author->assignRole('Author');
    $author->withAccessToken($author->createToken('Test MCP', ['pilot-mcp'])->accessToken);

    PilotServer::actingAs($author)
        ->tool(CreatePageDraft::class, [
            'space_id' => $space->id,
            'name' => 'Disallowed component',
            'content_type_key' => 'landing-page',
            'blocks' => [['component' => 'internal-only']],
        ])
        ->assertHasErrors(['not allowed by the selected content type']);

    PilotServer::actingAs($author)
        ->tool(CreatePageDraft::class, [
            'space_id' => $space->id,
            'name' => 'Foreign media',
            'content_type_key' => 'landing-page',
            'blocks' => [[
                'component' => 'hero',
                'media' => ['image' => $foreignAsset->id],
            ]],
        ])
        ->assertHasErrors(['does not exist in the page space']);

    expect(Content::query()->whereIn('name', ['Disallowed component', 'Foreign media'])->exists())->toBeFalse();
});

it('requires both a scoped token and normal Pilot permissions', function () {
    $space = Space::factory()->create();
    $viewer = User::factory()->create();
    $viewer->assignRole('Viewer');
    $viewer->withAccessToken($viewer->createToken('Wrong scope', ['something-else'])->accessToken);

    PilotServer::actingAs($viewer)
        ->tool(GetPageBuildingContext::class, ['space_id' => $space->id])
        ->assertHasErrors(['not authorized for Pilot MCP']);

    $viewer->withAccessToken($viewer->createToken('MCP', ['pilot-mcp'])->accessToken);

    PilotServer::actingAs($viewer)
        ->tool(CreatePageDraft::class, [
            'space_id' => $space->id,
            'name' => 'Unauthorized draft',
            'blocks' => [['component' => 'missing']],
        ])
        ->assertHasErrors(['needs the [create content] permission']);
});

it('authenticates the Streamable HTTP endpoint and exposes no publish tool', function () {
    $author = User::factory()->create();
    $author->assignRole('Author');
    $token = $author->createToken('HTTP MCP', ['pilot-mcp']);
    $wrongScope = $author->createToken('Other integration', ['something-else']);

    $this->postJson('/mcp/pilot', [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'initialize',
        'params' => [
            'protocolVersion' => '2025-06-18',
            'capabilities' => [],
            'clientInfo' => ['name' => 'Pilot test', 'version' => '1.0.0'],
        ],
    ])->assertUnauthorized();

    $this->withToken($token->plainTextToken)
        ->postJson('/mcp/pilot', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'initialize',
            'params' => [
                'protocolVersion' => '2025-06-18',
                'capabilities' => [],
                'clientInfo' => ['name' => 'Pilot test', 'version' => '1.0.0'],
            ],
        ])
        ->assertOk()
        ->assertJsonPath('result.serverInfo.name', 'Pilot CMS');

    $this->withToken($token->plainTextToken)
        ->postJson('/mcp/pilot', [
            'jsonrpc' => '2.0',
            'id' => 2,
            'method' => 'tools/list',
            'params' => [],
        ])
        ->assertOk()
        ->assertJsonPath('result.tools.0.name', 'get-page-building-context')
        ->assertJsonPath('result.tools.1.name', 'search-media')
        ->assertJsonPath('result.tools.2.name', 'create-page-draft');

    $this->app['auth']->forgetGuards();

    $this->withToken($wrongScope->plainTextToken)
        ->postJson('/mcp/pilot', [
            'jsonrpc' => '2.0',
            'id' => 3,
            'method' => 'initialize',
            'params' => [
                'protocolVersion' => '2025-06-18',
                'capabilities' => [],
                'clientInfo' => ['name' => 'Pilot test', 'version' => '1.0.0'],
            ],
        ])
        ->assertForbidden();

    $server = app()->make(PilotServer::class, ['transport' => new FakeTransporter]);
    $toolNames = $server->createContext()->tools()->map->name()->all();

    expect($toolNames)->toBe([
        'get-page-building-context',
        'search-media',
        'create-page-draft',
        'get-datasource-context',
        'add-datasource-entries',
    ])->and(collect($toolNames)->contains(fn (string $name): bool => str_contains($name, 'publish')))->toBeFalse();
});
