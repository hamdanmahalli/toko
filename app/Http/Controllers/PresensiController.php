<?php

namespace App\Http\Controllers;

use App\Enums\AbsenArah;
use App\Enums\AbsenMasukStatus;
use App\Enums\AbsenMethod;
use App\Exceptions\AbsenException;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Shop;
use App\Services\AbsenService;
use App\Services\AuditLogger;
use App\Services\QrService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Perangkat presensi karyawan per toko.
 *
 * Untuk karyawan yang tidak memakai HP: kartu yang dicetak berisi QR dan
 * barcode, dipindai scanner USB yang menulis string ke input, lalu Enter.
 *
 * Halamannya tidak memakai akun karyawan. Satu user dan satu password per
 * toko dipakai bersama siapa pun yang datang, jadi orang pertama yang masuk
 * bisa langsung memindai kartunya tanpa login lebih dulu.
 *
 * Lima hal yang perangkat presensi ini sengaja lakukan:
 *
 *  1. Perangkat yang belum punya login bersama ditolak, bukan dibuka. "Belum
 *     diisi" berarti "bisa dibuka siapa saja", jadi tidak ada keadaan
 *     setengah terbuka.
 *  2. Kartu harus milik toko yang sedang menjalankannya. Tanpa ini, siapa
 *     pun yang tahu URL toko lain bisa memakai kartu ini untuk absen di sana.
 *  3. Geofence memakai koordinat toko, bukan GPS perangkat. Perangkat ini
 *     berdiri di dalam toko, jadi jaraknya selalu 0 m dari titik geofence.
 *  4. Arah absensi ditentukan server dari sesi yang masih terbuka, bukan dari
 *     pilihan di browser, supaya tidak ada yang bisa memalsukan "pulang" lebih
 *     dulu.
 *  5. Setiap percobaan, berhasil atau tidak, masuk audit log.
 *
 * Karyawan yang tidak diizinkan admin (kolom `boleh_presensi`) ditolak di
 * sini. Izin ini khusus perangkat presensi: absen dari HP milik karyawan
 * tidak terpengaruh.
 */
class PresensiController extends Controller
{
    public function __construct(
        private readonly AbsenService $absen,
        private readonly QrService $qr,
        private readonly AuditLogger $audit,
    ) {}

    /** Halaman pemindai. `$kode` adalah `shops.kode`, bukan id. */
    public function form(Request $request, string $kode): View
    {
        $shop = $this->cariToko($kode);

        if (! $shop->presensiSiap()) {
            return view('presensi.belum-siap', ['shop' => $shop]);
        }

        if (! $this->terbuka($request, $shop)) {
            $this->sentuh($request, $shop);
        }

        $this->sentuh($request, $shop);

        return view('presensi.scan', [
            'shop' => $shop,
            'menitSisa' => $this->menitSisa($request, $shop),
        ]);
    }

    /** Halaman login perangkat presensi. */
    public function masuk(Request $request, string $kode): View
    {
        $shop = $this->cariToko($kode);

        if (! $shop->presensiSiap()) {
            return view('presensi.belum-siap', ['shop' => $shop]);
        }

        return view('presensi.login', ['shop' => $shop]);
    }

    /** Periksa login bersama perangkat presensi. */
    public function prosesMasuk(Request $request, string $kode): RedirectResponse
    {
        $shop = $this->cariToko($kode);

        if (! $shop->presensiSiap()) {
            return redirect()
                ->route('presensi.form', $shop->kode)
                ->with('galat', 'Presensi ini belum punya user dan password.');
        }

        $data = $request->validate([
            'user' => ['required', 'string', 'max:60'],
            'password' => ['required', 'string', 'max:60'],
        ]);

        if (! $shop->cocokkanPresensiLogin($data['user'], $data['password'])) {
            $this->audit->catatGagal('presensi.login', 'kredensial_salah', ['shop_id' => $shop->id]);

            return redirect()
                ->route('presensi.form', $shop->kode)
                ->with('galat', 'User atau password presensi salah.');
        }

        // Ditandai per toko, jadi login di satu toko tidak otomatis membuka
        // perangkat presensi toko lain di perangkat yang sama.
        $this->sentuh($request, $shop);

        return redirect()->route('presensi.form', $shop->kode);
    }

    /** Kunci perangkat presensi untuk dipakai orang berikutnya. */
    public function keluar(Request $request, string $kode): RedirectResponse
    {
        $shop = $this->cariToko($kode);

        $request->session()->forget($this->kunci($shop));

        return redirect()->route('presensi.form', $shop->kode);
    }

    /**
     * Proses satu pemindaian kartu.
     *
     * Hasilnya selalu kembali ke halaman pemindai supaya operator langsung
     * bisa memindai kartu berikutnya tanpa membuka menu.
     */
    public function proses(Request $request, string $kode): RedirectResponse
    {
        $shop = $this->cariToko($kode);

        // Selalu redirect, tidak pernah render langsung: kalau sesi terkunci
        // pemindaian yang ditolak akan membuat tombol "Kembali" mengulang
        // POST-nya.
        if (! $shop->presensiSiap() || ! $this->terbuka($request, $shop)) {
            return redirect()
                ->route('presensi.form', $shop->kode)
                ->with('pesan', 'Login ke perangkat presensi dulu.');
        }

        $data = $request->validate([
            'token' => ['required', 'string', 'max:120'],
        ]);

        $employee = $this->qr->cariKaryawan(trim($data['token']));

        if ($employee === null) {
            $this->audit->catatGagal('presensi.scan', 'kode_tidak_dikenal', ['shop_id' => $shop->id]);

            return back()
                ->withInput(['token' => ''])
                ->with('galat', 'Kode kartu tidak dikenali. Pastikan kartu masih berlaku.');
        }

        // Kartu toko lain ditolak di sini, bukan nanti di service. Geofence
        // memakai koordinat toko, jadi tanpa pengecekan ini siapa pun bisa
        // absen di toko mana saja dengan kartunya.
        if ($employee->shop_id !== $shop->id) {
            $this->audit->catatGagal('presensi.scan', 'kartu_toko_lain', [
                'shop_id' => $shop->id,
                'employee_id' => $employee->id,
                'shop_pemilik_kartu' => $employee->shop_id,
            ]);

            return back()
                ->withInput(['token' => ''])
                ->with('galat', 'Kartu ini bukan milik toko ini.');
        }

        // Izin khusus perangkat presensi, diatur admin di form karyawan.
        // Ditempatkan setelah pemeriksaan kepemilikan toko supaya alasan
        // penolakan tidak membocorkan keberadaan karyawan toko lain.
        if (! $employee->boleh_presensi) {
            $this->audit->catatGagal('presensi.scan', 'karyawan_dilarang', [
                'shop_id' => $shop->id,
                'employee_id' => $employee->id,
            ]);

            return back()
                ->withInput(['token' => ''])
                ->with('galat', 'Karyawan ini tidak diizinkan absen lewat perangkat presensi. Hubungi admin toko.');
        }

        $this->sentuh($request, $shop);

        return $this->catatAbsensi($shop, $employee);
    }

    /** Catat masuk atau pulang sesuai sesi yang sedang terbuka. */
    private function catatAbsensi(Shop $shop, Employee $employee): RedirectResponse
    {
        $arah = $this->absen->arahBerikutnya($employee);

        try {
            $absensi = $arah === AbsenArah::Masuk
                ? $this->absen->absenMasuk(
                    employee: $employee,
                    latitude: null,
                    longitude: null,
                    metode: AbsenMethod::Presensi,
                    deviceId: 'presensi:'.$shop->kode,
                    pakaiLokasiToko: true,
                )
                : $this->absen->absenPulang(
                    employee: $employee,
                    latitude: null,
                    longitude: null,
                    metode: AbsenMethod::Presensi,
                    deviceId: 'presensi:'.$shop->kode,
                    pakaiLokasiToko: true,
                );
        } catch (AbsenException $e) {
            $this->audit->catatGagal('presensi.scan', $e->alasan(), [
                'shop_id' => $shop->id,
                'employee_id' => $employee->id,
                'arah' => $arah->value,
            ]);

            return back()
                ->withInput(['token' => ''])
                ->with('arah', $arah->value)
                ->with('galat', $e->getMessage());
        }

        $this->audit->catatAbsensi($absensi, $arah->value, $shop->kode);

        return back()
            ->withInput(['token' => ''])
            ->with('arah', $arah->value)
            ->with('sukses', $this->pesanSukses($employee, $absensi, $arah));
    }

    private function pesanSukses(Employee $employee, Attendance $absensi, AbsenArah $arah): string
    {
        $jam = match ($arah) {
            AbsenArah::Masuk => $absensi->jam_masuk?->format('H:i'),
            AbsenArah::Pulang => $absensi->jam_pulang?->format('H:i'),
        } ?? now()->format('H:i');

        if ($arah === AbsenArah::Pulang) {
            return sprintf('Terima kasih, %s. Absen pulang tercatat pukul %s.', $employee->nama, $jam);
        }

        return $absensi->status_masuk === AbsenMasukStatus::Terlambat
            ? sprintf('Absen masuk %s tercatat pukul %s. Tercatat terlambat.', $employee->nama, $jam)
            : sprintf('Absen masuk %s tercatat pukul %s. Tepat waktu.', $employee->nama, $jam);
    }

    /**
     * Toko dicari lewat `kode`, bukan id, karena kode itu yang dicetak ke URL
     * perangkat presensi. Toko yang tidak ada, nonaktif, atau belum punya
     * koordinat semuanya diberi 404 yang sama supaya tidak membocorkan isi
     * daftar.
     */
    private function cariToko(string $kode): Shop
    {
        $shop = Shop::query()->where('kode', $kode)->first();

        abort_if($shop === null, 404, 'Perangkat presensi tidak ditemukan. Periksa kembali tautannya.');

        abort_unless($shop->aktif, 404, 'Perangkat presensi ini sedang dinonaktifkan.');

        abort_unless(
            $shop->hasGeofence(),
            404,
            'Perangkat presensi ini belum bisa dipakai karena koordinat tokonya belum diisi.',
        );

        return $shop;
    }

    /** Kunci sesi, disimpan per toko. */
    private function kunci(Shop $shop): string
    {
        return 'presensi_terbuka.'.$shop->id;
    }

    /**
     * Sesi dianggap aktif kalau sudah login dan belum idle lewat
     * `absensi.presensi.idle_timeout`. Sesi yang basi langsung dibuang, jadi
     * perangkat presensi tidak bisa dipakai cuma karena tab perangkat masih
     * kebuka.
     */
    private function terbuka(Request $request, Shop $shop): bool
    {
        $terakhir = $request->session()->get($this->kunci($shop));

        if (! is_int($terakhir) || $terakhir <= 0) {
            return false;
        }

        if ($this->idleMenit() > 0 && (time() - $terakhir) >= $this->idleMenit() * 60) {
            $request->session()->forget($this->kunci($shop));

            return false;
        }

        return true;
    }

    /** Tandai perangkat presensi baru saja dipakai, jadi timer idle mulai dari sekarang. */
    private function sentuh(Request $request, Shop $shop): void
    {
        $request->session()->put($this->kunci($shop), time());
    }

    private function idleMenit(): int
    {
        return (int) config('absensi.presensi.idle_timeout', 30);
    }

    /** Sisa waktu sebelum perangkat presensi mengunci diri, untuk ditampilkan di layar. */
    private function menitSisa(Request $request, Shop $shop): int
    {
        if ($this->idleMenit() <= 0) {
            return 0;
        }

        $terakhir = (int) $request->session()->get($this->kunci($shop), time());

        return max(0, $this->idleMenit() - (int) floor((time() - $terakhir) / 60));
    }
}
