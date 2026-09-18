<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login | MAI Consumable System</title>
    <meta name="description" content="Silakan masuk untuk melanjutkan ke sistem dan kelola permintaan material dengan lebih efektif - PT. Metalart Astra Indonesia">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400;1,600&family=Outfit:wght@300;400;500;600;700;800;900&family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">

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

        /* ============================================================
           CINEMATIC GALAXY TRANSITION OVERLAY
           ============================================================ */
        .galaxy-transition-overlay {
            position: fixed;
            inset: 0;
            width: 100vw;
            height: 100vh;
            z-index: 999999;
            background-color: #03050a;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            opacity: 0;
            pointer-events: none;
            visibility: hidden;
            transition: opacity 0.5s cubic-bezier(0.16, 1, 0.3, 1), visibility 0.5s;
        }

        .galaxy-transition-overlay.is-active {
            opacity: 1;
            pointer-events: all;
            visibility: visible;
        }

        /* Full-screen Galaxy Background Layer */
        .galaxy-bg-layer {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            background-size: cover;
            background-position: center center;
            background-repeat: no-repeat;
            transform: scale(1);
            transform-origin: center center;
            will-change: transform, filter;
            transition: transform 0.85s cubic-bezier(0.2, 0.8, 0.2, 1), filter 0.85s ease;
        }

        /* Phase 4 zoom-in effect into galaxy luminous core */
        .galaxy-transition-overlay.galaxy-zooming .galaxy-bg-layer {
            transform: scale(1.45);
            filter: brightness(1.22) contrast(1.12);
        }

        /* Subtle Cosmic Vignette & Depth Overlay */
        .galaxy-depth-overlay {
            position: absolute;
            inset: 0;
            background: radial-gradient(
                circle at 50% 50%,
                rgba(3, 7, 18, 0.15) 0%,
                rgba(3, 7, 18, 0.45) 50%,
                rgba(3, 7, 18, 0.82) 100%
            );
            pointer-events: none;
        }

        /* Soft Luminous Core Radial Pulse */
        .galaxy-core-pulse {
            position: absolute;
            top: 50%;
            left: 50%;
            width: 600px;
            height: 600px;
            margin-top: -300px;
            margin-left: -300px;
            background: radial-gradient(
                circle,
                rgba(56, 189, 248, 0.25) 0%,
                rgba(6, 182, 212, 0.12) 35%,
                rgba(3, 7, 18, 0) 70%
            );
            border-radius: 50%;
            pointer-events: none;
            animation: galaxyCorePulseAnim 3.2s ease-in-out infinite;
        }

        @keyframes galaxyCorePulseAnim {
            0%, 100% {
                transform: scale(0.95);
                opacity: 0.45;
            }
            50% {
                transform: scale(1.2);
                opacity: 0.8;
            }
        }

        /* Central Cinematic HUD & Typography Container */
        .galaxy-hud-container {
            position: relative;
            z-index: 10;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 100%;
            max-width: 820px;
            padding: 2rem;
            opacity: 0;
            transform: scale(0.95);
            transition: opacity 0.5s ease-out, transform 0.5s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .galaxy-transition-overlay.is-active .galaxy-hud-container {
            opacity: 1;
            transform: scale(1);
        }

        /* Phase 4 HUD exit fade */
        .galaxy-transition-overlay.galaxy-exiting .galaxy-hud-container {
            opacity: 0;
            transform: scale(1.05);
            transition: opacity 0.45s ease, transform 0.45s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Rotating Minimal HUD Ring */
        .hud-ring-wrapper {
            position: relative;
            width: 380px;
            height: 380px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 2.5rem;
        }

        .hud-circular-ring {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            animation: hudRingSpin 24s linear infinite;
            filter: drop-shadow(0 0 10px rgba(56, 189, 248, 0.3));
            pointer-events: none;
        }

        @keyframes hudRingSpin {
            from {
                transform: rotate(0deg);
            }
            to {
                transform: rotate(360deg);
            }
        }

        /* Central Typography */
        .hud-center-content {
            position: relative;
            z-index: 2;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            max-width: 320px;
        }

        .hud-welcome-tag {
            font-family: 'Outfit', 'Space Grotesk', sans-serif;
            font-size: 0.85rem;
            font-weight: 400;
            letter-spacing: 0.45em;
            text-transform: uppercase;
            color: rgba(224, 242, 254, 0.9);
            margin-bottom: 0.35rem;
            padding-left: 0.45em;
            text-shadow: 0 0 14px rgba(56, 189, 248, 0.6);
        }

        .hud-nova-title {
            font-family: 'Outfit', sans-serif;
            font-size: 4.5rem;
            font-weight: 800;
            letter-spacing: 0.22em;
            text-transform: uppercase;
            line-height: 1.05;
            margin: 0 0 0.5rem 0;
            padding-left: 0.22em;
            background: linear-gradient(180deg, #ffffff 15%, #e0f2fe 55%, #38bdf8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            filter: drop-shadow(0 0 24px rgba(56, 189, 248, 0.5));
        }

        .hud-subtitle {
            font-family: 'Space Grotesk', 'Plus Jakarta Sans', sans-serif;
            font-size: 0.85rem;
            font-weight: 350;
            letter-spacing: 0.14em;
            color: rgba(203, 213, 225, 0.82);
            margin: 0;
            text-shadow: 0 0 8px rgba(14, 165, 233, 0.3);
        }

        /* Horizontal System Status Progress Sequence */
        .hud-status-sequence-wrapper {
            width: 100%;
            max-width: 680px;
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.9rem;
        }

        .status-progress-track {
            position: relative;
            width: 100%;
            height: 2px;
            background: rgba(255, 255, 255, 0.12);
            border-radius: 2px;
            overflow: hidden;
        }

        .status-progress-fill {
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            width: 0%;
            background: linear-gradient(90deg, #38bdf8, #06b6d4, #22d3ee);
            box-shadow: 0 0 12px rgba(56, 189, 248, 0.9);
            transition: width 0.65s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .status-steps-grid {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            width: 100%;
            margin-top: -0.65rem;
        }

        .status-step-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.65rem;
            opacity: 0.32;
            transition: all 0.4s ease;
            flex: 1;
            text-align: center;
        }

        .step-node-dot {
            position: relative;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.25);
            border: 1.5px solid rgba(255, 255, 255, 0.4);
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .node-pulse-ring {
            position: absolute;
            inset: -4px;
            border-radius: 50%;
            border: 1.5px solid #38bdf8;
            opacity: 0;
            transform: scale(0.8);
            pointer-events: none;
        }

        .step-label {
            font-family: 'Space Grotesk', 'Outfit', sans-serif;
            font-size: 0.725rem;
            font-weight: 500;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.45);
            transition: all 0.4s ease;
            white-space: nowrap;
        }

        /* Active Step State */
        .status-step-item.is-active {
            opacity: 1;
        }

        .status-step-item.is-active .step-node-dot {
            background: #38bdf8;
            border-color: #ffffff;
            box-shadow: 0 0 16px #38bdf8, 0 0 26px rgba(56, 189, 248, 0.85);
            transform: scale(1.3);
        }

        .status-step-item.is-active .node-pulse-ring {
            opacity: 1;
            animation: stepNodePulse 1.4s ease-out infinite;
        }

        .status-step-item.is-active .step-label {
            color: #ffffff;
            text-shadow: 0 0 14px rgba(56, 189, 248, 0.95);
            font-weight: 700;
        }

        /* Completed Step State */
        .status-step-item.is-completed {
            opacity: 0.85;
        }

        .status-step-item.is-completed .step-node-dot {
            background: #0ea5e9;
            border-color: #38bdf8;
            box-shadow: 0 0 10px rgba(56, 189, 248, 0.5);
            transform: scale(1.1);
        }

        .status-step-item.is-completed .step-label {
            color: rgba(224, 242, 254, 0.85);
        }

        @keyframes stepNodePulse {
            0% {
                transform: scale(1);
                opacity: 0.9;
            }
            100% {
                transform: scale(2.2);
                opacity: 0;
            }
        }

        /* Final Cinematic Exit Flash */
        .galaxy-exit-flash {
            position: absolute;
            inset: 0;
            background: radial-gradient(
                circle at 50% 50%,
                rgba(224, 242, 254, 0.95) 0%,
                rgba(56, 189, 248, 0.8) 35%,
                rgba(3, 5, 10, 0.95) 100%
            );
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.35s ease-in;
            z-index: 20;
        }

        .galaxy-transition-overlay.galaxy-flashing .galaxy-exit-flash {
            opacity: 1;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .hud-ring-wrapper {
                width: 300px;
                height: 300px;
                margin-bottom: 2rem;
            }
            .hud-nova-title {
                font-size: 3.5rem;
            }
            .hud-subtitle {
                font-size: 0.775rem;
                letter-spacing: 0.08em;
            }
            .step-label {
                font-size: 0.65rem;
                letter-spacing: 0.08em;
            }
        }

        @media (max-width: 480px) {
            .hud-ring-wrapper {
                width: 260px;
                height: 260px;
                margin-bottom: 1.5rem;
            }
            .hud-welcome-tag {
                font-size: 0.75rem;
                letter-spacing: 0.3em;
            }
            .hud-nova-title {
                font-size: 2.75rem;
            }
            .step-label {
                font-size: 0.55rem;
                letter-spacing: 0.04em;
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
                <!-- <h2 class="welcome-saturnus" style="color: black;">
                    WAREHOUSE CONSUMABLE SYSTEM
                </h2> -->
                <p class="welcome-desc" style="color: black;">
                    WAREHOUSE CONSUMABLE SYSTEM
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

    <!-- ============================================================
         CINEMATIC GALAXY TRANSITION OVERLAY
         ============================================================ -->
    <div id="galaxyTransitionOverlay" class="galaxy-transition-overlay" aria-hidden="true">
        <!-- Full-screen Galaxy Background Layer -->
        <div class="galaxy-bg-layer" id="galaxyBgLayer" style="background-image: url('{{ asset('assets/images/galaxy.png') }}');"></div>
        
        <!-- Subtle Cosmic Depth & Vignette Overlay -->
        <div class="galaxy-depth-overlay"></div>

        <!-- Soft Luminous Core Radial Pulse -->
        <div class="galaxy-core-pulse"></div>

        <!-- Central Cinematic HUD & Typography Container -->
        <div class="galaxy-hud-container" id="galaxyHudContainer">
            
            <!-- Rotating Minimal Circular HUD Ring -->
            <div class="hud-ring-wrapper">
                <svg class="hud-circular-ring" viewBox="0 0 400 400" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <!-- Outer subtle dashed track -->
                    <circle cx="200" cy="200" r="185" stroke="rgba(56, 189, 248, 0.18)" stroke-width="1.2" stroke-dasharray="6 8" />
                    <!-- Primary luminous ring arc -->
                    <circle cx="200" cy="200" r="170" stroke="url(#hudCyanGradient)" stroke-width="1.5" stroke-dasharray="180 80 40 60" />
                    <!-- Inner precision tick marks -->
                    <circle cx="200" cy="200" r="155" stroke="rgba(255, 255, 255, 0.12)" stroke-width="1" stroke-dasharray="2 16" />
                    <!-- Decorative Cardinal HUD Crosshairs -->
                    <line x1="200" y1="10" x2="200" y2="25" stroke="rgba(56, 189, 248, 0.5)" stroke-width="1.5" />
                    <line x1="200" y1="375" x2="200" y2="390" stroke="rgba(56, 189, 248, 0.5)" stroke-width="1.5" />
                    <line x1="10" y1="200" x2="25" y2="200" stroke="rgba(56, 189, 248, 0.5)" stroke-width="1.5" />
                    <line x1="375" y1="200" x2="390" y2="200" stroke="rgba(56, 189, 248, 0.5)" stroke-width="1.5" />
                    <defs>
                        <linearGradient id="hudCyanGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" stop-color="#38bdf8" stop-opacity="0.85" />
                            <stop offset="50%" stop-color="#06b6d4" stop-opacity="0.3" />
                            <stop offset="100%" stop-color="#ffffff" stop-opacity="0.75" />
                        </linearGradient>
                    </defs>
                </svg>
                
                <!-- Central Typography -->
                <div class="hud-center-content">
                    <span class="hud-welcome-tag">WELCOME TO</span>
                    <h1 class="hud-nova-title">NOVA</h1>
                    <p class="hud-subtitle">Initializing Warehouse Intelligence System...</p>
                </div>
            </div>

            <!-- Horizontal System Status Progress Sequence -->
            <div class="hud-status-sequence-wrapper">
                <div class="status-progress-track">
                    <div class="status-progress-fill" id="statusProgressFill"></div>
                </div>
                <div class="status-steps-grid">
                    <!-- Step 1: CONNECTING -->
                    <div class="status-step-item" id="stepConnecting" data-step="1">
                        <div class="step-node-dot">
                            <span class="node-pulse-ring"></span>
                        </div>
                        <span class="step-label">CONNECTING</span>
                    </div>
                    <!-- Step 2: AUTHENTICATING -->
                    <div class="status-step-item" id="stepAuthenticating" data-step="2">
                        <div class="step-node-dot">
                            <span class="node-pulse-ring"></span>
                        </div>
                        <span class="step-label">AUTHENTICATING</span>
                    </div>
                    <!-- Step 3: SYNCHRONIZING -->
                    <div class="status-step-item" id="stepSynchronizing" data-step="3">
                        <div class="step-node-dot">
                            <span class="node-pulse-ring"></span>
                        </div>
                        <span class="step-label">SYNCHRONIZING</span>
                    </div>
                    <!-- Step 4: SYSTEM READY -->
                    <div class="status-step-item" id="stepSystemReady" data-step="4">
                        <div class="step-node-dot">
                            <span class="node-pulse-ring"></span>
                        </div>
                        <span class="step-label">SYSTEM READY</span>
                    </div>
                </div>
            </div>

        </div>

        <!-- Final Cinematic Flash / Luminous Transition Layer -->
        <div class="galaxy-exit-flash" id="galaxyExitFlash"></div>
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

            // ── Dynamic Toast Generator ──
            function showToast(message, type = 'error') {
                const container = document.getElementById('toastContainer') || (function() {
                    const c = document.createElement('div');
                    c.className = 'toast-container';
                    c.id = 'toastContainer';
                    document.body.appendChild(c);
                    return c;
                })();

                const toast = document.createElement('div');
                toast.className = `toast ${type}`;
                toast.setAttribute('role', 'alert');

                const icon = type === 'success'
                    ? `<svg viewBox="0 0 24 24" width="20" height="20" stroke="#059669" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>`
                    : `<svg viewBox="0 0 24 24" width="20" height="20" stroke="#dc2626" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>`;

                toast.innerHTML = `${icon}<div class="toast-message">${message}</div>`;
                container.appendChild(toast);

                setTimeout(() => toast.classList.add('show'), 50);
                setTimeout(() => {
                    toast.classList.remove('show');
                    setTimeout(() => toast.remove(), 350);
                }, 5000);
            }

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

            // ── Cinematic Galaxy Transition Controller ──
            const transitionOverlay = document.getElementById('galaxyTransitionOverlay');
            const progressFill = document.getElementById('statusProgressFill');
            const step1 = document.getElementById('stepConnecting');
            const step2 = document.getElementById('stepAuthenticating');
            const step3 = document.getElementById('stepSynchronizing');
            const step4 = document.getElementById('stepSystemReady');

            function startGalaxyTransition(redirectUrl) {
                if (!transitionOverlay) {
                    window.location.href = redirectUrl || '{{ route("dashboard.index") }}';
                    return;
                }

                // Activate Galaxy Transition Screen (Phase 1: Enter)
                transitionOverlay.classList.add('is-active');
                transitionOverlay.setAttribute('aria-hidden', 'false');

                // Step 1: CONNECTING (0 - 25%)
                if (step1) step1.classList.add('is-active');
                if (progressFill) progressFill.style.width = '12%';

                // Step 2: AUTHENTICATING (25 - 50%) at 700ms
                setTimeout(() => {
                    if (step1) {
                        step1.classList.remove('is-active');
                        step1.classList.add('is-completed');
                    }
                    if (step2) step2.classList.add('is-active');
                    if (progressFill) progressFill.style.width = '38%';
                }, 750);

                // Step 3: SYNCHRONIZING (50 - 75%) at 1650ms
                setTimeout(() => {
                    if (step2) {
                        step2.classList.remove('is-active');
                        step2.classList.add('is-completed');
                    }
                    if (step3) step3.classList.add('is-active');
                    if (progressFill) progressFill.style.width = '68%';
                }, 1650);

                // Step 4: SYSTEM READY (75 - 100%) at 2550ms (Phase 3: System Ready)
                setTimeout(() => {
                    if (step3) {
                        step3.classList.remove('is-active');
                        step3.classList.add('is-completed');
                    }
                    if (step4) {
                        step4.classList.add('is-active');
                        step4.classList.add('is-completed');
                    }
                    if (progressFill) progressFill.style.width = '100%';
                }, 2550);

                // Phase 4: ENTER DASHBOARD (Zoom into galaxy core + HUD fade) at 3250ms
                setTimeout(() => {
                    transitionOverlay.classList.add('galaxy-exiting');
                    transitionOverlay.classList.add('galaxy-zooming');
                }, 3250);

                // Phase 4.5: Luminous Flash / Seamless fade at 3650ms
                setTimeout(() => {
                    transitionOverlay.classList.add('galaxy-flashing');
                }, 3650);

                // Navigate to Dashboard at 3850ms
                setTimeout(() => {
                    try {
                        sessionStorage.setItem('mai_portal_transition', '1');
                    } catch (e) {}
                    window.location.href = redirectUrl || '{{ route("dashboard.index") }}';
                }, 3850);
            }

            // ── AJAX Login Form Submission Handler ──
            const loginForm = document.getElementById('loginForm');
            const submitBtn = loginForm ? loginForm.querySelector('.btn-submit-primary') : null;
            const originalBtnContent = submitBtn ? submitBtn.innerHTML : '';

            if (loginForm) {
                loginForm.addEventListener('submit', async function (e) {
                    e.preventDefault();

                    const usernameVal = document.getElementById('username')?.value.trim();
                    const passwordVal = document.getElementById('password')?.value;
                    const rememberVal = document.getElementById('remember')?.checked ? 1 : 0;
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
                                      loginForm.querySelector('input[name="_token"]')?.value;

                    // Clear previous inline field errors
                    document.querySelectorAll('.input-group-pill').forEach(el => el.classList.remove('has-error'));
                    document.querySelectorAll('.field-error-text').forEach(el => el.remove());

                    if (!usernameVal || !passwordVal) {
                        showToast('Username/Email dan Password wajib diisi.', 'error');
                        return;
                    }

                    // Button loading state
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.style.opacity = '0.85';
                        submitBtn.innerHTML = `
                            <span style="display:inline-block; width:16px; height:16px; border:2px solid #fff; border-right-color:transparent; border-radius:50%; animation:hudRingSpin 0.75s linear infinite; margin-right:8px;"></span>
                            <span>Memverifikasi...</span>
                        `;
                    }

                    try {
                        const response = await fetch(loginForm.action || '{{ url("/login") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': csrfToken
                            },
                            body: JSON.stringify({
                                username: usernameVal,
                                login: usernameVal,
                                password: passwordVal,
                                remember: rememberVal,
                                _token: csrfToken
                            })
                        });

                        const data = await response.json();

                        if (response.ok && data.success) {
                            // Login Success -> Trigger Galaxy Cinematic Transition!
                            startGalaxyTransition(data.redirect);
                        } else {
                            // Login Failed -> Restore button & show error notification
                            if (submitBtn) {
                                submitBtn.disabled = false;
                                submitBtn.style.opacity = '1';
                                submitBtn.innerHTML = originalBtnContent;
                            }

                            const errorMessage = data.message || (data.errors && (data.errors.username?.[0] || data.errors.login?.[0])) || 'Kredensial login tidak valid.';
                            showToast(errorMessage, 'error');

                            // Highlight inputs
                            document.getElementById('username')?.closest('.input-group-pill')?.classList.add('has-error');
                            document.getElementById('password')?.closest('.input-group-pill')?.classList.add('has-error');
                        }
                    } catch (err) {
                        // Fallback to standard form submission if fetch completely fails
                        console.error('AJAX login error:', err);
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.style.opacity = '1';
                            submitBtn.innerHTML = originalBtnContent;
                        }
                        showToast('Terjadi kesalahan koneksi. Mencoba login normal...', 'error');
                        loginForm.submit();
                    }
                });
            }

        });
    </script>
</body>
</html>
