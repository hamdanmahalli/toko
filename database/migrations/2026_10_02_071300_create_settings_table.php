<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key', 100)->primary();
            $table->text('value')->nullable();
            $table->enum('tipe', ['string', 'int', 'decimal', 'bool', 'json'])->default('string');
            $table->string('kelompok', 50)->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->index('kelompok');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
