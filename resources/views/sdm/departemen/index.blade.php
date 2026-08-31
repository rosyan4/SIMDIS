@extends('layouts.app')
@section('title', 'Struktur Departemen')
@section('page-title', 'Struktur Departemen')

@section('content')
<div class="mb-8">
    <p class="text-xs font-semibold tracking-widest text-accent uppercase mb-1">Admin SDM</p>
    <h1 class="font-display text-3xl text-ink">Struktur Departemen</h1>
    <p class="text-sm text-ink-soft mt-1">Data master — hanya dapat dilihat, tidak bisa diubah dari sini.</p>
</div>

@php
    // Statistik dari controller berupa array datar, dikelompokkan ulang di sini
    // berdasarkan departemen_id supaya gampang dicocokkan per kartu departemen.
    $statistikByDept = collect($statistik)->keyBy('departemen_id');
@endphp

{{-- Filter --}}
<form method="GET" class="card p-4 mb-6 flex gap-3 flex-wrap items-end">
    <div class="flex-1 min-w-[200px]">
        <label class="text-xs text-ink-soft mb-1 block">Cari (Kode / Nama)</label>
        <input type="text" name="search" value="{{ $search }}" class="field-input" placeholder="Ketik kode atau nama departemen...">
    </div>
    <button class="btn btn-primary">Cari</button>
    @if ($search || $filterDepartemen)
    <a href="{{ route('sdm.departemen.index') }}" class="btn btn-outline">Reset</a>
    @endif
</form>

<div class="grid md:grid-cols-2 gap-4">
    @forelse ($departemens as $d)
    @php $s = $statistikByDept->get($d->id); @endphp
    <a href="{{ route('sdm.departemen.show', $d->id) }}" class="card p-5 hover:border-accent transition-colors block">
        <div class="flex items-start justify-between gap-3 mb-3">
            <div>
                <p class="text-[11px] font-mono text-ink-soft mb-0.5">{{ $d->kode_departemen }}</p>
                <h3 class="font-display text-lg text-ink leading-snug">{{ $d->nama_departemen }}</h3>
            </div>
            @if ($d->manajerAktif)
            <span class="badge badge-disetujui shrink-0">Manajer Aktif</span>
            @else
            <span class="badge badge-menunggu shrink-0">Belum Ada Manajer</span>
            @endif
        </div>

        <div class="flex items-center gap-2 text-sm text-ink-soft mb-4">
            <i class="fas fa-user-tie w-4"></i>
            <span>{{ $d->manajerAktif?->name ?? 'Belum ditugaskan' }}</span>
        </div>

        <div class="grid grid-cols-3 gap-2 text-center border-t border-line pt-3">
            <div>
                <p class="font-display text-lg text-ink mono-data">{{ $s['pegawai_aktif'] ?? 0 }}</p>
                <p class="text-[11px] text-ink-soft">Pegawai Aktif</p>
            </div>
            <div>
                <p class="font-display text-lg text-ink mono-data">{{ $s['total_subdepartemen'] ?? 0 }}</p>
                <p class="text-[11px] text-ink-soft">Subdepartemen</p>
            </div>
            <div>
                <p class="font-display text-lg text-ink mono-data">{{ $d->subdepartemens->filter(fn ($sub) => $sub->asistenManajerAktif)->count() }}</p>
                <p class="text-[11px] text-ink-soft">Asmen Aktif</p>
            </div>
        </div>
    </a>
    @empty
    <div class="card p-10 text-center text-ink-soft md:col-span-2">Tidak ada departemen sesuai pencarian.</div>
    @endforelse
</div>
@endsection