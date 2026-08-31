<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pegawai extends Model
{
    use HasFactory;

    protected $fillable = [
        'nik',
        'nama_pegawai',
        'jenis_pegawai',
        'jabatan',
        'departemen_id',
        'subdepartemen_id',
        'no_telepon',
        'email',
        'status',
    ];

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

    public function dispensasis(): HasMany
    {
        return $this->hasMany(Dispensasi::class);
    }

    // ================================================================
    // SCOPE
    // ================================================================

    public function scopeAktif($query)
    {
        return $query->where('status', 'aktif');
    }

    public function scopeNonaktif($query)
    {
        return $query->where('status', 'nonaktif');
    }

    public function scopeDepartemen($query, int $departemenId)
    {
        return $query->where('departemen_id', $departemenId);
    }

    public function scopeSubdepartemen($query, int $subdepartemenId)
    {
        return $query->where('subdepartemen_id', $subdepartemenId);
    }

    // ================================================================
    // HELPER
    // ================================================================

    public function isAktif(): bool
    {
        return $this->status === 'aktif';
    }

    public function isNonaktif(): bool
    {
        return $this->status === 'nonaktif';
    }
}