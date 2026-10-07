<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shift_windows', function (Blueprint $table) {
            // Batas telat shift ini. Setelah jam ini, karyawan yang absen pada
            // window ini berstatus terlambat. Null berarti toleransi nol, yaitu
            // harus tepat pada jam mulai.
            $table->time('batas_telat')->nullable()->after('mulai');
        });
    }

    public function down(): void
    {
        Schema::table('shift_windows', function (Blueprint $table) {
            $table->dropColumn('batas_telat');
        });
    }
};
