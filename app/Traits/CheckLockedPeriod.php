<?php

namespace App\Traits;

use App\Models\TutupBuku;

trait CheckLockedPeriod
{
    /**
     * Memeriksa apakah tanggal transaksi dikunci karena periode telah ditutup buku.
     * 
     * @param string|\DateTime $tanggal
     * @return bool
     */
    public function isPeriodLocked($tanggal): bool
    {
        if (!$tanggal) {
            return false;
        }

        $tanggalTransaksi = is_string($tanggal) ? new \DateTime($tanggal) : $tanggal;
        
        // Ambil penutupan buku terakhir
        $lastClose = TutupBuku::orderBy('tanggal_tutup', 'desc')->first();
        
        if ($lastClose) {
            $tanggalTutup = new \DateTime($lastClose->tanggal_tutup->format('Y-m-d'));
            $tanggalTransaksiFormatted = new \DateTime($tanggalTransaksi->format('Y-m-d'));
            
            return $tanggalTransaksiFormatted <= $tanggalTutup;
        }
        
        return false;
    }

    /**
     * Memeriksa dan memblokir aksi jika tanggal dikunci.
     */
    public function checkLockedPeriod($tanggal)
    {
        if ($this->isPeriodLocked($tanggal)) {
            abort(403, 'Aksi ditolak. Periode transaksi ini telah ditutup buku dan dikunci.');
        }
    }
}
