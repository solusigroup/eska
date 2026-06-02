<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AsetTetap extends Model
{
    use HasFactory;

    protected $table = 'master_aset_tetap';

    protected $fillable = [
        'kode_aset',
        'nama_aset',
        'tanggal_perolehan',
        'harga_perolehan',
        'nilai_residu',
        'umur_ekonomis',
        'metode_depresiasi',
        'kode_akun_aset',
        'kode_akun_akumulasi',
        'kode_akun_beban',
        'status',
    ];

    protected $casts = [
        'tanggal_perolehan' => 'date',
        'harga_perolehan' => 'decimal:2',
        'nilai_residu' => 'decimal:2',
        'umur_ekonomis' => 'integer',
    ];

    public function akunAset()
    {
        return $this->belongsTo(Akun::class, 'kode_akun_aset', 'kode_akun');
    }

    public function akunAkumulasi()
    {
        return $this->belongsTo(Akun::class, 'kode_akun_akumulasi', 'kode_akun');
    }

    public function akunBeban()
    {
        return $this->belongsTo(Akun::class, 'kode_akun_beban', 'kode_akun');
    }

    public function depresiasiHistory()
    {
        return $this->hasMany(DepresiasiHistory::class, 'id_aset', 'id');
    }

    /**
     * Menghitung nilai penyusutan bulanan (Garis Lurus)
     */
    public function hitungDepresiasiBulanan(): float
    {
        if ($this->umur_ekonomis <= 0) {
            return 0;
        }
        $nilaiDisusutkan = $this->harga_perolehan - $this->nilai_residu;
        return round($nilaiDisusutkan / $this->umur_ekonomis, 2);
    }
}
