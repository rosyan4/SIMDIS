<?php

namespace Database\Seeders;

use App\Models\Departemen;
use App\Models\Divisi;
use App\Models\Subdepartemen;
use Illuminate\Database\Seeder;

class DepartemenSeeder extends Seeder
{
    public function run(): void
    {
        $divisiDefinisi = [
            'Divisi Perencanaan & Pengelolaan Aset' => [
                'nama_senior_manajer' => 'Senior Manajer Perencanaan & Pengelolaan Aset',
                'direktorat'          => 'Direktur Teknik',
            ],
            'Divisi Produksi & Distribusi' => [
                'nama_senior_manajer' => 'Senior Manajer Produksi & Distribusi',
                'direktorat'          => 'Direktur Teknik',
            ],
            'Divisi Keuangan & Pengelolaan Pelanggan' => [
                'nama_senior_manajer' => 'Senior Manajer Keuangan & Pengelolaan Pelanggan',
                'direktorat'          => 'Direktur Administrasi & Keuangan',
            ],
            'Divisi Bisnis' => [
                'nama_senior_manajer' => 'Senior Manajer Bisnis',
                'direktorat'          => 'Direktur Administrasi & Keuangan',
            ],
        ];

        $divisiIds = [];
        $urutan = 1;
        foreach ($divisiDefinisi as $namaDivisi => $detail) {
            $divisiIds[$namaDivisi] = Divisi::create([
                'nama_divisi'          => $namaDivisi,
                'nama_senior_manajer'  => $detail['nama_senior_manajer'],
                'direktorat'           => $detail['direktorat'],
                'urutan'               => $urutan++,
            ])->id;
        }

        $struktur = [
            'SPI' => [
                'kode' => 'SPI',
                'divisi' => null,
                'subdepartemens' => [],
            ],
            'Sekretariat Perusahaan' => [
                'kode' => 'SEK',
                'divisi' => null,
                'subdepartemens' => [
                    'Bidang Sekretariat & Rumah Tangga' => 'SEK-RT',
                    'Bidang Humas' => 'SEK-HMS',
                    'Bidang Keluhan Pelanggan' => 'SEK-KP',
                    'Bidang Hukum & Pengamanan' => 'SEK-HK',
                ],
            ],
            'IT' => [
                'kode' => 'IT',
                'divisi' => null,
                'subdepartemens' => [
                    'Aplikasi & Pengamanan IT' => 'IT-APP',
                    'Infrastruktur IT' => 'IT-INF',
                ],
            ],
            'Pengadaan' => [
                'kode' => 'PGD',
                'divisi' => null,
                'subdepartemens' => [
                    'Administrasi Pengadaan' => 'PGD-ADM',
                ],
            ],
            'SDM' => [
                'kode' => 'SDM',
                'divisi' => null,
                'subdepartemens' => [
                    'Personalia & Payroll' => 'SDM-PER',
                    'Pelatihan & Pengembangan' => 'SDM-DIK',
                ],
            ],
            'Bisnis Wilayah I' => [
                'kode' => 'BSN1',
                'divisi' => 'Divisi Bisnis',
                'subdepartemens' => [
                    'Pemasaran Wilayah I' => 'BSN1-PMR',
                    'Sambung Baru Wilayah I' => 'BSN1-SB',
                ],
            ],
            'Bisnis Wilayah II' => [
                'kode' => 'BSN2',
                'divisi' => 'Divisi Bisnis',
                'subdepartemens' => [
                    'Pemasaran Wilayah II' => 'BSN2-PMR',
                    'Sambung Baru Wilayah II' => 'BSN2-SB',
                ],
            ],
            'Keuangan' => [
                'kode' => 'KEU',
                'divisi' => 'Divisi Keuangan & Pengelolaan Pelanggan',
                'subdepartemens' => [
                    'Akuntansi' => 'KEU-AKT',
                    'Anggaran' => 'KEU-ANG',
                    'Kas & Perpajakan' => 'KEU-KAS',
                ],
            ],
            'Pengelolaan Pelanggan' => [
                'kode' => 'PEL',
                'divisi' => 'Divisi Keuangan & Pengelolaan Pelanggan',
                'subdepartemens' => [
                    'Meter Air' => 'PEL-MTR',
                    'Tunggakan Pelanggan' => 'PEL-TGK',
                    'Baca Meter & Rekening' => 'PEL-BCR',
                ],
            ],
            'Produksi' => [
                'kode' => 'PRD',
                'divisi' => 'Divisi Produksi & Distribusi',
                'subdepartemens' => [
                    'Laboratorium' => 'PRD-LAB',
                    'Pengolahan Air I' => 'PRD-OLA1',
                    'Pengolahan Air II' => 'PRD-OLA2',
                    'Pemeliharaan Aset Produksi' => 'PRD-AST',
                ],
            ],
            'Distribusi' => [
                'kode' => 'DIST',
                'divisi' => 'Divisi Produksi & Distribusi',
                'subdepartemens' => [
                    'Pengaliran Wilayah I' => 'DIST-AL1',
                    'Pengaliran Wilayah II' => 'DIST-AL2',
                    'Pemeliharaan & Perbaikan Perpipaan' => 'DIST-PIPA',
                ],
            ],
            'Pengawasan Teknik & Pemeliharaan Bangunan' => [
                'kode' => 'PWS',
                'divisi' => 'Divisi Perencanaan & Pengelolaan Aset',
                'subdepartemens' => [
                    'Pengawasan Teknik & Perizinan' => 'PWS-TEK',
                    'Pemeliharaan Bangunan & K3' => 'PWS-K3',
                ],
            ],
            'Perencanaan dan Database Aset' => [
                'kode' => 'REN',
                'divisi' => 'Divisi Perencanaan & Pengelolaan Aset',
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
                'divisi_id'       => $detail['divisi'] ? $divisiIds[$detail['divisi']] : null,
            ]);

            foreach ($detail['subdepartemens'] as $namaSub => $kodeSub) {
                Subdepartemen::create([
                    'departemen_id'       => $departemen->id,
                    'kode_subdepartemen'  => $kodeSub,
                    'nama_subdepartemen'  => $namaSub,
                ]);
            }
        }
    }
}