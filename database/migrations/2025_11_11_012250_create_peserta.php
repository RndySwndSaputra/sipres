<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('peserta', function (Blueprint $table) {
            // PERBAIKAN: Gunakan ID auto increment sebagai Primary Key
            $table->id(); 
            
            // NIP bukan lagi primary key, hanya string biasa
            $table->string('nip'); 
            
            $table->foreignUuid('id_acara')
                  ->constrained('acara', 'id_acara')
                  ->cascadeOnDelete();

            $table->string('nama');
            $table->string('lokasi_unit_kerja');
            $table->string('skpd');
            $table->timestamps();

            // PERBAIKAN UTAMA:
            // Kombinasi id_acara dan nip harus unik. 
            // Artinya: 1 NIP tidak bisa daftar 2x di acara SAMA, 
            // tapi BISA daftar di acara BEDA.
            $table->unique(['id_acara', 'nip']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('peserta');
    }
};