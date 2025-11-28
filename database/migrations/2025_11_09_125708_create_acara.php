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
        Schema::create('acara', function (Blueprint $table) {
            $table->uuid('id_acara')->primary(); 
            
            $table->string('nama_acara');
            $table->text('materi')->nullable();
            $table->string('lokasi')->nullable();
            $table->text('link_meeting')->nullable();
            
            // Kolom ini bisa kita biarkan untuk backward compatibility atau logika internal
            // Namun karena logika utama sekarang pindah ke 'mode_presensi', ini opsional.
            // Saya set default 'offline' agar aman.
            $table->string('jenis_acara')->default('offline'); 
            
            $table->string('status_keberlangsungan')->default('upcoming');
            
            $table->dateTime('waktu_mulai');
            $table->dateTime('waktu_selesai');
            $table->dateTime('waktu_istirahat_mulai')->nullable();
            $table->dateTime('waktu_istirahat_selesai')->nullable();
            
            $table->unsignedInteger('maximal_peserta')->default(0);
            $table->integer('toleransi_menit')->default(15);
            
            // --- PERUBAHAN UTAMA DISINI ---
            
            // 1. mode_presensi: Menyimpan 'Offline', 'Online', atau 'Kombinasi'
            $table->string('mode_presensi')->default('Offline'); 

            // 2. tipe_presensi: Menyimpan 'Tradisional' (TTD) atau 'Cepat' (Scan only)
            // Default 'Tradisional' agar aman
            $table->string('tipe_presensi')->default('Tradisional')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('acara');
    }
};