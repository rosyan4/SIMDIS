@extends('layouts.app')
@section('title', 'Monitoring Dispensasi')

@section('content')
<div class="mb-8">
    <p class="text-xs font-semibold tracking-widest text-accent uppercase mb-1">Admin SDM</p>
    <h1 class="font-display text-3xl text-ink mb-2">Monitoring Dispensasi</h1>
    <p class="text-ink-soft text-sm">Menampilkan pengajuan yang telah disetujui Manajer/Asisten Manajer.</p>
</div>

<form method="GET" class="card p-4 mb-6 flex gap-3 flex-wrap items-end">
    <div>
        <label class="text-xs text-ink-soft mb-1 block">Departemen</label>
        <select name="departemen_id" class="field-input" style="width:auto">
            <option value="">Semua</option>
            @foreach ($departemens as $d)
            <option value="{{ $d->id }}" @selected(request('departemen_id') == $d->id)>{{ $d->nama_departemen }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="text-xs text-ink-soft mb-1 block">Dari Tanggal</label>
        <input type="date" name="dari_tanggal" value="{{ request('dari_tanggal') }}" class="field-input" style="width:auto">
    </div>
    <div>
        <label class="text-xs text-ink-soft mb-1 block">Sampai Tanggal</label>
        <input type="date" name="sampai_tanggal" value="{{ request('sampai_tanggal') }}" class="field-input" style="width:auto">
    </div>

    <button class="btn btn-outline">Filter</button>
    <a href="{{ route('sdm.monitoring.export.excel', request()->query()) }}" class="btn" style="background:#E1F3EA;color:#2E9E6B;">Export Excel</a>
    <a href="{{ route('sdm.monitoring.export.pdf', request()->query()) }}" class="btn" style="background:#FBE7E4;color:#C1483A;">Export PDF</a>
</form>

<div class="space-y-3" x-data="{ openDept: null, openSub: null }">
    @forelse ($terkelompok as $namaDept => $subGrup)
    @php $deptKey = \Str::slug($namaDept); @endphp
    <div class="card overflow-hidden">
        <button @click="openDept = openDept === '{{ $deptKey }}' ? null : '{{ $deptKey }}'"
                class="w-full flex justify-between items-center px-6 py-4 hover:bg-canvas">
            <span class="font-semibold text-ink">{{ $namaDept }}</span>
            <span class="text-xs text-ink-soft">
                {{ $subGrup->flatten(1)->count() }} disetujui
                <span x-text="openDept === '{{ $deptKey }}' ? '▲' : '▼'" class="ml-2"></span>
            </span>
        </button>

        <div x-show="openDept === '{{ $deptKey }}'" x-cloak class="border-t border-line">
            @foreach ($subGrup as $namaSub => $daftarDispensasi)
            @php $subKey = $deptKey . '-' . \Str::slug($namaSub); @endphp
            <div class="border-b border-line last:border-b-0">
                <button @click="openSub = openSub === '{{ $subKey }}' ? null : '{{ $subKey }}'"
                        class="w-full flex justify-between items-center px-7 py-3 hover:bg-canvas text-sm">
                    <span class="font-medium text-ink">{{ $namaSub }}</span>
                    <span class="text-xs text-ink-soft">
                        {{ $daftarDispensasi->count() }} disetujui
                        <span x-text="openSub === '{{ $subKey }}' ? '▲' : '▼'" class="ml-2"></span>
                    </span>
                </button>

                <div x-show="openSub === '{{ $subKey }}'" x-cloak class="overflow-x-auto">
                    <table class="table-pro">
                        <thead>
                            <tr><th>Nomor</th><th>Pegawai</th><th>Tanggal</th><th>Waktu</th><th>Keterangan</th><th>Bukti</th><th>Disetujui Oleh</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($daftarDispensasi as $d)
                            <tr>
                                <td class="mono-data text-ink-soft">{{ $d->nomor_dispensasi }}</td>
                                <td class="font-medium">{{ $d->pegawai->name }}</td>
                                <td>{{ $d->tanggal_dispensasi->format('d M Y') }}</td>
                                <td class="mono-data text-ink-soft">{{ $d->jam_mulai ?? '-' }} @if($d->jam_selesai) - {{ $d->jam_selesai }} @endif</td>
                                <td>{{ Str::limit($d->alasan, 50) }}</td>
                                <td>
                                    @if ($d->bukti_pendukung)
                                    <a href="{{ Storage::url($d->bukti_pendukung) }}" target="_blank" class="text-primary font-medium hover:underline">Lihat</a>
                                    @else <span class="text-ink-soft">-</span> @endif
                                </td>
                                <td class="text-ink-soft">{{ $d->approver?->name ?? '-' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @empty
    <div class="card p-10 text-center text-ink-soft">Belum ada dispensasi yang disetujui sesuai filter.</div>
    @endforelse
</div>
@endsection