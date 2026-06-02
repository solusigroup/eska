@extends('laporan.pdf.layout')

@section('content')
@php
    $summaryProyek = [];
    foreach ($proyeks as $p) {
        $pPendapatan = $pendapatan->sum(fn($row) => $row['proyek'][$p->id_proyek] ?? 0);
        $pHpp = $hpp->sum(fn($row) => $row['proyek'][$p->id_proyek] ?? 0);
        $pBeban = $beban->sum(fn($row) => $row['proyek'][$p->id_proyek] ?? 0);
        $summaryProyek[$p->id_proyek] = [
            'pendapatan' => $pPendapatan,
            'hpp' => $pHpp,
            'laba_kotor' => $pPendapatan - $pHpp,
            'beban' => $pBeban,
            'laba_bersih' => ($pPendapatan - $pHpp) - $pBeban,
        ];
    }
    
    $npPendapatan = $pendapatan->sum('non_proyek');
    $npHpp = $hpp->sum('non_proyek');
    $npBeban = $beban->sum('non_proyek');
    $summaryNonProyek = [
        'pendapatan' => $npPendapatan,
        'hpp' => $npHpp,
        'laba_kotor' => $npPendapatan - $npHpp,
        'beban' => $npBeban,
        'laba_bersih' => ($npPendapatan - $npHpp) - $npBeban,
    ];

    $totalPendapatan = $pendapatan->sum('total');
    $totalHpp = $hpp->sum('total');
    $totalBeban = $beban->sum('total');
    $summaryTotal = [
        'pendapatan' => $totalPendapatan,
        'hpp' => $totalHpp,
        'laba_kotor' => $totalPendapatan - $totalHpp,
        'beban' => $totalBeban,
        'laba_bersih' => ($totalPendapatan - $totalHpp) - $totalBeban,
    ];
@endphp

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
        <!-- PENDAPATAN -->
        <tr class="section-header" style="background-color: #343a40; color: white; font-weight: bold;">
            <td colspan="{{ $proyeks->count() + 3 }}" style="padding: 5px;">PENDAPATAN</td>
        </tr>
        @foreach($pendapatan as $row)
            <tr>
                <td style="border: 1px solid #ddd; padding: 4px; font-size: 8px;">{{ $row['kode_akun'] }} - {{ $row['nama_akun'] }}</td>
                @foreach($proyeks as $p)
                    <td class="amount" style="border: 1px solid #ddd; padding: 4px; font-size: 8px;">{{ number_format($row['proyek'][$p->id_proyek] ?? 0, 0, ',', '.') }}</td>
                @endforeach
                <td class="amount" style="border: 1px solid #ddd; padding: 4px; font-size: 8px;">{{ number_format($row['non_proyek'], 0, ',', '.') }}</td>
                <td class="amount text-bold" style="border: 1px solid #ddd; padding: 4px; font-size: 8px; font-weight: bold;">{{ number_format($row['total'], 0, ',', '.') }}</td>
            </tr>
        @endforeach
        <tr class="total-row" style="background-color: #f8f9fa; font-weight: bold;">
            <td style="border: 1px solid #ddd; padding: 4px; font-size: 8px;">Total Pendapatan</td>
            @foreach($proyeks as $p)
                <td class="amount" style="border: 1px solid #ddd; padding: 4px; font-size: 8px;">{{ number_format($summaryProyek[$p->id_proyek]['pendapatan'] ?? 0, 0, ',', '.') }}</td>
            @endforeach
            <td class="amount" style="border: 1px solid #ddd; padding: 4px; font-size: 8px;">{{ number_format($summaryNonProyek['pendapatan'], 0, ',', '.') }}</td>
            <td class="amount" style="border: 1px solid #ddd; padding: 4px; font-size: 8px;">{{ number_format($summaryTotal['pendapatan'], 0, ',', '.') }}</td>
        </tr>

        <!-- HPP -->
        <tr class="section-header" style="background-color: #6c757d; color: white; font-weight: bold;">
            <td colspan="{{ $proyeks->count() + 3 }}" style="padding: 5px;">HARGA POKOK PENJUALAN</td>
        </tr>
        @foreach($hpp as $row)
            <tr>
                <td style="border: 1px solid #ddd; padding: 4px; font-size: 8px;">{{ $row['kode_akun'] }} - {{ $row['nama_akun'] }}</td>
                @foreach($proyeks as $p)
                    <td class="amount" style="border: 1px solid #ddd; padding: 4px; font-size: 8px;">{{ number_format($row['proyek'][$p->id_proyek] ?? 0, 0, ',', '.') }}</td>
                @endforeach
                <td class="amount" style="border: 1px solid #ddd; padding: 4px; font-size: 8px;">{{ number_format($row['non_proyek'], 0, ',', '.') }}</td>
                <td class="amount text-bold" style="border: 1px solid #ddd; padding: 4px; font-size: 8px; font-weight: bold;">{{ number_format($row['total'], 0, ',', '.') }}</td>
            </tr>
        @endforeach
        <tr class="total-row" style="background-color: #f8f9fa; font-weight: bold;">
            <td style="border: 1px solid #ddd; padding: 4px; font-size: 8px;">Total HPP</td>
            @foreach($proyeks as $p)
                <td class="amount" style="border: 1px solid #ddd; padding: 4px; font-size: 8px;">{{ number_format($summaryProyek[$p->id_proyek]['hpp'] ?? 0, 0, ',', '.') }}</td>
            @endforeach
            <td class="amount" style="border: 1px solid #ddd; padding: 4px; font-size: 8px;">{{ number_format($summaryNonProyek['hpp'], 0, ',', '.') }}</td>
            <td class="amount" style="border: 1px solid #ddd; padding: 4px; font-size: 8px;">{{ number_format($summaryTotal['hpp'], 0, ',', '.') }}</td>
        </tr>

        <!-- LABA KOTOR -->
        <tr class="grand-total" style="background-color: #e9ecef; font-weight: bold;">
            <td style="border: 1px solid #ddd; padding: 4px; font-size: 8px;">LABA KOTOR</td>
            @foreach($proyeks as $p)
                <td class="amount" style="border: 1px solid #ddd; padding: 4px; font-size: 8px;">{{ number_format($summaryProyek[$p->id_proyek]['laba_kotor'] ?? 0, 0, ',', '.') }}</td>
            @endforeach
            <td class="amount" style="border: 1px solid #ddd; padding: 4px; font-size: 8px;">{{ number_format($summaryNonProyek['laba_kotor'], 0, ',', '.') }}</td>
            <td class="amount" style="border: 1px solid #ddd; padding: 4px; font-size: 8px;">{{ number_format($summaryTotal['laba_kotor'], 0, ',', '.') }}</td>
        </tr>

        <!-- BEBAN -->
        <tr class="section-header" style="background-color: #dc3545; color: white; font-weight: bold;">
            <td colspan="{{ $proyeks->count() + 3 }}" style="padding: 5px;">BEBAN OPERASIONAL</td>
        </tr>
        @foreach($beban as $row)
            <tr>
                <td style="border: 1px solid #ddd; padding: 4px; font-size: 8px;">{{ $row['kode_akun'] }} - {{ $row['nama_akun'] }}</td>
                @foreach($proyeks as $p)
                    <td class="amount" style="border: 1px solid #ddd; padding: 4px; font-size: 8px;">{{ number_format($row['proyek'][$p->id_proyek] ?? 0, 0, ',', '.') }}</td>
                @endforeach
                <td class="amount" style="border: 1px solid #ddd; padding: 4px; font-size: 8px;">{{ number_format($row['non_proyek'], 0, ',', '.') }}</td>
                <td class="amount text-bold" style="border: 1px solid #ddd; padding: 4px; font-size: 8px; font-weight: bold;">{{ number_format($row['total'], 0, ',', '.') }}</td>
            </tr>
        @endforeach
        <tr class="total-row" style="background-color: #f8f9fa; font-weight: bold;">
            <td style="border: 1px solid #ddd; padding: 4px; font-size: 8px;">Total Beban</td>
            @foreach($proyeks as $p)
                <td class="amount" style="border: 1px solid #ddd; padding: 4px; font-size: 8px;">{{ number_format($summaryProyek[$p->id_proyek]['beban'] ?? 0, 0, ',', '.') }}</td>
            @endforeach
            <td class="amount" style="border: 1px solid #ddd; padding: 4px; font-size: 8px;">{{ number_format($summaryNonProyek['beban'], 0, ',', '.') }}</td>
            <td class="amount" style="border: 1px solid #ddd; padding: 4px; font-size: 8px;">{{ number_format($summaryTotal['beban'], 0, ',', '.') }}</td>
        </tr>

        <!-- LABA BERSIH -->
        <tr class="grand-total text-bold" style="background-color: #d4edda; font-weight: bold; font-size: 9px;">
            <td style="border: 1px solid #ddd; padding: 5px; font-size: 9px;">LABA BERSIH</td>
            @foreach($proyeks as $p)
                @php $lb = $summaryProyek[$p->id_proyek]['laba_bersih'] ?? 0; @endphp
                <td class="amount {{ $lb < 0 ? 'negative' : 'positive' }}" style="border: 1px solid #ddd; padding: 5px; font-size: 9px;">{{ number_format($lb, 0, ',', '.') }}</td>
            @endforeach
            <td class="amount {{ $summaryNonProyek['laba_bersih'] < 0 ? 'negative' : 'positive' }}" style="border: 1px solid #ddd; padding: 5px; font-size: 9px;">
                {{ number_format($summaryNonProyek['laba_bersih'], 0, ',', '.') }}
            </td>
            <td class="amount text-bold {{ $summaryTotal['laba_bersih'] < 0 ? 'negative' : 'positive' }}" style="border: 1px solid #ddd; padding: 5px; font-size: 9px; font-weight: bold;">
                {{ number_format($summaryTotal['laba_bersih'], 0, ',', '.') }}
            </td>
        </tr>
    </tbody>
</table>
@endsection
