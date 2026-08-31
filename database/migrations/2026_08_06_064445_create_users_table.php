<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            // Identitas
            $table->string('name', 100);
            $table->string('username', 50)->unique(); // Bisa NIK atau email
            $table->string('email', 100)->nullable()->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();

            // Role pengguna (4 role sesuai dokumen)
            $table->enum('role', [
                'admin_sdm',
                'admin_departemen',
                'manajer_departemen',
                'asisten_manajer',
            ]);

            // Relasi ke struktur organisasi.
            // restrictOnDelete (bukan nullOnDelete): departemens/subdepartemens adalah
            // data master yang seharusnya tidak pernah dihapus setelah seeding. Kalau
            // masih ada user terikat, penghapusan departemen/subdepartemen ditolak di
            // level DB, supaya tidak ada user yang diam-diam kehilangan departemen_id-nya.
            $table->foreignId('departemen_id')->nullable()->constrained('departemens')->restrictOnDelete();
            $table->foreignId('subdepartemen_id')->nullable()->constrained('subdepartemens')->restrictOnDelete();

            // Status Manajer Departemen (hanya relevan untuk role manajer_departemen)
            $table->enum('status_manajer', ['aktif', 'berhalangan'])->default('aktif');
            $table->date('tanggal_mulai_berhalangan')->nullable();
            $table->date('tanggal_selesai_berhalangan')->nullable();
            $table->string('alasan_berhalangan', 200)->nullable();
            $table->text('keterangan_tambahan')->nullable();

            // Status akun
            $table->boolean('is_active')->default(true);
            // Dipakai untuk memaksa ganti password saat login pertama kali
            // (akun baru dari seeder/import selalu punya password default).
            $table->boolean('must_change_password')->default(false);

            $table->timestamps();

            // Index untuk performa query
            $table->index(['role', 'is_active']);
            $table->index(['departemen_id', 'role']);
            $table->index(['subdepartemen_id', 'role']);
            $table->index('status_manajer');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};