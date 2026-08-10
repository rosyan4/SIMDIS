<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subdepartemen extends Model
{
    protected $fillable = ['departemen_id', 'nama_subdepartemen', 'kode', 'asisten_manajer_id'];

    public function departemen(): BelongsTo
    {
        return $this->belongsTo(Departemen::class);
    }

    public function asistenManajer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'asisten_manajer_id');
    }

    public function pegawais(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Ambil pihak yang berwenang approve untuk subdepartemen ini.
     * Prioritas: Manajer Departemen. Kalau tidak ada, fallback ke Asisten Manajer.
     */
    public function getApprover(): ?User
    {
        return $this->departemen->manajer ?? $this->asistenManajer;
    }
}