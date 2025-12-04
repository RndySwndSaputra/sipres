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
        Schema::create('riwayat_perubahan_peserta', function (Blueprint $table) {
            $table->id();

            // --- BAGIAN INI YANG DIUBAH ---
            // Dari: $table->unsignedBigInteger('id_acara')->index(); 
            // Menjadi:
            $table->string('id_acara')->index(); 
            // ------------------------------

            $table->unsignedBigInteger('id_peserta');
            $table->string('nip');
            $table->string('nama_lama');
            $table->string('nama_baru');
            $table->timestamp('diubah_pada')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('riwayat_perubahan_peserta');
    }
};