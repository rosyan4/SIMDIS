<?php

namespace App\Http\Controllers;

use App\Models\Dispensasi;
use App\Notifications\DispensasiDiputuskan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApprovalController extends Controller
{
    /**
     * Antrian Manajer Departemen: pengajuan di departemennya sendiri, HANYA
     * kalau dia sendiri sedang berstatus aktif. Kalau sedang berhalangan,
     * dia tidak lagi berwenang — pengajuan sudah jadi tanggung jawab
     * Asisten Manajer, jadi ditampilkan pesan, bukan daftar kosong yang
     * membingungkan.
     */
    public function indexManajer()
    {
        $user = Auth::user();

        if (! $user->sedangAktif()) {
            return view('approval.manajer', [
                'dispensasis' => Dispensasi::whereRaw('1 = 0')->paginate(10),
                'sedangBerhalangan' => true,
            ]);
        }

        $dispensasis = Dispensasi::where('departemen_id', $user->departemen_id)
            ->where('status_pengajuan', 'menunggu_persetujuan')
            ->with('pegawai', 'subdepartemen')
            ->latest('tanggal_pengajuan')
            ->paginate(10);

        return view('approval.manajer', ['dispensasis' => $dispensasis, 'sedangBerhalangan' => false]);
    }

    /**
     * Antrian Asisten Manajer: pengajuan di subdepartemennya sendiri, HANYA
     * kalau Manajer Departemen dari departemen terkait SEDANG berhalangan.
     * Dicek dinamis (whereHas ke departemen->manajers), bukan dari flag
     * escalated_at statis — supaya kalau status Manajer berubah kembali jadi
     * aktif, pengajuan otomatis hilang dari antrian Asisten Manajer tanpa
     * perlu proses "un-escalate" terpisah.
     */
    public function indexAsmen()
    {
        $user = Auth::user();

        $dispensasis = Dispensasi::where('subdepartemen_id', $user->subdepartemen_id)
            ->where('status_pengajuan', 'menunggu_persetujuan')
            ->whereHas('departemen.manajers', function ($q) {
                $q->where('is_active', true)->where('status_manajer', 'berhalangan');
            })
            ->with('pegawai', 'subdepartemen')
            ->latest('tanggal_pengajuan')
            ->paginate(10);

        return view('approval.asmen', compact('dispensasis'));
    }

    /**
     * Halaman detail — tempat Manajer/Asisten Manajer melihat kelengkapan
     * pengajuan (termasuk bukti pendukung) sebelum memutuskan.
     */
    public function show(Dispensasi $dispensasi)
    {
        $dispensasi->load('pegawai', 'departemen', 'subdepartemen', 'adminDepartemen', 'diprosesOleh');

        $this->authorizeAccess($dispensasi);

        return view('approval.show', compact('dispensasi'));
    }

    public function approve(Request $request, Dispensasi $dispensasi)
    {
        $this->authorizeAccess($dispensasi);

        $dispensasi->update([
            'status_pengajuan'     => 'disetujui',
            'diproses_oleh_id'     => Auth::id(),
            'tanggal_keputusan'    => now(),
            'catatan_persetujuan'  => $request->input('catatan'),
        ]);

        $this->beriTahuAdminDepartemen($dispensasi);

        return redirect(Auth::user()->dashboardRoute())
            ->with('success', "Dispensasi {$dispensasi->nomor_dispensasi} disetujui.");
    }

    public function reject(Request $request, Dispensasi $dispensasi)
    {
        $request->validate(['catatan' => 'required|string|min:5'], [
            'catatan.required' => 'Alasan penolakan wajib diisi.',
            'catatan.min' => 'Alasan penolakan minimal 5 karakter.',
        ]);

        $this->authorizeAccess($dispensasi);

        $dispensasi->update([
            'status_pengajuan'     => 'ditolak',
            'diproses_oleh_id'     => Auth::id(),
            'tanggal_keputusan'    => now(),
            'catatan_persetujuan'  => $request->input('catatan'),
        ]);

        $this->beriTahuAdminDepartemen($dispensasi);

        return redirect(Auth::user()->dashboardRoute())
            ->with('success', "Dispensasi {$dispensasi->nomor_dispensasi} ditolak.");
    }

    /**
     * Pegawai TIDAK punya akun/login, jadi tidak bisa menerima notifikasi
     * langsung. Yang diberi tahu adalah Admin Departemen yang menginput
     * pengajuan tersebut — dialah yang menyampaikan hasilnya ke pegawai
     * secara manual/offline.
     */
    private function beriTahuAdminDepartemen(Dispensasi $dispensasi): void
    {
        $dispensasi->adminDepartemen?->notify(new DispensasiDiputuskan($dispensasi));
    }

    private function authorizeAccess(Dispensasi $dispensasi): void
    {
        $user = Auth::user();

        $isManajerTerkait = $user->isManajerDepartemen()
            && $dispensasi->departemen_id === $user->departemen_id
            && $user->sedangAktif();

        $isAsmenTerkait = $user->isAsistenManajer()
            && $dispensasi->subdepartemen_id === $user->subdepartemen_id
            && $dispensasi->departemen()
                ->whereHas('manajers', fn ($q) => $q->where('is_active', true)->where('status_manajer', 'berhalangan'))
                ->exists();

        abort_unless($isManajerTerkait || $isAsmenTerkait, 403, 'Anda tidak berwenang mengakses pengajuan ini.');
    }
}