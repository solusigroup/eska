<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Tambah id_proyek ke jurnal_umum
        Schema::table('jurnal_umum', function (Blueprint $table) {
            $table->unsignedBigInteger('id_proyek')->nullable()->after('sumber_jurnal');
        });

        // Tambah id_proyek ke jurnal_detail
        Schema::table('jurnal_detail', function (Blueprint $table) {
            $table->unsignedBigInteger('id_proyek')->nullable()->after('kredit');
        });

        // Tambah id_proyek ke penjualan
        Schema::table('penjualan', function (Blueprint $table) {
            $table->unsignedBigInteger('id_proyek')->nullable()->after('status_pembayaran');
        });

        // Tambah id_proyek ke pembelian
        Schema::table('pembelian', function (Blueprint $table) {
            $table->unsignedBigInteger('id_proyek')->nullable()->after('status_pembayaran');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jurnal_umum', function (Blueprint $table) {
            $table->dropColumn('id_proyek');
        });

        Schema::table('jurnal_detail', function (Blueprint $table) {
            $table->dropColumn('id_proyek');
        });

        Schema::table('penjualan', function (Blueprint $table) {
            $table->dropColumn('id_proyek');
        });

        Schema::table('pembelian', function (Blueprint $table) {
            $table->dropColumn('id_proyek');
        });
    }
};
