<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Dispensasi extends Model
{
    use HasFactory;

    private const DEPARTEMEN_TEKNIK = ['PWS', 'REN', 'PRD', 'DIST'];
    private const DEPARTEMEN_ADMINISTRASI_KEUANGAN = ['SDM', 'BSN1', 'BSN2', 'KEU', 'PEL'];
    private const DEPARTEMEN_MANDIRI = ['PGD', 'IT'];
    private const DEPARTEMEN_PRODUKSI_DISTRIBUSI = ['PRD', 'DIST'];
    private const DEPARTEMEN_PERENCANAAN_ASET = ['PWS', 'REN'];

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
        'nomor_surat_dispensasi',
        'tanggal_surat_dispensasi',
        'dicetak_oleh_id',
        'ditujukan_kepada_id',
        'token_verifikasi',
        'dicetak_pada',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_pengajuan'        => 'date',
            'tanggal_dispensasi'       => 'date',
            'tanggal_keputusan'        => 'datetime',
            'tanggal_surat_dispensasi' => 'date',
            'dicetak_pada'             => 'datetime',
        ];
    }

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

    public function dicetakOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dicetak_oleh_id');
    }

    public function ditujukanKepada(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ditujukan_kepada_id');
    }

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

    public function scopeBelumDijadikanSurat($query)
    {
        return $query->where('status_pengajuan', 'disetujui')
            ->whereNull('nomor_surat_dispensasi');
    }

    public function scopeUntukPemberiKeputusan($query, User $user)
    {
        return match ($user->role) {
            'direktur_teknik' => $query
                ->whereHas('departemen', fn ($q) => $q->whereIn('kode_departemen', self::DEPARTEMEN_TEKNIK))
                ->whereHas('pegawai', fn ($q) => $q->whereIn('posisi', [
                    'manajer',
                    'senior_manajer_produksi_distribusi',
                    'senior_manajer_perencanaan_aset',
                ])),
            'direktur_utama' => $query->where(function ($q) {
                $q->whereHas('departemen', fn ($qq) => $qq->where('kode_departemen', 'SEK'))
                  ->whereHas('pegawai', fn ($qq) => $qq->where('posisi', 'senior_manajer_sekper'));
            })->orWhere(function ($q) {
                $q->whereHas('departemen', fn ($qq) => $qq->where('kode_departemen', 'SPI'))
                  ->whereHas('pegawai', fn ($qq) => $qq->where('posisi', 'kepala_spi'));
            })->orWhere(function ($q) {
                $q->whereHas('departemen', fn ($qq) => $qq->whereIn('kode_departemen', self::DEPARTEMEN_MANDIRI))
                  ->whereHas('pegawai', fn ($qq) => $qq->where('posisi', 'manajer'));
            }),
            'direktur_administrasi_keuangan' => $query
                ->whereHas('departemen', fn ($q) => $q->whereIn('kode_departemen', self::DEPARTEMEN_ADMINISTRASI_KEUANGAN))
                ->whereHas('pegawai', fn ($q) => $q->whereIn('posisi', ['manajer', 'senior_manajer_bisnis', 'senior_manajer_keuangan_pelanggan'])),
            'senior_manajer_sekper' => $query
                ->where('departemen_id', $user->departemen_id)
                ->whereHas('pegawai', fn ($q) => $q->whereIn('posisi', ['staf', 'asisten_manajer_bidang'])),
            'kepala_spi' => $query
                ->where('departemen_id', $user->departemen_id)
                ->whereHas('pegawai', fn ($q) => $q->whereIn('posisi', ['staf', 'sekretaris_spi'])),
            'senior_manajer_produksi_distribusi' => $query
                ->whereHas('departemen', fn ($q) => $q->whereIn('kode_departemen', self::DEPARTEMEN_PRODUKSI_DISTRIBUSI))
                ->whereHas('pegawai', fn ($q) => $q->whereIn('posisi', ['staf', 'asisten_manajer_bidang'])),
            'senior_manajer_perencanaan_aset' => $query
                ->whereHas('departemen', fn ($q) => $q->whereIn('kode_departemen', self::DEPARTEMEN_PERENCANAAN_ASET))
                ->whereHas('pegawai', fn ($q) => $q->whereIn('posisi', ['staf', 'asisten_manajer_bidang'])),
            'manajer_departemen' => $query
                ->where('departemen_id', $user->departemen_id)
                ->whereHas('pegawai', fn ($q) => $q->whereIn('posisi', ['staf', 'asisten_manajer_bidang'])),
            default => $query->whereRaw('1 = 0'),
        };
    }

    public function scopeSatuKelompokSurat($query, self $acuan)
    {
        return $query->where('departemen_id', $acuan->departemen_id)
            ->where('diproses_oleh_id', $acuan->diproses_oleh_id)
            ->where('tanggal_dispensasi', $acuan->tanggal_dispensasi)
            ->where('status_pengajuan', 'disetujui')
            ->whereNull('nomor_surat_dispensasi');
    }

    public function scopeSatuSurat($query, string $nomorSurat)
    {
        return $query->where('nomor_surat_dispensasi', $nomorSurat);
    }

    public function scopeSatuPengajuanMenunggu($query, self $acuan)
    {
        return $query->where('pegawai_id', $acuan->pegawai_id)
            ->where('tanggal_dispensasi', $acuan->tanggal_dispensasi)
            ->where('status_pengajuan', 'menunggu_persetujuan');
    }

    public function scopeSatuKelompokPegawaiTanggal($query, self $acuan)
    {
        return $query->where('pegawai_id', $acuan->pegawai_id)
            ->where('tanggal_dispensasi', $acuan->tanggal_dispensasi);
    }

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

    public function isSudahDicetak(): bool
    {
        return $this->nomor_surat_dispensasi !== null;
    }

    public function keteranganStatusSurat(): ?string
    {
        if (! $this->isDisetujui()) {
            return null;
        }
        return $this->isSudahDicetak() ? null : 'Belum dijadikan e-dispensasi';
    }

    public function pemberiKeputusan(): ?User
    {
        $departemen = $this->departemen ?? $this->departemen()->first();
        $kode = $departemen?->kode_departemen;
        $posisi = ($this->pegawai ?? $this->pegawai()->first())?->posisi;
        if ($kode === null) {
            return null;
        }
        if (in_array($kode, self::DEPARTEMEN_PRODUKSI_DISTRIBUSI, true)) {
            return in_array($posisi, ['manajer', 'senior_manajer_produksi_distribusi'], true)
                ? User::role('direktur_teknik')->active()->first()
                : User::role('senior_manajer_produksi_distribusi')->active()->first();
        }
        if (in_array($kode, self::DEPARTEMEN_PERENCANAAN_ASET, true)) {
            return in_array($posisi, ['manajer', 'senior_manajer_perencanaan_aset'], true)
                ? User::role('direktur_teknik')->active()->first()
                : User::role('senior_manajer_perencanaan_aset')->active()->first();
        }
        if ($kode === 'SEK') {
            return $posisi === 'senior_manajer_sekper'
                ? User::role('direktur_utama')->active()->first()
                : User::role('senior_manajer_sekper')->where('departemen_id', $departemen->id)->active()->first();
        }
        if ($kode === 'SPI') {
            return $posisi === 'kepala_spi'
                ? User::role('direktur_utama')->active()->first()
                : User::role('kepala_spi')->where('departemen_id', $departemen->id)->active()->first();
        }
        if (in_array($kode, self::DEPARTEMEN_MANDIRI, true)) {
            return $posisi === 'manajer'
                ? User::role('direktur_utama')->active()->first()
                : User::role('manajer_departemen')->where('departemen_id', $departemen->id)->active()->first();
        }
        if (in_array($kode, self::DEPARTEMEN_ADMINISTRASI_KEUANGAN, true)) {
            return in_array($posisi, ['manajer', 'senior_manajer_bisnis', 'senior_manajer_keuangan_pelanggan'], true)
                ? User::role('direktur_administrasi_keuangan')->active()->first()
                : User::role('manajer_departemen')->where('departemen_id', $departemen->id)->active()->first();
        }
        return null;
    }

    public function labelInstansiPenyetuju(): string
    {
        return $this->labelInstansiDariDepartemen($this->departemen ?? $this->departemen()->first());
    }

    private function labelInstansiDariDepartemen(?Departemen $departemen): string
    {
        if ($departemen?->kode_departemen === 'SPI') {
            return 'SATUAN PENGAWAS INTERN (SPI)';
        }
        if ($departemen?->divisi) {
            return 'DIVISI ' . Str::upper($this->namaDivisiLengkap($departemen->divisi));
        }
        return 'DEPARTEMEN ' . Str::upper($this->namaDepartemenLengkap($departemen));
    }

    private function namaDepartemenLengkap(?Departemen $departemen): string
    {
        if (! $departemen) {
            return '-';
        }
        return $departemen->kode_departemen === 'SDM'
            ? 'Sumber Daya Manusia'
            : ($departemen->nama_departemen ?? '-');
    }

    private function namaDivisiLengkap(?Divisi $divisi): string
    {
        if (! $divisi || ! $divisi->nama_divisi) {
            return '-';
        }
        return trim(preg_replace('/^divisi\s+/i', '', $divisi->nama_divisi));
    }

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