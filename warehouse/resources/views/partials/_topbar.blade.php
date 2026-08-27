@php
    $user = auth()->user();
    $currentRoute = Route::currentRouteName() ?? '';
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
            <a href="{{ route('mars.dashboard') }}" class="portal-pill {{ str_starts_with($currentRoute, 'mars.') || str_starts_with($currentRoute, 'item_') ? 'active-mars' : '' }}">
                <i data-feather="package" style="width: 14px; height: 14px;"></i>
                <span>Modul MARS</span>
            </a>
            <a href="{{ route('saturnus.dashboard') }}" class="portal-pill {{ str_starts_with($currentRoute, 'saturnus.') || str_starts_with($currentRoute, 'form-') ? 'active-saturnus' : '' }}">
                <i data-feather="globe" style="width: 14px; height: 14px;"></i>
                <span>Modul SATURNUS</span>
            </a>
        </div>

        <ul class="navbar-nav align-items-center gap-3">
            <!-- Live Date & Clock -->
            <li class="nav-item d-none d-md-flex align-items-center text-muted" style="font-size: 0.82rem; font-weight: 600;">
                <i data-feather="calendar" class="me-1" style="width: 16px; height: 16px;"></i>
                <span>{{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }}</span>
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
                        <li class="dropdown-item py-2">
                            <a href="{{ route('mars.dashboard') }}" class="text-body ms-0 d-flex align-items-center">
                                <i class="me-2 icon-md" data-feather="bar-chart-2"></i>
                                <span>Dashboard MARS</span>
                            </a>
                        </li>
                        <li class="dropdown-item py-2">
                            <a href="{{ route('saturnus.dashboard') }}" class="text-body ms-0 d-flex align-items-center">
                                <i class="me-2 icon-md" data-feather="globe"></i>
                                <span>Dashboard SATURNUS</span>
                            </a>
                        </li>
                        @if($user && ($user->isMaster() || in_array(strtolower($user->username ?? ''), ['master', 'admin'])))
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
