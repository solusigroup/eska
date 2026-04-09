<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script>
        // Set theme before rendering
        if (localStorage.getItem('theme') === 'dark' || (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark-mode');
        } else {
            document.documentElement.classList.remove('dark-mode');
        }
    </script>
    <title>@yield('title', 'Simple Akunting')</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="{{ asset('css/custom.css') }}" rel="stylesheet">

    <style>
        /* ========================================
           App Layout Styles
        ======================================== */
        body {
            font-family: var(--font-family);
            font-size: var(--font-size-sm);
            background-color: var(--color-bg);
            overflow-x: hidden;
        }

        /* Top Header */
        .app-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 60px;
            background: var(--color-primary);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 var(--space-md);
            z-index: 1000;
            box-shadow: var(--shadow-md);
        }

        .app-brand {
            display: flex;
            align-items: center;
            gap: var(--space-sm);
            color: var(--color-white);
            text-decoration: none;
            font-weight: 600;
            font-size: var(--font-size-lg);
        }

        .app-brand-icon {
            font-size: 24px;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: var(--space-md);
        }

        .btn-sidebar-toggle {
            background: transparent;
            border: none;
            color: var(--color-white);
            padding: var(--space-sm);
            cursor: pointer;
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-sidebar-toggle:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .btn-logout {
            background: rgba(255, 255, 255, 0.1);
            border: none;
            color: var(--color-white);
            padding: var(--space-sm) var(--space-md);
            border-radius: var(--radius-sm);
            font-size: var(--font-size-sm);
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: var(--space-xs);
            transition: all var(--transition-fast);
        }

        .btn-logout:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        /* Sidebar */
        .app-sidebar {
            position: fixed;
            top: 60px;
            left: 0;
            width: 260px;
            height: calc(100vh - 60px);
            background: var(--color-white);
            border-right: 1px solid var(--color-border-light);
            overflow-y: auto;
            transition: transform var(--transition-base);
            z-index: 900;
        }

        .app-sidebar.collapsed {
            transform: translateX(-100%);
        }

        .sidebar-content {
            padding: var(--space-md) 0;
        }

        /* Sidebar Navigation */
        .sidebar-nav {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar-nav-item {
            margin-bottom: 2px;
        }

        .sidebar-nav-link {
            display: flex;
            align-items: center;
            gap: var(--space-sm);
            padding: 10px var(--space-lg);
            color: var(--color-text);
            text-decoration: none;
            font-weight: 500;
            font-size: var(--font-size-sm);
            transition: all var(--transition-fast);
            border-left: 3px solid transparent;
        }

        .sidebar-nav-link:hover {
            background: var(--color-bg);
            color: var(--color-primary);
        }

        .sidebar-nav-link.active {
            background: rgba(26, 42, 62, 0.05);
            color: var(--color-primary);
            border-left-color: var(--color-primary);
        }

        .sidebar-nav-link .feather {
            width: 18px;
            height: 18px;
            color: var(--color-text-muted);
        }

        .sidebar-nav-link:hover .feather,
        .sidebar-nav-link.active .feather {
            color: var(--color-primary);
        }

        /* Sidebar Section Header */
        .sidebar-section {
            margin-top: var(--space-lg);
        }

        .sidebar-section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: var(--space-sm) var(--space-lg);
            color: var(--color-text-muted);
            font-size: var(--font-size-xs);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            cursor: pointer;
            transition: all var(--transition-fast);
        }

        .sidebar-section-header:hover {
            background: var(--color-bg);
        }

        .sidebar-section-header .chevron {
            transition: transform var(--transition-fast);
        }

        .sidebar-section-header[aria-expanded="true"] .chevron {
            transform: rotate(180deg);
        }

        .sidebar-submenu {
            padding-left: var(--space-sm);
        }

        /* Main Content */
        .app-main {
            margin-left: 260px;
            margin-top: 60px;
            min-height: calc(100vh - 60px);
            padding: var(--space-lg);
            transition: margin-left var(--transition-base);
        }

        .app-main.expanded {
            margin-left: 0;
        }

        /* Page Header */
        .page-header {
            margin-bottom: var(--space-lg);
        }

        .page-title {
            font-size: var(--font-size-2xl);
            font-weight: 600;
            color: var(--color-text);
            margin: 0 0 var(--space-xs) 0;
        }

        .page-subtitle {
            color: var(--color-text-muted);
            font-size: var(--font-size-sm);
            margin: 0;
        }

        /* Mobile Bottom Navigation */
        .bottom-nav {
            display: none;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 64px;
            background: var(--color-white);
            border-top: 1px solid var(--color-border-light);
            z-index: 1000;
            padding: var(--space-sm) 0;
        }

        .bottom-nav-list {
            display: flex;
            justify-content: space-around;
            align-items: center;
            list-style: none;
            padding: 0;
            margin: 0;
            height: 100%;
        }

        .bottom-nav-item {
            flex: 1;
        }

        .bottom-nav-link {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            color: var(--color-text-muted);
            text-decoration: none;
            font-size: 10px;
            font-weight: 500;
            padding: var(--space-xs);
        }

        .bottom-nav-link .feather {
            width: 22px;
            height: 22px;
        }

        .bottom-nav-link.active,
        .bottom-nav-link:hover {
            color: var(--color-primary);
        }

        /* Alerts */
        .app-alert {
            padding: var(--space-md);
            border-radius: var(--radius-md);
            margin-bottom: var(--space-md);
            display: flex;
            align-items: center;
            gap: var(--space-sm);
        }

        .app-alert-success {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.2);
            color: var(--color-success);
        }

        .app-alert-danger {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: var(--color-danger);
        }

        .app-alert-close {
            margin-left: auto;
            background: none;
            border: none;
            color: inherit;
            cursor: pointer;
            padding: 0;
        }

        /* Responsive */
        @media (max-width: 991.98px) {
            .app-sidebar {
                transform: translateX(-100%);
            }

            .app-sidebar.show {
                transform: translateX(0);
            }

            .app-main {
                margin-left: 0;
            }

            .bottom-nav {
                display: block;
            }

            .app-main {
                padding-bottom: 80px;
            }
        }

        /* Override Bootstrap feather icons size */
        .feather {
            width: 18px;
            height: 18px;
            vertical-align: text-bottom;
        }
    </style>
</head>

<body>
    <!-- Header -->
    <header class="app-header">
        <div class="d-flex align-items-center gap-3">
            <button class="btn-sidebar-toggle" id="sidebarToggle" aria-label="Toggle Sidebar">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="3" y1="12" x2="21" y2="12"></line>
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <line x1="3" y1="18" x2="21" y2="18"></line>
                </svg>
            </button>
            <a href="{{ route('dashboard') }}" class="app-brand">
                <span class="app-brand-icon">📊</span>
                <span class="d-none d-sm-inline">Simple Akunting</span>
            </a>
        </div>
        <div class="header-actions">
            <button id="themeToggleBtn" class="btn-logout me-2" type="button" aria-label="Toggle Theme" title="Ganti Tema" style="padding: 6px 10px; background: rgba(255, 255, 255, 0.1);">
                <span data-feather="moon" id="themeIcon"></span>
            </button>
            <span class="text-white d-none d-md-inline" style="opacity: 0.8; font-size: 0.875rem;">
                {{ Auth::user()->nama_user }}
            </span>
            <form method="POST" action="{{ route('logout') }}" class="d-inline">
                @csrf
                <button type="submit" class="btn-logout">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                    <span class="d-none d-sm-inline">Keluar</span>
                </button>
            </form>
        </div>
    </header>

    <!-- Sidebar -->
    <aside class="app-sidebar" id="sidebarMenu">
        <div class="sidebar-content">
            <ul class="sidebar-nav">
                <li class="sidebar-nav-item">
                    <a class="sidebar-nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                        href="{{ route('dashboard') }}">
                        <span data-feather="home"></span>
                        Dashboard
                    </a>
                </li>
            </ul>

            <!-- Master Data -->
            @if(auth()->user()->canViewMasterData())
                @php
                    $isMasterActive = request()->routeIs('pelanggan.*') || request()->routeIs('pemasok.*') || request()->routeIs('persediaan.*') || request()->routeIs('akun.*');
                @endphp
                <div class="sidebar-section">
                    <div class="sidebar-section-header" data-bs-toggle="collapse" data-bs-target="#masterDataMenu"
                        aria-expanded="{{ $isMasterActive ? 'true' : 'false' }}">
                        <span>Master Data</span>
                        <span data-feather="chevron-down" class="chevron"></span>
                    </div>
                    <div class="collapse {{ $isMasterActive ? 'show' : '' }}" id="masterDataMenu">
                        <ul class="sidebar-nav sidebar-submenu">
                            <li class="sidebar-nav-item">
                                <a class="sidebar-nav-link {{ request()->routeIs('pelanggan.*') ? 'active' : '' }}"
                                    href="{{ route('pelanggan.index') }}">
                                    <span data-feather="users"></span>
                                    Pelanggan
                                </a>
                            </li>
                            <li class="sidebar-nav-item">
                                <a class="sidebar-nav-link {{ request()->routeIs('pemasok.*') ? 'active' : '' }}"
                                    href="{{ route('pemasok.index') }}">
                                    <span data-feather="truck"></span>
                                    Pemasok
                                </a>
                            </li>
                            <li class="sidebar-nav-item">
                                <a class="sidebar-nav-link {{ request()->routeIs('persediaan.*') ? 'active' : '' }}"
                                    href="{{ route('persediaan.index') }}">
                                    <span data-feather="box"></span>
                                    Persediaan
                                </a>
                            </li>
                            <li class="sidebar-nav-item">
                                <a class="sidebar-nav-link {{ request()->routeIs('akun.*') ? 'active' : '' }}"
                                    href="{{ route('akun.index') }}">
                                    <span data-feather="list"></span>
                                    Akun (COA)
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            @endif

            <!-- Transaksi -->
            @php
                $isTransaksiActive = request()->routeIs('penjualan.*') || request()->routeIs('pembelian.*') || request()->routeIs('jurnal.*');
            @endphp
            <div class="sidebar-section">
                <div class="sidebar-section-header" data-bs-toggle="collapse" data-bs-target="#transaksiMenu"
                    aria-expanded="{{ $isTransaksiActive ? 'true' : 'false' }}">
                    <span>Transaksi</span>
                    <span data-feather="chevron-down" class="chevron"></span>
                </div>
                <div class="collapse {{ $isTransaksiActive ? 'show' : '' }}" id="transaksiMenu">
                    <ul class="sidebar-nav sidebar-submenu">
                        <li class="sidebar-nav-item">
                            <a class="sidebar-nav-link {{ request()->routeIs('penjualan.*') ? 'active' : '' }}"
                                href="{{ route('penjualan.index') }}">
                                <span data-feather="shopping-cart"></span>
                                Penjualan
                            </a>
                        </li>
                        <li class="sidebar-nav-item">
                            <a class="sidebar-nav-link {{ request()->routeIs('pembelian.*') ? 'active' : '' }}"
                                href="{{ route('pembelian.index') }}">
                                <span data-feather="shopping-bag"></span>
                                Pembelian
                            </a>
                        </li>
                        <li class="sidebar-nav-item">
                            <a class="sidebar-nav-link {{ request()->routeIs('jurnal.*') ? 'active' : '' }}"
                                href="{{ route('jurnal.index') }}">
                                <span data-feather="file-text"></span>
                                Jurnal Umum
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Kas & Bank -->
            @php
                $isKasActive = request()->routeIs('penerimaan.*') || request()->routeIs('pembayaran.*') || request()->routeIs('kas.*') || request()->routeIs('jurnal-kas.*');
            @endphp
            <div class="sidebar-section">
                <div class="sidebar-section-header" data-bs-toggle="collapse" data-bs-target="#kasMenu"
                    aria-expanded="{{ $isKasActive ? 'true' : 'false' }}">
                    <span>Kas & Bank</span>
                    <span data-feather="chevron-down" class="chevron"></span>
                </div>
                <div class="collapse {{ $isKasActive ? 'show' : '' }}" id="kasMenu">
                    <ul class="sidebar-nav sidebar-submenu">
                        <li class="sidebar-nav-item">
                            <a class="sidebar-nav-link {{ request()->routeIs('penerimaan.*') ? 'active' : '' }}"
                                href="{{ route('penerimaan.index') }}">
                                <span data-feather="arrow-down-circle"></span>
                                Penerimaan
                            </a>
                        </li>
                        <li class="sidebar-nav-item">
                            <a class="sidebar-nav-link {{ request()->routeIs('pembayaran.*') ? 'active' : '' }}"
                                href="{{ route('pembayaran.index') }}">
                                <span data-feather="arrow-up-circle"></span>
                                Pembayaran
                            </a>
                        </li>
                        <li class="sidebar-nav-item">
                            <a class="sidebar-nav-link {{ request()->routeIs('jurnal-kas.*') ? 'active' : '' }}"
                                href="{{ route('jurnal-kas.index') }}">
                                <span data-feather="book-open"></span>
                                Jurnal Kas
                            </a>
                        </li>
                        <li class="sidebar-nav-item">
                            <a class="sidebar-nav-link {{ request()->routeIs('kas.*') ? 'active' : '' }}"
                                href="{{ route('kas.index') }}">
                                <span data-feather="dollar-sign"></span>
                                Transaksi Kas
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Proyek Management -->
            @php
                $isProyekActive = request()->routeIs('proyek.*');
            @endphp
            <div class="sidebar-section">
                <div class="sidebar-section-header" data-bs-toggle="collapse" data-bs-target="#proyekMenu"
                    aria-expanded="{{ $isProyekActive ? 'true' : 'false' }}">
                    <span>📁 Proyek</span>
                    <span data-feather="chevron-down" class="chevron"></span>
                </div>
                <div class="collapse {{ $isProyekActive ? 'show' : '' }}" id="proyekMenu">
                    <ul class="sidebar-nav sidebar-submenu">
                        <li class="sidebar-nav-item">
                            <a class="sidebar-nav-link {{ request()->routeIs('proyek.index') || request()->routeIs('proyek.show') ? 'active' : '' }}"
                                href="{{ route('proyek.index') }}">
                                <span data-feather="folder"></span>
                                Daftar Proyek
                            </a>
                        </li>
                        <li class="sidebar-nav-item">
                            <a class="sidebar-nav-link {{ request()->routeIs('proyek.create') ? 'active' : '' }}"
                                href="{{ route('proyek.create') }}">
                                <span data-feather="plus-circle"></span>
                                Tambah Proyek
                            </a>
                        </li>
                        <li class="sidebar-nav-item">
                            <a class="sidebar-nav-link {{ request()->routeIs('proyek.assign') ? 'active' : '' }}"
                                href="{{ route('proyek.assign') }}">
                                <span data-feather="link"></span>
                                Assign Transaksi
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Laporan -->
            @if(auth()->user()->canViewReports())
                @php
                    $isLaporanActive = request()->routeIs('bukubesar.*') || request()->routeIs('laporan.*');
                @endphp
                <div class="sidebar-section">
                    <div class="sidebar-section-header" data-bs-toggle="collapse" data-bs-target="#laporanMenu"
                        aria-expanded="{{ $isLaporanActive ? 'true' : 'false' }}">
                        <span>Laporan</span>
                        <span data-feather="chevron-down" class="chevron"></span>
                    </div>
                    <div class="collapse {{ $isLaporanActive ? 'show' : '' }}" id="laporanMenu">
                        <ul class="sidebar-nav sidebar-submenu">
                            <li class="sidebar-nav-item">
                                <a class="sidebar-nav-link {{ request()->routeIs('bukubesar.*') ? 'active' : '' }}"
                                    href="{{ route('bukubesar.index') }}">
                                    <span data-feather="book"></span>
                                    Buku Besar
                                </a>
                            </li>
                            <li class="sidebar-nav-item">
                                <a class="sidebar-nav-link {{ request()->routeIs('laporan.neraca') ? 'active' : '' }}"
                                    href="{{ route('laporan.neraca') }}">
                                    <span data-feather="bar-chart-2"></span>
                                    Neraca
                                </a>
                            </li>
                            <li class="sidebar-nav-item">
                                <a class="sidebar-nav-link {{ request()->routeIs('laporan.labarugi') ? 'active' : '' }}"
                                    href="{{ route('laporan.labarugi') }}">
                                    <span data-feather="trending-up"></span>
                                    Laba Rugi
                                </a>
                            </li>
                            <li class="sidebar-nav-item">
                                <a class="sidebar-nav-link {{ request()->routeIs('laporan.labarugi_proyek') ? 'active' : '' }}"
                                    href="{{ route('laporan.labarugi_proyek') }}">
                                    <span data-feather="folder"></span>
                                    L/R Per Proyek
                                </a>
                            </li>
                            <li class="sidebar-nav-item">
                                <a class="sidebar-nav-link {{ request()->routeIs('laporan.labarugi_konsolidasi') ? 'active' : '' }}"
                                    href="{{ route('laporan.labarugi_konsolidasi') }}">
                                    <span data-feather="layers"></span>
                                    L/R Konsolidasi
                                </a>
                            </li>
                            <li class="sidebar-nav-item">
                                <a class="sidebar-nav-link {{ request()->routeIs('laporan.aruskas_langsung') ? 'active' : '' }}"
                                    href="{{ route('laporan.aruskas_langsung') }}">
                                    <span data-feather="activity"></span>
                                    Arus Kas (Langsung)
                                </a>
                            </li>
                            <li class="sidebar-nav-item">
                                <a class="sidebar-nav-link {{ request()->routeIs('laporan.aruskas_tidak_langsung') ? 'active' : '' }}"
                                    href="{{ route('laporan.aruskas_tidak_langsung') }}">
                                    <span data-feather="activity"></span>
                                    Arus Kas (Tidak Langsung)
                                </a>
                            </li>
                            <li class="sidebar-nav-item">
                                <a class="sidebar-nav-link {{ request()->routeIs('laporan.aruskas_proyek') ? 'active' : '' }}"
                                    href="{{ route('laporan.aruskas_proyek') }}">
                                    <span data-feather="folder"></span>
                                    Arus Kas Per Proyek
                                </a>
                            </li>
                            <li class="sidebar-nav-item">
                                <a class="sidebar-nav-link {{ request()->routeIs('laporan.aruskas_konsolidasi') ? 'active' : '' }}"
                                    href="{{ route('laporan.aruskas_konsolidasi') }}">
                                    <span data-feather="layers"></span>
                                    Arus Kas Konsolidasi
                                </a>
                            </li>
                            <li class="sidebar-nav-item">
                                <a class="sidebar-nav-link {{ request()->routeIs('laporan.perubahan_ekuitas') ? 'active' : '' }}"
                                    href="{{ route('laporan.perubahan_ekuitas') }}">
                                    <span data-feather="pie-chart"></span>
                                    Perubahan Ekuitas
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            @endif

            <!-- Import/Export -->
            @if(auth()->user()->canImportExport())
                <ul class="sidebar-nav mt-3">
                    <li class="sidebar-nav-item">
                        <a class="sidebar-nav-link {{ request()->routeIs('import-export.*') ? 'active' : '' }}"
                            href="{{ route('import-export.index') }}">
                            <span data-feather="upload-cloud"></span>
                            Import/Export
                        </a>
                    </li>
                    <li class="sidebar-nav-item">
                        <a class="sidebar-nav-link {{ request()->routeIs('import-kas.*') ? 'active' : '' }}"
                            href="{{ route('import-kas.index') }}">
                            <span data-feather="download"></span>
                            Import Kas
                        </a>
                    </li>
                </ul>
            @endif

            <!-- Admin -->
            @if(auth()->user()->canManageUsers())
                @php
                    $isAdminActive = request()->routeIs('perusahaan.*') || request()->routeIs('users.*') || request()->routeIs('database.*');
                @endphp
                <div class="sidebar-section">
                    <div class="sidebar-section-header" data-bs-toggle="collapse" data-bs-target="#adminMenu"
                        aria-expanded="{{ $isAdminActive ? 'true' : 'false' }}">
                        <span>⚙️ Admin</span>
                        <span data-feather="chevron-down" class="chevron"></span>
                    </div>
                    <div class="collapse {{ $isAdminActive ? 'show' : '' }}" id="adminMenu">
                        <ul class="sidebar-nav sidebar-submenu">
                            @if(auth()->user()->canManageCompany())
                                <li class="sidebar-nav-item">
                                    <a class="sidebar-nav-link {{ request()->routeIs('perusahaan.*') ? 'active' : '' }}"
                                        href="{{ route('perusahaan.edit') }}">
                                        <span data-feather="settings"></span>
                                        Profil Perusahaan
                                    </a>
                                </li>
                            @endif
                            <li class="sidebar-nav-item">
                                <a class="sidebar-nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}"
                                    href="{{ route('users.index') }}">
                                    <span data-feather="users"></span>
                                    Manajemen User
                                </a>
                            </li>
                            @if(auth()->user()->canAccessDatabase())
                                <li class="sidebar-nav-item">
                                    <a class="sidebar-nav-link {{ request()->routeIs('database.*') ? 'active' : '' }}"
                                        href="{{ route('database.index') }}">
                                        <span data-feather="database"></span>
                                        Manajemen Database
                                    </a>
                                </li>
                            @endif
                        </ul>
                    </div>
                </div>
            @endif

            <!-- Panduan Pengguna -->
            <ul class="sidebar-nav mt-3" style="border-top: 1px solid var(--color-border-light); padding-top: 15px;">
                <li class="sidebar-nav-item">
                    <a class="sidebar-nav-link" href="{{ asset('docs/panduan-pengguna.html') }}" target="_blank">
                        <span data-feather="help-circle"></span>
                        Panduan Pengguna
                    </a>
                </li>
            </ul>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="app-main" id="mainContent">
        @if(session('success'))
            <div class="app-alert app-alert-success">
                <span data-feather="check-circle"></span>
                {{ session('success') }}
                <button type="button" class="app-alert-close" onclick="this.parentElement.remove()">
                    <span data-feather="x"></span>
                </button>
            </div>
        @endif

        @if(session('error'))
            <div class="app-alert app-alert-danger">
                <span data-feather="alert-circle"></span>
                {{ session('error') }}
                <button type="button" class="app-alert-close" onclick="this.parentElement.remove()">
                    <span data-feather="x"></span>
                </button>
            </div>
        @endif

        @yield('content')
    </main>

    <!-- Mobile Bottom Navigation -->
    <nav class="bottom-nav">
        <ul class="bottom-nav-list">
            <li class="bottom-nav-item">
                <a href="{{ route('dashboard') }}"
                    class="bottom-nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <span data-feather="home"></span>
                    Beranda
                </a>
            </li>
            <li class="bottom-nav-item">
                <a href="{{ route('jurnal.index') }}"
                    class="bottom-nav-link {{ request()->routeIs('jurnal.*') || request()->routeIs('penjualan.*') || request()->routeIs('pembelian.*') ? 'active' : '' }}">
                    <span data-feather="list"></span>
                    Transaksi
                </a>
            </li>
            <li class="bottom-nav-item">
                <a href="{{ route('laporan.neraca') }}"
                    class="bottom-nav-link {{ request()->routeIs('laporan.*') || request()->routeIs('bukubesar.*') ? 'active' : '' }}">
                    <span data-feather="bar-chart-2"></span>
                    Laporan
                </a>
            </li>
            <li class="bottom-nav-item">
                <a href="{{ route('users.index') }}"
                    class="bottom-nav-link {{ request()->routeIs('users.*') || request()->routeIs('perusahaan.*') ? 'active' : '' }}">
                    <span data-feather="user"></span>
                    Akun
                </a>
            </li>
        </ul>
    </nav>

    <!-- Sidebar Overlay (Mobile) -->
    <div class="sidebar-overlay" id="sidebarOverlay"
        style="display: none; position: fixed; top: 60px; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 800;">
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/feather-icons@4.28.0/dist/feather.min.js"></script>
    <script>
        (function () {
            'use strict'

            // Theme Toggle Logic
            const themeToggleBtn = document.getElementById('themeToggleBtn');
            const themeIcon = document.getElementById('themeIcon');
            const isDarkMode = document.documentElement.classList.contains('dark-mode');
            
            if (themeIcon) {
                themeIcon.setAttribute('data-feather', isDarkMode ? 'sun' : 'moon');
            }

            if (themeToggleBtn) {
                themeToggleBtn.addEventListener('click', () => {
                    document.documentElement.classList.toggle('dark-mode');
                    const isDarkNow = document.documentElement.classList.contains('dark-mode');
                    
                    localStorage.setItem('theme', isDarkNow ? 'dark' : 'light');
                    
                    if (themeIcon) {
                        themeIcon.setAttribute('data-feather', isDarkNow ? 'sun' : 'moon');
                        feather.replace();
                    }
                });
            }

            // Initialize Feather Icons
            feather.replace({ 'aria-hidden': 'true' });

            // Sidebar Toggle
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebar = document.getElementById('sidebarMenu');
            const main = document.getElementById('mainContent');
            const overlay = document.getElementById('sidebarOverlay');

            function isMobile() {
                return window.innerWidth < 992;
            }

            sidebarToggle.addEventListener('click', function () {
                if (isMobile()) {
                    sidebar.classList.toggle('show');
                    overlay.style.display = sidebar.classList.contains('show') ? 'block' : 'none';
                } else {
                    sidebar.classList.toggle('collapsed');
                    main.classList.toggle('expanded');
                }
            });

            overlay.addEventListener('click', function () {
                sidebar.classList.remove('show');
                overlay.style.display = 'none';
            });

            // Handle resize
            window.addEventListener('resize', function () {
                if (!isMobile()) {
                    sidebar.classList.remove('show');
                    overlay.style.display = 'none';
                }
            });

            // Slide Panel Functions (Global)
            window.openSlidePanel = function (panelId) {
                document.getElementById(panelId).classList.add('show');
                document.getElementById(panelId + 'Overlay').classList.add('show');
                document.body.style.overflow = 'hidden';
            };

            window.closeSlidePanel = function (panelId) {
                document.getElementById(panelId).classList.remove('show');
                document.getElementById(panelId + 'Overlay').classList.remove('show');
                document.body.style.overflow = '';
            };

            // Close slide panel on Escape key
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    document.querySelectorAll('.slide-panel.show').forEach(function (panel) {
                        closeSlidePanel(panel.id);
                    });
                }
            });
        })()
    </script>
    @stack('scripts')
</body>

</html>