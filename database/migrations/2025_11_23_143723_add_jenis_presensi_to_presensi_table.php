<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('presensi', function (Blueprint $table) {
            // 1. Tambahkan kolom jenis_presensi
            $table->string('jenis_presensi')->default('masuk')->after('nip'); 

            // 2. [PENTING] Hapus Foreign Key dulu agar Index Unik bisa dilepas
            // Kita perlu menghapus constraint foreign key pada id_acara
            $table->dropForeign(['id_acara']); 

            // 3. Sekarang baru aman untuk menghapus aturan Unik (1 orang 1 kali)
            $table->dropUnique(['id_acara', 'nip']); 

            // 4. Pasang kembali Foreign Key (Relasi) agar data tetap aman
            // Kali ini dia akan menggunakan index biasa (bukan index unik)
            $table->foreign('id_acara')
                  ->references('id_acara')
                  ->on('acara')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('presensi', function (Blueprint $table) {
            // Kembalikan seperti semula (agak tricky karena data mungkin sudah duplikat)
            // Jadi kita drop kolomnya saja dulu
            $table->dropColumn('jenis_presensi');
            
            // Kita tidak bisa mengembalikan unique constraint dengan mudah jika
            // di dalam database sudah terlanjur ada data absen ganda (masuk & pulang).
            // $table->unique(['id_acara', 'nip']); 
        });
    }
};