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
        Schema::create('jurnal_kas', function (Blueprint $table) {
            $table->id('id_jurnal_kas');
            $table->string('no_bukti');
            $table->date('tanggal');
            $table->enum('tipe', ['Masuk', 'Keluar']);
            $table->string('akun_kas'); // FK ke akun (Kas/Bank)
            $table->string('akun_lawan'); // FK ke akun lawan
            $table->decimal('jumlah', 15, 2);
            $table->text('keterangan')->nullable();
            $table->unsignedBigInteger('id_proyek')->nullable();
            $table->unsignedBigInteger('id_jurnal')->nullable(); // Links to jurnal_umum
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jurnal_kas');
    }
};
