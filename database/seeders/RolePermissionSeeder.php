<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * @var array<int, string>
     */
    private const PERMISSIONS = [
        'dashboard.lihat',

        'karyawan.lihat',
        'karyawan.kelola',
        'karyawan.hapus',

        'toko.lihat',
        'toko.kelola',

        'jabatan.lihat',
        'jabatan.kelola',

        'shift.lihat',
        'shift.kelola',

        'absen.catat',
        'absen.lihat',
        'absen.kelola',
        'absen.override',

        'pengajuan.lihat',
        'pengajuan.buat',
        'pengajuan.setujui',

        'laporan.lihat',
        'laporan.expor',

        'payroll.lihat',
        'payroll.kelola',
        'payroll.finalisasi',

        'libur.lihat',
        'libur.kelola',

        'audit.lihat',
        'pengguna.lihat',
        'pengguna.kelola',
        'perangkat.kelola',
        'peran.kelola',

        'pengaturan.lihat',
        'pengaturan.kelola',
    ];

    /**
     * Permission khusus Supervisor. Anything absent here is denied.
     *
     * @var array<int, string>
     */
    private const KECUALIAN_SUPERVISOR = [
        'karyawan.hapus',
        'payroll.finalisasi',
        'peran.kelola',
        'pengguna.kelola',
        'pengaturan.kelola',
    ];

    /**
     * @var array<int, string>
     */
    private const PERMISSION_KARYAWAN = [
        'dashboard.lihat',
        'absen.catat',
        'absen.lihat',
        'pengajuan.buat',
        'pengajuan.lihat',
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $semua = Permission::all();

        $pemilik = Role::findOrCreate('pemilik', 'web');
        $supervisor = Role::findOrCreate('supervisor', 'web');
        $karyawan = Role::findOrCreate('karyawan', 'web');

        $pemilik->syncPermissions($semua);
        $supervisor->syncPermissions(
            $semua->reject(fn (Permission $p) => in_array($p->name, self::KECUALIAN_SUPERVISOR, true))
        );
        $karyawan->syncPermissions(
            $semua->whereIn('name', self::PERMISSION_KARYAWAN)
        );

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
