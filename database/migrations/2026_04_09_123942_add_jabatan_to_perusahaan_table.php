<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('perusahaan', function (Blueprint $table) {
            $table->string('jabatan_direktur')->nullable()->after('nama_direktur')->default('Direktur');
            $table->string('jabatan_akuntan')->nullable()->after('nama_akuntan')->default('Akuntan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('perusahaan', function (Blueprint $table) {
            $table->dropColumn(['jabatan_direktur', 'jabatan_akuntan']);
        });
    }
};
