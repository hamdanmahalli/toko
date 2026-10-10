<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_book_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_book_id')->constrained('cash_books')->cascadeOnDelete();
            $table->enum('jenis', ['masuk', 'keluar']);
            $table->string('kategori', 40);
            $table->date('tanggal');
            $table->decimal('jumlah', 16, 2);
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->index(['cash_book_id', 'tanggal']);
            $table->index(['cash_book_id', 'jenis']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_book_transactions');
    }
};
