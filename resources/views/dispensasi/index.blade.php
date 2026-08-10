@extends('layouts.app')
@section('title', 'Riwayat Dispensasi')

@section('content')
<div class="flex justify-between items-end mb-8">
    <div>
        <p class="text-xs font-semibold tracking-widest text-accent uppercase mb-1">Riwayat</p>
        <h1 class="font-display text-3xl text-ink">Pengajuan Dispensasi</h1>
    </div>
    <a href="{{ route('dispensasi.create') }}" class="btn btn-primary">+ Ajukan Baru</a>
</div>

<div class="card overflow-hidden">
    <table class="table-pro">
        <thead>
            <tr>
                <th>Nomor</th><th>Tanggal</th><th>Keterangan</th><th>Status</th><th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($dispensasis as $d)
            <tr>
                <td class="mono-data text-ink-soft">{{ $d->nomor_dispensasi }}</td>
                <td>{{ $d->tanggal_dispensasi->format('d M Y') }}</td>
                <td>{{ Str::limit($d->alasan, 40) }}</td>
                <td><span class="badge badge-{{ $d->status }}">{{ ucfirst($d->status) }}</span></td>
                <td><a href="{{ route('dispensasi.show', $d) }}" class="text-primary font-medium hover:underline text-sm">Detail</a></td>
            </tr>
            @empty
            <tr><td colspan="5" class="text-center text-ink-soft py-10">Belum ada pengajuan.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $dispensasis->links() }}</div>
@endsection