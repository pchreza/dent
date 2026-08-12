<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TreatmentStageDefinition extends Model
{
    use HasFactory;

    protected $fillable = ['tenant_id', 'code', 'name', 'category', 'sort_order', 'color', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function planItems(): HasMany
    {
        return $this->hasMany(TreatmentPlanItem::class, 'stage_id');
    }
}
