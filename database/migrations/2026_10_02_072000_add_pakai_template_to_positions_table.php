<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('positions', function (Blueprint $table) {
            // Jabatan dengan pakai_template = true wajib punya template shift;
            // sisanya (kasir, pramuniaga) mengikuti window shift apa pun yang
            // cocok dengan jam datangnya.
            $table->boolean('pakai_template')->default(false)->after('aktif');
        });
    }

    public function down(): void
    {
        Schema::table('positions', function (Blueprint $table) {
            $table->dropColumn('pakai_template');
        });
    }
};
