<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shift_templates', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->enum('scope', ['global', 'toko', 'individual'])->default('toko');
            $table->foreignId('shop_id')->nullable()->constrained('shops')->cascadeOnDelete();
            $table->text('keterangan')->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();

            $table->index(['scope', 'aktif']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_templates');
    }
};
