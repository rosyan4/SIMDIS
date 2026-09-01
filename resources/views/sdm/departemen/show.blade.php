@extends('layouts.app')
@section('title', $departemen->nama_departemen)
@section('page-title', 'Struktur Departemen')

@section('content')
<div class="mb-8">
    <a href="{{ route('sdm.departemen.index') }}" class="text-xs text-accent font-semibold mb-2 inline-flex items-center gap-1">
        <i class="fas fa-arrow-left"></i> Kembali ke Struktur Departemen
    </a>
    <p class="text-[11px] font-mono text-ink-soft mt-2">{{ $departemen->kode_departemen }}</p>
    <h1 class="font-display text-3xl text-ink">{{ $departemen->nama_departemen }}</h1>
</div>

{{-- Ringkasan --}}
<div class="grid sm:grid-cols-3 gap-4 mb-6">
    <div class="card p-5">
        <p class="text-xs text-ink-soft mb-1">Dispensasi Tahun Ini</p>
        <p class="font-display text-2xl text-ink mono-data">{{ $statistikDispensasi['total_tahun_ini'] }}</p>
    </div>
    <div class="card p-5">
        <p class="text-xs text-ink-soft mb-1">Bulan Ini</p>
        <p class="font-display text-2xl text-ink mono-data">{{ $statistikDispensasi['bulan_ini'] }}</p>
    </div>
    <div class="card p-5">
        <p class="text-xs text-ink-soft mb-1">Menunggu Persetujuan</p>
        <p class="font-display text-2xl text-[#C8862B] mono-data">{{ $statistikDispensasi['per_status']['menunggu_persetujuan'] }}</p>
    </div>
</div>

<div class="grid lg:grid-cols-2 gap-4 mb-6">
    {{-- Manajer & Admin --}}
    <div class="card p-6">
        <h3 class="font-semibold text-ink mb-4">Manajer & Admin Departemen</h3>

        <div class="flex items-center gap-3 mb-4 pb-4 border-b border-line">
            <div class="h-10 w-10 rounded-lg bg-primary text-white font-bold flex items-center justify-center shrink-0">
                {{ $departemen->manajerAktif ? strtoupper(substr($departemen->manajerAktif->name, 0, 1)) : '-' }}
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold text-ink truncate">{{ $departemen->manajerAktif?->name ?? 'Belum ada Manajer aktif' }}</p>
                <p class="text-xs text-ink-soft">Manajer Departemen</p>
            </div>
            @if ($departemen->manajerAktif)
            <span class="badge badge-disetujui">Aktif</span>
            @else
            <span class="badge badge-menunggu">Kosong</span>
            @endif
        </div>

        @if ($departemen->adminDepartemens->isNotEmpty())
        <p class="text-xs font-semibold text-ink-soft uppercase tracking-wide mb-2">Admin Departemen</p>
        <div class="space-y-2">
            @foreach ($departemen->adminDepartemens as $admin)
            <div class="flex items-center gap-2 text-sm">
                <i class="fas fa-user-shield text-ink-soft w-4"></i>
                <span class="text-ink">{{ $admin->name }}</span>
                <span class="text-ink-soft text-xs">({{ $admin->email }})</span>
            </div>
            @endforeach
        </div>
        @else
        <p class="text-sm text-ink-soft">Belum ada Admin Departemen aktif.</p>
        @endif

        @if ($departemen->manajers->count() > 1)
        <p class="text-xs text-ink-soft mt-4 pt-3 border-t border-line">
            <i class="fas fa-triangle-exclamation text-[#C8862B]"></i>
            Ada {{ $departemen->manajers->count() }} akun dengan role Manajer Departemen tercatat di sini
            (termasuk yang nonaktif) — hanya satu yang aktif ditampilkan di atas.
        </p>
        @endif
    </div>

    {{-- Riwayat status dispensasi --}}
    <div class="card p-6">
        <h3 class="font-semibold text-ink mb-4">Status Dispensasi Tahun Ini</h3>
        <div class="space-y-3">
            <div class="flex items-center justify-between text-sm">
                <span class="flex items-center gap-2"><span class="badge badge-menunggu">Menunggu</span></span>
                <span class="mono-data text-ink font-semibold">{{ $statistikDispensasi['per_status']['menunggu_persetujuan'] }}</span>
            </div>
            <div class="flex items-center justify-between text-sm">
                <span class="flex items-center gap-2"><span class="badge badge-disetujui">Disetujui</span></span>
                <span class="mono-data text-ink font-semibold">{{ $statistikDispensasi['per_status']['disetujui'] }}</span>
            </div>
            <div class="flex items-center justify-between text-sm">
                <span class="flex items-center gap-2"><span class="badge badge-ditolak">Ditolak</span></span>
                <span class="mono-data text-ink font-semibold">{{ $statistikDispensasi['per_status']['ditolak'] }}</span>
            </div>
        </div>
    </div>
</div>

{{-- Subdepartemen --}}
<div class="card overflow-hidden mb-6">
    <div class="px-6 py-4 border-b border-line">
        <h3 class="font-semibold text-ink">Subdepartemen ({{ $departemen->subdepartemens->count() }})</h3>
    </div>
    <table class="table-pro">
        <thead>
            <tr>
                <th>Kode</th>
                <th>Nama Subdepartemen</th>
                <th>Asisten Manajer</th>
                <th>Pegawai Aktif</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($departemen->subdepartemens as $sub)
            @php $ss = collect($statistikSubdepartemen)->firstWhere('subdepartemen_id', $sub->id); @endphp
            <tr>
                <td class="mono-data text-ink-soft">{{ $sub->kode_subdepartemen }}</td>
                <td class="font-medium">{{ $sub->nama_subdepartemen }}</td>
                <td>
                    @if ($sub->asistenManajerAktif)
                    <span class="text-ink">{{ $sub->asistenManajerAktif->name }}</span>
                    @else
                    <span class="text-ink-soft">Belum ditugaskan</span>
                    @endif
                </td>
                <td class="mono-data">{{ $ss['pegawai_aktif'] ?? 0 }}</td>
            </tr>
            @empty
            <tr><td colspan="4" class="text-center text-ink-soft py-8">Departemen ini belum punya subdepartemen.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Pegawai langsung di departemen (tanpa subdepartemen) --}}
@php $pegawaiLangsung = $departemen->pegawais->whereNull('subdepartemen_id'); @endphp
@if ($pegawaiLangsung->isNotEmpty())
<div class="card overflow-hidden">
    <div class="px-6 py-4 border-b border-line">
        <h3 class="font-semibold text-ink">Pegawai Langsung di Departemen ({{ $pegawaiLangsung->count() }})</h3>
        <p class="text-xs text-ink-soft mt-0.5">Tidak ditempatkan di subdepartemen manapun.</p>
    </div>
    <table class="table-pro">
        <thead>
            <tr><th>NIK</th><th>Nama</th><th>Jabatan</th></tr>
        </thead>
        <tbody>
            @foreach ($pegawaiLangsung as $p)
            <tr>
                <td class="mono-data text-ink-soft">{{ $p->nik }}</td>
                <td class="font-medium">{{ $p->nama_pegawai }}</td>
                <td class="text-ink-soft">{{ $p->jabatan }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif
@endsection