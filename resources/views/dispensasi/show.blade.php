@extends('layouts.app')
@section('title', 'Detail Dispensasi')

@section('content')
<div class="max-w-xl">
    <a href="{{ route('dispensasi.index') }}" class="text-sm text-ink-soft hover:text-primary inline-flex items-center gap-1 mb-5">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        Kembali
    </a>

    <div class="flex justify-between items-start mb-8">
        <div>
            <p class="text-xs font-semibold tracking-widest text-accent uppercase mb-1">Detail Pengajuan</p>
            <h1 class="font-display text-2xl text-ink mono-data">{{ $dispensasi->nomor_dispensasi }}</h1>
        </div>
        <span class="badge badge-{{ $dispensasi->status }}">{{ ucfirst($dispensasi->status) }}</span>
    </div>

    <div class="card divide-y divide-line">
        <div class="px-6 py-4 flex justify-between text-sm">
            <span class="text-ink-soft">Tanggal</span>
            <span class="font-medium text-ink">{{ $dispensasi->tanggal_dispensasi->format('d M Y') }}</span>
        </div>
        <div class="px-6 py-4 flex justify-between text-sm">
            <span class="text-ink-soft">Waktu</span>
            <span class="font-medium text-ink mono-data">{{ $dispensasi->jam_mulai ?? '-' }} — {{ $dispensasi->jam_selesai ?? '-' }}</span>
        </div>
        <div class="px-6 py-4 text-sm">
            <span class="text-ink-soft block mb-1">Keterangan</span>
            <span class="text-ink">{{ $dispensasi->alasan }}</span>
        </div>
        @if ($dispensasi->bukti_pendukung)
        <div class="px-6 py-4 flex justify-between items-center text-sm">
            <span class="text-ink-soft">Bukti Pendukung</span>
            <a href="{{ Storage::url($dispensasi->bukti_pendukung) }}" target="_blank" class="text-primary font-medium hover:underline">Lihat File</a>
        </div>
        @endif
        @if ($dispensasi->catatan_persetujuan)
        <div class="px-6 py-4 text-sm">
            <span class="text-ink-soft block mb-1">Catatan</span>
            <span class="text-ink">{{ $dispensasi->catatan_persetujuan }}</span>
        </div>
        @endif
    </div>
</div>
@endsection