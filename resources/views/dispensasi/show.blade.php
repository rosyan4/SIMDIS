@extends('layouts.app')
@section('title', 'Detail Pengajuan — ' . $dispensasi->nomor_dispensasi)
@section('page-title', 'Detail Pengajuan')

@section('content')
@php
    $waktuLabel = $dispensasi->waktu_dispensasi;
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
@endphp

<div class="mb-8">
    <a href="{{ route('dispensasi.index') }}" class="text-xs text-accent font-semibold mb-2 inline-flex items-center gap-1">
        <i class="fas fa-arrow-left"></i> Kembali ke Pengajuan Dispensasi
    </a>
    <div class="flex items-center gap-3 flex-wrap mt-2">
        <h1 class="font-display text-3xl text-ink">{{ $dispensasi->nomor_dispensasi }}</h1>
        <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
    </div>
</div>

<div class="grid lg:grid-cols-3 gap-4">
    <div class="lg:col-span-2 space-y-4">
        <div class="card p-6">
            <h3 class="font-semibold text-ink mb-4">Data Pegawai</h3>
            <dl class="grid sm:grid-cols-2 gap-4 text-sm">
                <div>
                    <dt class="text-xs text-ink-soft mb-0.5">NIK</dt>
                    <dd class="mono-data text-ink">{{ $dispensasi->pegawai->nik }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-ink-soft mb-0.5">Nama</dt>
                    <dd class="font-medium text-ink">{{ $dispensasi->pegawai->nama_pegawai }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-ink-soft mb-0.5">Jabatan</dt>
                    <dd class="text-ink">{{ $dispensasi->pegawai->jabatan }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-ink-soft mb-0.5">Subdepartemen</dt>
                    <dd class="text-ink">{{ $dispensasi->subdepartemen?->nama_subdepartemen ?? '-' }}</dd>
                </div>
            </dl>
        </div>

        <div class="card p-6">
            <h3 class="font-semibold text-ink mb-4">Detail Pengajuan</h3>
            <dl class="grid sm:grid-cols-2 gap-4 text-sm mb-4">
                <div>
                    <dt class="text-xs text-ink-soft mb-0.5">Tanggal Dispensasi</dt>
                    <dd class="text-ink">{{ $dispensasi->tanggal_dispensasi->format('d M Y') }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-ink-soft mb-0.5">Waktu</dt>
                    <dd class="text-ink">{{ $waktuLabel }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-ink-soft mb-0.5">Diajukan Oleh</dt>
                    <dd class="text-ink">{{ $dispensasi->adminDepartemen?->name ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-ink-soft mb-0.5">Tanggal Pengajuan</dt>
                    <dd class="text-ink">{{ $dispensasi->tanggal_pengajuan->format('d M Y') }}</dd>
                </div>
            </dl>
            <div class="mb-4">
                <dt class="text-xs text-ink-soft mb-1">Keterangan</dt>
                <dd class="text-ink whitespace-pre-line">{{ $dispensasi->keterangan ?: '-' }}</dd>
            </div>
            @if ($dispensasi->bukti_pendukung)
            <div>
                <dt class="text-xs text-ink-soft mb-1">Bukti Pendukung</dt>
                <dd>
                    <a href="{{ asset('storage/' . $dispensasi->bukti_pendukung) }}" target="_blank" class="btn btn-sm btn-outline">
                        <i class="fas fa-paperclip"></i> Lihat Lampiran
                    </a>
                </dd>
            </div>
            @endif
        </div>
    </div>

    <div class="lg:col-span-1">
        <div class="card p-6">
            <h3 class="font-semibold text-ink mb-4">Status Keputusan</h3>
            @if ($dispensasi->status_pengajuan === 'menunggu_persetujuan')
            <p class="text-sm text-ink-soft">
                <i class="fas fa-clock text-[#C8862B]"></i>
                Masih menunggu keputusan dari pihak berwenang. Anda akan mendapat notifikasi begitu ada keputusan.
            </p>
            @else
            <dl class="space-y-3 text-sm">
                <div>
                    <dt class="text-xs text-ink-soft mb-0.5">Status</dt>
                    <dd><span class="badge {{ $statusClass }}">{{ $statusLabel }}</span></dd>
                </div>
                <div>
                    <dt class="text-xs text-ink-soft mb-0.5">Diputuskan Oleh</dt>
                    <dd class="text-ink">{{ $dispensasi->diprosesOleh?->name ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-ink-soft mb-0.5">Tanggal Keputusan</dt>
                    <dd class="text-ink">{{ $dispensasi->tanggal_keputusan?->format('d M Y, H:i') ?? '-' }}</dd>
                </div>
                @if ($dispensasi->catatan_persetujuan)
                <div>
                    <dt class="text-xs text-ink-soft mb-0.5">Catatan</dt>
                    <dd class="text-ink whitespace-pre-line">{{ $dispensasi->catatan_persetujuan }}</dd>
                </div>
                @endif
            </dl>
            @endif
        </div>
    </div>
</div>
@endsection