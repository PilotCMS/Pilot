<?php

use App\Livewire\Admin\Assets\Index as AssetIndex;
use App\Livewire\Admin\Blocks\Index as BlockIndex;
use App\Livewire\Admin\Content\Index as ContentIndex;
use App\Livewire\Admin\Datasources\Index as DatasourceIndex;
use App\Livewire\Admin\Users\Index as UserIndex;
use App\Models\Asset;
use App\Models\AssetFolder;
use App\Models\AssetTag;
use App\Models\BlockType;
use App\Models\BlockTypeFolder;
use App\Models\Content;
use App\Models\Datasource;
use App\Models\DatasourceEntry;
use App\Models\Space;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    $this->actingAs($admin);
});

it('filters the rendered content rows by search type folder and sort', function (): void {
    $space = Space::factory()->create(['name' => 'Website', 'slug' => 'website']);
    $folder = Content::factory()->create([
        'space_id' => $space->id,
        'name' => 'Landing Folder',
        'slug' => 'landing-folder',
        'type' => 'folder',
    ]);
    $nestedPage = Content::factory()->create([
        'space_id' => $space->id,
        'parent_id' => $folder->id,
        'name' => 'Nested Landing Page',
        'slug' => 'nested-landing-page',
        'type' => 'page',
    ]);
    $otherPage = Content::factory()->create([
        'space_id' => $space->id,
        'name' => 'Blog Article',
        'slug' => 'blog-article',
        'type' => 'page',
    ]);

    Livewire::test(ContentIndex::class)
        ->set('search', 'landing')
        ->assertSee('Landing Folder')
        ->assertSee('Nested Landing Page')
        ->assertDontSee('Blog Article')
        ->set('typeFilter', 'folder')
        ->assertSee('Landing Folder')
        ->assertDontSee('Nested Landing Page')
        ->set('search', '')
        ->set('typeFilter', 'all')
        ->call('selectFolder', $folder->id)
        ->assertSee('Nested Landing Page')
        ->assertDontSee('Blog Article')
        ->set('sortBy', 'name')
        ->set('sortDir', 'desc')
        ->set('search', 'landing')
        ->assertSeeInOrder([$nestedPage->name, $folder->name]);

    expect($otherPage->exists)->toBeTrue();
});

it('filters block types by search and folder', function (): void {
    $marketingFolder = BlockTypeFolder::create(['name' => 'Marketing']);
    $systemFolder = BlockTypeFolder::create(['name' => 'System']);

    BlockType::factory()->create([
        'folder_id' => $marketingFolder->id,
        'name' => 'Hero Banner',
        'key' => 'hero-banner',
    ]);
    BlockType::factory()->create([
        'folder_id' => $systemFolder->id,
        'name' => 'Footer Links',
        'key' => 'footer-links',
    ]);
    BlockType::factory()->create([
        'folder_id' => null,
        'name' => 'Loose Promo',
        'key' => 'loose-promo',
    ]);

    Livewire::test(BlockIndex::class)
        ->set('search', 'hero')
        ->assertSee('Hero Banner')
        ->assertDontSee('Footer Links')
        ->set('search', '')
        ->call('setFolderFilter', (string) $marketingFolder->id)
        ->assertSee('Hero Banner')
        ->assertDontSee('Footer Links')
        ->call('setFolderFilter', 'none')
        ->assertSee('Loose Promo')
        ->assertDontSee('Hero Banner');
});

it('filters assets by folder search tag and type', function (): void {
    $space = Space::factory()->create(['name' => 'Website', 'slug' => 'website']);
    $campaignFolder = AssetFolder::create([
        'space_id' => $space->id,
        'name' => 'Campaign',
    ]);
    $archiveFolder = AssetFolder::create([
        'space_id' => $space->id,
        'name' => 'Archive',
    ]);

    $heroAsset = Asset::factory()->create([
        'space_id' => $space->id,
        'folder_id' => $campaignFolder->id,
        'filename' => 'spring-hero.jpg',
        'display_name' => 'Spring Hero',
        'mime' => 'image/jpeg',
    ]);
    $briefAsset = Asset::factory()->create([
        'space_id' => $space->id,
        'folder_id' => $campaignFolder->id,
        'filename' => 'campaign-brief.pdf',
        'display_name' => 'Campaign Brief',
        'mime' => 'application/pdf',
    ]);
    $archivedAsset = Asset::factory()->create([
        'space_id' => $space->id,
        'folder_id' => $archiveFolder->id,
        'filename' => 'old-video.mp4',
        'display_name' => 'Old Video',
        'mime' => 'video/mp4',
    ]);

    $tag = AssetTag::create([
        'space_id' => $space->id,
        'name' => 'Launch',
        'slug' => 'launch',
    ]);
    $heroAsset->tags()->attach($tag);

    Livewire::test(AssetIndex::class)
        ->call('selectFolder', $campaignFolder->id)
        ->assertSee('Spring Hero')
        ->assertSee('Campaign Brief')
        ->assertDontSee('Old Video')
        ->set('search', 'launch')
        ->assertSee('Spring Hero')
        ->assertDontSee('Campaign Brief')
        ->set('search', '')
        ->set('typeFilter', 'documents')
        ->assertSee('Campaign Brief')
        ->assertDontSee('Spring Hero');

    expect($briefAsset->exists)->toBeTrue()
        ->and($archivedAsset->exists)->toBeTrue();
});

it('filters datasources by space and entry content search', function (): void {
    $website = Space::factory()->create(['name' => 'Website', 'slug' => 'website']);
    $app = Space::factory()->create(['name' => 'App', 'slug' => 'app']);

    $colors = Datasource::create([
        'space_id' => $website->id,
        'name' => 'Colors',
        'slug' => 'colors',
    ]);
    DatasourceEntry::create([
        'datasource_id' => $colors->id,
        'key' => 'teal',
        'value' => ['en' => 'Brand Teal'],
        'order' => 0,
    ]);

    Datasource::create([
        'space_id' => $app->id,
        'name' => 'Shapes',
        'slug' => 'shapes',
    ]);

    Livewire::test(DatasourceIndex::class)
        ->set('spaceId', $website->id)
        ->assertSee('Colors')
        ->assertDontSee('Shapes')
        ->set('search', 'Brand Teal')
        ->assertSee('Colors')
        ->set('search', '')
        ->set('spaceId', $app->id)
        ->assertSee('Shapes')
        ->assertDontSee('Colors');
});

it('filters users by name email and role', function (): void {
    $editor = User::factory()->create([
        'name' => 'Morgan Editor',
        'email' => 'morgan.editor@example.com',
    ]);
    $editor->assignRole('Editor');

    $viewer = User::factory()->create([
        'name' => 'Casey Viewer',
        'email' => 'casey.viewer@example.com',
    ]);
    $viewer->assignRole('Viewer');

    Livewire::test(UserIndex::class)
        ->set('search', 'morgan.editor@example.com')
        ->assertSee('Morgan Editor')
        ->assertDontSee('Casey Viewer')
        ->set('search', 'Viewer')
        ->assertSee('Casey Viewer')
        ->assertDontSee('Morgan Editor');
});
