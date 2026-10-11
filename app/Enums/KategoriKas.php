<?php

namespace App\Enums;

/**
 * Kategori tetap buku kas. Jenis (masuk/keluar) diturunkan dari kategori,
 * jadi satu transaksi tidak pernah punya jenis yang bertentangan dengan
 * kategorinya.
 */
enum KategoriKas: string
{
    case Penjualan = 'penjualan';
    case Modal = 'modal';
    case PendapatanLain = 'pendapatan_lain';
    case Belanja = 'belanja';
    case Gaji = 'gaji';
    case Operasional = 'operasional';
    case Transport = 'transport';
    case Sewa = 'sewa';
    case ListrikAir = 'listrik_air';
    case Lainnya = 'lainnya';

    public function label(): string
    {
        return match ($this) {
            self::Penjualan => 'Penjualan',
            self::Modal => 'Modal / talangan',
            self::PendapatanLain => 'Pendapatan lain',
            self::Belanja => 'Belanja stok',
            self::Gaji => 'Gaji / upah',
            self::Operasional => 'Operasional',
            self::Transport => 'Transport',
            self::Sewa => 'Sewa',
            self::ListrikAir => 'Listrik / air',
            self::Lainnya => 'Lainnya',
        };
    }

    public function jenis(): JenisKas
    {
        return match ($this) {
            self::Penjualan, self::Modal, self::PendapatanLain => JenisKas::Masuk,
            default => JenisKas::Keluar,
        };
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->label()])->all();
    }

    /** @return array<string, string> */
    public static function optionsFor(JenisKas $jenis): array
    {
        return collect(self::cases())
            ->filter(fn (self $case) => $case->jenis() === $jenis)
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }

    /**
     * Kategori bawaan yang disemai ke setiap karyawan. Nilai `kode` sengaja
     * sama dengan nilai enum supaya transaksi lama (yang tersimpan sebagai
     * string enum) langsung cocok tanpa migrasi data.
     *
     * @return array<int, array{kode: string, nama: string, jenis: JenisKas, urutan: int}>
     */
    public static function defaults(): array
    {
        return collect(self::cases())
            ->values()
            ->map(fn (self $case, int $i) => [
                'kode' => $case->value,
                'nama' => $case->label(),
                'jenis' => $case->jenis(),
                'urutan' => $i,
            ])
            ->all();
    }
}
