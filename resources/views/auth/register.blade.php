<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Setia Kawan</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        :root {
            --color-bg-dark: #020617;
            --color-surface-dark: #0f172a;
            --color-accent: #34d1c4;
            --color-accent-hover: #28a89e;
            --color-text: #f8fafc;
            --color-text-muted: #94a3b8;
            --font-family: 'Inter', -apple-system, sans-serif;
        }

        .back-to-home:hover {
            color: var(--color-accent) !important;
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
            overflow-y: auto;
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
                        url('https://images.unsplash.com/photo-1454165833767-027ffcb940d7?auto=format&fit=crop&q=80&w=2070&ixlib=rb-4.0.3');
            background-size: cover;
            background-position: center;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 4rem;
            border-right: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .hero-panel::after {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at 30% 50%, rgba(59, 130, 246, 0.1) 0%, transparent 60%);
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
            font-size: 1.1rem;
            color: var(--color-text-muted);
            line-height: 1.6;
        }
        
        .hero-footer {
            z-index: 2;
            font-size: 0.85rem;
            color: var(--color-text-muted);
        }

        /* Right Panel - Register */
        .register-panel {
            width: 550px;
            background-color: var(--color-bg-dark);
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 4rem;
            position: relative;
        }
        
        .back-to-home {
            position: absolute;
            top: 2rem;
            left: 4rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            color: var(--color-text-muted);
            font-size: 0.85rem;
            transition: color 0.2s;
        }
        
        .back-to-home:hover {
            color: var(--text-color);
        }
        
        .form-header {
            margin-bottom: 2.5rem;
        }
        
        .form-title {
            font-size: 2.25rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            letter-spacing: -0.02em;
        }
        
        .form-subtitle {
            font-size: 0.95rem;
            color: var(--color-text-muted);
        }
        
        .form-row {
            display: flex;
            gap: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 1.25rem;
            flex: 1;
        }
        
        .form-label {
            display: block;
            text-transform: uppercase;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.05em;
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
        
        .form-input, .form-select {
            width: 100%;
            background-color: var(--color-surface-dark);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            padding: 0.85rem 1rem 0.85rem 3rem;
            color: white;
            font-size: 0.95rem;
            outline: none;
            transition: all 0.2s;
        }
        
        .form-select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2394a3b8'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 1.25rem;
        }
        
        .form-input:focus, .form-select:focus {
            border-color: var(--color-accent);
            background-color: rgba(15, 23, 42, 0.8);
            box-shadow: 0 0 0 4px rgba(249, 115, 22, 0.1);
        }
        
        .btn-submit {
            width: 100%;
            background-color: var(--color-accent);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 1rem;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 4px 20px rgba(249, 115, 22, 0.3);
            margin-top: 1rem;
        }
        
        .btn-submit:hover {
            background-color: var(--color-accent-hover);
            transform: translateY(-1px);
        }
        
        .form-footer {
            margin-top: 2rem;
            text-align: center;
            font-size: 0.9rem;
            color: var(--color-text-muted);
        }
        
        .form-footer a {
            color: var(--color-accent);
            text-decoration: none;
            font-weight: 600;
        }
        
        .alert {
            background-color: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            padding: 0.85rem;
            border-radius: 8px;
            font-size: 0.85rem;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--color-text-muted);
            cursor: pointer;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.2s;
            z-index: 10;
        }

        .password-toggle:hover {
            color: var(--color-accent);
        }

        .toggle-icon {
            width: 18px;
            height: 18px;
        }

        @media (max-width: 1200px) {
            .hero-panel { display: none; }
            .register-panel { width: 100%; max-width: 600px; margin: 0 auto; }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <!-- Left Panel -->
        <div class="hero-panel">
            <div class="logo-container">
                <img src="{{ asset('assets/images/logo.png') }}" alt="Setia Kawan Logo" style="height: 60px; object-fit: contain;">
            </div>
            
            <div class="hero-content">
                <h1 class="hero-title">Join the Future of Finance</h1>
                <p class="hero-description">
                    Mulai perjalanan digital akuntansi Anda hari ini dengan sistem yang dirancang untuk kecepatan dan akurasi maksimal.
                </p>
            </div>
            
            <div class="hero-footer">
                <p>Developed by Kurniawan</p>
            </div>
        </div>

        <!-- Right Panel -->
        <div class="register-panel">
            <a href="/" class="back-to-home">
                <i data-lucide="arrow-left" style="width: 16px; height: 16px;"></i>
                Back to Landing Page
            </a>

            <div class="form-header">
                <h2 class="form-title">Create Account</h2>
                <p class="form-subtitle">Daftarkan diri Anda untuk mulai menggunakan sistem.</p>
            </div>

            @if ($errors->any())
                <div class="alert">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('register') }}">
                @csrf
                <div class="form-group">
                    <label class="form-label">Username</label>
                    <div class="input-wrapper">
                        <i data-lucide="user" class="input-icon"></i>
                        <input type="text" name="nama_user" class="form-input" placeholder="Masukkan username" value="{{ old('nama_user') }}" required autofocus>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <div class="input-wrapper">
                            <i data-lucide="lock" class="input-icon"></i>
                            <input type="password" id="register-password" name="password" class="form-input" placeholder="••••••••" required>
                            <button type="button" class="password-toggle" onclick="togglePassword('register-password', this)">
                                <i data-lucide="eye" class="toggle-icon"></i>
                            </button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Konfirmasi</label>
                        <div class="input-wrapper">
                            <i data-lucide="shield-check" class="input-icon"></i>
                            <input type="password" id="password_confirmation" name="password_confirmation" class="form-input" placeholder="••••••••" required>
                            <button type="button" class="password-toggle" onclick="togglePassword('password_confirmation', this)">
                                <i data-lucide="eye" class="toggle-icon"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Role</label>
                        <div class="input-wrapper">
                            <i data-lucide="briefcase" class="input-icon"></i>
                            <select name="role" class="form-select" required>
                                <option value="admin">Admin</option>
                                <option value="manajer">Manajer</option>
                                <option value="staff" selected>Staff</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Jabatan</label>
                        <div class="input-wrapper">
                            <i data-lucide="award" class="input-icon"></i>
                            <input type="text" name="jabatan" class="form-input" placeholder="Contoh: Manager Keuangan" value="{{ old('jabatan') }}" required>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn-submit">Daftar Akun Baru</button>
            </form>

            <div class="form-footer">
                Sudah punya akun? <a href="{{ route('login') }}">Masuk di sini</a>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();

        function togglePassword(inputId, button) {
            const input = document.getElementById(inputId);
            const icon = button.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.setAttribute('data-lucide', 'eye-off');
            } else {
                input.type = 'password';
                icon.setAttribute('data-lucide', 'eye');
            }
            
            lucide.createIcons();
        }
    </script>
</body>
</html>
