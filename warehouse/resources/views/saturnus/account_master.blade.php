@extends('layouts.app')

@section('title', 'Account Master Management')

@section('content')
@php
    $userRoleRaw = strtoupper(trim(Auth::user()->role ?? 'USER'));
    $isMaster = in_array($userRoleRaw, ['MASTER', 'ADMIN']) || (Auth::user() && method_exists(Auth::user(), 'isMaster') && Auth::user()->isMaster());
@endphp

<style>
    .modal {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: rgba(2, 6, 23, 0.75) !important;
        backdrop-filter: blur(12px) !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        z-index: 1060 !important;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.25s ease;
    }

    .modal.show {
        opacity: 1 !important;
        pointer-events: auto !important;
    }

    .modal-content {
        background: #ffffff !important;
        border: 1.5px solid #e2e8f0 !important;
        color: #0f172a !important;
        box-shadow: 0 25px 60px rgba(0, 0, 0, 0.3) !important;
        border-radius: 20px !important;
        width: 92%;
        max-width: 520px;
        padding: 2rem 2.25rem !important;
        transform: scale(0.95);
        transition: transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
        position: relative;
        max-height: 90vh;
        overflow-y: auto;
    }

    .modal.show .modal-content {
        transform: scale(1) !important;
    }

    .btn-close {
        background: transparent !important;
        border: none !important;
        font-size: 1.35rem !important;
        line-height: 1 !important;
        color: #94a3b8 !important;
        cursor: pointer !important;
        padding: 0.25rem !important;
        border-radius: 6px !important;
        transition: all 0.2s !important;
    }
    .btn-close:hover {
        color: #0f172a !important;
        background: #f1f5f9 !important;
    }
</style>

<div class="workspace-light-theme">

    <div style="display: flex; flex-direction: column; gap: 1.5rem; max-width: 1200px; margin: 0 auto;">
        
        {{-- Header Card & Actions --}}
        <div class="glass-card" style="padding: 1.75rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h3 style="font-family: var(--font-heading); font-weight: 700; color: var(--text-primary); margin-bottom: 0.35rem; display: flex; align-items: center; gap: 0.5rem; font-size: 1.35rem;">
                    <svg viewBox="0 0 24 24" width="22" height="22" stroke="var(--color-primary)" stroke-width="2.5" fill="none"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                    Account Master Management
                </h3>
                <p style="color: var(--text-muted); font-size: 0.875rem; margin: 0;">Kelola data pengguna, hak akses role (Master, User, Staff, Accounting, Warehouse Consumable), dan departemen.</p>
            </div>
            <button class="btn btn-primary" onclick="openModal('addAccountModal')" style="display: inline-flex; align-items: center; gap: 0.5rem; font-weight: 700; padding: 0.65rem 1.25rem; border-radius: var(--radius-md);">
                <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2.5" fill="none"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                + Buat Akun Baru
            </button>
        </div>

        {{-- Stats Cards Row --}}
        <div class="dataview-stats">
            <div class="dataview-stat-card">
                <div style="background-color: var(--color-primary-light); color: var(--color-primary); padding: 0.75rem; border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center;">
                    <svg viewBox="0 0 24 24" width="22" height="22" stroke="currentColor" stroke-width="2.5" fill="none"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle></svg>
                </div>
                <div>
                    <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 600; display: block; text-transform: uppercase;">Total Akun Terdaftar</span>
                    <span style="font-size: 1.4rem; font-weight: 800; color: var(--text-primary);" id="stat-total-accounts">{{ isset($users) ? $users->count() : 0 }} Akun</span>
                </div>
            </div>
            
            <div class="dataview-stat-card">
                <div style="background-color: rgba(59, 130, 246, 0.1); color: rgb(29, 78, 216); padding: 0.75rem; border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center;">
                    <svg viewBox="0 0 24 24" width="22" height="22" stroke="currentColor" stroke-width="2.5" fill="none"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle></svg>
                </div>
                <div>
                    <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 600; display: block; text-transform: uppercase;">Akun USER</span>
                    <span style="font-size: 1.4rem; font-weight: 800; color: rgb(29, 78, 216);" id="stat-user-accounts-count">{{ isset($users) ? $users->filter(fn($u) => strtolower($u->role) === 'user')->count() : 0 }} Akun</span>
                </div>
            </div>

            <div class="dataview-stat-card">
                <div style="background-color: rgba(245, 158, 11, 0.1); color: rgb(217, 119, 6); padding: 0.75rem; border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center;">
                    <svg viewBox="0 0 24 24" width="22" height="22" stroke="currentColor" stroke-width="2.5" fill="none"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path></svg>
                </div>
                <div>
                    <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 600; display: block; text-transform: uppercase;">Akun STAFF</span>
                    <span style="font-size: 1.4rem; font-weight: 800; color: var(--color-warning);" id="stat-staff-accounts-count">{{ isset($users) ? $users->filter(fn($u) => strtolower($u->role) === 'staff')->count() : 0 }} Akun</span>
                </div>
            </div>

            <div class="dataview-stat-card">
                <div style="background-color: var(--color-success-light); color: var(--color-success); padding: 0.75rem; border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center;">
                    <svg viewBox="0 0 24 24" width="22" height="22" stroke="currentColor" stroke-width="2.5" fill="none"><polyline points="20 6 9 17 4 12"></polyline></svg>
                </div>
                <div>
                    <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 600; display: block; text-transform: uppercase;">Akun ACC / WAREHOUSE</span>
                    <span style="font-size: 1.4rem; font-weight: 800; color: var(--color-success);" id="stat-acc-wh-accounts-count">{{ isset($users) ? $users->filter(fn($u) => in_array(strtolower($u->role), ['accounting', 'warehouse consumable', 'warehouse']))->count() : 0 }} Akun</span>
                </div>
            </div>
        </div>

        {{-- Table Card --}}
        <div class="glass-card" style="padding: 1.5rem;">
            <div class="form-reg-table-wrap">
                <table class="form-reg-table" id="table-accounts">
                    <thead>
                        <tr>
                            <th class="th-center" style="width: 50px;">NO</th>
                            <th>NAMA PENGGUNA</th>
                            <th>USERNAME / ID</th>
                            <th>DEPARTMENT</th>
                            <th class="th-center">ROLE / AKSES</th>
                            <th class="th-center">STATUS</th>
                            <th class="th-center" style="width: 150px;">AKSI</th>
                        </tr>
                    </thead>
                    <tbody id="account-table-body">
                        <!-- Populated by renderAccountTable() -->
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</div>

{{-- ===== MODAL: BUAT AKUN BARU ===== --}}
<div class="modal no-print" id="addAccountModal">
    <div class="modal-content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; border-bottom: 1.5px solid #f1f5f9; padding-bottom: 1rem;">
            <h3 style="font-size: 1.2rem; font-weight: 800; color: #0f172a; margin: 0;">Buat Akun Pengguna Baru</h3>
            <button class="btn-close" onclick="closeModal('addAccountModal')">&times;</button>
        </div>

        <form id="form-add-account" action="{{ route('users.store') }}" method="POST">
            @csrf
            
            <div class="form-group" style="margin-bottom: 1rem;">
                <label for="acc_add_name" style="font-weight: 700; font-size: 0.8rem; margin-bottom: 0.35rem; display: block; color: #1e293b;">Nama Lengkap <span style="color: #ef4444;">*</span></label>
                <input type="text" id="acc_add_name" name="name" class="form-control" placeholder="Cth: Budi Santoso" required style="height: 42px; border-radius: 8px; border: 1.5px solid #cbd5e1; padding: 0 0.85rem; font-size: 0.85rem; width: 100%;">
            </div>

            <div class="form-group" style="margin-bottom: 1rem;">
                <label for="acc_add_username" style="font-weight: 700; font-size: 0.8rem; margin-bottom: 0.35rem; display: block; color: #1e293b;">Username / Login ID</label>
                <input type="text" id="acc_add_username" name="username" class="form-control" placeholder="Cth: budi_santoso (opsional)" style="height: 42px; border-radius: 8px; border: 1.5px solid #cbd5e1; padding: 0 0.85rem; font-size: 0.85rem; width: 100%;">
            </div>

            <div class="form-group" style="margin-bottom: 1rem;">
                <label for="acc_add_dept" style="font-weight: 700; font-size: 0.8rem; margin-bottom: 0.35rem; display: block; color: #1e293b;">Department <span style="color: #ef4444;">*</span></label>
                <select id="acc_add_dept" name="department" class="form-control" style="height: 42px; border-radius: 8px; border: 1.5px solid #cbd5e1; padding: 0 0.85rem; font-size: 0.85rem; width: 100%;" required>
                    <option value="" disabled selected>-- Pilih Department --</option>
                    <option value="HRGA">HRGA</option>
                    <option value="PPIC Finish Good">PPIC Finish Good</option>
                    <option value="PPIC Warehouse">PPIC Warehouse</option>
                    <option value="QA">QA</option>
                    <option value="QC">QC</option>
                    <option value="Production">Production</option>
                    <option value="Die Shop">Die Shop</option>
                    <option value="Dies Assy">Dies Assy</option>
                    <option value="Production / Dies Assy">Production / Dies Assy</option>
                    <option value="Maintenance">Maintenance</option>
                    <option value="Accounting">Accounting</option>
                    <option value="Warehouse Consumable">Warehouse Consumable</option>
                </select>
            </div>

            <div class="form-group" style="margin-bottom: 1rem;">
                <label for="acc_add_role" style="font-weight: 700; font-size: 0.8rem; margin-bottom: 0.35rem; display: block; color: #1e293b;">Role Hak Akses <span style="color: #ef4444;">*</span></label>
                <select id="acc_add_role" name="role" class="form-control" style="height: 42px; border-radius: 8px; border: 1.5px solid #cbd5e1; padding: 0 0.85rem; font-size: 0.85rem; width: 100%;" required>
                    <option value="" disabled selected>-- Pilih Role --</option>
                    <option value="MASTER">Master (Akses Penuh & Account Master)</option>
                    <option value="User">User</option>
                    <option value="Staff">Staff</option>
                    <option value="Staff (Production / Dies Assy)">Staff (Production / Dies Assy)</option>
                    <option value="Accounting">Accounting</option>
                    <option value="Warehouse Consumable">Warehouse Consumable</option>
                </select>
            </div>

            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label for="acc_add_password" style="font-weight: 700; font-size: 0.8rem; margin-bottom: 0.35rem; display: block; color: #1e293b;">Password <span style="color: #ef4444;">*</span></label>
                <input type="password" id="acc_add_password" name="password" class="form-control" placeholder="Masukkan password akun..." required style="height: 42px; border-radius: 8px; border: 1.5px solid #cbd5e1; padding: 0 0.85rem; font-size: 0.85rem; width: 100%;">
            </div>

            <div style="display: flex; gap: 0.75rem; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addAccountModal')" style="padding: 0.6rem 1.25rem; font-weight: 700; border-radius: 8px;">Batal</button>
                <button type="submit" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.6rem 1.4rem; font-weight: 700; border-radius: 8px;">
                    <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2.5" fill="none"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    Simpan Akun
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ===== MODAL: EDIT AKUN ===== --}}
<div class="modal no-print" id="editAccountModal">
    <div class="modal-content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; border-bottom: 1.5px solid #f1f5f9; padding-bottom: 1rem;">
            <h3 style="font-size: 1.2rem; font-weight: 800; color: #0f172a; margin: 0;">Edit Akun Pengguna</h3>
            <button class="btn-close" onclick="closeModal('editAccountModal')">&times;</button>
        </div>

        <form id="form-edit-account" action="" method="POST">
            @csrf
            @method('PUT')
            
            <div class="form-group" style="margin-bottom: 1rem;">
                <label for="acc_edit_name" style="font-weight: 700; font-size: 0.8rem; margin-bottom: 0.35rem; display: block; color: #1e293b;">Nama Lengkap <span style="color: #ef4444;">*</span></label>
                <input type="text" id="acc_edit_name" name="name" class="form-control" required style="height: 42px; border-radius: 8px; border: 1.5px solid #cbd5e1; padding: 0 0.85rem; font-size: 0.85rem; width: 100%;">
            </div>

            <div class="form-group" style="margin-bottom: 1rem;">
                <label for="acc_edit_username" style="font-weight: 700; font-size: 0.8rem; margin-bottom: 0.35rem; display: block; color: #1e293b;">Username / Login ID</label>
                <input type="text" id="acc_edit_username" name="username" class="form-control" style="height: 42px; border-radius: 8px; border: 1.5px solid #cbd5e1; padding: 0 0.85rem; font-size: 0.85rem; width: 100%;">
            </div>

            <div class="form-group" style="margin-bottom: 1rem;">
                <label for="acc_edit_dept" style="font-weight: 700; font-size: 0.8rem; margin-bottom: 0.35rem; display: block; color: #1e293b;">Department <span style="color: #ef4444;">*</span></label>
                <select id="acc_edit_dept" name="department" class="form-control" style="height: 42px; border-radius: 8px; border: 1.5px solid #cbd5e1; padding: 0 0.85rem; font-size: 0.85rem; width: 100%;" required>
                    <option value="HRGA">HRGA</option>
                    <option value="PPIC Finish Good">PPIC Finish Good</option>
                    <option value="PPIC Warehouse">PPIC Warehouse</option>
                    <option value="QA">QA</option>
                    <option value="QC">QC</option>
                    <option value="Production">Production</option>
                    <option value="Die Shop">Die Shop</option>
                    <option value="Dies Assy">Dies Assy</option>
                    <option value="Production / Dies Assy">Production / Dies Assy</option>
                    <option value="Maintenance">Maintenance</option>
                    <option value="Accounting">Accounting</option>
                    <option value="Warehouse Consumable">Warehouse Consumable</option>
                </select>
            </div>

            <div class="form-group" style="margin-bottom: 1rem;">
                <label for="acc_edit_role" style="font-weight: 700; font-size: 0.8rem; margin-bottom: 0.35rem; display: block; color: #1e293b;">Role Hak Akses <span style="color: #ef4444;">*</span></label>
                <select id="acc_edit_role" name="role" class="form-control" style="height: 42px; border-radius: 8px; border: 1.5px solid #cbd5e1; padding: 0 0.85rem; font-size: 0.85rem; width: 100%;" required>
                    <option value="MASTER">Master (Akses Penuh & Account Master)</option>
                    <option value="User">User</option>
                    <option value="Staff">Staff</option>
                    <option value="Staff (Production / Dies Assy)">Staff (Production / Dies Assy)</option>
                    <option value="Accounting">Accounting</option>
                    <option value="Warehouse Consumable">Warehouse Consumable</option>
                </select>
            </div>

            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label for="acc_edit_password" style="font-weight: 700; font-size: 0.8rem; margin-bottom: 0.35rem; display: block; color: #1e293b;">Password Baru <span style="color: #94a3b8; font-size: 0.75rem;">(Kosongkan jika tidak diubah)</span></label>
                <input type="password" id="acc_edit_password" name="password" class="form-control" placeholder="Kosongkan jika tidak ingin mengubah password" style="height: 42px; border-radius: 8px; border: 1.5px solid #cbd5e1; padding: 0 0.85rem; font-size: 0.85rem; width: 100%;">
            </div>

            <div style="display: flex; gap: 0.75rem; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editAccountModal')" style="padding: 0.6rem 1.25rem; font-weight: 700; border-radius: 8px;">Batal</button>
                <button type="submit" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.6rem 1.4rem; font-weight: 700; border-radius: 8px;">
                    <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2.5" fill="none"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@section('scripts')
<script>
    const serverAccounts = @json($users ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    function openModal(id) {
        const el = document.getElementById(id);
        if (el) el.classList.add('show');
    }

    function closeModal(id) {
        const el = document.getElementById(id);
        if (el) el.classList.remove('show');
    }

    window.addEventListener('click', function(e) {
        if (e.target.classList && e.target.classList.contains('modal')) {
            e.target.classList.remove('show');
        }
    });

    function escapeHtml(text) {
        if (!text) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function renderAccountTable() {
        const tbody = document.getElementById('account-table-body');
        if (!tbody) return;

        if (serverAccounts.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                        Belum ada data akun terdaftar.
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = serverAccounts.map((acc, idx) => {
            const roleStr = String(acc.role || 'User').toUpperCase();
            let roleBadge = '';
            if (roleStr === 'MASTER' || roleStr === 'ADMIN') {
                roleBadge = '<span class="status-badge" style="background: #0f172a; color: #ffffff; font-weight: 700; padding: 0.3rem 0.65rem; border-radius: 8px; font-size: 0.78rem;">MASTER</span>';
            } else if (roleStr.includes('STAFF')) {
                roleBadge = '<span class="status-badge" style="background: rgba(37,99,235,0.15); color: #2563eb; font-weight: 700; padding: 0.3rem 0.65rem; border-radius: 8px; font-size: 0.78rem;">STAFF</span>';
            } else if (roleStr.includes('ACCOUNTING') || roleStr.includes('ACC')) {
                roleBadge = '<span class="status-badge" style="background: rgba(217,119,6,0.15); color: #d97706; font-weight: 700; padding: 0.3rem 0.65rem; border-radius: 8px; font-size: 0.78rem;">ACCOUNTING</span>';
            } else if (roleStr.includes('WAREHOUSE')) {
                roleBadge = '<span class="status-badge" style="background: rgba(16,185,129,0.15); color: #059669; font-weight: 700; padding: 0.3rem 0.65rem; border-radius: 8px; font-size: 0.78rem;">WH CONSUMABLE</span>';
            } else {
                roleBadge = '<span class="status-badge" style="background: #f1f5f9; color: #475569; font-weight: 700; padding: 0.3rem 0.65rem; border-radius: 8px; font-size: 0.78rem;">USER</span>';
            }

            return `
                <tr>
                    <td class="th-center" style="font-weight: 700; color: var(--text-muted);">${idx + 1}</td>
                    <td>
                        <strong style="color: #0f172a; font-size: 0.88rem;">${escapeHtml(acc.name)}</strong>
                        <div style="font-size: 0.75rem; color: #64748b;">${escapeHtml(acc.email || '')}</div>
                    </td>
                    <td>
                        <span style="font-family: var(--font-tech); font-weight: 600; color: #334155; font-size: 0.85rem;">${escapeHtml(acc.username || '-')}</span>
                    </td>
                    <td>
                        <span style="font-weight: 600; color: #0f172a; font-size: 0.85rem;">${escapeHtml(acc.department || '-')}</span>
                    </td>
                    <td class="th-center">${roleBadge}</td>
                    <td class="th-center">
                        <span style="display: inline-flex; align-items: center; gap: 0.35rem; font-size: 0.78rem; font-weight: 700; color: #059669;">
                            <span style="width: 7px; height: 7px; border-radius: 50%; background: #10b981;"></span> Aktif
                        </span>
                    </td>
                    <td class="th-center">
                        <div style="display: flex; gap: 0.35rem; justify-content: center;">
                            <button class="btn btn-sm btn-secondary" onclick="openEditUserModal(${escapeHtml(JSON.stringify(acc))})" style="font-size: 0.75rem; font-weight: 700; padding: 0.3rem 0.65rem; border-radius: 6px;">
                                Edit
                            </button>
                            ${(roleStr !== 'MASTER' && acc.id !== {{ Auth::id() }}) ? `
                            <button class="btn btn-sm" onclick="deleteUserAccount(${acc.id}, '${escapeHtml(acc.name)}')" style="font-size: 0.75rem; font-weight: 700; padding: 0.3rem 0.65rem; border-radius: 6px; background: #fee2e2; color: #ef4444; border: 1px solid #fecaca;">
                                Hapus
                            </button>
                            ` : ''}
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    }

    function openEditUserModal(acc) {
        if (!acc) return;
        const form = document.getElementById('form-edit-account');
        if (form) {
            form.action = `/users/${acc.id}`;
        }
        const nameInput = document.getElementById('acc_edit_name');
        if (nameInput) nameInput.value = acc.name || '';
        
        const usernameInput = document.getElementById('acc_edit_username');
        if (usernameInput) usernameInput.value = acc.username || '';
        
        const deptSelect = document.getElementById('acc_edit_dept');
        if (deptSelect) deptSelect.value = acc.department || '';
        
        const roleSelect = document.getElementById('acc_edit_role');
        if (roleSelect) roleSelect.value = acc.role || 'User';

        const passInput = document.getElementById('acc_edit_password');
        if (passInput) passInput.value = '';

        openModal('editAccountModal');
    }

    function deleteUserAccount(id, name) {
        if (!confirm(`Apakah Anda yakin ingin menghapus akun "${name}"? Tindakan ini tidak dapat dibatalkan.`)) {
            return;
        }

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/users/${id}`;

        const csrf = document.createElement('input');
        csrf.type = 'hidden';
        csrf.name = '_token';
        csrf.value = '{{ csrf_token() }}';
        form.appendChild(csrf);

        const methodInput = document.createElement('input');
        methodInput.type = 'hidden';
        methodInput.name = '_method';
        methodInput.value = 'DELETE';
        form.appendChild(methodInput);

        document.body.appendChild(form);
        form.submit();
    }

    document.addEventListener('DOMContentLoaded', function() {
        renderAccountTable();
    });
</script>
@endsection
