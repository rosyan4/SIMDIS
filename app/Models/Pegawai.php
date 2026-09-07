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
        'jabatan',
        'posisi',
        'departemen_id',
        'subdepartemen_id',
        'no_telepon',
        'email',
        'status',
    ];

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

    public function scopePosisi($query, string $posisi)
    {
        return $query->where('posisi', $posisi);
    }

    public function isAktif(): bool
    {
        return $this->status === 'aktif';
    }

    public function isNonaktif(): bool
    {
        return $this->status === 'nonaktif';
    }

    public function isManajer(): bool
    {
        return $this->posisi === 'manajer';
    }

    public function isSeniorManajerSekper(): bool
    {
        return $this->posisi === 'senior_manajer_sekper';
    }

    public function isKepalaSpi(): bool
    {
        return $this->posisi === 'kepala_spi';
    }

    public function isPosisiPuncakDepartemen(): bool
    {
        return in_array($this->posisi, ['manajer', 'senior_manajer_sekper', 'kepala_spi']);
    }
}