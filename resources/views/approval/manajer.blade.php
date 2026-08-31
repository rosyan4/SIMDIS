@extends('layouts.app')
@section('title', 'Persetujuan Dispensasi')
@section('page-title', 'Persetujuan Dispensasi')

@section('content')
<div class="mb-8">
    <p class="text-xs font-semibold tracking-widest text-accent uppercase mb-1">Manajer Departemen</p>
    <h1 class="font-display text-3xl text-ink">Persetujuan Dispensasi</h1>
</div>

@if ($sedangBerhalangan)
<div class="card p-10 text-center">
    <i class="fas fa-user-clock text-3xl text-[#C8862B] mb-3"></i>
    <h3 class="font-semibold text-ink mb-1">Status Anda sedang "Berhalangan"</h3>
    <p class="text-sm text-ink-soft max-w-md mx-auto">
        Selama status ini aktif, pengajuan dispensasi baru di departemen Anda
        diarahkan otomatis ke Asisten Manajer subdepartemen masing-masing pegawai.
        Hubungi Admin SDM kalau status ini perlu diubah kembali menjadi Aktif.
    </p>
</div>
@else

<div class="table-scroll-wrapper">
    <table class="table-pro">
        <thead>
            <tr>
                <th>Nomor</th>
                <th>Pegawai</th>
                <th>Subdepartemen</th>
                <th>Tanggal Dispensasi</th>
                <th>Waktu</th>
                <th>Diajukan</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($dispensasis as $d)
            @php
                $waktuLabel = ['pagi' => 'Pagi', 'istirahat' => 'Istirahat', 'siang' => 'Siang', 'sore' => 'Sore'][$d->waktu_dispensasi] ?? $d->waktu_dispensasi;
            @endphp
            <tr>
                <td class="mono-data text-ink-soft">{{ $d->nomor_dispensasi }}</td>
                <td class="font-medium">{{ $d->pegawai->nama_pegawai }}</td>
                <td class="text-ink-soft">{{ $d->subdepartemen?->nama_subdepartemen ?? '-' }}</td>
                <td>{{ $d->tanggal_dispensasi->format('d M Y') }}</td>
                <td class="text-ink-soft">{{ $waktuLabel }}</td>
                <td class="text-ink-soft text-xs">{{ $d->created_at->diffForHumans() }}</td>
                <td class="text-right">
                    <a href="{{ route('approval.show', $d) }}" class="btn btn-sm btn-primary">
                        Tinjau <i class="fas fa-arrow-right"></i>
                    </a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center text-ink-soft py-12">
                    <i class="fas fa-inbox text-2xl mb-2 block"></i>
                    Tidak ada pengajuan yang menunggu persetujuan Anda saat ini.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{ $dispensasis->links() }}
@endif
@endsection