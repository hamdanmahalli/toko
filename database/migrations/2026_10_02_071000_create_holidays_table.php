<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->date('tanggal');
            // null = libur nasional, berlaku untuk semua toko
            $table->foreignId('shop_id')->nullable()->constrained('shops')->cascadeOnDelete();
            $table->boolean('aktif')->default(true);
            $table->timestamps();

            $table->index('tanggal');
            $table->index(['shop_id', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('holidays');
    }
};
