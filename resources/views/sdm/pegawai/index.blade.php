@extends('layouts.app')
@section('title', 'Kelola Pegawai')
@section('page-title', 'Kelola Pegawai')

@section('content')
<div class="flex items-start justify-between gap-4 mb-8 flex-wrap">
    <div>
        <p class="text-xs font-semibold tracking-widest text-accent uppercase mb-1">Admin SDM</p>
        <h1 class="font-display text-3xl text-ink">Kelola Pegawai</h1>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('sdm.pegawai.import.form') }}" class="btn btn-outline">
            <i class="fas fa-file-import"></i> Import Excel
        </a>
        <a href="{{ route('sdm.pegawai.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Tambah Pegawai
        </a>
    </div>
</div>

{{-- Filter --}}
<form method="GET" class="card p-4 mb-6 flex gap-3 flex-wrap items-end">
    <div class="flex-1 min-w-[200px]">
        <label class="text-xs text-ink-soft mb-1 block">Cari (NIK / Nama)</label>
        <input type="text" name="search" value="{{ $search }}" class="field-input" placeholder="Ketik NIK atau nama...">
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
            <option value="aktif" @selected($status === 'aktif')>Aktif</option>
            <option value="nonaktif" @selected($status === 'nonaktif')>Nonaktif</option>
        </select>
    </div>
    <button class="btn btn-primary">Terapkan</button>
    @if ($search || $departemenId || $status)
    <a href="{{ route('sdm.pegawai.index') }}" class="btn btn-outline">Reset</a>
    @endif
</form>

<div class="table-scroll-wrapper">
    <table class="table-pro">
        <thead>
            <tr>
                <th>NIK</th>
                <th>Nama</th>
                <th>Jabatan</th>
                <th>Departemen</th>
                <th>Subdepartemen</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($pegawais as $p)
            <tr>
                <td class="mono-data text-ink-soft">{{ $p->nik }}</td>
                <td class="font-medium">{{ $p->nama_pegawai }}</td>
                <td>{{ $p->jabatan }}</td>
                <td class="text-ink-soft">{{ $p->departemen->nama_departemen }}</td>
                <td class="text-ink-soft">{{ $p->subdepartemen?->nama_subdepartemen ?? '-' }}</td>
                <td>
                    <span class="badge {{ $p->status === 'aktif' ? 'badge-disetujui' : 'badge-default' }}">
                        {{ ucfirst($p->status) }}
                    </span>
                </td>
                <td class="text-right whitespace-nowrap">
                    <a href="{{ route('sdm.pegawai.edit', $p) }}" class="btn btn-sm btn-outline">
                        <i class="fas fa-pen"></i>
                    </a>
                    @if ($p->status === 'aktif')
                    <form method="POST" action="{{ route('sdm.pegawai.destroy', $p) }}" class="inline"
                          onsubmit="return confirm('Nonaktifkan {{ $p->nama_pegawai }}?');">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-sm btn-danger"><i class="fas fa-ban"></i></button>
                    </form>
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="7" class="text-center text-ink-soft py-10">Tidak ada data pegawai sesuai filter.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

{{ $pegawais->links() }}
@endsection