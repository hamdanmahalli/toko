<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menambahkan atribut lengkap shift sesuai kebutuhan pengaturan shift:
 * kode, aturan ketat/toleran, dan batas durasi kerja.
 *
 * `durasi_maks_menit` sengaja hanya disimpan sebagai acuan/catatan. Durasi
 * kerja tidak dipotong di sini supaya tidak bentrok dengan modul lembur.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shift_windows', function (Blueprint $table) {
            $table->string('kode', 30)->nullable()->after('nama');
            $table->enum('aturan_absensi', ['ketat', 'toleran'])->default('ketat')->after('selesai');
            $table->unsignedSmallInteger('durasi_maks_menit')->nullable()->after('aturan_absensi');
        });

        Schema::table('shift_templates', function (Blueprint $table) {
            $table->string('kode', 30)->nullable()->after('nama');
            $table->unsignedSmallInteger('durasi_maks_menit')->nullable()->after('kode');
        });

        Schema::table('shift_slots', function (Blueprint $table) {
            // Slot per hari bisa menimpa batas durasi template, misalnya hari
            // Jumat lebih pendek.
            $table->unsignedSmallInteger('durasi_maks_menit')->nullable()->after('jam_pulang');
        });

        Schema::table('attendances', function (Blueprint $table) {
            // Shift interval punya beberapa sesi scan masuk/pulang per hari.
            $table->unsignedTinyInteger('sesi')->default(1)->after('tanggal');
            $table->unsignedSmallInteger('durasi_menit')->nullable()->after('jam_pulang');
            $table->unsignedSmallInteger('durasi_maks_menit')->nullable()->after('durasi_menit');
        });

        // Satu karyawan bisa punya beberapa sesi per tanggal, jadi unique lama
        // harus dilonggarkan. Sesi 1 tetap menjadi sesi pertama.
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropUnique('attendances_employee_id_tanggal_unique');
            $table->unique(['employee_id', 'tanggal', 'sesi']);
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropUnique('attendances_employee_id_tanggal_sesi_unique');
            $table->unique(['employee_id', 'tanggal']);

            $table->dropColumn(['sesi', 'durasi_menit', 'durasi_maks_menit']);
        });

        Schema::table('shift_slots', function (Blueprint $table) {
            $table->dropColumn('durasi_maks_menit');
        });

        Schema::table('shift_templates', function (Blueprint $table) {
            $table->dropColumn(['kode', 'durasi_maks_menit']);
        });

        Schema::table('shift_windows', function (Blueprint $table) {
            $table->dropColumn(['kode', 'aturan_absensi', 'durasi_maks_menit']);
        });
    }
};
