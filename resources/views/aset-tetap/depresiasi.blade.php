@extends('layouts.app')

@section('title', 'Depresiasi Bulanan - Simple Akunting')

@section('content')
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Penyusutan Aset Tetap</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="{{ route('aset-tetap.index') }}" class="btn btn-sm btn-outline-secondary">
                <span data-feather="arrow-left"></span> Daftar Aset Tetap
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session('info'))
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            {{ session('info') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        @if(auth()->user()->role === 'superuser' || auth()->user()->role === 'admin')
            <!-- Panel Jalankan Depresiasi -->
            <div class="col-md-4">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-primary text-white py-3">
                        <h5 class="card-title mb-0 h6 fw-bold">Jalankan Penyusutan Otomatis</h5>
                    </div>
                    <div class="card-body p-4">
                        <form action="{{ route('aset-tetap.depresiasi.proses') }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label for="periode" class="form-label fw-semibold">Pilih Periode Bulan <span class="text-danger">*</span></label>
                                <input type="month" class="form-control" id="periode" name="periode" value="{{ old('periode', $periodeDefault) }}" required>
                                <div class="form-text mt-2 text-muted">
                                    Sistem akan menghitung penyusutan garis lurus untuk semua aset yang aktif dan membuat jurnal penyesuaian otomatis di akhir bulan bersangkutan.
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 py-2">
                                <span data-feather="play"></span> Proses Penyusutan
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        <!-- Daftar Riwayat Depresiasi -->
        <div class="{{ (auth()->user()->role === 'superuser' || auth()->user()->role === 'admin') ? 'col-md-8' : 'col-md-12' }}">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-light py-3">
                    <h5 class="card-title mb-0 h6 fw-bold text-secondary">Riwayat Penyusutan Aset Tetap</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Periode</th>
                                    <th>Aset</th>
                                    <th>Jurnal Bukti</th>
                                    <th class="text-end">Jumlah Penyusutan</th>
                                    @if(auth()->user()->role === 'superuser' || auth()->user()->role === 'admin')
                                        <th class="text-center">Aksi</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($histories as $history)
                                    <tr>
                                        <td class="fw-bold">{{ date('F Y', strtotime($history->periode . '-01')) }}</td>
                                        <td>
                                            @if ($history->aset)
                                                <div class="fw-semibold">{{ $history->aset->nama_aset }}</div>
                                                <small class="text-muted">{{ $history->aset->kode_aset }}</small>
                                            @else
                                                <span class="text-danger">(Aset Dihapus)</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($history->jurnal)
                                                <div class="text-primary font-monospace">{{ $history->jurnal->no_transaksi }}</div>
                                                <small class="text-muted">{{ $history->jurnal->tanggal->format('d-m-Y') }}</small>
                                            @else
                                                <span class="text-danger">(Jurnal Dihapus)</span>
                                            @endif
                                        </td>
                                        <td class="text-end font-monospace text-success fw-bold">
                                            Rp {{ number_format($history->jumlah_depresiasi, 2, ',', '.') }}
                                        </td>
                                        @if(auth()->user()->role === 'superuser' || auth()->user()->role === 'admin')
                                            <td class="text-center">
                                                <form action="{{ route('aset-tetap.depresiasi.destroy', $history->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan penyusutan ini? Jurnal penyesuaian terkait akan dihapus secara permanen.')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Batalkan & Hapus Jurnal">
                                                        <span data-feather="trash-2" style="width: 14px; height: 14px;"></span> Batalkan
                                                    </button>
                                                </form>
                                            </td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">Belum ada riwayat penyusutan aset tetap.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if ($histories->hasPages())
                        <div class="card-footer bg-white border-0 py-3">
                            {{ $histories->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
