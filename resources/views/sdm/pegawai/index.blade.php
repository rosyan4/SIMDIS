@extends('layouts.app')
@section('title', 'Kelola Pegawai')

@section('content')
<div class="flex justify-between items-end mb-8">
    <div>
        <p class="text-xs font-semibold tracking-widest text-accent uppercase mb-1">Admin SDM</p>
        <h1 class="font-display text-3xl text-ink">Kelola Data Pegawai</h1>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('sdm.pegawai.import.form') }}" class="btn btn-outline">Import Excel</a>
        <a href="{{ route('sdm.pegawai.create') }}" class="btn btn-primary">+ Tambah Pegawai</a>
    </div>
</div>

<div class="space-y-3" x-data="{ openDept: null }">
    @foreach ($departemens as $dept)
    <div class="card overflow-hidden">
        <button @click="openDept = openDept === {{ $dept->id }} ? null : {{ $dept->id }}"
                class="w-full flex justify-between items-center px-6 py-4 hover:bg-canvas">
            <div class="text-left">
                <span class="font-semibold text-ink">{{ $dept->nama_departemen }}</span>
                <span class="text-xs text-ink-soft ml-2">
                    ({{ $dept->subdepartemens->sum(fn($s) => $s->pegawais->count()) }} pegawai)
                </span>
                <div class="text-xs text-ink-soft mt-0.5">Manajer: {{ $dept->manajer?->name ?? '— belum ditentukan —' }}</div>
            </div>
            <span class="text-ink-soft text-xs" x-text="openDept === {{ $dept->id }} ? '▲' : '▼'"></span>
        </button>

        <div x-show="openDept === {{ $dept->id }}" x-cloak class="border-t border-line">
            @forelse ($dept->subdepartemens as $sub)
            <div class="px-6 py-4 border-b border-line last:border-b-0">
                <div class="text-sm font-medium text-ink mb-3">
                    {{ $sub->nama_subdepartemen }}
                    <span class="text-xs text-ink-soft font-normal">— Asisten Manajer: {{ $sub->asistenManajer?->name ?? '-' }}</span>
                </div>

                @if ($sub->pegawais->isEmpty())
                    <p class="text-xs text-ink-soft">Belum ada pegawai di subdepartemen ini.</p>
                @else
                <table class="table-pro">
                    <thead><tr><th>Nama</th><th>Email</th><th>Role</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($sub->pegawais as $p)
                        <tr>
                            <td class="font-medium">{{ $p->name }}</td>
                            <td class="text-ink-soft">{{ $p->email }}</td>
                            <td class="capitalize">{{ str_replace('_', ' ', $p->role) }}</td>
                            <td><span class="badge {{ $p->is_active ? 'badge-disetujui' : 'badge-neutral' }}">{{ $p->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                            <td>
                                <div class="flex gap-3">
                                    <a href="{{ route('sdm.pegawai.edit', $p) }}" class="text-primary font-medium hover:underline">Edit</a>
                                    @if ($p->is_active)
                                    <form method="POST" action="{{ route('sdm.pegawai.destroy', $p) }}" onsubmit="return confirm('Nonaktifkan pegawai ini?')">
                                        @csrf @method('DELETE')
                                        <button class="text-[#C1483A] font-medium hover:underline">Nonaktifkan</button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @endif
            </div>
            @empty
            <p class="px-6 py-4 text-xs text-ink-soft">Belum ada subdepartemen di departemen ini.</p>
            @endforelse
        </div>
    </div>
    @endforeach

    @if ($tanpaSubdepartemen->isNotEmpty())
    <div class="card overflow-hidden">
        <div class="px-6 py-4 font-semibold text-ink border-b border-line">
            Tanpa Subdepartemen <span class="text-xs text-ink-soft font-normal ml-2">({{ $tanpaSubdepartemen->count() }} pegawai)</span>
        </div>
        <table class="table-pro">
            <thead><tr><th>Nama</th><th>Email</th><th>Role</th><th>Status</th><th></th></tr></thead>
            <tbody>
                @foreach ($tanpaSubdepartemen as $p)
                <tr>
                    <td class="font-medium">{{ $p->name }}</td>
                    <td class="text-ink-soft">{{ $p->email }}</td>
                    <td class="capitalize">{{ str_replace('_', ' ', $p->role) }}</td>
                    <td><span class="badge {{ $p->is_active ? 'badge-disetujui' : 'badge-neutral' }}">{{ $p->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                    <td><a href="{{ route('sdm.pegawai.edit', $p) }}" class="text-primary font-medium hover:underline">Edit</a></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
@endsection