<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Departemen extends Model
{
    use HasFactory;

    protected $fillable = [
        'kode_departemen',
        'nama_departemen',
        'divisi_id',
    ];

    public function divisi(): BelongsTo
    {
        return $this->belongsTo(Divisi::class);
    }

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

    public function manajer(): HasOne
    {
        return $this->hasOne(User::class)->where('role', 'manajer_departemen');
    }

    public function manajerAktif(): HasOne
    {
        return $this->hasOne(User::class)
            ->where('role', 'manajer_departemen')
            ->where('is_active', true);
    }

    public function manajers(): HasMany
    {
        return $this->hasMany(User::class)->where('role', 'manajer_departemen');
    }

    public function seniorManajerSekper(): HasOne
    {
        return $this->hasOne(User::class)->where('role', 'senior_manajer_sekper');
    }

    public function seniorManajerSekperAktif(): HasOne
    {
        return $this->hasOne(User::class)
            ->where('role', 'senior_manajer_sekper')
            ->where('is_active', true);
    }

    public function kepalaSpi(): HasOne
    {
        return $this->hasOne(User::class)->where('role', 'kepala_spi');
    }

    public function kepalaSpiAktif(): HasOne
    {
        return $this->hasOne(User::class)
            ->where('role', 'kepala_spi')
            ->where('is_active', true);
    }

    public function adminDepartemens(): HasMany
    {
        return $this->hasMany(User::class)->where('role', 'admin_departemen');
    }

    public function pemberiKeputusanUtama(): ?User
    {
        static $direkturTeknik = null;
        static $direkturTeknikSudahDicek = false;

        $departemenTeknik = ['PWS', 'REN', 'PRD', 'DIST'];

        if (in_array($this->kode_departemen, $departemenTeknik, true)) {
            if (! $direkturTeknikSudahDicek) {
                $direkturTeknik = User::where('role', 'direktur_teknik')->where('is_active', true)->first();
                $direkturTeknikSudahDicek = true;
            }

            return $direkturTeknik;
        }

        return match ($this->kode_departemen) {
            'SEK' => $this->seniorManajerSekperAktif,
            'SPI' => $this->kepalaSpiAktif,
            default => $this->manajerAktif,
        };
    }
}