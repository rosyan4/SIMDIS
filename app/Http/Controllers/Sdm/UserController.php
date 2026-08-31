<?php

namespace App\Http\Controllers\Sdm;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Models\Departemen;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * KF: Kelola Data Pengguna. Mengelola akun 4 role sistem
     * (admin_sdm, admin_departemen, manajer_departemen, asisten_manajer).
     * Pegawai biasa TIDAK dikelola di sini — lihat PegawaiController.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $role = $request->input('role');
        $departemenId = $request->input('departemen_id');
        $status = $request->input('status'); // 'aktif' | 'nonaktif'

        $query = User::with(['departemen', 'subdepartemen']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('username', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        if ($role && in_array($role, ['admin_sdm', 'admin_departemen', 'manajer_departemen', 'asisten_manajer'], true)) {
            $query->where('role', $role);
        }

        if ($departemenId) {
            $query->where('departemen_id', $departemenId);
        }

        if ($status === 'aktif') {
            $query->where('is_active', true);
        } elseif ($status === 'nonaktif') {
            $query->where('is_active', false);
        }

        $users = $query->orderBy('name')->paginate(20)->withQueryString();

        $departemens = Departemen::orderBy('nama_departemen')->get();

        return view('sdm.pengguna.index', compact('users', 'departemens', 'search', 'role', 'departemenId', 'status'));
    }

    public function create()
    {
        $departemens = Departemen::with('subdepartemens')->orderBy('nama_departemen')->get();

        return view('sdm.pengguna.create', compact('departemens'));
    }

    public function store(StoreUserRequest $request)
    {
        $data = $request->validated();
        $data['password'] = Hash::make($data['password']);
        $data['is_active'] = $request->boolean('is_active', true);

        // Field yang tidak relevan untuk role tertentu dikosongkan eksplisit,
        // jangan andalkan nilai kosong dari form (bisa saja browser mengirim
        // sisa input field yang disembunyikan lewat JS).
        $data = $this->bersihkanFieldSesuaiRole($data);

        User::create($data);

        return redirect()->route('sdm.pengguna.index')->with('success', 'Pengguna berhasil ditambahkan.');
    }

    public function edit(User $pengguna)
    {
        $departemens = Departemen::with('subdepartemens')->orderBy('nama_departemen')->get();

        return view('sdm.pengguna.edit', ['user' => $pengguna, 'departemens' => $departemens]);
    }

    public function update(StoreUserRequest $request, User $pengguna)
    {
        $data = $request->validated();

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $data['is_active'] = $request->boolean('is_active');
        $data = $this->bersihkanFieldSesuaiRole($data);

        $pengguna->update($data);

        return redirect()->route('sdm.pengguna.index')->with('success', 'Data pengguna berhasil diperbarui.');
    }

    /**
     * Nonaktifkan akun (soft), bukan hard delete. dispensasis.admin_departemen_id
     * pakai restrictOnDelete() — hard delete akan ditolak DB kalau user ini
     * pernah menginput/memproses dispensasi. Menonaktifkan tetap menjaga riwayat.
     */
    public function destroy(User $pengguna)
    {
        $pengguna->update(['is_active' => false]);

        return back()->with('success', 'Pengguna berhasil dinonaktifkan.');
    }

    /**
     * Endpoint khusus untuk toggle status Manajer Departemen (Aktif/Berhalangan) —
     * inti dari mekanisme pengalihan otomatis ke Asisten Manajer (bagian 5 dokumen).
     * Dipisah dari update() biasa supaya Admin SDM bisa mengubah status ini cepat
     * tanpa membuka form edit lengkap.
     */
    public function updateStatusManajer(Request $request, User $pengguna)
    {
        if (! $pengguna->isManajerDepartemen()) {
            return back()->with('error', 'Status ini hanya berlaku untuk akun dengan role Manajer Departemen.');
        }

        $data = $request->validate([
            'status_manajer'              => ['required', 'in:aktif,berhalangan'],
            'tanggal_mulai_berhalangan'   => ['nullable', 'date'],
            'tanggal_selesai_berhalangan' => ['nullable', 'date', 'after_or_equal:tanggal_mulai_berhalangan'],
            'alasan_berhalangan'          => ['nullable', 'string', 'max:200'],
        ]);

        // Kalau diset kembali ke aktif, bersihkan sisa data periode berhalangan
        // supaya tidak ada data usang yang membingungkan di riwayat.
        if ($data['status_manajer'] === 'aktif') {
            $data['tanggal_mulai_berhalangan'] = null;
            $data['tanggal_selesai_berhalangan'] = null;
            $data['alasan_berhalangan'] = null;
        }

        $pengguna->update($data);

        return back()->with('success', 'Status Manajer Departemen berhasil diperbarui.');
    }

    /**
     * Kosongkan field yang tidak relevan untuk role tertentu supaya data
     * di DB tidak menyimpan sisa nilai lama (misal: subdepartemen_id lama
     * masih tersimpan padahal role diubah dari asisten_manajer -> admin_sdm).
     */
    private function bersihkanFieldSesuaiRole(array $data): array
    {
        if ($data['role'] === 'admin_sdm') {
            $data['departemen_id'] = null;
            $data['subdepartemen_id'] = null;
        }

        if ($data['role'] === 'admin_departemen' || $data['role'] === 'manajer_departemen') {
            $data['subdepartemen_id'] = null;
        }

        if ($data['role'] === 'asisten_manajer') {
            $data['departemen_id'] = null;
        }

        // Field status manajer hanya relevan untuk manajer_departemen
        if ($data['role'] !== 'manajer_departemen') {
            $data['status_manajer'] = 'aktif';
            $data['tanggal_mulai_berhalangan'] = null;
            $data['tanggal_selesai_berhalangan'] = null;
            $data['alasan_berhalangan'] = null;
        }

        return $data;
    }
}