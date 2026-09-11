@php
    $user = auth()->user();
    $currentRoute = Route::currentRouteName() ?? '';
    $userRole = strtoupper(trim($user->role ?? 'GUEST'));
    $isMasterOrAdmin = in_array($userRole, ['MASTER', 'ADMIN']) || ($user && $user->isMaster());

    $canAccessMars = $user && ($isMasterOrAdmin || $user->canAccessModule('mars') || $user->hasPermission('mars.*'));
    $canAccessSaturnus = $user && ($isMasterOrAdmin || $user->canAccessModule('saturnus') || $user->hasPermission('saturnus.*'));
    $canAccessSettings = $user && ($isMasterOrAdmin || $user->canAccessModule('settings') || $user->hasPermission('settings.*'));
@endphp

<!-- partial:partials/_navbar.html -->
<nav class="navbar">
    <a href="#" class="sidebar-toggler">
        <i data-feather="menu"></i>
    </a>
    <div class="navbar-content" style="display: flex; align-items: center; justify-content: space-between;">
        
        <!-- Portal Quick Switcher Pills -->
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('dashboard.index') }}" class="portal-pill {{ request()->routeIs('dashboard.index') ? 'active-main' : '' }}">
                <i data-feather="grid" style="width: 14px; height: 14px;"></i>
                <span>Dashboard Utama</span>
            </a>

            @if($canAccessMars)
            <a href="{{ route('mars.dashboard') }}" class="portal-pill {{ str_starts_with($currentRoute, 'mars.') || str_starts_with($currentRoute, 'item_') ? 'active-mars' : '' }}">
                <i data-feather="package" style="width: 14px; height: 14px;"></i>
                <span>Modul MARS</span>
            </a>
            @endif

            @if($canAccessSaturnus)
            <a href="{{ route('saturnus.dashboard') }}" class="portal-pill {{ str_starts_with($currentRoute, 'saturnus.') || str_starts_with($currentRoute, 'form-') ? 'active-saturnus' : '' }}">
                <i data-feather="globe" style="width: 14px; height: 14px;"></i>
                <span>Modul SATURNUS</span>
            </a>
            @endif
        </div>

        <ul class="navbar-nav align-items-center gap-3">
            <!-- Live Date & Clock -->
            <li class="nav-item d-none d-md-flex align-items-center text-muted" style="font-size: 0.82rem; font-weight: 600;">
                <i data-feather="calendar" class="me-1" style="width: 16px; height: 16px;"></i>
                <span>{{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }}</span>
            </li>

            <!-- Quick Theme Switcher Button -->
            <li class="nav-item">
                <button type="button" class="btn btn-icon btn-light rounded-circle shadow-sm" id="topbarThemeQuickToggle" title="Ganti Tema (Terang / Gelap / Kosmik)" onclick="document.getElementById('themeCustomizerTrigger')?.click()" style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; border: 1px solid #e2e8f0;">
                    <i data-feather="sun" id="topbarThemeIcon" style="width: 16px; height: 16px; color: #f59e0b;"></i>
                </button>
            </li>

            <!-- User Profile Dropdown -->
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="profileDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="display: flex; align-items: center; gap: 0.5rem;">
                    @if($user)
                        <div class="wd-35 ht-35 rounded-circle d-flex align-items-center justify-content-center bg-primary text-white" style="font-weight: 800; font-size: 0.85rem; box-shadow: 0 2px 8px rgba(101, 113, 255, 0.4);">
                            {{ strtoupper(substr($user->name, 0, 2)) }}
                        </div>
                    @else
                        <img class="wd-35 ht-35 rounded-circle" src="{{ asset('assets/images/faces/face1.jpg') }}" alt="profile">
                    @endif
                </a>
                <div class="dropdown-menu p-0" aria-labelledby="profileDropdown" style="min-width: 240px;">
                    <div class="d-flex flex-column align-items-center border-bottom px-4 py-3" style="background: #f8fafc;">
                        <div class="mb-2">
                            <div class="wd-60 ht-60 rounded-circle d-flex align-items-center justify-content-center bg-primary text-white" style="font-size: 1.4rem; font-weight: 800;">
                                {{ strtoupper(substr($user->name ?? 'U', 0, 2)) }}
                            </div>
                        </div>
                        <div class="text-center">
                            <p class="tx-14 fw-bolder mb-0 text-dark">{{ $user->name ?? 'Pengguna' }}</p>
                            <p class="tx-12 text-muted mb-1">{{ $user->email ?? '-' }}</p>
                            <span class="badge bg-primary" style="font-size: 0.7rem;">{{ $user->role ?? 'User' }} · {{ $user->department ?? 'General' }}</span>
                        </div>
                    </div>
                    <ul class="list-unstyled p-1 px-2 mb-0">
                        <li class="dropdown-item py-2">
                            <a href="{{ route('dashboard.index') }}" class="text-body ms-0 d-flex align-items-center">
                                <i class="me-2 icon-md" data-feather="grid"></i>
                                <span>Dashboard Utama</span>
                            </a>
                        </li>
                        @if($canAccessMars)
                        <li class="dropdown-item py-2">
                            <a href="{{ route('mars.dashboard') }}" class="text-body ms-0 d-flex align-items-center">
                                <i class="me-2 icon-md" data-feather="bar-chart-2"></i>
                                <span>Dashboard MARS</span>
                            </a>
                        </li>
                        @endif
                        @if($canAccessSaturnus)
                        <li class="dropdown-item py-2">
                            <a href="{{ route('saturnus.dashboard') }}" class="text-body ms-0 d-flex align-items-center">
                                <i class="me-2 icon-md" data-feather="globe"></i>
                                <span>Dashboard SATURNUS</span>
                            </a>
                        </li>
                        @endif
                        @if($canAccessSettings)
                        <li class="dropdown-item py-2">
                            <a href="{{ route('settings.users.index') }}" class="text-body ms-0 d-flex align-items-center">
                                <i class="me-2 icon-md" data-feather="users"></i>
                                <span>Manajemen User</span>
                            </a>
                        </li>
                        @endif
                        <li class="dropdown-item py-2 border-top">
                            <a href="javascript:void(0)" class="text-danger ms-0 d-flex align-items-center" onclick="event.preventDefault(); document.getElementById('navbar-logout-form').submit();">
                                <i class="me-2 icon-md text-danger" data-feather="log-out"></i>
                                <span>Keluar (Logout)</span>
                            </a>
                            <form id="navbar-logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                                @csrf
                            </form>
                        </li>
                    </ul>
                </div>
            </li>
        </ul>
    </div>
</nav>
