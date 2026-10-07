<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Pencatat jejak audit.
 *
 * Tabel `audit_logs` sudah ada sejak awal tapi belum pernah ditulis, sehingga
 * tidak ada cara menjawab "siapa yang mengubah ini?". Kelas ini mengisi
 * kekosongan itu dan sengaja dibuat satu pintu supaya semua pencatatan punya
 * bentuk yang sama.
 *
 * Menulis audit tidak boleh menggagalkan aksi yang sedang dicatat: kalau tabel
 * audit bermasalah, aksi utama sudah bisa jadi tersimpan. Jadi kegagalan
 * dicatat ke log aplikasi dan diteruskan, bukan dilempar ke pemanggil.
 */
class AuditLogger
{
    public function __construct(private readonly Request $request) {}

    /**
     * Catat satu aksi.
     *
     * @param  array<string, mixed>|null  $payload  detail tambahan; hanya yang berupa skalar yang disimpan supaya jsonb tidak gagal
     */
    public function catat(
        string $aksi,
        ?Model $entitas = null,
        array $payload = [],
        ?string $entitasId = null,
    ): void {
        try {
            AuditLog::create([
                'user_id' => $this->request->user()?->getKey(),
                'aksi' => $aksi,
                'entitas' => $entitas === null ? null : Str::snake(class_basename($entitas)),
                'entitas_id' => $entitasId ?? ($entitas?->getKey() === null ? null : (string) $entitas->getKey()),
                'ip' => $this->request->ip(),
                'user_agent' => substr((string) $this->request->userAgent(), 0, 2000) ?: null,
                'payload' => $payload === [] ? null : $this->amankan($payload),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /** Catat percobaan akses data yang gagal, mis. kartu QR tidak valid. */
    public function catatGagal(string $aksi, string $alasan, array $payload = []): void
    {
        $this->catat($aksi, null, ['berhasil' => false, 'alasan' => $alasan] + $payload);
    }

    /**
     * Buang nilai yang tidak bisa disimpan sebagai jsonb.
     *
     * Objek dan closure di dalam payload akan membuat insert gagal, dan
     * kegagalan audit tidak boleh menjatuhkan aksi bisnisnya.
     *
     * @param  array<array-key, mixed>  $payload
     * @return array<array-key, mixed>
     */
    private function amankan(array $payload): array
    {
        $aman = [];

        foreach ($payload as $kunci => $nilai) {
            if (is_scalar($nilai) || $nilai === null) {
                $aman[$kunci] = $nilai;
            } elseif (is_array($nilai)) {
                // Rekursi langsung pada array-nya, bukan dibungkus lagi:
                // membungkus akan mengulang nilai yang sama tanpa henti.
                $aman[$kunci] = $this->amankan($nilai);
            } elseif ($nilai instanceof \BackedEnum) {
                $aman[$kunci] = $nilai->value;
            } elseif ($nilai instanceof \DateTimeInterface) {
                $aman[$kunci] = $nilai->format(DATE_ATOM);
            } elseif ($nilai instanceof Model) {
                $aman[$kunci] = [
                    'model' => class_basename($nilai),
                    'id' => $nilai->getKey(),
                ];
            } else {
                $aman[$kunci] = null;
            }
        }

        return $aman;
    }

    /** Catat perubahan absensi, dipakai saat perangkat presensi memproses pemindaian. */
    public function catatAbsensi(Attendance $absensi, string $arah, ?string $presensi = null): void
    {
        $this->catat($arah === 'masuk' ? 'absen.masuk' : 'absen.pulang', $absensi, [
            'arah' => $arah,
            'sesi' => $absensi->sesi,
            'metode' => $absensi->metode?->value,
            'status_masuk' => $absensi->status_masuk?->value,
            'status_pulang' => $absensi->status_pulang?->value,
            'durasi_menit' => $absensi->durasi_menit,
            'presensi' => $presensi,
        ]);
    }
}
