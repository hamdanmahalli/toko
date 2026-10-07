<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shift_windows', function (Blueprint $table) {
            $table->id();
            $table->string('nama');

            // Pita waktu dalam sehari. Sengaja tidak boleh melewati tengah malam:
            // aplikasi mengasumsikan shift selalu selesai di hari yang sama.
            $table->time('mulai');
            $table->time('selesai');

            // null = berlaku untuk semua toko
            $table->foreignId('shop_id')->nullable()->constrained('shops')->cascadeOnDelete();

            // urutan tampil sekaligus penentu saat dua window tumpang tindih
            $table->unsignedSmallInteger('urutan')->default(0);

            $table->boolean('aktif')->default(true);
            $table->timestamps();

            $table->index(['shop_id', 'aktif']);
            $table->index(['mulai', 'selesai']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_windows');
    }
};
