<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Portal Terpadu Warehouse') | PT Metalart Astra Indonesia</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Roboto:wght@300;400;500;700;900&family=Space+Grotesk:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Core:css (Bootstrap 5 & vendor basics) -->
    <link rel="stylesheet" href="{{ asset('assets/vendors/core/core.css') }}">
    <!-- Plugin css for this page -->
    <link rel="stylesheet" href="{{ asset('assets/vendors/flatpickr/flatpickr.min.css') }}">
    <!-- Feather Font Icons -->
    <link rel="stylesheet" href="{{ asset('assets/fonts/feather-font/css/iconfont.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendors/flag-icon-css/css/flag-icon.min.css') }}">
    <!-- Layout styles (NobleUI / MARS base) -->
    <link rel="stylesheet" href="{{ asset('assets/css/demo1/style.css') }}">
    <!-- Unified Portal Custom Enhancements -->
    <link rel="stylesheet" href="{{ asset('css/unified-style.css') }}">
    <!-- SATURNUS Custom Styles (Scoped to Saturnus routes) -->
    @if(request()->is('saturnus*') || request()->is('form-registrasi*') || request()->is('form-unregistrasi*') || request()->is('proses-approval*') || request()->is('data-view*') || request()->is('account-master*'))
        <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    @endif

    <!-- QR Code & Barcode Libraries -->
    <script src="{{ asset('js/qrcode.min.js') }}"></script>
    <script src="{{ asset('js/JsBarcode.all.min.js') }}"></script>

    <!-- Instant Sidebar & Topbar Theme Initializer (Auto MARS = Light, SATURNUS = Dark) -->
    <script>
        (function() {
            try {
                const isSaturnusRoute = {{ (request()->is('saturnus*') || request()->is('form-registrasi*') || request()->is('form-unregistrasi*') || request()->is('proses-approval*') || request()->is('data-view*') || request()->is('account-master*')) ? 'true' : 'false' }};
                
                // Automatic Module-Based Defaults: MARS = Light, SATURNUS = Dark
                let activeSidebar = isSaturnusRoute ? 'cosmic' : 'light';
                let activeTopbar = isSaturnusRoute ? 'cosmic' : 'light';
                let activeAccent = isSaturnusRoute ? 'purple' : 'blue';

                const customSidebar = localStorage.getItem(isSaturnusRoute ? 'saturnus_sidebar_theme' : 'mars_sidebar_theme');
                const customTopbar = localStorage.getItem(isSaturnusRoute ? 'saturnus_topbar_theme' : 'mars_topbar_theme');
                const customAccent = localStorage.getItem('mai_portal_accent');

                if (customSidebar) activeSidebar = customSidebar;
                if (customTopbar) activeTopbar = customTopbar;
                if (customAccent) activeAccent = customAccent;
                
                const savedNavAnim = localStorage.getItem('mai_nav_animations') !== 'false';
                
                document.documentElement.setAttribute('data-sidebar', activeSidebar);
                document.documentElement.setAttribute('data-topbar', activeTopbar);
                document.documentElement.setAttribute('data-accent', activeAccent);
                if (!savedNavAnim) {
                    document.documentElement.classList.add('no-nav-animations');
                }
            } catch(e) {}
        })();
    </script>

    <style>
        /* Custom Master Layout Styling & Full Width Fix */
        :root {
            --sidebar-width: 260px;
        }

        html, body {
            width: 100% !important;
            min-height: 100vh !important;
            margin: 0 !important;
            padding: 0 !important;
            background-color: #f8fafc !important;
            font-family: 'Outfit', 'Plus Jakarta Sans', sans-serif !important;
            color: #334155;
            overflow-x: hidden;
        }

        .main-wrapper {
            width: 100% !important;
            min-height: 100vh !important;
            display: flex !important;
            flex-direction: row !important;
            position: relative !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        .sidebar {
            width: 260px !important;
            min-width: 260px !important;
            max-width: 260px !important;
            height: 100vh !important;
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            bottom: 0 !important;
            z-index: 1030 !important;
            overflow: hidden !important;
        }

        .page-wrapper {
            margin-left: 260px !important;
            width: calc(100% - 260px) !important;
            min-height: 100vh !important;
            display: flex !important;
            flex-direction: column !important;
            background-color: #f8fafc !important;
            flex-grow: 1 !important;
        }

        .navbar {
            width: calc(100% - 260px) !important;
            left: 260px !important;
            right: 0 !important;
            height: 60px !important;
            background-color: #ffffff !important;
            border-bottom: 1px solid #e2e8f0 !important;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05) !important;
            z-index: 1020 !important;
        }

        .page-content {
            flex: 1 0 auto !important;
            padding: 1.5rem 1.75rem !important;
            width: 100% !important;
            box-sizing: border-box !important;
        }

        /* Card & Table Full-Width Enhancement */
        .card {
            width: 100% !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 12px !important;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03) !important;
            background: #ffffff !important;
            margin-bottom: 1.5rem !important;
        }

        .card .card-body {
            padding: 1.5rem !important;
            width: 100% !important;
        }

        .card .card-title {
            font-weight: 600;
            color: #1e293b;
            font-size: 1.15rem;
        }

        .table-responsive {
            width: 100% !important;
            border-radius: 12px !important;
            border: 1px solid #cbd5e1 !important;
            background: #ffffff !important;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05) !important;
            overflow-x: auto !important;
        }

        .table {
            width: 100% !important;
            margin-bottom: 0 !important;
            white-space: nowrap !important;
        }

        .table thead th {
            font-weight: 600 !important;
            text-transform: uppercase !important;
            font-size: 0.8rem !important;
            letter-spacing: 0.05em !important;
            color: #ffffff !important;
            background-color: #334155 !important;
            border: 1px solid #475569 !important;
            padding: 0.85rem 1rem !important;
        }

        .table tbody td {
            padding: 0.75rem 1rem !important;
            vertical-align: middle !important;
            border: 1px solid #cbd5e1 !important;
            color: #1e293b !important;
            font-size: 0.92rem !important;
        }

        .table-striped>tbody>tr:nth-of-type(odd)>* {
            background-color: #f1f5f9 !important;
        }

        .table-striped>tbody>tr:nth-of-type(even)>* {
            background-color: #ffffff !important;
        }

        .table-striped>tbody>tr:hover>* {
            background-color: #eff6ff !important;
        }

        .btn {
            border-radius: 8px;
            font-weight: 500;
            padding: 0.5rem 1rem;
            transition: all 0.15s ease;
        }

        .btn-primary {
            background-color: #3b82f6;
            border-color: #3b82f6;
        }

        .btn-primary:hover {
            background-color: #2563eb;
            border-color: #2563eb;
            box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.4);
        }

        .form-control,
        .form-select {
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            padding: 0.5rem 0.75rem;
            font-size: 0.95rem;
            transition: all 0.2s ease;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
            background-color: #ffffff;
        }

        /* ==========================================================================
           SIMPLE TREE-STYLE SIDEBAR (MATCHING USER'S REFERENCE DESIGN)
           ========================================================================== */
        .simple-tree-sidebar {
            width: 260px !important;
            min-width: 260px !important;
            max-width: 260px !important;
            height: 100vh !important;
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            bottom: 0 !important;
            z-index: 1030 !important;
            display: flex !important;
            flex-direction: column !important;
            background: #f8fafc !important;
            border-right: 1px solid #e2e8f0 !important;
            box-shadow: 2px 0 8px rgba(0, 0, 0, 0.02) !important;
            user-select: none !important;
        }

        /* Sidebar Brand Header */
        .simple-tree-sidebar .sidebar-header-simple {
            padding: 1rem 1.25rem !important;
            background: #f8fafc !important;
            border-bottom: 1px solid #eef2f6 !important;
            display: flex !important;
            align-items: center !important;
            min-height: 64px !important;
            flex-shrink: 0 !important;
        }

        .simple-tree-sidebar .brand-link-simple {
            display: flex !important;
            align-items: center !important;
            text-decoration: none !important;
            width: 100% !important;
        }

        .simple-tree-sidebar .brand-logo-simple {
            height: 32px !important;
            width: auto !important;
            max-width: 175px !important;
            object-fit: contain !important;
        }

        /* Sidebar Scrollable Body */
        .simple-tree-sidebar .sidebar-scroll-simple {
            flex: 1 1 auto !important;
            overflow-y: auto !important;
            padding: 1rem 0.85rem !important;
            background: #f8fafc !important;
        }

        .simple-tree-sidebar .sidebar-scroll-simple::-webkit-scrollbar {
            width: 4px !important;
        }

        .simple-tree-sidebar .sidebar-scroll-simple::-webkit-scrollbar-thumb {
            background: #cbd5e1 !important;
            border-radius: 4px !important;
        }

        /* Standalone Top Menu Item (Dashboard) */
        .simple-tree-sidebar .nav-tree-standalone {
            margin-bottom: 0.5rem !important;
        }

        .simple-tree-sidebar .nav-tree-item {
            display: flex !important;
            align-items: center !important;
            gap: 0.65rem !important;
            padding: 0.48rem 0.65rem !important;
            border-radius: 6px !important;
            color: #334155 !important;
            font-size: 0.86rem !important;
            font-weight: 500 !important;
            text-decoration: none !important;
            transition: all 0.15s ease !important;
        }

        .simple-tree-sidebar .nav-tree-icon {
            width: 16px !important;
            height: 16px !important;
            color: #64748b !important;
            flex-shrink: 0 !important;
        }

        .simple-tree-sidebar .nav-tree-item:hover {
            background: #f1f5f9 !important;
            color: #0f172a !important;
        }

        .simple-tree-sidebar .nav-tree-item.active-tree-item {
            background: #ffffff !important;
            border: 1px solid #e2e8f0 !important;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05) !important;
            color: #1d4ed8 !important;
            font-weight: 600 !important;
        }

        .simple-tree-sidebar .nav-tree-item.active-tree-item .nav-tree-icon {
            color: #2563eb !important;
        }

        /* Collapsible Tree Groups (COMPLIANCE, MONITORING, ORGANIZATION style) */
        .simple-tree-sidebar .nav-tree-group {
            margin-top: 0.75rem !important;
            margin-bottom: 0.25rem !important;
        }

        .simple-tree-sidebar .nav-tree-header {
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            padding: 0.42rem 0.65rem !important;
            cursor: pointer !important;
            user-select: none !important;
            border-radius: 6px !important;
            transition: background 0.15s ease !important;
        }

        .simple-tree-sidebar .nav-tree-header:hover {
            background: #f1f5f9 !important;
        }

        .simple-tree-sidebar .header-left {
            display: flex !important;
            align-items: center !important;
            gap: 0.65rem !important;
            min-width: 0 !important;
        }

        .simple-tree-sidebar .group-icon {
            width: 15px !important;
            height: 15px !important;
            color: #64748b !important;
            flex-shrink: 0 !important;
        }

        .simple-tree-sidebar .group-title {
            font-size: 0.72rem !important;
            font-weight: 700 !important;
            color: #64748b !important;
            letter-spacing: 0.05em !important;
            text-transform: uppercase !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
        }

        .simple-tree-sidebar .group-caret {
            font-size: 0.75rem !important;
            color: #94a3b8 !important;
            display: inline-block !important;
            transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
            line-height: 1 !important;
        }

        .simple-tree-sidebar .nav-tree-header[aria-expanded="false"] .group-caret {
            transform: rotate(-90deg) !important;
        }

        .simple-tree-sidebar .nav-tree-header[aria-expanded="true"] .group-caret {
            transform: rotate(0deg) !important;
            color: #334155 !important;
        }

        /* Continuous Vertical Indented Line */
        .simple-tree-sidebar .nav-tree-sublist {
            position: relative !important;
            margin-left: 17px !important;
            padding-left: 14px !important;
            border-left: 1.5px solid #cbd5e1 !important;
            margin-top: 0.25rem !important;
            margin-bottom: 0.4rem !important;
            display: flex !important;
            flex-direction: column !important;
            gap: 2px !important;
        }

        .simple-tree-sidebar .sub-tree-link {
            display: flex !important;
            align-items: center !important;
            padding: 0.42rem 0.65rem !important;
            border-radius: 6px !important;
            color: #334155 !important;
            font-size: 0.84rem !important;
            font-weight: 500 !important;
            text-decoration: none !important;
            position: relative !important;
            transition: all 0.15s ease !important;
        }

        .simple-tree-sidebar .sub-tree-link:hover {
            background: #f1f5f9 !important;
            color: #0f172a !important;
        }

        /* Active Subtree Link (Floating card pill with blue bar like 'Frameworks' in image) */
        .simple-tree-sidebar .sub-tree-link.active-tree-item {
            background: #ffffff !important;
            border: 1px solid #e2e8f0 !important;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08), 0 1px 2px rgba(0, 0, 0, 0.04) !important;
            color: #1d4ed8 !important;
            font-weight: 600 !important;
        }

        .simple-tree-sidebar .active-bar-indicator {
            position: absolute !important;
            left: 6px !important;
            top: 20% !important;
            bottom: 20% !important;
            width: 2.5px !important;
            background: #2563eb !important;
            border-radius: 2px !important;
        }

        .simple-tree-sidebar .active-tree-item .sub-tree-text {
            padding-left: 8px !important;
        }

        /* Minimal User Profile Bottom Bar */
        .simple-tree-sidebar .sidebar-user-footer-simple {
            padding: 0.75rem 1rem !important;
            border-top: 1px solid #e2e8f0 !important;
            background: #f8fafc !important;
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            flex-shrink: 0 !important;
        }

        .simple-tree-sidebar .user-avatar-simple {
            width: 32px !important;
            height: 32px !important;
            border-radius: 50% !important;
            background: #e2e8f0 !important;
            color: #334155 !important;
            font-weight: 700 !important;
            font-size: 0.8rem !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            flex-shrink: 0 !important;
        }

        .simple-tree-sidebar .user-text-simple {
            display: flex !important;
            flex-direction: column !important;
            line-height: 1.2 !important;
            min-width: 0 !important;
        }

        .simple-tree-sidebar .user-name-simple {
            font-size: 0.82rem !important;
            font-weight: 600 !important;
            color: #0f172a !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            max-width: 125px !important;
        }

        .simple-tree-sidebar .user-role-simple {
            font-size: 0.65rem !important;
            color: #64748b !important;
            font-weight: 500 !important;
        }

        .simple-tree-sidebar .logout-btn-simple {
            color: #94a3b8 !important;
            width: 28px !important;
            height: 28px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            border-radius: 6px !important;
            transition: all 0.15s ease !important;
            text-decoration: none !important;
            flex-shrink: 0 !important;
        }

        .simple-tree-sidebar .logout-btn-simple:hover {
            color: #ef4444 !important;
            background: #fee2e2 !important;
        }
        /* Top Navigation Module Switcher */
        .portal-switcher {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-left: 1rem;
        }
        .portal-pill {
            padding: 0.35rem 0.85rem;
            border-radius: 20px;
            font-size: 0.78rem;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            transition: all 0.2s ease;
            color: #64748b;
            background: #f1f5f9;
        }
        .portal-pill:hover {
            color: #0f172a;
            background: #e2e8f0;
        }
        .portal-pill.active-main {
            background: #0f172a;
            color: #ffffff;
        }
        .portal-pill.active-mars {
            background: #ef4444;
            color: #ffffff;
        }
        .portal-pill.active-saturnus {
            background: #0284c7;
            color: #ffffff;
        }
        /* Toast Notifications */
        .mai-toast-container {
            position: fixed;
            top: 24px;
            right: 24px;
            z-index: 999999;
            display: flex;
            flex-direction: column;
            gap: 10px;
            pointer-events: none;
        }
        .mai-toast {
            pointer-events: auto;
            background: #ffffff;
            color: #0f172a;
            border-radius: 12px;
            padding: 1rem 1.25rem;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            min-width: 320px;
            max-width: 480px;
            border-left: 5px solid #3b82f6;
            animation: slideInToast 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        .mai-toast.success { border-left-color: #10b981; }
        .mai-toast.error { border-left-color: #ef4444; }
        .mai-toast.info { border-left-color: #3b82f6; }
        @keyframes slideInToast {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
    </style>
</head>
<body class="sidebar-light">
    <div class="main-wrapper">

        <!-- Sidebar Navigation -->
        @include('partials._sidebar')

        <!-- Page Wrapper -->
        <div class="page-wrapper">

            <!-- Navbar -->
            @include('partials._topbar')

            <!-- Main Page Content -->
            <div class="page-content">
                <!-- Toast Notification Box -->
                <div class="mai-toast-container" id="toastContainer">
                    @if(session('success'))
                        <div class="mai-toast success" role="alert">
                            <i data-feather="check-circle" class="text-success" style="width: 22px; height: 22px;"></i>
                            <div>
                                <div style="font-weight: 700; font-size: 0.9rem;">Berhasil!</div>
                                <div style="font-size: 0.82rem; color: #64748b;">{{ session('success') }}</div>
                            </div>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="mai-toast error" role="alert">
                            <i data-feather="alert-octagon" class="text-danger" style="width: 22px; height: 22px;"></i>
                            <div>
                                <div style="font-weight: 700; font-size: 0.9rem;">Perhatian / Gagal</div>
                                <div style="font-size: 0.82rem; color: #64748b;">{{ session('error') }}</div>
                            </div>
                        </div>
                    @endif

                    @if(session('info'))
                        <div class="mai-toast info" role="alert">
                            <i data-feather="info" class="text-primary" style="width: 22px; height: 22px;"></i>
                            <div>
                                <div style="font-weight: 700; font-size: 0.9rem;">Informasi</div>
                                <div style="font-size: 0.82rem; color: #64748b;">{{ session('info') }}</div>
                            </div>
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="mai-toast error" role="alert">
                            <i data-feather="alert-triangle" class="text-danger" style="width: 22px; height: 22px;"></i>
                            <div>
                                <div style="font-weight: 700; font-size: 0.9rem;">Validasi Error</div>
                                <ul style="margin: 0; padding-left: 1.2rem; font-size: 0.8rem; color: #64748b;">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif
                </div>

                @yield('content')
            </div>

            <!-- Footer -->
            @include('partials._footer')

        </div>
    </div>

    <!-- Core:js -->
    <script src="{{ asset('assets/vendors/core/core.js') }}"></script>
    <!-- Plugin js -->
    <script src="{{ asset('assets/vendors/flatpickr/flatpickr.min.js') }}"></script>
    <script src="{{ asset('assets/vendors/apexcharts/apexcharts.min.js') }}"></script>
    <!-- Feather Icons -->
    <script src="{{ asset('assets/vendors/feather-icons/feather.min.js') }}"></script>
    <script src="{{ asset('assets/js/template.js') }}"></script>

    <!-- Global Initialization -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof feather !== 'undefined') {
                feather.replace();
            }

            // Auto dismiss toasts after 5s
            const toasts = document.querySelectorAll('.mai-toast');
            toasts.forEach(t => {
                setTimeout(() => {
                    t.style.opacity = '0';
                    t.style.transform = 'translateX(100%)';
                    t.style.transition = 'all 0.4s ease';
                    setTimeout(() => t.remove(), 400);
                }, 4500);
            });
        });
    </script>

    <!-- Theme & UI Appearance Customizer Partial -->
    @include('partials._theme_customizer')

    @yield('scripts')
</body>
</html>
