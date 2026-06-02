@extends('layouts.app')

@section('title', 'Tutup Buku Akhir Periode - Simple Akunting')

@section('content')
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Tutup Buku Akhir Periode</h1>
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
        <!-- Panel Form Tutup Buku -->
        <div class="col-md-5">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-danger text-white py-3">
                    <h5 class="card-title mb-0 h6 fw-bold">Proses Tutup Buku Baru</h5>
                </div>
                <div class="card-body p-4">
                    @if ($lastClose)
                        <div class="p-3 bg-light rounded border border-warning mb-3">
                            <h6 class="fw-bold text-dark mb-1">Status Terkini:</h6>
                            <p class="mb-0 text-muted" style="font-size: 0.85rem;">
                                Periode pembukuan telah ditutup dan dikunci sampai dengan tanggal <strong>{{ $lastClose->tanggal_tutup->format('d-m-Y') }}</strong>.
                            </p>
                        </div>
                    @else
                        <div class="p-3 bg-light rounded border mb-3">
                            <h6 class="fw-bold text-dark mb-1">Status Terkini:</h6>
                            <p class="mb-0 text-muted" style="font-size: 0.85rem;">
                                Belum ada penutupan buku periode sebelumnya. Seluruh transaksi dari awal pembukuan masih aktif dan terbuka.
                            </p>
                        </div>
                    @endif

                    <form action="{{ route('tutup-buku.store') }}" method="POST" onsubmit="return confirm('PERINGATAN: Tutup Buku akan mengunci seluruh transaksi sebelum/pada tanggal penutupan secara permanen. Transaksi tidak dapat diubah/ditambah/dihapus lagi. Laba/Rugi bersih akan otomatis dipindahkan ke akun Laba Ditahan (3-30000). Apakah Anda yakin ingin memproses?')">
                        @csrf
                        <div class="mb-3">
                            <label for="tanggal_tutup" class="form-label fw-semibold">Tanggal Tutup Buku <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="tanggal_tutup" name="tanggal_tutup" min="{{ $tanggalMinimal }}" value="{{ old('tanggal_tutup', date('Y-m-t')) }}" required>
                            <div class="form-text text-danger mt-1" style="font-size: 0.8rem;">
                                * Tanggal minimal penutupan: {{ date('d-m-Y', strtotime($tanggalMinimal)) }}
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="keterangan" class="form-label fw-semibold">Keterangan / Memo</label>
                            <textarea class="form-control" id="keterangan" name="keterangan" rows="2" placeholder="Contoh: Tutup Buku Akhir Tahun 2026">{{ old('keterangan') }}</textarea>
                        </div>

                        <button type="submit" class="btn btn-danger w-100 py-2 fw-semibold">
                            <span data-feather="lock"></span> Jalankan Tutup Buku
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Daftar Riwayat Tutup Buku -->
        <div class="col-md-7">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-light py-3">
                    <h5 class="card-title mb-0 h6 fw-bold text-secondary">Riwayat Penutupan Buku</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Tgl Tutup</th>
                                    <th>Keterangan</th>
                                    <th>Jurnal Penutup</th>
                                    <th>Petugas</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($histories as $history)
                                    <tr>
                                        <td class="fw-bold">{{ $history->tanggal_tutup->format('d-m-Y') }}</td>
                                        <td>
                                            <div>{{ $history->keterangan }}</div>
                                            <small class="text-muted d-block" style="font-size: 0.75rem;">
                                                Dibuat: {{ $history->created_at->format('d-m-Y H:i') }}
                                            </small>
                                        </td>
                                        <td>
                                            @if ($history->jurnalPenutup)
                                                <span class="text-primary font-monospace">{{ $history->jurnalPenutup->no_transaksi }}</span>
                                            @else
                                                <span class="text-danger">(Jurnal Dihapus)</span>
                                            @endif
                                        </td>
                                        <td>{{ $history->user->nama_user ?? 'System' }}</td>
                                        <td class="text-center">
                                            @if ($loop->first)
                                                <form action="{{ route('tutup-buku.destroy', $history->id) }}" method="POST" class="d-inline" onsubmit="return confirm('PERINGATAN: Membatalkan Tutup Buku akan menghapus jurnal penutup terkait dan membuka kembali periode transaksi sehingga dapat diubah/ditambah kembali. Apakah Anda yakin?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-warning">
                                                        <span data-feather="unlock" style="width: 14px; height: 14px;"></span> Buka Kembali
                                                    </button>
                                                </form>
                                            @else
                                                <span class="text-muted" style="font-size: 0.8rem;">Terkunci</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">Belum ada riwayat penutupan buku.</td>
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
