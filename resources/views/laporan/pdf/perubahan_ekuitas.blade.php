@extends('laporan.pdf.layout')

@section('content')
<table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
    <tbody>
        <tr class="total-row" style="background-color: #f8f9fa; font-weight: bold; font-size: 11px;">
            <td style="padding: 8px; border: 1px solid #ddd;">Saldo Ekuitas Awal</td>
            <td class="amount" style="padding: 8px; border: 1px solid #ddd; text-align: right;">
                Rp {{ number_format($saldoAwal, 0, ',', '.') }}
            </td>
        </tr>

        <!-- Penambahan -->
        <tr style="background-color: #f5f5f5; font-weight: bold;">
            <td colspan="2" style="padding: 6px; border: 1px solid #ddd;">Penambahan:</td>
        </tr>
        <tr>
            <td class="indent-1" style="padding: 6px; border: 1px solid #ddd;">Laba Bersih Periode Berjalan</td>
            <td class="amount positive" style="padding: 6px; border: 1px solid #ddd; text-align: right; color: green;">
                Rp {{ number_format($labaBersih, 0, ',', '.') }}
            </td>
        </tr>
        <tr>
            <td class="indent-1" style="padding: 6px; border: 1px solid #ddd;">Setoran Modal (Investasi Pemilik)</td>
            <td class="amount positive" style="padding: 6px; border: 1px solid #ddd; text-align: right; color: green;">
                Rp {{ number_format($setoranModal, 0, ',', '.') }}
            </td>
        </tr>

        <!-- Pengurangan -->
        <tr style="background-color: #f5f5f5; font-weight: bold;">
            <td colspan="2" style="padding: 6px; border: 1px solid #ddd;">Pengurangan:</td>
        </tr>
        <tr>
            <td class="indent-1" style="padding: 6px; border: 1px solid #ddd;">Prive (Penarikan Modal Pemilik)</td>
            <td class="amount negative" style="padding: 6px; border: 1px solid #ddd; text-align: right; color: red;">
                (Rp {{ number_format($prive, 0, ',', '.') }})
            </td>
        </tr>

        <!-- Akhir -->
        <tr class="grand-total" style="background-color: #e9ecef; font-weight: bold; font-size: 12px;">
            <td style="padding: 8px; border: 1px solid #ddd; font-size: 12px;">Saldo Ekuitas Akhir</td>
            <td class="amount" style="padding: 8px; border: 1px solid #ddd; text-align: right; font-size: 12px;">
                Rp {{ number_format($saldoAkhir, 0, ',', '.') }}
            </td>
        </tr>
    </tbody>
</table>
@endsection
