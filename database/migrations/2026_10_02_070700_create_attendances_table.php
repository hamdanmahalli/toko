<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('shop_id')->constrained('shops')->restrictOnDelete();
            $table->date('tanggal');

            $table->time('jam_masuk')->nullable();
            $table->time('jam_pulang')->nullable();

            $table->enum('status_masuk', ['tepat_waktu', 'terlambat'])->nullable();
            $table->enum('status_pulang', ['tepat_waktu', 'pulang_cepat'])->nullable();

            // koordinat & jarak dihitung ulang di server, tidak pernah diambil dari browser
            $table->decimal('latitude_masuk', 10, 7)->nullable();
            $table->decimal('longitude_masuk', 10, 7)->nullable();
            $table->decimal('jarak_masuk_meter', 9, 1)->nullable();
            $table->decimal('accuracy_masuk_meter', 9, 1)->nullable();

            $table->decimal('latitude_pulang', 10, 7)->nullable();
            $table->decimal('longitude_pulang', 10, 7)->nullable();
            $table->decimal('jarak_pulang_meter', 9, 1)->nullable();
            $table->decimal('accuracy_pulang_meter', 9, 1)->nullable();

            $table->string('foto_masuk')->nullable();
            $table->string('foto_pulang')->nullable();

            $table->enum('metode', ['qr', 'selfie', 'kiosk'])->default('qr');
            $table->string('device_id')->nullable();
            $table->text('catatan')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            // dijamin database: satu karyawan hanya punya satu baris per hari
            $table->unique(['employee_id', 'tanggal']);
            $table->index(['tanggal', 'shop_id']);
            $table->index(['shop_id', 'tanggal']);
            $table->index('status_masuk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
