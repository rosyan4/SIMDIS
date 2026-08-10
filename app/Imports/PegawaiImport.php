<?php

namespace App\Imports;

use App\Models\Subdepartemen;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;

class PegawaiImport implements ToCollection, WithHeadingRow
{
    public array $errors = [];
    public int $successCount = 0;

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $baris = $index + 2; // +2 karena heading row + index mulai dari 0

            $validator = Validator::make($row->toArray(), [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'unique:users,email'],
                'role' => ['required', 'in:pegawai,manajer_departemen,asisten_manajer,admin_sdm'],
                'nama_subdepartemen' => ['nullable', 'string'],
            ]);

            if ($validator->fails()) {
                $this->errors[] = "Baris {$baris}: " . $validator->errors()->first();
                continue;
            }

            $subdepartemenId = null;
            if (! empty($row['nama_subdepartemen'])) {
                $subdepartemen = Subdepartemen::where('nama_subdepartemen', trim($row['nama_subdepartemen']))->first();

                if (! $subdepartemen) {
                    $this->errors[] = "Baris {$baris}: Subdepartemen '{$row['nama_subdepartemen']}' tidak ditemukan.";
                    continue;
                }

                $subdepartemenId = $subdepartemen->id;
            }

            User::create([
                'name' => trim($row['name']),
                'email' => trim($row['email']),
                'password' => Hash::make('password123'), // password default, wajib diganti user
                'role' => $row['role'],
                'subdepartemen_id' => $subdepartemenId,
                'is_active' => true,
                'must_change_password' => true,
            ]);

            $this->successCount++;
        }
    }
}