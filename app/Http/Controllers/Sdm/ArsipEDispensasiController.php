<?php

namespace App\Http\Controllers\Sdm;

use App\Http\Controllers\Controller;
use App\Models\Departemen;
use App\Models\Dispensasi;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class ArsipEDispensasiController extends Controller
{
    private const NAMA_BULAN = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    public function index(Request $request)
    {
        $tahun = $request->input('tahun');
        $bulan = $request->input('bulan');
        $departemenId = $request->input('departemen_id');
        $kataKunci = $request->input('cari');

        $query = Dispensasi::query()
            ->whereNotNull('nomor_surat_dispensasi')
            ->with(['pegawai', 'departemen', 'diprosesOleh', 'dicetakOleh']);

        if ($tahun) {
            $query->whereYear('tanggal_surat_dispensasi', $tahun);
        }
        if ($bulan) {
            $query->whereMonth('tanggal_surat_dispensasi', $bulan);
        }
        if ($departemenId) {
            $query->where('departemen_id', $departemenId);
        }
        if ($kataKunci) {
            $query->where(function ($q) use ($kataKunci) {
                $q->where('nomor_surat_dispensasi', 'like', "%{$kataKunci}%")
                  ->orWhereHas('pegawai', fn ($qq) => $qq->where('nama_pegawai', 'like', "%{$kataKunci}%"));
            });
        }

        $semuaBaris = $query->orderByDesc('tanggal_surat_dispensasi')->get();

        $suratTerkelompok = $semuaBaris
            ->groupBy('nomor_surat_dispensasi')
            ->map(function ($baris, $nomorSurat) {
                $acuan = $baris->first();
                return (object) [
                    'nomor_surat'      => $nomorSurat,
                    'tanggal_surat'    => $acuan->tanggal_surat_dispensasi,
                    'tanggal_dispensasi' => $acuan->tanggal_dispensasi,
                    'departemen'       => $acuan->departemen,
                    'penyetuju'        => $acuan->diprosesOleh,
                    'dicetak_oleh'     => $acuan->dicetakOleh,
                    'dicetak_pada'     => $acuan->dicetak_pada,
                    'jumlah_pegawai'   => $baris->pluck('pegawai_id')->unique()->count(),
                    'baris_jangkar_id' => $acuan->id,
                    'daftar_nama'      => $baris->pluck('pegawai.nama_pegawai')->unique()->implode(', '),
                ];
            })
            ->values()
            ->sortByDesc('tanggal_surat')
            ->values();

        $perPage = 15;
        $halaman = $request->input('page', 1);
        $arsip = new LengthAwarePaginator(
            $suratTerkelompok->forPage($halaman, $perPage)->values(),
            $suratTerkelompok->count(),
            $perPage,
            $halaman,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('sdm.arsip-e-dispensasi.index', [
            'arsip'          => $arsip,
            'tahun'          => $tahun,
            'bulan'          => $bulan,
            'departemenId'   => $departemenId,
            'cari'           => $kataKunci,
            'tahunTersedia'  => $this->getTahunTersedia(),
            'namaBulan'      => self::NAMA_BULAN,
            'departemenList' => Departemen::orderBy('nama_departemen')->get(),
        ]);
    }

    private function getTahunTersedia(): array
    {
        $tahun = Dispensasi::whereNotNull('tanggal_surat_dispensasi')
            ->selectRaw('DISTINCT YEAR(tanggal_surat_dispensasi) as tahun')
            ->orderByDesc('tahun')
            ->pluck('tahun')
            ->map(fn ($t) => (int) $t)
            ->toArray();
        $tahunSekarang = now()->year;
        if (! in_array($tahunSekarang, $tahun, true)) {
            $tahun[] = $tahunSekarang;
            rsort($tahun);
        }
        return $tahun;
    }
}