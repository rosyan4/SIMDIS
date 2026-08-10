@extends('layouts.app')
@section('title', 'Ajukan Dispensasi')

@section('content')
<div class="max-w-xl">
    <p class="text-xs font-semibold tracking-widest text-accent uppercase mb-1">Pengajuan Baru</p>
    <h1 class="font-display text-3xl text-ink mb-8">Ajukan Dispensasi</h1>

    <form method="POST" action="{{ route('dispensasi.store') }}" enctype="multipart/form-data" class="card p-7 space-y-5">
        @csrf

        <div>
            <label class="field-label">Tanggal Dispensasi</label>
            <input type="date" name="tanggal_dispensasi" value="{{ old('tanggal_dispensasi') }}" class="field-input">
            @error('tanggal_dispensasi') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="field-label">Jam Mulai</label>
                <input type="time" name="jam_mulai" value="{{ old('jam_mulai') }}" class="field-input">
            </div>
            <div>
                <label class="field-label">Jam Selesai</label>
                <input type="time" name="jam_selesai" value="{{ old('jam_selesai') }}" class="field-input">
                @error('jam_selesai') <p class="field-error">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <label class="field-label">Keterangan</label>
            <textarea name="alasan" rows="4" class="field-input">{{ old('alasan') }}</textarea>
            @error('alasan') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="field-label">Bukti Pendukung <span class="text-ink-soft font-normal">(opsional)</span></label>
            <input type="file" name="bukti_pendukung" class="field-input">
            <p class="text-xs text-ink-soft mt-1.5">Format PDF/JPG/PNG, maksimal 2MB.</p>
            @error('bukti_pendukung') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <button type="submit" class="btn btn-primary w-full">Ajukan Dispensasi</button>
    </form>
</div>
@endsection