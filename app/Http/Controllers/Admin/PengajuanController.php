<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RequestStatus;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\OvertimeRequest;
use App\Models\Shop;
use App\Support\CakupanToko;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Persetujuan pengajuan izin/cuti dan lembur.
 *
 * Atasan hanya boleh memproses pengajuan karyawan di toko yang diawasi.
 * Pengajuan yang sudah diproses tidak bisa diubah statusnya lagi.
 */
class PengajuanController extends Controller
{
    private const PER_HALAMAN = 20;

    public function __construct(private readonly CakupanToko $cakupan) {}

    public function index(Request $request): View
    {
        $izin = $this->queryIzin($request);
        $lembur = $this->queryLembur($request);

        // Dihitung dari klon supaya filter status tidak ikut menempel ke query
        // yang nanti dipaginate.
        $jumlahPending = (clone $izin)->where('status', RequestStatus::Pending->value)->count()
            + (clone $lembur)->where('status', RequestStatus::Pending->value)->count();

        return view('admin.pengajuan.index', [
            // Dua tabel dipaginasi terpisah supaya mengurutkan izin tidak
            // membuat halaman lembur ikut terpotong.
            'izin' => $izin->paginate(self::PER_HALAMAN, ['*'], 'page_izin')->withQueryString(),
            'lembur' => $lembur->paginate(self::PER_HALAMAN, ['*'], 'page_lembur')->withQueryString(),
            'jumlahPending' => $jumlahPending,
            'status' => RequestStatus::options(),
            'toko' => $this->pilihanToko($request),
        ]);
    }

    /** Toko yang boleh offered sebagai filter, mengikuti cakupan akses atasan. */
    private function pilihanToko(Request $request)
    {
        return Shop::query()
            ->when(! $this->cakupan->semuaToko($request->user()), fn ($q) => $q->whereIn('id', $this->cakupan->idToko($request->user())))
            ->orderBy('nama')
            ->get(['id', 'nama']);
    }

    public function setujui(Request $request, string $jenis, int $id): RedirectResponse
    {
        return $this->tinjau($request, $jenis, $id, RequestStatus::Approved);
    }

    public function tolak(Request $request, string $jenis, int $id): RedirectResponse
    {
        return $this->tinjau($request, $jenis, $id, RequestStatus::Rejected);
    }

    private function tinjau(Request $request, string $jenis, int $id, RequestStatus $status): RedirectResponse
    {
        $pengajuan = $this->cari($request, $jenis, $id);

        // Double submit atau halaman basi: statusnya sudah berubah.
        // Redirect dengan pesan, bukan 422, supaya atasan tidak melihat halaman error.
        if (! $pengajuan->status->isPending()) {
            return back()->with(
                'galat',
                'Pengajuan ini sudah diproses menjadi "'.$pengajuan->status->label().'".',
            );
        }

        // Menolak wajib menyertakan alasan supaya karyawan tahu apa yang diperbaiki.
        $catatan = $status->isApproved()
            ? $request->validate([
                'catatan' => ['nullable', 'string', 'max:500'],
            ])['catatan'] ?? null
            : $request->validate([
                'catatan' => ['required', 'string', 'max:500'],
            ])['catatan'];

        $pengajuan->update([
            'status' => $status,
            'reviewed_by' => $request->user()->getKey(),
            'reviewed_at' => now(),
            'catatan_reviewer' => $catatan ?: null,
        ]);

        $kata = $status->isApproved() ? 'disetujui' : 'ditolak';

        return back()->with('sukses', 'Pengajuan '.$jenis.' '.$pengajuan->employee->nama.' berhasil '.$kata.'.');
    }

    /** @return Builder<LeaveRequest> */
    private function queryIzin(Request $request)
    {
        $query = LeaveRequest::query()->with(['employee.shop', 'reviewer']);

        // Pengajuan tidak punya kolom shop_id sendiri, jadi cakupan lewat relasi.
        if (! $this->cakupan->semuaToko($request->user())) {
            $query->whereIn('employee_id', Employee::query()
                ->select('id')
                ->whereIn('shop_id', $this->cakupan->idToko($request->user())));
        }

        return $this->filter($query, $request);
    }

    /** @return Builder<OvertimeRequest> */
    private function queryLembur(Request $request)
    {
        $query = OvertimeRequest::query()->with(['employee.shop', 'reviewer']);

        if (! $this->cakupan->semuaToko($request->user())) {
            $query->whereIn('employee_id', Employee::query()
                ->select('id')
                ->whereIn('shop_id', $this->cakupan->idToko($request->user())));
        }

        return $this->filter($query, $request);
    }

    /**
     * @param  Builder<LeaveRequest|OvertimeRequest>  $query
     * @return Builder<LeaveRequest|OvertimeRequest>
     */
    private function filter($query, Request $request)
    {
        return $query
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')->value))
            ->when($request->filled('shop'), fn ($q) => $q->whereHas(
                'employee',
                fn ($e) => $e->where('shop_id', $request->integer('shop')),
            ))
            ->when($request->filled('q'), fn ($q) => $q->whereHas(
                'employee',
                fn ($e) => $e->where(fn ($w) => $w
                    ->where('nama', 'ilike', "%{$request->string('q')}%")
                    ->orWhere('nip', 'ilike', "%{$request->string('q')}%")),
            ))
            ->when($request->boolean('hanya_pending'), fn ($q) => $q->where('status', RequestStatus::Pending->value))
            ->latest('id');
    }

    /** @return LeaveRequest|OvertimeRequest */
    private function cari(Request $request, string $jenis, int $id)
    {
        $model = $jenis === 'lembur' ? OvertimeRequest::class : LeaveRequest::class;

        $pengajuan = $model::query()->with('employee')->find($id);

        abort_if($pengajuan === null, 404);

        // 404 (bukan 403) supaya keberadaan pengajuan toko lain tidak terkonfirmasi.
        abort_unless($this->cakupan->boleh($request->user(), $pengajuan->employee->shop_id), 404);

        return $pengajuan;
    }
}
