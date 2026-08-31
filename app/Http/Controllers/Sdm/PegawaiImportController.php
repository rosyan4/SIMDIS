<?php

namespace App\Http\Controllers\Sdm;

use App\Http\Controllers\Controller;
use App\Models\Departemen;
use App\Models\Pegawai;
use App\Models\Subdepartemen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class PegawaiImportController extends Controller
{
    // KF-05 (Import Data Pegawai). Target import adalah tabel pegawais
    // (data master), bukan akun User — jadi tidak ada lagi field "role"
    // atau penugasan manajer/asisten manajer di sini.
    private const TARGET_FIELDS = [
        'nik'              => 'NIK',
        'nama'             => 'Nama Pegawai',
        'jenis_pegawai'    => 'Jenis Pegawai',
        'jabatan'          => 'Jabatan',
        'departemen'       => 'Departemen',
        'subdepartemen'    => 'Subdepartemen',
        'no_telepon'       => 'No. Telepon',
        'email'            => 'Email',
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
     * Langkah 3: proses import sesuai pemetaan kolom yang dipilih Admin SDM.
     */
    public function confirm(Request $request)
    {
        $request->validate([
            'path' => ['required', 'string'],
            'mapping.nik' => ['required'],
            'mapping.nama' => ['required'],
            'mapping.departemen' => ['required'],
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

                $nik = trim((string) ($row[$mapping['nik']] ?? ''));
                $nama = trim((string) ($row[$mapping['nama']] ?? ''));
                $namaDept = $this->ambilKolom($row, $mapping, 'departemen');
                $namaSub = $this->ambilKolom($row, $mapping, 'subdepartemen');
                $jenisRaw = strtolower($this->ambilKolom($row, $mapping, 'jenis_pegawai') ?? 'pegawai');
                $jabatan = $this->ambilKolom($row, $mapping, 'jabatan') ?? '-';
                $noTelepon = $this->ambilKolom($row, $mapping, 'no_telepon');
                $email = $this->ambilKolom($row, $mapping, 'email');

                if ($nik === '' || $nama === '') {
                    $gagal[] = "Baris {$baris}: NIK/nama kosong, dilewati.";
                    continue;
                }

                if (Pegawai::where('nik', $nik)->exists()) {
                    $gagal[] = "Baris {$baris}: NIK {$nik} sudah terdaftar, dilewati.";
                    continue;
                }

                // Departemen WAJIB (kolom departemen_id di migration pegawais tidak nullable).
                $departemen = $namaDept ? $this->cariDepartemen($namaDept) : null;
                if (! $departemen) {
                    $gagal[] = "Baris {$baris}: departemen '{$namaDept}' tidak dikenali sistem atau kosong, baris dilewati.";
                    continue;
                }

                $subdepartemen = $namaSub ? $this->cariSubdepartemen($namaSub, $namaDept) : null;
                if ($namaSub && ! $subdepartemen) {
                    $gagal[] = "Baris {$baris}: subdepartemen '{$namaSub}' tidak dikenali sistem, pegawai dibuat tanpa penempatan subdepartemen.";
                }

                $jenisPegawai = in_array($jenisRaw, ['pegawai', 'pekerja_lapangan'], true) ? $jenisRaw : 'pegawai';

                Pegawai::create([
                    'nik'              => $nik,
                    'nama_pegawai'     => $nama,
                    'jenis_pegawai'    => $jenisPegawai,
                    'jabatan'          => $jabatan,
                    'departemen_id'    => $departemen->id,
                    'subdepartemen_id' => $subdepartemen?->id,
                    'no_telepon'       => $noTelepon,
                    'email'            => $email,
                    'status'           => 'aktif',
                ]);

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
     * Tebak otomatis kolom Excel mana yang cocok dengan tiap field sistem.
     */
    private function suggestMapping(array $headers): array
    {
        $keywords = [
            'nik'           => ['nik'],
            'nama'          => ['nama', 'name'],
            'jenis_pegawai' => ['jenis', 'tipe'],
            'jabatan'       => ['jabatan', 'posisi'],
            'subdepartemen' => ['subdepartemen', 'sub departemen', 'sub-departemen', 'unit'],
            'departemen'    => ['departemen', 'department', 'divisi'],
            'no_telepon'    => ['telepon', 'telp', 'hp', 'phone'],
            'email'         => ['email', 'e-mail'],
        ];

        $suggestion = [];
        $usedHeaders = [];

        // Tahap 1: cocokkan header yang PERSIS SAMA dengan kata kunci dulu.
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

        // Tahap 2: untuk field yang belum ketemu, cari yang MENGANDUNG kata kunci.
        // Field "departemen" sengaja melewati header yang mengandung "sub", supaya
        // tidak salah tangkap kolom "Subdepartemen".
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
            $q->whereRaw('LOWER(kode_departemen) = ?', [strtolower($namaDept)])
              ->orWhereRaw('LOWER(nama_departemen) = ?', [strtolower($namaDept)])
              ->orWhereRaw('LOWER(nama_departemen) LIKE ?', ['%' . $normal . '%']);
        })->first();
    }

    private function cariSubdepartemen(string $namaSub, ?string $namaDept): ?Subdepartemen
    {
        $normalSub = $this->normalisasiNamaOrganisasi($namaSub);

        $query = Subdepartemen::where(function ($q) use ($namaSub, $normalSub) {
            $q->whereRaw('LOWER(kode_subdepartemen) = ?', [strtolower($namaSub)])
              ->orWhereRaw('LOWER(nama_subdepartemen) = ?', [strtolower($namaSub)])
              ->orWhereRaw('LOWER(nama_subdepartemen) LIKE ?', ['%' . $normalSub . '%']);
        });

        // Kalau nama departemen ditemukan, pakai untuk mempersempit pencarian.
        // Kalau tidak, tetap lanjut cari subdepartemen tanpa filter itu (fallback),
        // supaya 1 kesalahan ketik departemen tidak menggagalkan seluruh baris.
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