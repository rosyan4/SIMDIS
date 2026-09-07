<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Subdepartemen extends Model
{
    use HasFactory;

    protected $fillable = [
        'departemen_id',
        'kode_subdepartemen',
        'nama_subdepartemen',
    ];

    public function departemen(): BelongsTo
    {
        return $this->belongsTo(Departemen::class);
    }

    public function pegawais(): HasMany
    {
        return $this->hasMany(Pegawai::class);
    }

    public function dispensasis(): HasMany
    {
        return $this->hasMany(Dispensasi::class);
    }

    public function asistenManajerAktif(): HasOne
    {
        return $this->hasOne(Pegawai::class)
            ->where('posisi', 'asisten_manajer_bidang')
            ->where('status', 'aktif');
    }
}