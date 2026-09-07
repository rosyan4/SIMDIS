@extends('layouts.app')
@section('title', 'Persetujuan Dispensasi')
@section('page-title', 'Persetujuan Dispensasi')

@section('content')
<div class="mb-8">
    <p class="text-xs font-semibold tracking-widest text-accent uppercase mb-1">Persetujuan</p>
    <h1 class="font-display text-3xl text-ink">Menunggu Keputusan Anda</h1>
    <p class="text-sm text-ink-soft mt-1">Pengajuan dispensasi yang perlu Anda setujui atau tolak.</p>
</div>

<div class="table-scroll-wrapper">
    <table class="table-pro">
        <thead>
            <tr>
                <th>Nomor</th>
                <th>Pegawai</th>
                <th>Departemen</th>
                <th>Tanggal Dispensasi</th>
                <th>Waktu</th>
                <th>Diajukan</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($dispensasis as $d)
            @php
                $waktuLabel = $d->waktu_dispensasi;
            @endphp
            <tr>
                <td class="mono-data text-ink-soft">{{ $d->nomor_dispensasi }}</td>
                <td>
                    <p class="font-medium text-ink">{{ $d->pegawai->nama_pegawai }}</p>
                    <p class="text-xs text-ink-soft">{{ $d->pegawai->jabatan }}</p>
                </td>
                <td class="text-ink-soft">
                    {{ $d->departemen->nama_departemen }}
                    @if ($d->subdepartemen)
                    <span class="text-xs">/ {{ $d->subdepartemen->nama_subdepartemen }}</span>
                    @endif
                </td>
                <td>{{ $d->tanggal_dispensasi->format('d M Y') }}</td>
                <td class="text-ink-soft">{{ $waktuLabel }}</td>
                <td class="text-ink-soft text-xs">
                    {{ $d->tanggal_pengajuan->format('d M Y') }}
                    <span class="block">({{ $d->created_at->diffForHumans() }})</span>
                </td>
                <td class="text-right whitespace-nowrap">
                    <a href="{{ route('approval.show', $d) }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-eye"></i> Tinjau
                    </a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center text-ink-soft py-12">
                    <i class="fas fa-circle-check text-2xl mb-2 block"></i>
                    Tidak ada pengajuan yang menunggu persetujuan Anda saat ini.
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