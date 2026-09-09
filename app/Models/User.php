<?php

namespace App\Models;

use App\Enums\RoleCode;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['role_id', 'school_id', 'name', 'email', 'password', 'preferred_locale', 'is_active', 'last_login_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function student(): HasOne
    {
        return $this->hasOne(Student::class);
    }

    public function mentor(): HasOne
    {
        return $this->hasOne(Mentor::class);
    }

    public function hasRole(RoleCode ...$roles): bool
    {
        $roleCode = $this->role?->code;

        return $roleCode !== null && in_array($roleCode, $roles, true);
    }

    public function canAccessPortal(): bool
    {
        if (! $this->is_active || $this->role === null) {
            return false;
        }

        if ($this->hasRole(RoleCode::SuperAdmin)) {
            return true;
        }

        if ($this->school === null || ! $this->school->is_active) {
            return false;
        }

        return match ($this->role->code) {
            RoleCode::Student => $this->student?->school_id === $this->school_id,
            RoleCode::Mentor => $this->mentor?->school_id === $this->school_id,
            RoleCode::SchoolAdmin => true,
            default => false,
        };
    }
}
