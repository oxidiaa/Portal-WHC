@php
    $user = auth()->user();
    $currentRoute = Route::currentRouteName() ?? '';
    $userRole = strtoupper(trim($user->role ?? 'GUEST'));
    $isMasterOrAdmin = in_array($userRole, ['MASTER', 'ADMIN']) || ($user && $user->isMaster());

    $canAccessMars = $user && ($isMasterOrAdmin || $user->canAccessModule('mars'));
    $canAccessSaturnus = $user && ($isMasterOrAdmin || $user->canAccessModule('saturnus'));
    $canAccessSettings = $user && ($isMasterOrAdmin || $user->canAccessModule('settings'));

    $hasNotificationsTable = \Illuminate\Support\Facades\Schema::hasTable('notifications');
    $unreadNotifCount = ($user && $hasNotificationsTable) ? $user->unreadNotifications()->count() : 0;
    $recentNotifications = ($user && $hasNotificationsTable) ? $user->notifications()->latest()->limit(15)->get() : collect();
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

            <!-- In-App Notification Bell Dropdown -->
            @if($user)
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle position-relative d-flex align-items-center justify-content-center rounded-circle shadow-sm"
                   href="#"
                   id="notificationDropdown"
                   role="button"
                   data-bs-toggle="dropdown"
                   aria-haspopup="true"
                   aria-expanded="false"
                   title="Notifikasi Komentar SATURNUS"
                   style="width: 36px; height: 36px; background: #ffffff; border: 1px solid #e2e8f0; padding: 0;">
                    <i data-feather="bell" style="width: 16px; height: 16px; color: #334155;"></i>
                    <span id="notifUnreadBadge"
                          class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger {{ $unreadNotifCount > 0 ? '' : 'd-none' }}"
                          style="font-size: 0.65rem; min-width: 18px; padding: 0.22em 0.45em;">
                        {{ $unreadNotifCount > 99 ? '99+' : $unreadNotifCount }}
                    </span>
                </a>
                <div class="dropdown-menu dropdown-menu-end p-0 shadow-lg border-0" aria-labelledby="notificationDropdown" style="width: 360px; max-width: 92vw; border-radius: 14px; overflow: hidden;">
                    <div class="d-flex align-items-center justify-content-between px-3 py-2.5 border-bottom" style="background: #0f172a; color: #f8fafc;">
                        <div class="d-flex align-items-center gap-2">
                            <span style="font-weight: 700; font-size: 0.85rem;">Notifikasi</span>
                            <span id="notifHeaderCount" class="badge bg-primary" style="font-size: 0.68rem;">{{ $unreadNotifCount }} baru</span>
                        </div>
                        <button type="button"
                                id="markAllNotifBtn"
                                class="btn btn-link btn-sm p-0 text-info text-decoration-none {{ $unreadNotifCount > 0 ? '' : 'd-none' }}"
                                style="font-size: 0.74rem; font-weight: 600;">
                            Tandai semua dibaca
                        </button>
                    </div>
                    <div id="notifDropdownList" style="max-height: 360px; overflow-y: auto; background: #ffffff;">
                        @forelse($recentNotifications as $notif)
                            @php
                                $nData = $notif->data ?? [];
                                $isUnread = $notif->read_at === null;
                                $isUnreg = strtolower($nData['module'] ?? '') === 'unregistrasi';
                            @endphp
                            <a href="{{ route('notifications.open', ['id' => $notif->id]) }}"
                               class="d-block px-3 py-2.5 border-bottom text-decoration-none"
                               style="background: {{ $isUnread ? '#f0f9ff' : '#ffffff' }}; transition: background 0.15s ease;">
                                <div class="d-flex align-items-center justify-content-between gap-2 mb-1">
                                    <div class="d-flex align-items-center gap-1">
                                        <span class="badge" style="font-size: 0.64rem; {{ $isUnreg ? 'background: #fef3c7; color: #92400e;' : 'background: #dcfce7; color: #166534;' }}">
                                            {{ $nData['module'] ?? 'SATURNUS' }}
                                        </span>
                                        <span style="font-size: 0.72rem; font-weight: 700; color: #0f172a;">
                                            {{ $nData['form_number'] ?? '-' }}
                                        </span>
                                    </div>
                                    <div class="d-flex align-items-center gap-1">
                                        <span style="font-size: 0.68rem; color: #64748b;">{{ $notif->created_at?->diffForHumans() }}</span>
                                        @if($isUnread)
                                            <span class="rounded-circle bg-primary d-inline-block" style="width: 7px; height: 7px;"></span>
                                        @endif
                                    </div>
                                </div>
                                <div style="font-size: 0.76rem; font-weight: 700; color: #1e293b;">
                                    {{ $nData['commenter_name'] ?? 'User' }}
                                    @if(!empty($nData['commenter_role']))
                                        <span style="font-weight: 500; color: #64748b;">({{ $nData['commenter_role'] }})</span>
                                    @endif
                                </div>
                                <div style="font-size: 0.76rem; color: #475569; line-height: 1.35; margin-top: 2px;">
                                    "{{ $nData['comment_excerpt'] ?? '' }}"
                                </div>
                            </a>
                        @empty
                            <div class="text-center py-4 px-3" style="color: #64748b; font-size: 0.8rem;">
                                Belum ada notifikasi komentar.
                            </div>
                        @endforelse
                    </div>
                </div>
            </li>
            @endif

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

@if($user)
<script>
document.addEventListener('DOMContentLoaded', function () {
    const badgeEl = document.getElementById('notifUnreadBadge');
    const headerCountEl = document.getElementById('notifHeaderCount');
    const listEl = document.getElementById('notifDropdownList');
    const markAllBtn = document.getElementById('markAllNotifBtn');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    let knownUnreadIds = new Set(@json($recentNotifications->whereNull('read_at')->pluck('id')->values()));

    function esc(str) {
        return String(str ?? '').replace(/[&<>"']/g, function (m) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[m];
        });
    }

    function showLiveNotifToast(item) {
        const container = document.getElementById('toastContainer');
        if (!container) return;

        const toast = document.createElement('a');
        toast.href = item.open_url;
        toast.className = 'mai-toast info text-decoration-none';
        toast.setAttribute('role', 'alert');
        toast.innerHTML = `
            <div style="width: 10px; height: 10px; border-radius: 50%; background: #0284c7; flex-shrink: 0; margin-top: 4px;"></div>
            <div>
                <div style="font-weight: 700; font-size: 0.86rem; color: #0f172a;">
                    Komentar Baru (${esc(item.module)} · ${esc(item.form_number)})
                </div>
                <div style="font-size: 0.78rem; color: #475569;">
                    <strong>${esc(item.commenter_name)}</strong>: "${esc(item.comment_excerpt)}"
                </div>
            </div>
        `;
        container.appendChild(toast);

        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(100%)';
            toast.style.transition = 'all 0.4s ease';
            setTimeout(() => toast.remove(), 400);
        }, 6500);
    }

    function renderNotifications(data) {
        const count = Number(data.unread_count || 0);
        if (badgeEl) {
            badgeEl.textContent = count > 99 ? '99+' : String(count);
            badgeEl.classList.toggle('d-none', count <= 0);
        }
        if (headerCountEl) {
            headerCountEl.textContent = count + ' baru';
        }
        if (markAllBtn) {
            markAllBtn.classList.toggle('d-none', count <= 0);
        }

        if (!listEl) return;

        const items = Array.isArray(data.notifications) ? data.notifications : [];
        if (items.length === 0) {
            listEl.innerHTML = '<div class="text-center py-4 px-3" style="color: #64748b; font-size: 0.8rem;">Belum ada notifikasi komentar.</div>';
            return;
        }

        listEl.innerHTML = items.map(function (item) {
            const isUnreg = String(item.module || '').toLowerCase() === 'unregistrasi';
            const pillStyle = isUnreg ? 'background: #fef3c7; color: #92400e;' : 'background: #dcfce7; color: #166534;';
            const bgStyle = item.read ? '#ffffff' : '#f0f9ff';
            const dot = item.read ? '' : '<span class="rounded-circle bg-primary d-inline-block" style="width: 7px; height: 7px;"></span>';
            const rolePart = item.commenter_role ? `<span style="font-weight: 500; color: #64748b;">(${esc(item.commenter_role)})</span>` : '';

            return `
                <a href="${esc(item.open_url)}" class="d-block px-3 py-2.5 border-bottom text-decoration-none" style="background: ${bgStyle}; transition: background 0.15s ease;">
                    <div class="d-flex align-items-center justify-content-between gap-2 mb-1">
                        <div class="d-flex align-items-center gap-1">
                            <span class="badge" style="font-size: 0.64rem; ${pillStyle}">${esc(item.module)}</span>
                            <span style="font-size: 0.72rem; font-weight: 700; color: #0f172a;">${esc(item.form_number)}</span>
                        </div>
                        <div class="d-flex align-items-center gap-1">
                            <span style="font-size: 0.68rem; color: #64748b;">${esc(item.time_ago)}</span>
                            ${dot}
                        </div>
                    </div>
                    <div style="font-size: 0.76rem; font-weight: 700; color: #1e293b;">
                        ${esc(item.commenter_name)} ${rolePart}
                    </div>
                    <div style="font-size: 0.76rem; color: #475569; line-height: 1.35; margin-top: 2px;">
                        "${esc(item.comment_excerpt)}"
                    </div>
                </a>
            `;
        }).join('');
    }

    async function pollNotifications() {
        if (document.hidden) return;
        try {
            const resp = await fetch('{{ route("notifications.index") }}', {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!resp.ok) return;
            const data = await resp.json();

            const items = Array.isArray(data.notifications) ? data.notifications : [];
            items.forEach(function (item) {
                if (!item.read && !knownUnreadIds.has(item.id)) {
                    knownUnreadIds.add(item.id);
                    showLiveNotifToast(item);
                }
            });

            renderNotifications(data);
        } catch (e) {
            // Silent fail on network glitch
        }
    }

    if (markAllBtn) {
        markAllBtn.addEventListener('click', async function (e) {
            e.preventDefault();
            e.stopPropagation();
            try {
                const resp = await fetch('{{ route("notifications.read_all") }}', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken
                    }
                });
                if (resp.ok) {
                    pollNotifications();
                }
            } catch (e) {}
        });
    }

    setInterval(pollNotifications, 20000);
});
</script>
@endif
