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
        Schema::create('subdepartemens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('departemen_id')->constrained()->cascadeOnDelete();
            $table->string('nama_subdepartemen');
            $table->string('kode', 20)->nullable();
            $table->foreignId('asisten_manajer_id')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subdepartemens');
    }
};
