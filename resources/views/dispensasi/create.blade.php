@extends('layouts.app')
@section('title', 'Ajukan Dispensasi')
@section('page-title', 'Ajukan Dispensasi')

@section('content')
<div class="mb-8">
    <p class="text-xs font-semibold tracking-widest text-accent uppercase mb-1">Admin Departemen</p>
    <h1 class="font-display text-3xl text-ink">Ajukan Dispensasi</h1>
</div>

<form method="POST" action="{{ route('dispensasi.store') }}" enctype="multipart/form-data" class="card p-6 max-w-2xl">
    @csrf

    <div class="mb-5">
        <label class="field-label" for="pegawai_id">Pegawai</label>
        <select id="pegawai_id" name="pegawai_id" class="field-input" required>
            <option value="">— Pilih Pegawai —</option>
            @forelse ($pegawais as $p)
            <option value="{{ $p->id }}" @selected((int) old('pegawai_id') === $p->id)>
                {{ $p->nama_pegawai }} — {{ $p->nik }}
            </option>
            @empty
            <option value="" disabled>Tidak ada pegawai aktif di departemen Anda</option>
            @endforelse
        </select>
        @error('pegawai_id') <p class="field-error">{{ $message }}</p> @enderror
    </div>

    <div class="mb-5">
        <label class="field-label" for="tanggal_dispensasi">Tanggal Dispensasi</label>
        <input type="date" id="tanggal_dispensasi" name="tanggal_dispensasi" class="field-input" style="max-width:220px"
               value="{{ old('tanggal_dispensasi') }}" required>
        @error('tanggal_dispensasi') <p class="field-error">{{ $message }}</p> @enderror
    </div>

    <div class="mb-5">
        <label class="field-label">Waktu Dispensasi</label>
        <p class="text-xs text-ink-soft mb-2">Bisa pilih lebih dari satu — tiap waktu yang dicentang akan jadi pengajuan terpisah.</p>
        <div class="flex flex-wrap gap-4">
            @foreach (['T', 'TBO', 'TBI', 'CP'] as $val)
            <label class="inline-flex items-center gap-2 text-sm text-ink">
                <input type="checkbox" name="waktu_dispensasi[]" value="{{ $val }}" class="h-4 w-4"
                       @checked(in_array($val, old('waktu_dispensasi', [])))>
                {{ $val }}
            </label>
            @endforeach
        </div>
        @error('waktu_dispensasi') <p class="field-error">{{ $message }}</p> @enderror
    </div>

    <div class="mb-5">
        <label class="field-label" for="keterangan">Keterangan</label>
        <textarea id="keterangan" name="keterangan" rows="4" class="field-input" required>{{ old('keterangan') }}</textarea>
        @error('keterangan') <p class="field-error">{{ $message }}</p> @enderror
    </div>

    <div class="mb-6">
        <label class="field-label" for="bukti_pendukung">Bukti Pendukung <span class="text-ink-soft font-normal">(opsional, PDF/JPG/PNG maks 2MB)</span></label>
        <input type="file" id="bukti_pendukung" name="bukti_pendukung" class="field-input" accept=".pdf,.jpg,.jpeg,.png">
        @error('bukti_pendukung') <p class="field-error">{{ $message }}</p> @enderror
    </div>

    <div class="flex gap-2">
        <button type="submit" class="btn btn-primary">Kirim Pengajuan</button>
        <a href="{{ route('dispensasi.index') }}" class="btn btn-outline">Batal</a>
    </div>
</form>
@endsection