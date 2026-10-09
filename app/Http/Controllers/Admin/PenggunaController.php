<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StatusPerangkat;
use App\Http\Controllers\Controller;
use App\Mail\KirimAkunBaru;
use App\Models\Employee;
use App\Models\Shop;
use App\Models\User;
use App\Models\UserDevice;
use App\Support\CakupanToko;
use App\Support\Username;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;
use Throwable;

/**
 * Manajemen akun login: peran, status aktif, dan penugasan toko. Akun dibuat
 * sepenuhnya dari halaman ini oleh admin.
 *
 * Tabel `user_shop` diatur lewat halaman ini; akun yang tertaut data karyawan
 * juga otomatis mewarisi toko karyawannya, sehingga supervisor yang belum
 * ditugaskan tetap perlu diatur manual di sini (kalau tidak, ia melihat nol
 * toko). Karena itu halaman ini menampilkan peringatan eksplisit untuk akun
 * tanpa penugasan.
 */
class PenggunaController extends Controller
{
    public function __construct(private readonly CakupanToko $cakupan) {}

    public function index(Request $request): View
    {
        $pengguna = User::query()
            ->with(['roles', 'shops', 'employee', 'devices'])
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'ilike', "%{$request->string('q')}%")
                ->orWhere('email', 'ilike', "%{$request->string('q')}%")
                ->orWhere('username', 'ilike', "%{$request->string('q')}%")))
            ->when($request->filled('peran'), fn ($q) => $q->whereHas(
                'roles',
                fn ($r) => $r->where('name', $request->string('peran')->value),
            ))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.pengguna.index', [
            'pengguna' => $pengguna,
            'peran' => $this->pilihanPeran($request),
            'toko' => $this->pilihanToko($request),
            // Karyawan yang boleh dibuatkan akun: aktif, sudah punya email,
            // dan belum tertaut ke akun mana pun.
            'tanpaAkun' => $this->karyawanTanpaAkun($request),
            // Disampaikan ke view supaya checkbox toko dimatikan untuk akun
            // yang perannya sudah memberi akses ke seluruh toko.
            'peranGlobal' => (array) config('absensi.peran_toko_global', ['pemilik']),
            'tanpaPenugasan' => fn (User $u) => $this->cakupan->tanpaPenugasan($u),
        ]);
    }

    /**
     * Buat akun login dari halaman Pengguna. Dipakai dua cara: menautkan akun
     * ke data karyawan lewat NIP (email diambil dari data karyawan), atau
     * membuat akun bebas tanpa data karyawan (mis. supervisor).
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'mode' => ['required', Rule::in(['karyawan', 'bebas'])],
            'nip' => ['nullable', 'string', 'max:30', 'required_if:mode,karyawan'],
            'nama' => ['nullable', 'string', 'max:120', 'required_if:mode,bebas'],
            'email' => ['nullable', 'email', 'max:120', 'required_if:mode,bebas', Rule::unique('users', 'email')],
            'peran' => ['required', 'array', 'min:1'],
            'peran.*' => ['string', Rule::exists('roles', 'name')->where('guard_name', 'web')],
        ]);

        // Peran di luar wewenang admin tidak boleh dipaksa lewat POST langsung.
        $peranDiizinkan = $this->pilihanPeran($request)->pluck('name')->all();

        if (array_diff($data['peran'], $peranDiizinkan) !== []) {
            return back()->with('galat', 'Anda tidak berhak memberikan salah satu peran yang dipilih.');
        }

        if ($data['mode'] === 'karyawan') {
            $karyawan = Employee::query()
                ->where('nip', $data['nip'])
                ->whereNull('user_id')
                ->where('aktif', true)
                ->first();

            if (! $karyawan) {
                return back()->with('galat', 'Karyawan dengan nomor ID tersebut tidak ditemukan, nonaktif, atau sudah punya akun.');
            }

            if (blank($karyawan->email)) {
                return back()->with('galat', 'Karyawan '.$karyawan->nama.' belum punya email. Isi email di data karyawan dulu.');
            }

            // Supervisor hanya boleh membuat akun untuk tokonya sendiri.
            abort_unless(
                $this->cakupan->boleh($request->user(), $karyawan->shop_id),
                403,
                'Anda tidak berhak membuat akun untuk karyawan toko tersebut.',
            );

            // Email karyawan bisa saja sudah dipakai akun lain (mis. akun bebas).
            if (User::where('email', $karyawan->email)->exists()) {
                return back()->with('galat', 'Email '.$karyawan->email.' sudah dipakai akun lain.');
            }
        } else {
            $karyawan = null;
        }

        $nama = $karyawan?->nama ?? $data['nama'];
        $email = $karyawan?->email ?? $data['email'];
        $username = (new Username)->dariEmail($email);
        $passwordAwal = Str::random(12);

        try {
            // Satu transaksi: kalau email gagal terkirim, akun + tautan ke
            // karyawan dibatalkan supaya tidak ada akun tanpa password diketahui.
            DB::transaction(function () use ($data, $karyawan, $nama, $email, $username, $passwordAwal) {
                $akun = User::create([
                    'name' => $nama,
                    'email' => $email,
                    'username' => $username,
                    'password' => Hash::make($passwordAwal),
                    'telepon' => $karyawan?->telepon,
                    'aktif' => true,
                ]);

                $akun->syncRoles($data['peran']);

                if ($karyawan) {
                    $karyawan->forceFill(['user_id' => $akun->id])->save();

                    // Akun tertaut mewarisi toko karyawannya, kecuali peran
                    // global (pemilik) yang memang sudah boleh semua toko.
                    if (! $this->adalahPeranGlobal($data['peran'])) {
                        $akun->sertakanToko($karyawan->shop_id);
                    }
                }

                Mail::to($email)->send(new KirimAkunBaru($akun, $passwordAwal));
            });
        } catch (Throwable $e) {
            report($e);

            return back()->with('galat', 'Akun gagal dibuat karena email belum bisa dikirim. Coba lagi.');
        }

        return back()->with('sukses', 'Akun '.$nama.' berhasil dibuat. Password awal dikirim ke '.$email.'.');
    }

    public function update(Request $request, User $pengguna): RedirectResponse
    {
        $data = $request->validate([
            'peran' => ['required', 'array', 'min:1'],
            'peran.*' => ['string', Rule::exists('roles', 'name')->where('guard_name', 'web')],
            'toko' => ['nullable', 'array'],
            'toko.*' => ['integer', Rule::exists('shops', 'id')],
            'aktif' => ['nullable', 'boolean'],
            'bebas_perangkat' => ['nullable', 'boolean'],
        ]);

        // Menonaktifkan diri sendiri mengunci akun sendiri dari halaman ini.
        if ($pengguna->is($request->user()) && ! ($data['aktif'] ?? false)) {
            return back()->with('galat', 'Tidak bisa menonaktifkan akun Anda sendiri.');
        }

        // Peran global sudah boleh semua toko, jadi daftar toko tidak berarti apa-apa.
        if ($this->adalahPeranGlobal($data['peran'])) {
            $data['toko'] = [];
        } else {
            // Toko dari data karyawan tidak boleh hilang hanya karena admin
            // tidak mencentangnya lagi (checkbox-nya memang dimatikan di form).
            $data['toko'] = array_values(array_unique(array_merge(
                $data['toko'] ?? [],
                array_filter([$pengguna->employee?->shop_id]),
            )));
        }

        DB::transaction(function () use ($pengguna, $data) {
            $pengguna->syncRoles($data['peran']);
            $pengguna->shops()->sync($data['toko'] ?? []);
            $pengguna->forceFill([
                'aktif' => (bool) ($data['aktif'] ?? false),
                'bebas_perangkat' => (bool) ($data['bebas_perangkat'] ?? false),
            ])->save();

            // Ganti peran atau toko = hak akses berubah total, jadi sessi lama
            // (termasuk active_session_id) tidak boleh tetap dipakai.
            $pengguna->forceFill(['active_session_id' => null])->save();
        });

        return back()->with('sukses', 'Akun '.$pengguna->name.' berhasil diperbarui.');
    }

    /**
     * Setujui/tolak/hapus perangkat akun. Semuanya butuh `perangkat.kelola`,
     * dan target harus benar-benar milik akun yang disebut di URL.
     */
    public function perangkatSetujui(User $pengguna, UserDevice $perangkat): RedirectResponse
    {
        abort_unless($perangkat->user_id === $pengguna->id, 404);
        $perangkat->update(['status' => StatusPerangkat::Disetujui, 'last_seen_at' => now()]);

        return back()->with('sukses', 'Perangkat '.$pengguna->name.' diizinkan untuk login.');
    }

    public function perangkatTolak(User $pengguna, UserDevice $perangkat): RedirectResponse
    {
        abort_unless($perangkat->user_id === $pengguna->id, 404);
        $perangkat->update(['status' => StatusPerangkat::Ditolak]);

        return back()->with('sukses', 'Perangkat '.$pengguna->name.' ditolak untuk login.');
    }

    public function perangkatHapus(User $pengguna, UserDevice $perangkat): RedirectResponse
    {
        abort_unless($perangkat->user_id === $pengguna->id, 404);
        $perangkat->delete();

        return back()->with('sukses', 'Riwayat perangkat '.$pengguna->name.' dihapus.');
    }

    /**
     * Admin hanya boleh menugaskan toko yang boleh diaawasi sendiri, jadi
     * checkbox toko di luar haknya tidak akan muncul sebagai pilihan.
     *
     * @return Collection<int, Shop>
     */
    private function pilihanToko(Request $request)
    {
        return Shop::query()
            ->when(! $this->cakupan->semuaToko($request->user()), fn ($q) => $q->whereIn('id', $this->cakupan->idToko($request->user())))
            ->orderBy('nama')
            ->get(['id', 'nama', 'kode']);
    }

    /**
     * @return Collection<int, Role>
     */
    private function pilihanPeran(Request $request)
    {
        return Role::query()
            ->when(
                // Menyekat admin dari mengubah peran miliknya sendiri supaya
                // tidak terkunci dari halaman ini tanpa sadar.
                ! $request->user()->can('peran.kelola'),
                fn ($q) => $q->whereNotIn('name', ['pemilik']),
            )
            ->orderBy('name')
            ->get(['name']);
    }

    /**
     * Kandidat akun dari data karyawan: masih aktif, sudah punya email, dan
     * belum tertaut akun. Dibatasai toko yang boleh diakses admin pembuat.
     *
     * @return Collection<int, Employee>
     */
    private function karyawanTanpaAkun(Request $request)
    {
        return Employee::query()
            ->with('shop')
            ->whereNull('user_id')
            ->where('aktif', true)
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->when(
                ! $this->cakupan->semuaToko($request->user()),
                fn ($q) => $q->whereIn('shop_id', $this->cakupan->idToko($request->user())),
            )
            ->orderBy('nama')
            ->get();
    }

    /** @param  array<int, string>  $peran */
    private function adalahPeranGlobal(array $peran): bool
    {
        return array_intersect($peran, (array) config('absensi.peran_toko_global', ['pemilik'])) !== [];
    }
}
