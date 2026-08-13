<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

class FileAsset extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'owner_type',
        'owner_id',
        'category',
        'disk',
        'path',
        'mime_type',
        'size',
        'metadata_json',
        'uploaded_by',
        'deleted_at',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'metadata_json' => 'array',
            'deleted_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    public function isArchived(): bool
    {
        return $this->deleted_at instanceof Carbon;
    }

    public function displayName(): string
    {
        return (string) ($this->metadata_json['original_name'] ?? 'فایل پزشکی');
    }

    public function title(): string
    {
        return (string) ($this->metadata_json['title'] ?? $this->displayName());
    }

    public function sizeInKilobytes(): string
    {
        return number_format($this->size / 1024, 1, '.', '').' KB';
    }
}
