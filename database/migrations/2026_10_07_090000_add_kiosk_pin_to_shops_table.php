<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            // PIN kios opsional. Null = halaman kios terbuka tanpa PIN, untuk toko
            // yang perangkatnya sengaja berada di dalam area toko. Diisi =
            // kios meminta PIN sebelum boleh memindai kartu.
            $table->string('kiosk_pin', 60)->nullable()->after('tutup');
        });
    }

    public function down(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->dropColumn('kiosk_pin');
        });
    }
};
