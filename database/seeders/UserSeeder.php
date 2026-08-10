<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Admin SDM',
            'email' => 'sdm@tirtamayang.test',
            'password' => Hash::make('password'),
            'role' => 'admin_sdm',
        ]);
    }
}