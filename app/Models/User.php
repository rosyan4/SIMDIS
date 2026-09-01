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
        'status_manajer',
        'tanggal_mulai_berhalangan',
        'tanggal_selesai_berhalangan',
        'alasan_berhalangan',
        'keterangan_tambahan',
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
            'email_verified_at'           => 'datetime',
            'password'                    => 'hashed',
            'tanggal_mulai_berhalangan'   => 'date',
            'tanggal_selesai_berhalangan' => 'date',
            'is_active'                   => 'boolean',
            'must_change_password'        => 'boolean',
        ];
    }

    // ================================================================
    // RELASI
    // ================================================================

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

    // ================================================================
    // NAVIGASI
    // ================================================================

    /**
     * Dipanggil oleh middleware RedirectIfAuthenticated (app/Http/Middleware)
     * saat user yang sudah login mengakses halaman login/register — user
     * langsung diarahkan ke dashboard sesuai role-nya.
     *
     * Nama rute di bawah HARUS sama persis dengan yang didaftarkan di
     * routes/web.php. Route::has() tetap dipertahankan sebagai pengaman
     * kalau suatu saat route-nya berubah nama lagi tanpa method ini
     * ikut diperbarui — sistem tidak crash, hanya fallback ke '/'.
     */
    public function dashboardRoute(): string
    {
        $routeName = match ($this->role) {
            'admin_sdm'           => 'sdm.dashboard',
            'admin_departemen'    => 'dashboard.admin-departemen',
            'manajer_departemen'  => 'dashboard.manajer',
            'asisten_manajer'     => 'dashboard.asmen',
            default               => null,
        };

        if ($routeName && \Illuminate\Support\Facades\Route::has($routeName)) {
            return route($routeName);
        }

        return '/';
    }

    // ================================================================
    // CHECK ROLE
    // ================================================================

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

    public function isAsistenManajer(): bool
    {
        return $this->role === 'asisten_manajer';
    }

    // ================================================================
    // STATUS MANAJER
    // ================================================================

    public function sedangBerhalangan(): bool
    {
        if (! $this->isManajerDepartemen()) {
            return false;
        }

        if ($this->status_manajer !== 'berhalangan') {
            return false;
        }

        $today = now()->toDateString();

        if ($this->tanggal_mulai_berhalangan && $today < $this->tanggal_mulai_berhalangan->toDateString()) {
            return false;
        }

        if ($this->tanggal_selesai_berhalangan && $today > $this->tanggal_selesai_berhalangan->toDateString()) {
            return false;
        }

        return true;
    }

    public function sedangAktif(): bool
    {
        if (! $this->isManajerDepartemen()) {
            return false;
        }

        return $this->status_manajer === 'aktif' && $this->is_active;
    }

    // ================================================================
    // SCOPE
    // ================================================================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    public function scopeManajerAktif($query)
    {
        return $query->where('role', 'manajer_departemen')
                     ->where('status_manajer', 'aktif')
                     ->where('is_active', true);
    }

    public function scopeManajerBerhalangan($query)
    {
        return $query->where('role', 'manajer_departemen')
                     ->where('status_manajer', 'berhalangan')
                     ->where('is_active', true);
    }
}