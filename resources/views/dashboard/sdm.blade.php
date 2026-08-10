@extends('layouts.app')
@section('title', 'Dashboard Admin SDM')

@section('content')
<div class="mb-8">
    <p class="text-xs font-semibold tracking-widest text-accent uppercase mb-1">Dashboard</p>
    <h1 class="font-display text-3xl text-ink">Admin SDM</h1>
</div>

{{-- Navigasi cepat --}}
<div class="grid sm:grid-cols-3 gap-4 mb-10">
    <a href="{{ route('sdm.pegawai.index') }}" class="card p-5 hover:border-accent transition-colors flex items-center gap-3">
        <div class="w-9 h-9 rounded-lg bg-accent-soft flex items-center justify-center shrink-0">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#0E4F56" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8z"/></svg>
        </div>
        <span class="font-medium text-ink text-sm">Kelola Pegawai</span>
    </a>
    <a href="{{ route('sdm.departemen.index') }}" class="card p-5 hover:border-accent transition-colors flex items-center gap-3">
        <div class="w-9 h-9 rounded-lg bg-accent-soft flex items-center justify-center shrink-0">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#0E4F56" stroke-width="2"><path d="M3 21h18M5 21V7l8-4 8 4v14"/></svg>
        </div>
        <span class="font-medium text-ink text-sm">Struktur Departemen</span>
    </a>
    <a href="{{ route('sdm.monitoring.index') }}" class="card p-5 hover:border-accent transition-colors flex items-center gap-3">
        <div class="w-9 h-9 rounded-lg bg-accent-soft flex items-center justify-center shrink-0">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#0E4F56" stroke-width="2"><path d="M3 3v18h18M7 15l4-6 3 4 5-8"/></svg>
        </div>
        <span class="font-medium text-ink text-sm">Monitoring Dispensasi</span>
    </a>
</div>

{{-- Ringkasan statistik --}}
<div class="mb-3">
    <h2 class="font-display text-xl text-ink">Ringkasan Dispensasi</h2>
</div>
<div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
    <div class="card p-5">
        <p class="text-xs text-ink-soft mb-1">Total Pengajuan</p>
        <p class="font-display text-2xl text-ink mono-data">{{ $totalSemua }}</p>
    </div>
    <div class="card p-5">
        <p class="text-xs text-ink-soft mb-1">Menunggu</p>
        <p class="font-display text-2xl text-[#C8862B] mono-data">{{ $totalDiajukan }}</p>
    </div>
    <div class="card p-5">
        <p class="text-xs text-ink-soft mb-1">Disetujui</p>
        <p class="font-display text-2xl text-[#2E9E6B] mono-data">{{ $totalDisetujui }}</p>
    </div>
    <div class="card p-5">
        <p class="text-xs text-ink-soft mb-1">Ditolak</p>
        <p class="font-display text-2xl text-[#C1483A] mono-data">{{ $totalDitolak }}</p>
    </div>
    <div class="card p-5">
        <p class="text-xs text-ink-soft mb-1">Tingkat Persetujuan</p>
        <p class="font-display text-2xl text-primary mono-data">{{ $tingkatPersetujuan !== null ? $tingkatPersetujuan . '%' : '-' }}</p>
    </div>
</div>

<div class="grid sm:grid-cols-2 gap-4 mb-10">
    <div class="card p-5 flex justify-between items-center">
        <div>
            <p class="text-xs text-ink-soft mb-1">Rata-rata Waktu Persetujuan</p>
            <p class="text-ink font-medium">{{ $rataRataJamPersetujuan !== null ? $rataRataJamPersetujuan . ' jam' : 'Belum ada data' }}</p>
        </div>
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#17B8A6" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
    </div>
    <div class="card p-5 flex justify-between items-center">
        <div>
            <p class="text-xs text-ink-soft mb-1">Total Dieskalasi ke Asisten Manajer</p>
            <p class="text-ink font-medium">{{ $totalEskalasi }} pengajuan</p>
        </div>
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#17B8A6" stroke-width="1.5"><path d="M12 19V5M5 12l7-7 7 7"/></svg>
    </div>
</div>

{{-- Grafik: per departemen & tren bulanan --}}
<div class="grid lg:grid-cols-2 gap-4 mb-10">
    <div class="card p-6">
        <h3 class="font-semibold text-ink mb-4">Dispensasi per Departemen</h3>
        @if ($perDepartemen->isEmpty())
        <p class="text-sm text-ink-soft py-8 text-center">Belum ada data.</p>
        @else
        <canvas id="chartDepartemen" height="220"></canvas>
        @endif
    </div>

    <div class="card p-6">
        <h3 class="font-semibold text-ink mb-4">Tren 6 Bulan Terakhir</h3>
        <canvas id="chartTren" height="220"></canvas>
    </div>
</div>

{{-- Grafik: pola hari --}}
<div class="card p-6 mb-10">
    <h3 class="font-semibold text-ink mb-4">Pola Hari Pengajuan Dispensasi</h3>
    <canvas id="chartHari" height="140"></canvas>
</div>

{{-- Tabel: tanggal & pegawai terbanyak --}}
<div class="grid lg:grid-cols-2 gap-4 mb-6">
    <div class="card overflow-hidden">
        <div class="px-6 py-4 border-b border-line">
            <h3 class="font-semibold text-ink">Tanggal Terbanyak Dispensasi</h3>
        </div>
        <table class="table-pro">
            <thead><tr><th>Tanggal</th><th>Jumlah</th></tr></thead>
            <tbody>
                @forelse ($tanggalTerbanyak as $t)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($t['tanggal'])->translatedFormat('d M Y') }}</td>
                    <td class="mono-data font-medium">{{ $t['total'] }}</td>
                </tr>
                @empty
                <tr><td colspan="2" class="text-center text-ink-soft py-8">Belum ada data.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="card overflow-hidden">
        <div class="px-6 py-4 border-b border-line">
            <h3 class="font-semibold text-ink">Pegawai Terbanyak Mengajukan</h3>
        </div>
        <table class="table-pro">
            <thead><tr><th>Nama</th><th>Departemen</th><th>Jumlah</th></tr></thead>
            <tbody>
                @forelse ($pegawaiTerbanyak as $p)
                <tr>
                    <td class="font-medium">{{ $p['nama'] }}</td>
                    <td class="text-ink-soft">{{ $p['departemen'] }}</td>
                    <td class="mono-data font-medium">{{ $p['total'] }}</td>
                </tr>
                @empty
                <tr><td colspan="3" class="text-center text-ink-soft py-8">Belum ada data.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
    const primary = '#0E4F56';
    const accent = '#17B8A6';
    const accentSoft = '#D9F3EF';
    const ink = '#16262A';
    const line = '#E2E8E7';

    Chart.defaults.font.family = "'Plus Jakarta Sans', sans-serif";
    Chart.defaults.color = '#5B6D70';

    @if ($perDepartemen->isNotEmpty())
    new Chart(document.getElementById('chartDepartemen'), {
        type: 'bar',
        data: {
            labels: @json($perDepartemen->pluck('nama')),
            datasets: [{
                label: 'Jumlah Pengajuan',
                data: @json($perDepartemen->pluck('total')),
                backgroundColor: accent,
                borderRadius: 6,
                barThickness: 28,
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
    @endif

    new Chart(document.getElementById('chartTren'), {
        type: 'line',
        data: {
            labels: @json($trenBulanan->pluck('label')),
            datasets: [{
                label: 'Pengajuan',
                data: @json($trenBulanan->pluck('total')),
                borderColor: primary,
                backgroundColor: accentSoft,
                fill: true,
                tension: 0.35,
                pointBackgroundColor: primary,
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

    new Chart(document.getElementById('chartHari'), {
        type: 'bar',
        data: {
            labels: @json($polaHari->pluck('label')),
            datasets: [{
                label: 'Jumlah Pengajuan',
                data: @json($polaHari->pluck('total')),
                backgroundColor: primary,
                borderRadius: 6,
                barThickness: 36,
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
</script>
@endsection