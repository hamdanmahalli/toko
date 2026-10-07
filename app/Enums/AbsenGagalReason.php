<?php

namespace App\Enums;

/**
 * Alasan penolakan saat mencatat absensi.
 */
enum AbsenGagalReason: string
{
    case KaryawanTidakAktif = 'karyawan_tidak_aktif';
    case TokoTanpaKoordinat = 'toko_tanpa_koordinat';
    case KoordinatTidakValid = 'koordinat_tidak_valid';
    case AkurasiGpsTerlaluKasar = 'akurasi_gps_terlalu_kasar';
    case DiluarRadius = 'diluar_radius';
    case SudahAbsenMasuk = 'sudah_absen_masuk';
    case BelumAbsenMasuk = 'belum_absen_masuk';
    case SudahAbsenPulang = 'sudah_absen_pulang';
    case LewatJamCutOff = 'lewat_jam_cut_off';
    case DiLuarSesiInterval = 'diluar_sesi_interval';
    case QrTidakValid = 'qr_tidak_valid';
    case PegawaiTidakDitemukan = 'pegawai_tidak_ditemukan';
    case KaryawanTokoLain = 'karyawan_toko_lain';

    /** Pesan yang layak ditampilkan ke karyawan. */
    public function pesan(): string
    {
        return match ($this) {
            self::KaryawanTidakAktif => 'Akun Anda sudah tidak aktif. Hubungi atasan.',
            self::TokoTanpaKoordinat => 'Toko Anda belum diisi koordinat, geofence tidak bisa diperiksa.',
            self::KoordinatTidakValid => 'Lokasi GPS tidak terbaca. Coba buka di ruang terbuka.',
            self::AkurasiGpsTerlaluKasar => 'Sinyal GPS terlalu lemah untuk memastikan lokasi. Coba beberapa saat lagi.',
            self::DiluarRadius => 'Anda berada di luar area toko.',
            self::SudahAbsenMasuk => 'Anda sudah absen masuk hari ini.',
            self::BelumAbsenMasuk => 'Anda belum absen masuk hari ini.',
            self::SudahAbsenPulang => 'Anda sudah absen pulang hari ini.',
            self::LewatJamCutOff => 'Sudah lewat jam cut-off, absen masuk tidak bisa lagi.',
            self::DiLuarSesiInterval => 'Di luar jam sesi shift, absen masuk belum bisa dilakukan.',
            self::QrTidakValid => 'Kode QR tidak dikenali. Pastikan memindai kartu yang masih berlaku.',
            self::PegawaiTidakDitemukan => 'Data karyawan tidak ditemukan.',
            self::KaryawanTokoLain => 'Kartu ini milik karyawan toko lain.',
        };
    }

    /** Alasan yang disebabkan lokasi atau akurasi GPS, bukan aturan absensi. */
    public function masalahLokasi(): bool
    {
        return in_array($this, [
            self::DiluarRadius,
            self::AkurasiGpsTerlaluKasar,
            self::KoordinatTidakValid,
        ], true);
    }
}
