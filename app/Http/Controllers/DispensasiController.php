<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDispensasiRequest;
use App\Models\Dispensasi;
use App\Models\Pegawai;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DispensasiController extends Controller
{
    private const NAMA_BULAN = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];
    public function create()
    {
        $pegawais = Pegawai::where('departemen_id', Auth::user()->departemen_id)
            ->aktif()
            ->orderBy('nama_pegawai')
            ->get();
        return view('dispensasi.create', compact('pegawais'));
    }

    public function store(StoreDispensasiRequest $request)
    {
        $data = $request->validated();
        $pegawai = Pegawai::findOrFail($data['pegawai_id']);

        if ($request->hasFile('bukti_pendukung')) {
            $data['bukti_pendukung'] = $request->file('bukti_pendukung')->store('bukti-dispensasi', 'public');
        }
        $dispensasiList = DB::transaction(function () use ($data, $pegawai) {
            $list = [];
            foreach ($data['waktu_dispensasi'] as $waktu) {
                $nomor = Dispensasi::generateNomor(); 
                $list[] = Dispensasi::create([
                    'nomor_dispensasi'    => $nomor,
                    'pegawai_id'          => $pegawai->id,
                    'departemen_id'       => $pegawai->departemen_id,
                    'subdepartemen_id'    => $pegawai->subdepartemen_id,
                    'admin_departemen_id' => Auth::id(),
                    'tanggal_pengajuan'   => now()->toDateString(),
                    'tanggal_dispensasi'  => $data['tanggal_dispensasi'],
                    'waktu_dispensasi'    => $waktu,
                    'keterangan'          => $data['keterangan'],
                    'bukti_pendukung'     => $data['bukti_pendukung'] ?? null,
                    'status_pengajuan'    => 'menunggu_persetujuan',
                ]);
            }
            return $list;
        });

        foreach ($dispensasiList as $dispensasi) {
            $this->notifikasiPihakBerwenang($dispensasi);
        }

        $jumlah = count($dispensasiList);
        $nomorList = collect($dispensasiList)->pluck('nomor_dispensasi')->implode(', ');

        return redirect()->route('dispensasi.index')->with(
            'success',
            $jumlah > 1
                ? "{$jumlah} pengajuan dispensasi berhasil dikirim ({$nomorList}), menunggu persetujuan."
                : "Pengajuan dispensasi {$nomorList} berhasil dikirim, menunggu persetujuan."
        );
    }

    public function index(Request $request)
    {
        $tahun = $request->input('tahun');
        $bulan = $request->input('bulan');

        $query = Dispensasi::where('departemen_id', Auth::user()->departemen_id)
            ->with(['pegawai', 'subdepartemen', 'diprosesOleh']);

        if ($tahun) {
            $query->whereYear('tanggal_dispensasi', $tahun);
        }
        if ($bulan) {
            $query->whereMonth('tanggal_dispensasi', $bulan);
        }

        $dispensasis = $query->latest('tanggal_pengajuan')->paginate(10)->withQueryString();

        return view('dispensasi.index', [
            'dispensasis' => $dispensasis,
            'tahun' => $tahun,
            'bulan' => $bulan,
            'tahunTersedia' => $this->getTahunTersedia(),
            'namaBulan' => self::NAMA_BULAN,
        ]);
    }

    public function show(Dispensasi $dispensasi)
    {
        abort_unless($dispensasi->departemen_id === Auth::user()->departemen_id, 403);
        $dispensasi->load(['pegawai', 'subdepartemen', 'adminDepartemen', 'diprosesOleh']);
        return view('dispensasi.show', compact('dispensasi'));
    }

    public function exportPdf(Request $request)
    {
        $departemenId = Auth::user()->departemen_id;
        $namaDepartemen = Auth::user()->departemen?->nama_departemen ?? 'Departemen';
        $tahun = $request->input('tahun');
        $bulan = $request->input('bulan');

        $query = Dispensasi::where('departemen_id', $departemenId)
            ->where('status_pengajuan', 'disetujui')
            ->with(['pegawai', 'subdepartemen', 'diprosesOleh']);

        if ($tahun) {
            $query->whereYear('tanggal_dispensasi', $tahun);
        }
        if ($bulan) {
            $query->whereMonth('tanggal_dispensasi', $bulan);
        }

        $dispensasis = $query->orderBy('tanggal_dispensasi')->get();

        $bagianNamaFile = [Str::slug($namaDepartemen)];
        if ($bulan && isset(self::NAMA_BULAN[$bulan])) {
            $bagianNamaFile[] = Str::slug(self::NAMA_BULAN[$bulan]);
        }
        if ($tahun) {
            $bagianNamaFile[] = $tahun;
        }
        $namaFile = implode('-', $bagianNamaFile) . '.pdf';

        $pdf = Pdf::loadView('dispensasi.export-pdf', [
            'dispensasis' => $dispensasis,
            'namaDepartemen' => $namaDepartemen,
            'tahun' => $tahun,
            'bulan' => $bulan,
        ])->setPaper('a4', 'landscape');

        return $pdf->download($namaFile);
    }

    private function getTahunTersedia(): array
    {
        $tahun = Dispensasi::where('departemen_id', Auth::user()->departemen_id)
            ->selectRaw('DISTINCT YEAR(tanggal_dispensasi) as tahun')
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

    private function notifikasiPihakBerwenang(Dispensasi $dispensasi): void
    {
        $dispensasi->pemberiKeputusan()?->notify(new \App\Notifications\DispensasiDiajukan($dispensasi));
    }
}