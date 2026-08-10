<?php

namespace App\Notifications;

use App\Models\Dispensasi;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DispensasiDiputuskan extends Notification
{
    use Queueable;

    public function __construct(public Dispensasi $dispensasi) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        $status = $this->dispensasi->status === 'disetujui' ? 'disetujui' : 'ditolak';

        return [
            'dispensasi_id' => $this->dispensasi->id,
            'nomor_dispensasi' => $this->dispensasi->nomor_dispensasi,
            'pesan' => "Pengajuan dispensasi {$this->dispensasi->nomor_dispensasi} Anda telah {$status}.",
            'url' => '/dispensasi/' . $this->dispensasi->id,
        ];
    }
}