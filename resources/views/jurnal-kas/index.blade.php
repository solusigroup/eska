@extends('layouts.app')

@section('title', 'Jurnal Kas')

@section('content')
    <div class="page-header d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-title">Jurnal Kas</h1>
            <p class="page-subtitle">Transaksi kas masuk dan kas keluar dengan posting otomatis ke jurnal umum</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('jurnal-kas.create', ['tipe' => 'Masuk']) }}" class="btn btn-success">
                <span data-feather="arrow-down-circle"></span> Kas Masuk
            </a>
            <a href="{{ route('jurnal-kas.create', ['tipe' => 'Keluar']) }}" class="btn btn-danger">
                <span data-feather="arrow-up-circle"></span> Kas Keluar
            </a>
        </div>
    </div>

    <!-- Filter -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('jurnal-kas.index') }}" class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label for="tipe" class="form-label">Tipe</label>
                    <select class="form-select" id="tipe" name="tipe">
                        <option value="">Semua</option>
                        <option value="Masuk" {{ request('tipe') == 'Masuk' ? 'selected' : '' }}>Kas Masuk</option>
                        <option value="Keluar" {{ request('tipe') == 'Keluar' ? 'selected' : '' }}>Kas Keluar</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="id_proyek" class="form-label">Proyek</label>
                    <select class="form-select" id="id_proyek" name="id_proyek">
                        <option value="">Semua Proyek</option>
                        @foreach($proyeks as $p)
                            <option value="{{ $p->id_proyek }}" {{ request('id_proyek') == $p->id_proyek ? 'selected' : '' }}>
                                {{ $p->kode_proyek }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="start_date" class="form-label">Dari Tanggal</label>
                    <input type="date" class="form-control" id="start_date" name="start_date"
                        value="{{ request('start_date') }}">
                </div>
                <div class="col-md-2">
                    <label for="end_date" class="form-label">Sampai Tanggal</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" value="{{ request('end_date') }}">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <span data-feather="search"></span> Filter
                    </button>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('jurnal-kas.index') }}" class="btn btn-outline-secondary w-100">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>No. Bukti</th>
                            <th>Tipe</th>
                            <th>Akun Kas</th>
                            <th>Akun Lawan</th>
                            <th>Proyek</th>
                            <th class="text-end">Jumlah</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($jurnalKas as $jk)
                            <tr>
                                <td>{{ $jk->tanggal->format('d/m/Y') }}</td>
                                <td><code>{{ $jk->no_bukti }}</code></td>
                                <td>
                                    @if($jk->tipe == 'Masuk')
                                        <span class="badge bg-success">Masuk</span>
                                    @else
                                        <span class="badge bg-danger">Keluar</span>
                                    @endif
                                </td>
                                <td>{{ $jk->akun_kas }} - {{ $jk->akunKasRef->nama_akun ?? '-' }}</td>
                                <td>{{ $jk->akun_lawan }} - {{ $jk->akunLawanRef->nama_akun ?? '-' }}</td>
                                <td>
                                    @if($jk->proyek)
                                        <span class="badge bg-primary">{{ $jk->proyek->kode_proyek }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-end fw-medium">Rp {{ number_format($jk->jumlah, 0, ',', '.') }}</td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('jurnal-kas.edit', $jk->id_jurnal_kas) }}"
                                            class="btn btn-outline-secondary" title="Edit">
                                            <span data-feather="edit-2"></span>
                                        </a>
                                        <form action="{{ route('jurnal-kas.destroy', $jk->id_jurnal_kas) }}" method="POST"
                                            class="d-inline"
                                            onsubmit="return confirm('Hapus transaksi ini? Jurnal umum terkait juga akan dihapus!')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger" title="Hapus">
                                                <span data-feather="trash-2"></span>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    <span data-feather="inbox" style="width: 48px; height: 48px; opacity: 0.5"></span>
                                    <p class="mt-2 mb-0">Belum ada transaksi jurnal kas.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            {{ $jurnalKas->appends(request()->query())->links() }}
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        feather.replace({ 'aria-hidden': 'true' });
    </script>
@endpush