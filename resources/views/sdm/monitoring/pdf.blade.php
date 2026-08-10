<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: sans-serif; font-size: 11px; }
        h1 { font-size: 16px; margin-bottom: 4px; }
        p.meta { color: #666; margin-top: 0; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
        th { background: #f3f4f6; }
    </style>
</head>
<body>
    <h1>Laporan Dispensasi Disetujui</h1>
    <p class="meta">
        Perumda Air Minum Tirta Mayang
        @if ($departemen) — Departemen: {{ $departemen->nama_departemen }} @endif
        @if (!empty($filters['dari_tanggal']) || !empty($filters['sampai_tanggal']))
            — Periode: {{ $filters['dari_tanggal'] ?? '...' }} s/d {{ $filters['sampai_tanggal'] ?? '...' }}
        @endif
        <br>Dicetak pada: {{ now()->format('d M Y H:i') }}
    </p>

    <table>
        <thead>
            <tr>
                <th>Nomor</th><th>Pegawai</th><th>Departemen</th><th>Subdepartemen</th>
                <th>Tanggal</th><th>Keterangan</th><th>Disetujui Oleh</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($dispensasis as $d)
            <tr>
                <td>{{ $d->nomor_dispensasi }}</td>
                <td>{{ $d->pegawai->name }}</td>
                <td>{{ $d->pegawai->subdepartemen?->departemen?->nama_departemen ?? '-' }}</td>
                <td>{{ $d->pegawai->subdepartemen?->nama_subdepartemen ?? '-' }}</td>
                <td>{{ $d->tanggal_dispensasi->format('d-m-Y') }}</td>
                <td>{{ $d->alasan }}</td>
                <td>{{ $d->approver?->name ?? '-' }}</td>
            </tr>
            @empty
            <tr><td colspan="7" style="text-align:center;">Tidak ada data.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>