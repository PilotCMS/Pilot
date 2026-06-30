<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentRevision extends Model
{
    protected $fillable = [
        'content_id',
        'user_id',
        'snapshot',
        'label',
        'revision_type',
        'source_revision_id',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'snapshot' => 'array',
            'meta' => 'array',
        ];
    }

    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sourceRevision(): BelongsTo
    {
        return $this->belongsTo(ContentRevision::class, 'source_revision_id');
    }
}
