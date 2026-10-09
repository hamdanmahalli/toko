<?php

namespace App\Http\Controllers;

use App\Enums\LeaveType;
use App\Enums\RequestStatus;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\OvertimeRequest;
use App\Models\User;
use App\Services\FcmService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Pengajuan yang dibuat karyawan sendiri: izin, sakit, cuti, dinas, dan lembur.
 *
 * Karyawan hanya boleh melihat serta membatalkan pengajuannya sendiri.
 * Persetujuan ditangani modul admin.
 */
class PengajuanController extends Controller
{
    public function index(Request $request): View
    {
        $employee = $this->employee($request);

        return view('pengajuan.index', [
            'employee' => $employee,
            'izin' => LeaveRequest::query()
                ->where('employee_id', $employee->id)
                ->orderByDesc('tanggal_mulai')
                ->orderByDesc('id')
                ->limit(50)
                ->get(),
            'lembur' => OvertimeRequest::query()
                ->where('employee_id', $employee->id)
                ->orderByDesc('tanggal')
                ->orderByDesc('id')
                ->limit(50)
                ->get(),
        ]);
    }

    public function formIzin(Request $request): View
    {
        return view('pengajuan.izin', [
            'employee' => $this->employee($request),
            'jenis' => LeaveType::options(),
        ]);
    }

    public function simpanIzin(Request $request): RedirectResponse
    {
        $employee = $this->employee($request);

        $data = $request->validate([
            'jenis' => ['required', Rule::enum(LeaveType::class)],
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_selesai' => ['required', 'date', 'after_or_equal:tanggal_mulai'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
        ]);

        $mulai = Carbon::parse($data['tanggal_mulai'])->startOfDay();
        $selesai = Carbon::parse($data['tanggal_selesai'])->startOfDay();

        // Batasi rentang supaya salah input tanggal tidak jadi cuti setahun.
        if ($mulai->diffInDays($selesai) > 90) {
            return back()
                ->withInput()
                ->with('galat', 'Rentang pengajuan maksimal 90 hari.');
        }

        LeaveRequest::create([
            'employee_id' => $employee->id,
            'jenis' => $data['jenis'],
            'tanggal_mulai' => $mulai->toDateString(),
            'tanggal_selesai' => $selesai->toDateString(),
            'jumlah_hari' => $mulai->diffInDays($selesai) + 1,
            'keterangan' => $data['keterangan'] ?? null,
            'status' => RequestStatus::Pending,
        ]);

        $this->beriTahuAtasan(
            'Pengajuan '.$data['jenis'].' baru',
            $employee->nama.' mengajukan '.$data['jenis'].' tanggal '.$mulai->format('d/m/Y').'.',
        );

        return redirect()
            ->route('pengajuan.index')
            ->with('sukses', 'Pengajuan '.$data['jenis'].' terkirim dan menunggu persetujuan atasan.');
    }

    public function formLembur(Request $request): View
    {
        return view('pengajuan.lembur', [
            'employee' => $this->employee($request),
            'tarifDefault' => $this->employee($request)->tarif_jam,
        ]);
    }

    public function simpanLembur(Request $request): RedirectResponse
    {
        $employee = $this->employee($request);

        $data = $request->validate([
            'tanggal' => ['required', 'date'],
            'jam_mulai' => ['required', 'date_format:H:i'],
            'jam_selesai' => ['required', 'date_format:H:i'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
        ]);

        $durasi = $this->durasiLembur($data['jam_mulai'], $data['jam_selesai']);

        if ($durasi === null) {
            return back()
                ->withInput()
                ->with('galat', 'Jam selesai lembur harus setelah jam mulai.');
        }

        if ($durasi > 12) {
            return back()
                ->withInput()
                ->with('galat', 'Durasi lembur maksimal 12 jam.');
        }

        $tarif = $employee->tarif_jam;

        OvertimeRequest::create([
            'employee_id' => $employee->id,
            'tanggal' => $data['tanggal'],
            'jam_mulai' => $data['jam_mulai'],
            'jam_selesai' => $data['jam_selesai'],
            'durasi_jam' => $durasi,
            'tarif_per_jam' => $tarif,
            'total_lembur' => $tarif === null ? 0 : round($durasi * (float) $tarif, 2),
            'keterangan' => $data['keterangan'] ?? null,
            'status' => RequestStatus::Pending,
        ]);

        $this->beriTahuAtasan(
            'Pengajuan lembur baru',
            $employee->nama.' mengajukan lembur '.$durasi.' jam pada '.Carbon::parse($data['tanggal'])->format('d/m/Y').'.',
        );

        return redirect()
            ->route('pengajuan.index')
            ->with('sukses', 'Pengajuan lembur terkirim dan menunggu persetujuan atasan.');
    }

    /** Batalkan pengajuan sendiri selama masih menunggu. */
    public function batal(Request $request, string $jenis, int $id): RedirectResponse
    {
        $employee = $this->employee($request);

        $pengajuan = $this->cariMilik($jenis, $id, $employee);

        // Sudah diproses atau dibatalkan: pesan saja, bukan halaman error.
        if (! $pengajuan->status->isPending()) {
            return back()->with(
                'galat',
                'Pengajuan ini sudah diproses menjadi "'.$pengajuan->status->label().'".',
            );
        }

        $pengajuan->update(['status' => RequestStatus::Cancelled]);

        return back()->with('sukses', 'Pengajuan dibatalkan.');
    }

    /** @return LeaveRequest|OvertimeRequest */
    private function cariMilik(string $jenis, int $id, Employee $employee)
    {
        $model = $jenis === 'lembur' ? OvertimeRequest::class : LeaveRequest::class;

        $pengajuan = $model::query()
            ->where('employee_id', $employee->id)
            ->find($id);

        abort_if($pengajuan === null, 404);

        return $pengajuan;
    }

    /**
     * Durasi lembur dalam jam; jam selesai yang lebih kecil dianggap lewat
     * tengah malam. Null bila jam selesai sama dengan jam mulai.
     */
    private function durasiLembur(string $mulai, string $selesai): ?float
    {
        $awal = Carbon::createFromTimeString($mulai);
        $akhir = Carbon::createFromTimeString($selesai);

        if ($akhir->lessThan($awal)) {
            $akhir->addDay();
        }

        $selisih = $awal->diffInMinutes($akhir, false);

        return $selisih > 0 ? round($selisih / 60, 2) : null;
    }

    private function employee(Request $request): Employee
    {
        $employee = Employee::query()->where('user_id', $request->user()->getKey())->first();

        abort_if($employee === null, 403, 'Akun ini belum tertaut ke data karyawan.');

        return $employee;
    }

    /**
     * Kabari semua akun yang berhak menyetujui pengajuan lewat notifikasi APK.
     * Kegagalan pengiriman tidak boleh menggagalkan pengajuan karyawan.
     */
    private function beriTahuAtasan(string $judul, string $pesan): void
    {
        try {
            $ids = User::query()->permission('pengajuan.setujui')->pluck('id')->all();

            if ($ids === []) {
                return;
            }

            app(FcmService::class)->kirimKeUsers($ids, $judul, $pesan, [
                'jenis' => 'pengajuan',
                'url' => route('admin.pengajuan.index'),
            ]);
        } catch (\Throwable $e) {
            // Sengaja diabaikan: notifikasi bersifat pelengkap.
        }
    }
}
