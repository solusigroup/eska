@extends('laporan.pdf.layout')

@section('content')
<table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
    <thead>
        <tr style="background-color: #f5f5f5;">
            <th style="width: 12%; border: 1px solid #ddd; padding: 6px;">Tanggal</th>
            <th style="width: 18%; border: 1px solid #ddd; padding: 6px;">No. Transaksi</th>
            <th style="width: 30%; border: 1px solid #ddd; padding: 6px;">Keterangan</th>
            <th style="width: 13%; border: 1px solid #ddd; padding: 6px; text-align: right;">Debit</th>
            <th style="width: 13%; border: 1px solid #ddd; padding: 6px; text-align: right;">Kredit</th>
            <th style="width: 14%; border: 1px solid #ddd; padding: 6px; text-align: right;">Saldo</th>
        </tr>
    </thead>
    <tbody>
        <!-- Saldo Awal Row -->
        <tr>
            <td style="border: 1px solid #ddd; padding: 6px;">{{ date('d-m-Y', strtotime($startDate)) }}</td>
            <td style="border: 1px solid #ddd; padding: 6px; font-family: monospace;">-</td>
            <td style="border: 1px solid #ddd; padding: 6px; font-weight: bold;">SALDO AWAL</td>
            <td style="border: 1px solid #ddd; padding: 6px; text-align: right;">-</td>
            <td style="border: 1px solid #ddd; padding: 6px; text-align: right;">-</td>
            <td class="amount" style="border: 1px solid #ddd; padding: 6px; text-align: right; font-weight: bold;">
                Rp {{ number_format($saldoAwal, 2, ',', '.') }}
            </td>
        </tr>

        @php $saldo = $saldoAwal; @endphp
        @foreach($transaksi as $t)
            @php
                if ($selectedAkun->saldo_normal == 'Debit') {
                    $saldo += $t->debit - $t->kredit;
                } else {
                    $saldo += $t->kredit - $t->debit;
                }
            @endphp
            <tr>
                <td style="border: 1px solid #ddd; padding: 6px;">{{ \Carbon\Carbon::parse($t->jurnal->tanggal)->format('d-m-Y') }}</td>
                <td style="border: 1px solid #ddd; padding: 6px; font-family: monospace;">{{ $t->jurnal->no_transaksi }}</td>
                <td style="border: 1px solid #ddd; padding: 6px;">{{ $t->jurnal->deskripsi }}</td>
                <td class="amount" style="border: 1px solid #ddd; padding: 6px; text-align: right;">
                    {{ $t->debit > 0 ? 'Rp ' . number_format($t->debit, 2, ',', '.') : '-' }}
                </td>
                <td class="amount" style="border: 1px solid #ddd; padding: 6px; text-align: right;">
                    {{ $t->kredit > 0 ? 'Rp ' . number_format($t->kredit, 2, ',', '.') : '-' }}
                </td>
                <td class="amount" style="border: 1px solid #ddd; padding: 6px; text-align: right; font-weight: bold;">
                    Rp {{ number_format($saldo, 2, ',', '.') }}
                </td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr class="grand-total">
            <td colspan="5" style="font-weight: bold; padding: 6px; border: 1px solid #ddd;">SALDO AKHIR PERIODE</td>
            <td class="amount" style="font-weight: bold; text-align: right; padding: 6px; border: 1px solid #ddd;">
                Rp {{ number_format($saldo, 2, ',', '.') }}
            </td>
        </tr>
    </tfoot>
</table>
@endsection
