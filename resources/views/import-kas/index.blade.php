@extends('layouts.app')

@section('title', 'Import Kas - Simple Akunting')

@section('content')
<div class="page-header-actions">
    <div>
        <h1 class="page-title">Import Kas Masuk & Keluar</h1>
        <p class="page-subtitle">Import data transaksi kas dari file CSV</p>
    </div>
    @if($hasPendingData)
    <div>
        <a href="{{ route('import-kas.review') }}" class="btn btn-primary">
            <span data-feather="eye"></span> Lihat Data Pending
        </a>
    </div>
    @endif
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <strong>📁 Upload File CSV</strong>
            </div>
            <div class="card-body">
                <form action="{{ route('import-kas.upload') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    
                    <div class="mb-4">
                        <label class="form-label">Pilih File CSV</label>
                        <input type="file" name="file" class="form-control @error('file') is-invalid @enderror" 
                               accept=".csv,.txt" required>
                        @error('file')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Format yang didukung: CSV dengan separator titik koma (;)</div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <span data-feather="upload"></span> Upload & Proses
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header bg-info text-white">
                <strong>📋 Format CSV</strong>
            </div>
            <div class="card-body">
                <p class="small mb-2">File CSV harus memiliki kolom berikut:</p>
                <code class="d-block mb-3" style="font-size: 0.75rem;">No;Tanggal;Uraian;Uang Masuk;Uang Keluar</code>
                
                <p class="small mb-2"><strong>Contoh data:</strong></p>
                <pre class="bg-light p-2" style="font-size: 0.7rem; overflow-x: auto;">
1;01-01-2026;Penjualan Tunai;500000;0
2;02-01-2026;Beli ATK;0;150000
3;03-01-2026;Pendapatan Jasa;1000000;0</pre>

                <div class="alert alert-warning py-2 mb-0" style="font-size: 0.75rem;">
                    <strong>Catatan:</strong>
                    <ul class="mb-0 ps-3">
                        <li>Gunakan titik koma (;) sebagai separator</li>
                        <li>Format tanggal: DD-MM-YYYY atau YYYY-MM-DD</li>
                        <li>Angka tanpa separator ribuan</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    feather.replace();
</script>
@endpush
