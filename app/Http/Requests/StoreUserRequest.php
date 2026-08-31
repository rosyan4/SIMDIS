<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Otorisasi role Admin SDM ditangani middleware route
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id;
        $role = $this->input('role');

        return [
            'name'     => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:50', Rule::unique('users', 'username')->ignore($userId)],
            'email'    => ['nullable', 'email', 'max:100', Rule::unique('users', 'email')->ignore($userId)],

            // Password wajib saat create, opsional saat update (kosong = tidak diganti)
            'password' => [$this->isMethod('post') ? 'required' : 'nullable', 'string', 'min:8', 'confirmed'],

            'role' => ['required', Rule::in(['admin_sdm', 'admin_departemen', 'manajer_departemen', 'asisten_manajer'])],

            // departemen_id WAJIB untuk admin_departemen & manajer_departemen, kosong untuk yang lain
            'departemen_id' => [
                Rule::requiredIf(in_array($role, ['admin_departemen', 'manajer_departemen'], true)),
                'nullable',
                'exists:departemens,id',
            ],

            // subdepartemen_id WAJIB hanya untuk asisten_manajer
            'subdepartemen_id' => [
                Rule::requiredIf($role === 'asisten_manajer'),
                'nullable',
                'exists:subdepartemens,id',
                // Cegah 1 manajer aktif ganda: kalau role manajer_departemen atau
                // asisten_manajer, pastikan belum ada user AKTIF lain di unit yang sama.
                function ($attribute, $value, $fail) use ($role, $userId) {
                    if ($role === 'asisten_manajer' && $value) {
                        $adaAsistenAktif = User::where('role', 'asisten_manajer')
                            ->where('subdepartemen_id', $value)
                            ->where('is_active', true)
                            ->when($userId, fn ($q) => $q->where('id', '!=', $userId))
                            ->exists();

                        if ($adaAsistenAktif) {
                            $fail('Subdepartemen ini sudah punya Asisten Manajer aktif. Nonaktifkan yang lama terlebih dahulu.');
                        }
                    }
                },
            ],

            'status_manajer' => ['nullable', Rule::in(['aktif', 'berhalangan'])],
            'tanggal_mulai_berhalangan'   => ['nullable', 'date'],
            'tanggal_selesai_berhalangan' => ['nullable', 'date', 'after_or_equal:tanggal_mulai_berhalangan'],
            'alasan_berhalangan'          => ['nullable', 'string', 'max:200'],
            'keterangan_tambahan'         => ['nullable', 'string'],

            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $role = $this->input('role');
            $departemenId = $this->input('departemen_id');
            $userId = $this->route('user')?->id;

            // Aturan yang sama untuk manajer_departemen: 1 manajer aktif per departemen.
            // Ditaruh di withValidator (bukan rule closure di atas) karena field yang
            // relevan (departemen_id) berbeda dari field yang divalidasi (subdepartemen_id).
            if ($role === 'manajer_departemen' && $departemenId) {
                $adaManajerAktif = User::where('role', 'manajer_departemen')
                    ->where('departemen_id', $departemenId)
                    ->where('is_active', true)
                    ->when($userId, fn ($q) => $q->where('id', '!=', $userId))
                    ->exists();

                if ($adaManajerAktif) {
                    $validator->errors()->add(
                        'departemen_id',
                        'Departemen ini sudah punya Manajer Departemen aktif. Nonaktifkan yang lama terlebih dahulu.'
                    );
                }
            }
        });
    }
}