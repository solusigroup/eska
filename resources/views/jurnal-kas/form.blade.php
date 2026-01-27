@extends('layouts.app')

@section('title', isset($jurnalKas) ? 'Edit Jurnal Kas' : ($tipe == 'Masuk' ? 'Kas Masuk' : 'Kas Keluar'))

@section('content')
    <div class="page-header">
        <h1 class="page-title">
            @if(isset($jurnalKas))
                Edit Jurnal Kas
            @else
                {{ $tipe == 'Masuk' ? '💰 Kas Masuk' : '💸 Kas Keluar' }}
            @endif
        </h1>
        <p class="page-subtitle">
            @if($tipe == 'Masuk')
                Catat penerimaan kas/bank dan otomatis posting ke jurnal umum
            @else
                Catat pengeluaran kas/bank dan otomatis posting ke jurnal umum
            @endif
        </p>
    </div>

    <div class="card">
        <div class="card-body">
            <form
                action="{{ isset($jurnalKas) ? route('jurnal-kas.update', $jurnalKas->id_jurnal_kas) : route('jurnal-kas.store') }}"
                method="POST">
                @csrf
                @if(isset($jurnalKas)) @method('PUT') @endif

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="no_bukti" class="form-label">No. Bukti <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('no_bukti') is-invalid @enderror" id="no_bukti"
                            name="no_bukti" value="{{ old('no_bukti', $jurnalKas->no_bukti ?? $noBukti) }}" required>
                        @error('no_bukti')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="tanggal" class="form-label">Tanggal <span class="text-danger">*</span></label>
                        <input type="date" class="form-control @error('tanggal') is-invalid @enderror" id="tanggal"
                            name="tanggal"
                            value="{{ old('tanggal', isset($jurnalKas) ? $jurnalKas->tanggal->format('Y-m-d') : date('Y-m-d')) }}"
                            required>
                        @error('tanggal')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="tipe" class="form-label">Tipe <span class="text-danger">*</span></label>
                        <select class="form-select @error('tipe') is-invalid @enderror" id="tipe" name="tipe" required>
                            <option value="Masuk" {{ old('tipe', $jurnalKas->tipe ?? $tipe) == 'Masuk' ? 'selected' : '' }}>
                                Kas Masuk</option>
                            <option value="Keluar" {{ old('tipe', $jurnalKas->tipe ?? $tipe) == 'Keluar' ? 'selected' : '' }}>
                                Kas Keluar</option>
                        </select>
                        @error('tipe')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="akun_kas" class="form-label">Akun Kas/Bank <span class="text-danger">*</span></label>
                        <select class="form-select @error('akun_kas') is-invalid @enderror" id="akun_kas" name="akun_kas"
                            required>
                            <option value="">-- Pilih Akun Kas/Bank --</option>
                            @foreach($akunKas as $ak)
                                <option value="{{ $ak->kode_akun }}" {{ old('akun_kas', $jurnalKas->akun_kas ?? '') == $ak->kode_akun ? 'selected' : '' }}>
                                    {{ $ak->kode_akun }} - {{ $ak->nama_akun }}
                                </option>
                            @endforeach
                        </select>
                        @error('akun_kas')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="akun_lawan" class="form-label">Akun Lawan <span class="text-danger">*</span></label>
                        <select class="form-select @error('akun_lawan') is-invalid @enderror" id="akun_lawan"
                            name="akun_lawan" required>
                            <option value="">-- Pilih Akun Lawan --</option>
                            @foreach($akunLawan as $al)
                                <option value="{{ $al->kode_akun }}" {{ old('akun_lawan', $jurnalKas->akun_lawan ?? '') == $al->kode_akun ? 'selected' : '' }}>
                                    {{ $al->kode_akun }} - {{ $al->nama_akun }}
                                </option>
                            @endforeach
                        </select>
                        @error('akun_lawan')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="jumlah" class="form-label">Jumlah <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="number" class="form-control @error('jumlah') is-invalid @enderror" id="jumlah"
                                name="jumlah" value="{{ old('jumlah', $jurnalKas->jumlah ?? '') }}" min="1" step="1"
                                placeholder="0" required>
                        </div>
                        @error('jumlah')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="id_proyek" class="form-label">Proyek</label>
                        <select class="form-select @error('id_proyek') is-invalid @enderror" id="id_proyek"
                            name="id_proyek">
                            <option value="">-- Tanpa Proyek --</option>
                            @foreach($proyeks as $p)
                                <option value="{{ $p->id_proyek }}" {{ old('id_proyek', $jurnalKas->id_proyek ?? '') == $p->id_proyek ? 'selected' : '' }}>
                                    {{ $p->kode_proyek }} - {{ $p->nama_proyek }}
                                </option>
                            @endforeach
                        </select>
                        @error('id_proyek')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label for="keterangan" class="form-label">Keterangan</label>
                    <textarea class="form-control @error('keterangan') is-invalid @enderror" id="keterangan"
                        name="keterangan" rows="3"
                        placeholder="Keterangan transaksi...">{{ old('keterangan', $jurnalKas->keterangan ?? '') }}</textarea>
                    @error('keterangan')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="alert alert-info d-flex align-items-center">
                    <span data-feather="info" class="me-2"></span>
                    <div>
                        <strong>Info:</strong> Transaksi akan otomatis diposting ke Jurnal Umum.
                        <ul class="mb-0 mt-1">
                            @if($tipe == 'Masuk')
                                <li><strong>Debit:</strong> Akun Kas/Bank</li>
                                <li><strong>Kredit:</strong> Akun Lawan</li>
                            @else
                                <li><strong>Debit:</strong> Akun Lawan</li>
                                <li><strong>Kredit:</strong> Akun Kas/Bank</li>
                            @endif
                        </ul>
                    </div>
                </div>

                <hr class="my-4">

                <div class="d-flex justify-content-between">
                    <a href="{{ route('jurnal-kas.index') }}" class="btn btn-outline-secondary">
                        <span data-feather="arrow-left"></span> Kembali
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <span data-feather="save"></span> {{ isset($jurnalKas) ? 'Simpan Perubahan' : 'Simpan Transaksi' }}
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