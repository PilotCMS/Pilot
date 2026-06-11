<?php

namespace App\Support\Cms;

use App\Models\Block;
use App\Models\Content;

class ContentSyncFingerprint
{
    public static function make(Content $content): string
    {
        $blocks = $content->allBlocks()
            ->get(['id', 'parent_block_id', 'type', 'position', 'data', 'updated_at'])
            ->map(fn (Block $block): array => [
                'id' => $block->id,
                'parent_block_id' => $block->parent_block_id,
                'type' => $block->type,
                'position' => $block->position,
                'data' => $block->data ?? [],
                'updated_at' => $block->updated_at?->toJSON(),
            ])
            ->values()
            ->all();

        return hash('sha256', json_encode([
            'content' => [
                'id' => $content->id,
                'name' => $content->name,
                'slug' => $content->slug,
                'status' => $content->status,
                'workflow_status' => $content->workflow_status,
                'meta' => $content->meta ?? [],
                'updated_at' => $content->updated_at?->toJSON(),
            ],
            'blocks' => $blocks,
        ]) ?: '');
    }
}
