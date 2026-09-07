<?php

namespace App\Http\Controllers\Sdm;

use App\Http\Controllers\Controller;
use App\Models\Departemen;
use App\Models\Dispensasi;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    private const NAMA_BULAN = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

    private const AMBANG_HARI_MENGGANTUNG = 3;

    public function index(Request $request)
    {
        $tahun = (int) $request->input('tahun', now()->year);
        $departemenId = $request->input('departemen_id');
        $status = $request->input('status');

        $base = $this->buildFilteredQuery($tahun, $departemenId, $status);

        // Total per status
        $statusCounts = (clone $base)
            ->select('status_pengajuan', DB::raw('COUNT(*) as jumlah'))
            ->groupBy('status_pengajuan')
            ->pluck('jumlah', 'status_pengajuan');

        $totalMenunggu  = (int) ($statusCounts['menunggu_persetujuan'] ?? 0);
        $totalDisetujui = (int) ($statusCounts['disetujui'] ?? 0);
        $totalDitolak   = (int) ($statusCounts['ditolak'] ?? 0);
        $totalSemua     = $totalMenunggu + $totalDisetujui + $totalDitolak;

        // Dispensasi per bulan
        $jumlahPerBulanRaw = (clone $base)
            ->select(DB::raw('MONTH(tanggal_dispensasi) as bulan'), DB::raw('COUNT(*) as jumlah'))
            ->groupBy(DB::raw('MONTH(tanggal_dispensasi)'))
            ->pluck('jumlah', 'bulan');

        $perBulan = collect(range(1, 12))->map(fn ($bulan) => [
            'label' => self::NAMA_BULAN[$bulan - 1],
            'total' => (int) ($jumlahPerBulanRaw[$bulan] ?? 0),
        ]);

        // Dispensasi per departemen
        $perDepartemen = (clone $base)
            ->join('departemens', 'departemens.id', '=', 'dispensasis.departemen_id')
            ->select('departemens.id', 'departemens.nama_departemen', DB::raw('COUNT(*) as total'))
            ->groupBy('departemens.id', 'departemens.nama_departemen')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'id'    => $row->id,
                'nama'  => $row->nama_departemen,
                'total' => (int) $row->total,
            ]);

        // Dispensasi terbaru
        $terbaru = (clone $base)
            ->with(['pegawai', 'departemen', 'subdepartemen', 'diprosesOleh'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        // Pengajuan menggantung (perlu perhatian)
        $pengajuanMenggantung = $this->getPengajuanMenggantung($departemenId);

        // Data untuk filter
        $departemens = Departemen::orderBy('nama_departemen')->get();
        $tahunTersedia = $this->getTahunTersedia();

        return view('dashboard.sdm', compact(
            'totalSemua',
            'totalMenunggu',
            'totalDisetujui',
            'totalDitolak',
            'perBulan',
            'perDepartemen',
            'terbaru',
            'pengajuanMenggantung',
            'departemens',
            'tahunTersedia',
            'tahun',
            'departemenId',
            'status'
        ));
    }

    private function buildFilteredQuery(int $tahun, $departemenId, $status): Builder
    {
        $query = Dispensasi::query()->whereYear('tanggal_dispensasi', $tahun);

        if ($departemenId) {
            $query->where('departemen_id', $departemenId);
        }

        if ($status && in_array($status, ['menunggu_persetujuan', 'disetujui', 'ditolak'], true)) {
            $query->where('status_pengajuan', $status);
        }

        return $query;
    }

    private function getTahunTersedia(): array
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

    private function getPengajuanMenggantung($departemenId)
    {
        $query = Dispensasi::where('status_pengajuan', 'menunggu_persetujuan')
            ->where('tanggal_pengajuan', '<=', now()->subDays(self::AMBANG_HARI_MENGGANTUNG)->toDateString())
            ->with(['pegawai', 'departemen']);

        if ($departemenId) {
            $query->where('departemen_id', $departemenId);
        }

        return $query->orderBy('tanggal_pengajuan')
            ->get()
            ->map(fn ($d) => [
                'nomor' => $d->nomor_dispensasi,
                'pegawai' => $d->pegawai?->nama_pegawai ?? '-',
                'departemen' => $d->departemen?->nama_departemen ?? '-',
                'hari_menunggu' => now()->diffInDays($d->tanggal_pengajuan),
            ]);
    }
}