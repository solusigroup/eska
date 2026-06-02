@extends('layouts.app')

@section('title', 'Diagnosa Keseimbangan Neraca - Simple Akunting')

@section('content')
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Diagnosa Neraca & Pembukuan</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <form method="GET" action="{{ route('diagnosa.index') }}" class="d-flex gap-2">
                <input type="date" name="per_tanggal" class="form-control form-control-sm" value="{{ $perTanggal }}">
                <button type="submit" class="btn btn-sm btn-primary">Filter</button>
            </form>
        </div>
    </div>

    <!-- Banner Status Utama -->
    @if ($isBalanced)
        <div class="card bg-success text-white shadow-sm border-0 mb-4">
            <div class="card-body p-4 d-flex align-items-center gap-3">
                <span style="font-size: 2.5rem;">✅</span>
                <div>
                    <h4 class="alert-heading fw-bold mb-1">Neraca Seimbang (Balanced)</h4>
                    <p class="mb-0" style="opacity: 0.9;">
                        Total Aktiva sama dengan total Pasiva s/d tanggal {{ date('d-m-Y', strtotime($perTanggal)) }}. Tidak terdeteksi selisih pembukuan.
                    </p>
                </div>
            </div>
        </div>
    @else
        <div class="card bg-danger text-white shadow-sm border-0 mb-4">
            <div class="card-body p-4 d-flex align-items-center gap-3">
                <span style="font-size: 2.5rem;">⚠️</span>
                <div>
                    <h4 class="alert-heading fw-bold mb-1">Neraca Tidak Seimbang (Unbalanced)</h4>
                    <p class="mb-0 fs-5 fw-semibold" style="opacity: 0.95;">
                        Terdapat selisih sebesar Rp {{ number_format($selisih, 2, ',', '.') }}
                    </p>
                    <p class="mb-0" style="opacity: 0.85; font-size: 0.9rem;">
                        Periksa rincian diagnosa di bawah untuk menemukan kesalahan pencatatan transaksi jurnal.
                    </p>
                </div>
            </div>
        </div>
    @endif

    <div class="row">
        <!-- Rincian Nilai Neraca -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-light py-3">
                    <h5 class="card-title mb-0 h6 fw-bold text-secondary">Rasio Neraca Aktiva vs Pasiva</h5>
                </div>
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted fw-semibold">Total Aktiva (Aset)</span>
                        <span class="font-monospace fw-bold text-dark">Rp {{ number_format($totalAktiva, 2, ',', '.') }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-3 border-bottom pb-2">
                        <span class="text-muted fw-semibold">Total Pasiva (Kewajiban + Ekuitas + Laba Berjalan)</span>
                        <span class="font-monospace fw-bold text-dark">Rp {{ number_format($totalPasiva, 2, ',', '.') }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="fw-bold text-secondary">Selisih Selaras</span>
                        <span class="font-monospace fw-bold {{ $isBalanced ? 'text-success' : 'text-danger' }}">
                            Rp {{ number_format($selisih, 2, ',', '.') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ringkasan Diagnosa Masalah -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-light py-3">
                    <h5 class="card-title mb-0 h6 fw-bold text-secondary">Ringkasan Temuan Sistem</h5>
                </div>
                <div class="card-body p-4">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span>Jurnal Tidak Seimbang (Debit != Kredit)</span>
                            <span class="badge {{ count($unbalancedJurnals) > 0 ? 'bg-danger' : 'bg-success' }}">
                                {{ count($unbalancedJurnals) }} Temuan
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span>Transaksi dengan Akun Tidak Terdaftar (COA)</span>
                            <span class="badge {{ count($invalidAccountDetails) > 0 ? 'bg-danger' : 'bg-success' }}">
                                {{ count($invalidAccountDetails) }} Temuan
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span>Jurnal Kosong (Tanpa Detail Transaksi)</span>
                            <span class="badge {{ count($emptyJurnals) > 0 ? 'bg-warning text-dark' : 'bg-success' }}">
                                {{ count($emptyJurnals) }} Temuan
                            </span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- PANEL DETAIL TEMUAN -->

    <!-- 1. Jurnal Tidak Seimbang -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-light py-3 d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0 h6 fw-bold text-danger">🔎 Detail Jurnal Tidak Seimbang</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>No. Transaksi</th>
                            <th>Tanggal</th>
                            <th>Deskripsi / Memo</th>
                            <th class="text-end">Total Debit</th>
                            <th class="text-end">Total Kredit</th>
                            <th class="text-end">Selisih</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($unbalancedJurnals as $jurnal)
                            <tr>
                                <td class="font-monospace fw-bold">{{ $jurnal->no_transaksi }}</td>
                                <td>{{ $jurnal->tanggal->format('d-m-Y') }}</td>
                                <td>{{ $jurnal->deskripsi }}</td>
                                <td class="text-end font-monospace">Rp {{ number_format($jurnal->total_debit, 2, ',', '.') }}</td>
                                <td class="text-end font-monospace">Rp {{ number_format($jurnal->total_kredit, 2, ',', '.') }}</td>
                                <td class="text-end font-monospace fw-bold text-danger">
                                    Rp {{ number_format($jurnal->selisih, 2, ',', '.') }}
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('jurnal.edit', $jurnal->id_jurnal) }}" class="btn btn-sm btn-outline-primary" target="_blank">
                                        Perbaiki Jurnal
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-3 text-muted">Selamat! Seluruh jurnal transaksi seimbang (Debit = Kredit).</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- 2. Jurnal Detail dengan Akun Tidak Terdaftar -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-light py-3">
            <h5 class="card-title mb-0 h6 fw-bold text-danger">🔎 Detail Jurnal dengan Akun Tidak Terdaftar (Invalid COA)</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>No. Transaksi</th>
                            <th>Tanggal Jurnal</th>
                            <th>Kode Akun Invalid</th>
                            <th class="text-end">Debit</th>
                            <th class="text-end">Kredit</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($invalidAccountDetails as $detail)
                            <tr>
                                <td class="font-monospace fw-bold">
                                    {{ $detail->jurnal->no_transaksi ?? 'N/A' }}
                                </td>
                                <td>
                                    {{ $detail->jurnal ? $detail->jurnal->tanggal->format('d-m-Y') : 'N/A' }}
                                </td>
                                <td class="text-danger fw-bold font-monospace">{{ $detail->kode_akun }}</td>
                                <td class="text-end font-monospace">Rp {{ number_format($detail->debit, 2, ',', '.') }}</td>
                                <td class="text-end font-monospace">Rp {{ number_format($detail->kredit, 2, ',', '.') }}</td>
                                <td class="text-center">
                                    @if($detail->id_jurnal)
                                        <a href="{{ route('jurnal.edit', $detail->id_jurnal) }}" class="btn btn-sm btn-outline-primary" target="_blank">
                                            Perbaiki Jurnal
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-3 text-muted">Seluruh jurnal menggunakan akun yang terdaftar di COA secara valid.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- 3. Jurnal Kosong -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-light py-3">
            <h5 class="card-title mb-0 h6 fw-bold text-warning text-dark">🔎 Detail Jurnal Kosong (Tanpa Detail Transaksi)</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>No. Transaksi</th>
                            <th>Tanggal</th>
                            <th>Deskripsi / Memo</th>
                            <th>Sumber Jurnal</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($emptyJurnals as $jurnal)
                            <tr>
                                <td class="font-monospace fw-bold">{{ $jurnal->no_transaksi }}</td>
                                <td>{{ $jurnal->tanggal->format('d-m-Y') }}</td>
                                <td>{{ $jurnal->deskripsi }}</td>
                                <td>{{ $jurnal->sumber_jurnal }}</td>
                                <td class="text-center">
                                    <a href="{{ route('jurnal.edit', $jurnal->id_jurnal) }}" class="btn btn-sm btn-outline-primary" target="_blank">
                                        Isi/Edit Jurnal
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-3 text-muted">Tidak ditemukan jurnal kosong. Seluruh jurnal memiliki baris transaksi detail.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
