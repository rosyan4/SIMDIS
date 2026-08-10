<?php

namespace Database\Seeders;

use App\Models\Departemen;
use App\Models\Subdepartemen;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DepartemenSeeder extends Seeder
{
    public function run(): void
    {
        $strukturDummy = [
            'Departemen Produksi' => [
                'kode' => 'PRD',
                'subdepartemens' => [
                    'Subdepartemen Pengolahan Air' => 'OLA',
                    'Subdepartemen Distribusi' => 'DIST',
                ],
            ],
            'Departemen Teknik' => [
                'kode' => 'TEK',
                'subdepartemens' => [
                    'Subdepartemen Perawatan Jaringan' => 'PJR',
                    'Subdepartemen Instalasi' => 'INST',
                ],
            ],
            'Departemen Keuangan' => [
                'kode' => 'KEU',
                'subdepartemens' => [
                    'Subdepartemen Penagihan' => 'TAG',
                    'Subdepartemen Akuntansi' => 'AKT',
                ],
            ],
            'Departemen Umum' => [
                'kode' => 'UMM',
                'subdepartemens' => [
                    'Subdepartemen Rumah Tangga' => 'RT',
                    'Subdepartemen Keamanan' => 'KAM',
                ],
            ],
        ];

        foreach ($strukturDummy as $namaDepartemen => $detail) {
            $manajer = User::create([
                'name' => 'Manajer ' . str_replace('Departemen ', '', $namaDepartemen),
                'email' => 'manajer.' . Str::slug(str_replace('Departemen ', '', $namaDepartemen)) . '@tirtamayang.test',
                'password' => Hash::make('password'),
                'role' => 'manajer_departemen',
            ]);

            $departemen = Departemen::create([
                'nama_departemen' => $namaDepartemen,
                'kode' => $detail['kode'],
                'manajer_id' => $manajer->id,
            ]);

            foreach ($detail['subdepartemens'] as $namaSub => $kodeSub) {
                $asmen = User::create([
                    'name' => 'Asmen ' . $namaSub,
                    'email' => 'asmen.' . Str::slug($namaSub) . '@tirtamayang.test',
                    'password' => Hash::make('password'),
                    'role' => 'asisten_manajer',
                ]);

                $subdepartemen = Subdepartemen::create([
                    'departemen_id' => $departemen->id,
                    'nama_subdepartemen' => $namaSub,
                    'kode' => $kodeSub,
                    'asisten_manajer_id' => $asmen->id,
                ]);

                $asmen->update(['subdepartemen_id' => $subdepartemen->id]);
            }
        }
    }
}