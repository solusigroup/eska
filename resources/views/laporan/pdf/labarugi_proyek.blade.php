@extends('laporan.pdf.layout')

@section('content')
<div style="margin-bottom: 15px; font-weight: bold; font-size: 11px;">
    Proyek: {{ $proyek->kode_proyek }} - {{ $proyek->nama_proyek }}
</div>

<table>
    <!-- PENDAPATAN Section -->
    <tr class="section-header">
        <td colspan="2">PENDAPATAN</td>
    </tr>
    @foreach($pendapatan as $item)
    <tr>
        <td class="indent-1">{{ $item['kode'] }} - {{ $item['nama'] }}</td>
        <td class="amount">Rp {{ number_format($item['saldo'], 0, ',', '.') }}</td>
    </tr>
    @endforeach
    <tr class="total-row">
        <td>Total Pendapatan</td>
        <td class="amount">Rp {{ number_format($pendapatan->sum('saldo'), 0, ',', '.') }}</td>
    </tr>

    <!-- HPP Section -->
    <tr class="section-header">
        <td colspan="2">HARGA POKOK PENJUALAN</td>
    </tr>
    @foreach($hpp as $item)
    <tr>
        <td class="indent-1">{{ $item['kode'] }} - {{ $item['nama'] }}</td>
        <td class="amount">Rp {{ number_format($item['saldo'], 0, ',', '.') }}</td>
    </tr>
    @endforeach
    <tr class="total-row">
        <td>Total HPP</td>
        <td class="amount">Rp {{ number_format($hpp->sum('saldo'), 0, ',', '.') }}</td>
    </tr>

    @php 
        $labaKotor = $pendapatan->sum('saldo') - $hpp->sum('saldo');
    @endphp
    <tr class="grand-total">
        <td>LABA KOTOR</td>
        <td class="amount {{ $labaKotor >= 0 ? 'positive' : 'negative' }}">
            Rp {{ number_format($labaKotor, 0, ',', '.') }}
        </td>
    </tr>

    <!-- Empty row -->
    <tr><td colspan="2" style="height: 15px; border: none;"></td></tr>

    <!-- BEBAN OPERASIONAL Section -->
    <tr class="section-header">
        <td colspan="2">BEBAN OPERASIONAL</td>
    </tr>
    @foreach($beban as $item)
    <tr>
        <td class="indent-1">{{ $item['kode'] }} - {{ $item['nama'] }}</td>
        <td class="amount">Rp {{ number_format($item['saldo'], 0, ',', '.') }}</td>
    </tr>
    @endforeach
    <tr class="total-row">
        <td>Total Beban Operasional</td>
        <td class="amount">Rp {{ number_format($beban->sum('saldo'), 0, ',', '.') }}</td>
    </tr>

    @php
        $labaBersih = $labaKotor - $beban->sum('saldo');
    @endphp
    <tr class="grand-total" style="font-size: 12px;">
        <td>LABA BERSIH PROYEK</td>
        <td class="amount {{ $labaBersih >= 0 ? 'positive' : 'negative' }}" style="font-size: 12px;">
            Rp {{ number_format($labaBersih, 0, ',', '.') }}
        </td>
    </tr>
</table>
@endsection
