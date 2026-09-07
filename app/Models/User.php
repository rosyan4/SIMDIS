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
        'name',
        'email',
        'password',
        'role',
        'departemen_id',
        'subdepartemen_id',
        'is_active',
        'must_change_password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'    => 'datetime',
            'password'             => 'hashed',
            'is_active'            => 'boolean',
            'must_change_password' => 'boolean',
        ];
    }

    public function departemen(): BelongsTo
    {
        return $this->belongsTo(Departemen::class);
    }

    public function subdepartemen(): BelongsTo
    {
        return $this->belongsTo(Subdepartemen::class);
    }

    public function dispensasiDiinput(): HasMany
    {
        return $this->hasMany(Dispensasi::class, 'admin_departemen_id');
    }

    public function dispensasiDiproses(): HasMany
    {
        return $this->hasMany(Dispensasi::class, 'diproses_oleh_id');
    }

    public function dashboardRoute(): string
    {
        $routeName = match ($this->role) {
            'admin_sdm'                       => 'sdm.dashboard',
            'admin_departemen'                => 'dispensasi.index',
            'manajer_departemen'              => 'dashboard.manajer',
            'senior_manajer_sekper'           => 'dashboard.senior-manajer-sekper',
            'kepala_spi'                      => 'dashboard.kepala-spi',
            'direktur_teknik'                 => 'dashboard.direktur-teknik',
            'direktur_administrasi_keuangan'  => 'dashboard.direktur-administrasi-keuangan',
            'direktur_utama'                  => 'dashboard.direktur-utama',
            default                           => null,
        };

        if ($routeName && \Illuminate\Support\Facades\Route::has($routeName)) {
            return route($routeName);
        }

        return '/';
    }

    public function isAdminSdm(): bool
    {
        return $this->role === 'admin_sdm';
    }

    public function isAdminDepartemen(): bool
    {
        return $this->role === 'admin_departemen';
    }

    public function isManajerDepartemen(): bool
    {
        return $this->role === 'manajer_departemen';
    }

    public function isSeniorManajerSekper(): bool
    {
        return $this->role === 'senior_manajer_sekper';
    }

    public function isKepalaSpi(): bool
    {
        return $this->role === 'kepala_spi';
    }

    public function isDirekturTeknik(): bool
    {
        return $this->role === 'direktur_teknik';
    }

    public function isDirekturAdministrasiKeuangan(): bool
    {
        return $this->role === 'direktur_administrasi_keuangan';
    }

    public function isDirekturUtama(): bool
    {
        return $this->role === 'direktur_utama';
    }

    public function isPemberiKeputusan(): bool
    {
        return in_array($this->role, [
            'manajer_departemen',
            'senior_manajer_sekper',
            'kepala_spi',
            'direktur_teknik',
            'direktur_administrasi_keuangan',
            'direktur_utama',
        ]);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRole($query, string $role)
    {
        return $query->where('role', $role);
    }
}