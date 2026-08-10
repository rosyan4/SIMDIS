<?php

namespace App\Console\Commands;

use App\Models\Dispensasi;
use App\Notifications\DispensasiDiajukan;
use Illuminate\Console\Command;

class EscalateDispensasi extends Command
{
    protected $signature = 'dispensasi:escalate';
    protected $description = 'Eskalasi pengajuan dispensasi ke Asisten Manajer jika belum diproses > 24 jam';

    public function handle(): void
    {
        $dispensasis = Dispensasi::where('status', 'diajukan')
            ->whereNull('escalated_at')
            ->where('created_at', '<=', now()->subHours(24))
            ->with('pegawai.subdepartemen.asistenManajer')
            ->get();

        foreach ($dispensasis as $dispensasi) {
            $dispensasi->update(['escalated_at' => now()]);

            $asmen = $dispensasi->pegawai->subdepartemen?->asistenManajer;
            if ($asmen) {
                $asmen->notify(new DispensasiDiajukan($dispensasi));
            }
        }

        $this->info("{$dispensasis->count()} pengajuan dieskalasi ke Asisten Manajer.");
    }
}