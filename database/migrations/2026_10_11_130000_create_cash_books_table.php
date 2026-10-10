<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_books', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('nama', 120);
            $table->text('keterangan')->nullable();
            $table->decimal('saldo_awal', 16, 2)->default(0);
            $table->boolean('aktif')->default(true);
            $table->timestamps();

            $table->index(['employee_id', 'aktif']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_books');
    }
};
