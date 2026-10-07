<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('shop_id')->constrained('shops')->restrictOnDelete();
            $table->foreignId('position_id')->nullable()->constrained('positions')->nullOnDelete();

            $table->string('nip', 30)->nullable()->unique();
            $table->string('nama');
            $table->string('telepon', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('foto')->nullable();

            // incremented whenever the QR card must be invalidated
            $table->unsignedInteger('qr_version')->default(1);

            $table->date('tanggal_masuk')->nullable();
            $table->date('tanggal_keluar')->nullable();

            $table->enum('tipe_payroll', ['harian', 'jam'])->default('harian');
            $table->decimal('gaji_harian', 14, 2)->nullable();
            $table->decimal('tarif_jam', 14, 2)->nullable();

            $table->boolean('aktif')->default(true);
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->index(['shop_id', 'aktif']);
            $table->index('nama');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
