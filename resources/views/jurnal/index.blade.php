@extends('layouts.app')

@section('title', 'Jurnal Umum - Simple Akunting')

@section('content')
    <!-- Page Header -->
    <div class="page-header-actions">
        <div>
            <h1 class="page-title">Jurnal Umum</h1>
            <p class="page-subtitle">Daftar semua transaksi jurnal</p>
        </div>
        <div>
            <a href="{{ route('jurnal.create') }}" class="btn btn-primary btn-sm">
                <span data-feather="plus" style="width: 16px; height: 16px; margin-right: 4px;"></span>
                Buat Jurnal Manual
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Table Card -->
    <div class="table-card">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>No Transaksi</th>
                        <th>Deskripsi</th>
                        <th>Sumber</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($jurnal as $j)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($j->tanggal)->format('d/m/Y') }}</td>
                            <td><strong>{{ $j->no_transaksi }}</strong></td>
                            <td>{{ Str::limit($j->deskripsi, 50) }}</td>
                            <td>
                                <span class="badge {{ $j->sumber_jurnal == 'Manual' ? 'badge-secondary' : 'badge-info' }}">
                                    {{ $j->sumber_jurnal }}
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons" style="display: flex; gap: 4px;">
                                    <a href="{{ route('jurnal.show', $j->id_jurnal) }}" class="btn btn-sm btn-primary">
                                        Detail
                                    </a>
                                    @if ($j->sumber_jurnal == 'Manual' && !$j->is_locked)
                                        <a href="{{ route('jurnal.edit', $j->id_jurnal) }}" class="btn btn-sm btn-warning">
                                            Edit
                                        </a>
                                        <form action="{{ route('jurnal.destroy', $j->id_jurnal) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus jurnal ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="table-empty">
                                    <div class="table-empty-icon">📋</div>
                                    <p>Belum ada data jurnal.</p>
                                    <a href="{{ route('jurnal.create') }}" class="btn btn-primary btn-sm">Buat Jurnal Pertama</a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($jurnal->hasPages())
        <div class="pagination-wrapper">
            {{ $jurnal->links() }}
        </div>
        @endif
    </div>
@endsection
