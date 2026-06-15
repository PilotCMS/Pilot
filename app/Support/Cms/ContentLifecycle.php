<?php

namespace App\Support\Cms;

use App\Models\Block;
use App\Models\BlockType;
use App\Models\Content;
use App\Models\ContentReference;
use App\Models\ContentRevision;
use App\Models\Redirect;
use Illuminate\Support\Arr;

class ContentLifecycle
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function updateContent(Content $content, array $attributes, ?int $userId = null): void
    {
        $oldSlug = $content->slug;

        if ($userId !== null) {
            $attributes['updated_by'] = $userId;
        }

        $content->update($attributes);

        if (array_key_exists('slug', $attributes) && $oldSlug !== $content->slug) {
            $this->createRedirectForSlugChange($content, $oldSlug);
        }
    }

    public function publish(Content $content, ?int $userId = null): ContentRevision
    {
        $revision = $this->createRevision($content, 'Published', $userId);

        $content->update([
            'status' => 'published',
            'workflow_status' => 'published',
            'published_at' => now(),
            'scheduled_for' => null,
            'review_requested_at' => null,
            'review_requested_by' => null,
            'reviewer_id' => null,
            'review_due_at' => null,
            'review_note' => null,
            'published_revision_id' => $revision->id,
            'updated_by' => $userId,
        ]);

        return $revision;
    }

    public function requestReview(Content $content, ?int $userId = null): void
    {
        $content->update([
            'workflow_status' => 'in_review',
            'review_requested_at' => now(),
            'review_requested_by' => $userId,
            'updated_by' => $userId,
        ]);
    }

    public function assignReview(Content $content, ?int $reviewerId, ?string $dueAt, ?string $note, ?int $userId = null): void
    {
        $content->update([
            'workflow_status' => 'in_review',
            'review_requested_at' => now(),
            'review_requested_by' => $userId,
            'reviewer_id' => $reviewerId,
            'review_due_at' => $dueAt,
            'review_note' => $note,
            'updated_by' => $userId,
        ]);
    }

    public function approveReview(Content $content, ?int $userId = null): void
    {
        $content->update([
            'workflow_status' => 'approved',
            'updated_by' => $userId,
        ]);
    }

    public function requestChanges(Content $content, ?string $note = null, ?int $userId = null): void
    {
        $content->update([
            'workflow_status' => 'changes_requested',
            'review_note' => $note ?: $content->review_note,
            'updated_by' => $userId,
        ]);
    }

    public function schedule(Content $content, string $scheduledFor, ?int $userId = null): void
    {
        $content->update([
            'status' => 'draft',
            'workflow_status' => 'scheduled',
            'scheduled_for' => $scheduledFor,
            'updated_by' => $userId,
        ]);
    }

    public function unpublish(Content $content, ?int $userId = null): void
    {
        $content->update([
            'status' => 'draft',
            'workflow_status' => 'draft',
            'published_at' => null,
            'scheduled_for' => null,
            'updated_by' => $userId,
        ]);
    }

    public function createRevision(Content $content, ?string $label = null, ?int $userId = null): ContentRevision
    {
        return ContentRevision::create([
            'content_id' => $content->id,
            'user_id' => $userId,
            'snapshot' => $this->snapshot($content),
            'label' => $label,
        ]);
    }

    public function restoreRevision(Content $content, ContentRevision $revision, ?int $userId = null): void
    {
        $snapshot = $revision->snapshot;

        if (isset($snapshot['content'])) {
            $this->updateContent($content, array_merge($snapshot['content'], [
                'updated_by' => $userId,
            ]), $userId);
        }

        if (isset($snapshot['blocks'])) {
            Block::where('content_id', $content->id)->delete();

            foreach ($snapshot['blocks'] as $index => $blockSnapshot) {
                $this->restoreSnapshotBlock($content, $blockSnapshot, null, $index);
            }
        }

        $this->syncReferences($content);
    }

    public function syncReferences(Content $content): void
    {
        ContentReference::where('content_id', $content->id)->delete();

        $content->allBlocks()->get()->each(function (Block $block) use ($content): void {
            $blockType = BlockType::query()->where('key', $block->type)->first();

            foreach ($this->extractReferenceValues($block->data ?? [], $blockType?->schema['fields'] ?? []) as $fieldKey => $targetIds) {
                foreach ($targetIds as $targetId) {
                    if ((int) $targetId === $content->id) {
                        continue;
                    }

                    ContentReference::firstOrCreate([
                        'content_id' => $content->id,
                        'target_content_id' => (int) $targetId,
                        'block_id' => $block->id,
                        'field_key' => $fieldKey,
                    ]);
                }
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function snapshot(Content $content): array
    {
        $blocks = $content->blocks()
            ->with('children')
            ->orderBy('position')
            ->get();

        return [
            'content' => [
                'name' => $content->name,
                'slug' => $content->slug,
                'status' => $content->status,
                'workflow_status' => $content->workflow_status,
                'content_type_id' => $content->content_type_id,
                'scheduled_for' => $content->scheduled_for?->toDateTimeString(),
                'meta' => $content->meta,
                'categories' => $content->categories,
                'tags' => $content->tags,
            ],
            'blocks' => $blocks->map(fn (Block $block): array => $this->snapshotBlock($block))->toArray(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function snapshotBlock(Block $block): array
    {
        return [
            'type' => $block->type,
            'position' => $block->position,
            'data' => $block->data ?? [],
            'children' => $block->children
                ->sortBy('position')
                ->map(fn (Block $child): array => $this->snapshotBlock($child))
                ->values()
                ->toArray(),
        ];
    }

    /**
     * @param  array<string, mixed>  $blockSnapshot
     */
    protected function restoreSnapshotBlock(Content $content, array $blockSnapshot, ?int $parentBlockId, int $fallbackPosition): Block
    {
        $block = Block::create([
            'content_id' => $content->id,
            'type' => $blockSnapshot['type'],
            'position' => $blockSnapshot['position'] ?? $fallbackPosition,
            'data' => $blockSnapshot['data'] ?? [],
            'parent_block_id' => $parentBlockId,
        ]);

        foreach ($blockSnapshot['children'] ?? [] as $index => $childSnapshot) {
            $this->restoreSnapshotBlock($content, $childSnapshot, $block->id, $index);
        }

        return $block;
    }

    protected function createRedirectForSlugChange(Content $content, string $oldSlug): void
    {
        if ($oldSlug === '') {
            return;
        }

        Redirect::updateOrCreate(
            [
                'space_id' => $content->space_id,
                'source' => '/'.trim($oldSlug, '/'),
            ],
            [
                'content_id' => $content->id,
                'destination' => '/'.trim($content->slug, '/'),
                'status_code' => 301,
                'is_active' => true,
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, array<string, mixed>>  $fields
     * @return array<string, array<int|string>>
     */
    protected function extractReferenceValues(array $data, array $fields = []): array
    {
        $references = [];

        foreach ($fields as $field) {
            if (($field['type'] ?? '') !== 'reference') {
                continue;
            }

            $fieldKey = (string) ($field['key'] ?? '');

            if ($fieldKey !== '' && array_key_exists($fieldKey, $data)) {
                $references[$fieldKey] = array_filter(Arr::wrap($data[$fieldKey]));
            }
        }

        foreach ($data as $key => $value) {
            if (str_ends_with((string) $key, '_content_id')) {
                $references[$key] = array_filter(Arr::wrap($value));
            }

            if (str_ends_with((string) $key, '_content_ids') && is_array($value)) {
                $references[$key] = array_filter($value);
            }
        }

        return $references;
    }
}
