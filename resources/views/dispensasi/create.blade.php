@extends('layouts.app')
@section('title', 'Ajukan Dispensasi')
@section('page-title', 'Ajukan Dispensasi')

@section('content')
<div class="mb-8">
    <p class="text-xs font-semibold tracking-widest text-accent uppercase mb-1">Admin Departemen</p>
    <h1 class="font-display text-3xl text-ink">Ajukan Dispensasi</h1>
</div>

<form method="POST" action="{{ route('dispensasi.store') }}" class="card p-6 max-w-2xl" enctype="multipart/form-data">
    @csrf

    <div class="mb-5">
        <label class="field-label" for="pegawai_id">Pegawai</label>
        <select id="pegawai_id" name="pegawai_id" class="field-input" required>
            <option value="">— Pilih Pegawai —</option>
            @forelse ($pegawais as $p)
            <option value="{{ $p->id }}" @selected((int) old('pegawai_id') === $p->id)>
                {{ $p->nama_pegawai }} — {{ $p->jabatan }} ({{ $p->nik }})
            </option>
            @empty
            <option value="" disabled>Tidak ada pegawai aktif di departemen Anda</option>
            @endforelse
        </select>
        @error('pegawai_id') <p class="field-error">{{ $message }}</p> @enderror
    </div>

    <div class="mb-5">
        <label class="field-label" for="tanggal_dispensasi">Tanggal Dispensasi</label>
        <input type="date" id="tanggal_dispensasi" name="tanggal_dispensasi" class="field-input"
               value="{{ old('tanggal_dispensasi') }}" required style="max-width:220px">
        @error('tanggal_dispensasi') <p class="field-error">{{ $message }}</p> @enderror
    </div>

    <div class="mb-5">
        <label class="field-label">Waktu Dispensasi</label>
        <p class="text-xs text-ink-soft mb-2">Bisa pilih lebih dari satu — masing-masing akan tercatat sebagai pengajuan terpisah dengan nomor sendiri.</p>

        @php
            $waktuOptions = ['pagi' => 'Pagi', 'istirahat' => 'Istirahat', 'siang' => 'Siang', 'sore' => 'Sore'];
            $oldWaktu = old('waktu_dispensasi', []);
        @endphp

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            @foreach ($waktuOptions as $val => $label)
            <label class="flex items-center gap-2 border border-line rounded-lg px-3 py-2.5 cursor-pointer hover:border-accent has-[:checked]:border-accent has-[:checked]:bg-accent-soft transition-colors">
                <input type="checkbox" name="waktu_dispensasi[]" value="{{ $val }}"
                       @checked(in_array($val, $oldWaktu)) class="accent-accent">
                <span class="text-sm text-ink">{{ $label }}</span>
            </label>
            @endforeach
        </div>
        @error('waktu_dispensasi') <p class="field-error">{{ $message }}</p> @enderror
    </div>

    <div class="mb-5">
        <label class="field-label" for="keterangan">Keterangan / Alasan</label>
        <textarea id="keterangan" name="keterangan" class="field-input" rows="4" required>{{ old('keterangan') }}</textarea>
        @error('keterangan') <p class="field-error">{{ $message }}</p> @enderror
    </div>

    <div class="mb-6">
        <label class="field-label" for="bukti_pendukung">Bukti Pendukung <span class="text-ink-soft font-normal">(opsional)</span></label>
        <input type="file" id="bukti_pendukung" name="bukti_pendukung" class="field-input" accept=".pdf,.jpg,.jpeg,.png">
        <p class="text-xs text-ink-soft mt-1.5">PDF/JPG/PNG, maksimal 2 MB. Berkas yang sama dipakai untuk semua waktu yang dicentang di atas.</p>
        @error('bukti_pendukung') <p class="field-error">{{ $message }}</p> @enderror
    </div>

    <div class="flex gap-2">
        <button type="submit" class="btn btn-primary">Kirim Pengajuan</button>
        <a href="{{ route('dispensasi.index') }}" class="btn btn-outline">Batal</a>
    </div>
</form>
@endsection