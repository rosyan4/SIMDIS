@extends('layouts.app')
@section('title', 'Detail Pengajuan Dispensasi')
@section('page-title', 'Detail Pengajuan')

@section('content')
@php
    $statusLabel = match ($dispensasi->status_pengajuan) {
        'menunggu_persetujuan' => 'Menunggu Persetujuan',
        'disetujui' => 'Disetujui',
        'ditolak' => 'Ditolak',
        default => ucfirst($dispensasi->status_pengajuan),
    };
    $statusClass = match ($dispensasi->status_pengajuan) {
        'menunggu_persetujuan' => 'badge-menunggu',
        'disetujui' => 'badge-disetujui',
        'ditolak' => 'badge-ditolak',
        default => 'badge-default',
    };
    $waktuLabel = ['pagi' => 'Pagi', 'istirahat' => 'Istirahat', 'siang' => 'Siang', 'sore' => 'Sore'][$dispensasi->waktu_dispensasi] ?? $dispensasi->waktu_dispensasi;
@endphp

<a href="{{ url()->previous() }}" class="text-xs text-accent font-semibold mb-4 inline-flex items-center gap-1">
    <i class="fas fa-arrow-left"></i> Kembali
</a>

<div class="flex items-start justify-between gap-4 mb-8 flex-wrap">
    <div>
        <p class="mono-data text-xs text-ink-soft mb-1">{{ $dispensasi->nomor_dispensasi }}</p>
        <h1 class="font-display text-3xl text-ink">{{ $dispensasi->pegawai->nama_pegawai }}</h1>
    </div>
    <span class="badge {{ $statusClass }} text-sm">{{ $statusLabel }}</span>
</div>

<div class="grid lg:grid-cols-3 gap-4">
    {{-- Kolom kiri: detail pengajuan --}}
    <div class="lg:col-span-2 space-y-4">
        <div class="card p-6">
            <h3 class="font-semibold text-ink mb-4">Detail Pengajuan</h3>

            <dl class="grid sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                <div>
                    <dt class="text-ink-soft text-xs mb-0.5">Pegawai</dt>
                    <dd class="text-ink font-medium">{{ $dispensasi->pegawai->nama_pegawai }}</dd>
                    <dd class="text-ink-soft text-xs mono-data">{{ $dispensasi->pegawai->nik }}</dd>
                </div>
                <div>
                    <dt class="text-ink-soft text-xs mb-0.5">Jabatan</dt>
                    <dd class="text-ink">{{ $dispensasi->pegawai->jabatan }}</dd>
                </div>
                <div>
                    <dt class="text-ink-soft text-xs mb-0.5">Departemen</dt>
                    <dd class="text-ink">{{ $dispensasi->departemen->nama_departemen }}</dd>
                </div>
                <div>
                    <dt class="text-ink-soft text-xs mb-0.5">Subdepartemen</dt>
                    <dd class="text-ink">{{ $dispensasi->subdepartemen?->nama_subdepartemen ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-ink-soft text-xs mb-0.5">Tanggal Dispensasi</dt>
                    <dd class="text-ink font-medium">{{ $dispensasi->tanggal_dispensasi->format('d M Y') }}</dd>
                </div>
                <div>
                    <dt class="text-ink-soft text-xs mb-0.5">Waktu</dt>
                    <dd class="text-ink">{{ $waktuLabel }}</dd>
                </div>
                <div>
                    <dt class="text-ink-soft text-xs mb-0.5">Tanggal Pengajuan</dt>
                    <dd class="text-ink">{{ $dispensasi->tanggal_pengajuan->format('d M Y') }}</dd>
                </div>
                <div>
                    <dt class="text-ink-soft text-xs mb-0.5">Diinput oleh</dt>
                    <dd class="text-ink">{{ $dispensasi->adminDepartemen?->name ?? '-' }}</dd>
                </div>
            </dl>

            <div class="mt-5 pt-5 border-t border-line">
                <dt class="text-ink-soft text-xs mb-1">Keterangan / Alasan</dt>
                <dd class="text-ink text-sm leading-relaxed">{{ $dispensasi->keterangan }}</dd>
            </div>

            @if ($dispensasi->bukti_pendukung)
            <div class="mt-5 pt-5 border-t border-line">
                <dt class="text-ink-soft text-xs mb-2">Bukti Pendukung</dt>
                <a href="{{ asset('storage/' . $dispensasi->bukti_pendukung) }}" target="_blank"
                   class="btn btn-outline btn-sm inline-flex">
                    <i class="fas fa-paperclip"></i> Lihat Berkas
                </a>
            </div>
            @endif
        </div>

        {{-- Kalau sudah diputuskan, tampilkan catatannya --}}
        @if ($dispensasi->sudahDiputuskan())
        <div class="card p-6">
            <h3 class="font-semibold text-ink mb-4">Riwayat Keputusan</h3>
            <dl class="grid sm:grid-cols-2 gap-x-6 gap-y-4 text-sm mb-4">
                <div>
                    <dt class="text-ink-soft text-xs mb-0.5">Diputuskan oleh</dt>
                    <dd class="text-ink">{{ $dispensasi->diprosesOleh?->name ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-ink-soft text-xs mb-0.5">Tanggal Keputusan</dt>
                    <dd class="text-ink">{{ $dispensasi->tanggal_keputusan?->format('d M Y, H:i') ?? '-' }}</dd>
                </div>
            </dl>
            @if ($dispensasi->catatan_persetujuan)
            <div class="pt-4 border-t border-line">
                <dt class="text-ink-soft text-xs mb-1">Catatan</dt>
                <dd class="text-ink text-sm leading-relaxed">{{ $dispensasi->catatan_persetujuan }}</dd>
            </div>
            @endif
        </div>
        @endif
    </div>

    {{-- Kolom kanan: aksi keputusan --}}
    <div class="lg:col-span-1">
        @if ($dispensasi->isMenungguPersetujuan())
        <div class="card p-6" x-data="{ tolakOpen: false }">
            <h3 class="font-semibold text-ink mb-2">Ambil Keputusan</h3>
            <p class="text-xs text-ink-soft mb-5">Keputusan ini tidak bisa diubah setelah disimpan.</p>

            <form method="POST" action="{{ route('dispensasi.approve', $dispensasi) }}" class="mb-3">
                @csrf
                <div class="mb-3">
                    <label class="field-label" for="catatan_setuju">Catatan <span class="text-ink-soft font-normal">(opsional)</span></label>
                    <textarea id="catatan_setuju" name="catatan" class="field-input" rows="3" placeholder="Catatan tambahan (opsional)..."></textarea>
                </div>
                <button type="submit" class="btn btn-primary w-full" onclick="return confirm('Setujui pengajuan {{ $dispensasi->nomor_dispensasi }}?');">
                    <i class="fas fa-check"></i> Setujui
                </button>
            </form>

            <button type="button" @click="tolakOpen = true" class="btn btn-danger w-full">
                <i class="fas fa-xmark"></i> Tolak
            </button>

            {{-- Modal alasan penolakan --}}
            <div x-show="tolakOpen" x-cloak class="fixed inset-0 z-[300] flex items-center justify-center p-4" style="background: rgba(15,23,42,.48);">
                <div @click.outside="tolakOpen = false" class="card w-full max-w-sm p-6">
                    <h3 class="font-bold text-ink mb-1">Tolak Pengajuan</h3>
                    <p class="text-sm text-ink-soft mb-4">Alasan penolakan wajib diisi, minimal 5 karakter — akan disampaikan ke Admin Departemen.</p>

                    <form method="POST" action="{{ route('dispensasi.reject', $dispensasi) }}">
                        @csrf
                        <textarea name="catatan" class="field-input mb-3" rows="3" placeholder="Alasan penolakan..." required minlength="5"></textarea>
                        @error('catatan') <p class="field-error mb-3">{{ $message }}</p> @enderror
                        <div class="flex gap-2 justify-end">
                            <button type="button" @click="tolakOpen = false" class="btn btn-outline">Batal</button>
                            <button type="submit" class="btn btn-danger">Ya, Tolak</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @else
        <div class="card p-6 text-center">
            <i class="fas fa-circle-check text-2xl text-ink-soft mb-2"></i>
            <p class="text-sm text-ink-soft">Pengajuan ini sudah {{ strtolower($statusLabel) }} dan tidak dapat diubah lagi.</p>
        </div>
        @endif
    </div>
</div>
@endsection