<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Services\LaporanService;
use App\Support\CakupanToko;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

/**
 * Laporan durasi kerja karyawan.
 *
 * Rentang tanggal dibatasi satu tahun supaya satu klik tidak memuat seluruh
 * riwayat absensi dan membuat halaman berat.
 */
class LaporanController extends Controller
{
    private const PER_HALAMAN = 25;

    public function __construct(
        private readonly LaporanService $laporan,
        private readonly CakupanToko $cakupan,
    ) {}

    public function index(Request $request): View
    {
        // Input mentah divalidasi dulu. Memparsing tanggal lebih dulu akan
        // melempar exception saat pengguna mengetik tanggal setengah jadi, dan
        // exception-nya bocor sebagai halaman 500.
        $data = Validator::make($request->query(), [
            'dari' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date', 'after_or_equal:dari'],
            'shop' => ['nullable', 'integer'],
            'q' => ['nullable', 'string', 'max:100'],
        ])->validate();

        $dari = isset($data['dari']) ? Carbon::parse($data['dari'])->startOfDay() : now()->startOfMonth();
        $sampai = isset($data['sampai']) ? Carbon::parse($data['sampai'])->endOfDay() : now()->endOfDay();

        // Permintaan dengan rentang tidak wajar ditolak, bukan diam-diam dipotong,
        // supaya angka yang tampil selalu sama dengan yang diminta. Rentang
        // dibatasi dua tahun supaya laporan satu klik tidak memuat seluruh
        // riwayat absensi.
        Validator::make([
            'dari' => $dari->toDateString(),
            'sampai' => $sampai->toDateString(),
        ], [
            'dari' => [
                'before_or_equal:'.now()->toDateString(),
                'after_or_equal:'.now()->subYears(2)->toDateString(),
            ],
            'sampai' => [
                'after_or_equal:dari',
                'before_or_equal:'.now()->addMonth()->toDateString(),
            ],
        ], [
            'before_or_equal' => 'Tanggal tidak boleh berada di masa depan.',
            'after_or_equal' => 'Rentang laporan dibatasi dua tahun dan tanggal akhir harus setelah tanggal mulai.',
        ])->validate();

        $tokoDiminta = isset($data['shop']) ? (int) $data['shop'] : null;

        // Filter toko yang tidak diizinkan diubah jadi "semua toko milik
        // pengguna", bukan Toko NULL yang berarti seluruh jaringan.
        $shopId = $tokoDiminta === null || ! $this->cakupan->boleh($request->user(), $tokoDiminta)
            ? ($this->cakupan->semuaToko($request->user()) ? null : $this->cakupan->idToko($request->user())->all())
            : [$tokoDiminta];

        $cari = $data['q'] ?? null;

        $hasil = $this->laporan->durasiKaryawan($dari->toDateString(), $sampai->toDateString(), $shopId, $cari);

        $baris = new LengthAwarePaginator(
            $hasil['baris']->forPage($request->integer('page', 1), self::PER_HALAMAN)->values(),
            $hasil['baris']->count(),
            self::PER_HALAMAN,
            $request->integer('page', 1),
            ['path' => $request->url(), 'query' => $request->query()],
        );

        return view('admin.laporan.index', [
            'baris' => $baris,
            'ringkasan' => $hasil['ringkasan'],
            'dari' => $dari,
            'sampai' => $sampai,
            'toko' => $this->pilihanToko($request),
        ]);
    }

    /** Toko yang boleh dipakai sebagai filter, mengikuti cakupan akses pengguna. */
    private function pilihanToko(Request $request)
    {
        return Shop::query()
            ->when(! $this->cakupan->semuaToko($request->user()), fn ($q) => $q->whereIn('id', $this->cakupan->idToko($request->user())))
            ->orderBy('nama')
            ->get(['id', 'nama']);
    }
}
