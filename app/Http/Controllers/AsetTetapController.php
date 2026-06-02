<?php

namespace App\Http\Controllers;

use App\Models\AsetTetap;
use App\Models\DepresiasiHistory;
use App\Models\Akun;
use App\Models\Jurnal;
use App\Models\JurnalDetail;
use App\Traits\CheckLockedPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class AsetTetapController extends Controller
{
    use CheckLockedPeriod;

    /**
     * Tampilkan daftar aset tetap
     */
    public function index()
    {
        $assets = AsetTetap::with(['akunAset', 'akunAkumulasi', 'akunBeban'])
            ->orderBy('kode_aset')
            ->get();

        // Hitung akumulasi depresiasi terkini untuk masing-masing aset
        foreach ($assets as $asset) {
            $asset->total_akumulasi = DepresiasiHistory::where('id_aset', $asset->id)->sum('jumlah_depresiasi');
            $asset->nilai_buku = $asset->harga_perolehan - $asset->total_akumulasi;
        }

        return view('aset-tetap.index', compact('assets'));
    }

    /**
     * Form tambah aset tetap
     */
    public function create()
    {
        $akunAset = Akun::where('tipe_akun', 'Aset Tetap')->orderBy('kode_akun')->get();
        $akunAkumulasi = Akun::where('tipe_akun', 'Aset Tetap')->orderBy('kode_akun')->get();
        $akunBeban = Akun::where('tipe_akun', 'Beban')->orderBy('kode_akun')->get();

        return view('aset-tetap.form', compact('akunAset', 'akunAkumulasi', 'akunBeban'));
    }

    /**
     * Simpan aset tetap baru
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode_aset' => 'required|string|max:50|unique:master_aset_tetap,kode_aset',
            'nama_aset' => 'required|string|max:255',
            'tanggal_perolehan' => 'required|date',
            'harga_perolehan' => 'required|numeric|min:0',
            'nilai_residu' => 'required|numeric|min:0',
            'umur_ekonomis' => 'required|integer|min:1',
            'kode_akun_aset' => 'required|exists:akun,kode_akun',
            'kode_akun_akumulasi' => 'required|exists:akun,kode_akun',
            'kode_akun_beban' => 'required|exists:akun,kode_akun',
        ]);

        $this->checkLockedPeriod($validated['tanggal_perolehan']);

        AsetTetap::create($validated);

        return redirect()->route('aset-tetap.index')
            ->with('success', 'Aset Tetap berhasil ditambahkan.');
    }

    /**
     * Form edit aset tetap
     */
    public function edit($id)
    {
        $asset = AsetTetap::findOrFail($id);
        $akunAset = Akun::where('tipe_akun', 'Aset Tetap')->orderBy('kode_akun')->get();
        $akunAkumulasi = Akun::where('tipe_akun', 'Aset Tetap')->orderBy('kode_akun')->get();
        $akunBeban = Akun::where('tipe_akun', 'Beban')->orderBy('kode_akun')->get();

        return view('aset-tetap.form', compact('asset', 'akunAset', 'akunAkumulasi', 'akunBeban'));
    }

    /**
     * Perbarui aset tetap
     */
    public function update(Request $request, $id)
    {
        $asset = AsetTetap::findOrFail($id);

        $validated = $request->validate([
            'kode_aset' => 'required|string|max:50|unique:master_aset_tetap,kode_aset,' . $id,
            'nama_aset' => 'required|string|max:255',
            'tanggal_perolehan' => 'required|date',
            'harga_perolehan' => 'required|numeric|min:0',
            'nilai_residu' => 'required|numeric|min:0',
            'umur_ekonomis' => 'required|integer|min:1',
            'kode_akun_aset' => 'required|exists:akun,kode_akun',
            'kode_akun_akumulasi' => 'required|exists:akun,kode_akun',
            'kode_akun_beban' => 'required|exists:akun,kode_akun',
            'status' => 'required|in:Aktif,Habis,Terjual',
        ]);

        $this->checkLockedPeriod($validated['tanggal_perolehan']);
        $this->checkLockedPeriod($asset->tanggal_perolehan);

        $asset->update($validated);

        return redirect()->route('aset-tetap.index')
            ->with('success', 'Aset Tetap berhasil diperbarui.');
    }

    /**
     * Hapus aset tetap
     */
    public function destroy($id)
    {
        $asset = AsetTetap::findOrFail($id);
        $this->checkLockedPeriod($asset->tanggal_perolehan);

        // Periksa apakah sudah ada riwayat depresiasi
        if (DepresiasiHistory::where('id_aset', $asset->id)->exists()) {
            return back()->with('error', 'Aset Tetap tidak dapat dihapus karena sudah memiliki riwayat depresiasi. Silakan hapus riwayat depresiasi terlebih dahulu.');
        }

        $asset->delete();

        return redirect()->route('aset-tetap.index')
            ->with('success', 'Aset Tetap berhasil dihapus.');
    }

    /**
     * Tampilkan form penyusutan bulanan
     */
    public function showDepresiasiForm()
    {
        $periodeDefault = date('Y-m');
        
        // Cari riwayat depresiasi yang sudah dilakukan
        $histories = DepresiasiHistory::with(['aset', 'jurnal'])
            ->orderBy('periode', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15);

        return view('aset-tetap.depresiasi', compact('periodeDefault', 'histories'));
    }

    /**
     * Proses jalankan depresiasi bulanan
     */
    public function processDepresiasi(Request $request)
    {
        $validated = $request->validate([
            'periode' => 'required|string|regex:/^\d{4}-\d{2}$/',
        ]);

        $periode = $validated['periode'];
        
        // Dapatkan hari terakhir dari bulan periode tersebut untuk tanggal jurnal
        $tanggalJurnal = date('Y-m-t', strtotime($periode . '-01'));

        $this->checkLockedPeriod($tanggalJurnal);

        // Ambil semua aset tetap yang aktif, tanggal perolehan <= tanggal akhir periode,
        // dan belum disusutkan pada periode tersebut
        $assets = AsetTetap::where('status', 'Aktif')
            ->where('tanggal_perolehan', '<=', $tanggalJurnal)
            ->whereNotExists(function ($query) use ($periode) {
                $query->select(DB::raw(1))
                    ->from('depresiasi_history')
                    ->whereColumn('depresiasi_history.id_aset', 'master_aset_tetap.id')
                    ->where('depresiasi_history.periode', $periode);
            })
            ->get();

        if ($assets->isEmpty()) {
            return back()->with('info', 'Tidak ada aset tetap yang membutuhkan penyusutan untuk periode ' . $periode . '.');
        }

        try {
            DB::beginTransaction();

            $successCount = 0;

            foreach ($assets as $asset) {
                // Hitung akumulasi saat ini untuk mengecek apakah sudah habis disusutkan
                $totalSut = DepresiasiHistory::where('id_aset', $asset->id)->sum('jumlah_depresiasi');
                $sisaNilaiDisusutkan = $asset->harga_perolehan - $asset->nilai_residu - $totalSut;

                if ($sisaNilaiDisusutkan <= 0) {
                    // Update status aset menjadi Habis
                    $asset->update(['status' => 'Habis']);
                    continue;
                }

                $nilaiDepresiasi = $asset->hitungDepresiasiBulanan();
                
                // Jika nilai depresiasi melebihi sisa nilai yang bisa disusutkan (penyusutan terakhir)
                if ($nilaiDepresiasi > $sisaNilaiDisusutkan) {
                    $nilaiDepresiasi = $sisaNilaiDisusutkan;
                }

                // 1. Buat nomor bukti jurnal otomatis
                $lastJurnal = Jurnal::where('no_transaksi', 'like', 'DP-' . str_replace('-', '', $periode) . '%')
                    ->orderBy('id_jurnal', 'desc')
                    ->first();
                $nextNo = 1;
                if ($lastJurnal && preg_match('/DP-\d{6}-(\d+)/', $lastJurnal->no_transaksi, $matches)) {
                    $nextNo = (int)$matches[1] + 1;
                }
                $noTransaksi = 'DP-' . str_replace('-', '', $periode) . '-' . str_pad($nextNo, 3, '0', STR_PAD_LEFT);

                // 2. Buat Jurnal Umum
                $jurnal = Jurnal::create([
                    'no_transaksi' => $noTransaksi,
                    'tanggal' => $tanggalJurnal,
                    'deskripsi' => 'Penyusutan Aset Tetap [' . $asset->kode_aset . '] - ' . $asset->nama_aset . ' Periode ' . $periode,
                    'sumber_jurnal' => 'Depresiasi Aset',
                    'is_locked' => 0,
                ]);

                // 3. Detail Jurnal: Debit Beban Penyusutan, Kredit Akumulasi Penyusutan
                JurnalDetail::create([
                    'id_jurnal' => $jurnal->id_jurnal,
                    'kode_akun' => $asset->kode_akun_beban,
                    'debit' => $nilaiDepresiasi,
                    'kredit' => 0,
                ]);

                JurnalDetail::create([
                    'id_jurnal' => $jurnal->id_jurnal,
                    'kode_akun' => $asset->kode_akun_akumulasi,
                    'debit' => 0,
                    'kredit' => $nilaiDepresiasi,
                ]);

                // 4. Catat riwayat depresiasi
                DepresiasiHistory::create([
                    'id_aset' => $asset->id,
                    'id_jurnal' => $jurnal->id_jurnal,
                    'periode' => $periode,
                    'jumlah_depresiasi' => $nilaiDepresiasi,
                ]);

                // Cek lagi setelah penyusutan ini apakah sudah habis
                if (($totalSut + $nilaiDepresiasi) >= ($asset->harga_perolehan - $asset->nilai_residu)) {
                    $asset->update(['status' => 'Habis']);
                }

                $successCount++;
            }

            DB::commit();

            return redirect()->route('aset-tetap.depresiasi')
                ->with('success', 'Berhasil menjalankan depresiasi bulanan untuk ' . $successCount . ' aset tetap pada periode ' . $periode . '.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menjalankan depresiasi: ' . $e->getMessage());
        }
    }

    /**
     * Batalkan depresiasi bulanan
     */
    public function destroyDepresiasi($id)
    {
        $history = DepresiasiHistory::findOrFail($id);
        
        $tanggalJurnal = date('Y-m-t', strtotime($history->periode . '-01'));
        $this->checkLockedPeriod($tanggalJurnal);

        try {
            DB::beginTransaction();

            // Ambil jurnal terkait
            $jurnal = Jurnal::find($history->id_jurnal);

            // Hapus riwayat depresiasi (foreign key cascade akan menghapus jurnal detail, 
            // dan event deleting pada Jurnal akan membersihkan detailnya juga)
            if ($jurnal) {
                $jurnal->delete(); // Jurnal deleting event will trigger details deletion
            }

            // Kembalikan status aset tetap jika habis
            $asset = AsetTetap::find($history->id_aset);
            if ($asset && $asset->status === 'Habis') {
                $asset->update(['status' => 'Aktif']);
            }

            $history->delete();

            DB::commit();

            return redirect()->route('aset-tetap.depresiasi')
                ->with('success', 'Penyusutan berhasil dibatalkan dan jurnal terkait telah dihapus.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal membatalkan penyusutan: ' . $e->getMessage());
        }
    }
}
