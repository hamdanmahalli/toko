<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Identitas perangkat tempat akun boleh login. Karyawan hanya boleh
        // masuk dari perangkat yang disetujui pemilik/kepala toko; baris
        // tercipta otomatis saat ada percobaan masuk dari perangkat baru.
        Schema::create('user_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('device_token', 64);
            $table->string('label', 120)->nullable();
            $table->string('status', 16)->default('pending');
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'device_token']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_devices');
    }
};
