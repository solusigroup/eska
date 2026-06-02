<?php

namespace App\Http\Controllers;

use App\Models\Akun;
use App\Models\Jurnal;
use App\Models\JurnalDetail;
use App\Models\TutupBuku;
use App\Traits\CheckLockedPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class TutupBukuController extends Controller
{
    use CheckLockedPeriod;

    /**
     * Tampilkan halaman status dan riwayat tutup buku
     */
    public function index()
    {
        $lastClose = TutupBuku::with(['user', 'jurnalPenutup'])->orderBy('tanggal_tutup', 'desc')->first();
        $histories = TutupBuku::with(['user', 'jurnalPenutup'])->orderBy('tanggal_tutup', 'desc')->paginate(15);
        
        $tanggalMinimal = '2020-01-01';
        if ($lastClose) {
            $tanggalMinimal = date('Y-m-d', strtotime($lastClose->tanggal_tutup->format('Y-m-d') . ' +1 day'));
        }

        return view('tutup-buku.index', compact('lastClose', 'histories', 'tanggalMinimal'));
    }

    /**
     * Jalankan proses tutup buku akhir periode
     */
    public function closePeriod(Request $request)
    {
        $validated = $request->validate([
            'tanggal_tutup' => 'required|date',
            'keterangan' => 'nullable|string',
        ]);

        $tanggalTutup = $validated['tanggal_tutup'];

        // Validasi: harus lebih baru dari tanggal tutup buku sebelumnya
        $lastClose = TutupBuku::orderBy('tanggal_tutup', 'desc')->first();
        if ($lastClose && strtotime($tanggalTutup) <= strtotime($lastClose->tanggal_tutup->format('Y-m-d'))) {
            return back()->with('error', 'Tanggal tutup buku harus lebih baru dari tanggal penutupan buku sebelumnya (' . $lastClose->tanggal_tutup->format('d-m-Y') . ').');
        }

        // Validasi: pastikan akun Laba Ditahan (3-30000) ada di database
        $akunLabaDitahan = Akun::where('kode_akun', '3-30000')
            ->orWhere('nama_akun', 'like', '%Laba Ditahan%')
            ->first();

        if (!$akunLabaDitahan) {
            return back()->with('error', 'Akun Laba Ditahan (kode: 3-30000 atau nama: Laba Ditahan) tidak ditemukan. Silakan buat akun tersebut terlebih dahulu di Chart of Accounts (COA).');
        }

        // 1. Ambil semua akun Pendapatan, HPP, Beban
        $akunLabaRugi = Akun::whereIn('tipe_akun', [
            'Pendapatan',
            'Pendapatan Lainnya',
            'HPP',
            'Beban',
            'Beban Lainnya'
        ])->get();

        // 2. Hitung saldo akhir per akun s.d. tanggal tutup buku
        // Kita gunakan kueri agregasi tunggal demi performa tinggi (menghindari N+1)
        $saldos = JurnalDetail::whereHas('jurnal', function ($q) use ($tanggalTutup) {
                $q->where('tanggal', '<=', $tanggalTutup . ' 23:59:59');
            })
            ->select('kode_akun', DB::raw('SUM(debit) as total_debit'), DB::raw('SUM(kredit) as total_kredit'))
            ->groupBy('kode_akun')
            ->get()
            ->keyBy('kode_akun');

        $detailJurnals = [];
        $totalDebitPenutup = 0;
        $totalKreditPenutup = 0;

        foreach ($akunLabaRugi as $akun) {
            $saldo = $saldos->get($akun->kode_akun);
            if (!$saldo) {
                continue;
            }

            $totalDebit = $saldo->total_debit ?? 0;
            $totalKredit = $saldo->total_kredit ?? 0;

            // Hitung saldo bersih akun
            if ($akun->saldo_normal == 'Kredit') {
                $saldoBersih = $totalKredit - $totalDebit;
            } else {
                $saldoBersih = $totalDebit - $totalKredit;
            }

            // Jika tidak ada saldo, lewatkan
            if (round($saldoBersih, 2) == 0) {
                continue;
            }

            // Jurnal Penutup menihilkan saldo:
            if ($akun->saldo_normal == 'Kredit') {
                // Pendapatan bersaldo normal Kredit, untuk menihilkan, kita DEBIT
                $detailJurnals[] = [
                    'kode_akun' => $akun->kode_akun,
                    'debit' => $saldoBersih,
                    'kredit' => 0,
                ];
                $totalDebitPenutup += $saldoBersih;
            } else {
                // Beban bersaldo normal Debit, untuk menihilkan, kita KREDIT
                $detailJurnals[] = [
                    'kode_akun' => $akun->kode_akun,
                    'debit' => 0,
                    'kredit' => $saldoBersih,
                ];
                $totalKreditPenutup += $saldoBersih;
            }
        }

        if (empty($detailJurnals)) {
            return back()->with('info', 'Tidak ada saldo pendapatan atau beban yang aktif untuk ditutup pada periode ini.');
        }

        try {
            DB::beginTransaction();

            // 3. Hitung selisih Laba/Rugi Bersih untuk dialihkan ke Laba Ditahan
            // Selisih = Debit Penutup (Pendapatan) - Kredit Penutup (Beban)
            $labaRugiBersih = $totalDebitPenutup - $totalKreditPenutup;

            // Generate nomor transaksi penutup
            $noTransaksi = 'CL-' . date('Ymd', strtotime($tanggalTutup));

            // Hapus jika ada Jurnal Penutup lama pada tanggal yang sama untuk mencegah duplikasi
            $oldJurnal = Jurnal::where('no_transaksi', $noTransaksi)->first();
            if ($oldJurnal) {
                $oldJurnal->delete();
            }

            // 4. Buat Jurnal Penutup
            $jurnal = Jurnal::create([
                'no_transaksi' => $noTransaksi,
                'tanggal' => $tanggalTutup,
                'deskripsi' => 'Jurnal Penutup Periode s/d ' . date('d-m-Y', strtotime($tanggalTutup)) . ' (' . ($validated['keterangan'] ?? 'Tutup Buku') . ')',
                'sumber_jurnal' => 'Jurnal Penutup',
                'is_locked' => 1, // Kunci jurnal penutup ini
            ]);

            // 5. Masukkan detail penihilan akun Laba Rugi
            foreach ($detailJurnals as $detail) {
                JurnalDetail::create([
                    'id_jurnal' => $jurnal->id_jurnal,
                    'kode_akun' => $detail['kode_akun'],
                    'debit' => $detail['debit'],
                    'kredit' => $detail['kredit'],
                ]);
            }

            // 6. Masukkan detail pemindahan Laba/Rugi Bersih ke Laba Ditahan
            if ($labaRugiBersih > 0) {
                // Laba Bersih: Kredit Laba Ditahan (Kredit bertambah)
                JurnalDetail::create([
                    'id_jurnal' => $jurnal->id_jurnal,
                    'kode_akun' => $akunLabaDitahan->kode_akun,
                    'debit' => 0,
                    'kredit' => $labaRugiBersih,
                ]);
            } elseif ($labaRugiBersih < 0) {
                // Rugi Bersih: Debit Laba Ditahan (Kredit berkurang / Debit bertambah)
                JurnalDetail::create([
                    'id_jurnal' => $jurnal->id_jurnal,
                    'kode_akun' => $akunLabaDitahan->kode_akun,
                    'debit' => abs($labaRugiBersih),
                    'kredit' => 0,
                ]);
            }

            // 7. Simpan log Tutup Buku
            TutupBuku::create([
                'tanggal_tutup' => $tanggalTutup,
                'id_jurnal_penutup' => $jurnal->id_jurnal,
                'user_id' => Auth::id(),
                'keterangan' => $validated['keterangan'] ?? 'Proses Tutup Buku',
            ]);

            DB::commit();

            return redirect()->route('tutup-buku.index')
                ->with('success', 'Tutup Buku akhir periode s/d ' . date('d-m-Y', strtotime($tanggalTutup)) . ' berhasil diselesaikan. Seluruh transaksi pada periode ini telah dikunci.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal memproses tutup buku: ' . $e->getMessage());
        }
    }

    /**
     * Membatalkan Tutup Buku terakhir (Membuka kembali periode)
     */
    public function cancelPeriod($id)
    {
        $closeLog = TutupBuku::findOrFail($id);

        // Hanya boleh membatalkan tutup buku paling akhir (LIFO)
        $latestClose = TutupBuku::orderBy('tanggal_tutup', 'desc')->first();
        if ($closeLog->id !== $latestClose->id) {
            return back()->with('error', 'Hanya penutupan buku paling akhir yang dapat dibatalkan.');
        }

        try {
            DB::beginTransaction();

            // Hapus Jurnal Penutup terkait
            if ($closeLog->id_jurnal_penutup) {
                $jurnal = Jurnal::find($closeLog->id_jurnal_penutup);
                if ($jurnal) {
                    // Pastikan is_locked dinonaktifkan sementara agar bisa dihapus
                    $jurnal->update(['is_locked' => 0]);
                    $jurnal->delete(); // Cascading delete will remove details
                }
            }

            // Hapus log tutup buku
            $closeLog->delete();

            DB::commit();

            return redirect()->route('tutup-buku.index')
                ->with('success', 'Penutupan buku berhasil dibatalkan. Periode transaksi kembali dibuka.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal membatalkan tutup buku: ' . $e->getMessage());
        }
    }
}
