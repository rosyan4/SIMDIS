<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDispensasiRequest;
use App\Models\Dispensasi;
use App\Notifications\DispensasiDiajukan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DispensasiController extends Controller
{
    public function create()
    {
        return view('dispensasi.create');
    }

    public function store(StoreDispensasiRequest $request)
    {
        $data = $request->validated();
        $data['user_id'] = Auth::id();
        $data['status'] = 'diajukan';

        if ($request->hasFile('bukti_pendukung')) {
            $data['bukti_pendukung'] = $request->file('bukti_pendukung')->store('bukti-dispensasi', 'public');
        }

        $dispensasi = DB::transaction(function () use ($data) {
            $data['nomor_dispensasi'] = $this->generateNomorDispensasi();
            return Dispensasi::create($data);
        });

        $manajer = Auth::user()->subdepartemen?->departemen?->manajer;
        if ($manajer) {
            $manajer->notify(new DispensasiDiajukan($dispensasi));
        }

        return redirect()->route('dashboard.pegawai')
            ->with('success', 'Pengajuan dispensasi berhasil dikirim, menunggu persetujuan.');
    }

    public function index()
    {
        $dispensasis = Auth::user()->dispensasis()->latest()->paginate(10);
        return view('dispensasi.index', compact('dispensasis'));
    }

    public function show(Dispensasi $dispensasi)
    {
        abort_unless($dispensasi->user_id === Auth::id(), 403);
        return view('dispensasi.show', compact('dispensasi'));
    }

    private function generateNomorDispensasi(): string
    {
        $tahun = now()->format('Y');
        $bulan = now()->format('m');

        // lockForUpdate mengunci baris bulan berjalan sampai transaction ini selesai —
        // request lain yang datang bersamaan harus menunggu, sehingga tidak ada 2 nomor sama.
        $urutan = Dispensasi::whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->lockForUpdate()
            ->count() + 1;

        return sprintf('DISP/%s/%s/%s', $tahun, $bulan, str_pad($urutan, 4, '0', STR_PAD_LEFT));
    }
}