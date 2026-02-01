<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="SimpleAkunting - Solution for modern accounting. Manage your finances with ease.">
    
    <title>{{ config('app.name', 'SimpleAkunting') }} - Partner Access Portal</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Lucide Icons (CDN for icons) -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        :root {
            --color-bg-dark: #020617;
            --color-surface-dark: #0f172a;
            --color-accent: #f97316; /* Orange from reference */
            --color-accent-hover: #ea580c;
            --color-text: #f8fafc;
            --color-text-muted: #94a3b8;
            --font-family: 'Inter', -apple-system, sans-serif;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: var(--font-family);
            background-color: var(--color-bg-dark);
            color: var(--color-text);
            min-height: 100vh;
            overflow-x: hidden;
        }
        
        .main-container {
            display: flex;
            min-height: 100vh;
        }
        
        /* Left Panel - Hero */
        .hero-panel {
            flex: 1;
            position: relative;
            background: linear-gradient(rgba(2, 6, 23, 0.8), rgba(2, 6, 23, 0.9)), 
                        url('https://images.unsplash.com/photo-1554224155-6726b3ff858f?auto=format&fit=crop&q=80&w=2022&ixlib=rb-4.0.3');
            background-size: cover;
            background-position: center;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 3rem;
            border-right: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .hero-panel::after {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at 30% 50%, rgba(59, 130, 246, 0.15) 0%, transparent 60%);
            pointer-events: none;
        }
        
        .logo-container {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            z-index: 2;
        }
        
        .logo-box {
            width: 40px;
            height: 40px;
            background-color: var(--color-accent);
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.5rem;
        }
        
        .logo-text {
            font-size: 1.25rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            text-transform: uppercase;
        }
        
        .hero-content {
            max-width: 500px;
            z-index: 2;
            margin-top: auto;
            margin-bottom: auto;
        }
        
        .hero-title {
            font-size: 3.5rem;
            font-weight: 700;
            line-height: 1.1;
            margin-bottom: 1.5rem;
            letter-spacing: -0.02em;
        }
        
        .hero-description {
            font-size: 1rem;
            color: var(--color-text-muted);
            line-height: 1.6;
        }
        
        .hero-stats {
            display: flex;
            gap: 3rem;
            z-index: 2;
            margin-top: 2rem;
        }
        
        .stat-item {
            display: flex;
            flex-direction: column;
        }
        
        .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--color-accent);
        }
        
        .stat-label {
            font-size: 0.8rem;
            color: var(--color-text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        /* Right Panel - Login */
        .login-panel {
            width: 450px;
            background-color: var(--color-bg-dark);
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 4rem;
        }
        
        .back-to-home {
            position: absolute;
            top: 2rem;
            right: 4rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            color: var(--color-text-muted);
            font-size: 0.85rem;
            transition: color 0.2s;
        }
        
        .back-to-home:hover {
            color: var(--color-text);
        }
        
        .form-header {
            margin-bottom: 2.5rem;
        }
        
        .form-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .form-subtitle {
            font-size: 0.95rem;
            color: var(--color-text-muted);
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            text-transform: uppercase;
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 0.1em;
            color: var(--color-text-muted);
            margin-bottom: 0.5rem;
        }
        
        .input-wrapper {
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--color-text-muted);
            width: 18px;
            height: 18px;
        }
        
        .form-input {
            width: 100%;
            background-color: var(--color-surface-dark);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 6px;
            padding: 0.85rem 1rem 0.85rem 3rem;
            color: white;
            font-size: 0.95rem;
            outline: none;
            transition: border-color 0.2s;
        }
        
        .form-input:focus {
            border-color: var(--color-accent);
        }
        
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            font-size: 0.85rem;
        }
        
        .remember-me {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            color: var(--color-text-muted);
        }
        
        .remember-me input {
            accent-color: var(--color-accent);
        }
        
        .forgot-password {
            color: var(--color-accent);
            text-decoration: none;
            font-weight: 500;
        }
        
        .btn-submit {
            width: 100%;
            background-color: var(--color-accent);
            color: white;
            border: none;
            border-radius: 6px;
            padding: 1rem;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 4px 15px rgba(249, 115, 22, 0.2);
        }
        
        .btn-submit:hover {
            background-color: var(--color-accent-hover);
            transform: translateY(-1px);
        }
        
        .form-footer {
            margin-top: 2rem;
            text-align: center;
            font-size: 0.85rem;
            color: var(--color-text-muted);
        }
        
        .form-footer a {
            color: var(--color-accent);
            text-decoration: none;
            font-weight: 500;
        }
        
        .error-message {
            background-color: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            padding: 0.75rem;
            border-radius: 6px;
            font-size: 0.85rem;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .hero-panel {
                padding: 2rem;
            }
            .hero-title {
                font-size: 2.5rem;
            }
            .login-panel {
                width: 400px;
                padding: 2rem;
            }
        }
        
        @media (max-width: 768px) {
            .main-container {
                flex-direction: column;
            }
            .hero-panel {
                min-height: 40vh;
                padding: 2rem;
            }
            .login-panel {
                width: 100%;
                padding: 3rem 2rem;
            }
            .hero-stats {
                gap: 2rem;
            }
            .stat-value {
                font-size: 1.25rem;
            }
            .back-to-home {
                right: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <!-- Left Panel: Hero Section -->
        <div class="hero-panel">
            <div class="logo-container">
                <div class="logo-box">S</div>
                <span class="logo-text">SimpleAkunting</span>
            </div>
            
            <div class="hero-content">
                <h1 class="hero-title">Partner Access Portal</h1>
                <p class="hero-description">
                    Monitor progress keuangan Anda, kelola tagihan, dan pantau arus kas dalam satu dashboard industrial yang terpusat dan efisien.
                </p>
            </div>
            
            <div class="hero-stats">
                <div class="stat-item">
                    <span class="stat-value">100+</span>
                    <span class="stat-label">Fitur Aktif</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value">24/7</span>
                    <span class="stat-label">Dukungan Sistem</span>
                </div>
            </div>
        </div>

        <!-- Right Panel: Login Form -->
        <div class="login-panel">
            @guest
                <a href="/" class="back-to-home">
                    <i data-lucide="arrow-left" style="width: 16px; height: 16px;"></i>
                    Back to Home
                </a>
                
                <div class="form-header">
                    <h2 class="form-title">Welcome Back</h2>
                    <p class="form-subtitle">Enter your credentials to access the client portal.</p>
                </div>

                @if ($errors->any())
                    <div class="error-message">
                        @foreach ($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <form action="{{ route('login') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label class="form-label">Alamat Email</label>
                        <div class="input-wrapper">
                            <i data-lucide="mail" class="input-icon"></i>
                            <input type="email" name="email" class="form-input" placeholder="nama@perusahaan.com" required value="{{ old('email') }}">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Kata Sandi</label>
                        <div class="input-wrapper">
                            <i data-lucide="lock" class="input-icon"></i>
                            <input type="password" name="password" class="form-input" placeholder="••••••••" required>
                        </div>
                    </div>

                    <div class="form-options">
                        <label class="remember-me">
                            <input type="checkbox" name="remember">
                            Tetap masuk selama 30 hari
                        </label>
                        <a href="#" class="forgot-password">Lupa?</a>
                    </div>

                    <button type="submit" class="btn-submit">Masuk ke Dashboard</button>
                </form>

                <div class="form-footer">
                    Belum punya akun? <a href="{{ route('register') }}">Hubungi Administrasi</a>
                </div>
            @else
                <div class="text-center">
                    <h2 class="form-title">Sesi Aktif</h2>
                    <p class="form-subtitle mb-6">Anda sudah masuk sebagai <strong>{{ auth()->user()->name }}</strong></p>
                    <a href="{{ url('/dashboard') }}" class="btn-submit" style="display: block; text-decoration: none; text-align: center;">Buka Dashboard</a>
                    <form action="{{ route('logout') }}" method="POST" style="margin-top: 1rem;">
                        @csrf
                        <button type="submit" class="btn-submit" style="background-color: #334155;">Keluar</button>
                    </form>
                </div>
            @endguest
        </div>
    </div>

    <script>
        // Initialize Lucide Icons
        lucide.createIcons();
    </script>
</body>
</html>