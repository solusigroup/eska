<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Jurnal extends Model
{
    use HasFactory;

    protected $table = 'jurnal_umum';
    protected $primaryKey = 'id_jurnal';
    protected $guarded = ['id_jurnal'];

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($jurnal) {
            $jurnal->details()->delete();
        });
    }

    protected $casts = [
        'tanggal' => 'date',
    ];

    public function details()
    {
        return $this->hasMany(JurnalDetail::class, 'id_jurnal');
    }

    public function proyek()
    {
        return $this->belongsTo(Proyek::class, 'id_proyek', 'id_proyek');
    }
}
