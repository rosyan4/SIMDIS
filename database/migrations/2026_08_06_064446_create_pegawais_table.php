<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pegawais', function (Blueprint $table) {
            $table->id();
            // Data identitas
            $table->string('nik', 20)->unique(); 
            $table->string('nama_pegawai', 100);
            $table->string('jabatan', 100);
            $table->enum('posisi', [
                'staf',
                'asisten_manajer_bidang',
                'manajer',
                'senior_manajer_sekper',
                'senior_manajer_bisnis',
                'senior_manajer_keuangan_pelanggan',
                'senior_manajer_produksi_distribusi',
                'senior_manajer_perencanaan_aset',
                'kepala_spi',
                'sekretaris_spi',
            ])->default('staf');

            // Struktur organisasi
            $table->foreignId('departemen_id')->constrained('departemens')->restrictOnDelete();
            $table->foreignId('subdepartemen_id')->nullable()->constrained('subdepartemens')->nullOnDelete();

            // Kontak (opsional)
            $table->string('no_telepon', 20)->nullable();
            $table->string('email', 100)->nullable();

            // Status pegawai
            $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');
            $table->timestamps();

            // Index untuk pencarian & filter
            $table->index(['departemen_id', 'status']);
            $table->index(['subdepartemen_id', 'status']);
            $table->index(['departemen_id', 'posisi']);
            $table->index('nama_pegawai');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pegawais');
    }
};