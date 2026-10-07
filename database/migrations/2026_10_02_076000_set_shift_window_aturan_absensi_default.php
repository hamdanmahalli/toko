<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Menyamakan default `aturan_absensi` dengan default di seluruh aplikasi.
 *
 * Model, controller, dan form semuanya memakai Toleran saat aturan tidak
 * diisi, tapi kolomnya masih default "ketat". Akibatnya window yang dibuat di
 * luar form ikut window—seed, factory, impor—diberi sifat ketat tanpa pernah
 * ada yang memilihnya, jadi karyawannya bisa terlahat tanpa disengaja.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shift_windows', function (Blueprint $table) {
            $table->string('aturan_absensi', 255)->default('toleran')->change();
        });
    }

    public function down(): void
    {
        Schema::table('shift_windows', function (Blueprint $table) {
            $table->string('aturan_absensi', 255)->default('ketat')->change();
        });

        // Baris yang sebelumnya tidak punya aturan eksplisit ikut dikembalikan
        // ke default lamanya supaya rollback tidak menyisakan data yang tidak
        // bisa dihasilkan lagi oleh kode versi ini.
        DB::table('shift_windows')->update(['aturan_absensi' => 'ketat']);
    }
};
