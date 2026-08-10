@extends('layouts.app')
@section('title', 'Detail Pengajuan Dispensasi')

@section('content')
<div class="max-w-2xl" x-data="{ showReject: false }">
    <a href="{{ route('dashboard.' . (auth()->user()->isManajerDepartemen() ? 'manajer' : 'asmen')) }}"
       class="text-sm text-ink-soft hover:text-primary inline-flex items-center gap-1 mb-5">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        Kembali ke Daftar
    </a>

    <div class="flex justify-between items-start mb-8">
        <div>
            <p class="text-xs font-semibold tracking-widest text-accent uppercase mb-1">Tinjau Pengajuan</p>
            <h1 class="font-display text-2xl text-ink mono-data">{{ $dispensasi->nomor_dispensasi }}</h1>
        </div>
        <span class="badge badge-{{ $dispensasi->status }}">{{ ucfirst($dispensasi->status) }}</span>
    </div>

    <div class="card divide-y divide-line mb-6">
        <div class="px-6 py-4 flex justify-between text-sm">
            <span class="text-ink-soft">Nama Pegawai</span>
            <span class="font-medium text-ink">{{ $dispensasi->pegawai->name }}</span>
        </div>
        <div class="px-6 py-4 flex justify-between text-sm">
            <span class="text-ink-soft">Departemen</span>
            <span class="font-medium text-ink">
                {{ $dispensasi->pegawai->subdepartemen?->departemen?->nama_departemen ?? '-' }}
                <span class="text-ink-soft font-normal">/ {{ $dispensasi->pegawai->subdepartemen?->nama_subdepartemen ?? '-' }}</span>
            </span>
        </div>
        <div class="px-6 py-4 flex justify-between text-sm">
            <span class="text-ink-soft">Tanggal Dispensasi</span>
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
        <div class="px-6 py-4 flex justify-between items-center text-sm">
            <span class="text-ink-soft">Bukti Pendukung</span>
            @if ($dispensasi->bukti_pendukung)
                <a href="{{ Storage::url($dispensasi->bukti_pendukung) }}" target="_blank" class="text-primary font-medium hover:underline">Lihat File</a>
            @else
                <span class="text-ink-soft italic">Tidak dilampirkan</span>
            @endif
        </div>
        <div class="px-6 py-4 flex justify-between text-sm">
            <span class="text-ink-soft">Diajukan</span>
            <span class="text-ink">{{ $dispensasi->created_at->format('d M Y, H:i') }} ({{ $dispensasi->created_at->diffForHumans() }})</span>
        </div>
        @if ($dispensasi->escalated_at)
        <div class="px-6 py-4 flex justify-between text-sm">
            <span class="text-ink-soft">Status Eskalasi</span>
            <span class="text-[#C8862B] font-medium">Dieskalasi ke Asisten Manajer sejak {{ $dispensasi->escalated_at->format('d M Y, H:i') }}</span>
        </div>
        @endif
    </div>

    @if ($dispensasi->status === 'diajukan')
    {{-- Preview bukti langsung di halaman kalau berupa gambar --}}
    @if ($dispensasi->bukti_pendukung && Str::endsWith(strtolower($dispensasi->bukti_pendukung), ['.jpg', '.jpeg', '.png']))
    <div class="card p-4 mb-6">
        <p class="text-xs text-ink-soft mb-3">Pratinjau Bukti Pendukung</p>
        <img src="{{ Storage::url($dispensasi->bukti_pendukung) }}" alt="Bukti pendukung" class="rounded-lg border border-line max-h-96 mx-auto">
    </div>
    @endif

    <div class="card p-6">
        <p class="font-semibold text-ink mb-4">Keputusan</p>

        <div x-show="!showReject">
            <form method="POST" action="{{ route('dispensasi.approve', $dispensasi) }}" class="mb-3">
                @csrf
                <label class="field-label">Catatan (opsional)</label>
                <textarea name="catatan" rows="2" class="field-input mb-3" placeholder="Catatan tambahan untuk pegawai..."></textarea>
                <button type="submit" class="btn w-full" style="background:#2E9E6B;color:#fff;">Setujui Pengajuan</button>
            </form>
            <button @click="showReject = true" class="btn btn-outline w-full">Tolak Pengajuan</button>
        </div>

        <form x-show="showReject" x-cloak method="POST" action="{{ route('dispensasi.reject', $dispensasi) }}">
            @csrf
            <label class="field-label">Alasan Penolakan <span class="text-[#C1483A]">*</span></label>
            <textarea name="catatan" rows="3" class="field-input mb-3" placeholder="Jelaskan alasan penolakan, akan dikirim ke pegawai..." required></textarea>
            @error('catatan') <p class="field-error mb-3">{{ $message }}</p> @enderror
            <div class="flex gap-3">
                <button type="button" @click="showReject = false" class="btn btn-outline flex-1">Batal</button>
                <button type="submit" class="btn btn-danger flex-1">Kirim Penolakan</button>
            </div>
        </form>
    </div>
    @else
    <div class="card p-6">
        <p class="text-sm text-ink-soft">
            Pengajuan ini sudah diputuskan sebagai <strong class="text-ink">{{ $dispensasi->status }}</strong>
            oleh {{ $dispensasi->approver?->name ?? '-' }} pada {{ $dispensasi->approved_at?->format('d M Y, H:i') }}.
        </p>
        @if ($dispensasi->catatan_persetujuan)
        <p class="text-sm text-ink mt-3"><span class="text-ink-soft">Catatan:</span> {{ $dispensasi->catatan_persetujuan }}</p>
        @endif
    </div>
    @endif
</div>
@endsection