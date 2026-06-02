<?php

namespace App\Http\Controllers;

use App\Models\Akun;
use App\Models\JurnalDetail;
use Illuminate\Http\Request;

class BukuBesarController extends Controller
{
    public function index(Request $request)
    {
        $akunList = Akun::orderBy('kode_akun')->get();
        
        $kodeAkun = $request->input('kode_akun');
        $startDate = $request->input('start_date', date('Y-m-01'));
        $endDate = $request->input('end_date', date('Y-m-d'));

        $transaksi = collect([]);
        $saldoAwal = 0;
        $selectedAkun = null;

        if ($kodeAkun) {
            $selectedAkun = Akun::find($kodeAkun);
            
            // Hitung Saldo Awal (Transaksi sebelum start_date)
            // Note: Ini simplifikasi. Idealnya ada tabel saldo_awal_periode atau hitung dari awal tahun.
            // Untuk sekarang kita hitung semua transaksi sebelum start_date.
            
            $prevTrans = JurnalDetail::where('kode_akun', $kodeAkun)
                ->whereHas('jurnal', function ($q) use ($startDate) {
                    $q->where('tanggal', '<', $startDate);
                })
                ->get();

            $debitAwal = $prevTrans->sum('debit');
            $kreditAwal = $prevTrans->sum('kredit');

            if ($selectedAkun->saldo_normal == 'Debit') {
                $saldoAwal = $debitAwal - $kreditAwal;
            } else {
                $saldoAwal = $kreditAwal - $debitAwal;
            }

            // Ambil Transaksi Periode Ini
            $transaksi = JurnalDetail::with('jurnal')
                ->where('kode_akun', $kodeAkun)
                ->whereHas('jurnal', function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('tanggal', [$startDate, $endDate]);
                })
                ->get()
                ->sortBy(function($detail) {
                    return $detail->jurnal->tanggal . $detail->jurnal->created_at;
                });
        }

        return view('bukubesar.index', compact('akunList', 'transaksi', 'saldoAwal', 'selectedAkun', 'startDate', 'endDate', 'kodeAkun'));
    }

    public function exportPdf(Request $request)
    {
        $kodeAkun = $request->input('kode_akun');
        $startDate = $request->input('start_date', date('Y-m-01'));
        $endDate = $request->input('end_date', date('Y-m-d'));

        if (!$kodeAkun) {
            return back()->with('error', 'Silakan pilih akun terlebih dahulu.');
        }

        $selectedAkun = Akun::findOrFail($kodeAkun);
        $perusahaan = \Illuminate\Support\Facades\DB::table('perusahaan')->find(1);

        $prevTrans = JurnalDetail::where('kode_akun', $kodeAkun)
            ->whereHas('jurnal', function ($q) use ($startDate) {
                $q->where('tanggal', '<', $startDate);
            })->get();

        $debitAwal = $prevTrans->sum('debit');
        $kreditAwal = $prevTrans->sum('kredit');
        $saldoAwal = ($selectedAkun->saldo_normal == 'Debit') ? $debitAwal - $kreditAwal : $kreditAwal - $debitAwal;

        $transaksi = JurnalDetail::with('jurnal')
            ->where('kode_akun', $kodeAkun)
            ->whereHas('jurnal', function ($q) use ($startDate, $endDate) {
                $q->whereBetween('tanggal', [$startDate, $endDate]);
            })
            ->get()
            ->sortBy(function($detail) {
                return $detail->jurnal->tanggal . $detail->jurnal->created_at;
            });

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('laporan.pdf.bukubesar', compact('perusahaan', 'transaksi', 'saldoAwal', 'selectedAkun', 'startDate', 'endDate'));
        $pdf->setPaper('a4', 'portrait');

        return $pdf->download('buku_besar_' . $kodeAkun . '_' . $startDate . '_' . $endDate . '.pdf');
    }

    public function exportExcel(Request $request)
    {
        $kodeAkun = $request->input('kode_akun');
        $startDate = $request->input('start_date', date('Y-m-01'));
        $endDate = $request->input('end_date', date('Y-m-d'));

        if (!$kodeAkun) {
            return back()->with('error', 'Silakan pilih akun terlebih dahulu.');
        }

        $selectedAkun = Akun::findOrFail($kodeAkun);

        $prevTrans = JurnalDetail::where('kode_akun', $kodeAkun)
            ->whereHas('jurnal', function ($q) use ($startDate) {
                $q->where('tanggal', '<', $startDate);
            })->get();

        $debitAwal = $prevTrans->sum('debit');
        $kreditAwal = $prevTrans->sum('kredit');
        $saldoAwal = ($selectedAkun->saldo_normal == 'Debit') ? $debitAwal - $kreditAwal : $kreditAwal - $debitAwal;

        $transaksi = JurnalDetail::with('jurnal')
            ->where('kode_akun', $kodeAkun)
            ->whereHas('jurnal', function ($q) use ($startDate, $endDate) {
                $q->whereBetween('tanggal', [$startDate, $endDate]);
            })
            ->get()
            ->sortBy(function($detail) {
                return $detail->jurnal->tanggal . $detail->jurnal->created_at;
            });

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        $sheet->setCellValue('A1', 'LAPORAN BUKU BESAR');
        $sheet->setCellValue('A2', 'Akun: ' . $selectedAkun->kode_akun . ' - ' . $selectedAkun->nama_akun);
        $sheet->setCellValue('A3', 'Periode: ' . $startDate . ' s/d ' . $endDate);

        $sheet->setCellValue('A5', 'Tanggal');
        $sheet->setCellValue('B5', 'No. Transaksi');
        $sheet->setCellValue('C5', 'Keterangan');
        $sheet->setCellValue('D5', 'Debit');
        $sheet->setCellValue('E5', 'Kredit');
        $sheet->setCellValue('F5', 'Saldo');

        // Saldo Awal row
        $sheet->setCellValue('A6', $startDate);
        $sheet->setCellValue('C6', 'Saldo Awal');
        $sheet->setCellValue('F6', $saldoAwal);

        $rowNum = 7;
        $runningSaldo = $saldoAwal;
        foreach ($transaksi as $t) {
            if ($selectedAkun->saldo_normal == 'Debit') {
                $runningSaldo += ($t->debit - $t->kredit);
            } else {
                $runningSaldo += ($t->kredit - $t->debit);
            }

            $sheet->setCellValue('A' . $rowNum, $t->jurnal->tanggal->format('Y-m-d'));
            $sheet->setCellValue('B' . $rowNum, $t->jurnal->no_transaksi);
            $sheet->setCellValue('C' . $rowNum, $t->jurnal->deskripsi);
            $sheet->setCellValue('D' . $rowNum, $t->debit);
            $sheet->setCellValue('E' . $rowNum, $t->kredit);
            $sheet->setCellValue('F' . $rowNum, $runningSaldo);
            $rowNum++;
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        
        return response()->streamDownload(function() use ($writer) {
            $writer->save('php://output');
        }, 'buku_besar_' . $kodeAkun . '_' . $startDate . '_' . $endDate . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
