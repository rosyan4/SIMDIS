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
            $table->string('name', 100);
            $table->string('email', 100)->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->enum('role', [
                'admin_sdm',
                'admin_departemen',
                'manajer_departemen',
                'senior_manajer_sekper',
                'kepala_spi',
                'direktur_teknik',
                'direktur_administrasi_keuangan',
                'direktur_utama',
            ]);
            $table->foreignId('departemen_id')->nullable()->constrained('departemens')->restrictOnDelete();
            $table->foreignId('subdepartemen_id')->nullable()->constrained('subdepartemens')->restrictOnDelete();

            // Status akun
            $table->boolean('is_active')->default(true);
            $table->boolean('must_change_password')->default(false);
            $table->timestamps();

            // Index untuk performa query
            $table->index(['role', 'is_active']);
            $table->index(['departemen_id', 'role']);
            $table->index(['subdepartemen_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};