<?php

namespace App\Services;

use App\Enums\AbsenGagalReason;
use App\Exceptions\AbsenException;
use App\Models\Setting;
use App\Models\Shop;

/**
 * Perhitungan geofence. Seluruh angka jarak dihitung ULANG di sini dari
 * koordinat, jadi nilai jarak yang dikirim browser tidak pernah dipakai.
 */
class GeoService
{
    /** Radius bumi rata-rata dalam meter (WGS84). */
    private const RADIUS_BUMI = 6371008.8;

    /**
     * Jarak dua titik koordinat dalam meter (haversine).
     */
    public function jarakMeter(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        // atan2 lebih stabil secara numerik daripada asin(sqrt($a))
        return self::RADIUS_BUMI * 2 * atan2(sqrt($a), sqrt(max(0.0, 1 - $a)));
    }

    public function koordinatValid(?float $lat, ?float $lng): bool
    {
        return $lat !== null && $lng !== null
            && $lat >= -90 && $lat <= 90
            && $lng >= -180 && $lng <= 180;
    }

    public function pastikanKoordinat(?float $lat, ?float $lng): void
    {
        if (! $this->koordinatValid($lat, $lng)) {
            throw AbsenException::dari(AbsenGagalReason::KoordinatTidakValid);
        }
    }

    /**
     * Akurasi GPS dari browser (radius lingkaran ketidakpastian, dalam meter).
     * Nilai yang terlalu besar berarti posisi belum bisa dipercaya.
     *
     * Batasnya longgar secara sengaja: di dalam toko akurasi 100-300 m itu
     * wajar, sedangkan menolak absensi hanya karena sinyal kasar membuat
     * karyawan terkunci di luar. Batas bisa diatur lewat setting
     * `geofence.akurasi_maks_meter`.
     */
    public function pastikanAkurasi(?float $akurasi): void
    {
        if ($akurasi === null) {
            return;
        }

        $maks = (float) Setting::ambilInt('geofence.akurasi_maks_meter', 500);

        if ($akurasi > $maks) {
            throw AbsenException::dari(AbsenGagalReason::AkurasiGpsTerlaluKasar, [
                'akurasi_meter' => round($akurasi, 1),
                'maks_meter' => $maks,
            ]);
        }
    }

    /** True bila akurasi lebih kasar dari radius geofence, jadi perlu dicatat manual. */
    public function akurasiPerluPerhatian(?float $akurasi, Shop $shop): bool
    {
        return $akurasi !== null && $akurasi > $shop->radius_meter;
    }

    /** Jarak ke titik koordinat toko, dihitung di server. */
    public function jarakKeToko(Shop $shop, float $lat, float $lng): float
    {
        if (! $shop->hasGeofence()) {
            throw AbsenException::dari(AbsenGagalReason::TokoTanpaKoordinat, [
                'toko' => $shop->nama,
            ]);
        }

        return $this->jarakMeter($lat, $lng, $shop->latitude, $shop->longitude);
    }

    /**
     * Tolak kalau jarak ke toko melebihi radius yang ditentukan toko.
     *
     * @return float jarak terhitung dalam meter
     */
    public function pastikanDalamRadius(Shop $shop, float $lat, float $lng): float
    {
        $jarak = $this->jarakKeToko($shop, $lat, $lng);

        if ($jarak > $shop->radius_meter) {
            throw AbsenException::dari(AbsenGagalReason::DiluarRadius, [
                'jarak_meter' => round($jarak, 1),
                'radius_meter' => $shop->radius_meter,
                'toko' => $shop->nama,
            ]);
        }

        return $jarak;
    }

    /** "45 m" atau "1,2 km". */
    public function formatJarak(?float $meter): string
    {
        if ($meter === null) {
            return '-';
        }

        if ($meter < 1000) {
            return round($meter).' m';
        }

        return number_format($meter / 1000, 1, ',', '.').' km';
    }
}
