<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{ $title ?? 'Laporan' }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10px;
            line-height: 1.4;
            color: #333;
        }
        .container {
            padding: 20px;
        }
        /* Header */
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h1 {
            font-size: 16px;
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        .header h2 {
            font-size: 14px;
            font-weight: normal;
            margin-bottom: 3px;
        }
        .header p {
            font-size: 9px;
            color: #666;
        }
        /* Report Title */
        .report-title {
            text-align: center;
            margin: 15px 0;
        }
        .report-title h3 {
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .report-title p {
            font-size: 10px;
            color: #666;
        }
        /* Tables */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        table th, table td {
            padding: 6px 8px;
            border: 1px solid #ddd;
            text-align: left;
        }
        table th {
            background-color: #f5f5f5;
            font-weight: bold;
            font-size: 10px;
        }
        table td {
            font-size: 9px;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .text-bold {
            font-weight: bold;
        }
        /* Financial amounts */
        .amount {
            text-align: right;
            font-family: 'DejaVu Sans Mono', monospace;
        }
        .positive {
            color: #28a745;
        }
        .negative {
            color: #dc3545;
        }
        /* Total rows */
        .total-row td {
            font-weight: bold;
            background-color: #f8f9fa;
            border-top: 2px solid #333;
        }
        .grand-total td {
            font-weight: bold;
            background-color: #e9ecef;
            border-top: 3px double #333;
            font-size: 11px;
        }
        /* Section headers */
        .section-header {
            background-color: #343a40;
            color: white;
            font-weight: bold;
            padding: 8px;
            margin-top: 15px;
        }
        /* Sub-section */
        .sub-section {
            background-color: #6c757d;
            color: white;
        }
        /* Indent */
        .indent-1 { padding-left: 20px; }
        .indent-2 { padding-left: 40px; }
        /* Footer */
        .footer {
            margin-top: 30px;
            font-size: 8px;
            color: #999;
            text-align: center;
        }
        /* Signatures */
        .signatures {
            margin-top: 50px;
            page-break-inside: avoid;
        }
        .signatures table {
            border: none;
        }
        .signatures td {
            border: none;
            text-align: center;
            width: 33.33%;
            padding: 10px;
            vertical-align: top;
        }
        .signature-line {
            border-bottom: 1px solid #333;
            width: 80%;
            margin: 50px auto 5px;
        }
        /* Page break */
        .page-break {
            page-break-after: always;
        }
        /* No border table variant */
        table.no-border td,
        table.no-border th {
            border: none;
        }
        /* Balance Sheet specific */
        .balance-section {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Company Header -->
        <div class="header">
            <h1>{{ $perusahaan->nama_perusahaan ?? 'NAMA PERUSAHAAN' }}</h1>
            <p>{{ $perusahaan->alamat ?? '' }}</p>
            <p>Telp: {{ $perusahaan->telepon ?? '-' }} | Email: {{ $perusahaan->email ?? '-' }}</p>
        </div>

        <!-- Report Title -->
        <div class="report-title">
            <h3>{{ $title ?? 'LAPORAN' }}</h3>
            <p>{{ $subtitle ?? '' }}</p>
        </div>

        <!-- Report Content -->
        @yield('content')

        <!-- Signatures -->
        @if(isset($showSignatures) && $showSignatures)
        <div class="signatures">
            <table class="no-border">
                <tr>
                    <td></td>
                    <td></td>
                    <td>
                        {{ $tanggal ?? date('d F Y') }}
                    </td>
                </tr>
                <tr>
                    <td>
                        <p>Mengetahui,</p>
                        <p><strong>{{ $perusahaan->jabatan_direktur ?? 'Direktur' }}</strong></p>
                        <div class="signature-line"></div>
                        <p>{{ $perusahaan->nama_direktur ?? '________________' }}</p>
                    </td>
                    <td></td>
                    <td>
                        <p>Dibuat oleh,</p>
                        <p><strong>{{ $perusahaan->jabatan_akuntan ?? 'Bag. Keuangan' }}</strong></p>
                        <div class="signature-line"></div>
                        <p>{{ $perusahaan->nama_akuntan ?? '________________' }}</p>
                    </td>
                </tr>
            </table>
        </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            Dicetak pada: {{ now()->format('d/m/Y H:i:s') }} | Simple Akunting v3
        </div>
    </div>
</body>
</html>
