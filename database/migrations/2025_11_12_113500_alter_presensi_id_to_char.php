<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('presensi')) {
            DB::statement("ALTER TABLE `presensi` MODIFY `id_presensi` CHAR(10) NOT NULL");
            DB::statement("ALTER TABLE `presensi` DROP PRIMARY KEY, ADD PRIMARY KEY (`id_presensi`)");
        }
    }

    public function down(): void
    {
    }
};
