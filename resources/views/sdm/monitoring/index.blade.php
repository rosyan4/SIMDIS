@extends('layouts.app')
@section('title', 'Monitoring Dispensasi')
@section('page-title', 'Monitoring Dispensasi')

@section('content')
<div class="flex items-start justify-between gap-4 mb-8 flex-wrap">
    <div>
        <p class="text-xs font-semibold tracking-widest text-accent uppercase mb-1">Admin SDM</p>
        <h1 class="font-display text-3xl text-ink">Monitoring Dispensasi</h1>
    </div>
    <a href="{{ route('sdm.monitoring.export.excel', request()->query()) }}" class="btn btn-primary">
        <i class="fas fa-file-excel"></i> Export ke Excel
    </a>
</div>

<p class="text-xs text-ink-soft -mt-6 mb-6">
    <i class="fas fa-circle-info"></i>
    Export Excel hanya menyertakan pengajuan berstatus <strong>Disetujui</strong>
    sesuai filter tahun/bulan/departemen yang sedang aktif di bawah — filter status
    tidak ikut mempengaruhi export.
</p>

{{-- Filter --}}
<form method="GET" class="card p-4 mb-6 flex gap-3 flex-wrap items-end">
    <div>
        <label class="text-xs text-ink-soft mb-1 block">Tahun</label>
        <select name="tahun" class="field-input" style="width:auto">
            <option value="">Semua Tahun</option>
            @foreach ($tahunTersedia as $t)
            <option value="{{ $t }}" @selected($tahun == $t)>{{ $t }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="text-xs text-ink-soft mb-1 block">Bulan</label>
        <select name="bulan" class="field-input" style="width:auto">
            <option value="">Semua Bulan</option>
            @foreach ($namaBulan as $angka => $nama)
            <option value="{{ $angka }}" @selected($bulan == $angka)>{{ $nama }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="text-xs text-ink-soft mb-1 block">Departemen</label>
        <select name="departemen_id" class="field-input" style="width:auto">
            <option value="">Semua Departemen</option>
            @foreach ($departemens as $d)
            <option value="{{ $d->id }}" @selected($departemenId == $d->id)>{{ $d->nama_departemen }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="text-xs text-ink-soft mb-1 block">Status</label>
        <select name="status" class="field-input" style="width:auto">
            <option value="">Semua Status</option>
            <option value="menunggu_persetujuan" @selected($status === 'menunggu_persetujuan')>Menunggu Persetujuan</option>
            <option value="disetujui" @selected($status === 'disetujui')>Disetujui</option>
            <option value="ditolak" @selected($status === 'ditolak')>Ditolak</option>
        </select>
    </div>
    <button class="btn btn-primary">Terapkan Filter</button>
    @if ($tahun || $bulan || $departemenId || $status)
    <a href="{{ route('sdm.monitoring.index') }}" class="btn btn-outline">Reset</a>
    @endif
</form>

<div class="table-scroll-wrapper">
    <table class="table-pro">
        <thead>
            <tr>
                <th>Nomor</th>
                <th>Pegawai</th>
                <th>Departemen</th>
                <th>Tanggal Dispensasi</th>
                <th>Waktu</th>
                <th>Diproses Oleh</th>
                <th>Status</th>
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
                <td class="text-ink-soft">
                    {{ $d->departemen->nama_departemen }}
                    @if ($d->subdepartemen)
                    <span class="text-xs">/ {{ $d->subdepartemen->nama_subdepartemen }}</span>
                    @endif
                </td>
                <td>{{ $d->tanggal_dispensasi->format('d M Y') }}</td>
                <td class="text-ink-soft">{{ $waktuLabel }}</td>
                <td class="text-ink-soft">{{ $d->diprosesOleh?->name ?? '-' }}</td>
                <td><span class="badge {{ $statusClass }}">{{ $statusLabel }}</span></td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center text-ink-soft py-12">
                    <i class="fas fa-inbox text-2xl mb-2 block"></i>
                    Belum ada data dispensasi sesuai filter yang dipilih.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if ($dispensasis->isNotEmpty())
<p class="text-xs text-ink-soft mt-3">Menampilkan {{ $dispensasis->count() }} data.</p>
@endif
@endsection