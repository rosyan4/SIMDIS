<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispensasis', function (Blueprint $table) {
            $table->id();

            // Nomor dispensasi otomatis. unique() sudah membuat index,
            // tidak perlu index('nomor_dispensasi') lagi.
            $table->string('nomor_dispensasi', 50)->unique();

            // Pegawai yang mengajukan
            $table->foreignId('pegawai_id')->constrained('pegawais')->restrictOnDelete();

            // Struktur organisasi pegawai (denormalisasi untuk performa)
            $table->foreignId('departemen_id')->constrained('departemens')->restrictOnDelete();
            $table->foreignId('subdepartemen_id')->nullable()->constrained('subdepartemens')->nullOnDelete();

            // Admin Departemen yang menginput pengajuan
            $table->foreignId('admin_departemen_id')
                  ->comment('User dengan role admin_departemen yang menginput')
                  ->constrained('users')->restrictOnDelete();

            // Data dispensasi
            $table->date('tanggal_pengajuan');
            $table->date('tanggal_dispensasi');
            $table->enum('waktu_dispensasi', ['pagi', 'istirahat', 'siang', 'sore']);
            $table->text('keterangan');
            $table->string('bukti_pendukung', 255)->nullable();

            // Status pengajuan
            $table->enum('status_pengajuan', [
                'menunggu_persetujuan',
                'disetujui',
                'ditolak',
            ])->default('menunggu_persetujuan');

            // Pihak yang memproses (Manajer atau Asisten Manajer)
            $table->foreignId('diproses_oleh_id')
                  ->nullable()
                  ->comment('User (manajer_departemen atau asisten_manajer) yang memberi keputusan')
                  ->constrained('users')->nullOnDelete();

            // Data persetujuan
            $table->text('catatan_persetujuan')->nullable();
            $table->timestamp('tanggal_keputusan')->nullable();

            $table->timestamps();

            // Index untuk performa monitoring
            $table->index(['departemen_id', 'status_pengajuan']);
            $table->index(['subdepartemen_id', 'status_pengajuan']);
            $table->index(['pegawai_id', 'status_pengajuan']);
            $table->index('tanggal_dispensasi');
            $table->index('tanggal_pengajuan');
            $table->index('status_pengajuan');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispensasis');
    }
};