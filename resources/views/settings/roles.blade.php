@extends('layouts.app')

@section('title', 'Hak Akses & Role Management')
@section('page_title', 'Role & Hak Akses')

@section('content')
<div class="container-fluid p-0">
    <!-- Header Banner -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <div style="display: flex; align-items: center; gap: 0.6rem; margin-bottom: 0.25rem;">
                <span style="font-size: 1.75rem;">🔐</span>
                <h2 style="font-family: var(--font-head); font-size: 1.6rem; font-weight: 800; color: var(--text-main); margin: 0;">
                    Manajemen Role &amp; Hak Akses (RBAC)
                </h2>
            </div>
            <p style="font-size: 0.88rem; color: var(--text-muted); margin: 0;">
                Atur matriks kewenangan modul General, MARS, SATURNUS, dan Pengaturan Sistem untuk setiap tingkatan pengguna.
            </p>
        </div>
        <div style="display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap;">
            <!-- View Switcher Pills -->
            <div style="background: #ffffff; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 0.25rem; display: flex; gap: 0.25rem; box-shadow: var(--shadow-sm);">
                <a href="{{ route('settings.roles.index', ['view' => 'tabs', 'role_id' => $activeRole->id ?? 1]) }}" 
                   class="btn-mai btn-mai-sm {{ ($viewMode ?? 'tabs') === 'tabs' ? 'btn-mai-primary' : 'btn-mai-secondary' }}" 
                   style="border: none; padding: 0.4rem 0.85rem; font-weight: 700;">
                    <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none">
                        <rect x="3" y="3" width="7" height="7"></rect>
                        <rect x="14" y="3" width="7" height="7"></rect>
                        <rect x="14" y="14" width="7" height="7"></rect>
                        <rect x="3" y="14" width="7" height="7"></rect>
                    </svg>
                    <span>Editor Per Role</span>
                </a>
                <a href="{{ route('settings.roles.index', ['view' => 'matrix']) }}" 
                   class="btn-mai btn-mai-sm {{ ($viewMode ?? 'tabs') === 'matrix' ? 'btn-mai-primary' : 'btn-mai-secondary' }}" 
                   style="border: none; padding: 0.4rem 0.85rem; font-weight: 700;">
                    <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none">
                        <line x1="18" y1="20" x2="18" y2="10"></line>
                        <line x1="12" y1="20" x2="12" y2="4"></line>
                        <line x1="6" y1="20" x2="6" y2="14"></line>
                    </svg>
                    <span>Matriks Perbandingan</span>
                </a>
            </div>

            <button type="button" class="btn-mai btn-mai-primary" onclick="openCreateRoleModal()">
                <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2.5" fill="none">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                <span>Tambah Role Baru</span>
            </button>
        </div>
    </div>

    <!-- Stat Metric Cards -->
    <div class="stat-card-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); margin-bottom: 1.5rem;">
        <div class="stat-card">
            <div class="stat-card-icon blue">
                <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                </svg>
            </div>
            <div class="stat-card-details">
                <span class="stat-card-label">Total Role Terdaftar</span>
                <span class="stat-card-value">{{ $totalRoles }}</span>
                <span class="stat-card-sub">Tingkatan kewenangan</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-card-icon purple">
                <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
            </div>
            <div class="stat-card-details">
                <span class="stat-card-label">Total Hak Akses</span>
                <span class="stat-card-value">{{ $totalPermissions }}</span>
                <span class="stat-card-sub">Izin fitur &amp; modul</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-card-icon emerald">
                <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none">
                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                </svg>
            </div>
            <div class="stat-card-details">
                <span class="stat-card-label">Modul Aktif</span>
                <span class="stat-card-value">{{ $totalModules }}</span>
                <span class="stat-card-sub">General, MARS, Saturnus, Settings</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-card-icon orange">
                <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                </svg>
            </div>
            <div class="stat-card-details">
                <span class="stat-card-label">Status Keamanan RBAC</span>
                <span class="stat-card-value" style="color: #10b981; font-size: 1.35rem;">Aktif &amp; Terlindungi</span>
                <span class="stat-card-sub">Role-Based Access Control</span>
            </div>
        </div>
    </div>

    @if(($viewMode ?? 'tabs') === 'matrix')
    <!-- ================================================================= -->
    <!-- VIEW MODE 2: MATRIX COMPARISON OVERVIEW                            -->
    <!-- ================================================================= -->
    <div class="card-mai">
        <div class="card-mai-header" style="background: #ffffff;">
            <div class="card-mai-title">
                <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none" style="color: var(--mai-primary);">
                    <line x1="18" y1="20" x2="18" y2="10"></line>
                    <line x1="12" y1="20" x2="12" y2="4"></line>
                    <line x1="6" y1="20" x2="6" y2="14"></line>
                </svg>
                <span>Matriks Hak Akses Role RBAC (Komprehensif)</span>
            </div>
            <div>
                <input type="text" id="matrixSearchInput" class="form-control form-control-sm" placeholder="🔍 Cari izin fitur..." style="width: 240px; height: 36px;" onkeyup="filterMatrixTable()">
            </div>
        </div>
        <div class="card-mai-body" style="padding: 0;">
            <div class="table-mai-responsive" style="border: none; border-radius: 0;">
                <table class="table-mai" id="matrixTable">
                    <thead>
                        <tr style="position: sticky; top: 0; z-index: 10;">
                            <th style="min-width: 260px; background: #f1f5f9;">Modul &amp; Hak Akses</th>
                            @foreach($roles as $r)
                            @php
                                $rLower = strtolower($r->slug ?? '');
                                $badgeColor = match(true) {
                                    str_contains($rLower, 'master') => '#ef4444',
                                    str_contains($rLower, 'warehouse') => '#f97316',
                                    str_contains($rLower, 'purchasing') => '#3b82f6',
                                    str_contains($rLower, 'accounting') => '#10b981',
                                    default => '#8b5cf6',
                                };
                            @endphp
                            <th style="text-align: center; min-width: 130px; background: #f8fafc; border-left: 1px solid var(--border-subtle);">
                                <div style="font-size: 0.8rem; font-weight: 800; color: {{ $badgeColor }};">
                                    {{ $r->name }}
                                </div>
                                <div style="font-size: 0.68rem; font-family: monospace; color: var(--text-muted); font-weight: normal;">
                                    {{ $r->slug }}
                                </div>
                            </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($permissionsByModule as $modName => $perms)
                        <tr class="matrix-module-header" style="background: #f8fafc; border-top: 2px solid var(--border-color);">
                            <td colspan="{{ $roles->count() + 1 }}" style="font-weight: 800; font-size: 0.82rem; text-transform: uppercase; color: var(--mai-primary); letter-spacing: 0.05em; padding: 0.75rem 1rem;">
                                📁 Modul: {{ strtoupper($modName) }} ({{ $perms->count() }} Izin)
                            </td>
                        </tr>
                        @foreach($perms as $perm)
                        <tr class="matrix-row">
                            <td style="padding: 0.75rem 1rem;">
                                <div style="font-weight: 700; color: var(--text-main); font-size: 0.85rem;">{{ $perm->name }}</div>
                                <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 0.15rem;">
                                    <code style="font-size: 0.7rem; color: var(--mai-primary);">{{ $perm->slug }}</code> &bull; {{ $perm->description }}
                                </div>
                            </td>
                            @foreach($roles as $r)
                            @php
                                $hasAccess = $r->permissions->contains('id', $perm->id);
                            @endphp
                            <td style="text-align: center; border-left: 1px solid var(--border-subtle); vertical-align: middle;">
                                @if($hasAccess)
                                    <span style="display: inline-flex; align-items: center; justify-content: center; width: 26px; height: 26px; border-radius: 50%; background: #dcfce7; color: #16a34a; font-weight: 800; font-size: 0.85rem;" title="{{ $r->name }} memiliki izin ini">
                                        ✓
                                    </span>
                                @else
                                    <span style="display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px; color: #cbd5e1; font-weight: 700; font-size: 1rem;">
                                        &minus;
                                    </span>
                                @endif
                            </td>
                            @endforeach
                        </tr>
                        @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @else
    <!-- ================================================================= -->
    <!-- VIEW MODE 1: TABBED ROLE PERMISSION EDITOR                         -->
    <!-- ================================================================= -->
    <div style="display: grid; grid-template-columns: 300px 1fr; gap: 1.5rem; align-items: start;">
        <!-- Left Column: Role Selector List -->
        <div style="display: flex; flex-direction: column; gap: 0.75rem;">
            <div class="card-mai" style="margin-bottom: 0;">
                <div class="card-mai-header" style="background: #ffffff; padding: 1rem 1.25rem;">
                    <div class="card-mai-title" style="font-size: 0.95rem;">
                        <span>Pilih Tingkatan Role</span>
                    </div>
                    <span class="badge-mai primary" style="font-size: 0.72rem;">{{ $roles->count() }} Role</span>
                </div>
                <div class="card-mai-body" style="padding: 0.5rem;">
                    <div style="display: flex; flex-direction: column; gap: 0.35rem;">
                        @foreach($roles as $r)
                        @php
                            $isSelected = ($activeRole && $activeRole->id === $r->id);
                            $rLower = strtolower($r->slug ?? '');
                            $icon = match(true) {
                                str_contains($rLower, 'master') || str_contains($rLower, 'admin') => '👑',
                                str_contains($rLower, 'warehouse') || str_contains($rLower, 'whc') => '📦',
                                str_contains($rLower, 'purchasing') => '🛒',
                                str_contains($rLower, 'accounting') => '📊',
                                str_contains($rLower, 'staff') => '📝',
                                str_contains($rLower, 'maintenance') => '⚙️',
                                str_contains($rLower, 'guest') => '👁️',
                                default => '👤',
                            };
                        @endphp
                        <a href="{{ route('settings.roles.index', ['role_id' => $r->id, 'view' => 'tabs']) }}" 
                           style="display: flex; align-items: center; justify-content: space-between; padding: 0.75rem 0.85rem; border-radius: var(--radius-md); text-decoration: none; transition: all var(--transition-fast); border: 1px solid {{ $isSelected ? 'var(--mai-primary)' : 'transparent' }}; background: {{ $isSelected ? 'linear-gradient(135deg, #1e3a8a, #2563eb)' : '#ffffff' }}; color: {{ $isSelected ? '#ffffff' : 'var(--text-main)' }};">
                            <div style="display: flex; align-items: center; gap: 0.65rem; overflow: hidden;">
                                <span style="font-size: 1.15rem; flex-shrink: 0;">{{ $icon }}</span>
                                <div style="overflow: hidden;">
                                    <div style="font-weight: 700; font-size: 0.85rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: {{ $isSelected ? '#ffffff' : 'var(--text-main)' }};">
                                        {{ $r->name }}
                                    </div>
                                    <div style="font-size: 0.72rem; color: {{ $isSelected ? 'rgba(255,255,255,0.75)' : 'var(--text-muted)' }}; font-family: monospace;">
                                        {{ $r->slug }}
                                    </div>
                                </div>
                            </div>
                            <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 0.2rem; flex-shrink: 0;">
                                <span class="badge-mai" style="background: {{ $isSelected ? 'rgba(255,255,255,0.2)' : '#f1f5f9' }}; color: {{ $isSelected ? '#ffffff' : 'var(--mai-primary)' }}; font-size: 0.68rem;">
                                    {{ $r->permissions->count() }} Izin
                                </span>
                                @if($r->users_count > 0)
                                <span style="font-size: 0.68rem; color: {{ $isSelected ? 'rgba(255,255,255,0.8)' : 'var(--text-muted)' }};">
                                    {{ $r->users_count }} user
                                </span>
                                @endif
                            </div>
                        </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Active Role Permission Editor -->
        @if($activeRole)
        <div>
            <!-- Active Role Details Card -->
            <div class="card-mai" style="margin-bottom: 1.25rem;">
                <div class="card-mai-header" style="background: #f8fafc; padding: 1.25rem 1.5rem;">
                    <div style="display: flex; align-items: center; gap: 0.85rem;">
                        <div style="width: 42px; height: 42px; border-radius: var(--radius-md); background: linear-gradient(135deg, #3b82f6, #1d4ed8); color: #ffffff; display: flex; align-items: center; justify-content: center; font-size: 1.35rem; box-shadow: 0 4px 10px rgba(59, 130, 246, 0.3);">
                            🛡️
                        </div>
                        <div>
                            <div style="display: flex; align-items: center; gap: 0.6rem; flex-wrap: wrap;">
                                <h4 style="font-family: var(--font-head); font-weight: 800; color: var(--text-main); margin: 0; font-size: 1.2rem;">
                                    {{ $activeRole->name }}
                                </h4>
                                <code style="background: #e2e8f0; color: var(--mai-primary); font-size: 0.75rem; padding: 0.15rem 0.45rem; border-radius: var(--radius-sm); font-weight: 700;">
                                    {{ $activeRole->slug }}
                                </code>
                                @if($activeRole->is_system)
                                    <span class="badge-mai info" style="font-size: 0.7rem;">🔒 Role Sistem Default</span>
                                @else
                                    <span class="badge-mai warning" style="font-size: 0.7rem;">⭐ Role Kustom</span>
                                @endif
                            </div>
                            <p style="font-size: 0.82rem; color: var(--text-muted); margin: 0.25rem 0 0 0;">
                                {{ $activeRole->description ?: 'Tidak ada deskripsi khusus.' }}
                            </p>
                        </div>
                    </div>
                    <div style="display: flex; gap: 0.5rem; align-items: center;">
                        <button type="button" class="btn-mai btn-mai-secondary btn-mai-sm" onclick="openEditRoleInfoModal({{ json_encode($activeRole) }})" title="Edit Deskripsi Role">
                            <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                            </svg>
                            <span>Edit Info</span>
                        </button>

                        @if(!$activeRole->is_system)
                        <form action="{{ route('settings.roles.destroy', $activeRole->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus role kustom {{ $activeRole->name }}?')" style="display: inline; margin: 0;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-mai btn-mai-danger btn-mai-sm" title="Hapus Role">
                                <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none">
                                    <polyline points="3 6 5 6 21 6"></polyline>
                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                </svg>
                            </button>
                        </form>
                        @endif
                    </div>
                </div>

                <!-- Global Quick Action Bar for Checking Permissions -->
                <div style="padding: 0.75rem 1.5rem; background: #ffffff; border-bottom: 1px solid var(--border-subtle); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem;">
                    <div style="font-size: 0.82rem; color: var(--text-main); font-weight: 700;">
                        <span>Status: </span>
                        <strong style="color: var(--mai-primary);" id="selectedCountDisplay">{{ $activeRole->permissions->count() }}</strong>
                        <span style="color: var(--text-muted);"> dari {{ $totalPermissions }} izin terpasang</span>
                    </div>
                    <div style="display: flex; gap: 0.5rem;">
                        <button type="button" class="btn-mai btn-mai-secondary btn-mai-sm" onclick="toggleAllPermissions(true)">
                            <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                            <span>Pilih Semua</span>
                        </button>
                        <button type="button" class="btn-mai btn-mai-secondary btn-mai-sm" onclick="toggleAllPermissions(false)">
                            <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                            <span>Batal Semua</span>
                        </button>
                    </div>
                </div>

                <!-- Permission Modules Form -->
                <form id="rolePermissionForm" action="{{ route('settings.roles.update', $activeRole->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="card-mai-body" style="padding: 1.5rem; display: flex; flex-direction: column; gap: 1.5rem;">
                        @foreach($permissionsByModule as $moduleName => $modulePerms)
                        @php
                            $moduleColor = match($moduleName) {
                                'mars' => '#ea580c',
                                'saturnus' => '#7c3aed',
                                'settings' => '#0284c7',
                                default => '#2563eb',
                            };
                            $moduleIcon = match($moduleName) {
                                'mars' => '📦',
                                'saturnus' => '🚀',
                                'settings' => '⚙️',
                                default => '🌐',
                            };
                            $moduleTitle = match($moduleName) {
                                'mars' => 'Modul MARS (Warehouse Consumable)',
                                'saturnus' => 'Modul SATURNUS (Direct Item)',
                                'settings' => 'Pengaturan & Administrasi Sistem',
                                default => 'General & Dashboard Terpadu',
                            };
                        @endphp
                        <div style="background: #ffffff; border: 1px solid var(--border-color); border-radius: var(--radius-lg); overflow: hidden; box-shadow: var(--shadow-sm);">
                            <!-- Module Header -->
                            <div style="padding: 0.85rem 1.25rem; background: #f8fafc; border-bottom: 1px solid var(--border-subtle); display: flex; justify-content: space-between; align-items: center;">
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <span style="font-size: 1.1rem;">{{ $moduleIcon }}</span>
                                    <span style="font-weight: 800; font-size: 0.88rem; color: var(--text-main); font-family: var(--font-head);">
                                        {{ $moduleTitle }}
                                    </span>
                                    <span class="badge-mai primary" style="font-size: 0.7rem;">{{ $modulePerms->count() }} Izin</span>
                                </div>
                                <div>
                                    <button type="button" class="btn-mai btn-mai-secondary btn-mai-sm" style="font-size: 0.75rem; padding: 0.25rem 0.6rem;" onclick="toggleModulePermissions('{{ $moduleName }}')">
                                        Pilih / Batal Modul Ini
                                    </button>
                                </div>
                            </div>

                            <!-- Module Checkbox List -->
                            <div style="padding: 1.25rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 0.85rem;">
                                @foreach($modulePerms as $perm)
                                @php
                                    $isAssigned = $activeRole->permissions->contains('id', $perm->id);
                                @endphp
                                <label class="perm-card-label" style="display: flex; align-items: flex-start; gap: 0.75rem; padding: 0.85rem 1rem; border-radius: var(--radius-md); border: 1px solid {{ $isAssigned ? 'rgba(59, 130, 246, 0.4)' : 'var(--border-subtle)' }}; background: {{ $isAssigned ? '#f0f7ff' : '#ffffff' }}; cursor: pointer; transition: all var(--transition-fast);">
                                    <input type="checkbox" name="permissions[]" value="{{ $perm->id }}" class="perm-checkbox module-{{ $moduleName }}" {{ $isAssigned ? 'checked' : '' }} onchange="onPermCheckboxChange(this)" style="margin-top: 0.25rem; width: 16px; height: 16px; cursor: pointer;">
                                    <div style="flex: 1;">
                                        <div style="font-weight: 700; font-size: 0.85rem; color: var(--text-main);">
                                            {{ $perm->name }}
                                        </div>
                                        <div style="font-size: 0.72rem; color: var(--mai-primary); font-family: 'JetBrains Mono', monospace; margin: 0.15rem 0;">
                                            {{ $perm->slug }}
                                        </div>
                                        <div style="font-size: 0.75rem; color: var(--text-muted); line-height: 1.35;">
                                            {{ $perm->description }}
                                        </div>
                                    </div>
                                </label>
                                @endforeach
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <!-- Bottom Action Footer -->
                    <div style="padding: 1.25rem 1.5rem; background: #f8fafc; border-top: 1px solid var(--border-subtle); display: flex; justify-content: space-between; align-items: center; border-radius: 0 0 var(--radius-lg) var(--radius-lg);">
                        <div style="font-size: 0.82rem; color: var(--text-muted);">
                            💡 Pastikan perubahan izin telah sesuai dengan SOP kewenangan pengguna sebelum menyimpan.
                        </div>
                        <button type="submit" class="btn-mai btn-mai-primary" style="padding: 0.65rem 1.5rem; font-weight: 700;">
                            <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none">
                                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                                <polyline points="17 21 17 13 7 13 7 21"></polyline>
                                <polyline points="7 3 7 8 15 8"></polyline>
                            </svg>
                            <span>Simpan Hak Akses Role {{ $activeRole->name }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        @endif
    </div>
    @endif
</div>

<!-- Modal Tambah Role Baru -->
<div class="modal fade" id="createRoleModal" tabindex="-1" aria-labelledby="createRoleModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 500px;">
        <div class="modal-content">
            <div class="modal-header">
                <div style="display: flex; align-items: center; gap: 0.6rem;">
                    <div style="width: 34px; height: 34px; border-radius: 8px; background: linear-gradient(135deg, #3b82f6, #1d4ed8); color: #fff; display: flex; align-items: center; justify-content: center;">
                        <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                        </svg>
                    </div>
                    <h5 class="modal-title" id="createRoleModalTitle" style="font-weight: 800; font-family: var(--font-head); font-size: 1.2rem; margin: 0;">Tambah Role Baru</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('settings.roles.store') }}" method="POST">
                @csrf
                <div class="modal-body" style="padding: 1.5rem; display: flex; flex-direction: column; gap: 1rem;">
                    <div>
                        <label class="form-label" style="font-weight: 700; font-size: 0.85rem; margin-bottom: 0.35rem;">
                            Nama Role <span style="color: #ef4444;">*</span>
                        </label>
                        <input type="text" name="name" id="new_role_name" class="form-control" required placeholder="Contoh: Quality Assurance Staff" style="height: 42px;" onkeyup="generateSlug(this.value)">
                    </div>

                    <div>
                        <label class="form-label" style="font-weight: 700; font-size: 0.85rem; margin-bottom: 0.35rem;">
                            Slug / Identifier Role <span style="color: #ef4444;">*</span>
                        </label>
                        <input type="text" name="slug" id="new_role_slug" class="form-control" required placeholder="qa_staff" style="height: 42px;">
                        <small style="color: var(--text-muted); font-size: 0.72rem;">Identifier unik dalam huruf kecil &amp; underscore.</small>
                    </div>

                    <div>
                        <label class="form-label" style="font-weight: 700; font-size: 0.85rem; margin-bottom: 0.35rem;">
                            Deskripsi Kewenangan Role
                        </label>
                        <textarea name="description" id="new_role_description" class="form-control" rows="3" placeholder="Jelaskan cakupan akses atau kewenangan role ini..."></textarea>
                    </div>
                </div>

                <div class="modal-footer" style="padding: 1rem 1.5rem; background: #f8fafc; border-top: 1px solid var(--border-subtle); display: flex; justify-content: flex-end; gap: 0.75rem;">
                    <button type="button" class="btn-mai btn-mai-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn-mai btn-mai-primary">
                        <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none">
                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                            <polyline points="17 21 17 13 7 13 7 21"></polyline>
                            <polyline points="7 3 7 8 15 8"></polyline>
                        </svg>
                        <span>Simpan Role</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Info Role -->
<div class="modal fade" id="editRoleInfoModal" tabindex="-1" aria-labelledby="editRoleInfoModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 500px;">
        <div class="modal-content">
            <div class="modal-header">
                <div style="display: flex; align-items: center; gap: 0.6rem;">
                    <div style="width: 34px; height: 34px; border-radius: 8px; background: linear-gradient(135deg, #3b82f6, #1d4ed8); color: #fff; display: flex; align-items: center; justify-content: center;">
                        <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                    </div>
                    <h5 class="modal-title" id="editRoleInfoModalTitle" style="font-weight: 800; font-family: var(--font-head); font-size: 1.2rem; margin: 0;">Edit Informasi Role</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editRoleInfoForm" action="" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="update_role_info" value="1">

                <div class="modal-body" style="padding: 1.5rem; display: flex; flex-direction: column; gap: 1rem;">
                    <div>
                        <label class="form-label" style="font-weight: 700; font-size: 0.85rem; margin-bottom: 0.35rem;">
                            Nama Role <span style="color: #ef4444;">*</span>
                        </label>
                        <input type="text" name="name" id="edit_role_name" class="form-control" required style="height: 42px;">
                    </div>

                    <div>
                        <label class="form-label" style="font-weight: 700; font-size: 0.85rem; margin-bottom: 0.35rem;">
                            Deskripsi Kewenangan Role
                        </label>
                        <textarea name="description" id="edit_role_description" class="form-control" rows="3"></textarea>
                    </div>
                </div>

                <div class="modal-footer" style="padding: 1rem 1.5rem; background: #f8fafc; border-top: 1px solid var(--border-subtle); display: flex; justify-content: flex-end; gap: 0.75rem;">
                    <button type="button" class="btn-mai btn-mai-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn-mai btn-mai-primary">
                        <span>Perbarui Role</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    let createRoleModalInstance = null;
    let editRoleInfoModalInstance = null;

    document.addEventListener('DOMContentLoaded', function() {
        const createModalEl = document.getElementById('createRoleModal');
        if (createModalEl && typeof bootstrap !== 'undefined') {
            createRoleModalInstance = new bootstrap.Modal(createModalEl);
        }

        const editModalEl = document.getElementById('editRoleInfoModal');
        if (editModalEl && typeof bootstrap !== 'undefined') {
            editRoleInfoModalInstance = new bootstrap.Modal(editModalEl);
        }
    });

    function openCreateRoleModal() {
        document.getElementById('new_role_name').value = '';
        document.getElementById('new_role_slug').value = '';
        document.getElementById('new_role_description').value = '';

        if (createRoleModalInstance) {
            createRoleModalInstance.show();
        } else if (typeof $ !== 'undefined') {
            $('#createRoleModal').modal('show');
        } else {
            document.getElementById('createRoleModal').style.display = 'block';
        }
    }

    function openEditRoleInfoModal(role) {
        document.getElementById('editRoleInfoForm').action = "/settings/roles/" + role.id;
        document.getElementById('edit_role_name').value = role.name || '';
        document.getElementById('edit_role_description').value = role.description || '';

        if (editRoleInfoModalInstance) {
            editRoleInfoModalInstance.show();
        } else if (typeof $ !== 'undefined') {
            $('#editRoleInfoModal').modal('show');
        } else {
            document.getElementById('editRoleInfoModal').style.display = 'block';
        }
    }

    function generateSlug(text) {
        const slug = text.toLowerCase()
            .replace(/[^a-z0-9]+/g, '_')
            .replace(/^_+|_+$/g, '');
        document.getElementById('new_role_slug').value = slug;
    }

    function toggleAllPermissions(checked) {
        const checkboxes = document.querySelectorAll('.perm-checkbox');
        checkboxes.forEach(cb => {
            cb.checked = checked;
            updatePermCardStyle(cb);
        });
        updateCountDisplay();
    }

    function toggleModulePermissions(moduleName) {
        const checkboxes = document.querySelectorAll('.module-' + moduleName);
        if (checkboxes.length === 0) return;

        // If all are checked, uncheck all; otherwise check all
        const allChecked = Array.from(checkboxes).every(cb => cb.checked);
        checkboxes.forEach(cb => {
            cb.checked = !allChecked;
            updatePermCardStyle(cb);
        });
        updateCountDisplay();
    }

    function onPermCheckboxChange(cb) {
        updatePermCardStyle(cb);
        updateCountDisplay();
    }

    function updatePermCardStyle(cb) {
        const cardLabel = cb.closest('.perm-card-label');
        if (!cardLabel) return;
        if (cb.checked) {
            cardLabel.style.background = '#f0f7ff';
            cardLabel.style.borderColor = 'rgba(59, 130, 246, 0.4)';
        } else {
            cardLabel.style.background = '#ffffff';
            cardLabel.style.borderColor = 'var(--border-subtle)';
        }
    }

    function updateCountDisplay() {
        const checkedCount = document.querySelectorAll('.perm-checkbox:checked').length;
        const displayEl = document.getElementById('selectedCountDisplay');
        if (displayEl) {
            displayEl.innerText = checkedCount;
        }
    }

    function filterMatrixTable() {
        const query = document.getElementById('matrixSearchInput').value.toLowerCase();
        const rows = document.querySelectorAll('#matrixTable .matrix-row');
        rows.forEach(row => {
            const text = row.innerText.toLowerCase();
            row.style.display = text.includes(query) ? '' : 'none';
        });
    }
</script>
@endsection
