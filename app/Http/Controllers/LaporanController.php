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
            $saldos = JurnalDetail::whereHas('jurnal', function ($q) use ($tanggal) {
                    $q->where('tanggal', '<=', $tanggal . ' 23:59:59');
                })
                ->select('kode_akun', DB::raw('SUM(debit) as total_debit'), DB::raw('SUM(kredit) as total_kredit'))
                ->groupBy('kode_akun')
                ->get()
                ->keyBy('kode_akun');

            return $akunNeraca->map(function ($akun) use ($saldos) {
                // Clone akun agar tidak merubah referensi asli saat loop kedua
                $akunClone = clone $akun;

                $saldo = $saldos->get($akun->kode_akun);
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
            $saldos = JurnalDetail::whereHas('jurnal', function ($q) use ($start, $end) {
                    $q->whereBetween('tanggal', [$start, $end]);
                })
                ->select('kode_akun', DB::raw('SUM(debit) as total_debit'), DB::raw('SUM(kredit) as total_kredit'))
                ->groupBy('kode_akun')
                ->get()
                ->keyBy('kode_akun');

            return $akunLabaRugi->map(function ($akun) use ($saldos) {
                $akunClone = clone $akun;
                $saldo = $saldos->get($akun->kode_akun);
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
                $q->where('tanggal', '<=', $endDate . ' 23:59:59');
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

        $saldos = JurnalDetail::whereHas('jurnal', function ($q) use ($startDate, $endDate) {
                $q->whereBetween('tanggal', [$startDate, $endDate]);
            })
            ->select('kode_akun', DB::raw('SUM(debit) as total_debit'), DB::raw('SUM(kredit) as total_kredit'))
            ->groupBy('kode_akun')
            ->get()
            ->keyBy('kode_akun');

        $laporan = $akunLabaRugi->map(function ($akun) use ($saldos) {
            $saldo = $saldos->get($akun->kode_akun);
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
            $saldos = JurnalDetail::where('id_proyek', $idProyek)
                ->whereHas('jurnal', function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('tanggal', [$startDate, $endDate]);
                })
                ->select('kode_akun', DB::raw('SUM(debit) as total_debit'), DB::raw('SUM(kredit) as total_kredit'))
                ->groupBy('kode_akun')
                ->get()
                ->keyBy('kode_akun');

            $laporan = $akunLabaRugi->map(function ($akun) use ($saldos) {
                $akunClone = clone $akun;
                $saldo = $saldos->get($akun->kode_akun);
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

        // Ambil semua detail jurnal per kode_akun dan id_proyek (termasuk null untuk non_proyek) dengan 1 query agregasi
        $saldosGrouped = JurnalDetail::whereHas('jurnal', function ($q) use ($startDate, $endDate) {
                $q->whereBetween('tanggal', [$startDate, $endDate]);
            })
            ->select('kode_akun', 'id_proyek', DB::raw('SUM(debit) as total_debit'), DB::raw('SUM(kredit) as total_kredit'))
            ->groupBy('kode_akun', 'id_proyek')
            ->get();

        $saldoMap = [];
        foreach ($saldosGrouped as $s) {
            $keyProyek = $s->id_proyek ?? 'non_proyek';
            $saldoMap[$s->kode_akun][$keyProyek] = [
                'total_debit' => $s->total_debit,
                'total_kredit' => $s->total_kredit,
            ];
        }

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

            $akunSaldos = $saldoMap[$akun->kode_akun] ?? [];

            // Hitung untuk setiap proyek
            foreach ($proyeks as $proyek) {
                $saldo = $akunSaldos[$proyek->id_proyek] ?? ['total_debit' => 0, 'total_kredit' => 0];
                $totalDebit = $saldo['total_debit'];
                $totalKredit = $saldo['total_kredit'];

                if ($akun->saldo_normal == 'Kredit') {
                    $nilai = $totalKredit - $totalDebit;
                } else {
                    $nilai = $totalDebit - $totalKredit;
                }

                $row['proyek'][$proyek->id_proyek] = $nilai;
            }

            // Hitung transaksi tanpa proyek
            $saldoNP = $akunSaldos['non_proyek'] ?? ['total_debit' => 0, 'total_kredit' => 0];
            $totalDebitNP = $saldoNP['total_debit'];
            $totalKreditNP = $saldoNP['total_kredit'];

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

    public function neracaExcel(Request $request)
    {
        $perTanggal = $request->input('per_tanggal', date('Y-m-d'));
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        $sheet->setCellValue('A1', 'LAPORAN NERACA');
        $sheet->setCellValue('A2', 'Per Tanggal: ' . $perTanggal);

        $akunNeraca = Akun::whereIn('tipe_akun', [
            'Kas & Bank', 'Piutang', 'Persediaan', 'Aset Lancar Lainnya', 'Aset Tetap',
            'Utang Usaha', 'Kewajiban Lancar Lainnya', 'Kewajiban Jangka Panjang', 'Ekuitas'
        ])->orderBy('kode_akun')->get();

        $saldos = JurnalDetail::whereHas('jurnal', function ($q) use ($perTanggal) {
                $q->where('tanggal', '<=', $perTanggal . ' 23:59:59');
            })
            ->select('kode_akun', DB::raw('SUM(debit) as total_debit'), DB::raw('SUM(kredit) as total_kredit'))
            ->groupBy('kode_akun')
            ->get()
            ->keyBy('kode_akun');

        $sheet->setCellValue('A4', 'Kode Akun');
        $sheet->setCellValue('B4', 'Nama Akun');
        $sheet->setCellValue('C4', 'Tipe Akun');
        $sheet->setCellValue('D4', 'Saldo');

        $rowNum = 5;
        foreach ($akunNeraca as $akun) {
            $saldo = $saldos->get($akun->kode_akun);
            $totalDebit = $saldo->total_debit ?? 0;
            $totalKredit = $saldo->total_kredit ?? 0;
            $nilaiSaldo = $akun->saldo_normal == 'Debit' ? $totalDebit - $totalKredit : $totalKredit - $totalDebit;

            $sheet->setCellValue('A' . $rowNum, $akun->kode_akun);
            $sheet->setCellValue('B' . $rowNum, $akun->nama_akun);
            $sheet->setCellValue('C' . $rowNum, $akun->tipe_akun);
            $sheet->setCellValue('D' . $rowNum, $nilaiSaldo);
            $rowNum++;
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        return response()->streamDownload(function() use ($writer) {
            $writer->save('php://output');
        }, 'neraca_' . $perTanggal . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function labaRugiExcel(Request $request)
    {
        $startDate = $request->input('start_date', date('Y-m-01'));
        $endDate = $request->input('end_date', date('Y-m-d'));
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'LAPORAN LABA RUGI');
        $sheet->setCellValue('A2', 'Periode: ' . $startDate . ' s/d ' . $endDate);

        $akunLabaRugi = Akun::whereIn('tipe_akun', [
            'Pendapatan', 'Pendapatan Lainnya', 'HPP', 'Beban', 'Beban Lainnya'
        ])->orderBy('kode_akun')->get();

        $saldos = JurnalDetail::whereHas('jurnal', function ($q) use ($startDate, $endDate) {
                $q->whereBetween('tanggal', [$startDate, $endDate]);
            })
            ->select('kode_akun', DB::raw('SUM(debit) as total_debit'), DB::raw('SUM(kredit) as total_kredit'))
            ->groupBy('kode_akun')
            ->get()
            ->keyBy('kode_akun');

        $sheet->setCellValue('A4', 'Kode Akun');
        $sheet->setCellValue('B4', 'Nama Akun');
        $sheet->setCellValue('C4', 'Tipe Akun');
        $sheet->setCellValue('D4', 'Saldo');

        $rowNum = 5;
        foreach ($akunLabaRugi as $akun) {
            $saldo = $saldos->get($akun->kode_akun);
            $totalDebit = $saldo->total_debit ?? 0;
            $totalKredit = $saldo->total_kredit ?? 0;
            $nilaiSaldo = $akun->saldo_normal == 'Kredit' ? $totalKredit - $totalDebit : $totalDebit - $totalKredit;

            $sheet->setCellValue('A' . $rowNum, $akun->kode_akun);
            $sheet->setCellValue('B' . $rowNum, $akun->nama_akun);
            $sheet->setCellValue('C' . $rowNum, $akun->tipe_akun);
            $sheet->setCellValue('D' . $rowNum, $nilaiSaldo);
            $rowNum++;
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        return response()->streamDownload(function() use ($writer) {
            $writer->save('php://output');
        }, 'laba_rugi_' . $startDate . '_' . $endDate . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function labaRugiProyekPdf(Request $request)
    {
        $startDate = $request->input('start_date', date('Y-m-01'));
        $endDate = $request->input('end_date', date('Y-m-d'));
        $idProyek = $request->input('id_proyek');

        $perusahaan = DB::table('perusahaan')->find(1);
        $proyek = \App\Models\Proyek::findOrFail($idProyek);

        $akunLabaRugi = Akun::whereIn('tipe_akun', [
            'Pendapatan', 'Pendapatan Lainnya', 'HPP', 'Beban', 'Beban Lainnya'
        ])->orderBy('kode_akun')->get();

        $saldos = JurnalDetail::where('id_proyek', $idProyek)
            ->whereHas('jurnal', function ($q) use ($startDate, $endDate) {
                $q->whereBetween('tanggal', [$startDate, $endDate]);
            })
            ->select('kode_akun', DB::raw('SUM(debit) as total_debit'), DB::raw('SUM(kredit) as total_kredit'))
            ->groupBy('kode_akun')
            ->get()
            ->keyBy('kode_akun');

        $laporan = $akunLabaRugi->map(function ($akun) use ($saldos) {
            $saldo = $saldos->get($akun->kode_akun);
            $totalDebit = $saldo->total_debit ?? 0;
            $totalKredit = $saldo->total_kredit ?? 0;

            return [
                'kode' => $akun->kode_akun,
                'nama' => $akun->nama_akun,
                'tipe' => $akun->tipe_akun,
                'saldo' => $akun->saldo_normal == 'Kredit' ? $totalKredit - $totalDebit : $totalDebit - $totalKredit,
            ];
        });

        $pendapatan = $laporan->whereIn('tipe', ['Pendapatan', 'Pendapatan Lainnya'])->values();
        $hpp = $laporan->where('tipe', 'HPP')->values();
        $beban = $laporan->whereIn('tipe', ['Beban', 'Beban Lainnya'])->values();

        $pdf = Pdf::loadView('laporan.pdf.labarugi_proyek', compact(
            'perusahaan', 'startDate', 'endDate', 'proyek', 'pendapatan', 'hpp', 'beban'
        ));
        $pdf->setPaper('a4', 'portrait');
        return $pdf->download('laba_rugi_proyek_' . $proyek->kode_proyek . '_' . $startDate . '_' . $endDate . '.pdf');
    }

    public function labaRugiProyekExcel(Request $request)
    {
        $startDate = $request->input('start_date', date('Y-m-01'));
        $endDate = $request->input('end_date', date('Y-m-d'));
        $idProyek = $request->input('id_proyek');
        $proyek = \App\Models\Proyek::findOrFail($idProyek);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'LAPORAN LABA RUGI PROYEK');
        $sheet->setCellValue('A2', 'Proyek: ' . $proyek->kode_proyek . ' - ' . $proyek->nama_proyek);
        $sheet->setCellValue('A3', 'Periode: ' . $startDate . ' s/d ' . $endDate);

        $akunLabaRugi = Akun::whereIn('tipe_akun', [
            'Pendapatan', 'Pendapatan Lainnya', 'HPP', 'Beban', 'Beban Lainnya'
        ])->orderBy('kode_akun')->get();

        $saldos = JurnalDetail::where('id_proyek', $idProyek)
            ->whereHas('jurnal', function ($q) use ($startDate, $endDate) {
                $q->whereBetween('tanggal', [$startDate, $endDate]);
            })
            ->select('kode_akun', DB::raw('SUM(debit) as total_debit'), DB::raw('SUM(kredit) as total_kredit'))
            ->groupBy('kode_akun')
            ->get()
            ->keyBy('kode_akun');

        $sheet->setCellValue('A5', 'Kode Akun');
        $sheet->setCellValue('B5', 'Nama Akun');
        $sheet->setCellValue('C5', 'Tipe Akun');
        $sheet->setCellValue('D5', 'Saldo');

        $rowNum = 6;
        foreach ($akunLabaRugi as $akun) {
            $saldo = $saldos->get($akun->kode_akun);
            $totalDebit = $saldo->total_debit ?? 0;
            $totalKredit = $saldo->total_kredit ?? 0;
            $nilaiSaldo = $akun->saldo_normal == 'Kredit' ? $totalKredit - $totalDebit : $totalDebit - $totalKredit;

            $sheet->setCellValue('A' . $rowNum, $akun->kode_akun);
            $sheet->setCellValue('B' . $rowNum, $akun->nama_akun);
            $sheet->setCellValue('C' . $rowNum, $akun->tipe_akun);
            $sheet->setCellValue('D' . $rowNum, $nilaiSaldo);
            $rowNum++;
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        return response()->streamDownload(function() use ($writer) {
            $writer->save('php://output');
        }, 'laba_rugi_proyek_' . $proyek->kode_proyek . '_' . $startDate . '_' . $endDate . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function labaRugiKonsolidasiPdf(Request $request)
    {
        $startDate = $request->input('start_date', date('Y-m-01'));
        $endDate = $request->input('end_date', date('Y-m-d'));
        $perusahaan = DB::table('perusahaan')->find(1);
        $proyeks = \App\Models\Proyek::orderBy('kode_proyek')->get();

        $akunLabaRugi = Akun::whereIn('tipe_akun', [
            'Pendapatan', 'Pendapatan Lainnya', 'HPP', 'Beban', 'Beban Lainnya'
        ])->orderBy('kode_akun')->get();

        $saldosGrouped = JurnalDetail::whereHas('jurnal', function ($q) use ($startDate, $endDate) {
                $q->whereBetween('tanggal', [$startDate, $endDate]);
            })
            ->select('kode_akun', 'id_proyek', DB::raw('SUM(debit) as total_debit'), DB::raw('SUM(kredit) as total_kredit'))
            ->groupBy('kode_akun', 'id_proyek')
            ->get();

        $saldoMap = [];
        foreach ($saldosGrouped as $s) {
            $keyProyek = $s->id_proyek ?? 'non_proyek';
            $saldoMap[$s->kode_akun][$keyProyek] = [
                'total_debit' => $s->total_debit,
                'total_kredit' => $s->total_kredit,
            ];
        }

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

            $akunSaldos = $saldoMap[$akun->kode_akun] ?? [];

            foreach ($proyeks as $proyek) {
                $saldo = $akunSaldos[$proyek->id_proyek] ?? ['total_debit' => 0, 'total_kredit' => 0];
                $totalDebit = $saldo['total_debit'];
                $totalKredit = $saldo['total_kredit'];
                $row['proyek'][$proyek->id_proyek] = $akun->saldo_normal == 'Kredit' ? $totalKredit - $totalDebit : $totalDebit - $totalKredit;
            }

            $saldoNP = $akunSaldos['non_proyek'] ?? ['total_debit' => 0, 'total_kredit' => 0];
            $row['non_proyek'] = $akun->saldo_normal == 'Kredit' ? $saldoNP['total_kredit'] - $saldoNP['total_debit'] : $saldoNP['total_debit'] - $saldoNP['total_kredit'];
            $row['total'] = array_sum($row['proyek']) + $row['non_proyek'];

            $laporanData[] = $row;
        }

        $laporan = collect($laporanData);
        $pendapatan = $laporan->whereIn('tipe_akun', ['Pendapatan', 'Pendapatan Lainnya']);
        $hpp = $laporan->where('tipe_akun', 'HPP');
        $beban = $laporan->whereIn('tipe_akun', ['Beban', 'Beban Lainnya']);

        $pdf = Pdf::loadView('laporan.pdf.labarugi_konsolidasi', compact(
            'perusahaan', 'startDate', 'endDate', 'proyeks', 'pendapatan', 'hpp', 'beban'
        ));
        $pdf->setPaper('a4', 'landscape');
        return $pdf->download('laba_rugi_konsolidasi_' . $startDate . '_' . $endDate . '.pdf');
    }

    public function labaRugiKonsolidasiExcel(Request $request)
    {
        $startDate = $request->input('start_date', date('Y-m-01'));
        $endDate = $request->input('end_date', date('Y-m-d'));
        $proyeks = \App\Models\Proyek::orderBy('kode_proyek')->get();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'LAPORAN LABA RUGI KONSOLIDASI');
        $sheet->setCellValue('A2', 'Periode: ' . $startDate . ' s/d ' . $endDate);

        $akunLabaRugi = Akun::whereIn('tipe_akun', [
            'Pendapatan', 'Pendapatan Lainnya', 'HPP', 'Beban', 'Beban Lainnya'
        ])->orderBy('kode_akun')->get();

        $saldosGrouped = JurnalDetail::whereHas('jurnal', function ($q) use ($startDate, $endDate) {
                $q->whereBetween('tanggal', [$startDate, $endDate]);
            })
            ->select('kode_akun', 'id_proyek', DB::raw('SUM(debit) as total_debit'), DB::raw('SUM(kredit) as total_kredit'))
            ->groupBy('kode_akun', 'id_proyek')
            ->get();

        $saldoMap = [];
        foreach ($saldosGrouped as $s) {
            $keyProyek = $s->id_proyek ?? 'non_proyek';
            $saldoMap[$s->kode_akun][$keyProyek] = [
                'total_debit' => $s->total_debit,
                'total_kredit' => $s->total_kredit,
            ];
        }

        $sheet->setCellValue('A4', 'Kode Akun');
        $sheet->setCellValue('B4', 'Nama Akun');
        $colIdx = 'C';
        foreach ($proyeks as $p) {
            $sheet->setCellValue($colIdx . '4', $p->kode_proyek);
            $colIdx++;
        }
        $sheet->setCellValue($colIdx . '4', 'Non Proyek');
        $colIdx++;
        $sheet->setCellValue($colIdx . '4', 'Total');

        $rowNum = 5;
        foreach ($akunLabaRugi as $akun) {
            $sheet->setCellValue('A' . $rowNum, $akun->kode_akun);
            $sheet->setCellValue('B' . $rowNum, $akun->nama_akun);

            $akunSaldos = $saldoMap[$akun->kode_akun] ?? [];
            $curColIdx = 'C';
            $totalRow = 0;

            foreach ($proyeks as $p) {
                $saldo = $akunSaldos[$p->id_proyek] ?? ['total_debit' => 0, 'total_kredit' => 0];
                $val = $akun->saldo_normal == 'Kredit' ? $saldo['total_kredit'] - $saldo['total_debit'] : $saldo['total_debit'] - $saldo['total_kredit'];
                $sheet->setCellValue($curColIdx . $rowNum, $val);
                $totalRow += $val;
                $curColIdx++;
            }

            $saldoNP = $akunSaldos['non_proyek'] ?? ['total_debit' => 0, 'total_kredit' => 0];
            $valNP = $akun->saldo_normal == 'Kredit' ? $saldoNP['total_kredit'] - $saldoNP['total_debit'] : $saldoNP['total_debit'] - $saldoNP['total_kredit'];
            $sheet->setCellValue($curColIdx . $rowNum, $valNP);
            $totalRow += $valNP;
            $curColIdx++;

            $sheet->setCellValue($curColIdx . $rowNum, $totalRow);
            $rowNum++;
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        return response()->streamDownload(function() use ($writer) {
            $writer->save('php://output');
        }, 'laba_rugi_konsolidasi_' . $startDate . '_' . $endDate . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function arusKasLangsungPdf(Request $request)
    {
        $startDate = $request->input('start_date', date('Y-m-01'));
        $endDate = $request->input('end_date', date('Y-m-d'));
        $perusahaan = DB::table('perusahaan')->find(1);

        $getFlow = function ($tipeAkunLawan, $isMasuk) use ($startDate, $endDate) {
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

        $terimaPelanggan = $getFlow(['Piutang', 'Pendapatan', 'Pendapatan Lainnya'], true);
        $bayarPemasok = $getFlow(['Utang Usaha', 'HPP', 'Beban', 'Beban Lainnya', 'Kewajiban Lancar Lainnya', 'Persediaan', 'Aset Lancar Lainnya'], false);
        $arusKasOperasi = $terimaPelanggan - $bayarPemasok;

        $jualAset = $getFlow('Aset Tetap', true);
        $beliAset = $getFlow('Aset Tetap', false);
        $arusKasInvestasi = $jualAset - $beliAset;

        $terimaPendanaan = $getFlow(['Ekuitas', 'Kewajiban Jangka Panjang'], true);
        $bayarPendanaan = $getFlow(['Ekuitas', 'Kewajiban Jangka Panjang'], false);
        $arusKasPendanaan = $terimaPendanaan - $bayarPendanaan;

        $kenaikanKas = $arusKasOperasi + $arusKasInvestasi + $arusKasPendanaan;

        $saldoAwal = JurnalDetail::whereHas('akun', function ($q) {
            $q->where('tipe_akun', 'Kas & Bank');
        })
            ->whereHas('jurnal', function ($q) use ($startDate) {
                $q->where('tanggal', '<', $startDate);
            })
            ->sum(DB::raw('debit - kredit'));

        $saldoAkhir = $saldoAwal + $kenaikanKas;

        $pdf = Pdf::loadView('laporan.pdf.aruskas_langsung', compact(
            'perusahaan', 'startDate', 'endDate', 'terimaPelanggan', 'bayarPemasok', 'arusKasOperasi',
            'jualAset', 'beliAset', 'arusKasInvestasi', 'terimaPendanaan', 'bayarPendanaan', 'arusKasPendanaan',
            'kenaikanKas', 'saldoAwal', 'saldoAkhir'
        ));
        $pdf->setPaper('a4', 'portrait');
        return $pdf->download('arus_kas_langsung_' . $startDate . '_' . $endDate . '.pdf');
    }

    public function arusKasLangsungExcel(Request $request)
    {
        $startDate = $request->input('start_date', date('Y-m-01'));
        $endDate = $request->input('end_date', date('Y-m-d'));

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'LAPORAN ARUS KAS (METODE LANGSUNG)');
        $sheet->setCellValue('A2', 'Periode: ' . $startDate . ' s/d ' . $endDate);

        $getFlow = function ($tipeAkunLawan, $isMasuk) use ($startDate, $endDate) {
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

        $terimaPelanggan = $getFlow(['Piutang', 'Pendapatan', 'Pendapatan Lainnya'], true);
        $bayarPemasok = $getFlow(['Utang Usaha', 'HPP', 'Beban', 'Beban Lainnya', 'Kewajiban Lancar Lainnya', 'Persediaan', 'Aset Lancar Lainnya'], false);
        $arusKasOperasi = $terimaPelanggan - $bayarPemasok;

        $jualAset = $getFlow('Aset Tetap', true);
        $beliAset = $getFlow('Aset Tetap', false);
        $arusKasInvestasi = $jualAset - $beliAset;

        $terimaPendanaan = $getFlow(['Ekuitas', 'Kewajiban Jangka Panjang'], true);
        $bayarPendanaan = $getFlow(['Ekuitas', 'Kewajiban Jangka Panjang'], false);
        $arusKasPendanaan = $terimaPendanaan - $bayarPendanaan;

        $kenaikanKas = $arusKasOperasi + $arusKasInvestasi + $arusKasPendanaan;

        $saldoAwal = JurnalDetail::whereHas('akun', function ($q) {
            $q->where('tipe_akun', 'Kas & Bank');
        })
            ->whereHas('jurnal', function ($q) use ($startDate) {
                $q->where('tanggal', '<', $startDate);
            })
            ->sum(DB::raw('debit - kredit'));

        $saldoAkhir = $saldoAwal + $kenaikanKas;

        $sheet->setCellValue('A4', 'Aktivitas Arus Kas');
        $sheet->setCellValue('B4', 'Jumlah');

        $sheet->setCellValue('A5', 'Arus Kas dari Aktivitas Operasi');
        $sheet->setCellValue('A6', '   Penerimaan Kas dari Pelanggan');
        $sheet->setCellValue('B6', $terimaPelanggan);
        $sheet->setCellValue('A7', '   Pengeluaran Kas untuk Pemasok');
        $sheet->setCellValue('B7', -$bayarPemasok);
        $sheet->setCellValue('A8', 'Total Arus Kas Aktivitas Operasi');
        $sheet->setCellValue('B8', $arusKasOperasi);

        $sheet->setCellValue('A10', 'Arus Kas dari Aktivitas Investasi');
        $sheet->setCellValue('A11', '   Penjualan Aset Tetap');
        $sheet->setCellValue('B11', $jualAset);
        $sheet->setCellValue('A12', '   Pembelian Aset Tetap');
        $sheet->setCellValue('B12', -$beliAset);
        $sheet->setCellValue('A13', 'Total Arus Kas Aktivitas Investasi');
        $sheet->setCellValue('B13', $arusKasInvestasi);

        $sheet->setCellValue('A15', 'Arus Kas dari Aktivitas Pendanaan');
        $sheet->setCellValue('A16', '   Penerimaan Modal/Pinjaman');
        $sheet->setCellValue('B16', $terimaPendanaan);
        $sheet->setCellValue('A17', '   Prive/Pelunasan Utang Bank');
        $sheet->setCellValue('B17', -$bayarPendanaan);
        $sheet->setCellValue('A18', 'Total Arus Kas Aktivitas Pendanaan');
        $sheet->setCellValue('B18', $arusKasPendanaan);

        $sheet->setCellValue('A20', 'Kenaikan/Penurunan Kas Bersih');
        $sheet->setCellValue('B20', $kenaikanKas);
        $sheet->setCellValue('A21', 'Saldo Awal Kas');
        $sheet->setCellValue('B21', $saldoAwal);
        $sheet->setCellValue('A22', 'Saldo Akhir Kas');
        $sheet->setCellValue('B22', $saldoAkhir);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        return response()->streamDownload(function() use ($writer) {
            $writer->save('php://output');
        }, 'arus_kas_langsung_' . $startDate . '_' . $endDate . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function arusKasTidakLangsungPdf(Request $request)
    {
        $startDate = $request->input('start_date', date('Y-m-01'));
        $endDate = $request->input('end_date', date('Y-m-d'));
        $perusahaan = DB::table('perusahaan')->find(1);

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

        $bebanPenyusutan = JurnalDetail::whereHas('akun', function ($q) {
            $q->where('nama_akun', 'like', '%Penyusutan%')
                ->orWhere('nama_akun', 'like', '%Depresiasi%');
        })->whereHas('jurnal', function ($q) use ($startDate, $endDate) {
            $q->whereBetween('tanggal', [$startDate, $endDate]);
        })->sum('debit');

        $getChange = function ($tipeAkun, $saldoNormal) use ($startDate, $endDate) {
            $awal = JurnalDetail::whereHas('akun', function ($q) use ($tipeAkun) {
                $q->where('tipe_akun', $tipeAkun);
            })->whereHas('jurnal', function ($q) use ($startDate) {
                $q->where('tanggal', '<', $startDate);
            })->sum(DB::raw($saldoNormal == 'Debit' ? 'debit - kredit' : 'kredit - debit'));

            $akhir = JurnalDetail::whereHas('akun', function ($q) use ($tipeAkun) {
                $q->where('tipe_akun', $tipeAkun);
            })->whereHas('jurnal', function ($q) use ($endDate) {
                $q->where('tanggal', '<=', $endDate . ' 23:59:59');
            })->sum(DB::raw($saldoNormal == 'Debit' ? 'debit - kredit' : 'kredit - debit'));

            return $akhir - $awal;
        };

        $kenaikanPiutang = $getChange('Piutang', 'Debit');
        $kenaikanPersediaan = $getChange('Persediaan', 'Debit');
        $kenaikanUtang = $getChange('Utang Usaha', 'Kredit');

        $arusKasOperasi = $labaBersih + $bebanPenyusutan - $kenaikanPiutang - $kenaikanPersediaan + $kenaikanUtang;

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

        $saldoAwal = JurnalDetail::whereHas('akun', function ($q) {
            $q->where('tipe_akun', 'Kas & Bank');
        })
            ->whereHas('jurnal', function ($q) use ($startDate) {
                $q->where('tanggal', '<', $startDate);
            })
            ->sum(DB::raw('debit - kredit'));

        $saldoAkhir = $saldoAwal + $kenaikanKas;

        $pdf = Pdf::loadView('laporan.pdf.aruskas_tidak_langsung', compact(
            'perusahaan', 'startDate', 'endDate', 'labaBersih', 'bebanPenyusutan',
            'kenaikanPiutang', 'kenaikanPersediaan', 'kenaikanUtang', 'arusKasOperasi',
            'arusKasInvestasi', 'arusKasPendanaan', 'kenaikanKas', 'saldoAwal', 'saldoAkhir'
        ));
        $pdf->setPaper('a4', 'portrait');
        return $pdf->download('arus_kas_tidak_langsung_' . $startDate . '_' . $endDate . '.pdf');
    }

    public function arusKasTidakLangsungExcel(Request $request)
    {
        $startDate = $request->input('start_date', date('Y-m-01'));
        $endDate = $request->input('end_date', date('Y-m-d'));

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'LAPORAN ARUS KAS (METODE TIDAK LANGSUNG)');
        $sheet->setCellValue('A2', 'Periode: ' . $startDate . ' s/d ' . $endDate);

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

        $bebanPenyusutan = JurnalDetail::whereHas('akun', function ($q) {
            $q->where('nama_akun', 'like', '%Penyusutan%')
                ->orWhere('nama_akun', 'like', '%Depresiasi%');
        })->whereHas('jurnal', function ($q) use ($startDate, $endDate) {
            $q->whereBetween('tanggal', [$startDate, $endDate]);
        })->sum('debit');

        $getChange = function ($tipeAkun, $saldoNormal) use ($startDate, $endDate) {
            $awal = JurnalDetail::whereHas('akun', function ($q) use ($tipeAkun) {
                $q->where('tipe_akun', $tipeAkun);
            })->whereHas('jurnal', function ($q) use ($startDate) {
                $q->where('tanggal', '<', $startDate);
            })->sum(DB::raw($saldoNormal == 'Debit' ? 'debit - kredit' : 'kredit - debit'));

            $akhir = JurnalDetail::whereHas('akun', function ($q) use ($tipeAkun) {
                $q->where('tipe_akun', $tipeAkun);
            })->whereHas('jurnal', function ($q) use ($endDate) {
                $q->where('tanggal', '<=', $endDate . ' 23:59:59');
            })->sum(DB::raw($saldoNormal == 'Debit' ? 'debit - kredit' : 'kredit - debit'));

            return $akhir - $awal;
        };

        $kenaikanPiutang = $getChange('Piutang', 'Debit');
        $kenaikanPersediaan = $getChange('Persediaan', 'Debit');
        $kenaikanUtang = $getChange('Utang Usaha', 'Kredit');

        $arusKasOperasi = $labaBersih + $bebanPenyusutan - $kenaikanPiutang - $kenaikanPersediaan + $kenaikanUtang;

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

        $saldoAwal = JurnalDetail::whereHas('akun', function ($q) {
            $q->where('tipe_akun', 'Kas & Bank');
        })
            ->whereHas('jurnal', function ($q) use ($startDate) {
                $q->where('tanggal', '<', $startDate);
            })
            ->sum(DB::raw('debit - kredit'));

        $saldoAkhir = $saldoAwal + $kenaikanKas;

        $sheet->setCellValue('A4', 'Deskripsi');
        $sheet->setCellValue('B4', 'Jumlah');

        $sheet->setCellValue('A5', 'Arus Kas dari Aktivitas Operasi');
        $sheet->setCellValue('A6', '   Laba Bersih Periode Ini');
        $sheet->setCellValue('B6', $labaBersih);
        $sheet->setCellValue('A7', '   Penyusutan & Amortisasi (Non-Kas)');
        $sheet->setCellValue('B7', $bebanPenyusutan);
        $sheet->setCellValue('A8', '   (Kenaikan)/Penurunan Piutang Usaha');
        $sheet->setCellValue('B8', -$kenaikanPiutang);
        $sheet->setCellValue('A9', '   (Kenaikan)/Penurunan Persediaan');
        $sheet->setCellValue('B9', -$kenaikanPersediaan);
        $sheet->setCellValue('A10', '   Kenaikan/(Penurunan) Utang Usaha');
        $sheet->setCellValue('B10', $kenaikanUtang);
        $sheet->setCellValue('A11', 'Total Arus Kas Aktivitas Operasi');
        $sheet->setCellValue('B11', $arusKasOperasi);

        $sheet->setCellValue('A13', 'Arus Kas dari Aktivitas Investasi');
        $sheet->setCellValue('A14', '   Penerimaan/(Pengeluaran) Aset Tetap');
        $sheet->setCellValue('B14', $arusKasInvestasi);

        $sheet->setCellValue('A16', 'Arus Kas dari Aktivitas Pendanaan');
        $sheet->setCellValue('A17', '   Penerimaan/(Pengeluaran) Modal & Utang Bank');
        $sheet->setCellValue('B17', $arusKasPendanaan);

        $sheet->setCellValue('A19', 'Kenaikan/Penurunan Kas Bersih');
        $sheet->setCellValue('B19', $kenaikanKas);
        $sheet->setCellValue('A20', 'Saldo Awal Kas');
        $sheet->setCellValue('B20', $saldoAwal);
        $sheet->setCellValue('A21', 'Saldo Akhir Kas');
        $sheet->setCellValue('B21', $saldoAkhir);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        return response()->streamDownload(function() use ($writer) {
            $writer->save('php://output');
        }, 'arus_kas_tidak_langsung_' . $startDate . '_' . $endDate . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function arusKasProyekPdf(Request $request)
    {
        $startDate = $request->input('start_date', date('Y-m-01'));
        $endDate = $request->input('end_date', date('Y-m-d'));
        $idProyek = $request->input('id_proyek');

        $perusahaan = DB::table('perusahaan')->find(1);
        $proyek = \App\Models\Proyek::findOrFail($idProyek);

        $getFlow = function ($tipeAkunLawan, $isMasuk) use ($startDate, $endDate, $idProyek) {
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

        $terimaPelanggan = $getFlow(['Piutang', 'Pendapatan', 'Pendapatan Lainnya'], true);
        $bayarPemasok = $getFlow(['Utang Usaha', 'HPP', 'Beban', 'Beban Lainnya', 'Kewajiban Lancar Lainnya', 'Persediaan', 'Aset Lancar Lainnya'], false);
        $arusKasOperasi = $terimaPelanggan - $bayarPemasok;

        $jualAset = $getFlow('Aset Tetap', true);
        $beliAset = $getFlow('Aset Tetap', false);
        $arusKasInvestasi = $jualAset - $beliAset;

        $terimaPendanaan = $getFlow(['Ekuitas', 'Kewajiban Jangka Panjang'], true);
        $bayarPendanaan = $getFlow(['Ekuitas', 'Kewajiban Jangka Panjang'], false);
        $arusKasPendanaan = $terimaPendanaan - $bayarPendanaan;

        $kenaikanKas = $arusKasOperasi + $arusKasInvestasi + $arusKasPendanaan;

        $saldoAwal = JurnalDetail::whereHas('akun', function ($q) {
            $q->where('tipe_akun', 'Kas & Bank');
        })
            ->where('id_proyek', $idProyek)
            ->whereHas('jurnal', function ($q) use ($startDate) {
                $q->where('tanggal', '<', $startDate);
            })
            ->sum(DB::raw('debit - kredit'));

        $saldoAkhir = $saldoAwal + $kenaikanKas;

        $pdf = Pdf::loadView('laporan.pdf.aruskas_proyek', compact(
            'perusahaan', 'startDate', 'endDate', 'proyek', 'terimaPelanggan', 'bayarPemasok', 'arusKasOperasi',
            'jualAset', 'beliAset', 'arusKasInvestasi', 'terimaPendanaan', 'bayarPendanaan', 'arusKasPendanaan',
            'kenaikanKas', 'saldoAwal', 'saldoAkhir'
        ));
        $pdf->setPaper('a4', 'portrait');
        return $pdf->download('arus_kas_proyek_' . $proyek->kode_proyek . '_' . $startDate . '_' . $endDate . '.pdf');
    }

    public function arusKasProyekExcel(Request $request)
    {
        $startDate = $request->input('start_date', date('Y-m-01'));
        $endDate = $request->input('end_date', date('Y-m-d'));
        $idProyek = $request->input('id_proyek');
        $proyek = \App\Models\Proyek::findOrFail($idProyek);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'LAPORAN ARUS KAS PROYEK');
        $sheet->setCellValue('A2', 'Proyek: ' . $proyek->kode_proyek . ' - ' . $proyek->nama_proyek);
        $sheet->setCellValue('A3', 'Periode: ' . $startDate . ' s/d ' . $endDate);

        $getFlow = function ($tipeAkunLawan, $isMasuk) use ($startDate, $endDate, $idProyek) {
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

        $terimaPelanggan = $getFlow(['Piutang', 'Pendapatan', 'Pendapatan Lainnya'], true);
        $bayarPemasok = $getFlow(['Utang Usaha', 'HPP', 'Beban', 'Beban Lainnya', 'Kewajiban Lancar Lainnya', 'Persediaan', 'Aset Lancar Lainnya'], false);
        $arusKasOperasi = $terimaPelanggan - $bayarPemasok;

        $jualAset = $getFlow('Aset Tetap', true);
        $beliAset = $getFlow('Aset Tetap', false);
        $arusKasInvestasi = $jualAset - $beliAset;

        $terimaPendanaan = $getFlow(['Ekuitas', 'Kewajiban Jangka Panjang'], true);
        $bayarPendanaan = $getFlow(['Ekuitas', 'Kewajiban Jangka Panjang'], false);
        $arusKasPendanaan = $terimaPendanaan - $bayarPendanaan;

        $kenaikanKas = $arusKasOperasi + $arusKasInvestasi + $arusKasPendanaan;

        $saldoAwal = JurnalDetail::whereHas('akun', function ($q) {
            $q->where('tipe_akun', 'Kas & Bank');
        })
            ->where('id_proyek', $idProyek)
            ->whereHas('jurnal', function ($q) use ($startDate) {
                $q->where('tanggal', '<', $startDate);
            })
            ->sum(DB::raw('debit - kredit'));

        $saldoAkhir = $saldoAwal + $kenaikanKas;

        $sheet->setCellValue('A5', 'Kategori Arus Kas');
        $sheet->setCellValue('B5', 'Jumlah');

        $sheet->setCellValue('A6', 'Arus Kas Aktivitas Operasi');
        $sheet->setCellValue('A7', '   Kas Diterima dari Pelanggan');
        $sheet->setCellValue('B7', $terimaPelanggan);
        $sheet->setCellValue('A8', '   Kas Dibayarkan ke Pemasok');
        $sheet->setCellValue('B8', -$bayarPemasok);
        $sheet->setCellValue('A9', 'Total Arus Kas Operasi Proyek');
        $sheet->setCellValue('B9', $arusKasOperasi);

        $sheet->setCellValue('A11', 'Arus Kas Aktivitas Investasi');
        $sheet->setCellValue('A12', '   Penerimaan/(Pengeluaran) Aset Tetap Proyek');
        $sheet->setCellValue('B12', $arusKasInvestasi);

        $sheet->setCellValue('A14', 'Arus Kas Aktivitas Pendanaan');
        $sheet->setCellValue('A15', '   Penerimaan/(Pengeluaran) Modal & Pendanaan Proyek');
        $sheet->setCellValue('B15', $arusKasPendanaan);

        $sheet->setCellValue('A17', 'Kenaikan Bersih Kas Proyek');
        $sheet->setCellValue('B17', $kenaikanKas);
        $sheet->setCellValue('A18', 'Saldo Awal Kas Proyek');
        $sheet->setCellValue('B18', $saldoAwal);
        $sheet->setCellValue('A19', 'Saldo Akhir Kas Proyek');
        $sheet->setCellValue('B19', $saldoAkhir);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        return response()->streamDownload(function() use ($writer) {
            $writer->save('php://output');
        }, 'arus_kas_proyek_' . $proyek->kode_proyek . '_' . $startDate . '_' . $endDate . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function arusKasKonsolidasiPdf(Request $request)
    {
        $startDate = $request->input('start_date', date('Y-m-01'));
        $endDate = $request->input('end_date', date('Y-m-d'));
        $perusahaan = DB::table('perusahaan')->find(1);
        $proyeks = \App\Models\Proyek::orderBy('kode_proyek')->get();

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

        $pdf = Pdf::loadView('laporan.pdf.aruskas_konsolidasi', compact(
            'perusahaan', 'startDate', 'endDate', 'proyeks', 'dataProyek', 'dataNonProyek', 'dataTotal'
        ));
        $pdf->setPaper('a4', 'landscape');
        return $pdf->download('arus_kas_konsolidasi_' . $startDate . '_' . $endDate . '.pdf');
    }

    public function arusKasKonsolidasiExcel(Request $request)
    {
        $startDate = $request->input('start_date', date('Y-m-01'));
        $endDate = $request->input('end_date', date('Y-m-d'));
        $proyeks = \App\Models\Proyek::orderBy('kode_proyek')->get();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'LAPORAN ARUS KAS KONSOLIDASI');
        $sheet->setCellValue('A2', 'Periode: ' . $startDate . ' s/d ' . $endDate);

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

        $sheet->setCellValue('A4', 'Kategori Arus Kas');
        $colIdx = 'B';
        foreach ($proyeks as $p) {
            $sheet->setCellValue($colIdx . '4', $p->kode_proyek);
            $colIdx++;
        }
        $sheet->setCellValue($colIdx . '4', 'Non Proyek');
        $colIdx++;
        $sheet->setCellValue($colIdx . '4', 'Total');

        $rowsData = [
            'terima_pelanggan' => 'Penerimaan Kas dari Pelanggan',
            'bayar_pemasok' => 'Pengeluaran Kas ke Pemasok',
            'arus_operasi' => 'Total Arus Kas Aktivitas Operasi',
            'jual_aset' => 'Penjualan Aset Tetap',
            'beli_aset' => 'Pembelian Aset Tetap',
            'arus_investasi' => 'Total Arus Kas Aktivitas Investasi',
            'terima_pendanaan' => 'Penerimaan Modal & Pinjaman',
            'bayar_pendanaan' => 'Pengeluaran Prive & Utang Bank',
            'arus_pendanaan' => 'Total Arus Kas Aktivitas Pendanaan',
            'kenaikan_kas' => 'Kenaikan Bersih Kas',
        ];

        $rowNum = 5;
        foreach ($rowsData as $key => $label) {
            $sheet->setCellValue('A' . $rowNum, $label);
            $curColIdx = 'B';
            $totalVal = 0;

            foreach ($proyeks as $p) {
                $val = 0;
                if ($key === 'terima_pelanggan') $val = $getFlowByProyek(['Piutang', 'Pendapatan', 'Pendapatan Lainnya'], true, $p->id_proyek);
                elseif ($key === 'bayar_pemasok') $val = $getFlowByProyek(['Utang Usaha', 'HPP', 'Beban', 'Beban Lainnya', 'Kewajiban Lancar Lainnya', 'Persediaan', 'Aset Lancar Lainnya'], false, $p->id_proyek);
                elseif ($key === 'arus_operasi') $val = $getFlowByProyek(['Piutang', 'Pendapatan', 'Pendapatan Lainnya'], true, $p->id_proyek) - $getFlowByProyek(['Utang Usaha', 'HPP', 'Beban', 'Beban Lainnya', 'Kewajiban Lancar Lainnya', 'Persediaan', 'Aset Lancar Lainnya'], false, $p->id_proyek);
                elseif ($key === 'jual_aset') $val = $getFlowByProyek('Aset Tetap', true, $p->id_proyek);
                elseif ($key === 'beli_aset') $val = $getFlowByProyek('Aset Tetap', false, $p->id_proyek);
                elseif ($key === 'arus_investasi') $val = $getFlowByProyek('Aset Tetap', true, $p->id_proyek) - $getFlowByProyek('Aset Tetap', false, $p->id_proyek);
                elseif ($key === 'terima_pendanaan') $val = $getFlowByProyek(['Ekuitas', 'Kewajiban Jangka Panjang'], true, $p->id_proyek);
                elseif ($key === 'bayar_pendanaan') $val = $getFlowByProyek(['Ekuitas', 'Kewajiban Jangka Panjang'], false, $p->id_proyek);
                elseif ($key === 'arus_pendanaan') $val = $getFlowByProyek(['Ekuitas', 'Kewajiban Jangka Panjang'], true, $p->id_proyek) - $getFlowByProyek(['Ekuitas', 'Kewajiban Jangka Panjang'], false, $p->id_proyek);
                elseif ($key === 'kenaikan_kas') {
                    $op = $getFlowByProyek(['Piutang', 'Pendapatan', 'Pendapatan Lainnya'], true, $p->id_proyek) - $getFlowByProyek(['Utang Usaha', 'HPP', 'Beban', 'Beban Lainnya', 'Kewajiban Lancar Lainnya', 'Persediaan', 'Aset Lancar Lainnya'], false, $p->id_proyek);
                    $inv = $getFlowByProyek('Aset Tetap', true, $p->id_proyek) - $getFlowByProyek('Aset Tetap', false, $p->id_proyek);
                    $fin = $getFlowByProyek(['Ekuitas', 'Kewajiban Jangka Panjang'], true, $p->id_proyek) - $getFlowByProyek(['Ekuitas', 'Kewajiban Jangka Panjang'], false, $p->id_proyek);
                    $val = $op + $inv + $fin;
                }

                $sheet->setCellValue($curColIdx . $rowNum, $val);
                $totalVal += $val;
                $curColIdx++;
            }

            $valNP = 0;
            if ($key === 'terima_pelanggan') $valNP = $getFlowByProyek(['Piutang', 'Pendapatan', 'Pendapatan Lainnya'], true, null);
            elseif ($key === 'bayar_pemasok') $valNP = $getFlowByProyek(['Utang Usaha', 'HPP', 'Beban', 'Beban Lainnya', 'Kewajiban Lancar Lainnya', 'Persediaan', 'Aset Lancar Lainnya'], false, null);
            elseif ($key === 'arus_operasi') $valNP = $getFlowByProyek(['Piutang', 'Pendapatan', 'Pendapatan Lainnya'], true, null) - $getFlowByProyek(['Utang Usaha', 'HPP', 'Beban', 'Beban Lainnya', 'Kewajiban Lancar Lainnya', 'Persediaan', 'Aset Lancar Lainnya'], false, null);
            elseif ($key === 'jual_aset') $valNP = $getFlowByProyek('Aset Tetap', true, null);
            elseif ($key === 'beli_aset') $valNP = $getFlowByProyek('Aset Tetap', false, null);
            elseif ($key === 'arus_investasi') $valNP = $getFlowByProyek('Aset Tetap', true, null) - $getFlowByProyek('Aset Tetap', false, null);
            elseif ($key === 'terima_pendanaan') $valNP = $getFlowByProyek(['Ekuitas', 'Kewajiban Jangka Panjang'], true, null);
            elseif ($key === 'bayar_pendanaan') $valNP = $getFlowByProyek(['Ekuitas', 'Kewajiban Jangka Panjang'], false, null);
            elseif ($key === 'arus_pendanaan') $valNP = $getFlowByProyek(['Ekuitas', 'Kewajiban Jangka Panjang'], true, null) - $getFlowByProyek(['Ekuitas', 'Kewajiban Jangka Panjang'], false, null);
            elseif ($key === 'kenaikan_kas') {
                $op = $getFlowByProyek(['Piutang', 'Pendapatan', 'Pendapatan Lainnya'], true, null) - $getFlowByProyek(['Utang Usaha', 'HPP', 'Beban', 'Beban Lainnya', 'Kewajiban Lancar Lainnya', 'Persediaan', 'Aset Lancar Lainnya'], false, null);
                $inv = $getFlowByProyek('Aset Tetap', true, null) - $getFlowByProyek('Aset Tetap', false, null);
                $fin = $getFlowByProyek(['Ekuitas', 'Kewajiban Jangka Panjang'], true, null) - $getFlowByProyek(['Ekuitas', 'Kewajiban Jangka Panjang'], false, null);
                $valNP = $op + $inv + $fin;
            }

            $sheet->setCellValue($curColIdx . $rowNum, $valNP);
            $totalVal += $valNP;
            $curColIdx++;

            $sheet->setCellValue($curColIdx . $rowNum, $totalVal);
            $rowNum++;
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        return response()->streamDownload(function() use ($writer) {
            $writer->save('php://output');
        }, 'arus_kas_konsolidasi_' . $startDate . '_' . $endDate . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function perubahanEkuitasPdf(Request $request)
    {
        $startDate = $request->input('start_date', date('Y-m-01'));
        $endDate = $request->input('end_date', date('Y-m-d'));
        $perusahaan = DB::table('perusahaan')->find(1);

        $saldoAwalAkunEkuitas = JurnalDetail::whereHas('akun', function ($q) {
            $q->where('tipe_akun', 'Ekuitas');
        })
            ->whereHas('jurnal', function ($q) use ($startDate) {
                $q->where('tanggal', '<', $startDate);
            })
            ->sum(DB::raw('kredit - debit'));

        $labaDitahanAwal = $this->hitungLabaRugi(date('Y-m-d', strtotime($startDate . ' -1 day')));
        $saldoAwal = $saldoAwalAkunEkuitas + $labaDitahanAwal;

        $labaBersih = $this->hitungLabaRugiPeriode($startDate, $endDate);

        $setoranModal = JurnalDetail::whereHas('akun', function ($q) {
            $q->where('tipe_akun', 'Ekuitas');
        })
            ->whereHas('jurnal', function ($q) use ($startDate, $endDate) {
                $q->whereBetween('tanggal', [$startDate, $endDate]);
            })
            ->sum('kredit');

        $prive = JurnalDetail::whereHas('akun', function ($q) {
            $q->where('tipe_akun', 'Ekuitas');
        })
            ->whereHas('jurnal', function ($q) use ($startDate, $endDate) {
                $q->whereBetween('tanggal', [$startDate, $endDate]);
            })
            ->sum('debit');

        $saldoAkhir = $saldoAwal + $labaBersih + $setoranModal - $prive;

        $pdf = Pdf::loadView('laporan.pdf.perubahan_ekuitas', compact(
            'perusahaan', 'startDate', 'endDate', 'saldoAwal', 'labaBersih', 'setoranModal', 'prive', 'saldoAkhir'
        ));
        $pdf->setPaper('a4', 'portrait');
        return $pdf->download('perubahan_ekuitas_' . $startDate . '_' . $endDate . '.pdf');
    }

    public function perubahanEkuitasExcel(Request $request)
    {
        $startDate = $request->input('start_date', date('Y-m-01'));
        $endDate = $request->input('end_date', date('Y-m-d'));

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'LAPORAN PERUBAHAN EKUITAS');
        $sheet->setCellValue('A2', 'Periode: ' . $startDate . ' s/d ' . $endDate);

        $saldoAwalAkunEkuitas = JurnalDetail::whereHas('akun', function ($q) {
            $q->where('tipe_akun', 'Ekuitas');
        })
            ->whereHas('jurnal', function ($q) use ($startDate) {
                $q->where('tanggal', '<', $startDate);
            })
            ->sum(DB::raw('kredit - debit'));

        $labaDitahanAwal = $this->hitungLabaRugi(date('Y-m-d', strtotime($startDate . ' -1 day')));
        $saldoAwal = $saldoAwalAkunEkuitas + $labaDitahanAwal;

        $labaBersih = $this->hitungLabaRugiPeriode($startDate, $endDate);

        $setoranModal = JurnalDetail::whereHas('akun', function ($q) {
            $q->where('tipe_akun', 'Ekuitas');
        })
            ->whereHas('jurnal', function ($q) use ($startDate, $endDate) {
                $q->whereBetween('tanggal', [$startDate, $endDate]);
            })
            ->sum('kredit');

        $prive = JurnalDetail::whereHas('akun', function ($q) {
            $q->where('tipe_akun', 'Ekuitas');
        })
            ->whereHas('jurnal', function ($q) use ($startDate, $endDate) {
                $q->whereBetween('tanggal', [$startDate, $endDate]);
            })
            ->sum('debit');

        $saldoAkhir = $saldoAwal + $labaBersih + $setoranModal - $prive;

        $sheet->setCellValue('A4', 'Komponen Modal');
        $sheet->setCellValue('B4', 'Jumlah');

        $sheet->setCellValue('A5', 'Saldo Awal Ekuitas');
        $sheet->setCellValue('B5', $saldoAwal);
        $sheet->setCellValue('A6', 'Kenaikan Bersih Modal (Laba)');
        $sheet->setCellValue('B6', $labaBersih);
        $sheet->setCellValue('A7', 'Setoran Modal Tambahan');
        $sheet->setCellValue('B7', $setoranModal);
        $sheet->setCellValue('A8', 'Prive/Penarikan Pemilik');
        $sheet->setCellValue('B8', -$prive);
        $sheet->setCellValue('A9', 'Saldo Akhir Ekuitas');
        $sheet->setCellValue('B9', $saldoAkhir);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        return response()->streamDownload(function() use ($writer) {
            $writer->save('php://output');
        }, 'perubahan_ekuitas_' . $startDate . '_' . $endDate . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function persediaanPdf()
    {
        $perusahaan = DB::table('perusahaan')->find(1);
        $persediaan = Persediaan::orderBy('nama_barang')->get();
        $totalNilai = $persediaan->sum(function ($item) {
            return $item->stok_saat_ini * $item->harga_beli;
        });

        $pdf = Pdf::loadView('laporan.pdf.persediaan', compact('perusahaan', 'persediaan', 'totalNilai'));
        $pdf->setPaper('a4', 'portrait');
        return $pdf->download('laporan_persediaan_' . date('Y-m-d') . '.pdf');
    }

    public function persediaanExcel()
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'LAPORAN NILAI PERSEDIAAN BARANG');
        $sheet->setCellValue('A2', 'Tanggal: ' . date('d-m-Y'));

        $persediaan = Persediaan::orderBy('nama_barang')->get();

        $sheet->setCellValue('A4', 'Kode Barang');
        $sheet->setCellValue('B4', 'Nama Barang');
        $sheet->setCellValue('C4', 'Satuan');
        $sheet->setCellValue('D4', 'Stok Saat Ini');
        $sheet->setCellValue('E4', 'Harga Beli');
        $sheet->setCellValue('F4', 'Total Nilai');

        $rowNum = 5;
        $grandTotal = 0;
        foreach ($persediaan as $item) {
            $nilai = $item->stok_saat_ini * $item->harga_beli;
            $grandTotal += $nilai;

            $sheet->setCellValue('A' . $rowNum, $item->kode_barang);
            $sheet->setCellValue('B' . $rowNum, $item->nama_barang);
            $sheet->setCellValue('C' . $rowNum, $item->satuan);
            $sheet->setCellValue('D' . $rowNum, $item->stok_saat_ini);
            $sheet->setCellValue('E' . $rowNum, $item->harga_beli);
            $sheet->setCellValue('F' . $rowNum, $nilai);
            $rowNum++;
        }

        $sheet->setCellValue('E' . $rowNum, 'TOTAL NILAI PERSEDIAAN');
        $sheet->setCellValue('F' . $rowNum, $grandTotal);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        return response()->streamDownload(function() use ($writer) {
            $writer->save('php://output');
        }, 'laporan_persediaan_' . date('Y-m-d') . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
