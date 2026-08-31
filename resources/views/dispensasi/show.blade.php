@extends('layouts.app')
@section('title', 'Detail Dispensasi')
@section('page-title', 'Detail Dispensasi')

@section('content')
@php
    $waktuLabel = ['pagi' => 'Pagi', 'istirahat' => 'Istirahat', 'siang' => 'Siang', 'sore' => 'Sore'][$dispensasi->waktu_dispensasi] ?? $dispensasi->waktu_dispensasi;
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

<div class="flex items-start justify-between gap-4 mb-8 flex-wrap">
    <div>
        <p class="text-xs font-semibold tracking-widest text-accent uppercase mb-1">Detail Pengajuan</p>
        <h1 class="font-display text-3xl text-ink">{{ $dispensasi->nomor_dispensasi }}</h1>
    </div>
    <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <div class="md:col-span-2 space-y-6">
        <div class="card p-6">
            <h2 class="font-display text-lg text-ink mb-4">Informasi Pengajuan</h2>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div>
                    <dt class="text-ink-soft mb-0.5">Pegawai</dt>
                    <dd class="font-medium text-ink">{{ $dispensasi->pegawai->nama_pegawai }}</dd>
                </div>
                <div>
                    <dt class="text-ink-soft mb-0.5">Subdepartemen</dt>
                    <dd class="text-ink">{{ $dispensasi->subdepartemen->nama_subdepartemen ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-ink-soft mb-0.5">Tanggal Dispensasi</dt>
                    <dd class="text-ink">{{ $dispensasi->tanggal_dispensasi->format('d M Y') }}</dd>
                </div>
                <div>
                    <dt class="text-ink-soft mb-0.5">Waktu</dt>
                    <dd class="text-ink">{{ $waktuLabel }}</dd>
                </div>
                <div>
                    <dt class="text-ink-soft mb-0.5">Tanggal Pengajuan</dt>
                    <dd class="text-ink">{{ \Carbon\Carbon::parse($dispensasi->tanggal_pengajuan)->format('d M Y') }}</dd>
                </div>
                <div>
                    <dt class="text-ink-soft mb-0.5">Diinput oleh</dt>
                    <dd class="text-ink">{{ $dispensasi->adminDepartemen->name ?? '-' }}</dd>
                </div>
            </dl>

            <div class="mt-4 pt-4 border-t">
                <dt class="text-ink-soft mb-1 text-sm">Keterangan</dt>
                <dd class="text-ink text-sm">{{ $dispensasi->keterangan }}</dd>
            </div>
        </div>

        <div class="card p-6">
            <h2 class="font-display text-lg text-ink mb-4">Bukti Pendukung</h2>
            @if ($dispensasi->bukti_pendukung)
                <a href="{{ Storage::url($dispensasi->bukti_pendukung) }}"
                   target="_blank"
                   class="btn btn-outline btn-sm">
                    <i class="fas fa-paperclip"></i> Lihat Lampiran
                </a>
            @else
                <p class="text-sm text-ink-soft">Tidak ada lampiran yang disertakan.</p>
            @endif
        </div>
    </div>

    <div class="space-y-6">
        <div class="card p-6">
            <h2 class="font-display text-lg text-ink mb-4">Status</h2>

            @if ($dispensasi->status_pengajuan === 'menunggu_persetujuan')
                <p class="text-sm text-ink-soft">
                    <i class="fas fa-clock"></i> Menunggu keputusan dari pihak berwenang (Manajer Departemen atau Asisten Manajer).
                </p>
            @else
                <dl class="text-sm space-y-3">
                    <div>
                        <dt class="text-ink-soft mb-0.5">Diproses oleh</dt>
                        <dd class="text-ink">{{ $dispensasi->diprosesOleh->name ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-ink-soft mb-0.5">Tanggal Keputusan</dt>
                        <dd class="text-ink">{{ optional($dispensasi->tanggal_keputusan)->format('d M Y H:i') ?? '-' }}</dd>
                    </div>
                    @if ($dispensasi->catatan_persetujuan)
                    <div>
                        <dt class="text-ink-soft mb-0.5">Catatan</dt>
                        <dd class="text-ink">{{ $dispensasi->catatan_persetujuan }}</dd>
                    </div>
                    @endif
                </dl>
            @endif
        </div>

        <a href="{{ route('dispensasi.index') }}" class="btn btn-outline w-full">
            <i class="fas fa-arrow-left"></i> Kembali ke Riwayat
        </a>
    </div>
</div>
@endsection