<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_runs', function (Blueprint $table) {
            $table->id();
            // always stored as the first day of the month
            $table->date('periode')->unique();
            $table->enum('status', ['draft', 'final', 'batal'])->default('draft');

            $table->unsignedInteger('jumlah_karyawan')->default(0);
            $table->decimal('total_pokok', 16, 2)->default(0);
            $table->decimal('total_lembur', 16, 2)->default(0);
            $table->decimal('total_potong', 16, 2)->default(0);
            $table->decimal('total_bersih', 16, 2)->default(0);

            $table->text('catatan')->nullable();
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_runs');
    }
};
