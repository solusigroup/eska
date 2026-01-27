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
        // Tabel Proyek
        Schema::create('proyek', function (Blueprint $table) {
            $table->id('id_proyek');
            $table->string('kode_proyek', 20)->unique();
            $table->string('nama_proyek');
            $table->text('deskripsi')->nullable();
            $table->enum('status', ['Aktif', 'Selesai', 'Ditunda'])->default('Aktif');
            $table->date('tanggal_mulai')->nullable();
            $table->date('tanggal_selesai')->nullable();
            $table->decimal('anggaran', 15, 2)->default(0);
            $table->string('lokasi')->nullable();
            $table->string('pelanggan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proyek');
    }
};
