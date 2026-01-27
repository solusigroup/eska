@extends('layouts.app')

@section('title', $isEdit ? 'Edit Proyek' : 'Tambah Proyek')

@section('content')
    <div class="page-header">
        <h1 class="page-title">{{ $isEdit ? 'Edit Proyek' : 'Tambah Proyek Baru' }}</h1>
        <p class="page-subtitle">{{ $isEdit ? 'Perbarui informasi proyek' : 'Buat proyek baru untuk tracking keuangan' }}
        </p>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ $isEdit ? route('proyek.update', $proyek->id_proyek) : route('proyek.store') }}" method="POST">
                @csrf
                @if($isEdit) @method('PUT') @endif

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="kode_proyek" class="form-label">Kode Proyek <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('kode_proyek') is-invalid @enderror" id="kode_proyek"
                            name="kode_proyek" value="{{ old('kode_proyek', $proyek->kode_proyek) }}" placeholder="PRJ-001"
                            required>
                        @error('kode_proyek')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                        <select class="form-select @error('status') is-invalid @enderror" id="status" name="status"
                            required>
                            <option value="Aktif" {{ old('status', $proyek->status ?? 'Aktif') == 'Aktif' ? 'selected' : '' }}>Aktif</option>
                            <option value="Selesai" {{ old('status', $proyek->status) == 'Selesai' ? 'selected' : '' }}>
                                Selesai</option>
                            <option value="Ditunda" {{ old('status', $proyek->status) == 'Ditunda' ? 'selected' : '' }}>
                                Ditunda</option>
                        </select>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label for="nama_proyek" class="form-label">Nama Proyek <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('nama_proyek') is-invalid @enderror" id="nama_proyek"
                        name="nama_proyek" value="{{ old('nama_proyek', $proyek->nama_proyek) }}"
                        placeholder="Nama proyek..." required>
                    @error('nama_proyek')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="deskripsi" class="form-label">Deskripsi</label>
                    <textarea class="form-control @error('deskripsi') is-invalid @enderror" id="deskripsi" name="deskripsi"
                        rows="3" placeholder="Deskripsi proyek...">{{ old('deskripsi', $proyek->deskripsi) }}</textarea>
                    @error('deskripsi')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="tanggal_mulai" class="form-label">Tanggal Mulai</label>
                        <input type="date" class="form-control @error('tanggal_mulai') is-invalid @enderror"
                            id="tanggal_mulai" name="tanggal_mulai"
                            value="{{ old('tanggal_mulai', $proyek->tanggal_mulai?->format('Y-m-d')) }}">
                        @error('tanggal_mulai')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="tanggal_selesai" class="form-label">Tanggal Selesai</label>
                        <input type="date" class="form-control @error('tanggal_selesai') is-invalid @enderror"
                            id="tanggal_selesai" name="tanggal_selesai"
                            value="{{ old('tanggal_selesai', $proyek->tanggal_selesai?->format('Y-m-d')) }}">
                        @error('tanggal_selesai')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="anggaran" class="form-label">Anggaran</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="number" class="form-control @error('anggaran') is-invalid @enderror" id="anggaran"
                                name="anggaran" value="{{ old('anggaran', $proyek->anggaran ?? 0) }}" min="0" step="1000">
                        </div>
                        @error('anggaran')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="lokasi" class="form-label">Lokasi</label>
                        <input type="text" class="form-control @error('lokasi') is-invalid @enderror" id="lokasi"
                            name="lokasi" value="{{ old('lokasi', $proyek->lokasi) }}" placeholder="Lokasi proyek...">
                        @error('lokasi')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="pelanggan" class="form-label">Pelanggan/Klien</label>
                        <input type="text" class="form-control @error('pelanggan') is-invalid @enderror" id="pelanggan"
                            name="pelanggan" value="{{ old('pelanggan', $proyek->pelanggan) }}"
                            placeholder="Nama pelanggan...">
                        @error('pelanggan')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <hr class="my-4">

                <div class="d-flex justify-content-between">
                    <a href="{{ route('proyek.index') }}" class="btn btn-outline-secondary">
                        <span data-feather="arrow-left"></span> Kembali
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <span data-feather="save"></span> {{ $isEdit ? 'Simpan Perubahan' : 'Simpan Proyek' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        feather.replace({ 'aria-hidden': 'true' });
    </script>
@endpush