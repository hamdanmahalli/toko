<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            // Window yang dipakai saat absen masuk. Disimpan terpisah dari
            // shift_label_masuk yang berupa teks, karena absen pulang butuh
            // tahu window yang sama tanpa menebak ulang dari jam pulang.
            // nullOnDelete: window yang dihapus tidak boleh merusak riwayat.
            $table->foreignId('shift_window_id')
                ->nullable()
                ->after('shift_label_masuk')
                ->constrained('shift_windows')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropConstrainedForeignId('shift_window_id');
        });
    }
};
