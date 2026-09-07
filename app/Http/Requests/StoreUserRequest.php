<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public const ROLE_TERSEDIA = [
        'admin_sdm',
        'admin_departemen',
        'manajer_departemen',
        'senior_manajer_sekper',
        'kepala_spi',
        'direktur_teknik',
        'direktur_administrasi_keuangan',
        'direktur_utama',
    ];

    public const ROLE_DENGAN_DEPARTEMEN = [
        'admin_departemen',
        'manajer_departemen',
        'senior_manajer_sekper',
        'kepala_spi',
    ];

    private const ROLE_TUNGGAL_PER_DEPARTEMEN = [
        'manajer_departemen',
        'senior_manajer_sekper',
        'kepala_spi',
    ];

    public function rules(): array
    {
        $userId = $this->route('pengguna')?->id;

        return [
            'name'  => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:100', Rule::unique('users', 'email')->ignore($userId)],
            'password' => [$this->isMethod('post') ? 'required' : 'nullable', 'string', 'min:8', 'confirmed'],
            'role' => ['required', Rule::in(self::ROLE_TERSEDIA)],
            'departemen_id' => [
                Rule::requiredIf(in_array($this->input('role'), self::ROLE_DENGAN_DEPARTEMEN, true)),
                'nullable',
                'exists:departemens,id',
            ],
            'keterangan_tambahan' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $role = $this->input('role');
            $departemenId = $this->input('departemen_id');
            $userId = $this->route('pengguna')?->id;

            if (in_array($role, self::ROLE_TUNGGAL_PER_DEPARTEMEN, true) && $departemenId) {
                $adaAktif = User::where('role', $role)
                    ->where('departemen_id', $departemenId)
                    ->where('is_active', true)
                    ->when($userId, fn ($q) => $q->where('id', '!=', $userId))
                    ->exists();

                if ($adaAktif) {
                    $validator->errors()->add(
                        'departemen_id',
                        'Departemen ini sudah punya akun aktif untuk role tersebut. Nonaktifkan yang lama terlebih dahulu.'
                    );
                }
            }
        });
    }
}