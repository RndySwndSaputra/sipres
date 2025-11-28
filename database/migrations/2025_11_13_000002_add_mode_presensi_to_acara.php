<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('acara') && ! Schema::hasColumn('acara', 'mode_presensi')) {
            Schema::table('acara', function (Blueprint $table) {
                $table->string('mode_presensi')->default('Offline')->after('materi');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('acara') && Schema::hasColumn('acara', 'mode_presensi')) {
            Schema::table('acara', function (Blueprint $table) {
                $table->dropColumn('mode_presensi');
            });
        }
    }
};