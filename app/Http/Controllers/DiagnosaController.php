<?php

namespace App\Http\Controllers;

use App\Models\Akun;
use App\Models\Jurnal;
use App\Models\JurnalDetail;
use App\Http\Controllers\LaporanController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DiagnosaController extends Controller
{
    /**
     * Tampilkan halaman diagnosa pembukuan
     */
    public function index(Request $request)
    {
        $perTanggal = $request->input('per_tanggal', date('Y-m-d'));

        // 1. Hitung total Aktiva vs Pasiva saat ini untuk mendeteksi selisih
        // Kita panggil LaporanController secara internal untuk konsistensi perhitungan saldo
        $laporanController = new LaporanController();
        
        $akunNeraca = Akun::whereIn('tipe_akun', [
            'Kas & Bank',
            'Piutang',
            'Persediaan',
            'Aset Lancar Lainnya',
            'Aset Tetap',
            'Utang Usaha',
            'Kewajiban Lancar Lainnya',
            'Kewajiban Jangka Panjang',
            'Ekuitas'
        ])->get();

        // Kueri agregasi tunggal untuk mendapatkan seluruh saldo
        $saldos = JurnalDetail::whereHas('jurnal', function ($q) use ($perTanggal) {
                $q->where('tanggal', '<=', $perTanggal . ' 23:59:59');
            })
            ->select('kode_akun', DB::raw('SUM(debit) as total_debit'), DB::raw('SUM(kredit) as total_kredit'))
            ->groupBy('kode_akun')
            ->get()
            ->keyBy('kode_akun');

        $laporan = $akunNeraca->map(function ($akun) use ($saldos) {
            $saldo = $saldos->get($akun->kode_akun);
            $totalDebit = $saldo->total_debit ?? 0;
            $totalKredit = $saldo->total_kredit ?? 0;

            return [
                'kode' => $akun->kode_akun,
                'nama' => $akun->nama_akun,
                'tipe' => $akun->tipe_akun,
                'saldo' => $akun->saldo_normal == 'Debit' ? $totalDebit - $totalKredit : $totalKredit - $totalDebit,
            ];
        });

        $aktivaLancar = $laporan->whereIn('tipe', ['Kas & Bank', 'Piutang', 'Persediaan', 'Aset Lancar Lainnya'])->sum('saldo');
        $aktivaTetap = $laporan->where('tipe', 'Aset Tetap')->sum('saldo');
        $totalAktiva = $aktivaLancar + $aktivaTetap;

        $totalKewajiban = $laporan->whereIn('tipe', ['Utang Usaha', 'Kewajiban Lancar Lainnya', 'Kewajiban Jangka Panjang'])->sum('saldo');
        $totalModal = $laporan->where('tipe', 'Ekuitas')->sum('saldo');
        
        // Panggil hitung Laba Rugi Berjalan secara dinamis
        $hitungLabaRugi = function ($perTanggal) {
            $pendapatan = JurnalDetail::whereHas('akun', function ($q) {
                $q->whereIn('tipe_akun', ['Pendapatan', 'Pendapatan Lainnya']);
            })
                ->whereHas('jurnal', function ($q) use ($perTanggal) {
                    $q->where('tanggal', '<=', $perTanggal . ' 23:59:59');
                })
                ->sum(DB::raw('kredit - debit'));

            $beban = JurnalDetail::whereHas('akun', function ($q) {
                $q->whereIn('tipe_akun', ['HPP', 'Beban', 'Beban Lainnya']);
            })
                ->whereHas('jurnal', function ($q) use ($perTanggal) {
                    $q->where('tanggal', '<=', $perTanggal . ' 23:59:59');
                })
                ->sum(DB::raw('debit - kredit'));

            return $pendapatan - $beban;
        };

        $labaBersih = $hitungLabaRugi($perTanggal);
        $totalPasiva = $totalKewajiban + $totalModal + $labaBersih;

        $selisih = abs($totalAktiva - $totalPasiva);
        $isBalanced = ($selisih < 0.01);

        // 2. DIAGNOSA 1: Cari Jurnal yang TIDAK SEIMBANG (Debit != Kredit)
        $unbalancedDetails = JurnalDetail::select('id_jurnal', DB::raw('SUM(debit) as total_debit'), DB::raw('SUM(kredit) as total_kredit'))
            ->groupBy('id_jurnal')
            ->havingRaw('ABS(SUM(debit) - SUM(kredit)) > 0.009')
            ->get();

        $unbalancedJurnals = [];
        foreach ($unbalancedDetails as $detail) {
            $jurnal = Jurnal::find($detail->id_jurnal);
            if ($jurnal) {
                $jurnal->total_debit = $detail->total_debit;
                $jurnal->total_kredit = $detail->total_kredit;
                $jurnal->selisih = abs($detail->total_debit - $detail->total_kredit);
                $unbalancedJurnals[] = $jurnal;
            }
        }

        // 3. DIAGNOSA 2: Cari Detail Jurnal dengan Kode Akun yang tidak valid / tidak ada di COA
        $invalidAccountDetails = JurnalDetail::whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('akun')
                    ->whereColumn('akun.kode_akun', 'jurnal_detail.kode_akun');
            })
            ->with('jurnal')
            ->get();

        // 4. DIAGNOSA 3: Cari Jurnal kosong (tidak memiliki detail)
        $emptyJurnals = Jurnal::whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('jurnal_detail')
                    ->whereColumn('jurnal_detail.id_jurnal', 'jurnal_umum.id_jurnal');
            })
            ->get();

        return view('diagnosa.index', compact(
            'perTanggal',
            'totalAktiva',
            'totalPasiva',
            'selisih',
            'isBalanced',
            'unbalancedJurnals',
            'invalidAccountDetails',
            'emptyJurnals'
        ));
    }
}
