@extends('layouts.app')

@section('title', 'Manajemen User & Akun')
@section('page_title', 'Manajemen User')

@section('content')
<div class="container-fluid p-0">
    <!-- Header Banner -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <div style="display: flex; align-items: center; gap: 0.6rem; margin-bottom: 0.25rem;">
                <span style="font-size: 1.75rem;">👥</span>
                <h2 style="font-family: var(--font-head); font-size: 1.6rem; font-weight: 800; color: var(--text-main); margin: 0;">
                    Manajemen Pengguna &amp; Akun
                </h2>
            </div>
            <p style="font-size: 0.88rem; color: var(--text-muted); margin: 0;">
                Kelola akun pengguna, hak akses operasional departemen, tingkatan role, dan status keaktifan user.
            </p>
        </div>
        <div style="display: flex; gap: 0.75rem; align-items: center;">
            <a href="{{ route('settings.roles.index') }}" class="btn-mai btn-mai-secondary">
                <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
                <span>Kelola Hak Akses &amp; Role</span>
            </a>
            <button type="button" class="btn-mai btn-mai-primary" onclick="openCreateUserModal()">
                <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2.5" fill="none">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                <span>Tambah Pengguna Baru</span>
            </button>
        </div>
    </div>

    <!-- Stat Metric Cards -->
    <div class="stat-card-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); margin-bottom: 1.5rem;">
        <div class="stat-card">
            <div class="stat-card-icon blue">
                <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
            </div>
            <div class="stat-card-details">
                <span class="stat-card-label">Total Akun Pengguna</span>
                <span class="stat-card-value">{{ $totalUsers ?? $users->total() }}</span>
                <span class="stat-card-sub">Terdaftar di sistem</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-card-icon emerald">
                <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
            </div>
            <div class="stat-card-details">
                <span class="stat-card-label">Akun Aktif</span>
                <span class="stat-card-value" style="color: #10b981;">{{ $activeUsers ?? 0 }}</span>
                <span class="stat-card-sub">{{ ($inactiveUsers ?? 0) > 0 ? $inactiveUsers . ' akun nonaktif' : 'Semua akun aktif' }}</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-card-icon purple">
                <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none">
                    <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                    <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                </svg>
            </div>
            <div class="stat-card-details">
                <span class="stat-card-label">Departemen</span>
                <span class="stat-card-value">{{ $totalDepts ?? count($departments) }}</span>
                <span class="stat-card-sub">Divisi operasional MAI</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-card-icon orange">
                <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                </svg>
            </div>
            <div class="stat-card-details">
                <span class="stat-card-label">Tingkatan Role</span>
                <span class="stat-card-value">{{ $totalRoles ?? $roles->count() }}</span>
                <span class="stat-card-sub">Level kewenangan RBAC</span>
            </div>
        </div>
    </div>

    <!-- Filter & Search Card -->
    <div class="card-mai" style="margin-bottom: 1.25rem;">
        <div class="card-mai-body" style="padding: 1.25rem;">
            <form action="{{ route('settings.users.index') }}" method="GET" style="display: flex; gap: 0.85rem; flex-wrap: wrap; align-items: center;">
                <div style="flex: 2; min-width: 240px;">
                    <div style="position: relative;">
                        <input type="text" name="search" class="form-control" placeholder="Cari nama, username, email, departemen, role..." value="{{ $search ?? '' }}" style="height: 42px; padding-left: 2.5rem;">
                        <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none" style="position: absolute; left: 0.85rem; top: 50%; transform: translateY(-50%); color: var(--text-muted);">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                    </div>
                </div>
                <div style="flex: 1; min-width: 170px;">
                    <select name="department" class="form-control" style="height: 42px;">
                        <option value="">🏢 Semua Departemen</option>
                        @foreach($departments as $d)
                            <option value="{{ $d }}" {{ ($department ?? '') === $d ? 'selected' : '' }}>{{ $d }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="flex: 1; min-width: 170px;">
                    <select name="role" class="form-control" style="height: 42px;">
                        <option value="">🛡️ Semua Role</option>
                        @foreach($roles as $r)
                            <option value="{{ $r->name }}" {{ ($role ?? '') === $r->name ? 'selected' : '' }}>{{ $r->display_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="min-width: 140px;">
                    <select name="status" class="form-control" style="height: 42px;">
                        <option value="">🔘 Semua Status</option>
                        <option value="active" {{ ($status ?? '') === 'active' ? 'selected' : '' }}>🟢 Aktif</option>
                        <option value="inactive" {{ ($status ?? '') === 'inactive' ? 'selected' : '' }}>🔴 Nonaktif</option>
                    </select>
                </div>
                <div style="display: flex; gap: 0.5rem;">
                    <button type="submit" class="btn-mai btn-mai-primary" style="height: 42px;">
                        <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                        <span>Filter</span>
                    </button>
                    @if($search || $role || $department || $status)
                        <a href="{{ route('settings.users.index') }}" class="btn-mai btn-mai-secondary" style="height: 42px; color: #ef4444;" title="Reset Filter">
                            <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                            <span>Reset</span>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Users Table Card -->
    <div class="card-mai">
        <div class="card-mai-header" style="background: #ffffff;">
            <div class="card-mai-title">
                <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none" style="color: var(--mai-primary);">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
                <span>Daftar Akun Pengguna Terdaftar</span>
                <span class="badge-mai primary" style="font-size: 0.75rem; margin-left: 0.5rem;">{{ $users->total() }} User</span>
            </div>
            <div style="font-size: 0.8rem; color: var(--text-muted);">
                Menampilkan <strong>{{ $users->firstItem() ?? 0 }}</strong> - <strong>{{ $users->lastItem() ?? 0 }}</strong> dari <strong>{{ $users->total() }}</strong> pengguna
            </div>
        </div>
        <div class="card-mai-body" style="padding: 0;">
            <div class="table-mai-responsive" style="border: none; border-radius: 0;">
                <table class="table-mai">
                    <thead>
                        <tr>
                            <th style="width: 50px; text-align: center;">ID</th>
                            <th>Pengguna</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Departemen</th>
                            <th>Role &amp; Hak Akses</th>
                            <th style="text-align: center;">Status</th>
                            <th style="text-align: center; width: 130px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $u)
                        @php
                            $isActive = $u->isActive();
                            $roleLower = strtolower($u->role ?? '');
                            $avatarGradient = match(true) {
                                str_contains($roleLower, 'master') || str_contains($roleLower, 'admin') => 'linear-gradient(135deg, #ef4444, #b91c1c)',
                                str_contains($roleLower, 'warehouse') || str_contains($roleLower, 'whc') => 'linear-gradient(135deg, #f97316, #c2410c)',
                                str_contains($roleLower, 'purchasing') => 'linear-gradient(135deg, #3b82f6, #1d4ed8)',
                                str_contains($roleLower, 'accounting') => 'linear-gradient(135deg, #10b981, #047857)',
                                str_contains($roleLower, 'staff') => 'linear-gradient(135deg, #06b6d4, #0e7490)',
                                default => 'linear-gradient(135deg, #8b5cf6, #6d28d9)',
                            };
                        @endphp
                        <tr>
                            <td style="text-align: center; font-weight: 700; color: var(--text-muted); font-size: 0.8rem;">
                                #{{ $u->id }}
                            </td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <div style="width: 36px; height: 36px; border-radius: 50%; background: {{ $avatarGradient }}; color: #ffffff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.9rem; box-shadow: 0 2px 6px rgba(0,0,0,0.15); flex-shrink: 0;">
                                        {{ strtoupper(substr($u->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <div style="font-weight: 700; color: var(--text-main); font-size: 0.9rem;">
                                            {{ $u->name }}
                                            @if($u->id === auth()->id())
                                                <span class="badge-mai info" style="font-size: 0.65rem; padding: 0.1rem 0.4rem; margin-left: 0.25rem;">(Anda)</span>
                                            @endif
                                        </div>
                                        <div style="font-size: 0.73rem; color: var(--text-muted);">
                                            Bergabung: {{ $u->created_at ? $u->created_at->format('d/m/Y') : '-' }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span style="font-family: 'JetBrains Mono', monospace; font-size: 0.82rem; font-weight: 700; color: var(--mai-primary); background: #eff6ff; padding: 0.2rem 0.5rem; border-radius: var(--radius-sm);">
                                    {{ '@' . $u->username }}
                                </span>
                            </td>
                            <td>
                                <span style="font-size: 0.84rem; color: var(--text-main);">{{ $u->email }}</span>
                            </td>
                            <td>
                                <span class="badge-mai info" style="font-weight: 600;">
                                    🏢 {{ $u->department ?? 'Umum' }}
                                </span>
                            </td>
                            <td>
                                <span class="badge-mai {{ $u->role_badge_class }}">
                                    {{ $u->role_icon }} {{ $u->role }}
                                </span>
                            </td>
                            <td style="text-align: center;">
                                @if($isActive)
                                    <span class="badge-mai success" style="display: inline-flex; align-items: center; gap: 0.35rem;">
                                        <span style="width: 7px; height: 7px; border-radius: 50%; background: #10b981; display: inline-block;"></span>
                                        Aktif
                                    </span>
                                @else
                                    <span class="badge-mai danger" style="display: inline-flex; align-items: center; gap: 0.35rem;">
                                        <span style="width: 7px; height: 7px; border-radius: 50%; background: #ef4444; display: inline-block;"></span>
                                        Nonaktif
                                    </span>
                                @endif
                            </td>
                            <td style="text-align: center;">
                                <div style="display: flex; gap: 0.4rem; justify-content: center; align-items: center;">
                                    <button type="button" class="btn-mai btn-mai-secondary btn-mai-sm" onclick="openEditUserModal({{ json_encode($u) }})" title="Edit User" style="padding: 0.35rem 0.6rem;">
                                        <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                        </svg>
                                    </button>

                                    @if($u->id !== auth()->id() && !in_array(strtolower($u->username), ['master', 'admin']))
                                    <form action="{{ route('settings.users.destroy', $u->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus user {{ $u->name }} (@{{ $u->username }})? Tindakan ini tidak dapat dibatalkan.')" style="display: inline; margin: 0;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-mai btn-mai-danger btn-mai-sm" title="Hapus User" style="padding: 0.35rem 0.6rem;">
                                            <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none">
                                                <polyline points="3 6 5 6 21 6"></polyline>
                                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                            </svg>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 3rem 1.5rem; color: var(--text-muted);">
                                <div style="display: flex; flex-direction: column; align-items: center; gap: 0.75rem;">
                                    <div style="width: 54px; height: 54px; border-radius: 50%; background: #f1f5f9; display: flex; align-items: center; justify-content: center; color: var(--text-muted);">
                                        <svg viewBox="0 0 24 24" width="28" height="28" stroke="currentColor" stroke-width="1.75" fill="none">
                                            <circle cx="11" cy="11" r="8"></circle>
                                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                        </svg>
                                    </div>
                                    <div style="font-weight: 700; font-size: 1rem; color: var(--text-main);">Tidak ada akun pengguna yang ditemukan</div>
                                    <div style="font-size: 0.85rem; color: var(--text-muted); max-width: 380px;">
                                        Coba ubah kata kunci pencarian atau reset filter untuk menampilkan semua data.
                                    </div>
                                    @if($search || $role || $department || $status)
                                        <a href="{{ route('settings.users.index') }}" class="btn-mai btn-mai-secondary btn-mai-sm" style="margin-top: 0.5rem;">
                                            Reset Filter
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($users->hasPages())
            <div style="padding: 1.25rem 1.5rem; border-top: 1px solid var(--border-subtle); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                <div style="font-size: 0.82rem; color: var(--text-muted);">
                    Halaman {{ $users->currentPage() }} dari {{ $users->lastPage() }}
                </div>
                <div>
                    {{ $users->links() }}
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Standard Bootstrap 5 Modal Create / Edit User -->
<div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 580px;">
        <div class="modal-content">
            <div class="modal-header">
                <div style="display: flex; align-items: center; gap: 0.6rem;">
                    <div id="modalHeaderIcon" style="width: 34px; height: 34px; border-radius: 8px; background: linear-gradient(135deg, #3b82f6, #1d4ed8); color: #fff; display: flex; align-items: center; justify-content: center;">
                        <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </div>
                    <h5 class="modal-title" id="userModalTitle" style="font-weight: 800; font-family: var(--font-head); font-size: 1.2rem; margin: 0;">Tambah Pengguna Baru</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="userForm" action="{{ route('settings.users.store') }}" method="POST">
                @csrf
                <div id="methodContainer"></div>

                <div class="modal-body" style="padding: 1.5rem; display: flex; flex-direction: column; gap: 1.15rem;">
                    <div>
                        <label class="form-label" style="font-weight: 700; font-size: 0.85rem; margin-bottom: 0.35rem;">
                            Nama Lengkap <span style="color: #ef4444;">*</span>
                        </label>
                        <input type="text" name="name" id="user_name" class="form-control" required placeholder="Contoh: Budi Santoso" style="height: 42px;">
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div>
                            <label class="form-label" style="font-weight: 700; font-size: 0.85rem; margin-bottom: 0.35rem;">
                                Username <span style="color: #ef4444;">*</span>
                            </label>
                            <input type="text" name="username" id="user_username" class="form-control" required placeholder="budi_user" style="height: 42px;">
                            <small style="color: var(--text-muted); font-size: 0.72rem;">Huruf kecil, angka, atau underscore.</small>
                        </div>
                        <div>
                            <label class="form-label" style="font-weight: 700; font-size: 0.85rem; margin-bottom: 0.35rem;">
                                Email <span style="color: #ef4444;">*</span>
                            </label>
                            <input type="email" name="email" id="user_email" class="form-control" required placeholder="budi@mai.co.id" style="height: 42px;">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div>
                            <label class="form-label" style="font-weight: 700; font-size: 0.85rem; margin-bottom: 0.35rem;">
                                Departemen <span style="color: #ef4444;">*</span>
                            </label>
                            <select name="department" id="user_department" class="form-control" required style="height: 42px;" onchange="checkCustomDepartment(this.value)">
                                <option value="" disabled selected>-- Pilih Departemen --</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept }}">{{ $dept }}</option>
                                @endforeach
                                <option value="__custom__">+ Input Departemen Lain...</option>
                            </select>
                            <div id="customDeptWrapper" style="display: none; margin-top: 0.5rem;">
                                <input type="text" id="user_custom_department" class="form-control" placeholder="Ketik nama departemen..." style="height: 38px;">
                            </div>
                        </div>

                        <div>
                            <label class="form-label" style="font-weight: 700; font-size: 0.85rem; margin-bottom: 0.35rem;">
                                Role Pengguna <span style="color: #ef4444;">*</span>
                            </label>
                            <select name="role" id="user_role" class="form-control" required style="height: 42px;">
                                @foreach($roles as $r)
                                    <option value="{{ $r->name }}">{{ $r->display_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div id="statusFieldContainer" style="display: none;">
                        <label class="form-label" style="font-weight: 700; font-size: 0.85rem; margin-bottom: 0.35rem;">
                            Status Keaktifan Akun
                        </label>
                        <select name="status" id="user_status" class="form-control" style="height: 42px;">
                            <option value="active">🟢 Active (Dapat Login)</option>
                            <option value="inactive">🔴 Inactive (Diblokir/Nonaktif)</option>
                        </select>
                    </div>

                    <div>
                        <label class="form-label" id="passwordLabel" style="font-weight: 700; font-size: 0.85rem; margin-bottom: 0.35rem;">
                            Password <span id="passwordRequiredStar" style="color: #ef4444;">*</span>
                        </label>
                        <div style="position: relative;">
                            <input type="password" name="password" id="user_password" class="form-control" placeholder="Minimal 4 karakter" style="height: 42px; padding-right: 2.75rem;">
                            <button type="button" onclick="togglePasswordVisibility('user_password', 'togglePasswordIcon')" style="position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: var(--text-muted); display: flex; align-items: center; justify-content: center; padding: 0.25rem;">
                                <svg id="togglePasswordIcon" viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                            </button>
                        </div>
                        <small id="passwordHelp" style="color: var(--text-muted); font-size: 0.75rem; display: none; margin-top: 0.25rem;">
                            💡 Kosongkan bidang password ini jika tidak ingin mengubah kata sandi pengguna.
                        </small>
                    </div>
                </div>

                <div class="modal-footer" style="padding: 1rem 1.5rem; background: #f8fafc; border-top: 1px solid var(--border-subtle); display: flex; justify-content: flex-end; gap: 0.75rem;">
                    <button type="button" class="btn-mai btn-mai-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn-mai btn-mai-primary" id="saveUserBtn">
                        <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none">
                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                            <polyline points="17 21 17 13 7 13 7 21"></polyline>
                            <polyline points="7 3 7 8 15 8"></polyline>
                        </svg>
                        <span id="saveBtnText">Simpan User</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    let userModalInstance = null;

    document.addEventListener('DOMContentLoaded', function() {
        const modalEl = document.getElementById('userModal');
        if (modalEl && typeof bootstrap !== 'undefined') {
            userModalInstance = new bootstrap.Modal(modalEl);
        }
    });

    function openCreateUserModal() {
        document.getElementById('userModalTitle').innerText = 'Tambah Pengguna Baru';
        document.getElementById('saveBtnText').innerText = 'Simpan User Baru';
        document.getElementById('userForm').action = "{{ route('settings.users.store') }}";
        document.getElementById('methodContainer').innerHTML = '';

        document.getElementById('user_name').value = '';
        document.getElementById('user_username').value = '';
        document.getElementById('user_email').value = '';
        document.getElementById('user_department').value = '';
        document.getElementById('customDeptWrapper').style.display = 'none';
        document.getElementById('user_custom_department').value = '';
        document.getElementById('user_role').selectedIndex = 0;
        document.getElementById('user_status').value = 'active';

        const passwordInput = document.getElementById('user_password');
        passwordInput.required = true;
        passwordInput.value = '';
        document.getElementById('passwordRequiredStar').style.display = 'inline';
        document.getElementById('passwordHelp').style.display = 'none';
        document.getElementById('statusFieldContainer').style.display = 'none';

        if (userModalInstance) {
            userModalInstance.show();
        } else if (typeof $ !== 'undefined') {
            $('#userModal').modal('show');
        } else {
            document.getElementById('userModal').classList.add('show');
            document.getElementById('userModal').style.display = 'block';
        }
    }

    function openEditUserModal(user) {
        document.getElementById('userModalTitle').innerText = 'Edit Data Pengguna: ' + user.name;
        document.getElementById('saveBtnText').innerText = 'Perbarui Pengguna';
        document.getElementById('userForm').action = "/settings/users/" + user.id;
        document.getElementById('methodContainer').innerHTML = '<input type="hidden" name="_method" value="PUT">';

        document.getElementById('user_name').value = user.name || '';
        document.getElementById('user_username').value = user.username || '';
        document.getElementById('user_email').value = user.email || '';

        // Set department
        const deptSelect = document.getElementById('user_department');
        let matched = false;
        for (let i = 0; i < deptSelect.options.length; i++) {
            if (deptSelect.options[i].value === user.department) {
                deptSelect.selectedIndex = i;
                matched = true;
                break;
            }
        }
        if (!matched && user.department) {
            deptSelect.value = '__custom__';
            document.getElementById('customDeptWrapper').style.display = 'block';
            document.getElementById('user_custom_department').value = user.department;
        } else {
            document.getElementById('customDeptWrapper').style.display = 'none';
            document.getElementById('user_custom_department').value = '';
        }

        // Set Role
        const roleSelect = document.getElementById('user_role');
        let roleMatched = false;
        for (let i = 0; i < roleSelect.options.length; i++) {
            if (roleSelect.options[i].value === user.role) {
                roleSelect.selectedIndex = i;
                roleMatched = true;
                break;
            }
        }
        if (!roleMatched) {
            roleSelect.value = user.role || '';
        }

        // Status
        const statusVal = (user.status && ['inactive', 'nonaktif', '0'].includes(user.status.toLowerCase())) ? 'inactive' : 'active';
        document.getElementById('user_status').value = statusVal;

        // Password fields
        const passwordInput = document.getElementById('user_password');
        passwordInput.required = false;
        passwordInput.value = '';
        document.getElementById('passwordRequiredStar').style.display = 'none';
        document.getElementById('passwordHelp').style.display = 'block';
        document.getElementById('statusFieldContainer').style.display = 'block';

        if (userModalInstance) {
            userModalInstance.show();
        } else if (typeof $ !== 'undefined') {
            $('#userModal').modal('show');
        } else {
            document.getElementById('userModal').classList.add('show');
            document.getElementById('userModal').style.display = 'block';
        }
    }

    function checkCustomDepartment(val) {
        const customWrapper = document.getElementById('customDeptWrapper');
        const customInput = document.getElementById('user_custom_department');
        if (val === '__custom__') {
            customWrapper.style.display = 'block';
            customInput.required = true;
            customInput.focus();
        } else {
            customWrapper.style.display = 'none';
            customInput.required = false;
        }
    }

    document.getElementById('userForm').addEventListener('submit', function(e) {
        const deptSelect = document.getElementById('user_department');
        if (deptSelect.value === '__custom__') {
            const customVal = document.getElementById('user_custom_department').value.trim();
            if (!customVal) {
                e.preventDefault();
                alert('Silakan ketikkan nama departemen.');
                return false;
            }
            deptSelect.options[deptSelect.selectedIndex].value = customVal;
        }
    });

    function togglePasswordVisibility(inputId, iconId) {
        const input = document.getElementById(inputId);
        const icon = document.getElementById(iconId);
        if (!input) return;

        if (input.type === 'password') {
            input.type = 'text';
            icon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>';
        } else {
            input.type = 'password';
            icon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>';
        }
    }
</script>
@endsection
