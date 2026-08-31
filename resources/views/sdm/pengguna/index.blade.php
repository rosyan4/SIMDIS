@extends('layouts.app')
@section('title', 'Manajemen Pengguna')
@section('page-title', 'Manajemen Pengguna')

@section('content')
<div class="flex items-start justify-between gap-4 mb-8 flex-wrap">
    <div>
        <p class="text-xs font-semibold tracking-widest text-accent uppercase mb-1">Admin SDM</p>
        <h1 class="font-display text-3xl text-ink">Manajemen Pengguna</h1>
    </div>
    <a href="{{ route('sdm.pengguna.create') }}" class="btn btn-primary">
        <i class="fas fa-user-plus"></i> Tambah Pengguna
    </a>
</div>

{{-- Filter --}}
<form method="GET" class="card p-4 mb-6 flex gap-3 flex-wrap items-end">
    <div class="flex-1 min-w-[200px]">
        <label class="text-xs text-ink-soft mb-1 block">Cari</label>
        <input type="text" name="search" value="{{ $search }}" placeholder="Nama, username, atau email..." class="field-input">
    </div>
    <div>
        <label class="text-xs text-ink-soft mb-1 block">Role</label>
        <select name="role" class="field-input" style="width:auto">
            <option value="">Semua Role</option>
            <option value="admin_sdm" @selected($role === 'admin_sdm')>Admin SDM</option>
            <option value="admin_departemen" @selected($role === 'admin_departemen')>Admin Departemen</option>
            <option value="manajer_departemen" @selected($role === 'manajer_departemen')>Manajer Departemen</option>
            <option value="asisten_manajer" @selected($role === 'asisten_manajer')>Asisten Manajer</option>
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
            <option value="aktif" @selected($status === 'aktif')>Aktif</option>
            <option value="nonaktif" @selected($status === 'nonaktif')>Nonaktif</option>
        </select>
    </div>
    <button class="btn btn-primary">Terapkan Filter</button>
    @if ($search || $role || $departemenId || $status)
    <a href="{{ route('sdm.pengguna.index') }}" class="btn btn-outline">Reset</a>
    @endif
</form>

<div class="table-scroll-wrapper">
    <table class="table-pro">
        <thead>
            <tr>
                <th>Nama</th>
                <th>Username / Email</th>
                <th>Role</th>
                <th>Departemen / Sub</th>
                <th>Status Manajer</th>
                <th>Status Akun</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($users as $u)
            @php
                $roleLabel = [
                    'admin_sdm' => 'Admin SDM',
                    'admin_departemen' => 'Admin Departemen',
                    'manajer_departemen' => 'Manajer Departemen',
                    'asisten_manajer' => 'Asisten Manajer',
                ][$u->role] ?? ucfirst(str_replace('_', ' ', $u->role));
            @endphp
            <tr>
                <td class="font-medium">{{ $u->name }}</td>
                <td class="text-ink-soft">
                    <div>{{ $u->username }}</div>
                    <div class="text-xs">{{ $u->email }}</div>
                </td>
                <td><span class="badge badge-default">{{ $roleLabel }}</span></td>
                <td class="text-ink-soft">
                    @if ($u->departemen)
                        {{ $u->departemen->nama_departemen }}
                    @elseif ($u->subdepartemen)
                        {{ $u->subdepartemen->nama_subdepartemen }}
                        <span class="text-xs">/ {{ $u->subdepartemen->departemen->nama_departemen ?? '-' }}</span>
                    @else
                        <span class="text-xs">-</span>
                    @endif
                </td>
                <td>
                    @if ($u->isManajerDepartemen())
                        @if ($u->status_manajer === 'berhalangan')
                            <span class="badge badge-menunggu">Berhalangan</span>
                            @if ($u->tanggal_mulai_berhalangan || $u->tanggal_selesai_berhalangan)
                            <div class="text-xs text-ink-soft mt-1">
                                {{ optional($u->tanggal_mulai_berhalangan)->format('d M Y') ?? '-' }}
                                &ndash;
                                {{ optional($u->tanggal_selesai_berhalangan)->format('d M Y') ?? '-' }}
                            </div>
                            @endif
                        @else
                            <span class="badge badge-disetujui">Aktif</span>
                        @endif
                    @else
                        <span class="text-xs text-ink-soft">-</span>
                    @endif
                </td>
                <td>
                    @if ($u->is_active)
                        <span class="badge badge-disetujui">Aktif</span>
                    @else
                        <span class="badge badge-ditolak">Nonaktif</span>
                    @endif
                </td>
                <td class="text-right whitespace-nowrap">
                    <div class="flex gap-2 justify-end">
                        @if ($u->isManajerDepartemen())
                        <div x-data="{ open: false }" class="inline-block">
                            <button type="button" @click="open = true" class="btn btn-outline btn-sm">
                                <i class="fas fa-user-clock"></i>
                            </button>
                            <template x-teleport="body">
                                <div x-show="open" x-cloak
                                     x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                                     class="fixed inset-0 z-[300] flex items-center justify-center p-4"
                                     style="background: rgba(15,23,42,.48); backdrop-filter: blur(2px);">
                                    <div @click.outside="open = false" class="card w-full max-w-sm p-6">
                                        <h3 class="font-bold text-ink mb-4">Status Manajer — {{ $u->name }}</h3>
                                        <form method="POST" action="{{ route('sdm.pengguna.status-manajer', $u) }}" x-data="{ status: '{{ $u->status_manajer }}' }">
                                            @csrf
                                            <div class="mb-3">
                                                <label class="text-xs text-ink-soft mb-1 block">Status</label>
                                                <select name="status_manajer" x-model="status" class="field-input">
                                                    <option value="aktif">Aktif</option>
                                                    <option value="berhalangan">Berhalangan</option>
                                                </select>
                                            </div>
                                            <div x-show="status === 'berhalangan'" x-cloak class="space-y-3 mb-3">
                                                <div>
                                                    <label class="text-xs text-ink-soft mb-1 block">Tanggal Mulai</label>
                                                    <input type="date" name="tanggal_mulai_berhalangan" value="{{ $u->tanggal_mulai_berhalangan?->format('Y-m-d') }}" class="field-input">
                                                </div>
                                                <div>
                                                    <label class="text-xs text-ink-soft mb-1 block">Tanggal Selesai</label>
                                                    <input type="date" name="tanggal_selesai_berhalangan" value="{{ $u->tanggal_selesai_berhalangan?->format('Y-m-d') }}" class="field-input">
                                                </div>
                                                <div>
                                                    <label class="text-xs text-ink-soft mb-1 block">Alasan</label>
                                                    <input type="text" name="alasan_berhalangan" value="{{ $u->alasan_berhalangan }}" placeholder="Cuti, dinas, sakit, dsb." class="field-input" maxlength="200">
                                                </div>
                                            </div>
                                            <div class="flex gap-2 justify-end mt-4">
                                                <button type="button" @click="open = false" class="btn btn-outline">Batal</button>
                                                <button type="submit" class="btn btn-primary">Simpan</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </template>
                        </div>
                        @endif

                        <a href="{{ route('sdm.pengguna.edit', $u) }}" class="btn btn-outline btn-sm">
                            <i class="fas fa-pen"></i>
                        </a>

                        @if ($u->is_active)
                        <form method="POST" action="{{ route('sdm.pengguna.destroy', $u) }}" onsubmit="return confirm('Nonaktifkan akun {{ $u->name }}?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline btn-sm" style="color:#C1483A;">
                                <i class="fas fa-user-slash"></i>
                            </button>
                        </form>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center text-ink-soft py-12">
                    <i class="fas fa-users-slash text-2xl mb-2 block"></i>
                    Tidak ada pengguna yang sesuai filter.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if ($users->hasPages())
<div class="mt-4">
    {{ $users->links() }}
</div>
@endif
@endsection