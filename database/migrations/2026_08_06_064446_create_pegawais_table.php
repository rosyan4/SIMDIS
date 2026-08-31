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
            $table->string('nik', 20)->unique(); // unique() sudah membuat index, tidak perlu index('nik') lagi
            $table->string('nama_pegawai', 100);
            $table->enum('jenis_pegawai', ['pegawai', 'pekerja_lapangan']);
            $table->string('jabatan', 100);

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
            $table->index('nama_pegawai');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pegawais');
    }
};