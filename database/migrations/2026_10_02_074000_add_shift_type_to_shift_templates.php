<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menambahkan tipe shift pada template: tetap, fleksibel, atau interval.
 *
 * Kolom ini yang membedakan cara jam shift dibaca:
 *   - tetap    : jam masuk dan jam pulang tetap, tidak boleh lewat tengah malam
 *   - fleksibel: karyawan boleh datang di rentang tertentu atau jam kerja
 *                dihitung dari jam datang, boleh lewat tengah malam
 *   - interval : satu hari pecah jadi beberapa sesi, ditangani di migration
 *                terpisah bersama tabel `shift_intervals`
 *
 * Hanya tipe fleksibel dan interval yang boleh melewati tengah malam. Aturan
 * shift tetap sengaja dibiarkan seperti semula supaya perubahan ini tidak
 * diam-diam mengubah perilaku shift yang sudah berjalan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shift_templates', function (Blueprint $table) {
            $table->string('tipe', 20)->default('tetap')->after('kode');
            // Hanya relevan untuk tipe fleksibel:
            // bebas          -> scan masuk tanpa batas jam
            // terbatas       -> scan masuk harus di antara jam masuk dan batas_telat
            // durasi_tetap   -> jam pulang dihitung dari jam masuk + durasi_kerja_menit
            $table->string('fleksibel_tipe', 20)->nullable()->after('tipe');
            $table->unsignedSmallInteger('durasi_kerja_menit')->nullable()->after('fleksibel_tipe');
            // Batas terakhir scan masuk. Setelah jam ini scan masuk ditolak.
            $table->time('jam_cut_off')->nullable()->after('durasi_kerja_menit');
        });
    }

    public function down(): void
    {
        Schema::table('shift_templates', function (Blueprint $table) {
            $table->dropColumn(['tipe', 'fleksibel_tipe', 'durasi_kerja_menit', 'jam_cut_off']);
        });
    }
};
