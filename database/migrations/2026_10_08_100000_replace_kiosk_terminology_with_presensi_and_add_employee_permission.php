<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Istilah "kios" diganti jadi "presensi karyawan" di data yang tersimpan:
 *  - kolom `shops.kiosk_*` di-rename jadi `presensi_*`
 *  - nilai enum `attendances.metode` 'kiosk' jadi 'presensi' (data lama digeser)
 *
 * Sekaligus menambah izin per karyawan untuk absen lewat perangkat presensi.
 * Bawaannya diizinkan; admin yang mematikan untuk karyawan yang tidak boleh
 * memakai kartunya di perangkat presensi toko.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->renameColumn('kiosk_user', 'presensi_user');
            $table->renameColumn('kiosk_password', 'presensi_password');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->boolean('boleh_presensi')->default(true)->after('aktif');
        });

        $this->gantiEnumMetode('presensi');
    }

    public function down(): void
    {
        $this->gantiEnumMetode('kiosk');

        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('boleh_presensi');
        });

        Schema::table('shops', function (Blueprint $table) {
            $table->renameColumn('presensi_user', 'kiosk_user');
            $table->renameColumn('presensi_password', 'kiosk_password');
        });
    }

    /**
     * Ganti nilai enum `attendances.metode`: lama 'kiosk', baru 'presensi'.
     *
     * Di PostgreSQL, enum Laravel disimpan sebagai varchar + check constraint
     * (`attendances_metode_check`), bukan tipe enum sungguhan. Jadi caranya:
     * buang constraint, geser datanya, pasang constraint dengan nilai yang
     * baru. Di MySQL kolomnya enum asli: geser data dulu, baru MODIFY.
     */
    private function gantiEnumMetode(string $nilai): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("UPDATE attendances SET metode = '{$nilai}' WHERE metode = 'kiosk'");
            DB::statement("ALTER TABLE attendances MODIFY COLUMN metode ENUM('qr','selfie','{$nilai}') NOT NULL DEFAULT 'qr'");

            return;
        }

        // PostgreSQL: constraint lama dibuang dengan menyebutkan kolomnya,
        // bukan namanya, supaya tetap jalan walau Laravel mengubah penamaan.
        DB::statement(<<<'SQL'
            DO $$
            DECLARE
                nama text;
            BEGIN
                FOR nama IN
                    SELECT c.conname
                    FROM pg_constraint c
                    JOIN pg_attribute a ON a.attrelid = c.conrelid AND a.attnum = ANY(c.conkey)
                    WHERE c.conrelid = 'attendances'::regclass
                      AND c.contype = 'c'
                      AND a.attname = 'metode'
                LOOP
                    EXECUTE format('ALTER TABLE attendances DROP CONSTRAINT %I', nama);
                END LOOP;
            END $$;
            SQL);

        DB::statement("UPDATE attendances SET metode = '{$nilai}' WHERE metode = 'kiosk'");

        DB::statement(
            "ALTER TABLE attendances ADD CONSTRAINT attendances_metode_check CHECK (metode in ('qr','selfie','{$nilai}'))"
        );
    }
};
