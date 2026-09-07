<?php

namespace App\Http\Controllers;

use App\Models\Dispensasi;
use App\Notifications\DispensasiDiputuskan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApprovalController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        abort_unless($user->isPemberiKeputusan(), 403, 'Akun Anda tidak berwenang memutuskan pengajuan dispensasi.');

        $dispensasis = Dispensasi::untukPemberiKeputusan($user)
            ->where('status_pengajuan', 'menunggu_persetujuan')
            ->with(['pegawai', 'departemen', 'subdepartemen'])
            ->latest('tanggal_pengajuan')
            ->paginate(10);

        return view('approval.index', compact('dispensasis'));
    }

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
            'status_pengajuan'    => 'disetujui',
            'diproses_oleh_id'    => Auth::id(),
            'tanggal_keputusan'   => now(),
            'catatan_persetujuan' => $request->input('catatan'),
        ]);

        $this->beriTahuAdminDepartemen($dispensasi);

        return redirect(Auth::user()->dashboardRoute())
            ->with('success', "Dispensasi {$dispensasi->nomor_dispensasi} disetujui.");
    }

    public function reject(Request $request, Dispensasi $dispensasi)
    {
        $request->validate(['catatan' => 'required|string|min:5'], [
            'catatan.required' => 'Alasan penolakan wajib diisi.',
            'catatan.min'       => 'Alasan penolakan minimal 5 karakter.',
        ]);

        $this->authorizeAccess($dispensasi);

        $dispensasi->update([
            'status_pengajuan'    => 'ditolak',
            'diproses_oleh_id'    => Auth::id(),
            'tanggal_keputusan'   => now(),
            'catatan_persetujuan' => $request->input('catatan'),
        ]);

        $this->beriTahuAdminDepartemen($dispensasi);

        return redirect(Auth::user()->dashboardRoute())
            ->with('success', "Dispensasi {$dispensasi->nomor_dispensasi} ditolak.");
    }

    private function beriTahuAdminDepartemen(Dispensasi $dispensasi): void
    {
        $dispensasi->adminDepartemen?->notify(new DispensasiDiputuskan($dispensasi));
    }

    private function authorizeAccess(Dispensasi $dispensasi): void
    {
        $pemberiKeputusan = $dispensasi->pemberiKeputusan();
        abort_unless(
            $pemberiKeputusan && $pemberiKeputusan->is(Auth::user()),
            403,
            'Anda tidak berwenang mengakses pengajuan ini.'
        );
    }
}