<?php

namespace App\Http\Controllers;

use App\Models\Akun;
use App\Models\JurnalDetail;
use App\Models\Persediaan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class LaporanController extends Controller
{
    public function index()
    {
        return view('laporan.index');
    }

    public function neraca(Request $request)
    {
        $perTanggal = $request->input('per_tanggal', date('Y-m-d'));
        $bandingTanggal = $request->input('banding_tanggal'); // Tanggal pembanding (opsional)

        $perusahaan = DB::table('perusahaan')->find(1);

        // Ambil semua akun Neraca
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
        ])->orderBy('kode_akun')->get();

        // Helper untuk hitung saldo per tanggal
        $hitungSaldo = function ($tanggal) use ($akunNeraca) {
            return $akunNeraca->map(function ($akun) use ($tanggal) {
                // Clone akun agar tidak merubah referensi asli saat loop kedua
                $akunClone = clone $akun;

                $saldo = JurnalDetail::where('kode_akun', $akun->kode_akun)
                    ->whereHas('jurnal', function ($q) use ($tanggal) {
                        $q->where('tanggal', '<=', $tanggal);
                    })
                    ->select(DB::raw('SUM(debit) as total_debit'), DB::raw('SUM(kredit) as total_kredit'))
                    ->first();

                $totalDebit = $saldo->total_debit ?? 0;
                $totalKredit = $saldo->total_kredit ?? 0;

                if ($akun->saldo_normal == 'Debit') {
                    $akunClone->saldo_akhir = $totalDebit - $totalKredit;
                } else {
                    $akunClone->saldo_akhir = $totalKredit - $totalDebit;
                }
                return $akunClone;
            });
        };

        // Data Utama
        $laporan = $hitungSaldo($perTanggal);

        // Data Pembanding (jika ada)
        $laporanBanding = $bandingTanggal ? $hitungSaldo($bandingTanggal) : collect([]);

        // Grouping Data Utama
        $asetLancar = $laporan->whereIn('tipe_akun', ['Kas & Bank', 'Piutang', 'Persediaan', 'Aset Lancar Lainnya']);
        $asetTetap = $laporan->where('tipe_akun', 'Aset Tetap');
        $kewajiban = $laporan->whereIn('tipe_akun', ['Utang Usaha', 'Kewajiban Lancar Lainnya', 'Kewajiban Jangka Panjang']);
        $ekuitas = $laporan->where('tipe_akun', 'Ekuitas');

        // Laba Rugi Berjalan
        $labaRugiBerjalan = $this->hitungLabaRugi($perTanggal);
        $labaRugiBerjalanBanding = $bandingTanggal ? $this->hitungLabaRugi($bandingTanggal) : 0;

        return view('laporan.neraca', compact(
            'perusahaan',
            'perTanggal',
            'bandingTanggal',
            'asetLancar',
            'asetTetap',
            'kewajiban',
            'ekuitas',
            'labaRugiBerjalan',
            'labaRugiBerjalanBanding',
            'laporanBanding'
        ));
    }

    public function labaRugi(Request $request)
    {
        $startDate = $request->input('start_date', date('Y-m-01'));
        $endDate = $request->input('end_date', date('Y-m-d'));

        $startBanding = $request->input('start_banding');
        $endBanding = $request->input('end_banding');

        $perusahaan = DB::table('perusahaan')->find(1);

        $akunLabaRugi = Akun::whereIn('tipe_akun', [
            'Pendapatan',
            'Pendapatan Lainnya',
            'HPP',
            'Beban',
            'Beban Lainnya'
        ])->orderBy('kode_akun')->get();

        $hitungPeriode = function ($start, $end) use ($akunLabaRugi) {
            return $akunLabaRugi->map(function ($akun) use ($start, $end) {
                $akunClone = clone $akun;
                $saldo = JurnalDetail::where('kode_akun', $akun->kode_akun)
                    ->whereHas('jurnal', function ($q) use ($start, $end) {
                        $q->whereBetween('tanggal', [$start, $end]);
                    })
                    ->select(DB::raw('SUM(debit) as total_debit'), DB::raw('SUM(kredit) as total_kredit'))
                    ->first();

                $totalDebit = $saldo->total_debit ?? 0;
                $totalKredit = $saldo->total_kredit ?? 0;

                if ($akun->saldo_normal == 'Kredit') {
                    $akunClone->saldo_periode = $totalKredit - $totalDebit;
                } else {
                    $akunClone->saldo_periode = $totalDebit - $totalKredit;
                }
                return $akunClone;
            });
        };

        // Periode Utama
        $laporan = $hitungPeriode($startDate, $endDate);

        // Periode Pembanding
        $laporanBanding = ($startBanding && $endBanding) ? $hitungPeriode($startBanding, $endBanding) : collect([]);

        $pendapatan = $laporan->whereIn('tipe_akun', ['Pendapatan', 'Pendapatan Lainnya']);
        $hpp = $laporan->where('tipe_akun', 'HPP');
        $beban = $laporan->whereIn('tipe_akun', ['Beban', 'Beban Lainnya']);

        return view('laporan.labarugi', compact(
            'perusahaan',
            'startDate',
            'endDate',
            'startBanding',
            'endBanding',
            'pendapatan',
            'hpp',
            'beban',
            'laporanBanding'
        ));
    }

    public function arusKasLangsung(Request $request)
    {
        $startDate = $request->input('start_date', date('Y-m-01'));
        $endDate = $request->input('end_date', date('Y-m-d'));
        $perusahaan = DB::table('perusahaan')->find(1);

        // Helper untuk mendapatkan total arus kas berdasarkan tipe akun lawan
        $getFlow = function ($tipeAkunLawan, $isMasuk) use ($startDate, $endDate) {
            // Cari jurnal detail yang melibatkan Kas & Bank
            // Dan lawan transaksinya adalah tipe akun tertentu

            // Logic:
            // 1. Ambil semua ID Jurnal yang memiliki detail akun Kas & Bank dalam range tanggal
            $jurnalIds = JurnalDetail::whereHas('akun', function ($q) {
                $q->where('tipe_akun', 'Kas & Bank');
            })
                ->whereHas('jurnal', function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('tanggal', [$startDate, $endDate]);
                })
                ->pluck('id_jurnal');

            // 2. Dari ID Jurnal tersebut, cari detail yang BUKAN Kas & Bank (Lawannya)
            // Dan tipe akun lawannya sesuai parameter
            $query = JurnalDetail::whereIn('id_jurnal', $jurnalIds)
                ->whereHas('akun', function ($q) use ($tipeAkunLawan) {
                    if (is_array($tipeAkunLawan)) {
                        $q->whereIn('tipe_akun', $tipeAkunLawan);
                    } else {
                        $q->where('tipe_akun', $tipeAkunLawan);
                    }
                });

            // 3. Jika Arus Masuk (Cash Debit), maka Lawan adalah Kredit.
            // Jika Arus Keluar (Cash Kredit), maka Lawan adalah Debit.
            // Namun, di simple accounting ini, kita bisa sum amount lawannya.
            // Jika isMasuk = true (Penerimaan), kita cari total Kredit dari akun lawan.
            // Jika isMasuk = false (Pengeluaran), kita cari total Debit dari akun lawan.

            if ($isMasuk) {
                return $query->sum('kredit');
            } else {
                return $query->sum('debit');
            }
        };

        // --- AKTIVITAS OPERASI ---
        // Masuk: Dari Pelanggan (Piutang, Pendapatan)
        $terimaPelanggan = $getFlow(['Piutang', 'Pendapatan', 'Pendapatan Lainnya'], true);

        // Keluar: Ke Pemasok (Utang, HPP, Beban, Persediaan, Aset Lancar Lainnya)
        $bayarPemasok = $getFlow(['Utang Usaha', 'HPP', 'Beban', 'Beban Lainnya', 'Kewajiban Lancar Lainnya', 'Persediaan', 'Aset Lancar Lainnya'], false);

        $arusKasOperasi = $terimaPelanggan - $bayarPemasok;

        // --- AKTIVITAS INVESTASI ---
        // Masuk: Jual Aset Tetap
        $jualAset = $getFlow('Aset Tetap', true);
        // Keluar: Beli Aset Tetap
        $beliAset = $getFlow('Aset Tetap', false);

        $arusKasInvestasi = $jualAset - $beliAset;

        // --- AKTIVITAS PENDANAAN ---
        // Masuk: Modal, Utang Jangka Panjang
        $terimaPendanaan = $getFlow(['Ekuitas', 'Kewajiban Jangka Panjang'], true);
        // Keluar: Prive, Bayar Utang Jangka Panjang
        $bayarPendanaan = $getFlow(['Ekuitas', 'Kewajiban Jangka Panjang'], false);

        $arusKasPendanaan = $terimaPendanaan - $bayarPendanaan;

        $kenaikanKas = $arusKasOperasi + $arusKasInvestasi + $arusKasPendanaan;

        // Saldo Awal Kas
        $saldoAwal = JurnalDetail::whereHas('akun', function ($q) {
            $q->where('tipe_akun', 'Kas & Bank');
        })
            ->whereHas('jurnal', function ($q) use ($startDate) {
                $q->where('tanggal', '<', $startDate);
            })
            ->sum(DB::raw('debit - kredit'));

        $saldoAkhir = $saldoAwal + $kenaikanKas;

        return view('laporan.aruskas_langsung', compact(
            'perusahaan',
            'startDate',
            'endDate',
            'terimaPelanggan',
            'bayarPemasok',
            'arusKasOperasi',
            'jualAset',
            'beliAset',
            'arusKasInvestasi',
            'terimaPendanaan',
            'bayarPendanaan',
            'arusKasPendanaan',
            'kenaikanKas',
            'saldoAwal',
            'saldoAkhir'
        ));
    }

    public function arusKasTidakLangsung(Request $request)
    {
        $startDate = $request->input('start_date', date('Y-m-01'));
        $endDate = $request->input('end_date', date('Y-m-d'));
        $perusahaan = DB::table('perusahaan')->find(1);

        // 1. Laba Bersih
        // Hitung Pendapatan - Beban periode ini
        $pendapatan = JurnalDetail::whereHas('akun', function ($q) {
            $q->whereIn('tipe_akun', ['Pendapatan', 'Pendapatan Lainnya']);
        })->whereHas('jurnal', function ($q) use ($startDate, $endDate) {
            $q->whereBetween('tanggal', [$startDate, $endDate]);
        })->sum(DB::raw('kredit - debit'));

        $beban = JurnalDetail::whereHas('akun', function ($q) {
            $q->whereIn('tipe_akun', ['HPP', 'Beban', 'Beban Lainnya']);
        })->whereHas('jurnal', function ($q) use ($startDate, $endDate) {
            $q->whereBetween('tanggal', [$startDate, $endDate]);
        })->sum(DB::raw('debit - kredit'));

        $labaBersih = $pendapatan - $beban;

        // 2. Penyesuaian Non-Kas (Penyusutan)
        // Cari akun beban penyusutan (biasanya ada kata 'Penyusutan' atau 'Depreciation')
        // Untuk simplifikasi, kita ambil semua akun Beban yang namanya mengandung 'Penyusutan'
        $bebanPenyusutan = JurnalDetail::whereHas('akun', function ($q) {
            $q->where('nama_akun', 'like', '%Penyusutan%')
                ->orWhere('nama_akun', 'like', '%Depresiasi%');
        })->whereHas('jurnal', function ($q) use ($startDate, $endDate) {
            $q->whereBetween('tanggal', [$startDate, $endDate]);
        })->sum('debit');

        // 3. Perubahan Modal Kerja
        $getChange = function ($tipeAkun, $saldoNormal) use ($startDate, $endDate) {
            // Hitung selisih saldo akhir - saldo awal periode
            // Saldo Awal
            $awal = JurnalDetail::whereHas('akun', function ($q) use ($tipeAkun) {
                $q->where('tipe_akun', $tipeAkun);
            })->whereHas('jurnal', function ($q) use ($startDate) {
                $q->where('tanggal', '<', $startDate);
            })->sum(DB::raw($saldoNormal == 'Debit' ? 'debit - kredit' : 'kredit - debit'));

            // Saldo Akhir
            $akhir = JurnalDetail::whereHas('akun', function ($q) use ($tipeAkun) {
                $q->where('tipe_akun', $tipeAkun);
            })->whereHas('jurnal', function ($q) use ($endDate) {
                $q->where('tanggal', '<=', $endDate);
            })->sum(DB::raw($saldoNormal == 'Debit' ? 'debit - kredit' : 'kredit - debit'));

            return $akhir - $awal;
        };

        // Kenaikan Piutang (Mengurangi Kas)
        $kenaikanPiutang = $getChange('Piutang', 'Debit');

        // Kenaikan Persediaan (Mengurangi Kas)
        $kenaikanPersediaan = $getChange('Persediaan', 'Debit');

        // Kenaikan Utang Usaha (Menambah Kas)
        $kenaikanUtang = $getChange('Utang Usaha', 'Kredit');

        // Arus Kas Operasi
        // Rumus: Laba Bersih + Penyusutan - Kenaikan Piutang - Kenaikan Persediaan + Kenaikan Utang
        $arusKasOperasi = $labaBersih + $bebanPenyusutan - $kenaikanPiutang - $kenaikanPersediaan + $kenaikanUtang;

        // --- INVESTASI & PENDANAAN (Sama dengan Metode Langsung) ---
        // Kita copy logic getFlow dari metode langsung atau buat private method shared.
        // Untuk cepatnya, kita duplikasi logic querynya di sini tapi disesuaikan.

        $getFlowSimple = function ($tipeAkunLawan, $isMasuk) use ($startDate, $endDate) {
            $jurnalIds = JurnalDetail::whereHas('akun', function ($q) {
                $q->where('tipe_akun', 'Kas & Bank');
            })
                ->whereHas('jurnal', function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('tanggal', [$startDate, $endDate]);
                })
                ->pluck('id_jurnal');

            $query = JurnalDetail::whereIn('id_jurnal', $jurnalIds)
                ->whereHas('akun', function ($q) use ($tipeAkunLawan) {
                    if (is_array($tipeAkunLawan)) {
                        $q->whereIn('tipe_akun', $tipeAkunLawan);
                    } else {
                        $q->where('tipe_akun', $tipeAkunLawan);
                    }
                });

            return $isMasuk ? $query->sum('kredit') : $query->sum('debit');
        };

        $arusKasInvestasi = $getFlowSimple('Aset Tetap', true) - $getFlowSimple('Aset Tetap', false);
        $arusKasPendanaan = $getFlowSimple(['Ekuitas', 'Kewajiban Jangka Panjang'], true) - $getFlowSimple(['Ekuitas', 'Kewajiban Jangka Panjang'], false);

        $kenaikanKas = $arusKasOperasi + $arusKasInvestasi + $arusKasPendanaan;

        // Saldo Awal Kas
        $saldoAwal = JurnalDetail::whereHas('akun', function ($q) {
            $q->where('tipe_akun', 'Kas & Bank');
        })
            ->whereHas('jurnal', function ($q) use ($startDate) {
                $q->where('tanggal', '<', $startDate);
            })
            ->sum(DB::raw('debit - kredit'));

        $saldoAkhir = $saldoAwal + $kenaikanKas;

        return view('laporan.aruskas_tidak_langsung', compact(
            'perusahaan',
            'startDate',
            'endDate',
            'labaBersih',
            'bebanPenyusutan',
            'kenaikanPiutang',
            'kenaikanPersediaan',
            'kenaikanUtang',
            'arusKasOperasi',
            'arusKasInvestasi',
            'arusKasPendanaan',
            'kenaikanKas',
            'saldoAwal',
            'saldoAkhir'
        ));
    }

    public function perubahanEkuitas(Request $request)
    {
        $startDate = $request->input('start_date', date('Y-m-01'));
        $endDate = $request->input('end_date', date('Y-m-d'));
        $perusahaan = DB::table('perusahaan')->find(1);

        // 1. Saldo Awal Ekuitas (Sebelum Start Date)
        // = (Total Kredit - Total Debit Akun Ekuitas) + (Total Pendapatan - Total Beban sebelum periode)

        $saldoAwalAkunEkuitas = JurnalDetail::whereHas('akun', function ($q) {
            $q->where('tipe_akun', 'Ekuitas');
        })
            ->whereHas('jurnal', function ($q) use ($startDate) {
                $q->where('tanggal', '<', $startDate);
            })
            ->sum(DB::raw('kredit - debit'));

        $labaDitahanAwal = $this->hitungLabaRugi(date('Y-m-d', strtotime($startDate . ' -1 day')));

        $saldoAwal = $saldoAwalAkunEkuitas + $labaDitahanAwal;

        // 2. Perubahan Selama Periode
        // Laba Bersih Periode
        $labaBersih = $this->hitungLabaRugiPeriode($startDate, $endDate);

        // Setoran Modal (Kredit ke Ekuitas selama periode)
        $setoranModal = JurnalDetail::whereHas('akun', function ($q) {
            $q->where('tipe_akun', 'Ekuitas');
        })
            ->whereHas('jurnal', function ($q) use ($startDate, $endDate) {
                $q->whereBetween('tanggal', [$startDate, $endDate]);
            })
            ->sum('kredit');

        // Prive / Penarikan (Debit ke Ekuitas selama periode)
        $prive = JurnalDetail::whereHas('akun', function ($q) {
            $q->where('tipe_akun', 'Ekuitas');
        })
            ->whereHas('jurnal', function ($q) use ($startDate, $endDate) {
                $q->whereBetween('tanggal', [$startDate, $endDate]);
            })
            ->sum('debit');

        // Saldo Akhir
        // Saldo Awal + Laba Bersih + Setoran - Prive
        // Note: $setoranModal dan $prive diambil dari mutasi akun Ekuitas.
        // Jika akun Ekuitas bertambah di Kredit (Setoran) dan berkurang di Debit (Prive).
        // Namun, hitungLabaRugiPeriode sudah menghitung revenue-expense.
        // Jadi kita hanya perlu mutasi di akun Ekuitas murni.

        $saldoAkhir = $saldoAwal + $labaBersih + $setoranModal - $prive;

        return view('laporan.perubahan_ekuitas', compact(
            'perusahaan',
            'startDate',
            'endDate',
            'saldoAwal',
            'labaBersih',
            'setoranModal',
            'prive',
            'saldoAkhir'
        ));
    }

    private function hitungLabaRugiPeriode($startDate, $endDate)
    {
        $pendapatan = JurnalDetail::whereHas('akun', function ($q) {
            $q->whereIn('tipe_akun', ['Pendapatan', 'Pendapatan Lainnya']);
        })
            ->whereHas('jurnal', function ($q) use ($startDate, $endDate) {
                $q->whereBetween('tanggal', [$startDate, $endDate]);
            })
            ->sum(DB::raw('kredit - debit'));

        $beban = JurnalDetail::whereHas('akun', function ($q) {
            $q->whereIn('tipe_akun', ['HPP', 'Beban', 'Beban Lainnya']);
        })
            ->whereHas('jurnal', function ($q) use ($startDate, $endDate) {
                $q->whereBetween('tanggal', [$startDate, $endDate]);
            })
            ->sum(DB::raw('debit - kredit'));

        return $pendapatan - $beban;
    }

    private function hitungLabaRugi($perTanggal)
    {
        $pendapatan = JurnalDetail::whereHas('akun', function ($q) {
            $q->whereIn('tipe_akun', ['Pendapatan', 'Pendapatan Lainnya']);
        })
            ->whereHas('jurnal', function ($q) use ($perTanggal) {
                $q->where('tanggal', '<=', $perTanggal);
            })
            ->sum(DB::raw('kredit - debit'));

        $beban = JurnalDetail::whereHas('akun', function ($q) {
            $q->whereIn('tipe_akun', ['HPP', 'Beban', 'Beban Lainnya']);
        })
            ->whereHas('jurnal', function ($q) use ($perTanggal) {
                $q->where('tanggal', '<=', $perTanggal);
            })
            ->sum(DB::raw('debit - kredit'));

        return $pendapatan - $beban;
    }

    public function persediaan()
    {
        $perusahaan = DB::table('perusahaan')->find(1);
        $persediaan = Persediaan::orderBy('nama_barang')->get();

        // Hitung total nilai persediaan
        $totalNilai = $persediaan->sum(function ($item) {
            return $item->stok_saat_ini * $item->harga_beli;
        });

        return view('laporan.persediaan', compact('perusahaan', 'persediaan', 'totalNilai'));
    }

    /**
     * Export Neraca to PDF
     */
    public function neracaPdf(Request $request)
    {
        $perTanggal = $request->input('per_tanggal', date('Y-m-d'));
        $perusahaan = DB::table('perusahaan')->find(1);

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
        ])->orderBy('kode_akun')->get();

        $laporan = $akunNeraca->map(function ($akun) use ($perTanggal) {
            $saldo = JurnalDetail::where('kode_akun', $akun->kode_akun)
                ->whereHas('jurnal', function ($q) use ($perTanggal) {
                    $q->where('tanggal', '<=', $perTanggal);
                })
                ->select(DB::raw('SUM(debit) as total_debit'), DB::raw('SUM(kredit) as total_kredit'))
                ->first();

            $totalDebit = $saldo->total_debit ?? 0;
            $totalKredit = $saldo->total_kredit ?? 0;

            return [
                'kode' => $akun->kode_akun,
                'nama' => $akun->nama_akun,
                'tipe' => $akun->tipe_akun,
                'saldo' => $akun->saldo_normal == 'Debit' ? $totalDebit - $totalKredit : $totalKredit - $totalDebit,
            ];
        });

        $aktivaLancar = $laporan->whereIn('tipe', ['Kas & Bank', 'Piutang', 'Persediaan', 'Aset Lancar Lainnya'])->values();
        $aktivaTetap = $laporan->where('tipe', 'Aset Tetap')->values();
        $kewajiban = $laporan->whereIn('tipe', ['Utang Usaha', 'Kewajiban Lancar Lainnya', 'Kewajiban Jangka Panjang'])->values();
        $modal = $laporan->where('tipe', 'Ekuitas')->values();

        $totalAktivaLancar = $aktivaLancar->sum('saldo');
        $totalAktivaTetap = $aktivaTetap->sum('saldo');
        $totalAktiva = $totalAktivaLancar + $totalAktivaTetap;

        $totalKewajiban = $kewajiban->sum('saldo');
        $totalModal = $modal->sum('saldo');
        $labaBersih = $this->hitungLabaRugi($perTanggal);
        $totalPasiva = $totalKewajiban + $totalModal + $labaBersih;

        $data = [
            'title' => 'LAPORAN NERACA',
            'subtitle' => 'Per Tanggal ' . date('d F Y', strtotime($perTanggal)),
            'perusahaan' => $perusahaan,
            'aktivaLancar' => $aktivaLancar,
            'aktivaTetap' => $aktivaTetap,
            'kewajiban' => $kewajiban,
            'modal' => $modal,
            'totalAktivaLancar' => $totalAktivaLancar,
            'totalAktivaTetap' => $totalAktivaTetap,
            'totalAktiva' => $totalAktiva,
            'totalKewajiban' => $totalKewajiban,
            'totalModal' => $totalModal,
            'labaBersih' => $labaBersih,
            'totalPasiva' => $totalPasiva,
            'showSignatures' => true,
            'tanggal' => date('d F Y', strtotime($perTanggal)),
        ];

        $pdf = Pdf::loadView('laporan.pdf.neraca', $data);
        $pdf->setPaper('a4', 'portrait');

        return $pdf->download('neraca_' . $perTanggal . '.pdf');
    }

    /**
     * Export Laba Rugi to PDF
     */
    public function labaRugiPdf(Request $request)
    {
        $startDate = $request->input('start_date', date('Y-m-01'));
        $endDate = $request->input('end_date', date('Y-m-d'));
        $perusahaan = DB::table('perusahaan')->find(1);

        $akunLabaRugi = Akun::whereIn('tipe_akun', [
            'Pendapatan',
            'Pendapatan Lainnya',
            'HPP',
            'Beban',
            'Beban Lainnya'
        ])->orderBy('kode_akun')->get();

        $laporan = $akunLabaRugi->map(function ($akun) use ($startDate, $endDate) {
            $saldo = JurnalDetail::where('kode_akun', $akun->kode_akun)
                ->whereHas('jurnal', function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('tanggal', [$startDate, $endDate]);
                })
                ->select(DB::raw('SUM(debit) as total_debit'), DB::raw('SUM(kredit) as total_kredit'))
                ->first();

            $totalDebit = $saldo->total_debit ?? 0;
            $totalKredit = $saldo->total_kredit ?? 0;

            return [
                'kode' => $akun->kode_akun,
                'nama' => $akun->nama_akun,
                'tipe' => $akun->tipe_akun,
                'saldo' => $akun->saldo_normal == 'Kredit' ? $totalKredit - $totalDebit : $totalDebit - $totalKredit,
            ];
        });

        $pendapatan = $laporan->whereIn('tipe', ['Pendapatan'])->values();
        $hpp = $laporan->where('tipe', 'HPP')->values();
        $beban = $laporan->where('tipe', 'Beban')->values();
        $pendapatanLain = $laporan->where('tipe', 'Pendapatan Lainnya')->values();
        $bebanLain = $laporan->where('tipe', 'Beban Lainnya')->values();

        $totalPendapatan = $pendapatan->sum('saldo');
        $totalHpp = $hpp->sum('saldo');
        $labaKotor = $totalPendapatan - $totalHpp;
        $totalBeban = $beban->sum('saldo');
        $labaOperasional = $labaKotor - $totalBeban;
        $totalPendapatanLain = $pendapatanLain->sum('saldo');
        $totalBebanLain = $bebanLain->sum('saldo');
        $labaBersih = $labaOperasional + $totalPendapatanLain - $totalBebanLain;

        $data = [
            'title' => 'LAPORAN LABA RUGI',
            'subtitle' => 'Periode ' . date('d F Y', strtotime($startDate)) . ' s/d ' . date('d F Y', strtotime($endDate)),
            'perusahaan' => $perusahaan,
            'pendapatan' => $pendapatan,
            'hpp' => $hpp,
            'beban' => $beban,
            'pendapatanLain' => $pendapatanLain,
            'bebanLain' => $bebanLain,
            'totalPendapatan' => $totalPendapatan,
            'totalHpp' => $totalHpp,
            'labaKotor' => $labaKotor,
            'totalBeban' => $totalBeban,
            'labaOperasional' => $labaOperasional,
            'totalPendapatanLain' => $totalPendapatanLain,
            'totalBebanLain' => $totalBebanLain,
            'labaBersih' => $labaBersih,
            'showSignatures' => true,
            'tanggal' => date('d F Y', strtotime($endDate)),
        ];

        $pdf = Pdf::loadView('laporan.pdf.labarugi', $data);
        $pdf->setPaper('a4', 'portrait');

        return $pdf->download('laba_rugi_' . $startDate . '_' . $endDate . '.pdf');
    }

    /**
     * Laporan Laba Rugi Per Proyek
     */
    public function labaRugiProyek(Request $request)
    {
        $startDate = $request->input('start_date', date('Y-m-01'));
        $endDate = $request->input('end_date', date('Y-m-d'));
        $idProyek = $request->input('id_proyek');

        $perusahaan = DB::table('perusahaan')->find(1);
        $proyeks = \App\Models\Proyek::orderBy('kode_proyek')->get();
        $proyek = $idProyek ? \App\Models\Proyek::find($idProyek) : null;

        $akunLabaRugi = Akun::whereIn('tipe_akun', [
            'Pendapatan',
            'Pendapatan Lainnya',
            'HPP',
            'Beban',
            'Beban Lainnya'
        ])->orderBy('kode_akun')->get();

        $laporan = collect();

        if ($idProyek) {
            $laporan = $akunLabaRugi->map(function ($akun) use ($startDate, $endDate, $idProyek) {
                $akunClone = clone $akun;
                $saldo = JurnalDetail::where('kode_akun', $akun->kode_akun)
                    ->where('id_proyek', $idProyek)
                    ->whereHas('jurnal', function ($q) use ($startDate, $endDate) {
                        $q->whereBetween('tanggal', [$startDate, $endDate]);
                    })
                    ->select(DB::raw('SUM(debit) as total_debit'), DB::raw('SUM(kredit) as total_kredit'))
                    ->first();

                $totalDebit = $saldo->total_debit ?? 0;
                $totalKredit = $saldo->total_kredit ?? 0;

                if ($akun->saldo_normal == 'Kredit') {
                    $akunClone->saldo_periode = $totalKredit - $totalDebit;
                } else {
                    $akunClone->saldo_periode = $totalDebit - $totalKredit;
                }
                return $akunClone;
            });
        }

        $pendapatan = $laporan->whereIn('tipe_akun', ['Pendapatan', 'Pendapatan Lainnya']);
        $hpp = $laporan->where('tipe_akun', 'HPP');
        $beban = $laporan->whereIn('tipe_akun', ['Beban', 'Beban Lainnya']);

        return view('laporan.labarugi_proyek', compact(
            'perusahaan',
            'startDate',
            'endDate',
            'proyeks',
            'proyek',
            'idProyek',
            'pendapatan',
            'hpp',
            'beban'
        ));
    }

    /**
     * Laporan Laba Rugi Konsolidasi (Semua Proyek)
     */
    public function labaRugiKonsolidasi(Request $request)
    {
        $startDate = $request->input('start_date', date('Y-m-01'));
        $endDate = $request->input('end_date', date('Y-m-d'));

        $perusahaan = DB::table('perusahaan')->find(1);
        $proyeks = \App\Models\Proyek::orderBy('kode_proyek')->get();

        $akunLabaRugi = Akun::whereIn('tipe_akun', [
            'Pendapatan',
            'Pendapatan Lainnya',
            'HPP',
            'Beban',
            'Beban Lainnya'
        ])->orderBy('kode_akun')->get();

        // Proses setiap akun untuk setiap proyek
        $laporanData = [];

        foreach ($akunLabaRugi as $akun) {
            $row = [
                'kode_akun' => $akun->kode_akun,
                'nama_akun' => $akun->nama_akun,
                'tipe_akun' => $akun->tipe_akun,
                'saldo_normal' => $akun->saldo_normal,
                'proyek' => [],
                'non_proyek' => 0,
                'total' => 0,
            ];

            // Hitung untuk setiap proyek
            foreach ($proyeks as $proyek) {
                $saldo = JurnalDetail::where('kode_akun', $akun->kode_akun)
                    ->where('id_proyek', $proyek->id_proyek)
                    ->whereHas('jurnal', function ($q) use ($startDate, $endDate) {
                        $q->whereBetween('tanggal', [$startDate, $endDate]);
                    })
                    ->select(DB::raw('SUM(debit) as total_debit'), DB::raw('SUM(kredit) as total_kredit'))
                    ->first();

                $totalDebit = $saldo->total_debit ?? 0;
                $totalKredit = $saldo->total_kredit ?? 0;

                if ($akun->saldo_normal == 'Kredit') {
                    $nilai = $totalKredit - $totalDebit;
                } else {
                    $nilai = $totalDebit - $totalKredit;
                }

                $row['proyek'][$proyek->id_proyek] = $nilai;
            }

            // Hitung transaksi tanpa proyek
            $saldoNonProyek = JurnalDetail::where('kode_akun', $akun->kode_akun)
                ->whereNull('id_proyek')
                ->whereHas('jurnal', function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('tanggal', [$startDate, $endDate]);
                })
                ->select(DB::raw('SUM(debit) as total_debit'), DB::raw('SUM(kredit) as total_kredit'))
                ->first();

            $totalDebitNP = $saldoNonProyek->total_debit ?? 0;
            $totalKreditNP = $saldoNonProyek->total_kredit ?? 0;

            if ($akun->saldo_normal == 'Kredit') {
                $row['non_proyek'] = $totalKreditNP - $totalDebitNP;
            } else {
                $row['non_proyek'] = $totalDebitNP - $totalKreditNP;
            }

            // Total semua kolom
            $row['total'] = array_sum($row['proyek']) + $row['non_proyek'];

            $laporanData[] = $row;
        }

        $laporan = collect($laporanData);

        // Kelompokkan data
        $pendapatan = $laporan->whereIn('tipe_akun', ['Pendapatan', 'Pendapatan Lainnya']);
        $hpp = $laporan->where('tipe_akun', 'HPP');
        $beban = $laporan->whereIn('tipe_akun', ['Beban', 'Beban Lainnya']);

        // Hitung total per proyek untuk summary
        $summaryProyek = [];
        foreach ($proyeks as $proyek) {
            $totalPendapatan = $pendapatan->sum(fn($row) => $row['proyek'][$proyek->id_proyek] ?? 0);
            $totalHpp = $hpp->sum(fn($row) => $row['proyek'][$proyek->id_proyek] ?? 0);
            $totalBeban = $beban->sum(fn($row) => $row['proyek'][$proyek->id_proyek] ?? 0);

            $summaryProyek[$proyek->id_proyek] = [
                'pendapatan' => $totalPendapatan,
                'hpp' => $totalHpp,
                'beban' => $totalBeban,
                'laba_kotor' => $totalPendapatan - $totalHpp,
                'laba_bersih' => $totalPendapatan - $totalHpp - $totalBeban,
            ];
        }

        // Summary non-proyek
        $summaryNonProyek = [
            'pendapatan' => $pendapatan->sum('non_proyek'),
            'hpp' => $hpp->sum('non_proyek'),
            'beban' => $beban->sum('non_proyek'),
        ];
        $summaryNonProyek['laba_kotor'] = $summaryNonProyek['pendapatan'] - $summaryNonProyek['hpp'];
        $summaryNonProyek['laba_bersih'] = $summaryNonProyek['laba_kotor'] - $summaryNonProyek['beban'];

        // Summary total
        $summaryTotal = [
            'pendapatan' => $pendapatan->sum('total'),
            'hpp' => $hpp->sum('total'),
            'beban' => $beban->sum('total'),
        ];
        $summaryTotal['laba_kotor'] = $summaryTotal['pendapatan'] - $summaryTotal['hpp'];
        $summaryTotal['laba_bersih'] = $summaryTotal['laba_kotor'] - $summaryTotal['beban'];

        return view('laporan.labarugi_konsolidasi', compact(
            'perusahaan',
            'startDate',
            'endDate',
            'proyeks',
            'pendapatan',
            'hpp',
            'beban',
            'summaryProyek',
            'summaryNonProyek',
            'summaryTotal'
        ));
    }

    /**
     * Laporan Arus Kas Per Proyek (Metode Langsung)
     */
    public function arusKasProyek(Request $request)
    {
        $startDate = $request->input('start_date', date('Y-m-01'));
        $endDate = $request->input('end_date', date('Y-m-d'));
        $idProyek = $request->input('id_proyek');

        $perusahaan = DB::table('perusahaan')->find(1);
        $proyeks = \App\Models\Proyek::orderBy('kode_proyek')->get();
        $proyek = $idProyek ? \App\Models\Proyek::find($idProyek) : null;

        // Initialize values
        $terimaPelanggan = 0;
        $bayarPemasok = 0;
        $jualAset = 0;
        $beliAset = 0;
        $terimaPendanaan = 0;
        $bayarPendanaan = 0;
        $saldoAwal = 0;

        if ($idProyek) {
            // Helper untuk mendapatkan total arus kas berdasarkan tipe akun lawan
            $getFlow = function ($tipeAkunLawan, $isMasuk) use ($startDate, $endDate, $idProyek) {
                // Get jurnal IDs yang memiliki detail dengan Kas & Bank DAN id_proyek tertentu
                $jurnalIds = JurnalDetail::whereHas('akun', function ($q) {
                    $q->where('tipe_akun', 'Kas & Bank');
                })
                    ->where('id_proyek', $idProyek)
                    ->whereHas('jurnal', function ($q) use ($startDate, $endDate) {
                        $q->whereBetween('tanggal', [$startDate, $endDate]);
                    })
                    ->pluck('id_jurnal');

                $query = JurnalDetail::whereIn('id_jurnal', $jurnalIds)
                    ->where('id_proyek', $idProyek)
                    ->whereHas('akun', function ($q) use ($tipeAkunLawan) {
                        if (is_array($tipeAkunLawan)) {
                            $q->whereIn('tipe_akun', $tipeAkunLawan);
                        } else {
                            $q->where('tipe_akun', $tipeAkunLawan);
                        }
                    });

                return $isMasuk ? $query->sum('kredit') : $query->sum('debit');
            };

            // --- AKTIVITAS OPERASI ---
            $terimaPelanggan = $getFlow(['Piutang', 'Pendapatan', 'Pendapatan Lainnya'], true);
            $bayarPemasok = $getFlow(['Utang Usaha', 'HPP', 'Beban', 'Beban Lainnya', 'Kewajiban Lancar Lainnya', 'Persediaan', 'Aset Lancar Lainnya'], false);

            // --- AKTIVITAS INVESTASI ---
            $jualAset = $getFlow('Aset Tetap', true);
            $beliAset = $getFlow('Aset Tetap', false);

            // --- AKTIVITAS PENDANAAN ---
            $terimaPendanaan = $getFlow(['Ekuitas', 'Kewajiban Jangka Panjang'], true);
            $bayarPendanaan = $getFlow(['Ekuitas', 'Kewajiban Jangka Panjang'], false);

            // Saldo Awal Kas untuk proyek ini
            $saldoAwal = JurnalDetail::whereHas('akun', function ($q) {
                $q->where('tipe_akun', 'Kas & Bank');
            })
                ->where('id_proyek', $idProyek)
                ->whereHas('jurnal', function ($q) use ($startDate) {
                    $q->where('tanggal', '<', $startDate);
                })
                ->sum(DB::raw('debit - kredit'));
        }

        $arusKasOperasi = $terimaPelanggan - $bayarPemasok;
        $arusKasInvestasi = $jualAset - $beliAset;
        $arusKasPendanaan = $terimaPendanaan - $bayarPendanaan;
        $kenaikanKas = $arusKasOperasi + $arusKasInvestasi + $arusKasPendanaan;
        $saldoAkhir = $saldoAwal + $kenaikanKas;

        return view('laporan.aruskas_proyek', compact(
            'perusahaan', 'startDate', 'endDate', 'proyeks', 'proyek', 'idProyek',
            'terimaPelanggan', 'bayarPemasok', 'arusKasOperasi',
            'jualAset', 'beliAset', 'arusKasInvestasi',
            'terimaPendanaan', 'bayarPendanaan', 'arusKasPendanaan',
            'kenaikanKas', 'saldoAwal', 'saldoAkhir'
        ));
    }

    /**
     * Laporan Arus Kas Konsolidasi (Semua Proyek)
     */
    public function arusKasKonsolidasi(Request $request)
    {
        $startDate = $request->input('start_date', date('Y-m-01'));
        $endDate = $request->input('end_date', date('Y-m-d'));

        $perusahaan = DB::table('perusahaan')->find(1);
        $proyeks = \App\Models\Proyek::orderBy('kode_proyek')->get();

        // Helper untuk mendapatkan arus kas per proyek
        $getFlowByProyek = function ($tipeAkunLawan, $isMasuk, $idProyek = null) use ($startDate, $endDate) {
            $kasQuery = JurnalDetail::whereHas('akun', function ($q) {
                $q->where('tipe_akun', 'Kas & Bank');
            })
                ->whereHas('jurnal', function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('tanggal', [$startDate, $endDate]);
                });

            if ($idProyek === null) {
                $kasQuery->whereNull('id_proyek');
            } else {
                $kasQuery->where('id_proyek', $idProyek);
            }

            $jurnalIds = $kasQuery->pluck('id_jurnal');

            $query = JurnalDetail::whereIn('id_jurnal', $jurnalIds)
                ->whereHas('akun', function ($q) use ($tipeAkunLawan) {
                    if (is_array($tipeAkunLawan)) {
                        $q->whereIn('tipe_akun', $tipeAkunLawan);
                    } else {
                        $q->where('tipe_akun', $tipeAkunLawan);
                    }
                });

            if ($idProyek === null) {
                $query->whereNull('id_proyek');
            } else {
                $query->where('id_proyek', $idProyek);
            }

            return $isMasuk ? $query->sum('kredit') : $query->sum('debit');
        };

        // Hitung per proyek
        $dataProyek = [];
        foreach ($proyeks as $p) {
            $terimaPelanggan = $getFlowByProyek(['Piutang', 'Pendapatan', 'Pendapatan Lainnya'], true, $p->id_proyek);
            $bayarPemasok = $getFlowByProyek(['Utang Usaha', 'HPP', 'Beban', 'Beban Lainnya', 'Kewajiban Lancar Lainnya', 'Persediaan', 'Aset Lancar Lainnya'], false, $p->id_proyek);
            $jualAset = $getFlowByProyek('Aset Tetap', true, $p->id_proyek);
            $beliAset = $getFlowByProyek('Aset Tetap', false, $p->id_proyek);
            $terimaPendanaan = $getFlowByProyek(['Ekuitas', 'Kewajiban Jangka Panjang'], true, $p->id_proyek);
            $bayarPendanaan = $getFlowByProyek(['Ekuitas', 'Kewajiban Jangka Panjang'], false, $p->id_proyek);

            $arusOperasi = $terimaPelanggan - $bayarPemasok;
            $arusInvestasi = $jualAset - $beliAset;
            $arusPendanaan = $terimaPendanaan - $bayarPendanaan;

            $dataProyek[$p->id_proyek] = [
                'terima_pelanggan' => $terimaPelanggan,
                'bayar_pemasok' => $bayarPemasok,
                'arus_operasi' => $arusOperasi,
                'jual_aset' => $jualAset,
                'beli_aset' => $beliAset,
                'arus_investasi' => $arusInvestasi,
                'terima_pendanaan' => $terimaPendanaan,
                'bayar_pendanaan' => $bayarPendanaan,
                'arus_pendanaan' => $arusPendanaan,
                'kenaikan_kas' => $arusOperasi + $arusInvestasi + $arusPendanaan,
            ];
        }

        // Non-proyek
        $terimaPelangganNP = $getFlowByProyek(['Piutang', 'Pendapatan', 'Pendapatan Lainnya'], true, null);
        $bayarPemasokNP = $getFlowByProyek(['Utang Usaha', 'HPP', 'Beban', 'Beban Lainnya', 'Kewajiban Lancar Lainnya', 'Persediaan', 'Aset Lancar Lainnya'], false, null);
        $jualAsetNP = $getFlowByProyek('Aset Tetap', true, null);
        $beliAsetNP = $getFlowByProyek('Aset Tetap', false, null);
        $terimaPendanaanNP = $getFlowByProyek(['Ekuitas', 'Kewajiban Jangka Panjang'], true, null);
        $bayarPendanaanNP = $getFlowByProyek(['Ekuitas', 'Kewajiban Jangka Panjang'], false, null);

        $dataNonProyek = [
            'terima_pelanggan' => $terimaPelangganNP,
            'bayar_pemasok' => $bayarPemasokNP,
            'arus_operasi' => $terimaPelangganNP - $bayarPemasokNP,
            'jual_aset' => $jualAsetNP,
            'beli_aset' => $beliAsetNP,
            'arus_investasi' => $jualAsetNP - $beliAsetNP,
            'terima_pendanaan' => $terimaPendanaanNP,
            'bayar_pendanaan' => $bayarPendanaanNP,
            'arus_pendanaan' => $terimaPendanaanNP - $bayarPendanaanNP,
            'kenaikan_kas' => ($terimaPelangganNP - $bayarPemasokNP) + ($jualAsetNP - $beliAsetNP) + ($terimaPendanaanNP - $bayarPendanaanNP),
        ];

        // Total
        $dataTotal = [
            'terima_pelanggan' => collect($dataProyek)->sum('terima_pelanggan') + $dataNonProyek['terima_pelanggan'],
            'bayar_pemasok' => collect($dataProyek)->sum('bayar_pemasok') + $dataNonProyek['bayar_pemasok'],
            'arus_operasi' => collect($dataProyek)->sum('arus_operasi') + $dataNonProyek['arus_operasi'],
            'jual_aset' => collect($dataProyek)->sum('jual_aset') + $dataNonProyek['jual_aset'],
            'beli_aset' => collect($dataProyek)->sum('beli_aset') + $dataNonProyek['beli_aset'],
            'arus_investasi' => collect($dataProyek)->sum('arus_investasi') + $dataNonProyek['arus_investasi'],
            'terima_pendanaan' => collect($dataProyek)->sum('terima_pendanaan') + $dataNonProyek['terima_pendanaan'],
            'bayar_pendanaan' => collect($dataProyek)->sum('bayar_pendanaan') + $dataNonProyek['bayar_pendanaan'],
            'arus_pendanaan' => collect($dataProyek)->sum('arus_pendanaan') + $dataNonProyek['arus_pendanaan'],
            'kenaikan_kas' => collect($dataProyek)->sum('kenaikan_kas') + $dataNonProyek['kenaikan_kas'],
        ];

        return view('laporan.aruskas_konsolidasi', compact(
            'perusahaan', 'startDate', 'endDate', 'proyeks',
            'dataProyek', 'dataNonProyek', 'dataTotal'
        ));
    }
}
