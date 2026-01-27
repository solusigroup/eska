@extends('layouts.app')

@section('title', 'Review Import Kas - Simple Akunting')

@section('content')
<div class="page-header-actions">
    <div>
        <h1 class="page-title">Review Import Kas</h1>
        <p class="page-subtitle">Pilih transaksi dan lengkapi akun sebelum posting</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('import-kas.index') }}" class="btn btn-outline-secondary btn-sm">
            <span data-feather="arrow-left"></span> Kembali
        </a>
        <form action="{{ route('import-kas.clear') }}" method="POST" class="d-inline" 
              onsubmit="return confirm('Yakin hapus semua data import yang belum diposting?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-outline-danger btn-sm">
                <span data-feather="trash-2"></span> Hapus Semua
            </button>
        </form>
    </div>
</div>

@if($data->isEmpty())
<div class="alert alert-info">
    <span data-feather="info"></span> Tidak ada data import yang pending. 
    <a href="{{ route('import-kas.index') }}">Upload file CSV</a> untuk memulai.
</div>
@else

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card bg-light">
            <div class="card-body text-center py-3">
                <h5 class="mb-1">{{ $data->count() }}</h5>
                <small class="text-muted">Total Transaksi</small>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card" style="background: rgba(16, 185, 129, 0.1);">
            <div class="card-body text-center py-3">
                <h5 class="mb-1 text-success">Rp {{ number_format($totalMasuk, 0, ',', '.') }}</h5>
                <small class="text-muted">Total Kas Masuk (Dipilih)</small>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card" style="background: rgba(239, 68, 68, 0.1);">
            <div class="card-body text-center py-3">
                <h5 class="mb-1 text-danger">Rp {{ number_format($totalKeluar, 0, ',', '.') }}</h5>
                <small class="text-muted">Total Kas Keluar (Dipilih)</small>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Actions -->
<div class="card mb-3">
    <div class="card-body py-2">
        <div class="row align-items-center g-2">
            <div class="col-auto">
                <strong class="small">Set Akun Massal:</strong>
            </div>
            <div class="col-md-3">
                <select id="bulkAkunKas" class="form-select form-select-sm">
                    <option value="">Akun Kas...</option>
                    @foreach($akunKas as $akun)
                        <option value="{{ $akun->kode_akun }}">{{ $akun->kode_akun }} - {{ $akun->nama_akun }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select id="bulkAkunMasuk" class="form-select form-select-sm">
                    <option value="">Akun Uang Masuk...</option>
                    @foreach($akunPendapatan as $akun)
                        <option value="{{ $akun->kode_akun }}">{{ $akun->kode_akun }} - {{ $akun->nama_akun }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select id="bulkAkunKeluar" class="form-select form-select-sm">
                    <option value="">Akun Uang Keluar...</option>
                    @foreach($akunBiaya as $akun)
                        <option value="{{ $akun->kode_akun }}">{{ $akun->kode_akun }} - {{ $akun->nama_akun }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <button type="button" id="btnApplyBulk" class="btn btn-sm btn-secondary">Terapkan</button>
            </div>
        </div>
    </div>
</div>

<!-- Data Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 40px;">
                            <input type="checkbox" id="selectAll" class="form-check-input">
                        </th>
                        <th>No</th>
                        <th>Tanggal</th>
                        <th>Uraian</th>
                        <th class="text-end">Masuk</th>
                        <th class="text-end">Keluar</th>
                        <th>Akun Kas</th>
                        <th>Akun Lawan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data as $row)
                    <tr data-id="{{ $row->id }}" class="{{ $row->is_selected ? '' : 'table-secondary' }}">
                        <td>
                            <input type="checkbox" class="form-check-input row-select" 
                                   data-id="{{ $row->id }}" {{ $row->is_selected ? 'checked' : '' }}>
                        </td>
                        <td>{{ $row->no_referensi }}</td>
                        <td>{{ $row->tanggal->format('d/m/Y') }}</td>
                        <td>{{ Str::limit($row->uraian, 50) }}</td>
                        <td class="text-end {{ $row->uang_masuk > 0 ? 'text-success fw-bold' : '' }}">
                            {{ $row->uang_masuk > 0 ? number_format($row->uang_masuk, 0, ',', '.') : '-' }}
                        </td>
                        <td class="text-end {{ $row->uang_keluar > 0 ? 'text-danger fw-bold' : '' }}">
                            {{ $row->uang_keluar > 0 ? number_format($row->uang_keluar, 0, ',', '.') : '-' }}
                        </td>
                        <td>
                            <select class="form-select form-select-sm akun-kas" data-id="{{ $row->id }}" style="min-width: 180px;">
                                <option value="">Pilih...</option>
                                @foreach($akunKas as $akun)
                                    <option value="{{ $akun->kode_akun }}" {{ $row->kode_akun_kas == $akun->kode_akun ? 'selected' : '' }}>
                                        {{ $akun->kode_akun }} - {{ $akun->nama_akun }}
                                    </option>
                                @endforeach
                            </select>
                        </td>
                        <td>
                            <select class="form-select form-select-sm akun-lawan" data-id="{{ $row->id }}" style="min-width: 180px;">
                                <option value="">Pilih...</option>
                                @if($row->uang_masuk > 0)
                                    @foreach($akunPendapatan as $akun)
                                        <option value="{{ $akun->kode_akun }}" {{ $row->kode_akun_lawan == $akun->kode_akun ? 'selected' : '' }}>
                                            {{ $akun->kode_akun }} - {{ $akun->nama_akun }}
                                        </option>
                                    @endforeach
                                @else
                                    @foreach($akunBiaya as $akun)
                                        <option value="{{ $akun->kode_akun }}" {{ $row->kode_akun_lawan == $akun->kode_akun ? 'selected' : '' }}>
                                            {{ $akun->kode_akun }} - {{ $akun->nama_akun }}
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Post Button -->
<div class="mt-4 text-end">
    <form action="{{ route('import-kas.post') }}" method="POST" id="postForm"
          onsubmit="return confirm('Posting transaksi yang dipilih ke jurnal?')">
        @csrf
        <button type="submit" class="btn btn-success btn-lg">
            <span data-feather="check-circle"></span> Posting ke Jurnal
        </button>
    </form>
</div>

@endif
@endsection

@push('scripts')
<script>
    feather.replace();

    // Select All checkbox
    document.getElementById('selectAll')?.addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.row-select');
        checkboxes.forEach(cb => {
            cb.checked = this.checked;
            updateSelection(cb.dataset.id, this.checked);
        });
    });

    // Individual row selection
    document.querySelectorAll('.row-select').forEach(cb => {
        cb.addEventListener('change', function() {
            updateSelection(this.dataset.id, this.checked);
            const row = this.closest('tr');
            row.classList.toggle('table-secondary', !this.checked);
        });
    });

    // Akun Kas change
    document.querySelectorAll('.akun-kas').forEach(select => {
        select.addEventListener('change', function() {
            updateAkun(this.dataset.id, 'kode_akun_kas', this.value);
        });
    });

    // Akun Lawan change
    document.querySelectorAll('.akun-lawan').forEach(select => {
        select.addEventListener('change', function() {
            updateAkun(this.dataset.id, 'kode_akun_lawan', this.value);
        });
    });

    // Bulk apply button
    document.getElementById('btnApplyBulk')?.addEventListener('click', function() {
        const akunKas = document.getElementById('bulkAkunKas').value;
        const akunMasuk = document.getElementById('bulkAkunMasuk').value;
        const akunKeluar = document.getElementById('bulkAkunKeluar').value;

        if (akunKas) bulkUpdateAkun('kode_akun_kas', akunKas);
        if (akunMasuk) bulkUpdateAkun('kode_akun_lawan_masuk', akunMasuk);
        if (akunKeluar) bulkUpdateAkun('kode_akun_lawan_keluar', akunKeluar);

        // Reload after short delay
        setTimeout(() => location.reload(), 500);
    });

    function updateSelection(id, isSelected) {
        fetch('{{ route("import-kas.update-selection") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ id: id, is_selected: isSelected })
        });
    }

    function updateAkun(id, field, value) {
        fetch('{{ route("import-kas.update-akun") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ id: id, field: field, value: value })
        });
    }

    function bulkUpdateAkun(field, value) {
        fetch('{{ route("import-kas.bulk-update-akun") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ field: field, value: value })
        });
    }
</script>
@endpush
