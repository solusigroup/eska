<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JurnalKas extends Model
{
    use HasFactory;

    protected $table = 'jurnal_kas';
    protected $primaryKey = 'id_jurnal_kas';
    protected $guarded = ['id_jurnal_kas'];

    protected $casts = [
        'tanggal' => 'date',
        'jumlah' => 'decimal:2',
    ];

    /**
     * Relationship: Jurnal Kas belongs to Proyek
     */
    public function proyek()
    {
        return $this->belongsTo(Proyek::class, 'id_proyek', 'id_proyek');
    }

    /**
     * Relationship: Jurnal Kas belongs to Jurnal Umum
     */
    public function jurnal()
    {
        return $this->belongsTo(Jurnal::class, 'id_jurnal', 'id_jurnal');
    }

    /**
     * Relationship: Akun Kas
     */
    public function akunKasRef()
    {
        return $this->belongsTo(Akun::class, 'akun_kas', 'kode_akun');
    }

    /**
     * Relationship: Akun Lawan
     */
    public function akunLawanRef()
    {
        return $this->belongsTo(Akun::class, 'akun_lawan', 'kode_akun');
    }

    /**
     * Scope untuk kas masuk
     */
    public function scopeMasuk($query)
    {
        return $query->where('tipe', 'Masuk');
    }

    /**
     * Scope untuk kas keluar
     */
    public function scopeKeluar($query)
    {
        return $query->where('tipe', 'Keluar');
    }

    /**
     * Boot method untuk auto-generate jurnal umum
     */
    protected static function boot()
    {
        parent::boot();

        static::created(function ($jurnalKas) {
            $jurnalKas->createJurnalUmum();
        });

        static::updated(function ($jurnalKas) {
            // Update jurnal umum yang terkait
            if ($jurnalKas->id_jurnal) {
                $jurnalKas->updateJurnalUmum();
            }
        });

        static::deleting(function ($jurnalKas) {
            // Hapus jurnal umum yang terkait secara aman menggunakan instance model agar memicu event deleting
            if ($jurnalKas->id_jurnal) {
                $jurnal = Jurnal::find($jurnalKas->id_jurnal);
                if ($jurnal) {
                    $jurnal->delete();
                }
            }
        });
    }

    /**
     * Create jurnal umum dari jurnal kas
     */
    public function createJurnalUmum()
    {
        // Buat jurnal umum header
        $jurnal = Jurnal::create([
            'no_transaksi' => $this->no_bukti,
            'tanggal' => $this->tanggal,
            'deskripsi' => ($this->tipe === 'Masuk' ? 'Kas Masuk: ' : 'Kas Keluar: ') . $this->keterangan,
            'sumber_jurnal' => 'Jurnal Kas',
            'id_proyek' => $this->id_proyek,
        ]);

        // Buat jurnal detail
        if ($this->tipe === 'Masuk') {
            // Kas Masuk: Debit Kas, Kredit Akun Lawan
            JurnalDetail::create([
                'id_jurnal' => $jurnal->id_jurnal,
                'kode_akun' => $this->akun_kas,
                'debit' => $this->jumlah,
                'kredit' => 0,
                'id_proyek' => $this->id_proyek,
            ]);
            JurnalDetail::create([
                'id_jurnal' => $jurnal->id_jurnal,
                'kode_akun' => $this->akun_lawan,
                'debit' => 0,
                'kredit' => $this->jumlah,
                'id_proyek' => $this->id_proyek,
            ]);
        } else {
            // Kas Keluar: Kredit Kas, Debit Akun Lawan
            JurnalDetail::create([
                'id_jurnal' => $jurnal->id_jurnal,
                'kode_akun' => $this->akun_kas,
                'debit' => 0,
                'kredit' => $this->jumlah,
                'id_proyek' => $this->id_proyek,
            ]);
            JurnalDetail::create([
                'id_jurnal' => $jurnal->id_jurnal,
                'kode_akun' => $this->akun_lawan,
                'debit' => $this->jumlah,
                'kredit' => 0,
                'id_proyek' => $this->id_proyek,
            ]);
        }

        // Update id_jurnal di jurnal_kas without triggering events
        $this->id_jurnal = $jurnal->id_jurnal;
        $this->saveQuietly();
    }

    /**
     * Update jurnal umum dari jurnal kas
     */
    public function updateJurnalUmum()
    {
        $jurnal = Jurnal::find($this->id_jurnal);
        if (!$jurnal)
            return;

        // Update header
        $jurnal->update([
            'no_transaksi' => $this->no_bukti,
            'tanggal' => $this->tanggal,
            'deskripsi' => ($this->tipe === 'Masuk' ? 'Kas Masuk: ' : 'Kas Keluar: ') . $this->keterangan,
            'id_proyek' => $this->id_proyek,
        ]);

        // Hapus detail lama dan buat baru
        JurnalDetail::where('id_jurnal', $this->id_jurnal)->delete();

        if ($this->tipe === 'Masuk') {
            JurnalDetail::create([
                'id_jurnal' => $jurnal->id_jurnal,
                'kode_akun' => $this->akun_kas,
                'debit' => $this->jumlah,
                'kredit' => 0,
                'id_proyek' => $this->id_proyek,
            ]);
            JurnalDetail::create([
                'id_jurnal' => $jurnal->id_jurnal,
                'kode_akun' => $this->akun_lawan,
                'debit' => 0,
                'kredit' => $this->jumlah,
                'id_proyek' => $this->id_proyek,
            ]);
        } else {
            JurnalDetail::create([
                'id_jurnal' => $jurnal->id_jurnal,
                'kode_akun' => $this->akun_kas,
                'debit' => 0,
                'kredit' => $this->jumlah,
                'id_proyek' => $this->id_proyek,
            ]);
            JurnalDetail::create([
                'id_jurnal' => $jurnal->id_jurnal,
                'kode_akun' => $this->akun_lawan,
                'debit' => $this->jumlah,
                'kredit' => 0,
                'id_proyek' => $this->id_proyek,
            ]);
        }
    }
}
