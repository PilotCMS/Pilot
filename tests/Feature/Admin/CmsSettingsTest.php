<?php

use App\Http\Controllers\Api\PreviewController;
use App\Livewire\Admin\Settings\Index;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Pilot\Core\Database\Seeders\RoleSeeder;
use Pilot\Core\Models\Block;
use Pilot\Core\Models\CmsSetting;
use Pilot\Core\Models\Content;
use Pilot\Core\Models\Space;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('allows admins to view the cms settings area', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    $this->actingAs($admin)
        ->get(route('admin.settings.index'))
        ->assertOk()
        ->assertSee('CMS Settings')
        ->assertSee('Public website')
        ->assertSee('preview');
});

it('prevents non admins from viewing the cms settings area', function () {
    $editor = User::factory()->create();
    $editor->assignRole('Editor');

    $this->actingAs($editor)
        ->get(route('admin.settings.index'))
        ->assertForbidden();
});

it('saves cms settings from the admin screen', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    Space::create([
        'name' => 'Website',
        'slug' => 'website',
    ]);

    $this->actingAs($admin);

    Livewire::test(Index::class)
        ->set('defaultSpace', 'website')
        ->set('homeSlug', 'homepage')
        ->set('defaultLocale', 'es')
        ->set('draftApiEnabled', false)
        ->set('previewLinksEnabled', false)
        ->set('previewExpirationMinutes', 120)
        ->call('save')
        ->assertHasNoErrors();

    expect(CmsSetting::get('default_space'))->toBe('website');
    expect(CmsSetting::get('home_slug'))->toBe('homepage');
    expect(CmsSetting::get('default_locale'))->toBe('es');
    expect(CmsSetting::get('draft_api_enabled'))->toBeFalse();
    expect(CmsSetting::get('preview_links_enabled'))->toBeFalse();
    expect(CmsSetting::get('preview_expiration_minutes'))->toBe(120);
});

it('uses saved public rendering settings for the home route', function () {
    $user = User::factory()->create();
    $unusedSpace = Space::create([
        'name' => 'Unused',
        'slug' => 'unused',
    ]);
    $website = Space::create([
        'name' => 'Website',
        'slug' => 'website',
    ]);

    Content::create([
        'space_id' => $unusedSpace->id,
        'type' => 'page',
        'slug' => 'home',
        'name' => 'Unused Home',
        'status' => 'published',
        'published_at' => now(),
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $home = Content::create([
        'space_id' => $website->id,
        'type' => 'page',
        'slug' => 'homepage',
        'name' => 'Website Home',
        'status' => 'published',
        'published_at' => now(),
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    Block::create([
        'content_id' => $home->id,
        'type' => 'hero',
        'position' => 0,
        'data' => [
            'title' => 'Configured Home',
        ],
    ]);

    CmsSetting::setMany([
        'default_space' => 'website',
        'home_slug' => 'homepage',
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Configured Home')
        ->assertDontSee('Unused Home');
});

it('can disable draft api responses', function () {
    $user = User::factory()->create();
    $space = Space::create([
        'name' => 'Website',
        'slug' => 'website',
    ]);

    Content::create([
        'space_id' => $space->id,
        'type' => 'page',
        'slug' => 'draft-page',
        'name' => 'Draft Page',
        'status' => 'draft',
        'published_at' => null,
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    CmsSetting::set('draft_api_enabled', false);
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/spaces/website/contents/draft-page?version=draft')
        ->assertForbidden()
        ->assertJson(['error' => 'Draft API access is disabled']);
});

it('can disable signed preview links', function () {
    $user = User::factory()->create();
    $space = Space::create([
        'name' => 'Website',
        'slug' => 'website',
    ]);

    $content = Content::create([
        'space_id' => $space->id,
        'type' => 'page',
        'slug' => 'draft-page',
        'name' => 'Draft Page',
        'status' => 'draft',
        'published_at' => null,
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    CmsSetting::set('preview_links_enabled', false);

    $this->getJson(PreviewController::signedUrl($content))
        ->assertForbidden()
        ->assertJson(['error' => 'Preview links are disabled']);
});

it('resets cms settings back to environment defaults', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    CmsSetting::setMany([
        'default_space' => 'website',
        'home_slug' => 'homepage',
        'default_locale' => 'es',
        'draft_api_enabled' => false,
        'preview_links_enabled' => false,
        'preview_expiration_minutes' => 120,
    ]);

    $this->actingAs($admin);

    Livewire::test(Index::class)
        ->call('resetToEnvironmentDefaults')
        ->assertSet('homeSlug', config('cms.home_slug', 'home'))
        ->assertSet('defaultLocale', 'en')
        ->assertSet('draftApiEnabled', true)
        ->assertSet('previewLinksEnabled', true)
        ->assertSet('previewExpirationMinutes', 60);

    expect(CmsSetting::query()->count())->toBe(0);
});
