<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDispensasiRequest;
use App\Models\Dispensasi;
use App\Models\Pegawai;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DispensasiController extends Controller
{
    /**
     * KF-09 (Input Pengajuan Dispensasi). Dilakukan oleh Admin Departemen
     * ATAS NAMA pegawai — Pegawai sendiri tidak punya akun/login.
     */
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

        // Satu record Dispensasi PER waktu yang dicentang (Pagi + Siang = 2 baris
        // terpisah, masing-masing dengan nomor_dispensasi sendiri) — bukan satu
        // baris dengan beberapa waktu digabung.
        $dispensasiList = DB::transaction(function () use ($data, $pegawai) {
            $list = [];

            foreach ($data['waktu_dispensasi'] as $waktu) {
                $nomor = Dispensasi::generateNomor(); // sudah pakai lockForUpdate() di dalamnya

                $list[] = Dispensasi::create([
                    'nomor_dispensasi'    => $nomor,
                    'pegawai_id'          => $pegawai->id,
                    // Snapshot struktur organisasi pegawai SAAT pengajuan dibuat
                    // (lihat komentar di migration dispensasis kenapa ini terpisah
                    // dari pegawais.departemen_id).
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

    /**
     * Riwayat dispensasi departemen (bukan cuma yang diinput Admin Departemen
     * yang sedang login — tapi seluruh departemen, supaya kalau ada lebih dari
     * satu akun Admin Departemen di departemen yang sama, riwayatnya tetap utuh).
     */
    public function index()
    {
        $dispensasis = Dispensasi::where('departemen_id', Auth::user()->departemen_id)
            ->with(['pegawai', 'subdepartemen', 'diprosesOleh'])
            ->latest('tanggal_pengajuan')
            ->paginate(10);

        return view('dispensasi.index', compact('dispensasis'));
    }

    public function show(Dispensasi $dispensasi)
    {
        abort_unless($dispensasi->departemen_id === Auth::user()->departemen_id, 403);

        $dispensasi->load(['pegawai', 'subdepartemen', 'adminDepartemen', 'diprosesOleh']);

        return view('dispensasi.show', compact('dispensasi'));
    }

    /**
     * Beri tahu pihak yang berwenang memutuskan: Manajer Departemen kalau
     * sedang aktif, atau Asisten Manajer subdepartemen pegawai kalau Manajer
     * sedang berhalangan (bagian 5 dokumen). Ini murni notifikasi — keputusan
     * SIAPA yang benar-benar berwenang tetap dicek ulang secara dinamis oleh
     * ApprovalController saat pengajuan diproses (status manajer bisa berubah
     * di antara waktu pengajuan dibuat dan waktu diputuskan).
     */
    private function notifikasiPihakBerwenang(Dispensasi $dispensasi): void
    {
        $departemen = $dispensasi->departemen()->with('manajerAktif')->first();
        $manajerAktif = $departemen?->manajerAktif;

        if ($manajerAktif) {
            $manajerAktif->notify(new \App\Notifications\DispensasiDiajukan($dispensasi));
            return;
        }

        // Manajer tidak aktif / berhalangan -> alihkan notifikasi ke Asisten Manajer
        if ($dispensasi->subdepartemen_id) {
            $asistenAktif = \App\Models\Subdepartemen::find($dispensasi->subdepartemen_id)?->asistenManajerAktif;
            $asistenAktif?->notify(new \App\Notifications\DispensasiDiajukan($dispensasi));
        }
    }
}