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
    public function index(Request $request)
    {
        $search = $request->input('search');
        $filterDepartemen = $request->input('departemen_id');

        $query = Departemen::with([
            'divisi',
            'manajerAktif' => function ($query) {
                $query->select('id', 'name', 'email', 'departemen_id', 'is_active', 'role');
            },
            'seniorManajerSekperAktif' => function ($query) {
                $query->select('id', 'name', 'email', 'departemen_id', 'is_active', 'role');
            },
            'kepalaSpiAktif' => function ($query) {
                $query->select('id', 'name', 'email', 'departemen_id', 'is_active', 'role');
            },
            'subdepartemens' => function ($query) {
                $query->orderBy('nama_subdepartemen');
            },
            'subdepartemens.asistenManajerAktif' => function ($query) {
                $query->select('id', 'nama_pegawai', 'subdepartemen_id', 'posisi', 'status');
            },
            'pegawais' => function ($query) {
                $query->where('status', 'aktif')->select('id', 'nama_pegawai', 'departemen_id');
            },
        ]);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('kode_departemen', 'LIKE', "%{$search}%")
                  ->orWhere('nama_departemen', 'LIKE', "%{$search}%");
            });
        }

        if ($filterDepartemen) {
            $query->where('departemens.id', $filterDepartemen);
        }

        $departemens = $query->orderBy('nama_departemen')->get();
        $statistik = $this->getStatistikDepartemen();

        return view('sdm.departemen.index', compact(
            'departemens',
            'search',
            'filterDepartemen',
            'statistik'
        ));
    }

    public function show($id)
    {
        $departemen = Departemen::with([
            'divisi',
            'manajerAktif',
            'seniorManajerSekperAktif',
            'kepalaSpiAktif',
            'manajers',
            'subdepartemens' => function ($query) {
                $query->orderBy('nama_subdepartemen');
            },
            'subdepartemens.asistenManajerAktif',
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

        $statistikSubdepartemen = $this->getStatistikSubdepartemen($id);
        $statistikDispensasi = $this->getStatistikDispensasiDepartemen($id);

        return view('sdm.departemen.show', compact(
            'departemen',
            'statistikSubdepartemen',
            'statistikDispensasi'
        ));
    }

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

    public function tree()
    {
        $departemens = Departemen::with([
            'divisi',
            'subdepartemens' => function ($query) {
                $query->orderBy('nama_subdepartemen');
            },
            'manajer',
            'seniorManajerSekper',
            'kepalaSpi',
        ])->orderBy('nama_departemen')->get();

        $tree = $departemens->map(function ($dept) {
            return [
                'id' => $dept->id,
                'kode' => $dept->kode_departemen,
                'nama' => $dept->nama_departemen,
                'divisi' => $dept->divisi ? [
                    'nama' => $dept->divisi->nama_divisi,
                    'senior_manajer' => $dept->divisi->nama_senior_manajer,
                    'direktorat' => $dept->divisi->direktorat,
                ] : null,
                'manajer' => $dept->manajer ? [
                    'id' => $dept->manajer->id,
                    'nama' => $dept->manajer->name,
                ] : null,
                'senior_manajer_sekper' => $dept->seniorManajerSekper ? [
                    'id' => $dept->seniorManajerSekper->id,
                    'nama' => $dept->seniorManajerSekper->name,
                ] : null,
                'kepala_spi' => $dept->kepalaSpi ? [
                    'id' => $dept->kepalaSpi->id,
                    'nama' => $dept->kepalaSpi->name,
                ] : null,
                'subdepartemens' => $dept->subdepartemens->map(function ($sub) {
                    return [
                        'id' => $sub->id,
                        'kode' => $sub->kode_subdepartemen,
                        'nama' => $sub->nama_subdepartemen,
                    ];
                }),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $tree,
        ]);
    }

    private function getStatistikDepartemen(): array
    {
        return Departemen::select(
                'departemens.id',
                'departemens.nama_departemen',
                DB::raw('COUNT(DISTINCT pegawais.id) as total_pegawai'),
                DB::raw("COUNT(DISTINCT CASE WHEN pegawais.status = 'aktif' THEN pegawais.id END) as pegawai_aktif"),
                DB::raw('COUNT(DISTINCT subdepartemens.id) as total_subdepartemen'),
                DB::raw("COUNT(DISTINCT CASE WHEN users.role = 'admin_departemen' AND users.is_active = 1 THEN users.id END) as total_admin"),
                DB::raw("COUNT(DISTINCT CASE WHEN users.role IN ('manajer_departemen', 'senior_manajer_sekper', 'kepala_spi') AND users.is_active = 1 THEN users.id END) as total_manajer")
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

    private function getStatistikSubdepartemen(int $departemenId): array
    {
        return Subdepartemen::select(
                'subdepartemens.id',
                'subdepartemens.kode_subdepartemen',
                'subdepartemens.nama_subdepartemen',
                DB::raw('COUNT(DISTINCT pegawais.id) as total_pegawai'),
                DB::raw("COUNT(DISTINCT CASE WHEN pegawais.status = 'aktif' THEN pegawais.id END) as pegawai_aktif"),
                DB::raw("COUNT(DISTINCT CASE WHEN pegawais.posisi = 'asisten_manajer_bidang' AND pegawais.status = 'aktif' THEN pegawais.id END) as total_asisten")
            )
            ->where('subdepartemens.departemen_id', $departemenId)
            ->leftJoin('pegawais', 'subdepartemens.id', '=', 'pegawais.subdepartemen_id')
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

    private function getStatistikDispensasiDepartemen(int $departemenId): array
    {
        $tahunIni = now()->year;
        $bulanIni = now()->month;

        $counts = Dispensasi::where('departemen_id', $departemenId)
            ->whereYear('tanggal_dispensasi', $tahunIni)
            ->select(
                'status_pengajuan',
                DB::raw('COUNT(*) as total'),
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