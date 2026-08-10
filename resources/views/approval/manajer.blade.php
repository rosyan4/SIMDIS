@extends('layouts.app')
@section('title', 'Dashboard Manajer Departemen')

@section('content')
<div class="mb-8">
    <p class="text-xs font-semibold tracking-widest text-accent uppercase mb-1">Persetujuan</p>
    <h1 class="font-display text-3xl text-ink">Pengajuan Dispensasi Masuk</h1>
</div>

<div class="card overflow-hidden">
    <table class="table-pro">
        <thead>
            <tr>
                <th>Nomor</th><th>Pegawai</th><th>Tanggal</th><th>Keterangan</th><th>Diajukan</th><th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($dispensasis as $d)
            <tr>
                <td class="mono-data text-ink-soft">{{ $d->nomor_dispensasi }}</td>
                <td class="font-medium">{{ $d->pegawai->name }}</td>
                <td>{{ $d->tanggal_dispensasi->format('d M Y') }}</td>
                <td>{{ Str::limit($d->alasan, 40) }}</td>
                <td class="text-ink-soft text-xs">{{ $d->created_at->diffForHumans() }}</td>
                <td>
                    <a href="{{ route('approval.show', $d) }}" class="btn btn-outline btn-sm">Lihat Detail</a>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="text-center text-ink-soft py-10">Tidak ada pengajuan menunggu.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $dispensasis->links() }}</div>
@endsection