@extends('layouts.app')

@section('title', 'Detail Proyek: ' . $proyek->nama_proyek)

@section('content')
    <div class="page-header d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-title">{{ $proyek->nama_proyek }}</h1>
            <p class="page-subtitle">
                <code>{{ $proyek->kode_proyek }}</code> ·
                @if($proyek->status == 'Aktif')
                    <span class="badge bg-success">Aktif</span>
                @elseif($proyek->status == 'Selesai')
                    <span class="badge bg-secondary">Selesai</span>
                @else
                    <span class="badge bg-warning text-dark">Ditunda</span>
                @endif
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('proyek.edit', $proyek->id_proyek) }}" class="btn btn-outline-secondary">
                <span data-feather="edit-2"></span> Edit
            </a>
            <a href="{{ route('proyek.index') }}" class="btn btn-outline-primary">
                <span data-feather="arrow-left"></span> Kembali
            </a>
        </div>
    </div>

    <!-- Financial Summary -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-white bg-primary">
                <div class="card-body">
                    <h6 class="card-title text-white-50">Anggaran</h6>
                    <h4 class="mb-0">Rp {{ number_format($proyek->anggaran, 0, ',', '.') }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-success">
                <div class="card-body">
                    <h6 class="card-title text-white-50">Total Pendapatan</h6>
                    <h4 class="mb-0">Rp {{ number_format($totalPendapatan, 0, ',', '.') }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-danger">
                <div class="card-body">
                    <h6 class="card-title text-white-50">Total Beban</h6>
                    <h4 class="mb-0">Rp {{ number_format($totalBeban, 0, ',', '.') }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white {{ $labaRugi >= 0 ? 'bg-info' : 'bg-warning' }}">
                <div class="card-body">
                    <h6 class="card-title text-white-50">Laba/Rugi</h6>
                    <h4 class="mb-0">Rp {{ number_format($labaRugi, 0, ',', '.') }}</h4>
                </div>
            </div>
        </div>
    </div>

    <!-- Project Details -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Informasi Proyek</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless mb-0">
                        <tr>
                            <td class="text-muted" style="width: 40%">Deskripsi</td>
                            <td>{{ $proyek->deskripsi ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Lokasi</td>
                            <td>{{ $proyek->lokasi ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Pelanggan</td>
                            <td>{{ $proyek->pelanggan ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Tanggal Mulai</td>
                            <td>{{ $proyek->tanggal_mulai ? $proyek->tanggal_mulai->format('d F Y') : '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Tanggal Selesai</td>
                            <td>{{ $proyek->tanggal_selesai ? $proyek->tanggal_selesai->format('d F Y') : '-' }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Jurnal Terbaru</h5>
                    <a href="{{ route('jurnal.index', ['id_proyek' => $proyek->id_proyek]) }}"
                        class="btn btn-sm btn-outline-primary">Lihat Semua</a>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>No. Transaksi</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($jurnals as $jurnal)
                                <tr>
                                    <td>{{ $jurnal->tanggal->format('d/m/Y') }}</td>
                                    <td><code>{{ $jurnal->no_transaksi }}</code></td>
                                    <td>{{ Str::limit($jurnal->deskripsi, 30) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-3">Belum ada jurnal</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Penjualan Terbaru</h5>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>No. Faktur</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($penjualans as $pj)
                                <tr>
                                    <td>{{ $pj->tanggal_faktur->format('d/m/Y') }}</td>
                                    <td><code>{{ $pj->no_faktur }}</code></td>
                                    <td class="text-end">{{ number_format($pj->total, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-3">Belum ada penjualan</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Pembelian Terbaru</h5>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>No. Faktur</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($pembelians as $pb)
                                <tr>
                                    <td>{{ $pb->tanggal_faktur->format('d/m/Y') }}</td>
                                    <td><code>{{ $pb->no_faktur }}</code></td>
                                    <td class="text-end">{{ number_format($pb->total, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-3">Belum ada pembelian</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        feather.replace({ 'aria-hidden': 'true' });
    </script>
@endpush