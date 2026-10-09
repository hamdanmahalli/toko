<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AbsenMasukStatus;
use App\Enums\RequestStatus;
use App\Enums\StatusPerangkat;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\OvertimeRequest;
use App\Models\Shop;
use App\Models\UserDevice;
use App\Services\ShiftWindowService;
use App\Support\CakupanToko;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly CakupanToko $cakupan,
        private readonly ShiftWindowService $window,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $hariIni = now()->toDateString();

        $karyawan = $this->cakupan->batasi(Employee::query(), $user);
        $absensi = $this->cakupan->batasi(Attendance::query(), $user);

        $izin = LeaveRequest::query()
            ->whereHas('employee', fn (Builder $q) => $this->cakupan->batasi($q, $user))
            ->pending();
        $lembur = OvertimeRequest::query()
            ->whereHas('employee', fn (Builder $q) => $this->cakupan->batasi($q, $user))
            ->where('status', RequestStatus::Pending->value);

        // Perangkat karyawan yang menunggu persetujuan. Karyawan hanya punya
        // satu data karyawan yang menautkannya ke toko, jadi cakupan toko
        // disaring lewat relasi itu; peran global melihat semuanya.
        $perangkatMenunggu = UserDevice::query()
            ->where('status', StatusPerangkat::Pending->value)
            ->when(
                ! $this->cakupan->semuaToko($user),
                fn (Builder $q) => $q->whereHas(
                    'user.employee',
                    fn (Builder $e) => $e->whereIn('shop_id', $this->cakupan->idToko($user)),
                ),
            )
            ->count();

        $absensiHariIni = (clone $absensi)->whereDate('tanggal', $hariIni);

        $belumPulang = (clone $absensiHariIni)
            ->whereNotNull('jam_masuk')
            ->whereNull('jam_pulang');

        $tokoList = $this->cakupan->semuaToko($user)
            ? Shop::orderBy('nama')->get()
            : $user->shops()->orderBy('nama')->get();

        $perToko = $tokoList->map(fn (Shop $toko) => [
            'nama' => $toko->nama,
            'geofence' => $toko->hasGeofence(),
            'karyawan' => Employee::where('shop_id', $toko->id)->where('aktif', true)->count(),
            'hadir' => Attendance::where('shop_id', $toko->id)
                ->whereDate('tanggal', $hariIni)
                ->whereNotNull('jam_masuk')
                ->count(),
            'terlambat' => Attendance::where('shop_id', $toko->id)
                ->whereDate('tanggal', $hariIni)
                ->where('status_masuk', AbsenMasukStatus::Terlambat->value)
                ->count(),
        ]);

        return view('admin.dashboard', [
            'totalKaryawan' => (clone $karyawan)->where('aktif', true)->count(),
            'hadir' => (clone $absensiHariIni)->whereNotNull('jam_masuk')->count(),
            'terlambat' => (clone $absensiHariIni)
                ->where('status_masuk', AbsenMasukStatus::Terlambat->value)
                ->count(),
            'belumPulang' => (clone $belumPulang)->count(),
            'pendingIzin' => (clone $izin)->count(),
            'pendingLembur' => (clone $lembur)->count(),
            'perangkatMenunggu' => $perangkatMenunggu,
            'perToko' => $perToko,
            // Label window dihitung dari absensi yang benar-benar tercatat,
            // bukan dari window yang berlaku sekarang.
            'perWindow' => $this->window->rekap(
                $hariIni,
                $this->cakupan->semuaToko($user) ? null : $this->cakupan->idToko($user)->all(),
            ),
            'daftarBelumPulang' => (clone $belumPulang)
                ->with('employee')
                ->orderBy('jam_masuk')
                ->limit(10)
                ->get(),
        ]);
    }
}
