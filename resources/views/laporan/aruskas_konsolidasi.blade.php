@extends('layouts.app')

@section('title', 'Arus Kas Konsolidasi - Simple Akunting')

@section('content')
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Laporan Arus Kas Konsolidasi</h1>
    </div>

    <!-- Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('laporan.aruskas_konsolidasi') }}" method="GET">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="start_date" class="form-label">Dari Tanggal</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" value="{{ $startDate }}">
                    </div>
                    <div class="col-md-4">
                        <label for="end_date" class="form-label">Sampai Tanggal</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" value="{{ $endDate }}">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary">Tampilkan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Report Header -->
    <div class="text-center mb-4">
        <h3>{{ $perusahaan->nama_perusahaan ?? 'Nama Perusahaan Belum Diset' }}</h3>
        <h4>Laporan Arus Kas Konsolidasi</h4>
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
                                <th class="text-center small">{{ \Illuminate\Support\Str::limit($p->nama_proyek, 12) }}</th>
                            @endforeach
                            <th class="text-center small">-</th>
                            <th class="text-center small">-</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- AKTIVITAS OPERASI -->
                        <tr class="fw-bold table-primary">
                            <td colspan="{{ $proyeks->count() + 3 }}">AKTIVITAS OPERASI</td>
                        </tr>
                        <tr>
                            <td class="ps-4">Penerimaan dari Pelanggan</td>
                            @foreach($proyeks as $p)
                                <td class="text-end">{{ number_format($dataProyek[$p->id_proyek]['terima_pelanggan'] ?? 0, 0, ',', '.') }}</td>
                            @endforeach
                            <td class="text-end">{{ number_format($dataNonProyek['terima_pelanggan'], 0, ',', '.') }}</td>
                            <td class="text-end fw-bold">{{ number_format($dataTotal['terima_pelanggan'], 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td class="ps-4">Pembayaran ke Pemasok</td>
                            @foreach($proyeks as $p)
                                <td class="text-end text-danger">({{ number_format($dataProyek[$p->id_proyek]['bayar_pemasok'] ?? 0, 0, ',', '.') }})</td>
                            @endforeach
                            <td class="text-end text-danger">({{ number_format($dataNonProyek['bayar_pemasok'], 0, ',', '.') }})</td>
                            <td class="text-end fw-bold text-danger">({{ number_format($dataTotal['bayar_pemasok'], 0, ',', '.') }})</td>
                        </tr>
                        <tr class="fw-bold bg-light">
                            <td>Arus Kas Operasi</td>
                            @foreach($proyeks as $p)
                                @php $ao = $dataProyek[$p->id_proyek]['arus_operasi'] ?? 0; @endphp
                                <td class="text-end {{ $ao < 0 ? 'text-danger' : '' }}">{{ number_format($ao, 0, ',', '.') }}</td>
                            @endforeach
                            <td class="text-end {{ $dataNonProyek['arus_operasi'] < 0 ? 'text-danger' : '' }}">{{ number_format($dataNonProyek['arus_operasi'], 0, ',', '.') }}</td>
                            <td class="text-end fw-bold {{ $dataTotal['arus_operasi'] < 0 ? 'text-danger' : '' }}">{{ number_format($dataTotal['arus_operasi'], 0, ',', '.') }}</td>
                        </tr>

                        <!-- AKTIVITAS INVESTASI -->
                        <tr class="fw-bold table-warning">
                            <td colspan="{{ $proyeks->count() + 3 }}">AKTIVITAS INVESTASI</td>
                        </tr>
                        <tr class="fw-bold bg-light">
                            <td>Arus Kas Investasi</td>
                            @foreach($proyeks as $p)
                                @php $ai = $dataProyek[$p->id_proyek]['arus_investasi'] ?? 0; @endphp
                                <td class="text-end {{ $ai < 0 ? 'text-danger' : '' }}">{{ number_format($ai, 0, ',', '.') }}</td>
                            @endforeach
                            <td class="text-end {{ $dataNonProyek['arus_investasi'] < 0 ? 'text-danger' : '' }}">{{ number_format($dataNonProyek['arus_investasi'], 0, ',', '.') }}</td>
                            <td class="text-end fw-bold {{ $dataTotal['arus_investasi'] < 0 ? 'text-danger' : '' }}">{{ number_format($dataTotal['arus_investasi'], 0, ',', '.') }}</td>
                        </tr>

                        <!-- AKTIVITAS PENDANAAN -->
                        <tr class="fw-bold table-info">
                            <td colspan="{{ $proyeks->count() + 3 }}">AKTIVITAS PENDANAAN</td>
                        </tr>
                        <tr class="fw-bold bg-light">
                            <td>Arus Kas Pendanaan</td>
                            @foreach($proyeks as $p)
                                @php $ap = $dataProyek[$p->id_proyek]['arus_pendanaan'] ?? 0; @endphp
                                <td class="text-end {{ $ap < 0 ? 'text-danger' : '' }}">{{ number_format($ap, 0, ',', '.') }}</td>
                            @endforeach
                            <td class="text-end {{ $dataNonProyek['arus_pendanaan'] < 0 ? 'text-danger' : '' }}">{{ number_format($dataNonProyek['arus_pendanaan'], 0, ',', '.') }}</td>
                            <td class="text-end fw-bold {{ $dataTotal['arus_pendanaan'] < 0 ? 'text-danger' : '' }}">{{ number_format($dataTotal['arus_pendanaan'], 0, ',', '.') }}</td>
                        </tr>

                        <!-- KENAIKAN KAS -->
                        <tr class="fw-bold table-success fs-6">
                            <td>KENAIKAN (PENURUNAN) KAS</td>
                            @foreach($proyeks as $p)
                                @php $kk = $dataProyek[$p->id_proyek]['kenaikan_kas'] ?? 0; @endphp
                                <td class="text-end {{ $kk < 0 ? 'text-danger' : '' }}">{{ number_format($kk, 0, ',', '.') }}</td>
                            @endforeach
                            <td class="text-end {{ $dataNonProyek['kenaikan_kas'] < 0 ? 'text-danger' : '' }}">{{ number_format($dataNonProyek['kenaikan_kas'], 0, ',', '.') }}</td>
                            <td class="text-end fw-bold {{ $dataTotal['kenaikan_kas'] < 0 ? 'text-danger' : '' }}">{{ number_format($dataTotal['kenaikan_kas'], 0, ',', '.') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
