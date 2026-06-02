@extends('laporan.pdf.layout')

@section('content')
<table style="width: 100%; border-collapse: collapse; font-size: 8px;">
    <thead>
        <tr style="background-color: #f5f5f5;">
            <th rowspan="2" style="border: 1px solid #ddd; padding: 4px; font-size: 8px; vertical-align: middle;">Keterangan</th>
            @foreach($proyeks as $p)
                <th class="text-center" style="border: 1px solid #ddd; padding: 4px; font-size: 8px;">{{ $p->kode_proyek }}</th>
            @endforeach
            <th class="text-center" style="border: 1px solid #ddd; padding: 4px; font-size: 8px;">Non-Proyek</th>
            <th class="text-center fw-bold" style="border: 1px solid #ddd; padding: 4px; font-size: 8px;">TOTAL</th>
        </tr>
        <tr style="background-color: #f5f5f5;">
            @foreach($proyeks as $p)
                <th class="text-center small" style="border: 1px solid #ddd; padding: 2px; font-size: 7px; font-weight: normal;">{{ \Illuminate\Support\Str::limit($p->nama_proyek, 10) }}</th>
            @endforeach
            <th class="text-center small" style="border: 1px solid #ddd; padding: 2px; font-size: 7px; font-weight: normal;">-</th>
            <th class="text-center small" style="border: 1px solid #ddd; padding: 2px; font-size: 7px; font-weight: normal;">-</th>
        </tr>
    </thead>
    <tbody>
        <!-- AKTIVITAS OPERASI -->
        <tr class="section-header" style="background-color: #343a40; color: white; font-weight: bold;">
            <td colspan="{{ $proyeks->count() + 3 }}" style="padding: 5px;">AKTIVITAS OPERASI</td>
        </tr>
        <tr>
            <td style="border: 1px solid #ddd; padding: 4px; font-size: 8px;">Penerimaan dari Pelanggan</td>
            @foreach($proyeks as $p)
                <td class="amount" style="border: 1px solid #ddd; padding: 4px; font-size: 8px;">{{ number_format($dataProyek[$p->id_proyek]['terima_pelanggan'] ?? 0, 0, ',', '.') }}</td>
            @endforeach
            <td class="amount" style="border: 1px solid #ddd; padding: 4px; font-size: 8px;">{{ number_format($dataNonProyek['terima_pelanggan'], 0, ',', '.') }}</td>
            <td class="amount text-bold" style="border: 1px solid #ddd; padding: 4px; font-size: 8px; font-weight: bold;">{{ number_format($dataTotal['terima_pelanggan'], 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td style="border: 1px solid #ddd; padding: 4px; font-size: 8px;">Pembayaran ke Pemasok</td>
            @foreach($proyeks as $p)
                <td class="amount text-danger" style="border: 1px solid #ddd; padding: 4px; font-size: 8px;">({{ number_format($dataProyek[$p->id_proyek]['bayar_pemasok'] ?? 0, 0, ',', '.') }})</td>
            @endforeach
            <td class="amount text-danger" style="border: 1px solid #ddd; padding: 4px; font-size: 8px;">({{ number_format($dataNonProyek['bayar_pemasok'], 0, ',', '.') }})</td>
            <td class="amount text-bold text-danger" style="border: 1px solid #ddd; padding: 4px; font-size: 8px; font-weight: bold;">({{ number_format($dataTotal['bayar_pemasok'], 0, ',', '.') }})</td>
        </tr>
        <tr class="total-row" style="background-color: #f8f9fa; font-weight: bold;">
            <td style="border: 1px solid #ddd; padding: 4px; font-size: 8px;">Arus Kas Operasi</td>
            @foreach($proyeks as $p)
                @php $ao = $dataProyek[$p->id_proyek]['arus_operasi'] ?? 0; @endphp
                <td class="amount {{ $ao < 0 ? 'negative' : 'positive' }}" style="border: 1px solid #ddd; padding: 4px; font-size: 8px;">{{ number_format($ao, 0, ',', '.') }}</td>
            @endforeach
            <td class="amount {{ $dataNonProyek['arus_operasi'] < 0 ? 'negative' : 'positive' }}" style="border: 1px solid #ddd; padding: 4px; font-size: 8px;">{{ number_format($dataNonProyek['arus_operasi'], 0, ',', '.') }}</td>
            <td class="amount" style="border: 1px solid #ddd; padding: 4px; font-size: 8px;">{{ number_format($dataTotal['arus_operasi'], 0, ',', '.') }}</td>
        </tr>

        <!-- AKTIVITAS INVESTASI -->
        <tr class="section-header" style="background-color: #6c757d; color: white; font-weight: bold;">
            <td colspan="{{ $proyeks->count() + 3 }}" style="padding: 5px;">AKTIVITAS INVESTASI</td>
        </tr>
        <tr class="total-row" style="background-color: #f8f9fa; font-weight: bold;">
            <td style="border: 1px solid #ddd; padding: 4px; font-size: 8px;">Arus Kas Investasi</td>
            @foreach($proyeks as $p)
                @php $ai = $dataProyek[$p->id_proyek]['arus_investasi'] ?? 0; @endphp
                <td class="amount {{ $ai < 0 ? 'negative' : 'positive' }}" style="border: 1px solid #ddd; padding: 4px; font-size: 8px;">{{ number_format($ai, 0, ',', '.') }}</td>
            @endforeach
            <td class="amount {{ $dataNonProyek['arus_investasi'] < 0 ? 'negative' : 'positive' }}" style="border: 1px solid #ddd; padding: 4px; font-size: 8px;">{{ number_format($dataNonProyek['arus_investasi'], 0, ',', '.') }}</td>
            <td class="amount" style="border: 1px solid #ddd; padding: 4px; font-size: 8px;">{{ number_format($dataTotal['arus_investasi'], 0, ',', '.') }}</td>
        </tr>

        <!-- AKTIVITAS PENDANAAN -->
        <tr class="section-header" style="background-color: #28a745; color: white; font-weight: bold;">
            <td colspan="{{ $proyeks->count() + 3 }}" style="padding: 5px;">AKTIVITAS PENDANAAN</td>
        </tr>
        <tr class="total-row" style="background-color: #f8f9fa; font-weight: bold;">
            <td style="border: 1px solid #ddd; padding: 4px; font-size: 8px;">Arus Kas Pendanaan</td>
            @foreach($proyeks as $p)
                @php $ap = $dataProyek[$p->id_proyek]['arus_pendanaan'] ?? 0; @endphp
                <td class="amount {{ $ap < 0 ? 'negative' : 'positive' }}" style="border: 1px solid #ddd; padding: 4px; font-size: 8px;">{{ number_format($ap, 0, ',', '.') }}</td>
            @endforeach
            <td class="amount {{ $dataNonProyek['arus_pendanaan'] < 0 ? 'negative' : 'positive' }}" style="border: 1px solid #ddd; padding: 4px; font-size: 8px;">{{ number_format($dataNonProyek['arus_pendanaan'], 0, ',', '.') }}</td>
            <td class="amount" style="border: 1px solid #ddd; padding: 4px; font-size: 8px;">{{ number_format($dataTotal['arus_pendanaan'], 0, ',', '.') }}</td>
        </tr>

        <!-- KENAIKAN KAS -->
        <tr class="grand-total text-bold" style="background-color: #e9ecef; font-weight: bold; font-size: 9px;">
            <td style="border: 1px solid #ddd; padding: 5px; font-size: 9px;">KENAIKAN (PENURUNAN) KAS</td>
            @foreach($proyeks as $p)
                @php $kk = $dataProyek[$p->id_proyek]['kenaikan_kas'] ?? 0; @endphp
                <td class="amount {{ $kk < 0 ? 'negative' : 'positive' }}" style="border: 1px solid #ddd; padding: 5px; font-size: 9px;">{{ number_format($kk, 0, ',', '.') }}</td>
            @endforeach
            <td class="amount {{ $dataNonProyek['kenaikan_kas'] < 0 ? 'negative' : 'positive' }}" style="border: 1px solid #ddd; padding: 5px; font-size: 9px;">
                {{ number_format($dataNonProyek['kenaikan_kas'], 0, ',', '.') }}
            </td>
            <td class="amount text-bold {{ $dataTotal['kenaikan_kas'] < 0 ? 'negative' : 'positive' }}" style="border: 1px solid #ddd; padding: 5px; font-size: 9px; font-weight: bold;">
                {{ number_format($dataTotal['kenaikan_kas'], 0, ',', '.') }}
            </td>
        </tr>
    </tbody>
</table>
@endsection
