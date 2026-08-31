@extends('layouts.app')
@section('title', 'Persetujuan Dispensasi')
@section('page-title', 'Persetujuan Dispensasi')

@section('content')
<div class="mb-8">
    <p class="text-xs font-semibold tracking-widest text-accent uppercase mb-1">Asisten Manajer</p>
    <h1 class="font-display text-3xl text-ink">Persetujuan Dispensasi</h1>
    <p class="text-sm text-ink-soft mt-1">
        Menampilkan pengajuan di subdepartemen Anda yang dialihkan karena
        Manajer Departemen terkait sedang berstatus "Berhalangan".
    </p>
</div>

<div class="table-scroll-wrapper">
    <table class="table-pro">
        <thead>
            <tr>
                <th>Nomor</th>
                <th>Pegawai</th>
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
                <td colspan="6" class="text-center text-ink-soft py-12">
                    <i class="fas fa-inbox text-2xl mb-2 block"></i>
                    Tidak ada pengajuan yang perlu Anda tinjau saat ini.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{ $dispensasis->links() }}
@endsection