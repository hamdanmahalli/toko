<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Peran dengan cakupan toko global
    |--------------------------------------------------------------------------
    |
    | Hanya peran yang disebut di sini yang boleh melihat dan mengelola
    | seluruh toko. Semua akun lain selalu tersempit ke tabel `user_shop`,
    | termasuk akun yang belum ditugaskan ke toko mana pun — akun seperti itu
    | melihat nol toko, bukan semua toko.
    |
    | Ini sengaja gagal-tertutup. Sebelumnya "tidak punya toko" berarti
    | "boleh semua toko", sehingga supervisor yang lupa ditugaskan justru
    | mendapat akses ke seluruh cabang.
    |
    */

    'peran_toko_global' => ['pemilik'],

    /*
    |--------------------------------------------------------------------------
    | Perangkat presensi karyawan
    |--------------------------------------------------------------------------
    |
    | Perangkat ini dipakai bersama di satu lokasi toko, bukan oleh satu
    | karyawan. Jadi tidak ada akun per orang: satu user dan satu password per
    | toko yang dipakai siapa pun yang sedang absen di sana.
    |
    | `idle_timeout` menutup celah paling nyata dari login bersama: perangkat
    | yang ditinggal begitu saja akan terkunci sendiri, lalu siapa pun yang
    | datang berikutnya harus login lagi. Nilainya dalam menit, dan 0
    | berarti perangkat tidak pernah mengunci sendiri.
    |
    */

    'presensi' => [
        'idle_timeout' => (int) env('PRESENSI_IDLE_TIMEOUT', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Peran yang bebas dari penjagaan perangkat
    |--------------------------------------------------------------------------
    |
    | Karyawan hanya boleh login dari perangkat yang disetujui pemilik atau
    | kepala toko (tabel `user_devices`). Peran yang disebut di sini dianggap
    | sudah dipercaya dan tidak dibatasi, kecuali lewat `bebas_perangkat`
    | per-akun yang tetap bisa memilih untuk mengikuti aturan.
    |
    */

    'perangkat_bebas_roles' => ['pemilik', 'supervisor'],

];
