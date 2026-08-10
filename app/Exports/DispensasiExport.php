<?php

namespace App\Exports;

use App\Models\Dispensasi;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class DispensasiExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(protected array $filters) {}

    public function collection()
    {
        return $this->filteredQuery()->latest('approved_at')->get();
    }

    protected function filteredQuery(): Builder
    {
        $query = Dispensasi::where('status', 'disetujui')
            ->with(['pegawai.subdepartemen.departemen', 'approver']);

        if (! empty($this->filters['departemen_id'])) {
            $query->whereHas('pegawai.subdepartemen', fn ($q) =>
                $q->where('departemen_id', $this->filters['departemen_id'])
            );
        }

        if (! empty($this->filters['dari_tanggal'])) {
            $query->whereDate('tanggal_dispensasi', '>=', $this->filters['dari_tanggal']);
        }

        if (! empty($this->filters['sampai_tanggal'])) {
            $query->whereDate('tanggal_dispensasi', '<=', $this->filters['sampai_tanggal']);
        }

        return $query;
    }

    public function headings(): array
    {
        return [
            'Nomor Dispensasi', 'Nama Pegawai', 'Departemen', 'Subdepartemen',
            'Tanggal Dispensasi', 'Jam Mulai', 'Jam Selesai', 'Alasan',
            'Disetujui Oleh', 'Tanggal Disetujui', 'Catatan',
        ];
    }

    public function map($dispensasi): array
    {
        return [
            $dispensasi->nomor_dispensasi,
            $dispensasi->pegawai->name,
            $dispensasi->pegawai->subdepartemen?->departemen?->nama_departemen ?? '-',
            $dispensasi->pegawai->subdepartemen?->nama_subdepartemen ?? '-',
            $dispensasi->tanggal_dispensasi->format('d-m-Y'),
            $dispensasi->jam_mulai ?? '-',
            $dispensasi->jam_selesai ?? '-',
            $dispensasi->alasan,
            $dispensasi->approver?->name ?? '-',
            $dispensasi->approved_at?->format('d-m-Y H:i') ?? '-',
            $dispensasi->catatan_persetujuan ?? '-',
        ];
    }
}