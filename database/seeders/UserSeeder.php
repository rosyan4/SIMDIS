<?php

namespace Database\Seeders;

use App\Models\Departemen;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $defaultPassword = Hash::make('password123'); // ganti/hapus sebelum production

            // 1. Admin SDM
            User::create([
                'name'                  => 'Admin SDM',
                'email'                 => 'adminsdm@tirtamayang.co.id',
                'password'              => $defaultPassword,
                'role'                  => 'admin_sdm',
                'departemen_id'         => null,
                'subdepartemen_id'      => null,
                'is_active'             => true,
                'must_change_password'  => true,
            ]);

            // 2. Admin Departemen
            Departemen::all()->each(function (Departemen $departemen) use ($defaultPassword) {
                $slug = str($departemen->nama_departemen)->slug('.');

                User::create([
                    'name'                  => "Admin Departemen {$departemen->nama_departemen}",
                    'email'                 => "admindept.{$slug}@tirtamayang.co.id",
                    'password'              => $defaultPassword,
                    'role'                  => 'admin_departemen',
                    'departemen_id'         => $departemen->id,
                    'subdepartemen_id'      => null,
                    'is_active'             => true,
                    'must_change_password'  => true,
                ]);
            });

            // 3. Manajer Departemen 
            $kodeDepartemenBermanajer = ['IT', 'PGD', 'SDM', 'BSN1', 'BSN2', 'KEU', 'PEL'];

            Departemen::whereIn('kode_departemen', $kodeDepartemenBermanajer)
                ->get()
                ->each(function (Departemen $departemen) use ($defaultPassword) {
                    $slug = str($departemen->nama_departemen)->slug('.');

                    User::create([
                        'name'                  => "Manajer {$departemen->nama_departemen}",
                        'email'                 => "manajer.{$slug}@tirtamayang.co.id",
                        'password'              => $defaultPassword,
                        'role'                  => 'manajer_departemen',
                        'departemen_id'         => $departemen->id,
                        'subdepartemen_id'      => null,
                        'is_active'             => true,
                        'must_change_password'  => true,
                    ]);
                });

            // 4. Senior Manajer Sekretaris Perusahaan
            $sekretariat = Departemen::where('kode_departemen', 'SEK')->first();
            User::create([
                'name'                  => 'Senior Manajer Sekretaris Perusahaan',
                'email'                 => 'seniormanajer.sekretariat-perusahaan@tirtamayang.co.id',
                'password'              => $defaultPassword,
                'role'                  => 'senior_manajer_sekper',
                'departemen_id'         => $sekretariat->id,
                'subdepartemen_id'      => null,
                'is_active'             => true,
                'must_change_password'  => true,
            ]);

            // 5. Kepala SPI 
            $spi = Departemen::where('kode_departemen', 'SPI')->first();
            User::create([
                'name'                  => 'Kepala SPI',
                'email'                 => 'kepala.spi@tirtamayang.co.id',
                'password'              => $defaultPassword,
                'role'                  => 'kepala_spi',
                'departemen_id'         => $spi->id,
                'subdepartemen_id'      => null,
                'is_active'             => true,
                'must_change_password'  => true,
            ]);

            // 6. Direksi 
            User::create([
                'name'                  => 'Direktur Teknik',
                'email'                 => 'direktur.teknik@tirtamayang.co.id',
                'password'              => $defaultPassword,
                'role'                  => 'direktur_teknik',
                'departemen_id'         => null,
                'subdepartemen_id'      => null,
                'is_active'             => true,
                'must_change_password'  => true,
            ]);

            User::create([
                'name'                  => 'Direktur Administrasi & Keuangan',
                'email'                 => 'direktur.administrasi-keuangan@tirtamayang.co.id',
                'password'              => $defaultPassword,
                'role'                  => 'direktur_administrasi_keuangan',
                'departemen_id'         => null,
                'subdepartemen_id'      => null,
                'is_active'             => true,
                'must_change_password'  => true,
            ]);

            User::create([
                'name'                  => 'Direktur Utama',
                'email'                 => 'direktur.utama@tirtamayang.co.id',
                'password'              => $defaultPassword,
                'role'                  => 'direktur_utama',
                'departemen_id'         => null,
                'subdepartemen_id'      => null,
                'is_active'             => true,
                'must_change_password'  => true,
            ]);
        });
    }
}