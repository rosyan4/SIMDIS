<?php

namespace App\Exports;

use App\Models\Dispensasi;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * KF-23 (Export Data Dispensasi): hanya pengajuan berstatus DISETUJUI,
 * diurutkan per departemen (A-Z) lalu nama pegawai (A-Z) — sesuai
 * kebutuhan dokumen ("export per departemen per bulan, terurut A-Z").
 */
class DispensasiExport implements FromQuery, WithHeadings, WithMapping, WithTitle, ShouldAutoSize, WithStyles
{
    use Exportable;

    private int $nomor = 0;

    public function __construct(
        private readonly ?string $tahun,
        private readonly ?string $bulan,
        private readonly ?string $departemenId,
        private readonly array $namaBulan,
    ) {
    }

    public function query(): Builder
    {
        $query = Dispensasi::query()
            ->where('dispensasis.status_pengajuan', 'disetujui')
            ->with(['pegawai', 'departemen', 'subdepartemen', 'diprosesOleh']);

        if ($this->tahun) {
            $query->whereYear('dispensasis.tanggal_dispensasi', $this->tahun);
        }

        if ($this->bulan) {
            $query->whereMonth('dispensasis.tanggal_dispensasi', $this->bulan);
        }

        if ($this->departemenId) {
            // Wajib prefix 'dispensasis.' — setelah di-join ke pegawais, kolom
            // departemen_id jadi ambigu karena tabel pegawais JUGA punya kolom
            // departemen_id sendiri (SQLSTATE 23000 kalau tidak di-qualify).
            $query->where('dispensasis.departemen_id', $this->departemenId);
        }

        // select('dispensasis.*') WAJIB ada supaya hasil join tetap ter-hydrate
        // sebagai model Dispensasi yang utuh (bukan campuran kolom departemens/pegawais).
        return $query
            ->join('departemens', 'departemens.id', '=', 'dispensasis.departemen_id')
            ->join('pegawais', 'pegawais.id', '=', 'dispensasis.pegawai_id')
            ->orderBy('departemens.nama_departemen')
            ->orderBy('pegawais.nama_pegawai')
            ->orderBy('dispensasis.tanggal_dispensasi')
            ->select('dispensasis.*');
    }

    public function headings(): array
    {
        return [
            'No',
            'Nomor Dispensasi',
            'Departemen',
            'Subdepartemen',
            'NIK',
            'Nama Pegawai',
            'Jabatan',
            'Tanggal Dispensasi',
            'Waktu',
            'Keterangan',
            'Diproses Oleh',
            'Tanggal Keputusan',
            'Catatan Persetujuan',
        ];
    }

    public function map($dispensasi): array
    {
        $this->nomor++;

        $waktuLabel = [
            'pagi'      => 'Pagi',
            'istirahat' => 'Istirahat',
            'siang'     => 'Siang',
            'sore'      => 'Sore',
        ][$dispensasi->waktu_dispensasi] ?? $dispensasi->waktu_dispensasi;

        return [
            $this->nomor,
            $dispensasi->nomor_dispensasi,
            $dispensasi->departemen->nama_departemen,
            $dispensasi->subdepartemen?->nama_subdepartemen ?? '-',
            $dispensasi->pegawai->nik,
            $dispensasi->pegawai->nama_pegawai,
            $dispensasi->pegawai->jabatan,
            $dispensasi->tanggal_dispensasi->format('d-m-Y'),
            $waktuLabel,
            $dispensasi->keterangan,
            $dispensasi->diprosesOleh?->name ?? '-',
            $dispensasi->tanggal_keputusan?->format('d-m-Y H:i') ?? '-',
            $dispensasi->catatan_persetujuan ?? '-',
        ];
    }

    public function title(): string
    {
        $judul = 'Laporan Dispensasi';

        if ($this->bulan && isset($this->namaBulan[$this->bulan])) {
            $judul .= ' - ' . $this->namaBulan[$this->bulan];
        }

        if ($this->tahun) {
            $judul .= ' ' . $this->tahun;
        }

        // Nama sheet Excel dibatasi maksimal 31 karakter oleh format .xlsx itu sendiri.
        return substr($judul, 0, 31);
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}