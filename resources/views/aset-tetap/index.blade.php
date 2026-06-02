@extends('layouts.app')

@section('title', 'Daftar Aset Tetap - Simple Akunting')

@section('content')
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Master Aset Tetap</h1>
        <div class="btn-toolbar mb-2 mb-md-0 gap-2">
            <a href="{{ route('aset-tetap.depresiasi') }}" class="btn btn-sm btn-outline-secondary">
                <span data-feather="clock"></span> Riwayat & Jalankan Depresiasi
            </a>
            @if(auth()->user()->role === 'superuser' || auth()->user()->role === 'admin')
                <a href="{{ route('aset-tetap.create') }}" class="btn btn-sm btn-primary">
                    <span data-feather="plus"></span> Tambah Aset Tetap
                </a>
            @endif
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Kode Aset</th>
                            <th>Nama Aset</th>
                            <th>Tgl Perolehan</th>
                            <th class="text-end">Harga Perolehan</th>
                            <th class="text-end">Nilai Residu</th>
                            <th class="text-center">Umur (Bulan)</th>
                            <th class="text-end">Akm. Depresiasi</th>
                            <th class="text-end">Nilai Buku</th>
                            <th class="text-center">Status</th>
                            @if(auth()->user()->role === 'superuser' || auth()->user()->role === 'admin')
                                <th class="text-center">Aksi</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($assets as $asset)
                            <tr>
                                <td class="fw-bold text-secondary">{{ $asset->kode_aset }}</td>
                                <td>
                                    <div>{{ $asset->nama_aset }}</div>
                                    <small class="text-muted d-block" style="font-size: 0.75rem;">
                                        Aset: {{ $asset->kode_akun_aset }} | Akum: {{ $asset->kode_akun_akumulasi }} | Beban: {{ $asset->kode_akun_beban }}
                                    </small>
                                </td>
                                <td>{{ date('d-m-Y', strtotime($asset->tanggal_perolehan)) }}</td>
                                <td class="text-end font-monospace">Rp {{ number_format($asset->harga_perolehan, 2, ',', '.') }}</td>
                                <td class="text-end font-monospace text-muted">Rp {{ number_format($asset->nilai_residu, 2, ',', '.') }}</td>
                                <td class="text-center">{{ $asset->umur_ekonomis }}</td>
                                <td class="text-end font-monospace text-danger">Rp {{ number_format($asset->total_akumulasi, 2, ',', '.') }}</td>
                                <td class="text-end font-monospace fw-bold text-success">Rp {{ number_format($asset->nilai_buku, 2, ',', '.') }}</td>
                                <td class="text-center">
                                    @if ($asset->status === 'Aktif')
                                        <span class="badge bg-success">Aktif</span>
                                    @elseif ($asset->status === 'Habis')
                                        <span class="badge bg-warning text-dark">Habis</span>
                                    @else
                                        <span class="badge bg-secondary">Terjual</span>
                                    @endif
                                </td>
                                @if(auth()->user()->role === 'superuser' || auth()->user()->role === 'admin')
                                    <td class="text-center">
                                        <div class="d-inline-flex gap-1">
                                            <a href="{{ route('aset-tetap.edit', $asset->id) }}" class="btn btn-sm btn-outline-warning" title="Edit Aset">
                                                <span data-feather="edit-2" style="width: 14px; height: 14px;"></span>
                                            </a>
                                            <form action="{{ route('aset-tetap.destroy', $asset->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus aset ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus Aset">
                                                    <span data-feather="trash-2" style="width: 14px; height: 14px;"></span>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-4 text-muted">Belum ada data aset tetap yang terdaftar.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
