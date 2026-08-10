<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password',
        'role', 'subdepartemen_id', 'is_active',
        'must_change_password',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
        ];
    }

    public function subdepartemen(): BelongsTo
    {
        return $this->belongsTo(Subdepartemen::class);
    }

    public function dispensasis(): HasMany
    {
        return $this->hasMany(Dispensasi::class);
    }

    // Role helper — dipakai untuk cek akses di middleware/blade
    public function isPegawai(): bool
    {
        return $this->role === 'pegawai';
    }

    public function isManajerDepartemen(): bool
    {
        return $this->role === 'manajer_departemen';
    }

    public function isAsistenManajer(): bool
    {
        return $this->role === 'asisten_manajer';
    }

    public function isAdminSdm(): bool
    {
        return $this->role === 'admin_sdm';
    }

    public function dashboardRoute(): string
    {
        return match ($this->role) {
            'admin_sdm' => route('sdm.dashboard'),
            'manajer_departemen' => route('dashboard.manajer'),
            'asisten_manajer' => route('dashboard.asmen'),
            default => route('dashboard.pegawai'),
        };
    }
}