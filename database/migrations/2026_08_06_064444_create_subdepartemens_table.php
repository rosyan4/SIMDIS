<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subdepartemens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('departemen_id')->constrained('departemens')->cascadeOnDelete();
            $table->string('kode_subdepartemen', 20)->unique();
            $table->string('nama_subdepartemen', 100);
            $table->timestamps();

            $table->index('departemen_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subdepartemens');
    }
};