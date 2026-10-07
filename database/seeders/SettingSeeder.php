<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        Setting::simpan([
            'umum.nama_app' => 'Absensi Toko',
            'umum.nama_perusahaan' => '',

            // dipakai sebagai-isanya kalau shift karyawan tidak punya nilai sendiri
            'umum.jam_masuk' => '08:00',
            'umum.batas_telat' => '08:15',
            'umum.jam_pulang' => '17:00',

            // akurasi GPS di atas ini dianggap tidak bisa dipercaya.
            // Longgar karena di dalam toko 100-300 m itu normal.
            'geofence.akurasi_maks_meter' => 500,
            'geofence.radius_bawaan_meter' => 150,

            'payroll.tipe_bawaan' => 'harian',
            'payroll.tarif_lembur_bawaan' => 20000,
            'payroll.batas_jam_lembur' => 12,
        ]);
    }
}
