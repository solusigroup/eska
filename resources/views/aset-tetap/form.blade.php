@extends('layouts.app')

@section('title', isset($asset) ? 'Edit Aset Tetap - Simple Akunting' : 'Tambah Aset Tetap - Simple Akunting')

@section('content')
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">{{ isset($asset) ? 'Edit Aset Tetap' : 'Tambah Aset Tetap' }}</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="{{ route('aset-tetap.index') }}" class="btn btn-sm btn-outline-secondary">
                <span data-feather="arrow-left"></span> Kembali ke Daftar
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <h6 class="alert-heading fw-bold">Terjadi Kesalahan Validasi:</h6>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-4">
            <form action="{{ isset($asset) ? route('aset-tetap.update', $asset->id) : route('aset-tetap.store') }}" method="POST">
                @csrf
                @if (isset($asset))
                    @method('PUT')
                @endif

                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="kode_aset" class="form-label fw-semibold">Kode Aset <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('kode_aset') is-invalid @enderror" id="kode_aset" name="kode_aset" value="{{ old('kode_aset', $asset->kode_aset ?? '') }}" required {{ isset($asset) ? 'readonly' : '' }}>
                        <div class="form-text">Contoh: AT-001, COMP-002</div>
                    </div>
                    <div class="col-md-8">
                        <label for="nama_aset" class="form-label fw-semibold">Nama Aset Tetap <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('nama_aset') is-invalid @enderror" id="nama_aset" name="nama_aset" value="{{ old('nama_aset', $asset->nama_aset ?? '') }}" required>
                    </div>

                    <div class="col-md-4">
                        <label for="tanggal_perolehan" class="form-label fw-semibold">Tanggal Perolehan <span class="text-danger">*</span></label>
                        <input type="date" class="form-control @error('tanggal_perolehan') is-invalid @enderror" id="tanggal_perolehan" name="tanggal_perolehan" value="{{ old('tanggal_perolehan', isset($asset) ? $asset->tanggal_perolehan->format('Y-m-d') : date('Y-m-d')) }}" required>
                    </div>
                    <div class="col-md-4">
                        <label for="harga_perolehan" class="form-label fw-semibold">Harga Perolehan (IDR) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" class="form-control @error('harga_perolehan') is-invalid @enderror" id="harga_perolehan" name="harga_perolehan" value="{{ old('harga_perolehan', $asset->harga_perolehan ?? '0') }}" min="0" required>
                    </div>
                    <div class="col-md-4">
                        <label for="nilai_residu" class="form-label fw-semibold">Nilai Residu / Sisa (IDR) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" class="form-control @error('nilai_residu') is-invalid @enderror" id="nilai_residu" name="nilai_residu" value="{{ old('nilai_residu', $asset->nilai_residu ?? '0') }}" min="0" required>
                    </div>

                    <div class="col-md-4">
                        <label for="umur_ekonomis" class="form-label fw-semibold">Umur Ekonomis (Bulan) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control @error('umur_ekonomis') is-invalid @enderror" id="umur_ekonomis" name="umur_ekonomis" value="{{ old('umur_ekonomis', $asset->umur_ekonomis ?? '48') }}" min="1" required>
                        <div class="form-text">Berapa bulan aset akan didepresiasi (misal: 4 tahun = 48 bulan).</div>
                    </div>
                    @if (isset($asset))
                        <div class="col-md-8">
                            <label for="status" class="form-label fw-semibold">Status Aset <span class="text-danger">*</span></label>
                            <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                                <option value="Aktif" {{ old('status', $asset->status) == 'Aktif' ? 'selected' : '' }}>Aktif (Sedang Berjalan Depresiasinya)</option>
                                <option value="Habis" {{ old('status', $asset->status) == 'Habis' ? 'selected' : '' }}>Habis (Telah Sepenuhnya Disusutkan)</option>
                                <option value="Terjual" {{ old('status', $asset->status) == 'Terjual' ? 'selected' : '' }}>Terjual / Dihapus Buku</option>
                            </select>
                        </div>
                    @else
                        <div class="col-md-8"></div>
                    @endif

                    <div class="col-12 mt-4 border-top pt-3">
                        <h5 class="h6 text-primary fw-bold">Pemetaan Akun Jurnal Otomatis</h5>
                        <p class="text-muted" style="font-size: 0.85rem;">Pilih akun perkiraan yang akan digunakan untuk pencatatan transaksi pembelian & jurnal penyusutan bulanan secara otomatis.</p>
                    </div>

                    <div class="col-md-4">
                        <label for="kode_akun_aset" class="form-label fw-semibold">Akun Aset Tetap <span class="text-danger">*</span></label>
                        <select class="form-select @error('kode_akun_aset') is-invalid @enderror" id="kode_akun_aset" name="kode_akun_aset" required>
                            <option value="">-- Pilih Akun Aset --</option>
                            @foreach ($akunAset as $akun)
                                <option value="{{ $akun->kode_akun }}" {{ old('kode_akun_aset', $asset->kode_akun_aset ?? '') == $akun->kode_akun ? 'selected' : '' }}>
                                    {{ $akun->kode_akun }} - {{ $akun->nama_akun }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="kode_akun_akumulasi" class="form-label fw-semibold">Akun Akumulasi Depresiasi <span class="text-danger">*</span></label>
                        <select class="form-select @error('kode_akun_akumulasi') is-invalid @enderror" id="kode_akun_akumulasi" name="kode_akun_akumulasi" required>
                            <option value="">-- Pilih Akun Akumulasi --</option>
                            @foreach ($akunAkumulasi as $akun)
                                <option value="{{ $akun->kode_akun }}" {{ old('kode_akun_akumulasi', $asset->kode_akun_akumulasi ?? '') == $akun->kode_akun ? 'selected' : '' }}>
                                    {{ $akun->kode_akun }} - {{ $akun->nama_akun }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="kode_akun_beban" class="form-label fw-semibold">Akun Beban Depresiasi <span class="text-danger">*</span></label>
                        <select class="form-select @error('kode_akun_beban') is-invalid @enderror" id="kode_akun_beban" name="kode_akun_beban" required>
                            <option value="">-- Pilih Akun Beban --</option>
                            @foreach ($akunBeban as $akun)
                                <option value="{{ $akun->kode_akun }}" {{ old('kode_akun_beban', $asset->kode_akun_beban ?? '') == $akun->kode_akun ? 'selected' : '' }}>
                                    {{ $akun->kode_akun }} - {{ $akun->nama_akun }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 mt-4 text-end">
                        <button type="submit" class="btn btn-primary px-4">
                            <span data-feather="check-square"></span> Simpan Data
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
