<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 50)->nullable()->unique()->after('email');
            $table->boolean('bebas_perangkat')->default(false)->after('aktif');
        });

        $this->isiUsernameDariEmail();
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_username_unique');
            $table->dropColumn(['username', 'bebas_perangkat']);
        });
    }

    // Akun lama yang belum punya username dibuatkan dari prefiks email supaya
    // login memakai username langsung berlaku, tanpa menunggu data diibaru.
    private function isiUsernameDariEmail(): void
    {
        $baris = DB::table('users')
            ->select('id', 'email', 'username')
            ->whereNull('username')
            ->orderBy('id')
            ->get();

        $dipakai = [];

        foreach ($baris as $user) {
            $dasar = Str::slug(explode('@', (string) $user->email, 2)[0], '', 'id');
            $dasar = preg_replace('/[^a-z0-9_.-]/', '', $dasar);
            $dasar = mb_substr($dasar ?: 'pengguna', 0, 40);

            $coba = $dasar;
            $i = 1;
            while (isset($dipakai[$coba])) {
                $i++;
                $coba = $dasar.$i;
            }
            $dipakai[$coba] = true;

            DB::table('users')->where('id', $user->id)->update(['username' => $coba]);
        }
    }
};
