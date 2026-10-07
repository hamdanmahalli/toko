<?php

namespace App\Services;

use App\Enums\AbsenGagalReason;
use App\Exceptions\AbsenException;
use App\Models\Employee;
use Picqer\Barcode\Helpers\ColorHelper;
use Picqer\Barcode\Renderers\SvgRenderer;
use Picqer\Barcode\Types\TypeCode128;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * Token QR pribadi karyawan.
 *
 * Format: ATOKO:{id_karyawan}:{versi}:{tanda_tangan}
 *
 * Tanda tangan HMAC memakai APP_KEY, jadi token tidak bisa direkayasa walau
 * id karyawan-nya diketahui. Menaikkan `qr_version` membuat seluruh kartu lama
 * tidak berlaku lagi.
 */
class QrService
{
    private const PREFIX = 'ATOKO';

    public function token(Employee $employee): string
    {
        $id = $employee->id;
        $versi = (int) $employee->qr_version;
        $payload = "{$id}:{$versi}";

        $tanda = substr(hash_hmac('sha256', $payload, $this->kunci()), 0, 16);

        return self::PREFIX.':'.$id.':'.$versi.':'.$tanda;
    }

    /**
     * Baca token lalu kembalikan karyawannya, atau null kalau tidak valid.
     */
    public function cariKaryawan(string $token): ?Employee
    {
        $token = trim($token);

        $bagian = explode(':', $token);

        if (count($bagian) !== 4 || $bagian[0] !== self::PREFIX) {
            return null;
        }

        [, $id, $versi, $tanda] = $bagian;

        if (! ctype_digit($id) || ! ctype_digit($versi)) {
            return null;
        }

        $payload = "{$id}:{$versi}";
        $harapan = substr(hash_hmac('sha256', $payload, $this->kunci()), 0, 16);

        if (! hash_equals($harapan, $tanda)) {
            return null;
        }

        $employee = Employee::find((int) $id);

        // versi sudah naik -> kartu lama dicabut
        if ($employee === null || (int) $employee->qr_version !== (int) $versi) {
            return null;
        }

        return $employee;
    }

    /** Sama seperti cariKaryawan(), tapi melempar exception bila tidak valid. */
    public function wajibKaryawan(string $token): Employee
    {
        $employee = $this->cariKaryawan($token);

        if ($employee === null) {
            throw AbsenException::dari(AbsenGagalReason::QrTidakValid);
        }

        return $employee;
    }

    /** Buat kartu QR sebagai SVG supaya bisa langsung dicetak. */
    public function svg(Employee $employee, int $ukuran = 320): string
    {
        return QrCode::format('svg')
            ->size($ukuran)
            ->margin(1)
            ->errorCorrection('M')
            ->generate($this->token($employee));
    }

    /** Data URI untuk ditampilkan langsung di <img src="...">. */
    public function dataUri(Employee $employee, int $ukuran = 320): string
    {
        return 'data:image/svg+xml;base64,'.base64_encode($this->svg($employee, $ukuran));
    }

    /**
     * Barcode Code128 untuk kartu yang dipindai scanner USB.
     *
     * Isinya token yang sama persis dengan QR, jadi tidak ada format kedua
     * yang harus dipahami server: perangkat presensi cukup membaca string lalu
     * meneruskannya ke cariKaryawan(). Menaikkan `qr_version` otomatis
     * membatalkan barcode lama juga.
     *
     * Panjang awal diambil dari jumlah module kode, jadi `lebarBar` dikecilkan
     * dan `tinggi` dinaikkan di sini: lebar asli ~285px supaya muat di wadah
     * kartu yang lebarnya dibatasi 300px, dan tinggi yang lebih besar tetap
     * membuat baris kode yang ramping itu mudah dibaca scanner.
     *
     * Token hanya berisi huruf, angka, dan titik dua, semuanya ada di
     * Code128. SVG-nya disisipkan inline (tanpa header XML) supaya bisa
     * ditempel langsung ke halaman kartu.
     */
    public function barcodeSvg(Employee $employee, float $lebarBar = 0.9, float $tinggi = 36.0): string
    {
        $barcode = new TypeCode128;
        $data = $barcode->getBarcode($this->token($employee));

        $renderer = new SvgRenderer;
        $renderer->setSvgType(SvgRenderer::TYPE_SVG_INLINE);
        $renderer->setForegroundColor(ColorHelper::getArrayFromColorString('black'));

        return $renderer->render($data, round($data->getWidth() * $lebarBar, 3), $tinggi);
    }

    private function kunci(): string
    {
        $key = (string) config('app.key');

        // app.key disimpan sebagai "base64:..." oleh Laravel
        if (str_starts_with($key, 'base64:')) {
            $key = base64_decode(substr($key, 7)) ?: $key;
        }

        return $key;
    }
}
