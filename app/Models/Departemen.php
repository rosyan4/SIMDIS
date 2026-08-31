<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Departemen extends Model
{
    use HasFactory;

    protected $fillable = [
        'kode_departemen',
        'nama_departemen',
    ];

    public function subdepartemens(): HasMany
    {
        return $this->hasMany(Subdepartemen::class);
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
     * Manajer Departemen yang sedang aktif (bagian 3 & 5 dokumen).
     * Catatan: DB tidak menegakkan "hanya boleh 1 manajer aktif per departemen" —
     * itu harus divalidasi di FormRequest saat create/update user. hasOne() di
     * sini hanya mengambil salah satu kalau validasi tersebut ternyata bocor.
     */
    public function manajerAktif(): HasOne
    {
        return $this->hasOne(User::class)
                    ->where('role', 'manajer_departemen')
                    ->where('is_active', true)
                    ->where('status_manajer', 'aktif');
    }

    public function manajers(): HasMany
    {
        return $this->hasMany(User::class)->where('role', 'manajer_departemen');
    }

    public function adminDepartemens(): HasMany
    {
        return $this->hasMany(User::class)->where('role', 'admin_departemen');
    }
}