<?php

namespace App\Http\Controllers\Sdm;

use App\Http\Controllers\Controller;
use App\Models\Dispensasi;

class DashboardController extends Controller
{
    private const NAMA_BULAN = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    private const NAMA_HARI = ['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'];

    public function index()
    {
        // Catatan skala: untuk sistem internal seukuran Perumda (ratusan-ribuan baris/tahun),
        // memuat semua data lalu diagregasi di PHP masih aman dan lebih portabel antar-DB
        // dibanding query agregasi native SQL. Kalau volume data jadi sangat besar di masa depan,
        // bagian ini sebaiknya dipindah ke query groupBy/selectRaw di database.
        $dispensasis = Dispensasi::with('pegawai.subdepartemen.departemen')->get();

        $totalSemua = $dispensasis->count();
        $totalDisetujui = $dispensasis->where('status', 'disetujui')->count();
        $totalDitolak = $dispensasis->where('status', 'ditolak')->count();
        $totalDiajukan = $dispensasis->where('status', 'diajukan')->count();
        $totalDiputuskan = $totalDisetujui + $totalDitolak;
        $tingkatPersetujuan = $totalDiputuskan > 0 ? round($totalDisetujui / $totalDiputuskan * 100, 1) : null;
        $totalEskalasi = $dispensasis->whereNotNull('escalated_at')->count();

        $waktuPersetujuan = $dispensasis
            ->whereNotNull('approved_at')
            ->map(fn ($d) => $d->created_at->diffInHours($d->approved_at));
        $rataRataJamPersetujuan = $waktuPersetujuan->count() > 0 ? round($waktuPersetujuan->avg(), 1) : null;

        $perDepartemen = $dispensasis
            ->groupBy(fn ($d) => $d->pegawai->subdepartemen?->departemen?->nama_departemen ?? 'Tanpa Departemen')
            ->map(fn ($grup, $nama) => [
                'nama' => $nama,
                'total' => $grup->count(),
                'disetujui' => $grup->where('status', 'disetujui')->count(),
            ])
            ->sortByDesc('total')
            ->values();

        $trenBulanan = collect(range(5, 0))->map(function ($i) use ($dispensasis) {
            $bulan = now()->subMonths($i);
            $jumlah = $dispensasis->filter(fn ($d) => $d->tanggal_dispensasi->format('Y-m') === $bulan->format('Y-m'))->count();

            return [
                'label' => self::NAMA_BULAN[$bulan->month - 1] . ' ' . $bulan->format('y'),
                'total' => $jumlah,
            ];
        });

        $tanggalTerbanyak = $dispensasis
            ->groupBy(fn ($d) => $d->tanggal_dispensasi->format('Y-m-d'))
            ->map(fn ($grup, $tgl) => ['tanggal' => $tgl, 'total' => $grup->count()])
            ->sortByDesc('total')
            ->take(5)
            ->values();

        $pegawaiTerbanyak = $dispensasis
            ->groupBy('user_id')
            ->map(fn ($grup) => [
                'nama' => $grup->first()->pegawai->name,
                'departemen' => $grup->first()->pegawai->subdepartemen?->departemen?->nama_departemen ?? '-',
                'total' => $grup->count(),
            ])
            ->sortByDesc('total')
            ->take(5)
            ->values();

        $polaHari = collect(self::NAMA_HARI)->map(function ($nama, $index) use ($dispensasis) {
            $jumlah = $dispensasis->filter(fn ($d) => $d->tanggal_dispensasi->dayOfWeekIso - 1 === $index)->count();
            return ['label' => $nama, 'total' => $jumlah];
        });

        return view('dashboard.sdm', compact(
            'totalSemua', 'totalDisetujui', 'totalDitolak', 'totalDiajukan', 'tingkatPersetujuan',
            'totalEskalasi', 'rataRataJamPersetujuan', 'perDepartemen', 'trenBulanan',
            'tanggalTerbanyak', 'pegawaiTerbanyak', 'polaHari'
        ));
    }
}