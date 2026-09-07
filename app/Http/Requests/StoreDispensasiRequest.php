<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreDispensasiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::user()?->isAdminDepartemen() ?? false;
    }

    public function rules(): array
    {
        $departemenId = Auth::user()->departemen_id;

        return [
            'pegawai_id' => [
                'required',
                Rule::exists('pegawais', 'id')->where('departemen_id', $departemenId)->where('status', 'aktif'),
            ],
            'tanggal_dispensasi' => ['required', 'date'],
            'waktu_dispensasi'   => ['required', 'array', 'min:1'],
            'waktu_dispensasi.*' => [Rule::in(['T', 'TBO', 'TBI', 'CP'])],
            'keterangan'         => ['required', 'string'],
            'bukti_pendukung'    => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'pegawai_id.exists'    => 'Pegawai tidak ditemukan atau bukan bagian dari departemen Anda.',
            'waktu_dispensasi.required' => 'Pilih minimal satu waktu dispensasi.',
            'waktu_dispensasi.min'      => 'Pilih minimal satu waktu dispensasi.',
        ];
    }
}