@php
    $user = auth()->user();
    $userRole = strtoupper(trim($user->role ?? 'GUEST'));
    $isMasterOrAdmin = in_array($userRole, ['MASTER', 'ADMIN']) || ($user && $user->isMaster());
    
    $canAccessMars = $user && ($isMasterOrAdmin || $user->canAccessModule('mars') || $user->hasPermission('mars.*'));
    $canAccessSaturnus = $user && ($isMasterOrAdmin || $user->canAccessModule('saturnus') || $user->hasPermission('saturnus.*'));
    $canAccessSettings = $user && ($isMasterOrAdmin || $user->canAccessModule('settings') || $user->hasPermission('settings.*'));

    $isMarsActive = request()->routeIs('mars.*') || request()->routeIs('item_master.*') || request()->routeIs('data_po.*') || request()->routeIs('item_minim.*') || request()->routeIs('item_outstanding.*') || request()->routeIs('kedatangan_barang.*') || request()->routeIs('history.*');
    $isSaturnusActive = request()->routeIs('saturnus.*') || request()->routeIs('form_registrasi*') || request()->routeIs('form-registrasi*') || request()->routeIs('form_unregistrasi*') || request()->routeIs('form-unregistrasi*') || request()->routeIs('proses-approval*') || request()->routeIs('data-view*');
    $isSettingsActive = request()->routeIs('settings.*');
@endphp

<!-- Simple Tree-Style Light Sidebar (Matching Reference) -->
<nav class="sidebar simple-tree-sidebar">
    <!-- Brand Header -->
    <div class="sidebar-header-simple">
        <a href="{{ route('dashboard.index') }}" class="brand-link-simple" title="PT Metalart Astra Indonesia">
            <img src="{{ asset('assets/images/logo_mai_dark.png') }}?v={{ time() }}" alt="PT Metalart Astra Indonesia" class="brand-logo-simple logo-dark-version" onerror="this.src='{{ asset('assets/images/MAI GELAP.png') }}'">
            <img src="{{ asset('assets/images/logo_mai_light.png') }}?v={{ time() }}" alt="PT Metalart Astra Indonesia" class="brand-logo-simple logo-light-version" onerror="this.src='{{ asset('assets/images/MAI TERANG.png') }}'">
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
        @if($canAccessMars)
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
                    @if($isMasterOrAdmin || $user->hasPermission('mars.dashboard.view'))
                    <a href="{{ route('mars.dashboard') }}" class="sub-tree-link {{ request()->routeIs('mars.dashboard') ? 'active-tree-item' : '' }}">
                        @if(request()->routeIs('mars.dashboard'))<span class="active-bar-indicator"></span>@endif
                        <span class="sub-tree-text">Dashboard MARS</span>
                    </a>
                    @endif

                    @if($isMasterOrAdmin || $user->hasPermission('mars.master.view'))
                    <a href="{{ route('mars.item_master.index') }}" class="sub-tree-link {{ request()->routeIs('mars.item_master.*') || request()->routeIs('item_master.*') ? 'active-tree-item' : '' }}">
                        @if(request()->routeIs('mars.item_master.*') || request()->routeIs('item_master.*'))<span class="active-bar-indicator"></span>@endif
                        <span class="sub-tree-text">Data Master Item</span>
                    </a>
                    @endif

                    @if($isMasterOrAdmin || $user->hasPermission('mars.po.view'))
                    <a href="{{ route('mars.data_po.index') }}" class="sub-tree-link {{ request()->routeIs('mars.data_po.*') || request()->routeIs('data_po.*') ? 'active-tree-item' : '' }}">
                        @if(request()->routeIs('mars.data_po.*') || request()->routeIs('data_po.*'))<span class="active-bar-indicator"></span>@endif
                        <span class="sub-tree-text">Data PO</span>
                    </a>
                    @endif

                    @if($isMasterOrAdmin)
                    <a href="{{ route('mars.item_outstanding.index') }}" class="sub-tree-link {{ request()->routeIs('mars.item_outstanding.*') || request()->routeIs('item_outstanding.*') ? 'active-tree-item' : '' }}">
                        @if(request()->routeIs('mars.item_outstanding.*') || request()->routeIs('item_outstanding.*'))<span class="active-bar-indicator"></span>@endif
                        <span class="sub-tree-text">Item Outstanding</span>
                    </a>
                    @endif

                    @if($isMasterOrAdmin || $user->hasPermission('mars.minim.view'))
                    <a href="{{ route('mars.item_minim.index') }}" class="sub-tree-link {{ request()->routeIs('mars.item_minim.*') || request()->routeIs('item_minim.*') ? 'active-tree-item' : '' }}">
                        @if(request()->routeIs('mars.item_minim.*') || request()->routeIs('item_minim.*'))<span class="active-bar-indicator"></span>@endif
                        <span class="sub-tree-text">Item Minim (Order Point)</span>
                    </a>
                    @endif

                    @if($isMasterOrAdmin || $user->hasPermission('mars.kedatangan.view'))
                    <a href="{{ route('mars.kedatangan_barang.index') }}" class="sub-tree-link {{ request()->routeIs('mars.kedatangan_barang.*') || request()->routeIs('kedatangan_barang.*') ? 'active-tree-item' : '' }}">
                        @if(request()->routeIs('mars.kedatangan_barang.*') || request()->routeIs('kedatangan_barang.*'))<span class="active-bar-indicator"></span>@endif
                        <span class="sub-tree-text">Kedatangan Barang</span>
                    </a>
                    @endif

                    @if($isMasterOrAdmin || $user->hasPermission('mars.history.view'))
                    <a href="{{ route('mars.history.index') }}" class="sub-tree-link {{ request()->routeIs('mars.history.*') || request()->routeIs('history.*') ? 'active-tree-item' : '' }}">
                        @if(request()->routeIs('mars.history.*') || request()->routeIs('history.*'))<span class="active-bar-indicator"></span>@endif
                        <span class="sub-tree-text">History Kedatangan</span>
                    </a>
                    @endif
                </div>
            </div>
        </div>
        @endif

        {{-- 3. MONITORING / SATURNUS GROUP --}}
        @if($canAccessSaturnus)
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
                    @if($isMasterOrAdmin || $user->hasPermission('saturnus.directory.view'))
                    <a href="{{ route('saturnus.dashboard') }}" class="sub-tree-link {{ request()->routeIs('saturnus.dashboard') ? 'active-tree-item' : '' }}">
                        @if(request()->routeIs('saturnus.dashboard'))<span class="active-bar-indicator"></span>@endif
                        <span class="sub-tree-text">Dashboard SATURNUS</span>
                    </a>
                    @endif

                    @if($isMasterOrAdmin || $user->hasPermission('saturnus.registrasi.view'))
                    <a href="{{ route('saturnus.form_registrasi') }}" class="sub-tree-link {{ request()->routeIs('saturnus.form_registrasi') || request()->routeIs('form-registrasi') ? 'active-tree-item' : '' }}">
                        @if(request()->routeIs('saturnus.form_registrasi') || request()->routeIs('form-registrasi'))<span class="active-bar-indicator"></span>@endif
                        <span class="sub-tree-text">Form Registrasi</span>
                    </a>
                    @endif

                    @if($isMasterOrAdmin || $user->hasPermission('saturnus.registrasi.approve') || $user->hasPermission('saturnus.registrasi.view'))
                    <a href="{{ route('saturnus.proses_approval') }}" class="sub-tree-link {{ request()->routeIs('saturnus.proses_approval') || request()->routeIs('proses-approval') ? 'active-tree-item' : '' }}">
                        @if(request()->routeIs('saturnus.proses_approval') || request()->routeIs('proses-approval'))<span class="active-bar-indicator"></span>@endif
                        <span class="sub-tree-text">Proses Approval</span>
                    </a>
                    @endif

                    @if($isMasterOrAdmin || $user->hasPermission(['saturnus.directory.view', 'saturnus.registrasi.view']))
                    <a href="{{ route('saturnus.data_view') }}" class="sub-tree-link {{ request()->routeIs('saturnus.data_view') || request()->routeIs('data-view') ? 'active-tree-item' : '' }}">
                        @if(request()->routeIs('saturnus.data_view') || request()->routeIs('data-view'))<span class="active-bar-indicator"></span>@endif
                        <span class="sub-tree-text">Data View Explorer</span>
                    </a>
                    @endif

                    @if($isMasterOrAdmin || $user->hasPermission('saturnus.unregistrasi.view'))
                    <a href="{{ route('saturnus.form_unregistrasi') }}" class="sub-tree-link {{ request()->routeIs('saturnus.form_unregistrasi*') || request()->routeIs('form-unregistrasi*') ? 'active-tree-item' : '' }}">
                        @if(request()->routeIs('saturnus.form_unregistrasi*') || request()->routeIs('form-unregistrasi*'))<span class="active-bar-indicator"></span>@endif
                        <span class="sub-tree-text">Form Unregistrasi</span>
                    </a>
                    @endif
                </div>
            </div>
        </div>
        @endif

        {{-- 4. ORGANIZATION / ADMINISTRASI GROUP --}}
        @if($canAccessSettings)
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
                    @if($isMasterOrAdmin || $user->hasPermission('settings.users.manage'))
                    <a href="{{ route('settings.users.index') }}" class="sub-tree-link {{ request()->routeIs('settings.users.*') ? 'active-tree-item' : '' }}">
                        @if(request()->routeIs('settings.users.*'))<span class="active-bar-indicator"></span>@endif
                        <span class="sub-tree-text">Manajemen User</span>
                    </a>
                    @endif

                    @if($isMasterOrAdmin || $user->hasPermission('settings.roles.manage'))
                    <a href="{{ route('settings.roles.index') }}" class="sub-tree-link {{ request()->routeIs('settings.roles.*') ? 'active-tree-item' : '' }}">
                        @if(request()->routeIs('settings.roles.*'))<span class="active-bar-indicator"></span>@endif
                        <span class="sub-tree-text">Hak Akses &amp; Role</span>
                    </a>
                    @endif
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
