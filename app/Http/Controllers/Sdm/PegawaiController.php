<?php

namespace App\Http\Controllers\Sdm;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePegawaiRequest;
use App\Models\Departemen;
use App\Models\Subdepartemen;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PegawaiController extends Controller
{
    public function index()
    {
        $tanpaSubdepartemen = User::whereNull('subdepartemen_id')->orderBy('name')->get();

        $departemens = Departemen::with(['manajer', 'subdepartemens' => function ($q) {
            $q->with(['asistenManajer', 'pegawais' => fn ($q) => $q->orderBy('name')]);
        }])->orderBy('nama_departemen')->get();

        return view('sdm.pegawai.index', compact('departemens', 'tanpaSubdepartemen'));
    }

    public function create()
    {
        $departemens = Departemen::with('subdepartemens')->orderBy('nama_departemen')->get();
        return view('sdm.pegawai.create', compact('departemens'));
    }

    public function store(StorePegawaiRequest $request)
    {
        $data = $request->validated();
        $data['password'] = Hash::make($data['password']);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['must_change_password'] = true;

        $departemenId = $data['departemen_id'] ?? null;
        $subdepartemenId = $data['subdepartemen_id'] ?? null;
        unset($data['departemen_id']);

        // Manajer tidak terikat subdepartemen tertentu, jadi subdepartemen_id-nya null
        $data['subdepartemen_id'] = $data['role'] === 'manajer_departemen' ? null : $subdepartemenId;

        DB::transaction(function () use ($data, $departemenId, $subdepartemenId) {
            $user = User::create($data);
            $this->syncJabatan($user, $data['role'], $departemenId, $subdepartemenId);
        });

        return redirect()->route('sdm.pegawai.index')->with('success', 'Pegawai berhasil ditambahkan.');
    }

    public function edit(User $pegawai)
    {
        $departemens = Departemen::with('subdepartemens')->orderBy('nama_departemen')->get();

        $currentDepartemenId = match ($pegawai->role) {
            'manajer_departemen' => Departemen::where('manajer_id', $pegawai->id)->value('id'),
            'asisten_manajer', 'pegawai' => $pegawai->subdepartemen?->departemen_id,
            default => null,
        };

        return view('sdm.pegawai.edit', compact('pegawai', 'departemens', 'currentDepartemenId'));
    }

    public function update(StorePegawaiRequest $request, User $pegawai)
    {
        $data = $request->validated();

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $data['is_active'] = $request->boolean('is_active');

        $departemenId = $data['departemen_id'] ?? null;
        $subdepartemenId = $data['subdepartemen_id'] ?? null;
        unset($data['departemen_id']);

        $data['subdepartemen_id'] = $data['role'] === 'manajer_departemen' ? null : $subdepartemenId;

        DB::transaction(function () use ($pegawai, $data, $departemenId, $subdepartemenId) {
            $pegawai->update($data);
            $this->syncJabatan($pegawai, $data['role'], $departemenId, $subdepartemenId);
        });

        return redirect()->route('sdm.pegawai.index')->with('success', 'Data pegawai berhasil diperbarui.');
    }

    public function destroy(User $pegawai)
    {
        DB::transaction(function () use ($pegawai) {
            $pegawai->update(['is_active' => false]);
            // Lepas jabatan kalau yang dinonaktifkan sedang menjabat
            Departemen::where('manajer_id', $pegawai->id)->update(['manajer_id' => null]);
            Subdepartemen::where('asisten_manajer_id', $pegawai->id)->update(['asisten_manajer_id' => null]);
        });

        return back()->with('success', 'Pegawai berhasil dinonaktifkan.');
    }

    public function importForm()
    {
        return view('sdm.pegawai.import');
    }

    public function import(Request $request)
    {
        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,csv', 'max:5120']]);

        $import = new \App\Imports\PegawaiImport();
        \Maatwebsite\Excel\Facades\Excel::import($import, $request->file('file'));

        $errors = $import->errors();
        if ($errors->isNotEmpty()) {
            $pesan = $errors->map(fn ($e) => "Baris {$e->row()}: " . $e->errors()[0])->implode(' | ');
            return back()->with('warning', "Sebagian data gagal diimpor — {$pesan}");
        }

        return redirect()->route('sdm.pegawai.index')->with('success', 'Data pegawai berhasil diimpor.');
    }

    /**
     * Sinkronkan jabatan manajerial: lepas jabatan lama milik $user (di manapun),
     * lalu pasang jabatan baru sesuai role. Kalau posisi tujuan sudah ada penjabatnya,
     * penjabat lama otomatis diturunkan jadi 'pegawai'.
     */
    private function syncJabatan(User $user, string $role, ?int $departemenId, ?int $subdepartemenId): void
    {
        Departemen::where('manajer_id', $user->id)->update(['manajer_id' => null]);
        Subdepartemen::where('asisten_manajer_id', $user->id)->update(['asisten_manajer_id' => null]);

        if ($role === 'manajer_departemen' && $departemenId) {
            $departemen = Departemen::find($departemenId);

            if ($departemen->manajer_id && $departemen->manajer_id != $user->id) {
                User::where('id', $departemen->manajer_id)->update(['role' => 'pegawai']);
            }

            $departemen->update(['manajer_id' => $user->id]);
        }

        if ($role === 'asisten_manajer' && $subdepartemenId) {
            $subdepartemen = Subdepartemen::find($subdepartemenId);

            if ($subdepartemen->asisten_manajer_id && $subdepartemen->asisten_manajer_id != $user->id) {
                User::where('id', $subdepartemen->asisten_manajer_id)->update(['role' => 'pegawai']);
            }

            $subdepartemen->update(['asisten_manajer_id' => $user->id]);
        }
    }
}