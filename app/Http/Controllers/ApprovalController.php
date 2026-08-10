<?php

namespace App\Http\Controllers;

use App\Models\Dispensasi;
use App\Notifications\DispensasiDiputuskan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApprovalController extends Controller
{
    public function indexManajer()
    {
        $user = Auth::user();

        $dispensasis = Dispensasi::whereHas('pegawai.subdepartemen.departemen', function ($q) use ($user) {
            $q->where('manajer_id', $user->id);
        })
        ->where('status', 'diajukan')
        ->with('pegawai.subdepartemen.departemen')
        ->latest()
        ->paginate(10);

        return view('approval.manajer', compact('dispensasis'));
    }

    public function indexAsmen()
    {
        $user = Auth::user();

        $dispensasis = Dispensasi::whereHas('pegawai.subdepartemen', function ($q) use ($user) {
            $q->where('asisten_manajer_id', $user->id);
        })
        ->where('status', 'diajukan')
        ->whereNotNull('escalated_at')
        ->with('pegawai.subdepartemen')
        ->latest()
        ->paginate(10);

        return view('approval.asmen', compact('dispensasis'));
    }

    /**
     * Halaman detail — tempat Manajer/Asisten Manajer melihat kelengkapan
     * pengajuan (termasuk bukti pendukung) sebelum memutuskan.
     */
    public function show(Dispensasi $dispensasi)
    {
        $dispensasi->load('pegawai.subdepartemen.departemen', 'approver');

        $this->authorizeAccess($dispensasi);

        return view('approval.show', compact('dispensasi'));
    }

    public function approve(Request $request, Dispensasi $dispensasi)
    {
        $this->authorizeAccess($dispensasi);

        $dispensasi->update([
            'status' => 'disetujui',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'catatan_persetujuan' => $request->input('catatan'),
        ]);

        $dispensasi->pegawai->notify(new DispensasiDiputuskan($dispensasi));

        return redirect()->route('dashboard.' . $this->dashboardSuffix())
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
            'status' => 'ditolak',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'catatan_persetujuan' => $request->input('catatan'),
        ]);

        $dispensasi->pegawai->notify(new DispensasiDiputuskan($dispensasi));

        return redirect()->route('dashboard.' . $this->dashboardSuffix())
            ->with('success', "Dispensasi {$dispensasi->nomor_dispensasi} ditolak.");
    }

    private function dashboardSuffix(): string
    {
        return Auth::user()->isManajerDepartemen() ? 'manajer' : 'asmen';
    }

    private function authorizeAccess(Dispensasi $dispensasi): void
    {
        $user = Auth::user();
        $subdepartemen = $dispensasi->pegawai->subdepartemen;

        $isManajerTerkait = $user->isManajerDepartemen()
            && $subdepartemen?->departemen?->manajer_id === $user->id;

        $isAsmenTerkait = $user->isAsistenManajer()
            && $subdepartemen?->asisten_manajer_id === $user->id
            && $dispensasi->escalated_at !== null;

        abort_unless($isManajerTerkait || $isAsmenTerkait, 403, 'Anda tidak berwenang mengakses pengajuan ini.');
    }
}