@extends('laporan.pdf.layout')

@section('content')
<table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
    <thead>
        <tr style="background-color: #f5f5f5;">
            <th style="border: 1px solid #ddd; padding: 6px; font-weight: bold;">Kode Barang</th>
            <th style="border: 1px solid #ddd; padding: 6px; font-weight: bold;">Barcode</th>
            <th style="border: 1px solid #ddd; padding: 6px; font-weight: bold;">Nama Barang</th>
            <th style="border: 1px solid #ddd; padding: 6px; font-weight: bold;">Satuan</th>
            <th style="border: 1px solid #ddd; padding: 6px; font-weight: bold; text-align: right;">Stok</th>
            <th style="border: 1px solid #ddd; padding: 6px; font-weight: bold; text-align: right;">Harga Beli (Avg)</th>
            <th style="border: 1px solid #ddd; padding: 6px; font-weight: bold; text-align: right;">Total Nilai</th>
        </tr>
    </thead>
    <tbody>
        @forelse($persediaan as $item)
            <tr>
                <td style="border: 1px solid #ddd; padding: 6px;">{{ $item->kode_barang }}</td>
                <td style="border: 1px solid #ddd; padding: 6px;">{{ $item->barcode }}</td>
                <td style="border: 1px solid #ddd; padding: 6px;">{{ $item->nama_barang }}</td>
                <td style="border: 1px solid #ddd; padding: 6px; text-align: center;">{{ $item->satuan }}</td>
                <td class="amount" style="border: 1px solid #ddd; padding: 6px; text-align: right;">
                    {{ number_format($item->stok_saat_ini, 2, ',', '.') }}
                </td>
                <td class="amount" style="border: 1px solid #ddd; padding: 6px; text-align: right;">
                    Rp {{ number_format($item->harga_beli, 2, ',', '.') }}
                </td>
                <td class="amount" style="border: 1px solid #ddd; padding: 6px; text-align: right; font-weight: bold;">
                    Rp {{ number_format($item->stok_saat_ini * $item->harga_beli, 2, ',', '.') }}
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="text-center" style="padding: 10px; border: 1px solid #ddd;">Tidak ada data persediaan.</td>
            </tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr class="grand-total">
            <td colspan="6" style="font-weight: bold; padding: 6px; border: 1px solid #ddd; text-align: right;">GRAND TOTAL NILAI PERSEDIAAN</td>
            <td class="amount" style="font-weight: bold; text-align: right; padding: 6px; border: 1px solid #ddd;">
                Rp {{ number_format($totalNilai, 2, ',', '.') }}
            </td>
        </tr>
    </tfoot>
</table>
@endsection
