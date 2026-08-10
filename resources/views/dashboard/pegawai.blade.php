@extends('layouts.app')
@section('title', 'Dashboard Pegawai')

@section('content')
<div class="mb-8">
    <p class="text-xs font-semibold tracking-widest text-accent uppercase mb-1">Dashboard</p>
    <h1 class="font-display text-3xl text-ink">Halo, {{ explode(' ', auth()->user()->name)[0] }}</h1>
    <p class="text-ink-soft text-sm mt-1">Kelola pengajuan dispensasi Anda di sini.</p>
</div>

<div class="grid sm:grid-cols-2 gap-4 max-w-xl">
    <a href="{{ route('dispensasi.create') }}" class="card p-6 hover:border-accent transition-colors">
        <div class="w-10 h-10 rounded-lg bg-accent-soft flex items-center justify-center mb-4">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#0E4F56" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
        </div>
        <p class="font-semibold text-ink">Ajukan Dispensasi</p>
        <p class="text-ink-soft text-sm mt-1">Buat pengajuan baru beserta bukti pendukung.</p>
    </a>

    <a href="{{ route('dispensasi.index') }}" class="card p-6 hover:border-accent transition-colors">
        <div class="w-10 h-10 rounded-lg bg-accent-soft flex items-center justify-center mb-4">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#0E4F56" stroke-width="2"><path d="M3 3v18h18M18 9l-5 5-4-4-3 3"/></svg>
        </div>
        <p class="font-semibold text-ink">Riwayat Pengajuan</p>
        <p class="text-ink-soft text-sm mt-1">Pantau status seluruh pengajuan Anda.</p>
    </a>
</div>
@endsection