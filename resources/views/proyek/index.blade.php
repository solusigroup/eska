@extends('layouts.app')

@section('title', 'Manajemen Proyek')

@section('content')
    <div class="page-header d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-title">Manajemen Proyek</h1>
            <p class="page-subtitle">Kelola daftar proyek untuk tracking keuangan per proyek</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('proyek.assign') }}" class="btn btn-outline-secondary">
                <span data-feather="link"></span> Assign Transaksi
            </a>
            <a href="{{ route('proyek.create') }}" class="btn btn-primary">
                <span data-feather="plus"></span> Tambah Proyek
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Nama Proyek</th>
                            <th>Status</th>
                            <th>Tanggal Mulai</th>
                            <th>Anggaran</th>
                            <th>Lokasi</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($proyeks as $proyek)
                            <tr>
                                <td><code>{{ $proyek->kode_proyek }}</code></td>
                                <td>
                                    <a href="{{ route('proyek.show', $proyek->id_proyek) }}"
                                        class="text-decoration-none fw-medium">
                                        {{ $proyek->nama_proyek }}
                                    </a>
                                </td>
                                <td>
                                    @if($proyek->status == 'Aktif')
                                        <span class="badge bg-success">Aktif</span>
                                    @elseif($proyek->status == 'Selesai')
                                        <span class="badge bg-secondary">Selesai</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Ditunda</span>
                                    @endif
                                </td>
                                <td>{{ $proyek->tanggal_mulai ? $proyek->tanggal_mulai->format('d/m/Y') : '-' }}</td>
                                <td class="text-end">Rp {{ number_format($proyek->anggaran, 0, ',', '.') }}</td>
                                <td>{{ $proyek->lokasi ?? '-' }}</td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('proyek.show', $proyek->id_proyek) }}" class="btn btn-outline-primary"
                                            title="Lihat Detail">
                                            <span data-feather="eye"></span>
                                        </a>
                                        <a href="{{ route('proyek.edit', $proyek->id_proyek) }}"
                                            class="btn btn-outline-secondary" title="Edit">
                                            <span data-feather="edit-2"></span>
                                        </a>
                                        <form action="{{ route('proyek.destroy', $proyek->id_proyek) }}" method="POST"
                                            class="d-inline" onsubmit="return confirm('Hapus proyek ini?')">
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
                                <td colspan="7" class="text-center text-muted py-4">
                                    <span data-feather="folder" style="width: 48px; height: 48px; opacity: 0.5"></span>
                                    <p class="mt-2 mb-0">Belum ada proyek. Klik "Tambah Proyek" untuk membuat proyek baru.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $proyeks->links() }}
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        feather.replace({ 'aria-hidden': 'true' });
    </script>
@endpush