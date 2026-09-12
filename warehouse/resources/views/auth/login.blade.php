<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | MAI Consumable System</title>
    <meta name="description" content="Silakan masuk untuk melanjutkan ke sistem dan kelola permintaan material dengan lebih efektif - PT. Metalart Astra Indonesia">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400;1,600&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        /* ============================================================
           RESET & CSS VARIABLES
           ============================================================ */
        *, *::before, *::after {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-blue: #2563eb;
            --primary-blue-hover: #1d4ed8;
            --primary-blue-light: #3b82f6;
            --text-dark: #0f2b48;
            --text-title: #0f172a;
            --text-body: #475569;
            --text-muted: #64748b;
            --text-placeholder: #94a3b8;
            --border-color: rgba(226, 232, 240, 0.85);
            --card-glass-bg: rgba(255, 255, 255, 0.62);
            --card-border: rgba(255, 255, 255, 0.85);
            --card-shadow: 0 25px 60px -15px rgba(15, 43, 72, 0.18), 0 0 0 1px rgba(255, 255, 255, 0.6) inset;
            
            --font-main: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            --font-head: 'Outfit', sans-serif;
        }

        html, body {
            height: 100%;
            width: 100%;
            font-family: var(--font-main);
            color: var(--text-dark);
            background-color: #f1f5f9;
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

        /* Subtle atmospheric overlay for ultra-crisp contrast */
        body.login-page::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(
                135deg,
                rgba(255, 255, 255, 0.12) 0%,
                rgba(240, 248, 255, 0.05) 50%,
                rgba(255, 255, 255, 0.18) 100%
            );
            pointer-events: none;
            z-index: 1;
        }

        /* ============================================================
           TOAST NOTIFICATIONS
           ============================================================ */
        .toast-container {
            position: fixed;
            top: 1.5rem;
            right: 1.5rem;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            pointer-events: none;
        }

        .toast {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.875rem 1.25rem;
            border-radius: 16px;
            font-size: 0.875rem;
            font-weight: 600;
            min-width: 280px;
            max-width: 420px;
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255, 255, 255, 0.8);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12);
            opacity: 0;
            transform: translateX(30px);
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            pointer-events: auto;
        }

        .toast.show {
            opacity: 1;
            transform: translateX(0);
        }

        .toast.success {
            background: rgba(236, 253, 245, 0.92);
            border-color: rgba(110, 231, 183, 0.8);
            color: #065f46;
        }

        .toast.error {
            background: rgba(254, 242, 242, 0.92);
            border-color: rgba(252, 165, 165, 0.8);
            color: #991b1b;
        }

        .toast-message {
            flex: 1;
            line-height: 1.4;
        }

        /* ============================================================
           MAIN CONTAINER & LAYOUT
           ============================================================ */
        .login-layout-wrapper {
            position: relative;
            z-index: 2;
            width: 100%;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 3rem 4rem 2rem;
        }

        .login-main-section {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            align-items: center;
            width: 100%;
            max-width: 1400px;
            margin: auto;
            gap: 2rem;
            padding-top: 1rem;
            padding-bottom: 2rem;
        }

        /* ============================================================
           LEFT HERO / WELCOME TEXT
           ============================================================ */
        .login-hero-left {
            grid-column: 1;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            align-self: flex-start;
            padding-top: 0;
            margin-top: -3.5rem;
            padding-right: 1.5rem;
            animation: fadeInSlideLeft 0.8s ease-out;
        }

        .welcome-title-wrapper {
            position: relative;
            padding-left: 1.25rem;
            margin-bottom: 0.75rem;
        }

        .welcome-accent-bar {
            position: absolute;
            left: 0;
            top: 6px;
            bottom: 6px;
            width: 4px;
            background: linear-gradient(180deg, #1d68e0, #0284c7);
            border-radius: 4px;
        }

        .welcome-title {
            font-family: var(--font-head);
            font-size: 2.75rem;
            font-weight: 800;
            line-height: 1.08;
            letter-spacing: -0.03em;
            color: var(--text-dark);
            margin: 0;
        }

        .welcome-saturnus {
            font-family: var(--font-head);
            font-size: 2.25rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: #000000;
            padding-left: 1.25rem;
            margin-top: 0.25rem;
            margin-bottom: 0.75rem;
            line-height: 1.1;
        }

        .welcome-desc {
            font-size: 1rem;
            line-height: 1.55;
            color: var(--text-body);
            max-width: 4000px;
            padding-left: 1.25rem;
            font-weight: 450;
            margin: 0;
        }

        /* ============================================================
           CENTER GLASSMORPHISM LOGIN CARD
           ============================================================ */
        .login-card-container {
            grid-column: 2;
            width: 100%;
            max-width: 440px;
            animation: fadeInScale 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .glass-login-card {
            background: var(--card-glass-bg);
            backdrop-filter: blur(28px) saturate(190%);
            -webkit-backdrop-filter: blur(28px) saturate(190%);
            border: 1.5px solid var(--card-border);
            border-radius: 36px;
            padding: 2.75rem 2.5rem 2.25rem;
            box-shadow: var(--card-shadow);
            position: relative;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        /* Card ambient shine */
        .glass-login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 120px;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.4) 0%, rgba(255, 255, 255, 0) 100%);
            border-radius: 36px 36px 0 0;
            pointer-events: none;
        }

        /* Header Logo & Titles */
        .card-header-area {
            text-align: center;
            margin-bottom: 2rem;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .card-logo-badge {
            width: 68px;
            height: 68px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.25rem;
            filter: drop-shadow(0 8px 16px rgba(37, 99, 235, 0.22));
            transition: transform 0.3s ease;
        }

        .card-logo-badge:hover {
            transform: scale(1.05) rotate(2deg);
        }

        .card-logo-svg {
            width: 100%;
            height: 100%;
        }

        .card-title {
            font-family: var(--font-head);
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text-title);
            letter-spacing: -0.02em;
            margin-bottom: 0.25rem;
        }

        .card-subtitle {
            font-size: 0.875rem;
            color: var(--text-muted);
            font-weight: 400;
        }

        /* Form Controls */
        .login-form {
            display: flex;
            flex-direction: column;
            gap: 1.15rem;
        }

        .input-group-pill {
            position: relative;
            display: flex;
            align-items: center;
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 0.25rem 0.75rem;
            box-shadow: 0 2px 6px rgba(15, 23, 42, 0.04);
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .input-group-pill:hover {
            border-color: rgba(147, 197, 253, 0.8);
            background: #ffffff;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.08);
        }

        .input-group-pill:focus-within {
            border-color: var(--primary-blue);
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.15), 0 4px 12px rgba(37, 99, 235, 0.1);
        }

        .input-group-pill.has-error {
            border-color: #ef4444 !important;
            box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.12) !important;
        }

        .input-icon-left {
            display: flex;
            align-items: center;
            justify-content: center;
            padding-left: 0.5rem;
            padding-right: 0.65rem;
            color: var(--text-muted);
            flex-shrink: 0;
        }

        .input-icon-left svg {
            width: 19px;
            height: 19px;
            stroke-width: 1.9;
        }

        .pill-text-input {
            width: 100%;
            border: none;
            outline: none;
            background: transparent;
            font-family: var(--font-main);
            font-size: 0.925rem;
            color: var(--text-title);
            font-weight: 500;
            padding: 0.85rem 0.25rem;
        }

        .pill-text-input::placeholder {
            color: var(--text-placeholder);
            font-weight: 400;
        }

        .btn-eye-toggle {
            background: none;
            border: none;
            outline: none;
            padding: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: var(--text-muted);
            border-radius: 8px;
            transition: color 0.2s, background-color 0.2s;
            flex-shrink: 0;
        }

        .btn-eye-toggle:hover {
            color: var(--text-dark);
            background: rgba(0, 0, 0, 0.04);
        }

        .btn-eye-toggle svg {
            width: 19px;
            height: 19px;
            stroke-width: 1.9;
        }

        .field-error-text {
            font-size: 0.75rem;
            color: #dc2626;
            font-weight: 600;
            margin-top: -0.5rem;
            margin-left: 0.5rem;
        }

        /* Options Row (Remember Me & Forgot Password) */
        .form-options-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: -0.1rem;
            margin-bottom: 0.25rem;
            font-size: 0.85rem;
        }

        .checkbox-container {
            display: flex;
            align-items: center;
            gap: 0.55rem;
            cursor: pointer;
            user-select: none;
            color: var(--text-body);
            font-weight: 500;
        }

        .custom-checkbox-input {
            appearance: none;
            -webkit-appearance: none;
            width: 17px;
            height: 17px;
            border: 1.5px solid #cbd5e1;
            border-radius: 5px;
            background: rgba(255, 255, 255, 0.9);
            cursor: pointer;
            position: relative;
            transition: all 0.2s ease;
            margin: 0;
        }

        .custom-checkbox-input:checked {
            background-color: var(--primary-blue);
            border-color: var(--primary-blue);
        }

        .custom-checkbox-input:checked::after {
            content: '';
            position: absolute;
            top: 2px;
            left: 5px;
            width: 4px;
            height: 8px;
            border: solid white;
            border-width: 0 2px 2px 0;
            transform: rotate(45deg);
        }

        .custom-checkbox-input:focus-visible {
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.2);
        }

        .forgot-password-link {
            color: #0284c7;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s ease, text-decoration 0.2s ease;
        }

        .forgot-password-link:hover {
            color: var(--primary-blue-hover);
            text-decoration: underline;
        }

        /* Primary Submit Button */
        .btn-submit-primary {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            width: 100%;
            background: linear-gradient(135deg, #2f7cf6 0%, #1e60dc 100%);
            color: #ffffff;
            border: none;
            border-radius: 16px;
            padding: 0.95rem 1.5rem;
            font-family: var(--font-main);
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 8px 20px -4px rgba(37, 99, 235, 0.45);
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            margin-top: 0.35rem;
        }

        .btn-submit-primary:hover {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            box-shadow: 0 12px 25px -4px rgba(37, 99, 235, 0.55);
            transform: translateY(-1px);
        }

        .btn-submit-primary:active {
            transform: translateY(1px);
            box-shadow: 0 4px 12px -2px rgba(37, 99, 235, 0.4);
        }

        .btn-submit-primary svg {
            width: 18px;
            height: 18px;
            stroke-width: 2.2;
            transition: transform 0.2s ease;
        }

        .btn-submit-primary:hover svg {
            transform: translateX(3px);
        }

        /* Divider "atau" */
        .divider-atau {
            display: flex;
            align-items: center;
            text-align: center;
            color: #94a3b8;
            font-size: 0.775rem;
            font-weight: 500;
            margin: 0.25rem 0;
        }

        .divider-atau::before,
        .divider-atau::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid rgba(203, 213, 225, 0.7);
        }

        .divider-atau span {
            padding: 0 0.85rem;
            letter-spacing: 0.05em;
        }

        /* Secondary Guest Button */
        .btn-guest-secondary {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.55rem;
            width: 100%;
            background: rgba(255, 255, 255, 0.75);
            color: #1e40af;
            border: 1px solid rgba(191, 219, 254, 0.9);
            border-radius: 16px;
            padding: 0.85rem 1.5rem;
            font-family: var(--font-main);
            font-size: 0.925rem;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 2px 6px rgba(15, 23, 42, 0.03);
            transition: all 0.25s ease;
            text-decoration: none;
        }

        .btn-guest-secondary:hover {
            background: #ffffff;
            border-color: #93c5fd;
            color: #1d4ed8;
            box-shadow: 0 6px 16px rgba(37, 99, 235, 0.12);
            transform: translateY(-1px);
        }

        .btn-guest-secondary:active {
            transform: translateY(0);
        }

        .btn-guest-secondary svg {
            width: 19px;
            height: 19px;
            stroke-width: 2;
            color: #2563eb;
        }

        /* Card Footer Copyright */
        .card-footer-copy {
            text-align: center;
            font-size: 0.75rem;
            color: #64748b;
            margin-top: 1.75rem;
            font-weight: 500;
            letter-spacing: 0.01em;
        }

        /* ============================================================
           BOTTOM FEATURE HIGHLIGHTS (4 COLUMNS)
           ============================================================ */
        .bottom-features-grid {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 1350px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 2rem;
            padding-top: 1.5rem;
            animation: fadeInSlideUp 0.8s ease-out 0.2s both;
        }

        .feature-item {
            display: flex;
            align-items: flex-start;
            gap: 0.9rem;
        }

        .feature-icon-wrapper {
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.45);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.8);
            color: #1e3a5f;
            flex-shrink: 0;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.04);
            transition: transform 0.25s ease, background-color 0.25s ease;
        }

        .feature-item:hover .feature-icon-wrapper {
            transform: translateY(-2px);
            background: rgba(255, 255, 255, 0.8);
            color: #0284c7;
        }

        .feature-icon-wrapper svg {
            width: 22px;
            height: 22px;
            stroke-width: 2;
        }

        .feature-text {
            display: flex;
            flex-direction: column;
            gap: 0.2rem;
        }

        .feature-title {
            font-size: 0.9rem;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.2;
        }

        .feature-desc {
            font-size: 0.775rem;
            color: #475569;
            line-height: 1.4;
            font-weight: 450;
        }

        /* ============================================================
           ANIMATIONS
           ============================================================ */
        @keyframes fadeInSlideLeft {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes fadeInScale {
            from {
                opacity: 0;
                transform: scale(0.94);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        @keyframes fadeInSlideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .d-none {
            display: none !important;
        }

        /* ============================================================
           RESPONSIVE BREAKPOINTS
           ============================================================ */
        @media (max-width: 1200px) {
            .login-layout-wrapper {
                padding: 2.5rem 2.5rem 1.5rem;
            }

            .welcome-title {
                font-size: 2.25rem;
            }

            .bottom-features-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 1.5rem;
            }
        }

        @media (max-width: 900px) {
            body.login-page {
                background-attachment: scroll;
            }

            .login-layout-wrapper {
                padding: 2rem 1.5rem;
                min-height: 100vh;
                height: auto;
            }

            .login-main-section {
                grid-template-columns: 1fr;
                justify-items: center;
                gap: 2.5rem;
                padding-top: 1rem;
            }

            .login-hero-left {
                grid-column: 1;
                align-items: center;
                align-self: auto;
                padding-top: 0;
                margin-top: 0;
                text-align: center;
                padding-right: 0;
            }

            .welcome-title-wrapper {
                padding-left: 0;
            }

            .welcome-accent-bar {
                display: none;
            }

            .welcome-saturnus {
                padding-left: 0;
            }

            .welcome-desc {
                padding-left: 0;
                max-width: 440px;
            }

            .login-card-container {
                grid-column: 1;
            }

            .bottom-features-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 1.25rem;
                margin-top: 2rem;
            }
        }

        @media (max-width: 600px) {
            .login-layout-wrapper {
                padding: 1.5rem 1rem;
            }

            .glass-login-card {
                padding: 2rem 1.5rem 1.75rem;
                border-radius: 28px;
            }

            .welcome-title {
                font-size: 2rem;
            }

            .bottom-features-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
        }
    </style>
</head>
<body class="login-page">

    <!-- Toast Notifications -->
    <div class="toast-container" id="toastContainer">
        @if(session('success'))
            <div class="toast success" role="alert">
                <svg viewBox="0 0 24 24" width="20" height="20" stroke="#059669" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                <div class="toast-message">{{ session('success') }}</div>
            </div>
        @endif

        @if(session('error'))
            <div class="toast error" role="alert">
                <svg viewBox="0 0 24 24" width="20" height="20" stroke="#dc2626" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="15" y1="9" x2="9" y2="15"></line>
                    <line x1="9" y1="9" x2="15" y2="15"></line>
                </svg>
                <div class="toast-message">{{ session('error') }}</div>
            </div>
        @endif
    </div>

    <!-- Main Wrapper -->
    <div class="login-layout-wrapper">

        <!-- Main Middle Section -->
        <div class="login-main-section">

            <!-- LEFT HERO TEXT -->
            <div class="login-hero-left">
                <div class="welcome-title-wrapper">
                    <div class="welcome-accent-bar"></div>
                    <h1 class="welcome-title" style="color: black;">
                        Welcome<br>Back
                    </h1>
                </div>
                <h2 class="welcome-saturnus" style="color: black;">
                    SATURNUS
                </h2>
                <p class="welcome-desc" style="color: black;">
                    Smart Asset Tracking, Registration & Unregistration Network Utility System
                </p>
            </div>

            <!-- CENTER GLASS CARD -->
            <div class="login-card-container">
                <div class="glass-login-card">

                    <!-- Header with Hexagon Logo -->
                    <div class="card-header-area">
                        <div class="card-logo-badge">
                            <!-- Isometric 3D Hexagon Portal Logo matching reference image -->
                            <svg class="card-logo-svg" viewBox="0 0 100 115" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <defs>
                                    <linearGradient id="maiHexTop" x1="50" y1="5" x2="50" y2="45" gradientUnits="userSpaceOnUse">
                                        <stop offset="0%" stop-color="#38bdf8" />
                                        <stop offset="100%" stop-color="#0284c7" />
                                    </linearGradient>
                                    <linearGradient id="maiHexLeft" x1="10" y1="30" x2="50" y2="105" gradientUnits="userSpaceOnUse">
                                        <stop offset="0%" stop-color="#0ea5e9" />
                                        <stop offset="100%" stop-color="#0369a1" />
                                    </linearGradient>
                                    <linearGradient id="maiHexRight" x1="90" y1="30" x2="50" y2="105" gradientUnits="userSpaceOnUse">
                                        <stop offset="0%" stop-color="#1d4ed8" />
                                        <stop offset="100%" stop-color="#1e3a8a" />
                                    </linearGradient>
                                    <linearGradient id="maiHexInner" x1="50" y1="40" x2="50" y2="85" gradientUnits="userSpaceOnUse">
                                        <stop offset="0%" stop-color="#e0f2fe" />
                                        <stop offset="100%" stop-color="#bae6fd" />
                                    </linearGradient>
                                </defs>
                                <!-- Outer Hexagon / Cube Arch -->
                                <path d="M50 5 L90 28 L90 78 L50 101 L10 78 L10 28 Z" fill="url(#maiHexTop)" opacity="0.15" />
                                <!-- Isometric Facets -->
                                <path d="M50 5 L90 28 L50 50 L10 28 Z" fill="url(#maiHexTop)" />
                                <path d="M10 28 L50 50 L50 101 L10 78 Z" fill="url(#maiHexLeft)" />
                                <path d="M90 28 L90 78 L50 101 L50 50 Z" fill="url(#maiHexRight)" />
                                <!-- Inner Cutout / Gateway -->
                                <path d="M50 32 L72 45 L72 75 L50 88 L28 75 L28 45 Z" fill="#ffffff" />
                                <path d="M50 42 L64 50 L64 70 L50 78 L36 70 L36 50 Z" fill="url(#maiHexInner)" />
                            </svg>
                        </div>
                        <h2 class="card-title">Login</h2>
                        <p class="card-subtitle">Masuk ke akun Anda</p>
                    </div>

                    <!-- Login Form -->
                    <form action="{{ url('/login') }}" method="POST" class="login-form" id="loginForm">
                        @csrf

                        <!-- Username / Email Field -->
                        <div>
                            <div class="input-group-pill @error('username') has-error @enderror @error('login') has-error @enderror">
                                <div class="input-icon-left">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                        <circle cx="12" cy="7" r="4"></circle>
                                    </svg>
                                </div>
                                <input
                                    type="text"
                                    id="username"
                                    name="username"
                                    class="pill-text-input"
                                    value="{{ old('username', old('login')) }}"
                                    placeholder="Email atau Username"
                                    required
                                    autofocus
                                    autocomplete="username"
                                >
                            </div>
                            @error('username')
                                <div class="field-error-text">{{ $message }}</div>
                            @enderror
                            @if(!$errors->has('username') && $errors->has('login'))
                                <div class="field-error-text">{{ $errors->first('login') }}</div>
                            @endif
                        </div>

                        <!-- Password Field -->
                        <div>
                            <div class="input-group-pill @error('password') has-error @enderror">
                                <div class="input-icon-left">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                    </svg>
                                </div>
                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    class="pill-text-input"
                                    placeholder="Password"
                                    required
                                    autocomplete="current-password"
                                >
                                <button type="button" class="btn-eye-toggle" id="togglePasswordBtn" title="Lihat password" tabindex="-1">
                                    <!-- Eye Open Icon -->
                                    <svg class="icon-eye-open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                    <!-- Eye Closed Icon -->
                                    <svg class="icon-eye-closed d-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                        <line x1="1" y1="1" x2="23" y2="23"></line>
                                    </svg>
                                </button>
                            </div>
                            @error('password')
                                <div class="field-error-text">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Remember me & Forgot Password -->
                        <div class="form-options-row">
                            <label class="checkbox-container">
                                <input type="checkbox" id="remember" name="remember" class="custom-checkbox-input" {{ old('remember') ? 'checked' : '' }}>
                                <span>Ingat saya</span>
                            </label>
                            <a href="#" class="forgot-password-link" id="forgotPasswordLink">Lupa password?</a>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="btn-submit-primary">
                            <span>Masuk</span>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                                <polyline points="12 5 19 12 12 19"></polyline>
                            </svg>
                        </button>

                    </form>

                    <!-- Copyright -->
                    <div class="card-footer-copy">
                        &copy; 2026 MAI. All rights reserved.
                    </div>

                </div>
            </div>

            <!-- Empty Right Column for balance -->
            <div style="grid-column: 3;"></div>

        </div>

    </div>

    <!-- JavaScript Interactivity -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {

            // ── Auto dismiss toast notifications ──
            const toasts = document.querySelectorAll('.toast');
            toasts.forEach(toast => {
                setTimeout(() => toast.classList.add('show'), 100);
                setTimeout(() => {
                    toast.classList.remove('show');
                    setTimeout(() => toast.remove(), 350);
                }, 5000);
            });

            // ── Password Visibility Toggle ──
            const passwordInput = document.getElementById('password');
            const togglePasswordBtn = document.getElementById('togglePasswordBtn');

            if (togglePasswordBtn && passwordInput) {
                togglePasswordBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    const iconOpen = togglePasswordBtn.querySelector('.icon-eye-open');
                    const iconClosed = togglePasswordBtn.querySelector('.icon-eye-closed');

                    if (passwordInput.type === 'password') {
                        passwordInput.type = 'text';
                        if (iconOpen) iconOpen.classList.add('d-none');
                        if (iconClosed) iconClosed.classList.remove('d-none');
                    } else {
                        passwordInput.type = 'password';
                        if (iconOpen) iconOpen.classList.remove('d-none');
                        if (iconClosed) iconClosed.classList.add('d-none');
                    }
                });
            }

            // ── Forgot Password Action ──
            const forgotPasswordLink = document.getElementById('forgotPasswordLink');
            if (forgotPasswordLink) {
                forgotPasswordLink.addEventListener('click', function (e) {
                    e.preventDefault();
                    alert('Silakan hubungi Administrator IT PT. Metalart Astra Indonesia untuk reset password akun Anda.');
                });
            }

            // ── Guest Login Action (Autofill or Demo Guidance) ──
            const btnGuestLogin = document.getElementById('btnGuestLogin');
            const usernameInput = document.getElementById('username');
            if (btnGuestLogin && usernameInput && passwordInput) {
                btnGuestLogin.addEventListener('click', function () {
                    usernameInput.value = 'guest@metalart.co.id';
                    passwordInput.value = 'guest123';
                    
                    // Add subtle glow effect to inputs
                    usernameInput.closest('.input-group-pill')?.focus();
                    
                    // Show a helpful hint
                    const existingToast = document.querySelector('.toast-container');
                    if (existingToast) {
                        const guestToast = document.createElement('div');
                        guestToast.className = 'toast success';
                        guestToast.innerHTML = `
                            <svg viewBox="0 0 24 24" width="20" height="20" stroke="#059669" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                            <div class="toast-message">Kredensial Guest telah diisikan. Klik "Masuk" untuk melanjutkan.</div>
                        `;
                        existingToast.appendChild(guestToast);
                        setTimeout(() => guestToast.classList.add('show'), 50);
                        setTimeout(() => {
                            guestToast.classList.remove('show');
                            setTimeout(() => guestToast.remove(), 350);
                        }, 4000);
                    }
                });
            }

        });
    </script>
</body>
</html>
