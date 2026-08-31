@extends('layouts.app')
@section('title', 'Riwayat Dispensasi')
@section('page-title', 'Riwayat Dispensasi')

@section('content')
<div class="flex items-start justify-between gap-4 mb-8 flex-wrap">
    <div>
        <p class="text-xs font-semibold tracking-widest text-accent uppercase mb-1">Admin Departemen</p>
        <h1 class="font-display text-3xl text-ink">Riwayat Dispensasi Departemen</h1>
    </div>
    <a href="{{ route('dispensasi.create') }}" class="btn btn-primary">
        <i class="fas fa-plus"></i> Ajukan Dispensasi
    </a>
</div>

@if (session('success'))
<div class="card p-4 mb-6 border-l-4 border-green-500">
    <p class="text-sm text-ink">{{ session('success') }}</p>
</div>
@endif

<div class="table-scroll-wrapper">
    <table class="table-pro">
        <thead>
            <tr>
                <th>Nomor</th>
                <th>Pegawai</th>
                <th>Subdepartemen</th>
                <th>Tanggal Dispensasi</th>
                <th>Waktu</th>
                <th>Diproses Oleh</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($dispensasis as $d)
            @php
                $statusLabel = match ($d->status_pengajuan) {
                    'menunggu_persetujuan' => 'Menunggu',
                    'disetujui' => 'Disetujui',
                    'ditolak' => 'Ditolak',
                    default => ucfirst($d->status_pengajuan),
                };
                $statusClass = match ($d->status_pengajuan) {
                    'menunggu_persetujuan' => 'badge-menunggu',
                    'disetujui' => 'badge-disetujui',
                    'ditolak' => 'badge-ditolak',
                    default => 'badge-default',
                };
                $waktuLabel = ['pagi' => 'Pagi', 'istirahat' => 'Istirahat', 'siang' => 'Siang', 'sore' => 'Sore'][$d->waktu_dispensasi] ?? $d->waktu_dispensasi;
            @endphp
            <tr>
                <td class="mono-data text-ink-soft">{{ $d->nomor_dispensasi }}</td>
                <td class="font-medium">{{ $d->pegawai->nama_pegawai }}</td>
                <td class="text-ink-soft">{{ $d->subdepartemen->nama_subdepartemen ?? '-' }}</td>
                <td>{{ $d->tanggal_dispensasi->format('d M Y') }}</td>
                <td class="text-ink-soft">{{ $waktuLabel }}</td>
                <td class="text-ink-soft">{{ $d->diprosesOleh->name ?? '-' }}</td>
                <td><span class="badge {{ $statusClass }}">{{ $statusLabel }}</span></td>
                <td class="text-right">
                    <a href="{{ route('dispensasi.show', $d) }}" class="btn btn-outline btn-sm">
                        Detail <i class="fas fa-arrow-right"></i>
                    </a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="text-center text-ink-soft py-12">
                    <i class="fas fa-inbox text-2xl mb-2 block"></i>
                    Belum ada pengajuan dispensasi.
                    <a href="{{ route('dispensasi.create') }}" class="text-accent font-medium">Ajukan sekarang</a>.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if ($dispensasis->hasPages())
<div class="mt-4">
    {{ $dispensasis->links() }}
</div>
@endif
@endsection