<?php

namespace App\Http\Controllers\Sdm;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePegawaiRequest;
use App\Models\Departemen;
use App\Models\Pegawai;
use Illuminate\Http\Request;

class PegawaiController extends Controller
{
    /**
     * KF-04 (Kelola Data Pegawai). Pegawai di sini adalah data master
     * (NIK, nama, jabatan, dst) — BUKAN akun login. Akun login (Admin
     * Departemen / Manajer Departemen / Asisten Manajer / Admin SDM)
     * dikelola lewat User & controller "Kelola Data Pengguna" terpisah.
     */
    /**
     * Ditampilkan dikelompokkan per departemen (accordion), bukan tabel
     * datar dengan pagination — supaya Admin SDM langsung lihat sebaran
     * pegawai per unit tanpa harus filter satu-satu. Konsekuensinya:
     * TIDAK ada pagination di sini (paginate per baris pegawai tidak cocok
     * dengan tampilan berkelompok). Kalau jumlah pegawai sudah sangat besar
     * (ribuan), ini perlu dioptimasi lagi (mis. lazy-load per departemen).
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $departemenId = $request->input('departemen_id');
        $status = $request->input('status');

        $query = Pegawai::with(['departemen', 'subdepartemen']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nik', 'LIKE', "%{$search}%")
                  ->orWhere('nama_pegawai', 'LIKE', "%{$search}%");
            });
        }

        if ($departemenId) {
            $query->where('departemen_id', $departemenId);
        }

        if ($status && in_array($status, ['aktif', 'nonaktif'], true)) {
            $query->where('status', $status);
        }

        $pegawaiPerDepartemen = $query->orderBy('nama_pegawai')->get()->groupBy('departemen_id');

        $departemens = Departemen::orderBy('nama_departemen')->get();

        // Kalau lagi search/filter, departemen yang tidak punya hasil sama
        // sekali disembunyikan (mengurangi noise). Kalau tanpa filter, semua
        // departemen tetap ditampilkan (termasuk yang 0 pegawai) supaya
        // Admin SDM bisa lihat cakupan penuh organisasi.
        $adaFilterAktif = (bool) ($search || $departemenId || $status);
        if ($adaFilterAktif) {
            $departemens = $departemens->filter(
                fn ($d) => $pegawaiPerDepartemen->get($d->id, collect())->isNotEmpty()
            );
        }

        return view('sdm.pegawai.index', compact(
            'pegawaiPerDepartemen',
            'departemens',
            'search',
            'departemenId',
            'status'
        ));
    }

    public function create()
    {
        $departemens = Departemen::with('subdepartemens')->orderBy('nama_departemen')->get();

        return view('sdm.pegawai.create', compact('departemens'));
    }

    public function store(StorePegawaiRequest $request)
    {
        Pegawai::create($request->validated());

        return redirect()->route('sdm.pegawai.index')->with('success', 'Pegawai berhasil ditambahkan.');
    }

    public function edit(Pegawai $pegawai)
    {
        $departemens = Departemen::with('subdepartemens')->orderBy('nama_departemen')->get();

        return view('sdm.pegawai.edit', compact('pegawai', 'departemens'));
    }

    public function update(StorePegawaiRequest $request, Pegawai $pegawai)
    {
        $pegawai->update($request->validated());

        return redirect()->route('sdm.pegawai.index')->with('success', 'Data pegawai berhasil diperbarui.');
    }

    /**
     * Nonaktifkan (soft), bukan hard delete. Selain praktik umum, migration
     * dispensasis pakai restrictOnDelete() ke pegawais — kalau pegawai ini
     * masih punya riwayat dispensasi, hard delete akan ditolak DB. Set
     * status nonaktif tetap menjaga riwayat & integritas data.
     */
    public function destroy(Pegawai $pegawai)
    {
        $pegawai->update(['status' => 'nonaktif']);

        return back()->with('success', 'Pegawai berhasil dinonaktifkan.');
    }
}