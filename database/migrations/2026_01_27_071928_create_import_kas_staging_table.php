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
        Schema::create('import_kas_staging', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('batch_id', 50); // untuk grouping import session
            $table->string('no_referensi', 100)->nullable();
            $table->date('tanggal');
            $table->text('uraian');
            $table->decimal('uang_masuk', 15, 2)->default(0);
            $table->decimal('uang_keluar', 15, 2)->default(0);
            $table->string('kode_akun_kas', 20)->nullable(); // akun kas/bank
            $table->string('kode_akun_lawan', 20)->nullable(); // akun pendapatan/biaya
            $table->boolean('is_selected')->default(false);
            $table->boolean('is_posted')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'batch_id']);
            $table->index('is_posted');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_kas_staging');
    }
};
