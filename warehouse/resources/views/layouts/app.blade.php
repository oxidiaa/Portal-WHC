<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Portal Terpadu Warehouse') | PT. Meiwa Indonesia</title>

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
    <!-- Layout styles -->
    <link rel="stylesheet" href="{{ asset('assets/css/demo1/style.css') }}">
    <!-- SATURNUS & Form Checksheet Custom Styles -->
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <!-- Unified Portal Custom Enhancements -->
    <link rel="stylesheet" href="{{ asset('css/unified-style.css') }}">

    <!-- QR Code & Barcode Libraries -->
    <script src="{{ asset('js/qrcode.min.js') }}"></script>
    <script src="{{ asset('js/JsBarcode.all.min.js') }}"></script>

    <style>
        /* Custom Master Layout Styling & Full Width Fix */
        :root {
            --sidebar-width: 250px;
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
            width: 250px !important;
            min-width: 250px !important;
            max-width: 250px !important;
            height: 100vh !important;
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            bottom: 0 !important;
            z-index: 1030 !important;
            overflow-y: auto !important;
            background: #0f172a !important;
        }

        .page-wrapper {
            margin-left: 250px !important;
            width: calc(100% - 250px) !important;
            min-height: 100vh !important;
            display: flex !important;
            flex-direction: column !important;
            background-color: #f8fafc !important;
            flex-grow: 1 !important;
        }

        .navbar {
            width: calc(100% - 250px) !important;
            left: 250px !important;
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

        body.sidebar-dark .sidebar .sidebar-body .nav .nav-item .nav-link {
            color: #94a3b8;
            font-weight: 500;
            font-size: 0.88rem;
            padding: 0.65rem 1.5rem;
            display: flex;
            align-items: center;
            transition: all 0.2s ease;
        }
        body.sidebar-dark .sidebar .sidebar-body .nav .nav-item .nav-link:hover {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.05);
        }
        body.sidebar-dark .sidebar .sidebar-body .nav .nav-item .nav-link.active {
            color: #38bdf8;
            font-weight: 700;
            background: rgba(56, 189, 248, 0.12);
            border-left: 3px solid #38bdf8;
        }
        body.sidebar-dark .sidebar .sidebar-body .nav .nav-item .nav-link i.link-icon {
            width: 18px;
            height: 18px;
            margin-right: 12px;
        }
        .nav-category-badge {
            font-size: 0.68rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            margin-left: auto;
        }
        .bg-mars {
            background: rgba(239, 68, 68, 0.2);
            color: #f87171;
            border: 1px solid rgba(239, 68, 68, 0.4);
        }
        .bg-saturnus {
            background: rgba(56, 189, 248, 0.2);
            color: #38bdf8;
            border: 1px solid rgba(56, 189, 248, 0.4);
        }
        .bg-settings {
            background: rgba(168, 85, 247, 0.2);
            color: #c084fc;
            border: 1px solid rgba(168, 85, 247, 0.4);
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
<body class="sidebar-dark">
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

    @yield('scripts')
</body>
</html>
