<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePegawaiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public const PILIHAN_JABATAN = [
        'Staf',
        'Asisten Manajer',
        'Asisten Bidang',
        'Manajer',
        'Senior Manajer',
        'Kepala SPI',
        'Sekretaris SPI',
    ];

    public const PILIHAN_POSISI = [
        'staf',
        'asisten_manajer_bidang',
        'manajer',
        'senior_manajer_sekper',
        'senior_manajer_bisnis',
        'senior_manajer_keuangan_pelanggan',
        'senior_manajer_produksi_distribusi',
        'senior_manajer_perencanaan_aset',
        'kepala_spi',
        'sekretaris_spi',
    ];

    public function rules(): array
    {
        $pegawaiId = $this->route('pegawai')?->id;

        return [
            'nik'              => ['required', 'string', 'max:20', Rule::unique('pegawais', 'nik')->ignore($pegawaiId)],
            'nama_pegawai'     => ['required', 'string', 'max:100'],
            'jabatan'          => ['required', Rule::in(self::PILIHAN_JABATAN)],
            'posisi'           => ['required', Rule::in(self::PILIHAN_POSISI)],
            'departemen_id'    => ['required', 'exists:departemens,id'],
            'subdepartemen_id' => ['nullable', 'exists:subdepartemens,id'],
            'no_telepon'       => ['nullable', 'string', 'max:20'],
            'email'            => ['nullable', 'email', 'max:100'],
            'status'           => ['required', Rule::in(['aktif', 'nonaktif'])],
        ];
    }
}