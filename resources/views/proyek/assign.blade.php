@extends('layouts.app')

@section('title', 'Assign Transaksi ke Proyek')

@section('content')
    <div class="page-header">
        <h1 class="page-title">Assign Transaksi ke Proyek</h1>
        <p class="page-subtitle">Pilih transaksi lama untuk dihubungkan dengan proyek</p>
    </div>

    <!-- Filter Form -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('proyek.assign') }}" class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label for="filter" class="form-label">Tipe Transaksi</label>
                    <select class="form-select" id="filter" name="filter">
                        <option value="jurnal" {{ $filter == 'jurnal' ? 'selected' : '' }}>Jurnal Umum</option>
                        <option value="penjualan" {{ $filter == 'penjualan' ? 'selected' : '' }}>Penjualan</option>
                        <option value="pembelian" {{ $filter == 'pembelian' ? 'selected' : '' }}>Pembelian</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="start_date" class="form-label">Dari Tanggal</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" value="{{ $startDate }}">
                </div>
                <div class="col-md-2">
                    <label for="end_date" class="form-label">Sampai Tanggal</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" value="{{ $endDate }}">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <span data-feather="search"></span> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Bulk Assignment Form -->
    <form method="POST" action="{{ route('proyek.processBulkAssign') }}">
        @csrf
        <input type="hidden" name="filter" value="{{ $filter }}">

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Transaksi Tanpa Proyek</h5>
                <div class="d-flex gap-2 align-items-center">
                    <select name="id_proyek" class="form-select form-select-sm" style="width: auto" required>
                        <option value="">-- Pilih Proyek --</option>
                        @foreach($proyeks as $p)
                            <option value="{{ $p->id_proyek }}">{{ $p->kode_proyek }} - {{ $p->nama_proyek }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <span data-feather="link"></span> Assign Selected
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th style="width: 40px">
                                    <input type="checkbox" id="selectAll" class="form-check-input"
                                        onclick="toggleAll(this)">
                                </th>
                                <th>Tanggal</th>
                                <th>No. Transaksi</th>
                                <th>Keterangan</th>
                                @if($filter == 'penjualan' || $filter == 'pembelian')
                                    <th class="text-end">Total</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($transaksis as $trx)
                                <tr>
                                    <td>
                                        <input type="checkbox" name="transaksi_ids[]"
                                            value="{{ $filter == 'jurnal' ? $trx->id_jurnal : ($filter == 'penjualan' ? $trx->id_penjualan : $trx->id_pembelian) }}"
                                            class="form-check-input row-checkbox">
                                    </td>
                                    <td>{{ $filter == 'jurnal' ? $trx->tanggal->format('d/m/Y') : $trx->tanggal_faktur->format('d/m/Y') }}
                                    </td>
                                    <td><code>{{ $filter == 'jurnal' ? $trx->no_transaksi : $trx->no_faktur }}</code></td>
                                    <td>
                                        @if($filter == 'jurnal')
                                            {{ Str::limit($trx->deskripsi, 50) }}
                                        @elseif($filter == 'penjualan')
                                            {{ $trx->pelanggan->nama_pelanggan ?? '-' }}
                                        @else
                                            {{ $trx->pemasok->nama_pemasok ?? '-' }}
                                        @endif
                                    </td>
                                    @if($filter == 'penjualan' || $filter == 'pembelian')
                                        <td class="text-end">{{ number_format($trx->total, 0, ',', '.') }}</td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        <span data-feather="check-circle"
                                            style="width: 48px; height: 48px; opacity: 0.5; color: green"></span>
                                        <p class="mt-2 mb-0">Semua transaksi {{ $filter }} sudah memiliki proyek.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                {{ $transaksis->appends(request()->query())->links() }}
            </div>
        </div>
    </form>

    <div class="mt-3">
        <a href="{{ route('proyek.index') }}" class="btn btn-outline-secondary">
            <span data-feather="arrow-left"></span> Kembali ke Daftar Proyek
        </a>
    </div>
@endsection

@push('scripts')
    <script>
        feather.replace({ 'aria-hidden': 'true' });

        function toggleAll(source) {
            var checkboxes = document.querySelectorAll('.row-checkbox');
            checkboxes.forEach(function (cb) {
                cb.checked = source.checked;
            });
        }
    </script>
@endpush