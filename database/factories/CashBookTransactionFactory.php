<?php

namespace Database\Factories;

use App\Enums\JenisKas;
use App\Enums\KategoriKas;
use App\Models\CashBook;
use App\Models\CashBookTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CashBookTransaction>
 */
class CashBookTransactionFactory extends Factory
{
    protected $model = CashBookTransaction::class;

    public function definition(): array
    {
        return [
            'cash_book_id' => CashBook::factory(),
            'jenis' => JenisKas::Masuk,
            'kategori' => KategoriKas::Penjualan,
            'tanggal' => now()->toDateString(),
            'jumlah' => fake()->numberBetween(5_000, 500_000),
            'keterangan' => null,
        ];
    }

    public function masuk(): static
    {
        return $this->state(fn () => [
            'jenis' => JenisKas::Masuk,
            'kategori' => KategoriKas::Penjualan,
        ]);
    }

    public function keluar(): static
    {
        return $this->state(fn () => [
            'jenis' => JenisKas::Keluar,
            'kategori' => KategoriKas::Belanja,
        ]);
    }

    public function untuk(CashBook $book): static
    {
        return $this->state(fn () => ['cash_book_id' => $book->id]);
    }
}
