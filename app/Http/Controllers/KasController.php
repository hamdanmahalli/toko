<?php

namespace App\Http\Controllers;

use App\Enums\JenisKas;
use App\Enums\KategoriKas;
use App\Exports\KasLaporanExport;
use App\Models\CashBook;
use App\Models\Employee;
use App\Services\KasLaporanService;
use App\Support\Rupiah;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

/**
 * Buku kas pribadi karyawan: banyak buku per akun, catat pemasukan/pengeluaran
 * dengan kategori tetap, dan laporan yang bisa diekspor ke PDF/Excel.
 *
 * Karyawan hanya menyentuh buku miliknya sendiri. Buku karyawan lain dibuat
 * "tidak ditemukan" (404), bukan 403, supaya keberadaannya tidak terbocor.
 */
class KasController extends Controller
{
    public function __construct(private KasLaporanService $laporan) {}

    public function index(Request $request): View
    {
        $employee = $this->employee($request);

        $buku = CashBook::query()
            ->where('employee_id', $employee->id)
            ->withSum(['transactions as total_masuk' => fn ($q) => $q->where('jenis', JenisKas::Masuk->value)], 'jumlah')
            ->withSum(['transactions as total_keluar' => fn ($q) => $q->where('jenis', JenisKas::Keluar->value)], 'jumlah')
            ->orderByDesc('aktif')
            ->orderBy('nama')
            ->get();

        return view('kas.index', [
            'employee' => $employee,
            'buku' => $buku,
            'totalSaldo' => $buku->sum(fn (CashBook $b) => $b->saldoSaatIni()),
        ]);
    }

    public function create(Request $request): View
    {
        return view('kas.form', [
            'employee' => $this->employee($request),
            'buku' => new CashBook(['aktif' => true]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $employee = $this->employee($request);

        $request->merge(['saldo_awal' => $this->angka($request->input('saldo_awal'))]);
        $data = $this->validasiBuku($request);

        $buku = $employee->cashBooks()->create($data);

        return redirect()
            ->route('kas.show', $buku)
            ->with('sukses', 'Buku kas berhasil dibuat.');
    }

    public function show(Request $request, int $buku): View
    {
        $employee = $this->employee($request);
        $buku = $this->milik($employee, $buku);

        $masuk = $buku->transactions()->where('jenis', JenisKas::Masuk->value)->sum('jumlah');
        $keluar = $buku->transactions()->where('jenis', JenisKas::Keluar->value)->sum('jumlah');

        return view('kas.show', [
            'employee' => $employee,
            'buku' => $buku,
            'transaksi' => $buku->transactions()
                ->orderByDesc('tanggal')
                ->orderByDesc('id')
                ->limit(200)
                ->get(),
            'ringkasan' => [
                'masuk' => (float) $masuk,
                'keluar' => (float) $keluar,
                'saldo' => (float) $buku->saldo_awal + (float) $masuk - (float) $keluar,
            ],
            'kategori' => KategoriKas::options(),
        ]);
    }

    public function edit(Request $request, int $buku): View
    {
        $employee = $this->employee($request);

        return view('kas.form', [
            'employee' => $employee,
            'buku' => $this->milik($employee, $buku),
        ]);
    }

    public function update(Request $request, int $buku): RedirectResponse
    {
        $employee = $this->employee($request);
        $buku = $this->milik($employee, $buku);

        $request->merge(['saldo_awal' => $this->angka($request->input('saldo_awal'))]);
        $buku->update($this->validasiBuku($request));

        return redirect()
            ->route('kas.show', $buku)
            ->with('sukses', 'Buku kas berhasil diperbarui.');
    }

    public function destroy(Request $request, int $buku): RedirectResponse
    {
        $employee = $this->employee($request);
        $this->milik($employee, $buku)->delete();

        return redirect()
            ->route('kas.index')
            ->with('sukses', 'Buku kas beserta transaksinya berhasil dihapus.');
    }

    public function storeTransaksi(Request $request, int $buku): RedirectResponse
    {
        $employee = $this->employee($request);
        $buku = $this->milik($employee, $buku);

        $request->merge(['jumlah' => $this->angka($request->input('jumlah'))]);

        $data = $request->validate([
            'kategori' => ['required', Rule::enum(KategoriKas::class)],
            'tanggal' => ['required', 'date'],
            'jumlah' => ['required', 'numeric', 'min:1'],
            'keterangan' => ['nullable', 'string', 'max:500'],
        ], [], ['jumlah' => 'nominal']);

        $kategori = KategoriKas::from($data['kategori']);

        $buku->transactions()->create([
            'jenis' => $kategori->jenis()->value,
            'kategori' => $kategori->value,
            'tanggal' => $data['tanggal'],
            'jumlah' => $data['jumlah'],
            'keterangan' => $data['keterangan'] ?? null,
        ]);

        return redirect()
            ->route('kas.show', $buku)
            ->with('sukses', $kategori->jenis()->label().' '.Rupiah::format($data['jumlah']).' tercatat.');
    }

    public function destroyTransaksi(Request $request, int $buku, int $transaksi): RedirectResponse
    {
        $employee = $this->employee($request);
        $buku = $this->milik($employee, $buku);

        $buku->transactions()->whereKey($transaksi)->delete();

        return redirect()
            ->route('kas.show', $buku)
            ->with('sukses', 'Transaksi berhasil dihapus.');
    }

    public function laporan(Request $request): View
    {
        return view('kas.laporan', $this->dataLaporan($request));
    }

    public function laporanPdf(Request $request): Response
    {
        $data = $this->dataLaporan($request);

        return Pdf::loadView('kas.laporan-pdf', $data)
            ->setPaper('a4', 'portrait')
            ->download('laporan-kas-'.$data['dari']->toDateString().'.pdf');
    }

    public function laporanExcel(Request $request): Response
    {
        $data = $this->dataLaporan($request);

        return Excel::download(
            new KasLaporanExport($data['rekap']['baris']),
            'laporan-kas-'.$data['dari']->toDateString().'.xlsx',
        );
    }

    /**
     * Siapkan data laporan untuk layar maupun ekspor, memakai filter yang sama
     * supaya angka di PDF/Excel persis seperti yang terlihat di halaman.
     *
     * @return array<string, mixed>
     */
    private function dataLaporan(Request $request): array
    {
        $employee = $this->employee($request);

        $dari = Carbon::parse($request->input('dari') ?: now()->startOfMonth()->toDateString())->startOfDay();
        $sampai = Carbon::parse($request->input('sampai') ?: now()->toDateString())->startOfDay();

        if ($dari->greaterThan($sampai)) {
            [$dari, $sampai] = [$sampai, $dari];
        }

        $bukuId = $request->filled('buku') ? (int) $request->input('buku') : null;

        if ($bukuId !== null && ! $employee->cashBooks()->whereKey($bukuId)->exists()) {
            $bukuId = null;
        }

        $rekap = $this->laporan->rekap($employee, $bukuId, $dari, $sampai);

        return [
            'employee' => $employee,
            'dari' => $dari,
            'sampai' => $sampai,
            'bukuId' => $bukuId,
            'buku' => $rekap['buku'],
            'rekap' => $rekap,
        ];
    }

    /** @return array<string, mixed> */
    private function validasiBuku(Request $request): array
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:120'],
            'keterangan' => ['nullable', 'string', 'max:500'],
            'saldo_awal' => ['required', 'numeric', 'min:0'],
            'aktif' => ['nullable', 'boolean'],
        ]);

        $data['aktif'] = $request->boolean('aktif', true);

        return $data;
    }

    private function milik(Employee $employee, int $buku): CashBook
    {
        return $employee->cashBooks()->whereKey($buku)->firstOrFail();
    }

    /** Buang titik/spasi/prefix "Rp" supaya "Rp 50.000" tersimpan sebagai 50000. */
    private function angka(mixed $nilai): string
    {
        return preg_replace('/\D/', '', (string) $nilai) ?? '';
    }

    private function employee(Request $request): Employee
    {
        $employee = Employee::query()->where('user_id', $request->user()->getKey())->first();

        abort_if($employee === null, 403, 'Akun ini belum tertaut ke data karyawan.');

        return $employee;
    }
}
