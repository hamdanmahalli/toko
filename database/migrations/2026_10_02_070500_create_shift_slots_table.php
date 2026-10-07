<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shift_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_template_id')->constrained('shift_templates')->cascadeOnDelete();

            // 0 = Minggu ... 6 = Sabtu (mengikuti Carbon::dayOfWeek)
            $table->unsignedTinyInteger('hari');

            $table->time('jam_masuk');
            // batas toleransi telat, terpisah dari jam_masuk
            $table->time('batas_telat');
            $table->time('jam_pulang');
            $table->time('mulai_istirahat')->nullable();
            $table->time('selesai_istirahat')->nullable();

            $table->boolean('aktif')->default(true);
            $table->timestamps();

            $table->unique(['shift_template_id', 'hari']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_slots');
    }
};
