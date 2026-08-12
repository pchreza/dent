<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TreatmentPlanItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id', 'treatment_plan_id', 'stage_id', 'treatment_id', 'tooth_code', 'status', 'priority', 'estimated_cost', 'planned_on', 'completed_at', 'notes', 'sort_order',
    ];

    protected function casts(): array
    {
        return ['estimated_cost' => 'decimal:2', 'planned_on' => 'date', 'completed_at' => 'datetime'];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(TreatmentPlan::class, 'treatment_plan_id');
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(TreatmentStageDefinition::class, 'stage_id');
    }

    public function treatment(): BelongsTo
    {
        return $this->belongsTo(TreatmentCatalog::class, 'treatment_id');
    }
}
