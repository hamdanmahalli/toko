<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

/**
 * Halaman panduan penggunaan aplikasi.
 *
 * Route-nya berada di luar middleware auth supaya panduan tetap bisa dibaca
 * orang yang belum masuk, misalnya saat lupa password. Halaman ini juga memakai
 * layout sendiri supaya tidak menyerupai area admin maupun area karyawan.
 */
class PanduanController extends Controller
{
    /**
     * Daftar bagian. `id` dipakai sebagai anchor URL dan sebagai kunci
     * pencarian di sisi peramban, jadi harus unik dan tidak berubah.
     *
     * @var array<int, array{id: string, judul: string, ringkas: string, view: string}>
     */
    private const BAGIAN = [
        [
            'id' => 'tentang',
            'judul' => 'Tentang Aplikasi',
            'ringkas' => 'Apa ini aplikasi dan siapa yang memakainya',
            'view' => 'panduan.bagian.tentang',
        ],
        [
            'id' => 'mulai-cepat',
            'judul' => 'Mulai Cepat',
            'ringkas' => 'Langkah paling singkat sampai absensi pertama tercatat',
            'view' => 'panduan.bagian.mulai-cepat',
        ],
        [
            'id' => 'peran',
            'judul' => 'Peran dan Hak Akses',
            'ringkas' => 'Perbedaan pemilik, supervisor, dan karyawan',
            'view' => 'panduan.bagian.peran',
        ],
        [
            'id' => 'karyawan',
            'judul' => 'Panduan Karyawan',
            'ringkas' => 'Absen, kartu absen, riwayat, dan pengajuan',
            'view' => 'panduan.bagian.karyawan',
        ],
        [
            'id' => 'admin',
            'judul' => 'Panduan Admin',
            'ringkas' => 'Toko, karyawan, jabatan, shift, laporan, pengguna',
            'view' => 'panduan.bagian.admin',
        ],
        [
            'id' => 'cara-kerja-absen',
            'judul' => 'Cara Kerja Absen',
            'ringkas' => 'Aturan lengkap di balik status Tepat Waktu dan Terlambat',
            'view' => 'panduan.bagian.cara-kerja-absen',
        ],
        [
            'id' => 'masalah',
            'judul' => 'Masalah dan Solusinya',
            'ringkas' => 'Semua pesan error dan apa yang harus dilakukan',
            'view' => 'panduan.bagian.masalah',
        ],
        [
            'id' => 'pertanyaan',
            'judul' => 'Pertanyaan Umum',
            'ringkas' => 'Hal yang paling sering ditanyakan',
            'view' => 'panduan.bagian.pertanyaan',
        ],
    ];

    public function index(): View
    {
        return view('panduan.index', ['bagian' => self::BAGIAN]);
    }
}
