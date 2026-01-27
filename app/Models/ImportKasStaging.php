<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImportKasStaging extends Model
{
    use HasFactory;

    protected $table = 'import_kas_staging';

    protected $fillable = [
        'user_id',
        'batch_id',
        'no_referensi',
        'tanggal',
        'uraian',
        'uang_masuk',
        'uang_keluar',
        'kode_akun_kas',
        'kode_akun_lawan',
        'is_selected',
        'is_posted',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'uang_masuk' => 'decimal:2',
        'uang_keluar' => 'decimal:2',
        'is_selected' => 'boolean',
        'is_posted' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function akunKas()
    {
        return $this->belongsTo(Akun::class, 'kode_akun_kas', 'kode_akun');
    }

    public function akunLawan()
    {
        return $this->belongsTo(Akun::class, 'kode_akun_lawan', 'kode_akun');
    }

    /**
     * Check if this is a cash-in transaction
     */
    public function isCashIn(): bool
    {
        return $this->uang_masuk > 0;
    }

    /**
     * Check if this is a cash-out transaction
     */
    public function isCashOut(): bool
    {
        return $this->uang_keluar > 0;
    }

    /**
     * Get the transaction amount
     */
    public function getAmount(): float
    {
        return $this->isCashIn() ? $this->uang_masuk : $this->uang_keluar;
    }
}
