@extends('laporan.pdf.layout')

@section('content')
<table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
    <tbody>
        <!-- OPERASI -->
        <tr class="section-header" style="background-color: #343a40; color: white; font-weight: bold;">
            <td colspan="2" style="padding: 6px;">ARUS KAS DARI AKTIVITAS OPERASI</td>
        </tr>
        <tr>
            <td class="indent-1" style="padding: 6px; border: 1px solid #ddd;">Penerimaan dari Pelanggan</td>
            <td class="amount" style="padding: 6px; border: 1px solid #ddd; text-align: right;">
                Rp {{ number_format($terimaPelanggan, 0, ',', '.') }}
            </td>
        </tr>
        <tr>
            <td class="indent-1" style="padding: 6px; border: 1px solid #ddd;">Pembayaran kepada Pemasok & Beban</td>
            <td class="amount negative" style="padding: 6px; border: 1px solid #ddd; text-align: right; color: red;">
                (Rp {{ number_format($bayarPemasok, 0, ',', '.') }})
            </td>
        </tr>
        <tr class="total-row" style="background-color: #f8f9fa; font-weight: bold;">
            <td style="padding: 6px; border: 1px solid #ddd;">Arus Kas Bersih dari Aktivitas Operasi</td>
            <td class="amount {{ $arusKasOperasi >= 0 ? 'positive' : 'negative' }}" style="padding: 6px; border: 1px solid #ddd; text-align: right;">
                Rp {{ number_format($arusKasOperasi, 0, ',', '.') }}
            </td>
        </tr>

        <!-- Empty spacing row -->
        <tr><td colspan="2" style="height: 15px; border: none;"></td></tr>

        <!-- INVESTASI -->
        <tr class="section-header" style="background-color: #6c757d; color: white; font-weight: bold;">
            <td colspan="2" style="padding: 6px;">ARUS KAS DARI AKTIVITAS INVESTASI</td>
        </tr>
        <tr>
            <td class="indent-1" style="padding: 6px; border: 1px solid #ddd;">Penjualan Aset Tetap</td>
            <td class="amount" style="padding: 6px; border: 1px solid #ddd; text-align: right;">
                Rp {{ number_format($jualAset, 0, ',', '.') }}
            </td>
        </tr>
        <tr>
            <td class="indent-1" style="padding: 6px; border: 1px solid #ddd;">Pembelian Aset Tetap</td>
            <td class="amount negative" style="padding: 6px; border: 1px solid #ddd; text-align: right; color: red;">
                (Rp {{ number_format($beliAset, 0, ',', '.') }})
            </td>
        </tr>
        <tr class="total-row" style="background-color: #f8f9fa; font-weight: bold;">
            <td style="padding: 6px; border: 1px solid #ddd;">Arus Kas Bersih dari Aktivitas Investasi</td>
            <td class="amount {{ $arusKasInvestasi >= 0 ? 'positive' : 'negative' }}" style="padding: 6px; border: 1px solid #ddd; text-align: right;">
                Rp {{ number_format($arusKasInvestasi, 0, ',', '.') }}
            </td>
        </tr>

        <!-- Empty spacing row -->
        <tr><td colspan="2" style="height: 15px; border: none;"></td></tr>

        <!-- PENDANAAN -->
        <tr class="section-header" style="background-color: #28a745; color: white; font-weight: bold;">
            <td colspan="2" style="padding: 6px;">ARUS KAS DARI AKTIVITAS PENDANAAN</td>
        </tr>
        <tr>
            <td class="indent-1" style="padding: 6px; border: 1px solid #ddd;">Penerimaan Modal / Utang Jangka Panjang</td>
            <td class="amount" style="padding: 6px; border: 1px solid #ddd; text-align: right;">
                Rp {{ number_format($terimaPendanaan, 0, ',', '.') }}
            </td>
        </tr>
        <tr>
            <td class="indent-1" style="padding: 6px; border: 1px solid #ddd;">Pembayaran Prive / Utang Jangka Panjang</td>
            <td class="amount negative" style="padding: 6px; border: 1px solid #ddd; text-align: right; color: red;">
                (Rp {{ number_format($bayarPendanaan, 0, ',', '.') }})
            </td>
        </tr>
        <tr class="total-row" style="background-color: #f8f9fa; font-weight: bold;">
            <td style="padding: 6px; border: 1px solid #ddd;">Arus Kas Bersih dari Aktivitas Pendanaan</td>
            <td class="amount {{ $arusKasPendanaan >= 0 ? 'positive' : 'negative' }}" style="padding: 6px; border: 1px solid #ddd; text-align: right;">
                Rp {{ number_format($arusKasPendanaan, 0, ',', '.') }}
            </td>
        </tr>

        <!-- Empty spacing row -->
        <tr><td colspan="2" style="height: 15px; border: none;"></td></tr>

        <!-- SUMMARY -->
        <tr class="total-row" style="background-color: #f8f9fa; font-weight: bold;">
            <td style="padding: 6px; border: 1px solid #ddd;">Kenaikan (Penurunan) Bersih Kas</td>
            <td class="amount" style="padding: 6px; border: 1px solid #ddd; text-align: right;">
                Rp {{ number_format($kenaikanKas, 0, ',', '.') }}
            </td>
        </tr>
        <tr>
            <td style="padding: 6px; border: 1px solid #ddd; font-weight: bold;">Saldo Kas Awal Periode</td>
            <td class="amount" style="padding: 6px; border: 1px solid #ddd; text-align: right; font-weight: bold;">
                Rp {{ number_format($saldoAwal, 0, ',', '.') }}
            </td>
        </tr>
        <tr class="grand-total" style="background-color: #e9ecef; font-weight: bold;">
            <td style="padding: 6px; border: 1px solid #ddd; font-size: 11px;">Saldo Kas Akhir Periode</td>
            <td class="amount" style="padding: 6px; border: 1px solid #ddd; text-align: right; font-size: 11px;">
                Rp {{ number_format($saldoAkhir, 0, ',', '.') }}
            </td>
        </tr>
    </tbody>
</table>
@endsection
