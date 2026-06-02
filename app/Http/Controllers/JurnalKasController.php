<?php

namespace App\Http\Controllers;

use App\Models\JurnalKas;
use App\Models\Proyek;
use App\Models\Akun;
use Illuminate\Http\Request;

class JurnalKasController extends Controller
{
    use \App\Traits\CheckLockedPeriod;

    /**
     * Display a listing of cash journal entries.
     */
    public function index(Request $request)
    {
        $query = JurnalKas::with(['proyek', 'akunKasRef', 'akunLawanRef']);

        // Filter by type
        if ($request->has('tipe') && $request->tipe != '') {
            $query->where('tipe', $request->tipe);
        }

        // Filter by project
        if ($request->has('id_proyek') && $request->id_proyek != '') {
            $query->where('id_proyek', $request->id_proyek);
        }

        // Filter by date range
        if ($request->has('start_date') && $request->start_date != '') {
            $query->where('tanggal', '>=', $request->start_date);
        }
        if ($request->has('end_date') && $request->end_date != '') {
            $query->where('tanggal', '<=', $request->end_date);
        }

        $jurnalKas = $query->orderBy('tanggal', 'desc')->paginate(15);
        $proyeks = Proyek::aktif()->orderBy('kode_proyek')->get();

        return view('jurnal-kas.index', compact('jurnalKas', 'proyeks'));
    }

    /**
     * Show the form for creating a new cash entry.
     */
    public function create(Request $request)
    {
        $tipe = $request->input('tipe', 'Masuk');

        // Get cash/bank accounts
        $akunKas = Akun::where('tipe_akun', 'Kas & Bank')->orderBy('kode_akun')->get();

        // Get all accounts for counter entry
        $akunLawan = Akun::orderBy('kode_akun')->get();

        $proyeks = Proyek::aktif()->orderBy('kode_proyek')->get();

        // Generate nomor bukti
        $prefix = $tipe === 'Masuk' ? 'KM' : 'KK';
        $lastNo = JurnalKas::where('no_bukti', 'like', $prefix . '%')
            ->orderBy('no_bukti', 'desc')
            ->first();
        $nextNo = $lastNo ? intval(substr($lastNo->no_bukti, 2)) + 1 : 1;
        $noBukti = $prefix . str_pad($nextNo, 6, '0', STR_PAD_LEFT);

        return view('jurnal-kas.form', compact('tipe', 'akunKas', 'akunLawan', 'proyeks', 'noBukti'));
    }

    /**
     * Store a newly created cash entry.
     */
    public function store(Request $request)
    {
        $this->checkLockedPeriod($request->tanggal);

        $validated = $request->validate([
            'no_bukti' => 'required|string|max:50|unique:jurnal_kas,no_bukti',
            'tanggal' => 'required|date',
            'tipe' => 'required|in:Masuk,Keluar',
            'akun_kas' => 'required|exists:akun,kode_akun',
            'akun_lawan' => 'required|exists:akun,kode_akun',
            'jumlah' => 'required|numeric|min:0.01',
            'keterangan' => 'nullable|string',
            'id_proyek' => 'nullable|exists:proyek,id_proyek',
        ]);

        // JurnalKas model will auto-create jurnal_umum via boot events
        JurnalKas::create($validated);

        return redirect()->route('jurnal-kas.index')
            ->with('success', 'Jurnal Kas berhasil disimpan!');
    }

    /**
     * Display the specified cash entry.
     */
    public function show($id)
    {
        $jurnalKas = JurnalKas::with(['proyek', 'akunKasRef', 'akunLawanRef', 'jurnal.details'])
            ->findOrFail($id);

        return view('jurnal-kas.show', compact('jurnalKas'));
    }

    /**
     * Show the form for editing the specified cash entry.
     */
    public function edit($id)
    {
        $jurnalKas = JurnalKas::findOrFail($id);
        $tipe = $jurnalKas->tipe;

        $akunKas = Akun::where('tipe_akun', 'Kas & Bank')->orderBy('kode_akun')->get();
        $akunLawan = Akun::orderBy('kode_akun')->get();
        $proyeks = Proyek::aktif()->orderBy('kode_proyek')->get();
        $noBukti = $jurnalKas->no_bukti;

        return view('jurnal-kas.form', compact('jurnalKas', 'tipe', 'akunKas', 'akunLawan', 'proyeks', 'noBukti'));
    }

    /**
     * Update the specified cash entry.
     */
    public function update(Request $request, $id)
    {
        $jurnalKas = JurnalKas::findOrFail($id);

        $this->checkLockedPeriod($request->tanggal);
        $this->checkLockedPeriod($jurnalKas->tanggal);

        $validated = $request->validate([
            'no_bukti' => 'required|string|max:50|unique:jurnal_kas,no_bukti,' . $id . ',id_jurnal_kas',
            'tanggal' => 'required|date',
            'tipe' => 'required|in:Masuk,Keluar',
            'akun_kas' => 'required|exists:akun,kode_akun',
            'akun_lawan' => 'required|exists:akun,kode_akun',
            'jumlah' => 'required|numeric|min:0.01',
            'keterangan' => 'nullable|string',
            'id_proyek' => 'nullable|exists:proyek,id_proyek',
        ]);

        // JurnalKas model will auto-update jurnal_umum via boot events
        $jurnalKas->update($validated);

        return redirect()->route('jurnal-kas.index')
            ->with('success', 'Jurnal Kas berhasil diperbarui!');
    }

    /**
     * Remove the specified cash entry.
     */
    public function destroy($id)
    {
        $jurnalKas = JurnalKas::findOrFail($id);

        $this->checkLockedPeriod($jurnalKas->tanggal);

        // JurnalKas model will auto-delete jurnal_umum via boot events
        $jurnalKas->delete();

        return redirect()->route('jurnal-kas.index')
            ->with('success', 'Jurnal Kas berhasil dihapus!');
    }
}
