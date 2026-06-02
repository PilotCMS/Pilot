<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Block extends Model
{
    use HasFactory;

    protected $fillable = [
        'content_id',
        'parent_block_id',
        'type',
        'position',
        'data',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'data' => 'array',
        ];
    }

    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class);
    }

    public function parentBlock(): BelongsTo
    {
        return $this->belongsTo(Block::class, 'parent_block_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Block::class, 'parent_block_id')->orderBy('position');
    }

    public function blockType(): BelongsTo
    {
        return $this->belongsTo(BlockType::class, 'type', 'key');
    }
}
