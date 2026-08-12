<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = ['tenant_id', 'invoice_id', 'treatment_id', 'description', 'quantity', 'unit_price', 'total'];

    protected function casts(): array
    {
        return ['unit_price' => 'decimal:2', 'total' => 'decimal:2'];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function treatment(): BelongsTo
    {
        return $this->belongsTo(TreatmentCatalog::class, 'treatment_id');
    }
}
