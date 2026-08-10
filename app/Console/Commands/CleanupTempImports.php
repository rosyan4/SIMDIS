<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanupTempImports extends Command
{
    protected $signature = 'imports:cleanup';
    protected $description = 'Hapus file temporary import yang lebih dari 24 jam';

    public function handle(): void
    {
        $files = Storage::disk('local')->files('temp-imports');
        $dihapus = 0;

        foreach ($files as $file) {
            if (now()->diffInHours(Storage::disk('local')->lastModified($file)) > 24) {
                Storage::disk('local')->delete($file);
                $dihapus++;
            }
        }

        $this->info("{$dihapus} file temp import dihapus.");
    }
}