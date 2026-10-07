<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_run_id')->constrained('payroll_runs')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('shop_id')->constrained('shops')->restrictOnDelete();

            $table->unsignedSmallInteger('hari_hadir')->default(0);
            $table->unsignedSmallInteger('hari_izin')->default(0);
            $table->unsignedSmallInteger('hari_sakit')->default(0);
            $table->unsignedSmallInteger('hari_cuti')->default(0);
            $table->unsignedSmallInteger('hari_alpha')->default(0);
            $table->unsignedSmallInteger('hari_libur')->default(0);

            $table->unsignedSmallInteger('menit_telat')->default(0);
            $table->decimal('jam_lembur', 7, 2)->default(0);

            $table->decimal('gaji_pokok', 16, 2)->default(0);
            $table->decimal('total_lembur', 16, 2)->default(0);
            $table->decimal('potongan', 16, 2)->default(0);
            $table->decimal('total', 16, 2)->default(0);

            $table->jsonb('rincian')->nullable();
            $table->timestamps();

            $table->unique(['payroll_run_id', 'employee_id']);
            $table->index('employee_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_details');
    }
};
