<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TreatmentCatalog extends Model
{
    use HasFactory;

    protected $table = 'treatment_catalog';

    protected $fillable = ['tenant_id', 'code', 'name', 'category', 'default_duration_minutes', 'default_price', 'color', 'is_active'];

    protected function casts(): array
    {
        return ['default_price' => 'decimal:2', 'is_active' => 'boolean'];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function planItems(): HasMany
    {
        return $this->hasMany(TreatmentPlanItem::class, 'treatment_id');
    }
}
