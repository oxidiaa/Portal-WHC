@php
    $userRole = strtoupper(trim(Auth::user()->role ?? 'GUEST'));
    $isMaster = in_array($userRole, ['MASTER', 'ADMIN']) || (Auth::user() && method_exists(Auth::user(), 'isMaster') && Auth::user()->isMaster());
@endphp

<div class="sheet-tabs-container no-print">
    <a href="{{ route('saturnus.form_registrasi') }}" class="sheet-tab {{ request()->routeIs('saturnus.form_registrasi') || request()->routeIs('form-registrasi') ? 'active' : '' }}">
        <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2.5" fill="none">
            <polyline points="6 9 6 2 18 2 18 9"></polyline>
            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
            <rect x="6" y="14" width="12" height="8"></rect>
        </svg>
        <span>Form Registrasi (Lembar Cetak)</span>
    </a>
    <a href="{{ route('saturnus.proses_approval') }}" class="sheet-tab {{ request()->routeIs('saturnus.proses_approval') || request()->routeIs('proses-approval') ? 'active' : '' }}">
        <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2.5" fill="none">
            <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
        </svg>
        <span>Proses Approval</span>
    </a>
    <a href="{{ route('saturnus.data_view') }}" class="sheet-tab {{ request()->routeIs('saturnus.data_view') || request()->routeIs('data-view') ? 'active' : '' }}">
        <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2.5" fill="none">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
            <polyline points="14 2 14 8 20 8"></polyline>
            <line x1="16" y1="13" x2="8" y2="13"></line>
            <line x1="16" y1="17" x2="8" y2="17"></line>
            <polyline points="10 9 9 9 8 9"></polyline>
        </svg>
        <span>Data View Explorer</span>
    </a>
    @if($isMaster)
    <a href="{{ route('saturnus.account_master') }}" class="sheet-tab {{ request()->routeIs('saturnus.account_master') || request()->routeIs('account-master') ? 'active' : '' }}">
        <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2.5" fill="none">
            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
            <circle cx="9" cy="7" r="4"></circle>
            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
        </svg>
        <span>Data Account Master</span>
    </a>
    @endif
</div>
