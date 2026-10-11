<?php

namespace App\Models;

use App\Enums\JenisKas;
use Database\Factories\CashBookTransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

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
            'tanggal' => 'date',
            'jumlah' => 'decimal:2',
        ];
    }

    public function cashBook(): BelongsTo
    {
        return $this->belongsTo(CashBook::class);
    }

    /**
     * Nama kategori untuk ditampilkan. `kategori` menyimpan kode; peta nama
     * diambil dari daftar kategori karyawan, dengan cadangan bila kategori
     * sudah dihapus.
     *
     * @param  array<string, string>  $peta
     */
    public function labelKategori(array $peta): string
    {
        return $peta[$this->kategori] ?? Str::headline((string) $this->kategori);
    }

    /** Nilai bertanda: pemasukan positif, pengeluaran negatif. */
    public function nilaiBertanda(): float
    {
        return $this->jenis === JenisKas::Masuk ? (float) $this->jumlah : -(float) $this->jumlah;
    }
}
