@extends('layouts.app')

@section('title', 'Laba Rugi Konsolidasi - Simple Akunting')

@section('content')
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Laporan Laba Rugi Konsolidasi</h1>
    </div>

    <!-- Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('laporan.labarugi_konsolidasi') }}" method="GET">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="start_date" class="form-label">Dari Tanggal</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" value="{{ $startDate }}">
                    </div>
                    <div class="col-md-4">
                        <label for="end_date" class="form-label">Sampai Tanggal</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" value="{{ $endDate }}">
                    </div>
                    <div class="col-md-4 d-flex gap-2 align-items-end">
                        <button type="submit" class="btn btn-primary flex-grow-1">Tampilkan</button>
                        <a href="{{ route('laporan.labarugi_konsolidasi.pdf', request()->all()) }}" class="btn btn-danger" target="_blank" title="Cetak PDF">
                            <span data-feather="file-text"></span> PDF
                        </a>
                        <a href="{{ route('laporan.labarugi_konsolidasi.excel', request()->all()) }}" class="btn btn-success" title="Ekspor Excel">
                            <span data-feather="file"></span> Excel
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Report Header -->
    <div class="text-center mb-4">
        <h3>{{ $perusahaan->nama_perusahaan ?? 'Nama Perusahaan Belum Diset' }}</h3>
        <h4>Laporan Laba Rugi Konsolidasi</h4>
        <p class="text-muted">
            Periode {{ \Carbon\Carbon::parse($startDate)->format('d F Y') }} s/d {{ \Carbon\Carbon::parse($endDate)->format('d F Y') }}
        </p>
    </div>

    <!-- Report Content -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th rowspan="2" class="align-middle">Keterangan</th>
                            @foreach($proyeks as $p)
                                <th class="text-center">{{ $p->kode_proyek }}</th>
                            @endforeach
                            <th class="text-center">Non-Proyek</th>
                            <th class="text-center fw-bold">TOTAL</th>
                        </tr>
                        <tr>
                            @foreach($proyeks as $p)
                                <th class="text-center small">{{ \Illuminate\Support\Str::limit($p->nama_proyek, 15) }}</th>
                            @endforeach
                            <th class="text-center small">-</th>
                            <th class="text-center small">-</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- PENDAPATAN -->
                        <tr class="fw-bold table-primary">
                            <td colspan="{{ $proyeks->count() + 3 }}">PENDAPATAN</td>
                        </tr>
                        @foreach($pendapatan as $row)
                            <tr>
                                <td class="ps-4">{{ $row['kode_akun'] }} - {{ $row['nama_akun'] }}</td>
                                @foreach($proyeks as $p)
                                    <td class="text-end">{{ number_format($row['proyek'][$p->id_proyek] ?? 0, 0, ',', '.') }}</td>
                                @endforeach
                                <td class="text-end">{{ number_format($row['non_proyek'], 0, ',', '.') }}</td>
                                <td class="text-end fw-bold">{{ number_format($row['total'], 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                        <tr class="fw-bold bg-light">
                            <td>Total Pendapatan</td>
                            @foreach($proyeks as $p)
                                <td class="text-end">{{ number_format($summaryProyek[$p->id_proyek]['pendapatan'] ?? 0, 0, ',', '.') }}</td>
                            @endforeach
                            <td class="text-end">{{ number_format($summaryNonProyek['pendapatan'], 0, ',', '.') }}</td>
                            <td class="text-end fw-bold">{{ number_format($summaryTotal['pendapatan'], 0, ',', '.') }}</td>
                        </tr>

                        <!-- HPP -->
                        <tr class="fw-bold table-warning">
                            <td colspan="{{ $proyeks->count() + 3 }}">HARGA POKOK PENJUALAN</td>
                        </tr>
                        @foreach($hpp as $row)
                            <tr>
                                <td class="ps-4">{{ $row['kode_akun'] }} - {{ $row['nama_akun'] }}</td>
                                @foreach($proyeks as $p)
                                    <td class="text-end">{{ number_format($row['proyek'][$p->id_proyek] ?? 0, 0, ',', '.') }}</td>
                                @endforeach
                                <td class="text-end">{{ number_format($row['non_proyek'], 0, ',', '.') }}</td>
                                <td class="text-end fw-bold">{{ number_format($row['total'], 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                        <tr class="fw-bold bg-light">
                            <td>Total HPP</td>
                            @foreach($proyeks as $p)
                                <td class="text-end">{{ number_format($summaryProyek[$p->id_proyek]['hpp'] ?? 0, 0, ',', '.') }}</td>
                            @endforeach
                            <td class="text-end">{{ number_format($summaryNonProyek['hpp'], 0, ',', '.') }}</td>
                            <td class="text-end fw-bold">{{ number_format($summaryTotal['hpp'], 0, ',', '.') }}</td>
                        </tr>

                        <!-- LABA KOTOR -->
                        <tr class="fw-bold table-success">
                            <td>LABA KOTOR</td>
                            @foreach($proyeks as $p)
                                <td class="text-end">{{ number_format($summaryProyek[$p->id_proyek]['laba_kotor'] ?? 0, 0, ',', '.') }}</td>
                            @endforeach
                            <td class="text-end">{{ number_format($summaryNonProyek['laba_kotor'], 0, ',', '.') }}</td>
                            <td class="text-end fw-bold">{{ number_format($summaryTotal['laba_kotor'], 0, ',', '.') }}</td>
                        </tr>

                        <!-- BEBAN -->
                        <tr class="fw-bold table-danger">
                            <td colspan="{{ $proyeks->count() + 3 }}">BEBAN OPERASIONAL</td>
                        </tr>
                        @foreach($beban as $row)
                            <tr>
                                <td class="ps-4">{{ $row['kode_akun'] }} - {{ $row['nama_akun'] }}</td>
                                @foreach($proyeks as $p)
                                    <td class="text-end">{{ number_format($row['proyek'][$p->id_proyek] ?? 0, 0, ',', '.') }}</td>
                                @endforeach
                                <td class="text-end">{{ number_format($row['non_proyek'], 0, ',', '.') }}</td>
                                <td class="text-end fw-bold">{{ number_format($row['total'], 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                        <tr class="fw-bold bg-light">
                            <td>Total Beban</td>
                            @foreach($proyeks as $p)
                                <td class="text-end">{{ number_format($summaryProyek[$p->id_proyek]['beban'] ?? 0, 0, ',', '.') }}</td>
                            @endforeach
                            <td class="text-end">{{ number_format($summaryNonProyek['beban'], 0, ',', '.') }}</td>
                            <td class="text-end fw-bold">{{ number_format($summaryTotal['beban'], 0, ',', '.') }}</td>
                        </tr>

                        <!-- LABA BERSIH -->
                        <tr class="fw-bold table-success fs-5">
                            <td>LABA BERSIH</td>
                            @foreach($proyeks as $p)
                                @php $lb = $summaryProyek[$p->id_proyek]['laba_bersih'] ?? 0; @endphp
                                <td class="text-end {{ $lb < 0 ? 'text-danger' : '' }}">{{ number_format($lb, 0, ',', '.') }}</td>
                            @endforeach
                            <td class="text-end {{ $summaryNonProyek['laba_bersih'] < 0 ? 'text-danger' : '' }}">
                                {{ number_format($summaryNonProyek['laba_bersih'], 0, ',', '.') }}
                            </td>
                            <td class="text-end fw-bold {{ $summaryTotal['laba_bersih'] < 0 ? 'text-danger' : '' }}">
                                {{ number_format($summaryTotal['laba_bersih'], 0, ',', '.') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
