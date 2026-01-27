<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Proyek extends Model
{
    use HasFactory;

    protected $table = 'proyek';
    protected $primaryKey = 'id_proyek';
    protected $guarded = ['id_proyek'];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'anggaran' => 'decimal:2',
    ];

    /**
     * Scope untuk proyek aktif
     */
    public function scopeAktif($query)
    {
        return $query->where('status', 'Aktif');
    }

    /**
     * Relationship: Proyek memiliki banyak jurnal umum
     */
    public function jurnals()
    {
        return $this->hasMany(Jurnal::class, 'id_proyek', 'id_proyek');
    }

    /**
     * Relationship: Proyek memiliki banyak jurnal detail
     */
    public function jurnalDetails()
    {
        return $this->hasMany(JurnalDetail::class, 'id_proyek', 'id_proyek');
    }

    /**
     * Relationship: Proyek memiliki banyak penjualan
     */
    public function penjualans()
    {
        return $this->hasMany(Penjualan::class, 'id_proyek', 'id_proyek');
    }

    /**
     * Relationship: Proyek memiliki banyak pembelian
     */
    public function pembelians()
    {
        return $this->hasMany(Pembelian::class, 'id_proyek', 'id_proyek');
    }

    /**
     * Relationship: Proyek memiliki banyak jurnal kas
     */
    public function jurnalKas()
    {
        return $this->hasMany(JurnalKas::class, 'id_proyek', 'id_proyek');
    }

    /**
     * Hitung total pendapatan proyek
     */
    public function getTotalPendapatanAttribute()
    {
        return $this->jurnalDetails()
            ->whereHas('akun', function ($q) {
                $q->whereIn('tipe_akun', ['Pendapatan', 'Pendapatan Lainnya']);
            })
            ->sum(\DB::raw('kredit - debit'));
    }

    /**
     * Hitung total beban proyek
     */
    public function getTotalBebanAttribute()
    {
        return $this->jurnalDetails()
            ->whereHas('akun', function ($q) {
                $q->whereIn('tipe_akun', ['HPP', 'Beban', 'Beban Lainnya']);
            })
            ->sum(\DB::raw('debit - kredit'));
    }

    /**
     * Hitung laba/rugi proyek
     */
    public function getLabaRugiAttribute()
    {
        return $this->total_pendapatan - $this->total_beban;
    }
}
