<?php

namespace App\Http\Controllers\Sdm;

use App\Exports\DispensasiExport;
use App\Http\Controllers\Controller;
use App\Models\Departemen;
use App\Models\Dispensasi;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class MonitoringController extends Controller
{
    private const NAMA_BULAN = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    private const URUTAN_WAKTU = ['T', 'TBO', 'TBI', 'CP'];

    public function index(Request $request)
    {
        $tahun = $request->input('tahun');
        $bulan = $request->input('bulan');
        $departemenId = $request->input('departemen_id');
        $status = $request->input('status');

        $semuaBaris = $this->filteredQuery($tahun, $bulan, $departemenId, $status)
            ->with(['pegawai', 'departemen', 'subdepartemen', 'diprosesOleh'])
            ->orderByDesc('tanggal_dispensasi')
            ->get();

        $kelompok = $semuaBaris
            ->groupBy(fn ($d) => $d->pegawai_id . '|' . $d->tanggal_dispensasi->format('Y-m-d'))
            ->map(function ($baris) {
                $acuan = $baris->first();
                $statusUnik = $baris->pluck('status_pengajuan')->unique();
                return (object) [
                    'acuan'         => $acuan,
                    'baris'         => $baris->sortBy(
                        fn ($d) => array_search($d->waktu_dispensasi, self::URUTAN_WAKTU)
                    )->values(),
                    'nomor_list'    => $baris->pluck('nomor_dispensasi')->implode(', '),
                    'statusSeragam' => $statusUnik->count() === 1 ? $statusUnik->first() : null,
                    'tanggal_dispensasi' => $acuan->tanggal_dispensasi,
                ];
            })
            ->sortByDesc('tanggal_dispensasi')
            ->values();

        $perPage = 20;
        $halaman = $request->input('page', 1);
        $dispensasis = new LengthAwarePaginator(
            $kelompok->forPage($halaman, $perPage)->values(),
            $kelompok->count(),
            $perPage,
            $halaman,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('sdm.monitoring.index', [
            'dispensasis' => $dispensasis,
            'departemens' => Departemen::orderBy('nama_departemen')->get(),
            'tahunTersedia' => $this->tahunTersedia(),
            'namaBulan' => self::NAMA_BULAN,
            'tahun' => $tahun,
            'bulan' => $bulan,
            'departemenId' => $departemenId,
            'status' => $status,
        ]);
    }

    public function exportExcel(Request $request)
    {
        $tahun = $request->input('tahun');
        $bulan = $request->input('bulan');
        $departemenId = $request->input('departemen_id');

        $namaDepartemen = $departemenId
            ? (Departemen::find($departemenId)?->nama_departemen ?? 'Departemen')
            : 'Semua Departemen';

        $bagianNamaFile = [Str::slug($namaDepartemen)];

        if ($bulan && isset(self::NAMA_BULAN[$bulan])) {
            $bagianNamaFile[] = Str::slug(self::NAMA_BULAN[$bulan]);
        }

        if ($tahun) {
            $bagianNamaFile[] = $tahun;
        }

        $namaFile = implode('-', $bagianNamaFile) . '.xlsx';

        return Excel::download(
            new DispensasiExport($tahun, $bulan, $departemenId, self::NAMA_BULAN),
            $namaFile
        );
    }

    private function filteredQuery(?string $tahun, ?string $bulan, ?string $departemenId, ?string $status): Builder
    {
        $query = Dispensasi::query();

        if ($tahun) {
            $query->whereYear('tanggal_dispensasi', $tahun);
        }
        if ($bulan) {
            $query->whereMonth('tanggal_dispensasi', $bulan);
        }
        if ($departemenId) {
            $query->where('departemen_id', $departemenId);
        }
        if ($status && in_array($status, ['menunggu_persetujuan', 'disetujui', 'ditolak'], true)) {
            $query->where('status_pengajuan', $status);
        }

        return $query;
    }

    private function tahunTersedia(): array
    {
        $tahun = Dispensasi::selectRaw('DISTINCT YEAR(tanggal_dispensasi) as tahun')
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