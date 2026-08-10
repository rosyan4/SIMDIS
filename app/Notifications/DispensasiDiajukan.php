<?php

namespace App\Notifications;

use App\Models\Dispensasi;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DispensasiDiajukan extends Notification
{
    use Queueable;

    public function __construct(public Dispensasi $dispensasi) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'dispensasi_id' => $this->dispensasi->id,
            'nomor_dispensasi' => $this->dispensasi->nomor_dispensasi,
            'nama_pegawai' => $this->dispensasi->pegawai->name,
            'pesan' => "Pengajuan dispensasi baru dari {$this->dispensasi->pegawai->name} menunggu persetujuan Anda.",
            'url' => '/manajer/dashboard',
        ];
    }
}