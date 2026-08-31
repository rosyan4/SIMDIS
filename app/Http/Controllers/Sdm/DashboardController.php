<?php

namespace App\Http\Controllers\Sdm;

use App\Http\Controllers\Controller;
use App\Models\Departemen;
use App\Models\Dispensasi;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    private const NAMA_BULAN = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

    private const WAKTU_OPTIONS = [
        'pagi'      => 'Pagi',
        'istirahat' => 'Istirahat',
        'siang'     => 'Siang',
        'sore'      => 'Sore',
    ];

    public function index(Request $request)
    {
        $tahun = (int) $request->input('tahun', now()->year);
        $departemenId = $request->input('departemen_id');
        $status = $request->input('status');

        // Query dasar (belum dieksekusi) yang sudah kena filter tahun/departemen/status.
        // Dipakai ulang (clone) untuk tiap agregasi supaya filter selalu konsisten
        // dan tidak perlu menarik seluruh baris ke memory berkali-kali.
        $base = $this->buildFilteredQuery($tahun, $departemenId, $status);

        // ================================================================
        // 1. STATISTIK UTAMA (agregasi di DB, bukan Collection::count())
        // ================================================================
        $statusCounts = (clone $base)
            ->select('status_pengajuan', DB::raw('COUNT(*) as jumlah'))
            ->groupBy('status_pengajuan')
            ->pluck('jumlah', 'status_pengajuan');

        $totalMenunggu  = (int) ($statusCounts['menunggu_persetujuan'] ?? 0);
        $totalDisetujui = (int) ($statusCounts['disetujui'] ?? 0);
        $totalDitolak   = (int) ($statusCounts['ditolak'] ?? 0);
        $totalSemua     = $totalMenunggu + $totalDisetujui + $totalDitolak;

        // ================================================================
        // 2. DISPENSASI PER BULAN (agregasi di DB)
        // ================================================================
        $jumlahPerBulanRaw = (clone $base)
            ->select(DB::raw('MONTH(tanggal_dispensasi) as bulan'), DB::raw('COUNT(*) as jumlah'))
            ->groupBy(DB::raw('MONTH(tanggal_dispensasi)'))
            ->pluck('jumlah', 'bulan');

        $perBulan = collect(range(1, 12))->map(function ($bulan) use ($jumlahPerBulanRaw) {
            return [
                'label' => self::NAMA_BULAN[$bulan - 1],
                'total' => (int) ($jumlahPerBulanRaw[$bulan] ?? 0),
            ];
        });

        // ================================================================
        // 3. DISPENSASI BERDASARKAN WAKTU (agregasi di DB)
        // ================================================================
        $jumlahPerWaktuRaw = (clone $base)
            ->select('waktu_dispensasi', DB::raw('COUNT(*) as jumlah'))
            ->groupBy('waktu_dispensasi')
            ->pluck('jumlah', 'waktu_dispensasi');

        $perWaktu = collect(self::WAKTU_OPTIONS)->map(function ($label, $value) use ($jumlahPerWaktuRaw) {
            return [
                'label' => $label,
                'value' => $value,
                'total' => (int) ($jumlahPerWaktuRaw[$value] ?? 0),
            ];
        })->values();

        // ================================================================
        // 4. DISPENSASI PER DEPARTEMEN (agregasi di DB + join nama departemen)
        // ================================================================
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

        // ================================================================
        // 5. DAFTAR PENGAJUAN TERBARU (LIMIT 10 langsung di DB, bukan collection)
        // ================================================================
        $terbaru = (clone $base)
            ->with(['pegawai', 'departemen', 'subdepartemen', 'diprosesOleh'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        // ================================================================
        // 6. DATA UNTUK FILTER (dropdown)
        // ================================================================
        $departemens = Departemen::orderBy('nama_departemen')->get();
        $tahunTersedia = $this->getTahunTersedia();

        // ================================================================
        // 7. STATISTIK TAMBAHAN
        // ================================================================
        $statistikBulanIni = $this->getStatistikBulanIni();
        $statistikPerDepartemen = $this->getStatistikPerDepartemen($tahun);

        return view('dashboard.sdm', compact(
            'totalSemua',
            'totalMenunggu',
            'totalDisetujui',
            'totalDitolak',
            'perBulan',
            'perWaktu',
            'perDepartemen',
            'terbaru',
            'departemens',
            'tahunTersedia',
            'tahun',
            'departemenId',
            'status',
            'statistikBulanIni',
            'statistikPerDepartemen'
        ));
    }

    // ================================================================
    // PRIVATE METHODS
    // ================================================================

    /**
     * Query dasar dispensasis dengan filter tahun (wajib), departemen & status
     * (opsional). Dikembalikan sebagai Builder yang belum dieksekusi — caller
     * WAJIB clone() sebelum menambahkan select/groupBy/get supaya filter dasar
     * ini tidak saling menimpa antar agregasi.
     */
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

    /**
     * Mendapatkan daftar tahun yang tersedia di database + tahun sekarang
     */
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

    /**
     * Statistik dispensasi bulan ini
     */
    private function getStatistikBulanIni(): array
    {
        $bulanIni = now()->month;
        $tahunIni = now()->year;

        $counts = Dispensasi::whereMonth('tanggal_dispensasi', $bulanIni)
            ->whereYear('tanggal_dispensasi', $tahunIni)
            ->select('status_pengajuan', DB::raw('COUNT(*) as jumlah'))
            ->groupBy('status_pengajuan')
            ->pluck('jumlah', 'status_pengajuan');

        $menunggu  = (int) ($counts['menunggu_persetujuan'] ?? 0);
        $disetujui = (int) ($counts['disetujui'] ?? 0);
        $ditolak   = (int) ($counts['ditolak'] ?? 0);

        return [
            'total'     => $menunggu + $disetujui + $ditolak,
            'menunggu'  => $menunggu,
            'disetujui' => $disetujui,
            'ditolak'   => $ditolak,
        ];
    }

    /**
     * Statistik dispensasi per departemen untuk tahun tertentu
     */
    private function getStatistikPerDepartemen(int $tahun): array
    {
        return Dispensasi::select(
                'departemen_id',
                DB::raw('COUNT(*) as total'),
                // Kutip TUNGGAL, bukan kutip ganda — kutip ganda hanya kebetulan
                // jalan di MySQL selama ANSI_QUOTES nonaktif, dan tidak portable.
                DB::raw("SUM(CASE WHEN status_pengajuan = 'menunggu_persetujuan' THEN 1 ELSE 0 END) as menunggu"),
                DB::raw("SUM(CASE WHEN status_pengajuan = 'disetujui' THEN 1 ELSE 0 END) as disetujui"),
                DB::raw("SUM(CASE WHEN status_pengajuan = 'ditolak' THEN 1 ELSE 0 END) as ditolak")
            )
            ->whereYear('tanggal_dispensasi', $tahun)
            ->groupBy('departemen_id')
            ->with('departemen')
            ->get()
            ->map(fn ($item) => [
                'departemen' => $item->departemen?->nama_departemen ?? 'Tanpa Departemen',
                'total'      => (int) $item->total,
                'menunggu'   => (int) $item->menunggu,
                'disetujui'  => (int) $item->disetujui,
                'ditolak'    => (int) $item->ditolak,
            ])
            ->sortByDesc('total')
            ->values()
            ->toArray();
    }

    // ================================================================
    // API ENDPOINT UNTUK CHART (opsional)
    // ================================================================

    public function chartData(Request $request)
    {
        $tahun = (int) $request->input('tahun', now()->year);
        $departemenId = $request->input('departemen_id');

        $base = $this->buildFilteredQuery($tahun, $departemenId, null);

        $jumlahPerBulanRaw = (clone $base)
            ->select(DB::raw('MONTH(tanggal_dispensasi) as bulan'), DB::raw('COUNT(*) as jumlah'))
            ->groupBy(DB::raw('MONTH(tanggal_dispensasi)'))
            ->pluck('jumlah', 'bulan');

        $perBulan = collect(range(1, 12))->map(fn ($bulan) => [
            'bulan' => self::NAMA_BULAN[$bulan - 1],
            'total' => (int) ($jumlahPerBulanRaw[$bulan] ?? 0),
        ]);

        $statusCounts = (clone $base)
            ->select('status_pengajuan', DB::raw('COUNT(*) as jumlah'))
            ->groupBy('status_pengajuan')
            ->pluck('jumlah', 'status_pengajuan');

        $perStatus = [
            'menunggu_persetujuan' => (int) ($statusCounts['menunggu_persetujuan'] ?? 0),
            'disetujui'            => (int) ($statusCounts['disetujui'] ?? 0),
            'ditolak'              => (int) ($statusCounts['ditolak'] ?? 0),
        ];

        $waktuCounts = (clone $base)
            ->select('waktu_dispensasi', DB::raw('COUNT(*) as jumlah'))
            ->groupBy('waktu_dispensasi')
            ->pluck('jumlah', 'waktu_dispensasi');

        $perWaktu = collect(self::WAKTU_OPTIONS)->mapWithKeys(
            fn ($label, $value) => [$value => (int) ($waktuCounts[$value] ?? 0)]
        );

        return response()->json([
            'success' => true,
            'data' => [
                'per_bulan'  => $perBulan,
                'per_status' => $perStatus,
                'per_waktu'  => $perWaktu,
            ],
        ]);
    }
}