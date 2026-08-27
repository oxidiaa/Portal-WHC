@php
    $user = auth()->user();
    $userRole = strtoupper(trim($user->role ?? 'GUEST'));
    $isMasterOrAdmin = in_array($userRole, ['MASTER', 'ADMIN']) || ($user && $user->isMaster());
    
    $isMarsActive = request()->routeIs('mars.*') || request()->routeIs('item_master.*') || request()->routeIs('data_po.*') || request()->routeIs('item_minim.*') || request()->routeIs('item_outstanding.*') || request()->routeIs('kedatangan_barang.*') || request()->routeIs('history.*');
    $isSaturnusActive = request()->routeIs('saturnus.*') || request()->routeIs('form_registrasi*') || request()->routeIs('form_unregistrasi*');
    $isSettingsActive = request()->routeIs('settings.*');
@endphp

<!-- Simple Tree-Style Light Sidebar (Matching Reference) -->
<nav class="sidebar simple-tree-sidebar">
    <!-- Brand Header -->
    <div class="sidebar-header-simple">
        <a href="{{ route('dashboard.index') }}" class="brand-link-simple">
            <img src="{{ asset('assets/images/logo.png') }}" alt="PT Metalart Astra Indonesia" class="brand-logo-simple" onerror="this.src='{{ asset('assets/images/MAI.png') }}'">
        </a>
    </div>

    <!-- Navigation Scroll Area -->
    <div class="sidebar-scroll-simple">
        
        {{-- 1. STANDALONE TOP MENU ITEMS --}}
        <div class="nav-tree-standalone">
            <a href="{{ route('dashboard.index') }}" class="nav-tree-item {{ request()->routeIs('dashboard.index') ? 'active-tree-item' : '' }}">
                <i data-feather="grid" class="nav-tree-icon"></i>
                <span class="nav-tree-text">Dashboard</span>
            </a>
        </div>

        {{-- 2. COMPLIANCE / MARS GROUP --}}
        <div class="nav-tree-group">
            <div class="nav-tree-header" data-bs-toggle="collapse" data-bs-target="#collapseMars" aria-expanded="{{ $isMarsActive ? 'true' : 'false' }}">
                <div class="header-left">
                    <i data-feather="package" class="group-icon"></i>
                    <span class="group-title">MARS (STOCK MINIM)</span>
                </div>
                <span class="group-caret">▾</span>
            </div>
            
            <div class="collapse {{ $isMarsActive ? 'show' : '' }}" id="collapseMars">
                <div class="nav-tree-sublist">
                    <a href="{{ route('mars.dashboard') }}" class="sub-tree-link {{ request()->routeIs('mars.dashboard') ? 'active-tree-item' : '' }}">
                        @if(request()->routeIs('mars.dashboard'))<span class="active-bar-indicator"></span>@endif
                        <span class="sub-tree-text">Dashboard MARS</span>
                    </a>
                    <a href="{{ route('mars.item_master.index') }}" class="sub-tree-link {{ request()->routeIs('mars.item_master.*') || request()->routeIs('item_master.*') ? 'active-tree-item' : '' }}">
                        @if(request()->routeIs('mars.item_master.*') || request()->routeIs('item_master.*'))<span class="active-bar-indicator"></span>@endif
                        <span class="sub-tree-text">Data Master Item</span>
                    </a>
                    <a href="{{ route('mars.data_po.index') }}" class="sub-tree-link {{ request()->routeIs('mars.data_po.*') || request()->routeIs('data_po.*') ? 'active-tree-item' : '' }}">
                        @if(request()->routeIs('mars.data_po.*') || request()->routeIs('data_po.*'))<span class="active-bar-indicator"></span>@endif
                        <span class="sub-tree-text">Data PO</span>
                    </a>
                    @if($isMasterOrAdmin || in_array(strtolower($user->username ?? ''), ['master', 'admin']))
                    <a href="{{ route('mars.item_outstanding.index') }}" class="sub-tree-link {{ request()->routeIs('mars.item_outstanding.*') || request()->routeIs('item_outstanding.*') ? 'active-tree-item' : '' }}">
                        @if(request()->routeIs('mars.item_outstanding.*') || request()->routeIs('item_outstanding.*'))<span class="active-bar-indicator"></span>@endif
                        <span class="sub-tree-text">Item Outstanding</span>
                    </a>
                    @endif
                    <a href="{{ route('mars.item_minim.index') }}" class="sub-tree-link {{ request()->routeIs('mars.item_minim.*') || request()->routeIs('item_minim.*') ? 'active-tree-item' : '' }}">
                        @if(request()->routeIs('mars.item_minim.*') || request()->routeIs('item_minim.*'))<span class="active-bar-indicator"></span>@endif
                        <span class="sub-tree-text">Item Minim (Order Point)</span>
                    </a>
                    @if($isMasterOrAdmin || in_array(strtolower($user->username ?? ''), ['master', 'whc', 'warehouse', 'admin']))
                    <a href="{{ route('mars.kedatangan_barang.index') }}" class="sub-tree-link {{ request()->routeIs('mars.kedatangan_barang.*') || request()->routeIs('kedatangan_barang.*') ? 'active-tree-item' : '' }}">
                        @if(request()->routeIs('mars.kedatangan_barang.*') || request()->routeIs('kedatangan_barang.*'))<span class="active-bar-indicator"></span>@endif
                        <span class="sub-tree-text">Kedatangan Barang</span>
                    </a>
                    @endif
                    <a href="{{ route('mars.history.index') }}" class="sub-tree-link {{ request()->routeIs('mars.history.*') || request()->routeIs('history.*') ? 'active-tree-item' : '' }}">
                        @if(request()->routeIs('mars.history.*') || request()->routeIs('history.*'))<span class="active-bar-indicator"></span>@endif
                        <span class="sub-tree-text">History Kedatangan</span>
                    </a>
                </div>
            </div>
        </div>

        {{-- 3. MONITORING / SATURNUS GROUP --}}
        <div class="nav-tree-group">
            <div class="nav-tree-header" data-bs-toggle="collapse" data-bs-target="#collapseSaturnus" aria-expanded="{{ $isSaturnusActive ? 'true' : 'false' }}">
                <div class="header-left">
                    <i data-feather="disc" class="group-icon"></i>
                    <span class="group-title">SATURNUS (CONSUMABLE)</span>
                </div>
                <span class="group-caret">▾</span>
            </div>
            
            <div class="collapse {{ $isSaturnusActive ? 'show' : '' }}" id="collapseSaturnus">
                <div class="nav-tree-sublist">
                    <a href="{{ route('saturnus.dashboard') }}" class="sub-tree-link {{ request()->routeIs('saturnus.dashboard') ? 'active-tree-item' : '' }}">
                        @if(request()->routeIs('saturnus.dashboard'))<span class="active-bar-indicator"></span>@endif
                        <span class="sub-tree-text">Dashboard SATURNUS</span>
                    </a>
                    <a href="{{ route('saturnus.form_registrasi') }}" class="sub-tree-link {{ request()->routeIs('saturnus.form_registrasi*') ? 'active-tree-item' : '' }}">
                        @if(request()->routeIs('saturnus.form_registrasi*'))<span class="active-bar-indicator"></span>@endif
                        <span class="sub-tree-text">Form Registrasi Baru</span>
                    </a>
                    <a href="{{ route('saturnus.form_unregistrasi') }}" class="sub-tree-link {{ request()->routeIs('saturnus.form_unregistrasi*') ? 'active-tree-item' : '' }}">
                        @if(request()->routeIs('saturnus.form_unregistrasi*'))<span class="active-bar-indicator"></span>@endif
                        <span class="sub-tree-text">Form Unregistrasi</span>
                    </a>
                </div>
            </div>
        </div>

        {{-- 4. ORGANIZATION / ADMINISTRASI GROUP --}}
        @if($isMasterOrAdmin || in_array(strtolower($user->username ?? ''), ['master', 'admin']))
        <div class="nav-tree-group">
            <div class="nav-tree-header" data-bs-toggle="collapse" data-bs-target="#collapseSettings" aria-expanded="{{ $isSettingsActive ? 'true' : 'false' }}">
                <div class="header-left">
                    <i data-feather="shield" class="group-icon"></i>
                    <span class="group-title">ADMINISTRASI</span>
                </div>
                <span class="group-caret">▾</span>
            </div>
            
            <div class="collapse {{ $isSettingsActive ? 'show' : '' }}" id="collapseSettings">
                <div class="nav-tree-sublist">
                    <a href="{{ route('settings.users.index') }}" class="sub-tree-link {{ request()->routeIs('settings.users.*') ? 'active-tree-item' : '' }}">
                        @if(request()->routeIs('settings.users.*'))<span class="active-bar-indicator"></span>@endif
                        <span class="sub-tree-text">Manajemen User</span>
                    </a>
                    <a href="{{ route('settings.roles.index') }}" class="sub-tree-link {{ request()->routeIs('settings.roles.*') ? 'active-tree-item' : '' }}">
                        @if(request()->routeIs('settings.roles.*'))<span class="active-bar-indicator"></span>@endif
                        <span class="sub-tree-text">Hak Akses &amp; Role</span>
                    </a>
                </div>
            </div>
        </div>
        @endif

    </div>

    <!-- Sidebar Bottom User Profile Bar -->
    <div class="sidebar-user-footer-simple">
        <div class="d-flex align-items-center gap-2 min-w-0">
            <div class="user-avatar-simple">
                {{ strtoupper(substr($user->name ?? 'U', 0, 1)) }}
            </div>
            <div class="user-text-simple">
                <div class="user-name-simple">{{ $user->name ?? 'User' }}</div>
                <div class="user-role-simple">{{ $userRole }}</div>
            </div>
        </div>
        <a href="javascript:void(0)" class="logout-btn-simple" title="Logout" onclick="event.preventDefault(); document.getElementById('sidebar-logout-form').submit();">
            <i data-feather="log-out" style="width: 16px; height: 16px;"></i>
        </a>
        <form id="sidebar-logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
            @csrf
        </form>
    </div>
</nav>
