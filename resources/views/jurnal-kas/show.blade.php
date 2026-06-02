@extends('layouts.app')

@section('title', 'Detail Jurnal Kas - Simple Akunting')

@section('content')
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Detail Jurnal Kas #{{ $jurnalKas->no_bukti }}</h1>
        <div class="btn-toolbar mb-2 mb-md-0 gap-2">
            <a href="{{ route('jurnal-kas.index') }}" class="btn btn-sm btn-secondary">
                <span data-feather="arrow-left" style="width: 14px; height: 14px; margin-right: 4px;"></span>
                Kembali
            </a>
            <a href="{{ route('jurnal-kas.edit', $jurnalKas->id_jurnal_kas) }}" class="btn btn-sm btn-warning">
                <span data-feather="edit-2" style="width: 14px; height: 14px; margin-right: 4px;"></span>
                Edit
            </a>
            <form action="{{ route('jurnal-kas.destroy', $jurnalKas->id_jurnal_kas) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus transaksi ini? Jurnal umum terkait juga akan dihapus!')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-danger">
                    <span data-feather="trash-2" style="width: 14px; height: 14px; margin-right: 4px;"></span>
                    Hapus
                </button>
            </form>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Informasi Transaksi</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless mb-0">
                        <tr>
                            <td width="180"><strong>No. Bukti</strong></td>
                            <td>: <code>{{ $jurnalKas->no_bukti }}</code></td>
                        </tr>
                        <tr>
                            <td><strong>Tanggal</strong></td>
                            <td>: {{ $jurnalKas->tanggal->format('d F Y') }}</td>
                        </tr>
                        <tr>
                            <td><strong>Tipe Jurnal Kas</strong></td>
                            <td>: 
                                @if($jurnalKas->tipe == 'Masuk')
                                    <span class="badge bg-success">Kas Masuk</span>
                                @else
                                    <span class="badge bg-danger">Kas Keluar</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Jumlah</strong></td>
                            <td>: <strong class="text-primary">Rp {{ number_format($jurnalKas->jumlah, 2, ',', '.') }}</strong></td>
                        </tr>
                        <tr>
                            <td><strong>Proyek</strong></td>
                            <td>: 
                                @if($jurnalKas->proyek)
                                    <span class="badge bg-primary">{{ $jurnalKas->proyek->kode_proyek }} - {{ $jurnalKas->proyek->nama_proyek }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Keterangan</strong></td>
                            <td>: {{ $jurnalKas->keterangan ?? '-' }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Hubungan Akun & Jurnal Umum</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless mb-3">
                        <tr>
                            <td width="180"><strong>Akun Kas/Bank</strong></td>
                            <td>: {{ $jurnalKas->akun_kas }} - {{ $jurnalKas->akunKasRef->nama_akun ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td><strong>Akun Lawan</strong></td>
                            <td>: {{ $jurnalKas->akun_lawan }} - {{ $jurnalKas->akunLawanRef->nama_akun ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td><strong>Jurnal Umum Terkait</strong></td>
                            <td>: 
                                @if($jurnalKas->jurnal)
                                    <a href="{{ route('jurnal.show', $jurnalKas->id_jurnal) }}" class="fw-bold">
                                        {{ $jurnalKas->jurnal->no_transaksi }}
                                    </a>
                                @else
                                    <span class="text-danger">Belum Terposting</span>
                                @endif
                            </td>
                        </tr>
                    </table>

                    @if($jurnalKas->jurnal)
                        <h6 class="mt-4 mb-2">Entri Jurnal Terposting:</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Akun</th>
                                        <th class="text-end">Debit</th>
                                        <th class="text-end">Kredit</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($jurnalKas->jurnal->details as $det)
                                        <tr>
                                            <td>{{ $det->kode_akun }} - {{ $det->akun->nama_akun ?? '-' }}</td>
                                            <td class="text-end">{{ $det->debit > 0 ? 'Rp ' . number_format($det->debit, 2, ',', '.') : '-' }}</td>
                                            <td class="text-end">{{ $det->kredit > 0 ? 'Rp ' . number_format($det->kredit, 2, ',', '.') : '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        feather.replace({ 'aria-hidden': 'true' });
    </script>
@endpush
