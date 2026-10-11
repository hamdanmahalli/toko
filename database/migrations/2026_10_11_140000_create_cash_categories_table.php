<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('kode', 40);
            $table->string('nama', 120);
            $table->enum('jenis', ['masuk', 'keluar']);
            $table->unsignedInteger('urutan')->default(0);
            $table->boolean('aktif')->default(true);
            $table->timestamps();

            $table->unique(['employee_id', 'kode']);
            $table->index(['employee_id', 'jenis', 'aktif']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_categories');
    }
};
