<?php

namespace App\Http\Controllers\Sdm;

use App\Exports\DispensasiExport;
use App\Http\Controllers\Controller;
use App\Models\Departemen;
use App\Models\Dispensasi;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class MonitoringController extends Controller
{
    public function index(Request $request)
    {
        $dispensasis = $this->filteredQuery($request)
            ->with('pegawai.subdepartemen.departemen')
            ->latest('approved_at')
            ->get();

        $terkelompok = $dispensasis
            ->groupBy(fn ($d) => $d->pegawai->subdepartemen?->departemen?->nama_departemen ?? 'Tanpa Departemen')
            ->map(fn ($grupDept) => $grupDept->groupBy(
                fn ($d) => $d->pegawai->subdepartemen?->nama_subdepartemen ?? 'Tanpa Subdepartemen'
            ));

        $departemens = Departemen::orderBy('nama_departemen')->get();

        return view('sdm.monitoring.index', compact('terkelompok', 'departemens'));
    }

    public function exportExcel(Request $request)
    {
        $filters = $request->only(['departemen_id', 'dari_tanggal', 'sampai_tanggal']);

        return Excel::download(new DispensasiExport($filters), 'laporan-dispensasi-disetujui-' . now()->format('Ymd-His') . '.xlsx');
    }

    public function exportPdf(Request $request)
    {
        $dispensasis = $this->filteredQuery($request)->with('pegawai.subdepartemen.departemen')->latest('approved_at')->get();

        $pdf = Pdf::loadView('sdm.monitoring.pdf', [
            'dispensasis' => $dispensasis,
            'filters' => $request->only(['departemen_id', 'dari_tanggal', 'sampai_tanggal']),
            'departemen' => $request->filled('departemen_id') ? Departemen::find($request->departemen_id) : null,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('laporan-dispensasi-disetujui-' . now()->format('Ymd-His') . '.pdf');
    }

    /**
     * SDM hanya melihat pengajuan yang SUDAH DISETUJUI Manajer/Asisten Manajer.
     * Pengajuan yang masih 'diajukan' atau 'ditolak' bukan urusan monitoring SDM —
     * itu ranahnya approval di dashboard Manajer/Asisten Manajer.
     */
    private function filteredQuery(Request $request): Builder
    {
        $query = Dispensasi::where('status', 'disetujui');

        if ($request->filled('departemen_id')) {
            $query->whereHas('pegawai.subdepartemen', fn ($q) =>
                $q->where('departemen_id', $request->departemen_id)
            );
        }

        if ($request->filled('dari_tanggal')) {
            $query->whereDate('tanggal_dispensasi', '>=', $request->dari_tanggal);
        }

        if ($request->filled('sampai_tanggal')) {
            $query->whereDate('tanggal_dispensasi', '<=', $request->sampai_tanggal);
        }

        return $query;
    }
}