<?php

namespace Database\Seeders;

use App\Models\Departemen;
use App\Models\Subdepartemen;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Butuh DepartemenSeeder (departemens + subdepartemens) sudah dijalankan
     * lebih dulu. Urutan di DatabaseSeeder.php:
     *
     *   $this->call([
     *       DepartemenSeeder::class,
     *       UserSeeder::class,
     *   ]);
     *
     * Semua akun di sini pakai password default yang sama ('password123'),
     * jadi must_change_password di-set true supaya wajib diganti saat login
     * pertama (PasswordChangeController).
     */
    public function run(): void
    {
        DB::transaction(function () {
            $defaultPassword = Hash::make('password123'); // ganti/hapus sebelum production

            // 1. Admin SDM — tidak terikat departemen/subdepartemen manapun
            User::create([
                'name'                  => 'Admin SDM',
                'username'              => 'admin.sdm',
                'email'                 => 'adminsdm@tirtamayang.co.id',
                'password'              => $defaultPassword,
                'role'                  => 'admin_sdm',
                'departemen_id'         => null,
                'subdepartemen_id'      => null,
                'is_active'             => true,
                'must_change_password'  => true,
            ]);

            // 2. Admin Departemen & Manajer Departemen — satu pasang per departemen
            Departemen::all()->each(function (Departemen $departemen) use ($defaultPassword) {
                $slug = str($departemen->nama_departemen)->slug('.');

                User::create([
                    'name'                  => "Admin Departemen {$departemen->nama_departemen}",
                    'username'              => "admin.dept.{$slug}",
                    'email'                 => "admindept.{$slug}@tirtamayang.co.id",
                    'password'              => $defaultPassword,
                    'role'                  => 'admin_departemen',
                    'departemen_id'         => $departemen->id,
                    'subdepartemen_id'      => null,
                    'is_active'             => true,
                    'must_change_password'  => true,
                ]);

                User::create([
                    'name'                  => "Manajer {$departemen->nama_departemen}",
                    'username'              => "manajer.{$slug}",
                    'email'                 => "manajer.{$slug}@tirtamayang.co.id",
                    'password'              => $defaultPassword,
                    'role'                  => 'manajer_departemen',
                    'departemen_id'         => $departemen->id,
                    'subdepartemen_id'      => null,
                    'status_manajer'        => 'aktif',
                    'is_active'             => true,
                    'must_change_password'  => true,
                ]);
            });

            // 3. Asisten Manajer — satu per subdepartemen
            Subdepartemen::all()->each(function (Subdepartemen $subdepartemen) use ($defaultPassword) {
                $slug = str($subdepartemen->nama_subdepartemen)->slug('.');

                User::create([
                    'name'                  => "Asisten Manajer {$subdepartemen->nama_subdepartemen}",
                    'username'              => "asmen.{$slug}",
                    'email'                 => "asmen.{$slug}@tirtamayang.co.id",
                    'password'              => $defaultPassword,
                    'role'                  => 'asisten_manajer',
                    'departemen_id'         => null,
                    'subdepartemen_id'      => $subdepartemen->id,
                    'is_active'             => true,
                    'must_change_password'  => true,
                ]);
            });
        });
    }
}