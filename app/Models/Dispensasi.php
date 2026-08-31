<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class Dispensasi extends Model
{
    use HasFactory;

    protected $fillable = [
        'nomor_dispensasi',
        'pegawai_id',
        'departemen_id',
        'subdepartemen_id',
        'admin_departemen_id',
        'tanggal_pengajuan',
        'tanggal_dispensasi',
        'waktu_dispensasi',
        'keterangan',
        'bukti_pendukung',
        'status_pengajuan',
        'diproses_oleh_id',
        'catatan_persetujuan',
        'tanggal_keputusan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_pengajuan'  => 'date',
            'tanggal_dispensasi' => 'date',
            'tanggal_keputusan'  => 'datetime',
        ];
    }

    // ================================================================
    // RELASI
    // ================================================================

    public function pegawai(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class);
    }

    public function departemen(): BelongsTo
    {
        return $this->belongsTo(Departemen::class);
    }

    public function subdepartemen(): BelongsTo
    {
        return $this->belongsTo(Subdepartemen::class);
    }

    public function adminDepartemen(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_departemen_id');
    }

    public function diprosesOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diproses_oleh_id');
    }

    // ================================================================
    // SCOPE
    // ================================================================

    public function scopeMenungguPersetujuan($query)
    {
        return $query->where('status_pengajuan', 'menunggu_persetujuan');
    }

    public function scopeDisetujui($query)
    {
        return $query->where('status_pengajuan', 'disetujui');
    }

    public function scopeDitolak($query)
    {
        return $query->where('status_pengajuan', 'ditolak');
    }

    public function scopeDepartemen($query, int $departemenId)
    {
        return $query->where('departemen_id', $departemenId);
    }

    public function scopeSubdepartemen($query, int $subdepartemenId)
    {
        return $query->where('subdepartemen_id', $subdepartemenId);
    }

    public function scopePegawai($query, int $pegawaiId)
    {
        return $query->where('pegawai_id', $pegawaiId);
    }

    public function scopeBulan($query, int $bulan, int $tahun)
    {
        return $query->whereMonth('tanggal_dispensasi', $bulan)
                     ->whereYear('tanggal_dispensasi', $tahun);
    }

    public function scopeBulanPengajuan($query, int $bulan, int $tahun)
    {
        return $query->whereMonth('tanggal_pengajuan', $bulan)
                     ->whereYear('tanggal_pengajuan', $tahun);
    }

    public function scopePeriode($query, string $start, string $end)
    {
        return $query->whereBetween('tanggal_dispensasi', [$start, $end]);
    }

    // ================================================================
    // HELPER
    // ================================================================

    public function isMenungguPersetujuan(): bool
    {
        return $this->status_pengajuan === 'menunggu_persetujuan';
    }

    public function isDisetujui(): bool
    {
        return $this->status_pengajuan === 'disetujui';
    }

    public function isDitolak(): bool
    {
        return $this->status_pengajuan === 'ditolak';
    }

    public function sudahDiputuskan(): bool
    {
        return in_array($this->status_pengajuan, ['disetujui', 'ditolak']);
    }

    /**
     * Generate nomor dispensasi otomatis (KF-07), format: DISP/YYYY/MM/00001.
     *
     * Dibungkus DB::transaction() + lockForUpdate() supaya aman dari race condition:
     * kalau dua Admin Departemen submit pengajuan bersamaan di bulan yang sama,
     * baris terakhir di bulan itu dikunci sampai transaction pertama selesai,
     * sehingga transaction kedua menunggu dan tidak mendapat nomor urut yang sama.
     *
     * Panggil method ini DI DALAM transaction yang sama dengan Dispensasi::create(),
     * bukan terpisah, supaya lock-nya benar-benar melindungi proses insert.
     */
    public static function generateNomor(): string
    {
        return DB::transaction(function () {
            $tahun = now()->format('Y');
            $bulan = now()->format('m');

            $last = self::whereYear('tanggal_pengajuan', $tahun)
                        ->whereMonth('tanggal_pengajuan', $bulan)
                        ->lockForUpdate()
                        ->orderBy('id', 'desc')
                        ->first();

            $urutan = $last ? intval(substr($last->nomor_dispensasi, -5)) + 1 : 1;

            return sprintf('DISP/%s/%s/%05d', $tahun, $bulan, $urutan);
        });
    }
}