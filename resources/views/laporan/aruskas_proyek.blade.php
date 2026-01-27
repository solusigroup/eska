@extends('layouts.app')

@section('title', 'Arus Kas Per Proyek - Simple Akunting')

@section('content')
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Laporan Arus Kas Per Proyek</h1>
    </div>

    <!-- Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('laporan.aruskas_proyek') }}" method="GET">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label for="id_proyek" class="form-label">Pilih Proyek</label>
                        <select class="form-select" id="id_proyek" name="id_proyek" required>
                            <option value="">-- Pilih Proyek --</option>
                            @foreach($proyeks as $p)
                                <option value="{{ $p->id_proyek }}" {{ $idProyek == $p->id_proyek ? 'selected' : '' }}>
                                    {{ $p->kode_proyek }} - {{ $p->nama_proyek }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="start_date" class="form-label">Dari Tanggal</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" value="{{ $startDate }}">
                    </div>
                    <div class="col-md-3">
                        <label for="end_date" class="form-label">Sampai Tanggal</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" value="{{ $endDate }}">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary">Tampilkan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if($proyek)
        <!-- Report Header -->
        <div class="text-center mb-4">
            <h3>{{ $perusahaan->nama_perusahaan ?? 'Nama Perusahaan Belum Diset' }}</h3>
            <h4>Laporan Arus Kas (Metode Langsung)</h4>
            <h5 class="text-primary">Proyek: {{ $proyek->kode_proyek }} - {{ $proyek->nama_proyek }}</h5>
            <p class="text-muted">
                Periode {{ \Carbon\Carbon::parse($startDate)->format('d F Y') }} s/d {{ \Carbon\Carbon::parse($endDate)->format('d F Y') }}
            </p>
        </div>

        <!-- Report Content -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-light">
                            <tr>
                                <th width="60%">Keterangan</th>
                                <th class="text-end">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- AKTIVITAS OPERASI -->
                            <tr class="fw-bold table-primary"><td colspan="2">AKTIVITAS OPERASI</td></tr>
                            <tr>
                                <td class="ps-4">Penerimaan dari Pelanggan</td>
                                <td class="text-end">Rp {{ number_format($terimaPelanggan, 2, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td class="ps-4">Pembayaran ke Pemasok & Lainnya</td>
                                <td class="text-end text-danger">(Rp {{ number_format($bayarPemasok, 2, ',', '.') }})</td>
                            </tr>
                            <tr class="fw-bold bg-light">
                                <td>Arus Kas Bersih dari Aktivitas Operasi</td>
                                <td class="text-end {{ $arusKasOperasi < 0 ? 'text-danger' : '' }}">
                                    Rp {{ number_format($arusKasOperasi, 2, ',', '.') }}
                                </td>
                            </tr>

                            <!-- AKTIVITAS INVESTASI -->
                            <tr class="fw-bold table-warning"><td colspan="2">AKTIVITAS INVESTASI</td></tr>
                            <tr>
                                <td class="ps-4">Penjualan Aset Tetap</td>
                                <td class="text-end">Rp {{ number_format($jualAset, 2, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td class="ps-4">Pembelian Aset Tetap</td>
                                <td class="text-end text-danger">(Rp {{ number_format($beliAset, 2, ',', '.') }})</td>
                            </tr>
                            <tr class="fw-bold bg-light">
                                <td>Arus Kas Bersih dari Aktivitas Investasi</td>
                                <td class="text-end {{ $arusKasInvestasi < 0 ? 'text-danger' : '' }}">
                                    Rp {{ number_format($arusKasInvestasi, 2, ',', '.') }}
                                </td>
                            </tr>

                            <!-- AKTIVITAS PENDANAAN -->
                            <tr class="fw-bold table-info"><td colspan="2">AKTIVITAS PENDANAAN</td></tr>
                            <tr>
                                <td class="ps-4">Penerimaan Modal/Pinjaman</td>
                                <td class="text-end">Rp {{ number_format($terimaPendanaan, 2, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td class="ps-4">Pembayaran Prive/Pinjaman</td>
                                <td class="text-end text-danger">(Rp {{ number_format($bayarPendanaan, 2, ',', '.') }})</td>
                            </tr>
                            <tr class="fw-bold bg-light">
                                <td>Arus Kas Bersih dari Aktivitas Pendanaan</td>
                                <td class="text-end {{ $arusKasPendanaan < 0 ? 'text-danger' : '' }}">
                                    Rp {{ number_format($arusKasPendanaan, 2, ',', '.') }}
                                </td>
                            </tr>

                            <!-- SUMMARY -->
                            <tr class="fw-bold table-success">
                                <td>Kenaikan (Penurunan) Kas Bersih</td>
                                <td class="text-end {{ $kenaikanKas < 0 ? 'text-danger' : '' }}">
                                    Rp {{ number_format($kenaikanKas, 2, ',', '.') }}
                                </td>
                            </tr>
                            <tr>
                                <td>Saldo Awal Kas</td>
                                <td class="text-end">Rp {{ number_format($saldoAwal, 2, ',', '.') }}</td>
                            </tr>
                            <tr class="fw-bold table-success fs-5">
                                <td>Saldo Akhir Kas</td>
                                <td class="text-end {{ $saldoAkhir < 0 ? 'text-danger' : '' }}">
                                    Rp {{ number_format($saldoAkhir, 2, ',', '.') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @else
        <div class="alert alert-info">
            <span data-feather="info"></span>
            Silakan pilih proyek terlebih dahulu untuk melihat laporan.
        </div>
    @endif
@endsection
