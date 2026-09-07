@extends('layouts.app')
@section('title', 'Tambah Pengguna')
@section('page-title', 'Tambah Pengguna')

@section('content')
<div class="mb-8">
    <p class="text-xs font-semibold tracking-widest text-accent uppercase mb-1">Admin SDM</p>
    <h1 class="font-display text-3xl text-ink">Tambah Pengguna</h1>
</div>

@php
    // Label tampilan untuk tiap role. Daftar role & daftar role-yang-butuh-
    // departemen diambil langsung dari StoreUserRequest (public const),
    // supaya satu sumber kebenaran dengan validasi di backend.
    $semuaLabel = [
        'admin_sdm'                      => 'Admin SDM',
        'admin_departemen'               => 'Admin Departemen',
        'manajer_departemen'             => 'Manajer Departemen',
        'senior_manajer_sekper'          => 'Senior Manajer Sekretaris Perusahaan',
        'kepala_spi'                     => 'Kepala SPI',
        'direktur_teknik'                => 'Direktur Teknik',
        'direktur_administrasi_keuangan' => 'Direktur Administrasi & Keuangan',
        'direktur_utama'                 => 'Direktur Utama',
    ];
    $roleLabels = collect(\App\Http\Requests\StoreUserRequest::ROLE_TERSEDIA)
        ->mapWithKeys(fn ($r) => [$r => $semuaLabel[$r] ?? $r]);
    $roleDenganDepartemen = \App\Http\Requests\StoreUserRequest::ROLE_DENGAN_DEPARTEMEN;
@endphp

<form method="POST" action="{{ route('sdm.pengguna.store') }}" class="card p-6 max-w-3xl"
      x-data="{ role: '{{ old('role') }}', roleDenganDepartemen: {{ \Illuminate\Support\Js::from($roleDenganDepartemen) }} }">
    @csrf

    <div class="grid md:grid-cols-2 gap-5 mb-5">
        <div>
            <label class="field-label" for="name">Nama</label>
            <input type="text" id="name" name="name" class="field-input"
                   value="{{ old('name') }}" required maxlength="100">
            @error('name') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="field-label" for="email">Email</label>
            <input type="email" id="email" name="email" class="field-input"
                   value="{{ old('email') }}" required maxlength="100">
            @error('email') <p class="field-error">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="grid md:grid-cols-2 gap-5 mb-5">
        <div>
            <label class="field-label" for="password">Password</label>
            <input type="password" id="password" name="password" class="field-input" required minlength="8">
            @error('password') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="field-label" for="password_confirmation">Konfirmasi Password</label>
            <input type="password" id="password_confirmation" name="password_confirmation" class="field-input" required minlength="8">
        </div>
    </div>

    <div class="grid md:grid-cols-2 gap-5 mb-5">
        <div>
            <label class="field-label" for="role">Role</label>
            <select id="role" name="role" class="field-input" x-model="role" required>
                <option value="">— Pilih Role —</option>
                @foreach ($roleLabels as $val => $label)
                <option value="{{ $val }}" @selected(old('role') === $val)>{{ $label }}</option>
                @endforeach
            </select>
            @error('role') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div x-show="roleDenganDepartemen.includes(role)" x-cloak>
            <label class="field-label" for="departemen_id">Departemen</label>
            <select id="departemen_id" name="departemen_id" class="field-input">
                <option value="">— Pilih Departemen —</option>
                @foreach ($departemens as $d)
                <option value="{{ $d->id }}" @selected((int) old('departemen_id') === $d->id)>{{ $d->nama_departemen }}</option>
                @endforeach
            </select>
            <p class="text-xs text-ink-soft mt-1">
                Untuk role Manajer Departemen, Senior Manajer Sekper, dan Kepala SPI —
                hanya boleh ada satu akun aktif per departemen untuk role yang sama.
            </p>
            @error('departemen_id') <p class="field-error">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="mb-5">
        <label class="field-label" for="keterangan_tambahan">Keterangan Tambahan <span class="text-ink-soft font-normal">(opsional)</span></label>
        <textarea id="keterangan_tambahan" name="keterangan_tambahan" rows="3" class="field-input">{{ old('keterangan_tambahan') }}</textarea>
        @error('keterangan_tambahan') <p class="field-error">{{ $message }}</p> @enderror
    </div>

    <div class="mb-6 flex items-center gap-2">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" id="is_active" name="is_active" value="1"
               @checked(old('is_active', true)) class="h-4 w-4">
        <label for="is_active" class="text-sm text-ink">Akun aktif (bisa langsung login)</label>
    </div>

    <div class="flex gap-2">
        <button type="submit" class="btn btn-primary">Simpan Pengguna</button>
        <a href="{{ route('sdm.pengguna.index') }}" class="btn btn-outline">Batal</a>
    </div>
</form>
@endsection