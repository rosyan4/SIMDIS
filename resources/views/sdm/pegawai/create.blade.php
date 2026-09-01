@extends('layouts.app')
@section('title', 'Tambah Pegawai')
@section('page-title', 'Tambah Pegawai')

@section('content')
<div class="mb-8">
    <p class="text-xs font-semibold tracking-widest text-accent uppercase mb-1">Admin SDM</p>
    <h1 class="font-display text-3xl text-ink">Tambah Pegawai</h1>
</div>

@php
    $departemenJson = $departemens->map(fn ($d) => [
        'id' => $d->id,
        'subdepartemens' => $d->subdepartemens->map(fn ($s) => ['id' => $s->id, 'nama' => $s->nama_subdepartemen]),
    ]);
@endphp

<form method="POST" action="{{ route('sdm.pegawai.store') }}" class="card p-6 max-w-3xl"
      x-data="{
          departemenId: null,
          subdepartemenId: null,
          semuaDepartemen: {{ \Illuminate\Support\Js::from($departemenJson) }},
      }">
    @csrf

    <div class="grid md:grid-cols-2 gap-5 mb-5">
        <div>
            <label class="field-label" for="nik">NIK</label>
            <input type="text" id="nik" name="nik" class="field-input"
                   value="{{ old('nik') }}" required maxlength="20">
            @error('nik') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="field-label" for="nama_pegawai">Nama Pegawai</label>
            <input type="text" id="nama_pegawai" name="nama_pegawai" class="field-input"
                   value="{{ old('nama_pegawai') }}" required maxlength="100">
            @error('nama_pegawai') <p class="field-error">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="grid md:grid-cols-2 gap-5 mb-5">
        <div>
            <label class="field-label" for="jenis_pegawai">Jenis Pegawai</label>
            <select id="jenis_pegawai" name="jenis_pegawai" class="field-input" required>
                @foreach (['pegawai' => 'Pegawai', 'pekerja_lapangan' => 'Pekerja Lapangan'] as $val => $label)
                <option value="{{ $val }}" @selected(old('jenis_pegawai') === $val)>{{ $label }}</option>
                @endforeach
            </select>
            @error('jenis_pegawai') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="field-label" for="jabatan">Jabatan</label>
            <select id="jabatan" name="jabatan" class="field-input" required>
                <option value="">— Pilih Jabatan —</option>
                @foreach (\App\Http\Requests\StorePegawaiRequest::PILIHAN_JABATAN as $j)
                <option value="{{ $j }}" @selected(old('jabatan') === $j)>{{ $j }}</option>
                @endforeach
            </select>
            @error('jabatan') <p class="field-error">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="grid md:grid-cols-2 gap-5 mb-5">
        <div>
            <label class="field-label" for="departemen_id">Departemen</label>
            <select id="departemen_id" name="departemen_id" class="field-input"
                    x-model.number="departemenId" @change="subdepartemenId = null" required>
                <option value="">— Pilih Departemen —</option>
                @foreach ($departemens as $d)
                <option value="{{ $d->id }}" @selected((int) old('departemen_id') === $d->id)>{{ $d->nama_departemen }}</option>
                @endforeach
            </select>
            @error('departemen_id') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="field-label" for="subdepartemen_id">Subdepartemen <span class="text-ink-soft font-normal">(opsional)</span></label>
            <select id="subdepartemen_id" name="subdepartemen_id" class="field-input" x-model.number="subdepartemenId">
                <option value="">— Tidak ada —</option>
                <template x-for="sub in (semuaDepartemen.find(d => d.id === departemenId)?.subdepartemens ?? [])" :key="sub.id">
                    <option :value="sub.id" x-text="sub.nama"></option>
                </template>
            </select>
            @error('subdepartemen_id') <p class="field-error">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="grid md:grid-cols-2 gap-5 mb-5">
        <div>
            <label class="field-label" for="no_telepon">No. Telepon <span class="text-ink-soft font-normal">(opsional)</span></label>
            <input type="text" id="no_telepon" name="no_telepon" class="field-input"
                   value="{{ old('no_telepon') }}" maxlength="20">
            @error('no_telepon') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="field-label" for="email">Email <span class="text-ink-soft font-normal">(opsional)</span></label>
            <input type="email" id="email" name="email" class="field-input"
                   value="{{ old('email') }}" maxlength="100">
            @error('email') <p class="field-error">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="mb-6">
        <label class="field-label" for="status">Status</label>
        <select id="status" name="status" class="field-input" style="max-width:220px" required>
            @foreach (['aktif' => 'Aktif', 'nonaktif' => 'Nonaktif'] as $val => $label)
            <option value="{{ $val }}" @selected(old('status', 'aktif') === $val)>{{ $label }}</option>
            @endforeach
        </select>
        @error('status') <p class="field-error">{{ $message }}</p> @enderror
    </div>

    <div class="flex gap-2">
        <button type="submit" class="btn btn-primary">Simpan Pegawai</button>
        <a href="{{ route('sdm.pegawai.index') }}" class="btn btn-outline">Batal</a>
    </div>
</form>
@endsection