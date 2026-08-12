<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QrRegistrationRequest extends Model
{
    use HasFactory;

    protected $table = 'qr_registration_requests';

    protected $fillable = [
        'tenant_id', 'token_hash', 'payload', 'duplicate_match', 'status', 'reviewed_by', 'reviewed_at', 'review_reason',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'duplicate_match' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
