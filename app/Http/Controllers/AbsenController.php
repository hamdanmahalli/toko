<?php

namespace App\Http\Controllers;

use App\Enums\AbsenGagalReason;
use App\Enums\AbsenMasukStatus;
use App\Enums\AbsenMethod;
use App\Enums\RequestStatus;
use App\Exceptions\AbsenException;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\OvertimeRequest;
use App\Models\User;
use App\Services\AbsenService;
use App\Services\QrService;
use App\Services\ShiftService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class AbsenController extends Controller
{
    public function __construct(
        private readonly AbsenService $absen,
        private readonly ShiftService $shift,
        private readonly QrService $qr,
    ) {}

    /** Halaman utama: beranda karyawan, atau dasbor admin untuk atasan. */
    public function beranda(Request $request): View|RedirectResponse
    {
        $employee = $this->employeeFor($request);

        if ($employee === null) {
            // Atasan (pemilik/supervisor) langsung diarahkan ke dasbor admin.
            if ($this->bisaAksesAdmin($request->user())) {
                return redirect()->route('admin.dashboard');
            }

            abort(403, 'Akun ini belum tertaut ke data karyawan. Hubungi pemilik aplikasi.');
        }

        $sekarang = Carbon::now();

        $riwayat = $employee->attendances()
            ->antara($sekarang->copy()->subDays(13)->toDateString(), $sekarang->toDateString())
            ->orderByDesc('tanggal')
            ->orderByDesc('sesi')
            ->get();

        $bulanIni = $employee->attendances()
            ->whereDate('tanggal', '>=', $sekarang->copy()->startOfMonth()->toDateString())
            ->orderBy('tanggal')
            ->orderBy('sesi')
            ->get();

        $absensiHariIni = $employee->attendances()
            ->whereDate('tanggal', $sekarang->toDateString())
            ->orderByDesc('sesi')
            ->get();

        // Shift interval punya beberapa sesi sehari. Tombol absen harus
        // mengikuti sesi terakhir yang masih tersimpan, bukan baris pertama
        // yang kebetulan dikembalikan database.
        $hariIni = $absensiHariIni->first();

        return view('dashboard.karyawan', [
            'employee' => $employee,
            'slot' => $this->shift->slotUntuk($employee, $sekarang),
            'pakaiTemplate' => $this->shift->templateUntuk($employee, $sekarang) !== null,
            'hariIni' => $hariIni,
            'sesiHariIni' => $absensiHariIni,
            // Semua sesi hari ini sudah ditutup, bukan cuma sesi terakhir.
            'semuaSesiTutup' => $absensiHariIni->isNotEmpty()
                && $absensiHariIni->every(fn (Attendance $a) => $a->jam_pulang !== null),
            'riwayat' => $riwayat,
            'statistik' => [
                // Dihitung dari tanggal unik: satu hari dengan tiga sesi tetap
                // dihitung sebagai satu hari hadir.
                'hari' => $bulanIni->map(fn (Attendance $a) => $a->tanggal->toDateString())->unique()->count(),
                'terlambat' => $bulanIni->filter(
                    fn (Attendance $a) => $a->status_masuk === AbsenMasukStatus::Terlambat,
                )->count(),
                'jam' => round($bulanIni->sum(fn (Attendance $a) => $a->durasiKerja() ?? 0), 1),
                'bulan' => $sekarang->translatedFormat('M Y'),
            ],
            'pending' => LeaveRequest::query()
                ->where('employee_id', $employee->id)
                ->pending()
                ->count()
                + OvertimeRequest::query()
                    ->where('employee_id', $employee->id)
                    ->where('status', RequestStatus::Pending->value)
                    ->count(),
        ]);
    }

    /** True bila akun ini berperan sebagai atasan, bukan karyawan. */
    private function bisaAksesAdmin(?User $user): bool
    {
        return $user !== null
            && ($user->can('karyawan.lihat') || $user->can('toko.lihat'));
    }

    /** Kartu pribadi untuk dipindai saat datang. */
    public function kartuQr(Request $request): View
    {
        $employee = $this->employeeAtauKeluar($request);

        return view('absen.qr', [
            'employee' => $employee,
            'token' => $this->qr->token($employee),
            'qrSvg' => $this->qr->svg($employee, 360),
            'barcodeSvg' => $this->qr->barcodeSvg($employee),
        ]);
    }

    /**
     * Catat absen masuk atau pulang.
     *
     * Yang dikirim browser hanya koordinat. Jarak selalu dihitung ulang
     * di server oleh GeoService.
     */
    public function catat(Request $request): RedirectResponse|JsonResponse
    {
        $employee = $this->employeeAtauKeluar($request);

        $data = $request->validate([
            'arah' => ['required', 'in:masuk,pulang'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0'],
        ]);

        $metode = AbsenMethod::tryFrom($request->input('metode', 'qr')) ?? AbsenMethod::Qr;

        try {
            if ($data['arah'] === 'masuk') {
                $attendance = $this->absen->absenMasuk(
                    employee: $employee,
                    latitude: (float) $data['latitude'],
                    longitude: (float) $data['longitude'],
                    metode: $metode,
                    akurasi: isset($data['accuracy']) ? (float) $data['accuracy'] : null,
                    deviceId: (string) $request->user()->getKey(),
                );

                return $this->selesai($request, $this->pesanMasuk($attendance), null);
            }

            $attendance = $this->absen->absenPulang(
                employee: $employee,
                latitude: (float) $data['latitude'],
                longitude: (float) $data['longitude'],
                metode: $metode,
                akurasi: isset($data['accuracy']) ? (float) $data['accuracy'] : null,
                deviceId: (string) $request->user()->getKey(),
            );

            return $this->selesai($request, sprintf(
                'Absen pulang tercatat pukul %s. Durasi kerja %s jam.',
                $attendance->jam_pulang->format('H:i'),
                number_format((float) $attendance->durasiKerja(), 1, ',', '.'),
            ), null);
        } catch (AbsenException $e) {
            return $this->selesai($request, $e->getMessage(), $e->reason->value);
        }
    }

    /**
     * Balas JSON untuk permintaan AJAX (kamera/geolokasi), redirect untuk form biasa.
     */
    private function selesai(Request $request, string $pesan, ?string $alasan): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'sukses' => $alasan === null,
                'alasan' => $alasan,
                'pesan' => $pesan,
            ], $alasan === null ? 200 : 422);
        }

        return back()->with($alasan === null ? 'sukses' : 'galat', $pesan);
    }

    /** Riwayat absen milik karyawan yang sedang login. */
    public function riwayat(Request $request): View
    {
        $employee = $this->employeeAtauKeluar($request);

        $riwayat = $employee->attendances()
            ->orderByDesc('tanggal')
            ->paginate(20);

        return view('absen.riwayat', [
            'employee' => $employee,
            'riwayat' => $riwayat,
        ]);
    }

    // ------------------------------------------------------------------

    private function employeeFor(Request $request): ?Employee
    {
        /** @var ?Employee $employee */
        $employee = Employee::query()
            ->where('user_id', $request->user()->getKey())
            ->first();

        return $employee;
    }

    private function employeeAtauKeluar(Request $request): Employee
    {
        $employee = $this->employeeFor($request);

        abort_if($employee === null, 403, 'Akun ini tidak tertaut ke data karyawan.');

        if (! $employee->aktif) {
            throw AbsenException::dari(AbsenGagalReason::KaryawanTidakAktif);
        }

        return $employee;
    }

    private function pesanMasuk(Attendance $attendance): string
    {
        $jam = $attendance->jam_masuk->format('H:i');

        if ($attendance->status_masuk === null) {
            return "Absen masuk tercatat pukul {$jam}.";
        }

        return $attendance->status_masuk === AbsenMasukStatus::Terlambat
            ? "Absen masuk tercatat pukul {$jam}. Anda tercatat terlambat."
            : "Absen masuk tercatat pukul {$jam}. Tepat waktu.";
    }
}
