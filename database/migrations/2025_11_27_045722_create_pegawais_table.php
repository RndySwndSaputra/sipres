<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pegawai', function (Blueprint $table) {
            $table->id();
            $table->string('nip')->unique(); // Kunci utama pencarian
            $table->string('nama');
            $table->string('lokasi_unit_kerja')->nullable();
            $table->string('skpd')->nullable();
            $table->string('email')->nullable();
            $table->string('ponsel')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pegawai');
    }
};