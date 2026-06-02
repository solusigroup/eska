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
        Schema::create('master_aset_tetap', function (Blueprint $table) {
            $table->id();
            $table->string('kode_aset')->unique();
            $table->string('nama_aset');
            $table->date('tanggal_perolehan');
            $table->decimal('harga_perolehan', 15, 2);
            $table->decimal('nilai_residu', 15, 2)->default(0);
            $table->integer('umur_ekonomis'); // dalam bulan
            $table->string('metode_depresiasi')->default('Garis Lurus');
            $table->string('kode_akun_aset', 20);
            $table->string('kode_akun_akumulasi', 20);
            $table->string('kode_akun_beban', 20);
            $table->enum('status', ['Aktif', 'Habis', 'Terjual'])->default('Aktif');
            $table->timestamps();

            // Foreign keys
            $table->foreign('kode_akun_aset')->references('kode_akun')->on('akun');
            $table->foreign('kode_akun_akumulasi')->references('kode_akun')->on('akun');
            $table->foreign('kode_akun_beban')->references('kode_akun')->on('akun');
        });

        Schema::create('depresiasi_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_aset');
            $table->unsignedBigInteger('id_jurnal');
            $table->string('periode', 7); // format 'YYYY-MM'
            $table->decimal('jumlah_depresiasi', 15, 2);
            $table->timestamps();

            $table->foreign('id_aset')->references('id')->on('master_aset_tetap')->onDelete('cascade');
            $table->foreign('id_jurnal')->references('id_jurnal')->on('jurnal_umum')->onDelete('cascade');
            $table->unique(['id_aset', 'periode']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('depresiasi_history');
        Schema::dropIfExists('master_aset_tetap');
    }
};
