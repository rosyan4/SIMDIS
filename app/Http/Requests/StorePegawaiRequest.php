<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePegawaiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdminSdm();
    }

    public function rules(): array
    {
        $userId = $this->route('pegawai')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($userId)],
            'password' => [$userId ? 'nullable' : 'required', 'min:8'],
            'role' => ['required', Rule::in(['pegawai', 'manajer_departemen', 'asisten_manajer', 'admin_sdm'])],
            'departemen_id' => ['nullable', 'required_if:role,manajer_departemen', 'exists:departemens,id'],
            'subdepartemen_id' => ['nullable', 'required_if:role,asisten_manajer', 'exists:subdepartemens,id'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'departemen_id.required_if' => 'Pilih departemen yang akan dipimpin.',
            'subdepartemen_id.required_if' => 'Pilih subdepartemen yang akan ditangani.',
        ];
    }
}