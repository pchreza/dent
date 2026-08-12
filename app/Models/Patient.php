<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Patient extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id', 'patient_no', 'first_name', 'last_name', 'national_id', 'birth_date',
        'gender', 'mobile', 'phone', 'address', 'insurance_name', 'emergency_contact',
        'custom_fields', 'status', 'verified_at', 'verified_by', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'emergency_contact' => 'array',
            'custom_fields' => 'array',
            'verified_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function conditions(): HasMany
    {
        return $this->hasMany(PatientMedicalCondition::class);
    }

    public function allergies(): HasMany
    {
        return $this->hasMany(PatientAllergy::class);
    }

    public function medications(): HasMany
    {
        return $this->hasMany(PatientMedication::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(PatientNote::class);
    }

    public function qrRequests(): HasMany
    {
        return $this->hasMany(QrRegistrationRequest::class);
    }

    public function clinicalFieldValues(): HasMany
    {
        return $this->hasMany(PatientClinicalFieldValue::class);
    }

    public function dentalChartEntries(): HasMany
    {
        return $this->hasMany(DentalChartEntry::class);
    }

    public function treatmentPlans(): HasMany
    {
        return $this->hasMany(TreatmentPlan::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function fullName(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function hasCriticalAllergy(): bool
    {
        return $this->allergies()->where('is_critical', true)->exists();
    }
}
