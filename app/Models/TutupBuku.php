<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TutupBuku extends Model
{
    use HasFactory;

    protected $table = 'tutup_buku';

    protected $fillable = [
        'tanggal_tutup',
        'id_jurnal_penutup',
        'user_id',
        'keterangan',
    ];

    protected $casts = [
        'tanggal_tutup' => 'date',
        'id_jurnal_penutup' => 'integer',
        'user_id' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id_user');
    }

    public function jurnalPenutup()
    {
        return $this->belongsTo(Jurnal::class, 'id_jurnal_penutup', 'id_jurnal');
    }
}
