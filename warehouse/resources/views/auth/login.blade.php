<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Portal Warehouse PT Metalart Astra Indonesia</title>
    <meta name="description" content="Portal Terpadu Warehouse PT Metalart Astra Indonesia - Integrasi MARS & SATURNUS">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400;1,600&family=Outfit:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <style>
        *, *::before, *::after {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-blue: #1e3a8a;
            --primary-blue-hover: #172554;
            --primary-blue-light: #3b82f6;
            --mars-orange: #ea580c;
            --saturnus-purple: #7c3aed;
            --text-dark: #0f2b48;
            --text-title: #0f172a;
            --text-body: #475569;
            --text-muted: #64748b;
            --border-color: rgba(226, 232, 240, 0.85);
            --card-glass-bg: rgba(255, 255, 255, 0.82);
            --card-border: rgba(255, 255, 255, 0.95);
            --card-shadow: 0 25px 60px -15px rgba(15, 43, 72, 0.22), 0 0 0 1px rgba(255, 255, 255, 0.8) inset;
            --font-main: 'Plus Jakarta Sans', sans-serif;
            --font-head: 'Outfit', sans-serif;
        }

        html, body {
            height: 100%;
            width: 100%;
            font-family: var(--font-main);
            color: var(--text-dark);
            background-color: #0f172a;
        }

        body.login-page {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            background-image: url("{{ asset('assets/images/MAI DEPAN.png') }}");
            background-size: cover;
            background-position: center center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            overflow-x: hidden;
        }

        .bg-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.82) 0%, rgba(30, 58, 138, 0.65) 50%, rgba(15, 23, 42, 0.88) 100%);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            z-index: 1;
        }

        .page-content {
            position: relative;
            z-index: 2;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            justify-content: space-between;
            padding: 1.5rem 1rem;
        }

        .login-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            max-width: 1200px;
            width: 100%;
            margin: 0 auto;
            padding: 0.5rem 1rem;
        }

        .logo-container {
            display: flex;
            align-items: center;
            gap: 1rem;
            text-decoration: none;
        }

        .header-logo {
            height: 48px;
            width: auto;
            object-fit: contain;
            filter: drop-shadow(0 4px 6px rgba(0,0,0,0.3));
        }

        .header-title-box {
            display: flex;
            flex-direction: column;
        }

        .header-company-name {
            font-family: var(--font-head);
            font-size: 1.25rem;
            font-weight: 800;
            letter-spacing: -0.01em;
            color: #ffffff;
            line-height: 1.1;
        }

        .header-portal-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: #60a5fa;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .system-pill-group {
            display: flex;
            gap: 0.5rem;
        }

        .system-pill {
            padding: 0.35rem 0.85rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 700;
            color: #ffffff;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(4px);
        }

        .system-pill.mars {
            background: rgba(234, 88, 12, 0.25);
            border-color: rgba(234, 88, 12, 0.5);
            color: #fed7aa;
        }

        .system-pill.saturnus {
            background: rgba(124, 58, 237, 0.25);
            border-color: rgba(124, 58, 237, 0.5);
            color: #e9d5ff;
        }

        .login-main {
            display: flex;
            align-items: center;
            justify-content: center;
            flex: 1;
            padding: 2rem 1rem;
        }

        .login-card {
            width: 100%;
            max-width: 480px;
            background: var(--card-glass-bg);
            border: 1px solid var(--card-border);
            border-radius: 24px;
            box-shadow: var(--card-shadow);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            padding: 2.5rem 2.25rem;
            animation: fadeInCard 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes fadeInCard {
            from {
                opacity: 0;
                transform: translateY(20px) scale(0.98);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .card-header-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.12), rgba(59, 130, 246, 0.08));
            border: 1px solid rgba(37, 99, 235, 0.25);
            padding: 0.35rem 0.85rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 700;
            color: #1d4ed8;
            margin-bottom: 1rem;
        }

        .card-header-badge .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: #2563eb;
            box-shadow: 0 0 8px rgba(37, 99, 235, 0.6);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.3); opacity: 0.7; }
        }

        .card-title {
            font-family: var(--font-head);
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--text-title);
            letter-spacing: -0.02em;
            line-height: 1.2;
            margin-bottom: 0.5rem;
        }

        .card-desc {
            font-size: 0.9rem;
            color: var(--text-body);
            line-height: 1.5;
            margin-bottom: 1.75rem;
        }

        .form-group {
            margin-bottom: 1.25rem;
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
        }

        .form-label {
            font-size: 0.82rem;
            font-weight: 700;
            color: var(--text-dark);
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .input-group {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon {
            position: absolute;
            left: 1rem;
            color: var(--text-muted);
            pointer-events: none;
            width: 18px;
            height: 18px;
        }

        .form-control {
            width: 100%;
            height: 48px;
            padding: 0.6rem 1rem 0.6rem 2.85rem;
            font-family: var(--font-main);
            font-size: 0.95rem;
            color: var(--text-dark);
            background: rgba(255, 255, 255, 0.9);
            border: 1.5px solid #cbd5e1;
            border-radius: 12px;
            outline: none;
            transition: all 0.2s ease;
        }

        .form-control:focus {
            background: #ffffff;
            border-color: #2563eb;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.15);
        }

        .form-options {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            font-size: 0.85rem;
        }

        .remember-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            color: var(--text-body);
            font-weight: 500;
        }

        .btn-submit {
            width: 100%;
            height: 50px;
            background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
            border: none;
            border-radius: 12px;
            color: #ffffff;
            font-family: var(--font-main);
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.35);
            transition: all 0.2s ease;
        }

        .btn-submit:hover {
            background: linear-gradient(135deg, #172554 0%, #1d4ed8 100%);
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.45);
            transform: translateY(-1px);
        }

        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 1.5rem 0 1rem;
            color: var(--text-muted);
            font-size: 0.78rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .divider::before, .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #cbd5e1;
        }

        .divider span {
            padding: 0 0.75rem;
        }

        .btn-guest {
            width: 100%;
            height: 44px;
            background: rgba(255, 255, 255, 0.85);
            border: 1.5px solid #cbd5e1;
            border-radius: 12px;
            color: var(--text-dark);
            font-family: var(--font-main);
            font-size: 0.88rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .btn-guest:hover {
            background: #ffffff;
            border-color: #94a3b8;
            color: var(--primary-blue);
        }

        /* Demo Account Quick Switcher */
        .quick-accounts-box {
            margin-top: 1.5rem;
            background: rgba(241, 245, 249, 0.75);
            border: 1px dashed #cbd5e1;
            border-radius: 12px;
            padding: 0.85rem 1rem;
        }

        .quick-accounts-title {
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .quick-accounts-pills {
            display: flex;
            flex-wrap: wrap;
            gap: 0.35rem;
        }

        .quick-btn {
            padding: 0.25rem 0.55rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            color: var(--text-dark);
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .quick-btn:hover {
            background: #2563eb;
            color: #ffffff;
            border-color: #2563eb;
        }

        .login-footer {
            text-align: center;
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.8rem;
            max-width: 1200px;
            width: 100%;
            margin: 0 auto;
            padding: 0.5rem;
        }

        .alert-error {
            background: #fee2e2;
            border-left: 4px solid #ef4444;
            color: #b91c1c;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            font-size: 0.85rem;
            margin-bottom: 1.25rem;
        }
    </style>
</head>
<body class="login-page">
    <div class="bg-overlay"></div>

    <div class="page-content">
        <!-- Header -->
        <header class="login-header">
            <a href="{{ url('/') }}" class="logo-container">
                <img src="{{ asset('assets/images/MAI.png') }}" alt="MAI Logo" class="header-logo" onerror="this.src='{{ asset('assets/images/MAI TERANG.png') }}'">
                <div class="header-title-box">
                    <span class="header-company-name">PT METALART ASTRA INDONESIA</span>
                    <span class="header-portal-label">PORTAL WAREHOUSE TERPADU</span>
                </div>
            </a>
            <div class="system-pill-group">
                <span class="system-pill mars">MARS ENGINE</span>
                <span class="system-pill saturnus">SATURNUS 3D</span>
            </div>
        </header>

        <!-- Main Login Box -->
        <main class="login-main">
            <div class="login-card">
                <div class="card-header-badge">
                    <span class="dot"></span>
                    <span>SINGLE SIGN-ON GATEWAY</span>
                </div>

                <h1 class="card-title">Masuk ke Sistem</h1>
                <p class="card-desc">Gunakan akun Anda untuk mengakses sistem manajemen stok, request barang minim, dan pendaftaran consumable.</p>

                @if(session('error'))
                    <div class="alert-error">
                        {{ session('error') }}
                    </div>
                @endif

                @if($errors->any())
                    <div class="alert-error">
                        @foreach($errors->all() as $err)
                            <div>{{ $err }}</div>
                        @endforeach
                    </div>
                @endif

                <form action="{{ route('login.post') }}" method="POST" id="loginForm">
                    @csrf

                    <div class="form-group">
                        <label class="form-label" for="login_input">Username atau Email</label>
                        <div class="input-group">
                            <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                            <input type="text" id="login_input" name="login" class="form-control" placeholder="Contoh: admin / whc / purchasing" required autofocus value="{{ old('login') }}">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password">Password</label>
                        <div class="input-group">
                            <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                            </svg>
                            <input type="password" id="password" name="password" class="form-control" placeholder="Masukkan password Anda" required>
                        </div>
                    </div>

                    <div class="form-options">
                        <label class="remember-label">
                            <input type="checkbox" name="remember" value="1">
                            <span>Ingat saya di perangkat ini</span>
                        </label>
                    </div>

                    <button type="submit" class="btn-submit">
                        <span>Masuk ke Portal</span>
                        <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2.5" fill="none">
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                            <polyline points="12 5 19 12 12 19"></polyline>
                        </svg>
                    </button>
                </form>

                <div class="divider">
                    <span>Atau</span>
                </div>

                <a href="{{ route('guest.login') }}" class="btn-guest">
                    <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                    <span>Masuk sebagai Guest (Read-Only)</span>
                </a>

                <!-- Quick Accounts Selector Helper -->
                <div class="quick-accounts-box">
                    <div class="quick-accounts-title">
                        <span>Akun Pengujian Cepat:</span>
                        <span style="font-size: 0.68rem; font-weight: normal; color: #94a3b8;">Klik untuk auto-fill</span>
                    </div>
                    <div class="quick-accounts-pills">
                        <button type="button" class="quick-btn" onclick="fillAccount('admin', 'password')">👑 Admin</button>
                        <button type="button" class="quick-btn" onclick="fillAccount('whc', 'password')">📦 WH Consumable</button>
                        <button type="button" class="quick-btn" onclick="fillAccount('purchasing', 'password')">🛒 Purchasing</button>
                        <button type="button" class="quick-btn" onclick="fillAccount('staff', 'password')">✍️ Staff Approver</button>
                        <button type="button" class="quick-btn" onclick="fillAccount('accounting', 'password')">💰 Accounting</button>
                        <button type="button" class="quick-btn" onclick="fillAccount('budi_user', 'password')">🏭 Prod User</button>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="login-footer">
            &copy; {{ date('Y') }} PT Metalart Astra Indonesia. Warehouse Unified Portal — MARS &amp; SATURNUS Integrated Engine.
        </footer>
    </div>

    <script>
        function fillAccount(username, password) {
            document.getElementById('login_input').value = username;
            document.getElementById('password').value = password;
        }
    </script>
</body>
</html>
