<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PIN kios diganti jadi login bersama: satu user dan satu password per toko
 * yang dipakai bersama seluruh karyawan di toko itu, bukan akun per karyawan.
 *
 * PIN lama tidak bisa dipetakan ke user dan password, jadi setiap kios akan
 * ditolak sampai pemilik toko mengisi kredensial baru. Ini yang diinginkan:
 * kiosk tanpa kredensial tidak boleh terbuka.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->string('kiosk_user', 60)->nullable()->after('tutup');
            $table->string('kiosk_password')->nullable()->after('kiosk_user');
        });

        Schema::table('shops', function (Blueprint $table) {
            // Unik supaya satu user kios tidak dipakai dua toko: kalau sampai
            // terjadi, yang salah bisa saja masuk ke kios toko yang bukan
            // haknya. Postgres dan MySQL mengizinkan banyak NULL di indeks unik.
            $table->unique('kiosk_user');
        });

        Schema::table('shops', function (Blueprint $table) {
            $table->dropColumn('kiosk_pin');
        });
    }

    public function down(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->dropUnique(['kiosk_user']);
        });

        Schema::table('shops', function (Blueprint $table) {
            $table->string('kiosk_pin', 60)->nullable()->after('tutup');
        });

        Schema::table('shops', function (Blueprint $table) {
            $table->dropColumn(['kiosk_user', 'kiosk_password']);
        });
    }
};
