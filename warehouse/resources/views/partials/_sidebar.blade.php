@php
    $user = auth()->user();
    $userRole = strtoupper(trim($user->role ?? 'GUEST'));
    $isMasterOrAdmin = in_array($userRole, ['MASTER', 'ADMIN']) || ($user && $user->isMaster());
@endphp

<!-- partial:partials/_sidebar.html -->
<nav class="sidebar">
    <div class="sidebar-header" style="background: #0f172a; border-bottom: 1px solid rgba(255,255,255,0.08); padding: 0.85rem 1.25rem;">
        <a href="{{ route('dashboard.index') }}" class="sidebar-brand" style="display: flex; align-items: center; gap: 0.65rem; text-decoration: none;">
            <img src="{{ asset('assets/images/MAI TERANG.png') }}" alt="MAI Logo" style="height: 30px; object-fit: contain;">
            <div>
                <div style="font-family: 'Outfit', sans-serif; font-size: 0.95rem; font-weight: 800; color: #ffffff; letter-spacing: -0.01em; line-height: 1.1;">
                    PORTAL <span style="color: #38bdf8;">WAREHOUSE</span>
                </div>
                <div style="font-size: 0.65rem; font-weight: 700; color: #94a3b8; letter-spacing: 0.05em;">
                    PT MEIWA INDONESIA
                </div>
            </div>
        </a>
        <div class="sidebar-toggler not-active">
            <span></span>
            <span></span>
            <span></span>
        </div>
    </div>

    <div class="sidebar-body" style="background: #0b1120;">
        <ul class="nav">

            {{-- 1. PORTAL UTAMA --}}
            <li class="nav-item nav-category" style="color: #64748b; font-size: 0.7rem; font-weight: 800; letter-spacing: 0.08em; padding: 1.2rem 1.5rem 0.4rem;">
                SISTEM UTAMA
            </li>
            <li class="nav-item">
                <a href="{{ route('dashboard.index') }}" class="nav-link {{ request()->routeIs('dashboard*') && !request()->routeIs('mars.dashboard') && !request()->routeIs('saturnus.dashboard') ? 'active' : '' }}">
                    <i class="link-icon" data-feather="grid"></i>
                    <span class="link-title">Dashboard Utama</span>
                </a>
            </li>

            {{-- 2. MODUL MARS --}}
            <li class="nav-item nav-category" style="color: #f87171; font-size: 0.7rem; font-weight: 800; letter-spacing: 0.08em; padding: 1.2rem 1.5rem 0.4rem; display: flex; align-items: center; justify-content: space-between;">
                <span>MODUL MARS</span>
                <span class="nav-category-badge bg-mars">STOCK MINIM</span>
            </li>
            <li class="nav-item">
                <a href="{{ route('mars.dashboard') }}" class="nav-link {{ request()->routeIs('mars.dashboard') ? 'active' : '' }}">
                    <i class="link-icon" data-feather="bar-chart-2"></i>
                    <span class="link-title">Dashboard MARS</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('mars.item_master.index') }}" class="nav-link {{ request()->routeIs('mars.item_master.*') || request()->routeIs('item_master.*') ? 'active' : '' }}">
                    <i class="link-icon" data-feather="database"></i>
                    <span class="link-title">Data Master Item</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('mars.data_po.index') }}" class="nav-link {{ request()->routeIs('mars.data_po.*') || request()->routeIs('data_po.*') ? 'active' : '' }}">
                    <i class="link-icon" data-feather="file-text"></i>
                    <span class="link-title">Data PO</span>
                </a>
            </li>
            @if($isMasterOrAdmin || in_array(strtolower($user->username ?? ''), ['master', 'admin']))
            <li class="nav-item">
                <a href="{{ route('mars.item_outstanding.index') }}" class="nav-link {{ request()->routeIs('mars.item_outstanding.*') || request()->routeIs('item_outstanding.*') ? 'active' : '' }}">
                    <i class="link-icon" data-feather="layers"></i>
                    <span class="link-title">Item Outstanding</span>
                </a>
            </li>
            @endif
            <li class="nav-item">
                <a href="{{ route('mars.item_minim.index') }}" class="nav-link {{ request()->routeIs('mars.item_minim.*') || request()->routeIs('item_minim.*') ? 'active' : '' }}">
                    <i class="link-icon" data-feather="alert-triangle"></i>
                    <span class="link-title">Item Minim (Order Point)</span>
                </a>
            </li>
            @if($isMasterOrAdmin || in_array(strtolower($user->username ?? ''), ['master', 'whc', 'warehouse', 'admin']))
            <li class="nav-item">
                <a href="{{ route('mars.kedatangan_barang.index') }}" class="nav-link {{ request()->routeIs('mars.kedatangan_barang.*') || request()->routeIs('kedatangan_barang.*') ? 'active' : '' }}">
                    <i class="link-icon" data-feather="truck"></i>
                    <span class="link-title">Kedatangan Barang</span>
                </a>
            </li>
            @endif
            <li class="nav-item">
                <a href="{{ route('mars.history.index') }}" class="nav-link {{ request()->routeIs('mars.history.*') || request()->routeIs('history.*') ? 'active' : '' }}">
                    <i class="link-icon" data-feather="clock"></i>
                    <span class="link-title">History Kedatangan</span>
                </a>
            </li>

            {{-- 3. MODUL SATURNUS --}}
            <li class="nav-item nav-category" style="color: #38bdf8; font-size: 0.7rem; font-weight: 800; letter-spacing: 0.08em; padding: 1.2rem 1.5rem 0.4rem; display: flex; align-items: center; justify-content: space-between;">
                <span>MODUL SATURNUS</span>
                <span class="nav-category-badge bg-saturnus">CONSUMABLE</span>
            </li>
            <li class="nav-item">
                <a href="{{ route('saturnus.dashboard') }}" class="nav-link {{ request()->routeIs('saturnus.dashboard') ? 'active' : '' }}">
                    <i class="link-icon" data-feather="globe"></i>
                    <span class="link-title">Dashboard SATURNUS</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('saturnus.form_registrasi') }}" class="nav-link {{ request()->routeIs('saturnus.form_registrasi*') || request()->routeIs('form-registrasi*') ? 'active' : '' }}">
                    <i class="link-icon" data-feather="file-plus"></i>
                    <span class="link-title">Form Registrasi Baru</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('saturnus.form_unregistrasi') }}" class="nav-link {{ request()->routeIs('saturnus.form_unregistrasi*') || request()->routeIs('form-unregistrasi*') ? 'active' : '' }}">
                    <i class="link-icon" data-feather="file-minus"></i>
                    <span class="link-title">Form Unregistrasi</span>
                </a>
            </li>

            {{-- 4. PENGATURAN & AKUN --}}
            @if($isMasterOrAdmin || in_array(strtolower($user->username ?? ''), ['master', 'admin']))
            <li class="nav-item nav-category" style="color: #c084fc; font-size: 0.7rem; font-weight: 800; letter-spacing: 0.08em; padding: 1.2rem 1.5rem 0.4rem; display: flex; align-items: center; justify-content: space-between;">
                <span>PENGATURAN</span>
                <span class="nav-category-badge bg-settings">ADMIN</span>
            </li>
            <li class="nav-item">
                <a href="{{ route('settings.users.index') }}" class="nav-link {{ request()->routeIs('settings.users.*') ? 'active' : '' }}">
                    <i class="link-icon" data-feather="users"></i>
                    <span class="link-title">Manajemen User</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('settings.roles.index') }}" class="nav-link {{ request()->routeIs('settings.roles.*') ? 'active' : '' }}">
                    <i class="link-icon" data-feather="shield"></i>
                    <span class="link-title">Hak Akses &amp; Role</span>
                </a>
            </li>
            @endif

            {{-- 5. LOGOUT ACTION --}}
            <li class="nav-item nav-category" style="color: #64748b; font-size: 0.7rem; font-weight: 800; letter-spacing: 0.08em; padding: 1.2rem 1.5rem 0.4rem;">
                AKUN SAYA
            </li>
            <li class="nav-item">
                <a href="javascript:void(0)" class="nav-link" onclick="event.preventDefault(); document.getElementById('sidebar-logout-form').submit();" style="color: #ef4444 !important;">
                    <i class="link-icon text-danger" data-feather="log-out"></i>
                    <span class="link-title">Keluar (Logout)</span>
                </a>
                <form id="sidebar-logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                    @csrf
                </form>
            </li>

        </ul>
    </div>
</nav>
