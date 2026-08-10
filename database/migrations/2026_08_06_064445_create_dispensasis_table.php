<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('dispensasis', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_dispensasi')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('tanggal_dispensasi');
            $table->time('jam_mulai')->nullable();
            $table->time('jam_selesai')->nullable();
            $table->text('alasan');
            $table->string('bukti_pendukung')->nullable();
            $table->enum('status', ['diajukan', 'disetujui', 'ditolak'])
                ->default('diajukan');
            $table->foreignId('approved_by')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('catatan_persetujuan')->nullable();
            $table->timestamp('escalated_at')->nullable(); // <-- baru
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dispensasis');
    }
};
