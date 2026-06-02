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
        Schema::create('tutup_buku', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal_tutup');
            $table->unsignedBigInteger('id_jurnal_penutup')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->foreign('id_jurnal_penutup')->references('id_jurnal')->on('jurnal_umum')->onDelete('set null');
            $table->foreign('user_id')->references('id_user')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tutup_buku');
    }
};
