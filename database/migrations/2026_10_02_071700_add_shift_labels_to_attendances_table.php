<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            // Nama window shift yang cocok dengan jam absen, misalnya "Pagi".
            // Disimpan sebagai teks, bukan foreign key, supaya label lama tetap utuh
            // walaupun window-nya nanti diubah atau dinonaktifkan.
            // Murni informatif: TIDAK dipakai menghitung status telat/pulang.
            $table->string('shift_label_masuk')->nullable()->after('jam_masuk');
            $table->string('shift_label_pulang')->nullable()->after('jam_pulang');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn(['shift_label_masuk', 'shift_label_pulang']);
        });
    }
};
