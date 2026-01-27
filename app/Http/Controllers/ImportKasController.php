<?php

namespace App\Http\Controllers;

use App\Models\ImportKasStaging;
use App\Models\Akun;
use App\Models\Jurnal;
use App\Models\JurnalDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ImportKasController extends Controller
{
    /**
     * Halaman utama import kas
     */
    public function index()
    {
        $hasPendingData = ImportKasStaging::where('user_id', Auth::id())
            ->where('is_posted', false)
            ->exists();

        return view('import-kas.index', compact('hasPendingData'));
    }

    /**
     * Handle upload CSV dan simpan ke staging
     */
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        $file = $request->file('file');
        $batchId = Str::uuid()->toString();
        $userId = Auth::id();

        try {
            $handle = fopen($file->getPathname(), 'r');
            
            // Skip BOM if present
            $bom = fread($handle, 3);
            if ($bom !== chr(0xEF).chr(0xBB).chr(0xBF)) {
                rewind($handle);
            }
            
            // Read header
            $header = fgetcsv($handle, 0, ';');
            
            if (!$header || count($header) < 5) {
                fclose($handle);
                return back()->with('error', 'Format header tidak valid. Pastikan menggunakan separator titik koma (;) dengan kolom: No;Tanggal;Uraian;Uang Masuk;Uang Keluar');
            }

            $imported = 0;
            $errors = [];
            $lineNumber = 1;

            while (($row = fgetcsv($handle, 0, ';')) !== false) {
                $lineNumber++;
                
                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }

                // Ensure we have enough columns
                if (count($row) < 5) {
                    $errors[] = "Baris {$lineNumber}: Kolom tidak lengkap";
                    continue;
                }

                try {
                    $tanggal = $this->parseDate($row[1]);
                    if (!$tanggal) {
                        $errors[] = "Baris {$lineNumber}: Format tanggal tidak valid ({$row[1]})";
                        continue;
                    }

                    $uangMasuk = $this->parseNumber($row[3]);
                    $uangKeluar = $this->parseNumber($row[4]);

                    ImportKasStaging::create([
                        'user_id' => $userId,
                        'batch_id' => $batchId,
                        'no_referensi' => trim($row[0]),
                        'tanggal' => $tanggal,
                        'uraian' => trim($row[2]),
                        'uang_masuk' => $uangMasuk,
                        'uang_keluar' => $uangKeluar,
                        'is_selected' => true, // Default selected
                        'is_posted' => false,
                    ]);

                    $imported++;
                } catch (\Exception $e) {
                    $errors[] = "Baris {$lineNumber}: " . $e->getMessage();
                }
            }

            fclose($handle);

            if ($imported === 0) {
                return back()->with('error', 'Tidak ada data yang berhasil diimport. ' . implode('; ', array_slice($errors, 0, 3)));
            }

            $message = "Berhasil mengimport {$imported} transaksi.";
            if (count($errors) > 0) {
                $message .= " Ada " . count($errors) . " baris yang gagal.";
            }

            return redirect()->route('import-kas.review')->with('success', $message);

        } catch (\Exception $e) {
            return back()->with('error', 'Gagal membaca file: ' . $e->getMessage());
        }
    }

    /**
     * Tampilkan data staging untuk review
     */
    public function review()
    {
        $data = ImportKasStaging::where('user_id', Auth::id())
            ->where('is_posted', false)
            ->orderBy('tanggal')
            ->orderBy('id')
            ->get();

        // Get akun kas (1-xxxx) dan akun pendapatan/biaya
        $akunKas = Akun::where('kode_akun', 'like', '1-1%')
            ->orWhere('kode_akun', 'like', '1-2%')
            ->orderBy('kode_akun')
            ->get();

        $akunPendapatan = Akun::where('kode_akun', 'like', '4-%')
            ->orderBy('kode_akun')
            ->get();

        $akunBiaya = Akun::where('kode_akun', 'like', '5-%')
            ->orWhere('kode_akun', 'like', '6-%')
            ->orderBy('kode_akun')
            ->get();

        // Calculate totals
        $totalMasuk = $data->where('is_selected', true)->sum('uang_masuk');
        $totalKeluar = $data->where('is_selected', true)->sum('uang_keluar');

        return view('import-kas.review', compact('data', 'akunKas', 'akunPendapatan', 'akunBiaya', 'totalMasuk', 'totalKeluar'));
    }

    /**
     * Update checkbox selection via AJAX
     */
    public function updateSelection(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'is_selected' => 'required|boolean',
        ]);

        $item = ImportKasStaging::where('id', $request->id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $item->is_selected = $request->is_selected;
        $item->save();

        return response()->json(['success' => true]);
    }

    /**
     * Update akun yang dipilih via AJAX
     */
    public function updateAkun(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'field' => 'required|in:kode_akun_kas,kode_akun_lawan',
            'value' => 'nullable|string',
        ]);

        $item = ImportKasStaging::where('id', $request->id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $item->{$request->field} = $request->value ?: null;
        $item->save();

        return response()->json(['success' => true]);
    }

    /**
     * Bulk update akun untuk semua rows
     */
    public function bulkUpdateAkun(Request $request)
    {
        $request->validate([
            'field' => 'required|in:kode_akun_kas,kode_akun_lawan_masuk,kode_akun_lawan_keluar',
            'value' => 'nullable|string',
        ]);

        $query = ImportKasStaging::where('user_id', Auth::id())
            ->where('is_posted', false)
            ->where('is_selected', true);

        if ($request->field === 'kode_akun_kas') {
            $query->update(['kode_akun_kas' => $request->value]);
        } elseif ($request->field === 'kode_akun_lawan_masuk') {
            $query->where('uang_masuk', '>', 0)->update(['kode_akun_lawan' => $request->value]);
        } elseif ($request->field === 'kode_akun_lawan_keluar') {
            $query->where('uang_keluar', '>', 0)->update(['kode_akun_lawan' => $request->value]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Generate jurnal dari data yang dipilih
     */
    public function post(Request $request)
    {
        $items = ImportKasStaging::where('user_id', Auth::id())
            ->where('is_posted', false)
            ->where('is_selected', true)
            ->get();

        if ($items->isEmpty()) {
            return back()->with('error', 'Tidak ada data yang dipilih untuk diposting.');
        }

        // Validate all selected items have required akun
        $incomplete = $items->filter(function ($item) {
            return empty($item->kode_akun_kas) || empty($item->kode_akun_lawan);
        });

        if ($incomplete->isNotEmpty()) {
            return back()->with('error', 'Masih ada ' . $incomplete->count() . ' transaksi yang belum lengkap akun kas atau akun lawannya.');
        }

        try {
            DB::beginTransaction();

            $jurnalCount = 0;

            foreach ($items as $item) {
                // Generate nomor transaksi
                $lastJurnal = Jurnal::where('sumber_jurnal', 'Import Kas')->orderBy('id_jurnal', 'desc')->first();
                $nextNo = 1;
                if ($lastJurnal && preg_match('/IK-(\d+)/', $lastJurnal->no_transaksi, $matches)) {
                    $nextNo = (int)$matches[1] + 1;
                }
                $noTransaksi = 'IK-' . str_pad($nextNo, 5, '0', STR_PAD_LEFT);

                // Determine debit and kredit based on cash in/out
                $amount = $item->getAmount();
                
                if ($item->isCashIn()) {
                    // Kas Masuk: Debit Kas, Kredit Pendapatan
                    $debitAkun = $item->kode_akun_kas;
                    $kreditAkun = $item->kode_akun_lawan;
                } else {
                    // Kas Keluar: Debit Biaya, Kredit Kas
                    $debitAkun = $item->kode_akun_lawan;
                    $kreditAkun = $item->kode_akun_kas;
                }

                // Create Jurnal
                $jurnal = Jurnal::create([
                    'no_transaksi' => $noTransaksi,
                    'tanggal' => $item->tanggal,
                    'deskripsi' => $item->uraian,
                    'sumber_jurnal' => 'Import Kas',
                    'is_locked' => 0,
                ]);

                // Create Jurnal Details
                JurnalDetail::create([
                    'id_jurnal' => $jurnal->id_jurnal,
                    'kode_akun' => $debitAkun,
                    'debit' => $amount,
                    'kredit' => 0,
                ]);

                JurnalDetail::create([
                    'id_jurnal' => $jurnal->id_jurnal,
                    'kode_akun' => $kreditAkun,
                    'debit' => 0,
                    'kredit' => $amount,
                ]);

                // Mark as posted
                $item->is_posted = true;
                $item->save();

                $jurnalCount++;
            }

            DB::commit();

            return redirect()->route('import-kas.index')
                ->with('success', "Berhasil membuat {$jurnalCount} jurnal dari import kas.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal posting jurnal: ' . $e->getMessage());
        }
    }

    /**
     * Hapus data staging
     */
    public function clear()
    {
        ImportKasStaging::where('user_id', Auth::id())
            ->where('is_posted', false)
            ->delete();

        return redirect()->route('import-kas.index')
            ->with('success', 'Data import berhasil dihapus.');
    }

    /**
     * Parse date from various formats
     */
    private function parseDate(string $dateStr): ?string
    {
        $dateStr = trim($dateStr);
        
        // Try DD-MM-YYYY or DD/MM/YYYY
        if (preg_match('/^(\d{1,2})[-\/](\d{1,2})[-\/](\d{4})$/', $dateStr, $matches)) {
            return $matches[3] . '-' . str_pad($matches[2], 2, '0', STR_PAD_LEFT) . '-' . str_pad($matches[1], 2, '0', STR_PAD_LEFT);
        }
        
        // Try YYYY-MM-DD
        if (preg_match('/^(\d{4})[-\/](\d{1,2})[-\/](\d{1,2})$/', $dateStr, $matches)) {
            return $dateStr;
        }

        return null;
    }

    /**
     * Parse number from string (handle comma as decimal separator)
     */
    private function parseNumber(string $numStr): float
    {
        $numStr = trim($numStr);
        
        // Remove thousand separators (.) and convert comma to dot
        $numStr = str_replace('.', '', $numStr);
        $numStr = str_replace(',', '.', $numStr);
        
        return (float) $numStr;
    }
}
