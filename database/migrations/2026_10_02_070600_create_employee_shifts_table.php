<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('shift_template_id')->constrained('shift_templates')->cascadeOnDelete();

            $table->date('mulai_berlaku')->nullable();
            $table->date('selesai_berlaku')->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();

            $table->index(['employee_id', 'aktif']);
            $table->index(['employee_id', 'mulai_berlaku', 'selesai_berlaku']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_shifts');
    }
};
