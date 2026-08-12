<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'status',
        'plan_code',
        'starts_on',
        'ends_on',
        'branding',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'branding' => 'array',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tenant_user')
            ->withPivot(['role_id', 'branch_id', 'scope', 'status'])
            ->withTimestamps();
    }

    public function roles(): HasMany
    {
        return $this->hasMany(Role::class);
    }

    public function auditEvents(): HasMany
    {
        return $this->hasMany(AuditEvent::class);
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function practitioners(): HasMany
    {
        return $this->hasMany(Practitioner::class);
    }

    public function staff(): HasMany
    {
        return $this->hasMany(ClinicStaff::class);
    }

    public function settings(): HasMany
    {
        return $this->hasMany(ClinicSetting::class);
    }

    public function patients(): HasMany
    {
        return $this->hasMany(Patient::class);
    }

    public function qrRegistrationRequests(): HasMany
    {
        return $this->hasMany(QrRegistrationRequest::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function treatmentStages(): HasMany
    {
        return $this->hasMany(TreatmentStageDefinition::class);
    }

    public function treatmentPlans(): HasMany
    {
        return $this->hasMany(TreatmentPlan::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function hasQrToken(string $token): bool
    {
        return $this->qr_token_hash !== null
            && hash_equals($this->qr_token_hash, hash('sha256', $token));
    }

    public function qrRegistrationUrl(): string
    {
        if ($this->qr_token_encrypted === null) {
            return route('public.registration', ['tenantCode' => $this->code]);
        }

        return route('public.registration', ['tenantCode' => $this->code])
            .'?token='.urlencode(Crypt::decryptString($this->qr_token_encrypted));
    }
}
