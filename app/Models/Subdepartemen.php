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

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function dispensasis(): HasMany
    {
        return $this->hasMany(Dispensasi::class);
    }

    /**
     * Asisten Manajer yang sedang aktif untuk subdepartemen ini (bagian 4 dokumen).
     * Sama seperti Departemen::manajerAktif() — "hanya 1 asisten manajer aktif per
     * subdepartemen" perlu divalidasi di FormRequest, bukan hanya diasumsikan lewat hasOne().
     */
    public function asistenManajerAktif(): HasOne
    {
        return $this->hasOne(User::class)
                    ->where('role', 'asisten_manajer')
                    ->where('is_active', true);
    }

    public function asistenManajers(): HasMany
    {
        return $this->hasMany(User::class)->where('role', 'asisten_manajer');
    }
}