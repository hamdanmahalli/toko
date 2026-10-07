<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sesi jam kerja untuk template shift bertipe interval.
 *
 * Shift interval bukan satu rentang jam masuk-pulang, melainkan beberapa sesi
 * terpisah dalam sehari, misalnya "Shift Pagi 08:00-12:00" dan
 * "Shift Siang 12:00-16:00". Setiap sesi bisa punya durasi minimum sendiri,
 * dipakai sebagai catatan ketika durasi scan berbeda dari rencana.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shift_intervals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_template_id')->constrained()->cascadeOnDelete();
            $table->string('nama', 60);
            $table->time('mulai');
            $table->time('selesai');
            // Durasi rencana per sesi. Bila kosong, dihitung dari mulai/selesai.
            $table->unsignedSmallInteger('durasi_min_menit')->nullable();
            $table->unsignedTinyInteger('urutan')->default(1);
            $table->boolean('aktif')->default(true);
            $table->timestamps();

            $table->unique(['shift_template_id', 'urutan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_intervals');
    }
};
