<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DepresiasiHistory extends Model
{
    use HasFactory;

    protected $table = 'depresiasi_history';

    protected $fillable = [
        'id_aset',
        'id_jurnal',
        'periode',
        'jumlah_depresiasi',
    ];

    protected $casts = [
        'id_aset' => 'integer',
        'id_jurnal' => 'integer',
        'jumlah_depresiasi' => 'decimal:2',
    ];

    public function aset()
    {
        return $this->belongsTo(AsetTetap::class, 'id_aset', 'id');
    }

    public function jurnal()
    {
        return $this->belongsTo(Jurnal::class, 'id_jurnal', 'id_jurnal');
    }
}
