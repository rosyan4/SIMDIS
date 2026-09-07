@extends('layouts.app')
@section('title', 'Pengajuan Dispensasi')
@section('page-title', 'Pengajuan Dispensasi')

@section('content')
<div class="flex items-start justify-between gap-4 mb-8 flex-wrap">
    <div>
        <p class="text-xs font-semibold tracking-widest text-accent uppercase mb-1">Admin Departemen</p>
        <h1 class="font-display text-3xl text-ink">Pengajuan Dispensasi</h1>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('dispensasi.export.pdf', request()->query()) }}" class="btn btn-outline">
            <i class="fas fa-file-pdf"></i> Export ke PDF
        </a>
        <a href="{{ route('dispensasi.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Ajukan Dispensasi
        </a>
    </div>
</div>

<p class="text-xs text-ink-soft -mt-6 mb-6">
    <i class="fas fa-circle-info"></i>
    Export PDF hanya menyertakan pengajuan berstatus <strong>Disetujui</strong> sesuai filter tahun/bulan yang sedang aktif di bawah.
</p>

{{-- Filter --}}
<form method="GET" class="card p-4 mb-6 flex gap-3 flex-wrap items-end">
    <div class="flex-1 min-w-[160px]">
        <label class="text-xs text-ink-soft mb-1 block">Tahun</label>
        <select name="tahun" class="field-input">
            <option value="">Semua Tahun</option>
            @foreach ($tahunTersedia as $t)
            <option value="{{ $t }}" @selected($tahun == $t)>{{ $t }}</option>
            @endforeach
        </select>
    </div>
    <div class="flex-1 min-w-[160px]">
        <label class="text-xs text-ink-soft mb-1 block">Bulan</label>
        <select name="bulan" class="field-input">
            <option value="">Semua Bulan</option>
            @foreach ($namaBulan as $angka => $nama)
            <option value="{{ $angka }}" @selected($bulan == $angka)>{{ $nama }}</option>
            @endforeach
        </select>
    </div>
    <button class="btn btn-primary">Terapkan Filter</button>
    @if ($tahun || $bulan)
    <a href="{{ route('dispensasi.index') }}" class="btn btn-outline">Reset</a>
    @endif
</form>

<div class="table-scroll-wrapper">
    <table class="table-pro">
        <thead>
            <tr>
                <th>Nomor</th>
                <th>Pegawai</th>
                <th>Tanggal Dispensasi</th>
                <th>Waktu</th>
                <th>Status</th>
                <th>Diajukan</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($dispensasis as $d)
            @php
                $waktuLabel = $d->waktu_dispensasi;
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
            @endphp
            <tr>
                <td class="mono-data text-ink-soft">{{ $d->nomor_dispensasi }}</td>
                <td>
                    <p class="font-medium text-ink">{{ $d->pegawai->nama_pegawai }}</p>
                    @if ($d->subdepartemen)
                    <p class="text-xs text-ink-soft">{{ $d->subdepartemen->nama_subdepartemen }}</p>
                    @endif
                </td>
                <td>{{ $d->tanggal_dispensasi->format('d M Y') }}</td>
                <td class="text-ink-soft">{{ $waktuLabel }}</td>
                <td><span class="badge {{ $statusClass }}">{{ $statusLabel }}</span></td>
                <td class="text-ink-soft text-xs">{{ $d->tanggal_pengajuan->format('d M Y') }}</td>
                <td class="text-right whitespace-nowrap">
                    <a href="{{ route('dispensasi.show', $d) }}" class="btn btn-sm btn-outline">
                        <i class="fas fa-eye"></i>
                    </a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center text-ink-soft py-12">
                    <i class="fas fa-inbox text-2xl mb-2 block"></i>
                    Belum ada pengajuan dispensasi dari departemen Anda.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if ($dispensasis->isNotEmpty())
<div class="flex items-center justify-between flex-wrap gap-3 mt-3">
    <p class="text-xs text-ink-soft">
        Menampilkan {{ $dispensasis->firstItem() }}–{{ $dispensasis->lastItem() }}
        dari {{ $dispensasis->total() }} pengajuan.
    </p>
    {{ $dispensasis->links() }}
</div>
@endif
@endsection