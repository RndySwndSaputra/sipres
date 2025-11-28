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
        Schema::create('presensi', function (Blueprint $table) {
            // Saran: Gunakan auto-increment standar agar lebih mudah
            $table->id('id_presensi'); 
            
            // PERBAIKAN 1: Ganti ke foreignUuid agar cocok dengan tabel Acara
            $table->foreignUuid('id_acara')
                  ->constrained('acara', 'id_acara')
                  ->cascadeOnDelete();

            // Relasi ke Peserta (NIP tetap string)
            $table->string('nip', 32);
            // $table->foreign('nip')
            //       ->references('nip')
            //       ->on('peserta')
            //       ->cascadeOnDelete();

            // PERBAIKAN 2: Tambahkan 'Online' ke enum
            $table->string('mode_presensi')->default('Offline');
            
            $table->enum('status_kehadiran', ['Hadir', '?'])->default('?');
            $table->dateTime('waktu_presensi')->nullable();
            
            // PERBAIKAN 3: Perbaiki typo 'patch' jadi 'path' (lokasi file)
            $table->string('signature_path')->nullable(); 
            
            $table->timestamps();

            // Mencegah duplikasi presensi (satu NIP hanya bisa absen sekali per acara)
            $table->unique(['id_acara', 'nip']);
        });
    }
    
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('presensi');
    }
};