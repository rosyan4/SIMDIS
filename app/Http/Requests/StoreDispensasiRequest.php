<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDispensasiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isPegawai();
    }

    public function rules(): array
    {
        return [
            'tanggal_dispensasi' => ['required', 'date', 'after_or_equal:today'],
            'jam_mulai' => ['nullable', 'date_format:H:i'],
            'jam_selesai' => ['nullable', 'date_format:H:i', 'after:jam_mulai'],
            'alasan' => ['required', 'string', 'min:5', 'max:1000'],
            'bukti_pendukung' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'tanggal_dispensasi.after_or_equal' => 'Tanggal dispensasi tidak boleh tanggal yang sudah lewat.',
            'jam_selesai.after' => 'Jam selesai harus setelah jam mulai.',
            'alasan.min' => 'Alasan minimal 5 karakter, jelaskan dengan lengkap.',
            'bukti_pendukung.max' => 'Ukuran file maksimal 2MB.',
        ];
    }
}