<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PayrollType;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Position;
use App\Models\ShiftTemplate;
use App\Models\Shop;
use App\Models\User;
use App\Services\QrService;
use App\Services\ShiftResolver;
use App\Support\CakupanToko;
use App\Support\Username;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class EmployeeController extends Controller
{
    public function __construct(
        private readonly CakupanToko $cakupan,
        private readonly ShiftResolver $shift,
    ) {}

    public function index(Request $request): View
    {
        $query = Employee::query()
            ->with([
                'shop',
                'position',
                'user',
                'shiftAssignments' => fn ($q) => $q->where('aktif', true)->with('template'),
            ])
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w
                ->where('nama', 'ilike', "%{$request->string('q')}%")
                ->orWhere('nip', 'ilike', "%{$request->string('q')}%")
                ->orWhere('telepon', 'ilike', "%{$request->string('q')}%")))
            ->when($request->filled('shop'), fn ($q) => $q->where('shop_id', $request->integer('shop')))
            ->when($request->filled('status'), fn ($q) => match ($request->string('status')->value) {
                'aktif' => $q->where('aktif', true),
                'nonaktif' => $q->where('aktif', false),
                default => $q,
            })
            ->orderBy('nama');

        $this->cakupan->batasi($query, $request->user());

        return view('admin.karyawan.index', [
            'karyawan' => $query->paginate(20)->withQueryString(),
            'toko' => $this->pilihanToko($request),
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.karyawan.form', [
            'karyawan' => new Employee(['tipe_payroll' => PayrollType::Harian, 'aktif' => true, 'boleh_presensi' => true, 'qr_version' => 1]),
            'toko' => $this->pilihanToko($request),
            'jabatan' => Position::orderBy('nama')->get(),
            'shift' => $this->pilihanShift(null),
            'roleKaryawan' => Role::findByName('karyawan', 'web'),
            // Formulir perlu tahu apakah jabatan terpilih mewajibkan template,
            // supaya memberi tahu kasir bahwa template tidak wajib diisi.
            'pakaiTemplateDefault' => $this->pakaiTemplateDefault($request),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validasi($request);

        $user = $this->buatAkun($request);

        $karyawan = Employee::create($data + [
            'user_id' => $user?->id,
            'qr_version' => 1,
        ]);

        $this->pasangShift($request, $karyawan);

        return redirect()
            ->route('admin.karyawan.index')
            ->with('sukses', 'Karyawan '.$data['nama'].' ditambahkan.');
    }

    public function edit(Request $request, Employee $karyawan): View
    {
        return view('admin.karyawan.form', [
            'karyawan' => $karyawan,
            'toko' => $this->pilihanToko($request),
            'jabatan' => Position::orderBy('nama')->get(),
            'shift' => $this->pilihanShift($karyawan->shop_id),
            'pakaiTemplateDefault' => $karyawan->position?->wajibTemplate() ?? true,
            'roleKaryawan' => Role::findByName('karyawan', 'web'),
        ]);
    }

    public function update(Request $request, Employee $karyawan): RedirectResponse
    {
        $data = $this->validasi($request, $karyawan);

        // Akun login yang belum ada bisa dibuat dari halaman ubah ini. Untuk
        // akun yang sudah ada, emailnya tetap mengikuti perubahan email karyawan.
        if ($karyawan->user) {
            if (isset($data['email'])) {
                $karyawan->user->update(['email' => $data['email']]);
            }
        } elseif ($request->filled('buat_akun')) {
            $karyawan->forceFill(['user_id' => $this->buatAkun($request)?->id]);
        }

        $karyawan->update($data);

        $this->pasangShift($request, $karyawan);

        return redirect()
            ->route('admin.karyawan.index')
            ->with('sukses', 'Karyawan '.$karyawan->nama.' diperbarui.');
    }

    public function destroy(Employee $karyawan): RedirectResponse
    {
        $karyawan->update(['aktif' => false, 'tanggal_keluar' => now()->toDateString()]);

        if ($karyawan->user) {
            $karyawan->user->update(['aktif' => false]);
        }

        return redirect()
            ->route('admin.karyawan.index')
            ->with('sukses', 'Karyawan '.$karyawan->nama.' dinonaktifkan. Riwayat absensi tetap tersimpan.');
    }

    /** Buat akun login untuk karyawan (opsional saat menyimpan). */
    public function buatAkun(Request $request): ?User
    {
        if (! $request->filled('buat_akun')) {
            return null;
        }

        $user = User::create([
            'name' => $request->string('nama')->value(),
            'email' => $request->string('email')->value(),
            'username' => (new Username)->dariEmail($request->string('email')->value()),
            'password' => Hash::make($request->string('password')->value()),
            'telepon' => $request->string('telepon')->value() ?: null,
            'aktif' => true,
        ]);

        $user->assignRole('karyawan');

        return $user;
    }

    public function resetPassword(Request $request, Employee $karyawan): RedirectResponse
    {
        abort_if($karyawan->user === null, 404);

        $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $karyawan->user->update(['password' => Hash::make($request->string('password')->value())]);

        return back()->with('sukses', 'Password '.$karyawan->nama.' sudah diganti.');
    }

    /** Naikkan versi QR sehingga semua kartu lama otomatis tidak berlaku. */
    public function rotasiQr(Employee $karyawan): RedirectResponse
    {
        $karyawan->rotateQr();

        return back()->with('sukses', 'Kartu QR '.$karyawan->nama.' sudah diganti. Kartu lama tidak berlaku lagi.');
    }

    public function qr(Request $request, Employee $karyawan, QrService $qr): View
    {
        // 404 bila karyawan di luar toko yang boleh diakses atasan ini.
        abort_unless(
            $this->cakupan->boleh($request->user(), $karyawan->shop_id),
            404,
        );

        return view('admin.karyawan.qr', [
            'karyawan' => $karyawan,
            'token' => $qr->token($karyawan),
            'qrSvg' => $qr->svg($karyawan, 360),
            'barcodeSvg' => $qr->barcodeSvg($karyawan),
        ]);
    }

    /** @return Collection<int, Shop> */
    private function pilihanToko(Request $request)
    {
        return $this->cakupan->semuaToko($request->user())
            ? Shop::orderBy('nama')->get()
            : $request->user()->shops()->orderBy('nama')->get();
    }

    /**
     * Template shift yang boleh dipilih: template global dan template toko
     * karyawan. Template toko lain tidak mungkin dipilih lewat form.
     */
    private function pilihanShift(?int $shopId)
    {
        return $this->shift->pilihanUntuk($shopId);
    }

    /**
     * Jabatan mana pun yang tidak mewajibkan template dianggap bebas memilih,
     * supaya kasir tidak diberi pesan "template wajib" sebelum jabatan dipilih.
     */
    private function pakaiTemplateDefault(Request $request): bool
    {
        $jabatanId = $request->integer('position_id');

        if ($jabatanId === 0) {
            return false;
        }

        return Position::whereKey($jabatanId)->value('pakai_template') ?? false;
    }

    /** Terapkan pilihan template shift dari form (boleh kosong). */
    private function pasangShift(Request $request, Employee $karyawan): void
    {
        $templateId = $request->filled('shift_template_id')
            ? (int) $request->string('shift_template_id')->value()
            : null;

        if ($templateId !== null) {
            $boleh = $this->shift->pilihanUntuk($karyawan->shop_id)
                ->contains(fn (ShiftTemplate $t) => $t->id === $templateId);

            abort_unless($boleh, 422, 'Template shift itu tidak tersedia untuk toko karyawan tersebut.');
        }

        $karyawan->pasangShift(
            $templateId,
            $request->filled('shift_mulai_berlaku')
                ? $request->string('shift_mulai_berlaku')->value()
                : null,
        );
    }

    /** @return array<string, mixed> */
    private function validasi(Request $request, ?Employee $karyawan = null): array
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:120'],
            'nip' => ['nullable', 'string', 'max:30', Rule::unique('employees', 'nip')->ignore($karyawan?->id)],
            'telepon' => ['nullable', 'string', 'max:30'],
            'email' => [
                // Wajib kalau sekalian membuat akun login.
                'nullable',
                'required_if:buat_akun,1',
                'email',
                'max:120',
                Rule::unique('employees', 'email')->ignore($karyawan?->id),
                // Akun login memakai email yang sama, jadi harus bebas dari tabel user.
                // Saat mengubah, email user lama ikut diperbarui oleh pemanggil.
                $karyawan === null ? Rule::unique('users', 'email') : Rule::unique('users', 'email')->ignore($karyawan->user_id),
            ],
            'shop_id' => ['required', Rule::exists('shops', 'id')],
            'position_id' => ['nullable', Rule::exists('positions', 'id')],
            'shift_template_id' => ['nullable', Rule::exists('shift_templates', 'id')],
            'shift_mulai_berlaku' => ['nullable', 'date'],
            'tanggal_masuk' => ['nullable', 'date'],
            'tipe_payroll' => ['required', Rule::enum(PayrollType::class)],
            'gaji_harian' => ['nullable', 'numeric', 'min:0'],
            'tarif_jam' => ['nullable', 'numeric', 'min:0'],
            'aktif' => ['nullable', 'boolean'],
            'boleh_presensi' => ['nullable', 'boolean'],
            'catatan' => ['nullable', 'string', 'max:255'],

            'buat_akun' => ['nullable', 'boolean'],
            'password' => ['nullable', 'required_if:buat_akun,1', 'string', 'min:8', 'confirmed'],
        ]);

        // Supervisor tidak boleh memindahkan karyawan ke luar tokonya.
        abort_unless(
            $this->cakupan->boleh($request->user(), (int) $validated['shop_id']),
            403,
            'Anda tidak berhak menugaskan karyawan ke toko tersebut.',
        );

        // Checkbox: tidak dikirim berarti tidak aktif.
        $validated['aktif'] = $request->boolean('aktif');
        $validated['boleh_presensi'] = $request->boolean('boleh_presensi');

        unset(
            $validated['buat_akun'],
            $validated['password'],
            $validated['shift_template_id'],
            $validated['shift_mulai_berlaku'],
        );

        return $validated;
    }
}
