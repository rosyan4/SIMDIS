<?php

namespace Database\Seeders;

use App\Models\Departemen;
use App\Models\Subdepartemen;
use Illuminate\Database\Seeder;

class DepartemenSeeder extends Seeder
{
    public function run(): void
    {
        $struktur = [
            'SPI' => [
                'kode' => 'SPI',
                'subdepartemens' => [],
            ],
            'Sekretariat Perusahaan' => [
                'kode' => 'SEK',
                'subdepartemens' => [
                    'Bidang Sekretariat & Rumah Tangga' => 'SEK-RT',
                    'Bidang Humas' => 'SEK-HMS',
                    'Bidang Keluhan Pelanggan' => 'SEK-KP',
                    'Bidang Hukum & Pengamanan' => 'SEK-HK',
                ],
            ],
            'IT' => [
                'kode' => 'IT',
                'subdepartemens' => [
                    'Aplikasi & Pengamanan IT' => 'IT-APP',
                    'Infrastruktur IT' => 'IT-INF',
                ],
            ],
            'Pengadaan' => [
                'kode' => 'PGD',
                'subdepartemens' => [
                    'Administrasi Pengadaan' => 'PGD-ADM',
                ],
            ],
            'SDM' => [
                'kode' => 'SDM',
                'subdepartemens' => [
                    'Personalia & Payroll' => 'SDM-PER',
                    'Pelatihan & Pengembangan' => 'SDM-DIK',
                ],
            ],
            'Bisnis Wilayah I' => [
                'kode' => 'BSN1',
                'subdepartemens' => [
                    'Pemasaran Wilayah I' => 'BSN1-PMR',
                    'Sambung Baru Wilayah I' => 'BSN1-SB',
                ],
            ],
            'Bisnis Wilayah II' => [
                'kode' => 'BSN2',
                'subdepartemens' => [
                    'Pemasaran Wilayah II' => 'BSN2-PMR',
                    'Sambung Baru Wilayah II' => 'BSN2-SB',
                ],
            ],
            'Keuangan' => [
                'kode' => 'KEU',
                'subdepartemens' => [
                    'Akuntansi' => 'KEU-AKT',
                    'Anggaran' => 'KEU-ANG',
                    'Kas & Perpajakan' => 'KEU-KAS',
                ],
            ],
            'Pengelolaan Pelanggan' => [
                'kode' => 'PEL',
                'subdepartemens' => [
                    'Meter Air' => 'PEL-MTR',
                    'Tunggakan Pelanggan' => 'PEL-TGK',
                    'Baca Meter & Rekening' => 'PEL-BCR',
                ],
            ],
            'Produksi' => [
                'kode' => 'PRD',
                'subdepartemens' => [
                    'Laboratorium' => 'PRD-LAB',
                    'Pengolahan Air I' => 'PRD-OLA1',
                    'Pengolahan Air II' => 'PRD-OLA2',
                    'Pemeliharaan Aset Produksi' => 'PRD-AST',
                ],
            ],
            'Distribusi' => [
                'kode' => 'DIST',
                'subdepartemens' => [
                    'Pengaliran Wilayah I' => 'DIST-AL1',
                    'Pengaliran Wilayah II' => 'DIST-AL2',
                    'Pemeliharaan & Perbaikan Perpipaan' => 'DIST-PIPA',
                ],
            ],
            'Pengawasan Teknik & Pemeliharaan Bangunan' => [
                'kode' => 'PWS',
                'subdepartemens' => [
                    'Pengawasan Teknik & Perizinan' => 'PWS-TEK',
                    'Pemeliharaan Bangunan & K3' => 'PWS-K3',
                ],
            ],
            'Perencanaan dan Database Aset' => [
                'kode' => 'REN',
                'subdepartemens' => [
                    'Perencanaan Aset' => 'REN-AST',
                    'Database Aset & GIS' => 'REN-GIS',
                    'Pergudangan' => 'REN-GDG',
                ],
            ],
        ];

        foreach ($struktur as $namaDepartemen => $detail) {
            $departemen = Departemen::create([
                'kode_departemen' => $detail['kode'],
                'nama_departemen' => $namaDepartemen,
                // Manajer Departemen TIDAK disimpan di sini — ditentukan lewat
                // UserSeeder (users.role = 'manajer_departemen' + departemen_id).
            ]);

            foreach ($detail['subdepartemens'] as $namaSub => $kodeSub) {
                Subdepartemen::create([
                    'departemen_id'       => $departemen->id,
                    'kode_subdepartemen'  => $kodeSub,
                    'nama_subdepartemen'  => $namaSub,
                    // Asisten Manajer TIDAK disimpan di sini — ditentukan lewat
                    // UserSeeder (users.role = 'asisten_manajer' + subdepartemen_id).
                ]);
            }
        }
    }
}