@extends('layouts.app')

@section('title', 'Laba Rugi Per Proyek - Simple Akunting')

@section('content')
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Laporan Laba Rugi Per Proyek</h1>
    </div>

    <!-- Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('laporan.labarugi_proyek') }}" method="GET">
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
                    <div class="col-md-3 d-flex gap-2 align-items-end">
                        <button type="submit" class="btn btn-primary flex-grow-1">Tampilkan</button>
                        @if($idProyek)
                            <a href="{{ route('laporan.labarugi_proyek.pdf', request()->all()) }}" class="btn btn-danger" target="_blank" title="Cetak PDF">
                                <span data-feather="file-text"></span>
                            </a>
                            <a href="{{ route('laporan.labarugi_proyek.excel', request()->all()) }}" class="btn btn-success" title="Ekspor Excel">
                                <span data-feather="file"></span>
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if($proyek)
        <!-- Report Header -->
        <div class="text-center mb-4">
            <h3>{{ $perusahaan->nama_perusahaan ?? 'Nama Perusahaan Belum Diset' }}</h3>
            <h4>Laporan Laba Rugi</h4>
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
                            <!-- PENDAPATAN -->
                            <tr class="fw-bold table-primary"><td colspan="2">PENDAPATAN</td></tr>
                            @foreach($pendapatan as $akun)
                                <tr>
                                    <td class="ps-4">{{ $akun->kode_akun }} - {{ $akun->nama_akun }}</td>
                                    <td class="text-end">Rp {{ number_format($akun->saldo_periode, 2, ',', '.') }}</td>
                                </tr>
                            @endforeach
                            <tr class="fw-bold bg-light">
                                <td>Total Pendapatan</td>
                                <td class="text-end">Rp {{ number_format($pendapatan->sum('saldo_periode'), 2, ',', '.') }}</td>
                            </tr>

                            <!-- HPP -->
                            <tr class="fw-bold table-warning mt-4"><td colspan="2">HARGA POKOK PENJUALAN</td></tr>
                            @foreach($hpp as $akun)
                                <tr>
                                    <td class="ps-4">{{ $akun->kode_akun }} - {{ $akun->nama_akun }}</td>
                                    <td class="text-end">Rp {{ number_format($akun->saldo_periode, 2, ',', '.') }}</td>
                                </tr>
                            @endforeach
                            <tr class="fw-bold bg-light">
                                <td>Total HPP</td>
                                <td class="text-end">Rp {{ number_format($hpp->sum('saldo_periode'), 2, ',', '.') }}</td>
                            </tr>

                            <!-- LABA KOTOR -->
                            @php $labaKotor = $pendapatan->sum('saldo_periode') - $hpp->sum('saldo_periode'); @endphp
                            <tr class="fw-bold table-success">
                                <td>LABA KOTOR</td>
                                <td class="text-end">Rp {{ number_format($labaKotor, 2, ',', '.') }}</td>
                            </tr>

                            <!-- BEBAN -->
                            <tr class="fw-bold table-danger mt-4"><td colspan="2">BEBAN OPERASIONAL</td></tr>
                            @foreach($beban as $akun)
                                <tr>
                                    <td class="ps-4">{{ $akun->kode_akun }} - {{ $akun->nama_akun }}</td>
                                    <td class="text-end">Rp {{ number_format($akun->saldo_periode, 2, ',', '.') }}</td>
                                </tr>
                            @endforeach
                            <tr class="fw-bold bg-light">
                                <td>Total Beban</td>
                                <td class="text-end">Rp {{ number_format($beban->sum('saldo_periode'), 2, ',', '.') }}</td>
                            </tr>

                            <!-- LABA BERSIH -->
                            @php $labaBersih = $labaKotor - $beban->sum('saldo_periode'); @endphp
                            <tr class="fw-bold table-success fs-5">
                                <td>LABA BERSIH</td>
                                <td class="text-end {{ $labaBersih < 0 ? 'text-danger' : '' }}">
                                    Rp {{ number_format($labaBersih, 2, ',', '.') }}
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
