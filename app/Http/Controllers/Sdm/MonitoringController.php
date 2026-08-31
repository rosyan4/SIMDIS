<?php

namespace App\Http\Controllers\Sdm;

use App\Exports\DispensasiExport;
use App\Http\Controllers\Controller;
use App\Models\Departemen;
use App\Models\Dispensasi;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
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
        $status = $request->input('status');   // ← baris ini yang kemungkinan hilang

        $dispensasis = $this->filteredQuery($tahun, $bulan, $departemenId, $status)
            ->with(['pegawai', 'departemen', 'subdepartemen', 'diprosesOleh'])
            ->orderByDesc('tanggal_dispensasi')
            ->get();

        return view('sdm.monitoring.index', [
            'dispensasis' => $dispensasis,
            'departemens' => Departemen::orderBy('nama_departemen')->get(),
            'tahunTersedia' => $this->tahunTersedia(),
            'namaBulan' => self::NAMA_BULAN,
            'tahun' => $tahun,
            'bulan' => $bulan,
            'departemenId' => $departemenId,
            'status' => $status,               // ← dan ini
        ]);
    }

    public function exportExcel(Request $request)
    {
        $tahun = $request->input('tahun');
        $bulan = $request->input('bulan');
        $departemenId = $request->input('departemen_id');

        $namaFile = 'laporan-dispensasi'
            . ($tahun ? '-' . $tahun : '')
            . ($bulan ? '-' . self::NAMA_BULAN[$bulan] : '')
            . '-' . now()->format('His') . '.xlsx';

        return Excel::download(
            new DispensasiExport($tahun, $bulan, $departemenId, self::NAMA_BULAN),
            $namaFile
        );
    }

    private function filteredQuery(?string $tahun, ?string $bulan, ?string $departemenId): Builder
    {
        // KF-23: Admin SDM export data dispensasi yang SUDAH DISETUJUI saja.
        $query = Dispensasi::where('status_pengajuan', 'disetujui');

        if ($tahun) {
            $query->whereYear('tanggal_dispensasi', $tahun);
        }

        if ($bulan) {
            $query->whereMonth('tanggal_dispensasi', $bulan);
        }

        if ($departemenId) {
            // dispensasis.departemen_id adalah snapshot langsung di tabel dispensasi
            // (bukan lewat pegawai->subdepartemen->departemen), jadi cukup where
            // biasa — tidak perlu whereHas + join berlapis seperti versi sebelumnya.
            $query->where('departemen_id', $departemenId);
        }

        return $query;
    }

    private function tahunTersedia()
    {
        return Dispensasi::selectRaw('DISTINCT YEAR(tanggal_dispensasi) as tahun')
            ->orderByDesc('tahun')
            ->pluck('tahun');
    }
}