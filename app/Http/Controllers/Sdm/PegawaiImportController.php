<?php

namespace App\Http\Controllers\Sdm;

use App\Http\Controllers\Controller;
use App\Models\Departemen;
use App\Models\Subdepartemen;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class PegawaiImportController extends Controller
{
    private const TARGET_FIELDS = [
        'nama' => 'Nama',
        'email' => 'Email',
        'departemen' => 'Departemen',
        'subdepartemen' => 'Subdepartemen',
        'role' => 'Role',
    ];

    public function form()
    {
        return view('sdm.pegawai.import');
    }

    /**
     * Langkah 2: baca header + beberapa baris awal file, tampilkan form pemetaan kolom.
     */
    public function preview(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'],
        ]);

        $path = $request->file('file')->store('temp-imports', 'local');
        $rows = Excel::toArray(null, Storage::disk('local')->path($path))[0] ?? [];

        if (count($rows) < 2) {
            Storage::disk('local')->delete($path);
            return back()->withErrors(['file' => 'File kosong atau tidak ada data setelah header.']);
        }

        $headers = array_map(fn ($h) => trim((string) $h), $rows[0]);

        return view('sdm.pegawai.import-preview', [
            'path' => $path,
            'headers' => $headers,
            'previewRows' => array_slice($rows, 1, 5),
            'totalRows' => count($rows) - 1,
            'targetFields' => self::TARGET_FIELDS,
            'suggestedMapping' => $this->suggestMapping($headers),
        ]);
    }

    /**
     * Langkah 3: proses import sesuai pemetaan kolom yang dipilih admin.
     */
    public function confirm(Request $request)
    {
        $request->validate([
            'path' => ['required', 'string'],
            'mapping.nama' => ['required'],
            'mapping.email' => ['required'],
        ]);

        $path = $request->input('path');

        if (! Storage::disk('local')->exists($path)) {
            return back()->withErrors(['file' => 'Sesi import sudah kedaluwarsa, silakan upload ulang filenya.']);
        }

        $mapping = $request->input('mapping');
        $rows = Excel::toArray(null, Storage::disk('local')->path($path))[0] ?? [];
        $dataRows = array_slice($rows, 1);

        $sukses = 0;
        $gagal = [];

        DB::transaction(function () use ($dataRows, $mapping, &$sukses, &$gagal) {
            foreach ($dataRows as $i => $row) {
                $baris = $i + 2;

                $nama = trim((string) ($row[$mapping['nama']] ?? ''));
                $email = trim((string) ($row[$mapping['email']] ?? ''));
                $namaDept = $this->ambilKolom($row, $mapping, 'departemen');
                $namaSub = $this->ambilKolom($row, $mapping, 'subdepartemen');
                $roleRaw = strtolower($this->ambilKolom($row, $mapping, 'role') ?? 'pegawai');

                if ($nama === '' || $email === '') {
                    $gagal[] = "Baris {$baris}: nama/email kosong, dilewati.";
                    continue;
                }

                if (User::where('email', $email)->exists()) {
                    $gagal[] = "Baris {$baris}: email {$email} sudah terdaftar, dilewati.";
                    continue;
                }

                $role = in_array($roleRaw, ['pegawai', 'manajer_departemen', 'asisten_manajer', 'admin_sdm'])
                    ? $roleRaw : 'pegawai';

                // Dicari SEKALI saja, dipakai ulang untuk create + penugasan jabatan.
                $subdepartemen = $namaSub ? $this->cariSubdepartemen($namaSub, $namaDept) : null;
                if ($namaSub && ! $subdepartemen) {
                    $gagal[] = "Baris {$baris}: subdepartemen '{$namaSub}' tidak dikenali sistem, pegawai dibuat tanpa penempatan subdepartemen.";
                }

                $user = User::create([
                    'name' => $nama,
                    'email' => $email,
                    'password' => Hash::make('password123'),
                    'role' => $role,
                    'subdepartemen_id' => $role === 'manajer_departemen' ? null : $subdepartemen?->id,
                    'is_active' => true,
                    'must_change_password' => true,
                ]);

                if ($role === 'manajer_departemen' && $namaDept) {
                    // Pakai pencarian fuzzy yang sama seperti subdepartemen (kenali kode/singkatan).
                    $departemen = $this->cariDepartemen($namaDept);

                    if ($departemen && ! $departemen->manajer_id) {
                        $departemen->update(['manajer_id' => $user->id]);
                    } elseif ($departemen) {
                        $gagal[] = "Baris {$baris}: {$namaDept} sudah punya Manajer, akun {$nama} dibuat tapi tidak ditugaskan otomatis.";
                    } else {
                        $gagal[] = "Baris {$baris}: departemen '{$namaDept}' tidak dikenali sistem, akun {$nama} dibuat tanpa penugasan.";
                    }
                }

                if ($role === 'asisten_manajer' && $subdepartemen && ! $subdepartemen->asisten_manajer_id) {
                    $subdepartemen->update(['asisten_manajer_id' => $user->id]);
                } elseif ($role === 'asisten_manajer' && $subdepartemen) {
                    $gagal[] = "Baris {$baris}: {$namaSub} sudah punya Asisten Manajer, akun {$nama} dibuat tapi tidak ditugaskan otomatis.";
                }

                $sukses++;
            }
        });

        Storage::disk('local')->delete($path);

        return redirect()->route('sdm.pegawai.index')
            ->with('success', "{$sukses} pegawai berhasil diimpor." . (count($gagal) ? ' ' . count($gagal) . ' baris perlu dicek.' : ''))
            ->with('warning', count($gagal) ? implode(' | ', array_slice($gagal, 0, 10)) : null);
    }

    private function ambilKolom(array $row, array $mapping, string $field): ?string
    {
        if (empty($mapping[$field]) && $mapping[$field] !== '0') {
            return null;
        }

        return trim((string) ($row[$mapping[$field]] ?? '')) ?: null;
    }

    /**
     * Tebak otomatis kolom Excel mana yang cocok dengan tiap field sistem,
     * supaya admin tidak perlu pilih manual kalau nama kolomnya sudah mirip.
     */
    private function suggestMapping(array $headers): array
    {
        $keywords = [
            'nama' => ['nama', 'name'],
            'email' => ['email', 'e-mail'],
            'subdepartemen' => ['subdepartemen', 'sub departemen', 'sub-departemen', 'unit'],
            'departemen' => ['departemen', 'department', 'divisi'],
            'role' => ['role', 'jabatan', 'posisi'],
        ];

        $suggestion = [];
        $usedHeaders = [];

        // Tahap 1: cocokkan header yang PERSIS SAMA dengan kata kunci dulu (paling akurat).
        foreach ($keywords as $field => $terms) {
            foreach ($headers as $index => $header) {
                if (in_array($index, $usedHeaders)) continue;

                $h = strtolower(trim($header));
                foreach ($terms as $term) {
                    if ($h === $term) {
                        $suggestion[$field] = $index;
                        $usedHeaders[] = $index;
                        continue 3;
                    }
                }
            }
        }

        // Tahap 2: untuk field yang belum ketemu, baru cari yang MENGANDUNG kata kunci.
        // Field "departemen" sengaja melewati header yang mengandung "sub", supaya
        // tidak salah tangkap kolom "Subdepartemen" (yang juga mengandung kata "departemen").
        foreach ($keywords as $field => $terms) {
            if (isset($suggestion[$field])) continue;

            foreach ($headers as $index => $header) {
                if (in_array($index, $usedHeaders)) continue;

                $h = strtolower(trim($header));
                foreach ($terms as $term) {
                    if (str_contains($h, $term)) {
                        if ($field === 'departemen' && str_contains($h, 'sub')) {
                            continue;
                        }
                        $suggestion[$field] = $index;
                        $usedHeaders[] = $index;
                        continue 3;
                    }
                }
            }
        }

        return $suggestion;
    }

    private function cariDepartemen(string $namaDept): ?Departemen
    {
        $normal = $this->normalisasiNamaOrganisasi($namaDept);

        return Departemen::where(function ($q) use ($namaDept, $normal) {
            $q->whereRaw('LOWER(kode) = ?', [strtolower($namaDept)])
            ->orWhereRaw('LOWER(nama_departemen) = ?', [strtolower($namaDept)])
            ->orWhereRaw('LOWER(nama_departemen) LIKE ?', ['%' . $normal . '%']);
        })->first();
    }

    private function cariSubdepartemen(string $namaSub, ?string $namaDept): ?Subdepartemen
    {
        $normalSub = $this->normalisasiNamaOrganisasi($namaSub);

        $query = Subdepartemen::where(function ($q) use ($namaSub, $normalSub) {
            $q->whereRaw('LOWER(kode) = ?', [strtolower($namaSub)])
            ->orWhereRaw('LOWER(nama_subdepartemen) = ?', [strtolower($namaSub)])
            ->orWhereRaw('LOWER(nama_subdepartemen) LIKE ?', ['%' . $normalSub . '%']);
        });

        // Kalau nama departemen ditemukan, pakai untuk mempersempit pencarian.
        // Kalau departemen TIDAK ditemukan, tetap lanjut cari subdepartemen tanpa filter itu
        // (fallback), supaya 1 kesalahan ketik departemen tidak menggagalkan seluruh baris.
        if ($namaDept) {
            $departemen = $this->cariDepartemen($namaDept);
            if ($departemen) {
                $query->where('departemen_id', $departemen->id);
            }
        }

        return $query->first();
    }

    private function normalisasiNamaOrganisasi(string $nama): string
    {
        $nama = strtolower(trim($nama));
        $nama = preg_replace('/^(departemen|dept\.?|divisi|bagian)\s+/', '', $nama);
        $nama = preg_replace('/^(sub\s*-?\s*departemen|subdept\.?|unit)\s+/', '', $nama);
        return trim($nama);
    }
}