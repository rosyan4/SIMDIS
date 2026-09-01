<?php

namespace App\Http\Controllers\Sdm;

use App\Http\Controllers\Controller;
use App\Models\Departemen;
use App\Models\Dispensasi;
use App\Models\Subdepartemen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DepartemenController extends Controller
{
    /**
     * Menampilkan daftar struktur departemen dan subdepartemen
     * Admin SDM hanya dapat melihat (read-only)
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $filterDepartemen = $request->input('departemen_id');

        // Query departemen dengan relasi
        $query = Departemen::with([
            'manajerAktif' => function ($query) {
                $query->select('id', 'name', 'email', 'departemen_id', 'is_active', 'status_manajer');
            },
            'manajers' => function ($query) {
                $query->select('id', 'name', 'email', 'departemen_id', 'is_active', 'status_manajer');
            },
            'subdepartemens' => function ($query) {
                $query->orderBy('nama_subdepartemen');
            },
            'subdepartemens.asistenManajerAktif' => function ($query) {
                $query->select('id', 'name', 'email', 'subdepartemen_id', 'is_active');
            },
            'subdepartemens.asistenManajers' => function ($query) {
                $query->select('id', 'name', 'email', 'subdepartemen_id', 'is_active');
            },
            'pegawais' => function ($query) {
                $query->where('status', 'aktif')->select('id', 'nama_pegawai', 'departemen_id');
            },
        ]);

        // Filter pencarian
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('kode_departemen', 'LIKE', "%{$search}%")
                  ->orWhere('nama_departemen', 'LIKE', "%{$search}%");
            });
        }

        // Filter spesifik departemen
        if ($filterDepartemen) {
            $query->where('departemens.id', $filterDepartemen);
        }

        $departemens = $query->orderBy('nama_departemen')->get();

        // Statistik untuk setiap departemen
        $statistik = $this->getStatistikDepartemen();

        return view('sdm.departemen.index', compact(
            'departemens',
            'search',
            'filterDepartemen',
            'statistik'
        ));
    }

    /**
     * Menampilkan detail satu departemen beserta subdepartemen dan pegawai
     */
    public function show($id)
    {
        $departemen = Departemen::with([
            'manajerAktif',
            'manajers',
            'subdepartemens' => function ($query) {
                $query->orderBy('nama_subdepartemen');
            },
            'subdepartemens.asistenManajerAktif',
            'subdepartemens.asistenManajers',
            'subdepartemens.pegawais' => function ($query) {
                $query->where('status', 'aktif')->orderBy('nama_pegawai');
            },
            'pegawais' => function ($query) {
                $query->where('status', 'aktif')->orderBy('nama_pegawai');
            },
            'adminDepartemens' => function ($query) {
                $query->where('is_active', true)->select('id', 'name', 'email', 'departemen_id');
            },
        ])->findOrFail($id);

        // Statistik pegawai per subdepartemen
        $statistikSubdepartemen = $this->getStatistikSubdepartemen($id);

        // Statistik dispensasi per departemen
        $statistikDispensasi = $this->getStatistikDispensasiDepartemen($id);

        return view('sdm.departemen.show', compact(
            'departemen',
            'statistikSubdepartemen',
            'statistikDispensasi'
        ));
    }

    /**
     * API: Mendapatkan daftar departemen (untuk dropdown/filter)
     */
    public function list(Request $request)
    {
        $query = Departemen::select('id', 'kode_departemen', 'nama_departemen')
            ->orderBy('nama_departemen');

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('kode_departemen', 'LIKE', "%{$search}%")
                  ->orWhere('nama_departemen', 'LIKE', "%{$search}%");
            });
        }

        $departemens = $query->get();

        return response()->json([
            'success' => true,
            'data' => $departemens,
        ]);
    }

    /**
     * API: Mendapatkan struktur lengkap dalam format tree
     * (untuk keperluan frontend)
     */
    public function tree()
    {
        $departemens = Departemen::with([
            'subdepartemens' => function ($query) {
                $query->orderBy('nama_subdepartemen');
            },
            'subdepartemens.asistenManajerAktif',
            'manajerAktif',
        ])->orderBy('nama_departemen')->get();

        $tree = $departemens->map(function ($dept) {
            return [
                'id' => $dept->id,
                'kode' => $dept->kode_departemen,
                'nama' => $dept->nama_departemen,
                'manajer' => $dept->manajerAktif ? [
                    'id' => $dept->manajerAktif->id,
                    'nama' => $dept->manajerAktif->name,
                    'status' => $dept->manajerAktif->status_manajer,
                ] : null,
                'subdepartemens' => $dept->subdepartemens->map(function ($sub) {
                    return [
                        'id' => $sub->id,
                        'kode' => $sub->kode_subdepartemen,
                        'nama' => $sub->nama_subdepartemen,
                        'asisten_manajer' => $sub->asistenManajerAktif ? [
                            'id' => $sub->asistenManajerAktif->id,
                            'nama' => $sub->asistenManajerAktif->name,
                        ] : null,
                    ];
                }),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $tree,
        ]);
    }

    // ================================================================
    // PRIVATE METHODS (Statistik)
    // ================================================================

    /**
     * Statistik setiap departemen
     */
    private function getStatistikDepartemen(): array
    {
        return Departemen::select(
                'departemens.id',
                'departemens.nama_departemen',
                DB::raw('COUNT(DISTINCT pegawais.id) as total_pegawai'),
                // Kutip TUNGGAL untuk literal string di dalam raw SQL — kutip ganda
                // hanya kebetulan jalan di MySQL selama ANSI_QUOTES nonaktif.
                DB::raw("COUNT(DISTINCT CASE WHEN pegawais.status = 'aktif' THEN pegawais.id END) as pegawai_aktif"),
                DB::raw('COUNT(DISTINCT subdepartemens.id) as total_subdepartemen'),
                DB::raw("COUNT(DISTINCT CASE WHEN users.role = 'admin_departemen' AND users.is_active = 1 THEN users.id END) as total_admin"),
                DB::raw("COUNT(DISTINCT CASE WHEN users.role = 'manajer_departemen' AND users.is_active = 1 THEN users.id END) as total_manajer")
            )
            ->leftJoin('pegawais', 'departemens.id', '=', 'pegawais.departemen_id')
            ->leftJoin('subdepartemens', 'departemens.id', '=', 'subdepartemens.departemen_id')
            ->leftJoin('users', 'departemens.id', '=', 'users.departemen_id')
            ->groupBy('departemens.id', 'departemens.nama_departemen')
            ->orderBy('departemens.nama_departemen')
            ->get()
            ->map(fn ($item) => [
                'departemen_id'       => $item->id,
                'nama_departemen'     => $item->nama_departemen,
                'total_pegawai'       => (int) $item->total_pegawai,
                'pegawai_aktif'       => (int) $item->pegawai_aktif,
                'total_subdepartemen' => (int) $item->total_subdepartemen,
                'total_admin'         => (int) $item->total_admin,
                'total_manajer'       => (int) $item->total_manajer,
            ])
            ->toArray();
    }

    /**
     * Statistik subdepartemen dalam satu departemen
     */
    private function getStatistikSubdepartemen(int $departemenId): array
    {
        return Subdepartemen::select(
                'subdepartemens.id',
                'subdepartemens.kode_subdepartemen',
                'subdepartemens.nama_subdepartemen',
                DB::raw('COUNT(DISTINCT pegawais.id) as total_pegawai'),
                DB::raw("COUNT(DISTINCT CASE WHEN pegawais.status = 'aktif' THEN pegawais.id END) as pegawai_aktif"),
                DB::raw("COUNT(DISTINCT CASE WHEN users.role = 'asisten_manajer' AND users.is_active = 1 THEN users.id END) as total_asisten")
            )
            ->where('subdepartemens.departemen_id', $departemenId)
            ->leftJoin('pegawais', 'subdepartemens.id', '=', 'pegawais.subdepartemen_id')
            ->leftJoin('users', 'subdepartemens.id', '=', 'users.subdepartemen_id')
            ->groupBy('subdepartemens.id', 'subdepartemens.kode_subdepartemen', 'subdepartemens.nama_subdepartemen')
            ->orderBy('subdepartemens.nama_subdepartemen')
            ->get()
            ->map(fn ($item) => [
                'subdepartemen_id' => $item->id,
                'kode'              => $item->kode_subdepartemen,
                'nama'              => $item->nama_subdepartemen,
                'total_pegawai'     => (int) $item->total_pegawai,
                'pegawai_aktif'     => (int) $item->pegawai_aktif,
                'total_asisten'     => (int) $item->total_asisten,
            ])
            ->toArray();
    }

    /**
     * Statistik dispensasi per departemen (tahun berjalan + bulan berjalan)
     * digabung jadi satu query agregasi, bukan 3 query terpisah.
     */
    private function getStatistikDispensasiDepartemen(int $departemenId): array
    {
        $tahunIni = now()->year;
        $bulanIni = now()->month;

        $counts = Dispensasi::where('departemen_id', $departemenId)
            ->whereYear('tanggal_dispensasi', $tahunIni)
            ->select(
                'status_pengajuan',
                DB::raw('COUNT(*) as total'),
                // $bulanIni berasal dari now()->month (integer server-generated,
                // bukan input user), jadi aman diinterpolasi langsung tanpa binding.
                DB::raw("SUM(CASE WHEN MONTH(tanggal_dispensasi) = {$bulanIni} THEN 1 ELSE 0 END) as total_bulan_ini")
            )
            ->groupBy('status_pengajuan')
            ->get();

        $perStatus = [
            'menunggu_persetujuan' => 0,
            'disetujui'            => 0,
            'ditolak'              => 0,
        ];
        $totalTahunIni = 0;
        $bulanIniData = 0;

        foreach ($counts as $row) {
            if (array_key_exists($row->status_pengajuan, $perStatus)) {
                $perStatus[$row->status_pengajuan] = (int) $row->total;
            }
            $totalTahunIni += (int) $row->total;
            $bulanIniData  += (int) $row->total_bulan_ini;
        }

        return [
            'total_tahun_ini' => $totalTahunIni,
            'bulan_ini'       => $bulanIniData,
            'per_status'      => $perStatus,
        ];
    }
}