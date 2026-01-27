<?php

namespace App\Http\Controllers;

use App\Models\Proyek;
use App\Models\Jurnal;
use App\Models\Penjualan;
use App\Models\Pembelian;
use Illuminate\Http\Request;

class ProyekController extends Controller
{
    /**
     * Display a listing of projects.
     */
    public function index()
    {
        $proyeks = Proyek::orderBy('kode_proyek')->paginate(15);
        return view('proyek.index', compact('proyeks'));
    }

    /**
     * Show the form for creating a new project.
     */
    public function create()
    {
        return view('proyek.form', [
            'proyek' => new Proyek(),
            'isEdit' => false,
        ]);
    }

    /**
     * Store a newly created project.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode_proyek' => 'required|string|max:20|unique:proyek,kode_proyek',
            'nama_proyek' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'status' => 'required|in:Aktif,Selesai,Ditunda',
            'tanggal_mulai' => 'nullable|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
            'anggaran' => 'nullable|numeric|min:0',
            'lokasi' => 'nullable|string|max:255',
            'pelanggan' => 'nullable|string|max:255',
        ]);

        Proyek::create($validated);

        return redirect()->route('proyek.index')
            ->with('success', 'Proyek berhasil ditambahkan!');
    }

    /**
     * Display the specified project with financial summary.
     */
    public function show($id)
    {
        $proyek = Proyek::findOrFail($id);

        // Calculate financial summary
        $totalPendapatan = $proyek->total_pendapatan;
        $totalBeban = $proyek->total_beban;
        $labaRugi = $proyek->laba_rugi;

        // Get recent transactions
        $jurnals = Jurnal::where('id_proyek', $id)
            ->orderBy('tanggal', 'desc')
            ->limit(10)
            ->get();

        $penjualans = Penjualan::where('id_proyek', $id)
            ->orderBy('tanggal_faktur', 'desc')
            ->limit(10)
            ->get();

        $pembelians = Pembelian::where('id_proyek', $id)
            ->orderBy('tanggal_faktur', 'desc')
            ->limit(10)
            ->get();

        return view('proyek.show', compact(
            'proyek',
            'totalPendapatan',
            'totalBeban',
            'labaRugi',
            'jurnals',
            'penjualans',
            'pembelians'
        ));
    }

    /**
     * Show the form for editing the specified project.
     */
    public function edit($id)
    {
        $proyek = Proyek::findOrFail($id);
        return view('proyek.form', [
            'proyek' => $proyek,
            'isEdit' => true,
        ]);
    }

    /**
     * Update the specified project.
     */
    public function update(Request $request, $id)
    {
        $proyek = Proyek::findOrFail($id);

        $validated = $request->validate([
            'kode_proyek' => 'required|string|max:20|unique:proyek,kode_proyek,' . $id . ',id_proyek',
            'nama_proyek' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'status' => 'required|in:Aktif,Selesai,Ditunda',
            'tanggal_mulai' => 'nullable|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
            'anggaran' => 'nullable|numeric|min:0',
            'lokasi' => 'nullable|string|max:255',
            'pelanggan' => 'nullable|string|max:255',
        ]);

        $proyek->update($validated);

        return redirect()->route('proyek.index')
            ->with('success', 'Proyek berhasil diperbarui!');
    }

    /**
     * Remove the specified project.
     */
    public function destroy($id)
    {
        $proyek = Proyek::findOrFail($id);

        // Check if project has transactions
        $hasTransactions = Jurnal::where('id_proyek', $id)->exists()
            || Penjualan::where('id_proyek', $id)->exists()
            || Pembelian::where('id_proyek', $id)->exists();

        if ($hasTransactions) {
            return redirect()->route('proyek.index')
                ->with('error', 'Proyek tidak dapat dihapus karena memiliki transaksi terkait!');
        }

        $proyek->delete();

        return redirect()->route('proyek.index')
            ->with('success', 'Proyek berhasil dihapus!');
    }

    /**
     * Show page for bulk assignment of transactions to project.
     */
    public function assignTransaksi(Request $request)
    {
        $proyeks = Proyek::aktif()->orderBy('kode_proyek')->get();

        $filter = $request->input('filter', 'jurnal');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $transaksis = collect();

        if ($filter === 'jurnal') {
            $query = Jurnal::whereNull('id_proyek');
            if ($startDate)
                $query->where('tanggal', '>=', $startDate);
            if ($endDate)
                $query->where('tanggal', '<=', $endDate);
            $transaksis = $query->orderBy('tanggal', 'desc')->paginate(20);
        } elseif ($filter === 'penjualan') {
            $query = Penjualan::whereNull('id_proyek')->with('pelanggan');
            if ($startDate)
                $query->where('tanggal_faktur', '>=', $startDate);
            if ($endDate)
                $query->where('tanggal_faktur', '<=', $endDate);
            $transaksis = $query->orderBy('tanggal_faktur', 'desc')->paginate(20);
        } elseif ($filter === 'pembelian') {
            $query = Pembelian::whereNull('id_proyek')->with('pemasok');
            if ($startDate)
                $query->where('tanggal_faktur', '>=', $startDate);
            if ($endDate)
                $query->where('tanggal_faktur', '<=', $endDate);
            $transaksis = $query->orderBy('tanggal_faktur', 'desc')->paginate(20);
        }

        return view('proyek.assign', compact('proyeks', 'transaksis', 'filter', 'startDate', 'endDate'));
    }

    /**
     * Process bulk assignment of transactions to project.
     */
    public function processBulkAssign(Request $request)
    {
        $validated = $request->validate([
            'id_proyek' => 'required|exists:proyek,id_proyek',
            'filter' => 'required|in:jurnal,penjualan,pembelian',
            'transaksi_ids' => 'required|array|min:1',
            'transaksi_ids.*' => 'integer',
        ]);

        $idProyek = $validated['id_proyek'];
        $ids = $validated['transaksi_ids'];
        $filter = $validated['filter'];

        $count = 0;

        if ($filter === 'jurnal') {
            $count = Jurnal::whereIn('id_jurnal', $ids)->update(['id_proyek' => $idProyek]);
            // Also update jurnal_detail
            \App\Models\JurnalDetail::whereIn('id_jurnal', $ids)->update(['id_proyek' => $idProyek]);
        } elseif ($filter === 'penjualan') {
            $count = Penjualan::whereIn('id_penjualan', $ids)->update(['id_proyek' => $idProyek]);
        } elseif ($filter === 'pembelian') {
            $count = Pembelian::whereIn('id_pembelian', $ids)->update(['id_proyek' => $idProyek]);
        }

        return redirect()->route('proyek.assign')
            ->with('success', "$count transaksi berhasil di-assign ke proyek!");
    }
}
