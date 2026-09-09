<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Pilot\Core\Database\Seeders\RoleSeeder;
use Pilot\Core\Livewire\Admin\Datasources\Index as DatasourceIndex;
use Pilot\Core\Models\Block;
use Pilot\Core\Models\BlockType;
use Pilot\Core\Models\Content;
use Pilot\Core\Models\ContentReference;
use Pilot\Core\Models\ContentType;
use Pilot\Core\Models\Datasource;
use Pilot\Core\Models\DatasourceEntry;
use Pilot\Core\Models\Space;
use Pilot\Core\Support\Cms\SpaceTransfer;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('round trips datasources between spaces and updates matching slugs', function () {
    $source = Space::factory()->create();
    $target = Space::factory()->create();
    $datasource = Datasource::factory()->create([
        'space_id' => $source->id,
        'name' => 'CTA Styles',
        'slug' => 'cta-styles',
    ]);
    DatasourceEntry::factory()->create([
        'datasource_id' => $datasource->id,
        'key' => 'primary',
        'value' => ['en' => 'Primary', 'fr' => 'Principal'],
        'order' => 0,
    ]);
    $existing = Datasource::factory()->create([
        'space_id' => $target->id,
        'name' => 'Old styles',
        'slug' => 'cta-styles',
    ]);
    DatasourceEntry::factory()->create([
        'datasource_id' => $existing->id,
        'key' => 'old',
        'order' => 0,
    ]);

    $transfer = app(SpaceTransfer::class);
    $result = $transfer->importDatasources($target, $transfer->exportDatasources($source));

    expect($result)->toBe(['datasources' => 1, 'entries' => 1])
        ->and($existing->fresh()->name)->toBe('CTA Styles')
        ->and($existing->entries()->count())->toBe(1)
        ->and($existing->entries()->first()->key)->toBe('primary')
        ->and($existing->entries()->first()->value['fr'])->toBe('Principal');
});

it('round trips content hierarchy, blocks, and internal references', function () {
    $user = User::factory()->create();
    $source = Space::factory()->create();
    $target = Space::factory()->create();
    $contentType = ContentType::factory()->create(['key' => 'standard-page']);
    BlockType::factory()->create([
        'key' => 'related-card',
        'schema' => ['fields' => [['key' => 'related_entry', 'type' => 'reference']]],
    ]);
    $folder = Content::factory()->create([
        'space_id' => $source->id,
        'type' => 'folder',
        'slug' => 'guides',
        'created_by' => $user->id,
    ]);
    $targetPage = Content::factory()->create([
        'space_id' => $source->id,
        'parent_id' => $folder->id,
        'content_type_id' => $contentType->id,
        'slug' => 'target',
        'created_by' => $user->id,
    ]);
    $sourcePage = Content::factory()->create([
        'space_id' => $source->id,
        'parent_id' => $folder->id,
        'content_type_id' => $contentType->id,
        'slug' => 'source',
        'created_by' => $user->id,
    ]);
    Block::factory()->create([
        'content_id' => $sourcePage->id,
        'type' => 'related-card',
        'data' => ['related_entry' => $targetPage->id, 'heading' => 'Read next'],
    ]);

    $transfer = app(SpaceTransfer::class);
    $result = $transfer->importContent($target, $transfer->exportContent($source), $user->id);
    $transfer->importContent($target, $transfer->exportContent($source), $user->id);

    $importedFolder = Content::where('space_id', $target->id)->where('slug', 'guides')->firstOrFail();
    $importedSource = Content::where('space_id', $target->id)->where('slug', 'source')->firstOrFail();
    $importedTarget = Content::where('space_id', $target->id)->where('slug', 'target')->firstOrFail();
    $importedBlock = $importedSource->allBlocks()->firstOrFail();

    expect($result)->toBe(['contents' => 3, 'blocks' => 1])
        ->and($importedSource->parent_id)->toBe($importedFolder->id)
        ->and($importedBlock->data['related_entry'])->toBe($importedTarget->id)
        ->and(Content::where('space_id', $target->id)->count())->toBe(3)
        ->and(Block::whereIn('content_id', Content::where('space_id', $target->id)->pluck('id'))->count())->toBe(1)
        ->and(ContentReference::where('content_id', $importedSource->id)
            ->where('target_content_id', $importedTarget->id)->exists())->toBeTrue();
});

it('rejects the wrong transfer resource without changing data', function () {
    $space = Space::factory()->create();
    $transfer = app(SpaceTransfer::class);

    expect(fn () => $transfer->importContent($space, $transfer->exportDatasources($space)))
        ->toThrow(InvalidArgumentException::class, 'Choose a Pilot content export file.');

    expect(Content::where('space_id', $space->id)->count())->toBe(0);
});

it('imports a datasource export through the admin upload', function () {
    $editor = User::factory()->create();
    $editor->assignRole('Editor');
    $space = Space::factory()->create();
    $document = [
        'schema' => SpaceTransfer::SCHEMA,
        'version' => SpaceTransfer::VERSION,
        'resource' => 'datasources',
        'data' => [
            'datasources' => [[
                'name' => 'Statuses',
                'slug' => 'statuses',
                'entries' => [['key' => 'live', 'value' => ['en' => 'Live'], 'order' => 0]],
            ]],
        ],
    ];

    Livewire::actingAs($editor)
        ->test(DatasourceIndex::class)
        ->set('spaceId', $space->id)
        ->set('importFile', UploadedFile::fake()->createWithContent('datasources.json', json_encode($document)))
        ->call('importDatasources')
        ->assertHasNoErrors()
        ->assertSet('showImportModal', false);

    expect(Datasource::where('space_id', $space->id)->where('slug', 'statuses')->exists())->toBeTrue();

    Livewire::actingAs($editor)
        ->test(DatasourceIndex::class)
        ->set('spaceId', $space->id)
        ->call('exportDatasources')
        ->assertFileDownloaded("pilot-{$space->slug}-datasources-".now()->format('Y-m-d').'.json');
});
