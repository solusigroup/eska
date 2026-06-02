@extends('laporan.pdf.layout')

@section('content')
<table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
    <tbody>
        <!-- OPERASI -->
        <tr class="section-header" style="background-color: #343a40; color: white; font-weight: bold;">
            <td colspan="2" style="padding: 6px;">ARUS KAS DARI AKTIVITAS OPERASI</td>
        </tr>
        <tr style="font-weight: bold;">
            <td class="indent-1" style="padding: 6px; border: 1px solid #ddd;">Laba Bersih</td>
            <td class="amount" style="padding: 6px; border: 1px solid #ddd; text-align: right;">
                Rp {{ number_format($labaBersih, 0, ',', '.') }}
            </td>
        </tr>
        <tr style="font-weight: bold; font-style: italic;">
            <td colspan="2" class="indent-1" style="padding: 6px; border: 1px solid #ddd;">Penyesuaian untuk merekonsiliasi laba bersih menjadi kas bersih:</td>
        </tr>
        <tr>
            <td class="indent-2" style="padding: 6px; border: 1px solid #ddd; padding-left: 30px;">Beban Penyusutan Aset Tetap</td>
            <td class="amount" style="padding: 6px; border: 1px solid #ddd; text-align: right;">
                Rp {{ number_format($bebanPenyusutan, 0, ',', '.') }}
            </td>
        </tr>
        <tr>
            <td class="indent-2" style="padding: 6px; border: 1px solid #ddd; padding-left: 30px;">(Kenaikan) Penurunan Piutang</td>
            <td class="amount" style="padding: 6px; border: 1px solid #ddd; text-align: right;">
                Rp {{ number_format(-$kenaikanPiutang, 0, ',', '.') }}
            </td>
        </tr>
        <tr>
            <td class="indent-2" style="padding: 6px; border: 1px solid #ddd; padding-left: 30px;">(Kenaikan) Penurunan Persediaan</td>
            <td class="amount" style="padding: 6px; border: 1px solid #ddd; text-align: right;">
                Rp {{ number_format(-$kenaikanPersediaan, 0, ',', '.') }}
            </td>
        </tr>
        <tr>
            <td class="indent-2" style="padding: 6px; border: 1px solid #ddd; padding-left: 30px;">Kenaikan (Penurunan) Utang Usaha</td>
            <td class="amount" style="padding: 6px; border: 1px solid #ddd; text-align: right;">
                Rp {{ number_format($kenaikanUtang, 0, ',', '.') }}
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
