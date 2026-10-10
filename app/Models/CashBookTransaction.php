<?php

namespace App\Models;

use App\Enums\JenisKas;
use App\Enums\KategoriKas;
use Database\Factories\CashBookTransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[
    Fillable(['cash_book_id', 'jenis', 'kategori', 'tanggal', 'jumlah', 'keterangan']),
]
class CashBookTransaction extends Model
{
    /** @use HasFactory<CashBookTransactionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'jenis' => JenisKas::class,
            'kategori' => KategoriKas::class,
            'tanggal' => 'date',
            'jumlah' => 'decimal:2',
        ];
    }

    public function cashBook(): BelongsTo
    {
        return $this->belongsTo(CashBook::class);
    }

    /** Nilai bertanda: pemasukan positif, pengeluaran negatif. */
    public function nilaiBertanda(): float
    {
        return $this->jenis === JenisKas::Masuk ? (float) $this->jumlah : -(float) $this->jumlah;
    }
}
