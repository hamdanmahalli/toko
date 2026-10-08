<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StatusPerangkat;
use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Models\User;
use App\Models\UserDevice;
use App\Support\CakupanToko;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

/**
 * Manajemen akun login: peran, status aktif, dan penugasan toko.
 *
 * Tanpa halaman ini, tabel `user_shop` tidak pernah terisi, dan supervisor
 * yang belum ditugaskan tidak punya toko sama sekali. Karena itu halaman ini
 * menampilkan peringatan eksplisit untuk akun tanpa penugasan.
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
            // Disampaikan ke view supaya checkbox toko dimatikan untuk akun
            // yang perannya sudah memberi akses ke seluruh toko.
            'peranGlobal' => (array) config('absensi.peran_toko_global', ['pemilik']),
            'tanpaPenugasan' => fn (User $u) => $this->cakupan->tanpaPenugasan($u),
        ]);
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

    /** @param  array<int, string>  $peran */
    private function adalahPeranGlobal(array $peran): bool
    {
        return array_intersect($peran, (array) config('absensi.peran_toko_global', ['pemilik'])) !== [];
    }
}
