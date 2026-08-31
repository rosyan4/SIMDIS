<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePegawaiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Otorisasi role Admin SDM ditangani middleware route
    }

    public function rules(): array
    {
        $pegawaiId = $this->route('pegawai')?->id;

        return [
            'nik'              => ['required', 'string', 'max:20', Rule::unique('pegawais', 'nik')->ignore($pegawaiId)],
            'nama_pegawai'     => ['required', 'string', 'max:100'],
            'jenis_pegawai'    => ['required', Rule::in(['pegawai', 'pekerja_lapangan'])],
            'jabatan'          => ['required', 'string', 'max:100'],
            'departemen_id'    => ['required', 'exists:departemens,id'],
            'subdepartemen_id' => ['nullable', 'exists:subdepartemens,id'],
            'no_telepon'       => ['nullable', 'string', 'max:20'],
            'email'            => ['nullable', 'email', 'max:100'],
            'status'           => ['required', Rule::in(['aktif', 'nonaktif'])],
        ];
    }
}