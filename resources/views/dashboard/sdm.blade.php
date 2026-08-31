@extends('layouts.app')
@section('title', 'Dashboard Admin SDM')

@section('content')
<div class="mb-8">
    <p class="text-xs font-semibold tracking-widest text-accent uppercase mb-1">Dashboard</p>
    <h1 class="font-display text-3xl text-ink">Admin SDM</h1>
</div>

{{-- Filter --}}
<form method="GET" class="card p-4 mb-8 flex gap-3 flex-wrap items-end">
    <div>
        <label class="text-xs text-ink-soft mb-1 block">Periode / Tahun</label>
        <select name="tahun" class="field-input" style="width:auto">
            @foreach ($tahunTersedia as $t)
            <option value="{{ $t }}" @selected($tahun == $t)>{{ $t }}</option>
            @endforeach
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
            <option value="menunggu_persetujuan" @selected($status === 'menunggu_persetujuan')>Menunggu Persetujuan</option>
            <option value="disetujui" @selected($status === 'disetujui')>Disetujui</option>
            <option value="ditolak" @selected($status === 'ditolak')>Ditolak</option>
        </select>
    </div>
    <button class="btn btn-primary">Terapkan Filter</button>
    @if ($departemenId || $status)
    <a href="{{ route('sdm.dashboard') }}" class="btn btn-outline">Reset</a>
    @endif
</form>

{{-- 1. Total dispensasi --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
    <div class="card p-5">
        <p class="text-xs text-ink-soft mb-1">Total Dispensasi</p>
        <p class="font-display text-2xl text-ink mono-data">{{ $totalSemua }}</p>
    </div>
    <div class="card p-5">
        <p class="text-xs text-ink-soft mb-1">Menunggu Persetujuan</p>
        <p class="font-display text-2xl text-[#C8862B] mono-data">{{ $totalMenunggu }}</p>
    </div>
    <div class="card p-5">
        <p class="text-xs text-ink-soft mb-1">Disetujui</p>
        <p class="font-display text-2xl text-[#2E9E6B] mono-data">{{ $totalDisetujui }}</p>
    </div>
    <div class="card p-5">
        <p class="text-xs text-ink-soft mb-1">Ditolak</p>
        <p class="font-display text-2xl text-[#C1483A] mono-data">{{ $totalDitolak }}</p>
    </div>
</div>

{{-- 2. Per bulan & 3. Berdasarkan waktu --}}
<div class="grid lg:grid-cols-2 gap-4 mb-6">
    <div class="card p-6">
        <h3 class="font-semibold text-ink mb-4">Dispensasi per Bulan — {{ $tahun }}</h3>
        <canvas id="chartBulan" height="220"></canvas>
    </div>
    <div class="card p-6">
        <h3 class="font-semibold text-ink mb-4">Dispensasi Berdasarkan Waktu</h3>
        <canvas id="chartWaktu" height="220"></canvas>
    </div>
</div>

{{-- 4. Per departemen --}}
<div class="card p-6 mb-6">
    <h3 class="font-semibold text-ink mb-4">Dispensasi Berdasarkan Departemen</h3>
    @if ($perDepartemen->isEmpty())
    <p class="text-sm text-ink-soft py-8 text-center">Belum ada data.</p>
    @else
    <canvas id="chartDepartemen" height="220"></canvas>
    @endif
</div>

{{-- 5. Tabel dispensasi terbaru --}}
<div class="card overflow-hidden mb-6">
    <div class="px-6 py-4 border-b border-line">
        <h3 class="font-semibold text-ink">Dispensasi Terbaru</h3>
    </div>
    <table class="table-pro">
        <thead>
            <tr><th>Nomor</th><th>Pegawai</th><th>Departemen</th><th>Tanggal</th><th>Diajukan</th><th>Status</th></tr>
        </thead>
        <tbody>
            @forelse ($terbaru as $d)
            @php
                $statusLabel = match ($d->status_pengajuan) {
                    'menunggu_persetujuan' => 'Menunggu',
                    'disetujui' => 'Disetujui',
                    'ditolak' => 'Ditolak',
                    default => ucfirst($d->status_pengajuan),
                };
                $statusClass = match ($d->status_pengajuan) {
                    'menunggu_persetujuan' => 'badge-menunggu',
                    'disetujui' => 'badge-disetujui',
                    'ditolak' => 'badge-ditolak',
                    default => 'badge-default',
                };
            @endphp
            <tr>
                <td class="mono-data text-ink-soft">{{ $d->nomor_dispensasi }}</td>
                <td class="font-medium">{{ $d->pegawai->nama_pegawai }}</td>
                <td class="text-ink-soft">{{ $d->departemen?->nama_departemen ?? '-' }}</td>
                <td>{{ $d->tanggal_dispensasi->format('d M Y') }}</td>
                <td class="text-ink-soft text-xs">{{ $d->created_at->diffForHumans() }}</td>
                <td><span class="badge {{ $statusClass }}">{{ $statusLabel }}</span></td>
            </tr>
            @empty
            <tr><td colspan="6" class="text-center text-ink-soft py-10">Tidak ada data sesuai filter.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
    const primary = '#163A5C';
    const accent = '#2BAFC7';
    const line = '#E2E8EC';
    const palet = ['#2BAFC7', '#163A5C', '#A9BE2E', '#C8862B', '#C1483A', '#8A6FBF', '#5C6B78'];

    Chart.defaults.font.family = "'Plus Jakarta Sans', sans-serif";
    Chart.defaults.color = '#5C6B78';

    new Chart(document.getElementById('chartBulan'), {
        type: 'bar',
        data: {
            labels: @json($perBulan->pluck('label')),
            datasets: [{
                label: 'Jumlah Pengajuan',
                data: @json($perBulan->pluck('total')),
                backgroundColor: accent,
                borderRadius: 6,
                barThickness: 22,
            }]
        },
        options: {
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: line } },
                x: { grid: { display: false } },
            }
        }
    });

    new Chart(document.getElementById('chartWaktu'), {
        type: 'doughnut',
        data: {
            labels: @json($perWaktu->pluck('label')),
            datasets: [{
                data: @json($perWaktu->pluck('total')),
                backgroundColor: palet,
            }]
        },
        options: {
            plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, padding: 12 } } },
        }
    });

    @if ($perDepartemen->isNotEmpty())
    new Chart(document.getElementById('chartDepartemen'), {
        type: 'bar',
        data: {
            labels: @json($perDepartemen->pluck('nama')),
            datasets: [{
                label: 'Jumlah Pengajuan',
                data: @json($perDepartemen->pluck('total')),
                backgroundColor: primary,
                borderRadius: 6,
                barThickness: 18,
            }]
        },
        options: {
            indexAxis: 'y',
            plugins: { legend: { display: false } },
            scales: {
                x: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: line } },
                y: { grid: { display: false } },
            }
        }
    });
    @endif
</script>
@endsection