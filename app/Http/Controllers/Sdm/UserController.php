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
    private const ROLE_TERSEDIA = [
        'admin_sdm',
        'admin_departemen',
        'manajer_departemen',
        'senior_manajer_sekper',
        'kepala_spi',
        'direktur_teknik',
        'direktur_administrasi_keuangan',
        'direktur_utama',
    ];

    private const ROLE_DENGAN_DEPARTEMEN = [
        'admin_departemen',
        'manajer_departemen',
        'senior_manajer_sekper',
        'kepala_spi',
    ];

    public function index(Request $request)
    {
        $search = $request->input('search');
        $role = $request->input('role');
        $departemenId = $request->input('departemen_id');
        $status = $request->input('status'); // 'aktif' | 'nonaktif'

        $query = User::with(['departemen']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        if ($role && in_array($role, self::ROLE_TERSEDIA, true)) {
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
        $departemens = Departemen::orderBy('nama_departemen')->get();
        return view('sdm.pengguna.create', compact('departemens'));
    }

    public function store(StoreUserRequest $request)
    {
        $data = $request->validated();
        $data['password'] = Hash::make($data['password']);
        $data['is_active'] = $request->boolean('is_active', true);
        $data = $this->bersihkanFieldSesuaiRole($data);

        User::create($data);

        return redirect()->route('sdm.pengguna.index')->with('success', 'Pengguna berhasil ditambahkan.');
    }

    public function edit(User $pengguna)
    {
        $departemens = Departemen::orderBy('nama_departemen')->get();
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

    public function destroy(User $pengguna)
    {
        $pengguna->update(['is_active' => false]);
        return back()->with('success', 'Pengguna berhasil dinonaktifkan.');
    }

    private function bersihkanFieldSesuaiRole(array $data): array
    {
        if (! in_array($data['role'], self::ROLE_DENGAN_DEPARTEMEN, true)) {
            $data['departemen_id'] = null;
        }
        $data['subdepartemen_id'] = null;

        return $data;
    }
}