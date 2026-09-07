<?php

namespace App\Http\Controllers\Sdm;

use App\Exports\DispensasiExport;
use App\Http\Controllers\Controller;
use App\Models\Departemen;
use App\Models\Dispensasi;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class MonitoringController extends Controller
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
        $status = $request->input('status');

        $dispensasis = $this->filteredQuery($tahun, $bulan, $departemenId, $status)
            ->with(['pegawai', 'departemen', 'subdepartemen', 'diprosesOleh'])
            ->orderByDesc('tanggal_dispensasi')
            ->paginate(20)
            ->withQueryString();

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

    /**
     * Daftar tahun yang tersedia di database + tahun sekarang (kalau belum
     * ada data di tahun berjalan, tetap ditampilkan supaya admin bisa
     * langsung filter ke tahun ini). Konsisten dengan
     * DashboardController::getTahunTersedia().
     */
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