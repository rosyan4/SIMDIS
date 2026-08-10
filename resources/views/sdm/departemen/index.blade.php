@extends('layouts.app')
@section('title', 'Struktur Departemen')

@section('content')
<div class="mb-8">
    <p class="text-xs font-semibold tracking-widest text-accent uppercase mb-1">Admin SDM</p>
    <h1 class="font-display text-3xl text-ink mb-2">Struktur Departemen & Subdepartemen</h1>
    <p class="text-ink-soft text-sm">
        Struktur organisasi bersifat tetap. Untuk mengganti Manajer/Asisten Manajer, ubah role pegawai lewat
        <a href="{{ route('sdm.pegawai.index') }}" class="text-primary font-medium hover:underline">Kelola Pegawai</a>.
    </p>
</div>

<div class="space-y-3">
    @foreach ($departemens as $dept)
    <div class="card p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="font-semibold text-ink">{{ $dept->nama_departemen }}</h2>
            <span class="text-sm text-ink-soft">Manajer: <span class="font-medium text-ink">{{ $dept->manajer?->name ?? '— belum ditentukan —' }}</span></span>
        </div>

        <ul class="space-y-2">
            @forelse ($dept->subdepartemens as $sub)
            <li class="flex justify-between text-sm border-t border-line pt-2">
                <span class="text-ink">{{ $sub->nama_subdepartemen }}</span>
                <span class="text-ink-soft">Asisten Manajer: <span class="font-medium text-ink">{{ $sub->asistenManajer?->name ?? '-' }}</span></span>
            </li>
            @empty
            <li class="text-ink-soft text-sm">Belum ada subdepartemen.</li>
            @endforelse
        </ul>
    </div>
    @endforeach
</div>
@endsection