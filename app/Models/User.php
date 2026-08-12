<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'mobile',
        'username',
        'email',
        'password',
        'status',
        'is_system_admin',
        'must_change_password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_system_admin' => 'boolean',
            'must_change_password' => 'boolean',
        ];
    }

    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, 'tenant_user')
            ->withPivot(['role_id', 'branch_id', 'scope', 'status'])
            ->withTimestamps();
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'tenant_user', 'user_id', 'role_id')
            ->withPivot(['tenant_id', 'branch_id', 'scope', 'status'])
            ->withTimestamps();
    }

    public function patientAccounts(): HasMany
    {
        return $this->hasMany(PatientAccount::class);
    }

    public function auditEvents(): HasMany
    {
        return $this->hasMany(AuditEvent::class, 'actor_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'recipient_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isSystemAdmin(): bool
    {
        return $this->is_system_admin === true;
    }
}
