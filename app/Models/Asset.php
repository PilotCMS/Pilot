<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

class Asset extends Model
{
    use HasFactory;

    protected $fillable = [
        'space_id',
        'folder_id',
        'disk',
        'path',
        'filename',
        'display_name',
        'mime',
        'size',
        'width',
        'height',
        'focal_x',
        'focal_y',
        'alt',
        'title',
    ];

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(AssetTag::class, 'asset_asset_tag');
    }

    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'focal_x' => 'float',
            'focal_y' => 'float',
            'alt' => 'array',
            'title' => 'array',
        ];
    }

    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class);
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(AssetFolder::class, 'folder_id');
    }

    public function url(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }

    public function relativeUrl(): string
    {
        return static::toRelativeUrl($this->url());
    }

    public function focalX(): float
    {
        return $this->focal_x ?? 50.0;
    }

    public function focalY(): float
    {
        return $this->focal_y ?? 50.0;
    }

    public static function toRelativeUrl(string $url): string
    {
        if (! str_starts_with($url, 'http://') && ! str_starts_with($url, 'https://')) {
            return $url;
        }

        $parts = parse_url($url);
        $path = $parts['path'] ?? '/';
        $query = isset($parts['query']) ? '?'.$parts['query'] : '';
        $fragment = isset($parts['fragment']) ? '#'.$parts['fragment'] : '';

        return $path.$query.$fragment;
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime ?? '', 'image/');
    }

    public function isVideo(): bool
    {
        return str_starts_with($this->mime ?? '', 'video/');
    }

    public function isDocument(): bool
    {
        $docMimes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain',
            'text/csv',
        ];

        return in_array($this->mime ?? '', $docMimes) || str_starts_with($this->mime ?? '', 'application/');
    }

    public function displayName(): string
    {
        return $this->display_name ?? $this->filename;
    }

    public function fullUrl(): string
    {
        $url = Storage::disk($this->disk)->url($this->path);

        return str_starts_with($url, 'http') ? $url : url($url);
    }

    public function getAltAttribute($value): ?string
    {
        if (is_array($value)) {
            return $value['en'] ?? array_values($value)[0] ?? null;
        }

        return $value;
    }

    public function getTitleAttribute($value): ?string
    {
        if (is_array($value)) {
            return $value['en'] ?? array_values($value)[0] ?? null;
        }

        return $value;
    }
}
