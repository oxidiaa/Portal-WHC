@extends('layouts.app')

@section('title', 'Proses Approval Form Registrasi')

@section('content')
@php
    $userDeptTag = strtoupper(Auth::user()->department ?? Auth::user()->name ?? 'PRODUCTION');
    $userRoleRaw = strtoupper(trim(Auth::user()->role ?? 'USER'));
    $canViewAllDept = in_array($userRoleRaw, ['MASTER', 'ADMIN'])
        || str_contains($userRoleRaw, 'ACCOUNTING')
        || str_contains($userRoleRaw, 'ACC')
        || str_contains($userRoleRaw, 'WAREHOUSE');

    $curRole = strtolower(Auth::user()->role ?? 'user');
    $roleName = Auth::user()->role ?? 'User';
    $roleDesc = '';
    $roleColor = '#2563eb';
    if (in_array($curRole, ['master', 'admin'])) {
        $roleDesc = 'Anda login sebagai <strong>Administrator / Master</strong> (Wewenang penuh untuk verifikasi semua tahap approval).';
        $roleColor = '#0f172a';
    } elseif (str_contains($curRole, 'staff')) {
        $staffDept = Auth::user()->department ?? 'Production';
        if (str_contains(strtoupper($staffDept), 'PRODUCTION') && str_contains(strtoupper($staffDept), 'DIES ASSY')) {
            $roleDesc = 'Anda login sebagai <strong>Staff / Section Head Departemen Production / Dies Assy (Tahap 1)</strong>. Berwenang menyetujui formulir dari departemen <strong>Production</strong> dan <strong>Dies Assy</strong>.';
        } else {
            $roleDesc = 'Anda login sebagai <strong>Staff / Section Head Departemen ' . e($staffDept) . ' (Tahap 1)</strong>. Hanya berwenang menyetujui formulir dari departemen <strong>' . e($staffDept) . '</strong> Anda.';
        }
        $roleColor = '#2563eb';
    } elseif (str_contains($curRole, 'acc') || str_contains($curRole, 'accounting')) {
        $roleDesc = 'Anda login sebagai <strong>Accounting (Tahap 2)</strong>. Hanya dapat menyetujui formulir setelah disetujui oleh Staff / Section Head.';
        $roleColor = '#d97706';
    } elseif (str_contains($curRole, 'warehouse')) {
        $roleDesc = 'Anda login sebagai <strong>Warehouse Consumable (Tahap 3)</strong>. Melakukan finalisasi registrasi barang setelah formulir disetujui oleh Accounting.';
        $roleColor = '#059669';
    } else {
        $roleDesc = 'Anda login sebagai <strong>User (Pembuat Form)</strong>. Bertugas membuat formulir registrasi & mengajukannya ke Staff / Section Head. <em>(Akun User tidak memiliki wewenang approval)</em>.';
        $roleColor = '#475569';
    }
@endphp

<style>
    .filter-pill-btn {
        border-radius: 20px !important;
        font-size: 0.78rem !important;
        font-weight: 700 !important;
        padding: 0.4rem 0.95rem !important;
        transition: var(--transition-smooth);
    }

    .filter-pill-btn.active {
        background: var(--mai-blue) !important;
        color: #ffffff !important;
        box-shadow: 0 2px 8px rgba(26, 63, 168, 0.3);
    }

    .modal {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: rgba(2, 6, 23, 0.75) !important;
        backdrop-filter: blur(12px) !important;
        -webkit-backdrop-filter: blur(12px) !important;
        display: none !important;
        align-items: center !important;
        justify-content: center !important;
        z-index: 1060 !important;
        opacity: 0;
        pointer-events: none !important;
        visibility: hidden !important;
        transition: opacity 0.25s ease;
    }

    .modal.show {
        display: flex !important;
        opacity: 1 !important;
        pointer-events: auto !important;
        visibility: visible !important;
    }

    .modal-content {
        background: #ffffff !important;
        border: 1.5px solid #e2e8f0 !important;
        color: #0f172a !important;
        box-shadow: 0 25px 60px rgba(0, 0, 0, 0.3) !important;
        border-radius: 20px !important;
        width: 92% !important;
        max-width: 540px !important;
        padding: 2rem 2.25rem !important;
        transform: scale(0.95);
        transition: transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
        position: relative;
        z-index: 1070 !important;
        max-height: 90vh;
        overflow-y: auto;
        pointer-events: auto !important;
    }

    .modal.show .modal-content {
        transform: scale(1) !important;
        pointer-events: auto !important;
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

    .toast-container {
        position: fixed;
        bottom: 2rem;
        right: 2rem;
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        z-index: 9999;
        pointer-events: none;
    }

    .toast {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.9rem 1.25rem;
        background: #ffffff;
        color: #0f172a;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        border: 1px solid #e2e8f0;
        font-size: 0.88rem;
        font-weight: 600;
        opacity: 0;
        transform: translateY(20px);
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        pointer-events: auto;
    }

    .toast.show {
        opacity: 1;
        transform: translateY(0);
    }
</style>

<div class="workspace-light-theme">

    <!-- Header Section -->
    <div class="header no-print">
        <div class="header-title">
            <div class="galactic-badge" style="margin-bottom: 0.4rem;">
                <span class="pulse-beacon"></span>
                <span>MAI CONSUMABLE REGISTRY & WORKSPACE</span>
            </div>
            <h1 class="galactic-title" style="font-size: 1.6rem; margin-bottom: 0.2rem;">Proses Approval Form Registrasi</h1>
            <p class="galactic-subtitle">Monitoring tahapan persetujuan formulir pendaftaran barang consumable 4-tahap.</p>
        </div>
    </div>

    {{-- Clean Header Stats Row --}}
    <div class="dataview-stats">
        <div class="dataview-stat-card">
            <div style="background-color: var(--color-primary-light); color: var(--color-primary); padding: 0.75rem; border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center;">
                <svg viewBox="0 0 24 24" width="22" height="22" stroke="currentColor" stroke-width="2.5" fill="none">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                </svg>
            </div>
            <div>
                <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">Total Form (Proses)</span>
                <span style="font-size: 1.4rem; font-weight: 800; color: var(--text-primary);" id="approval-stat-total">0</span>
            </div>
        </div>

        <div class="dataview-stat-card">
            <div style="background-color: rgba(245, 158, 11, 0.1); color: rgb(217, 119, 6); padding: 0.75rem; border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center;">
                <svg viewBox="0 0 24 24" width="22" height="22" stroke="currentColor" stroke-width="2.5" fill="none">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
            </div>
            <div>
                <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">Butuh Staff / Section Head</span>
                <span style="font-size: 1.4rem; font-weight: 800; color: var(--text-primary);" id="approval-stat-staff">0</span>
            </div>
        </div>

        <div class="dataview-stat-card">
            <div style="background-color: rgba(59, 130, 246, 0.1); color: rgb(29, 78, 216); padding: 0.75rem; border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center;">
                <svg viewBox="0 0 24 24" width="22" height="22" stroke="currentColor" stroke-width="2.5" fill="none">
                    <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                    <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                </svg>
            </div>
            <div>
                <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">Butuh Accounting</span>
                <span style="font-size: 1.4rem; font-weight: 800; color: var(--text-primary);" id="approval-stat-accounting">0</span>
            </div>
        </div>

        <div class="dataview-stat-card">
            <div style="background-color: var(--color-success-light); color: var(--color-success); padding: 0.75rem; border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center;">
                <svg viewBox="0 0 24 24" width="22" height="22" stroke="currentColor" stroke-width="2.5" fill="none">
                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                </svg>
            </div>
            <div>
                <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">Butuh Warehouse Consumable</span>
                <span style="font-size: 1.4rem; font-weight: 800; color: var(--text-primary);" id="approval-stat-warehouse">0</span>
            </div>
        </div>
    </div>

    {{-- User Role Access Information Banner --}}
    <div style="background: #ffffff; border-left: 4px solid {{ $roleColor }}; border-radius: var(--radius-md); padding: 0.85rem 1.25rem; margin-top: 1rem; box-shadow: var(--shadow-sm); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem;">
        <div style="display: flex; align-items: center; gap: 0.75rem;">
            <div style="width: 36px; height: 36px; border-radius: 50%; background: {{ $roleColor }}18; color: {{ $roleColor }}; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.85rem; flex-shrink: 0;">
                <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2.5" fill="none">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
            </div>
            <div>
                <div style="font-size: 0.72rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em;">Hak Akses Login: <span style="color: {{ $roleColor }}; font-weight: 800;">{{ $roleName }}</span></div>
                <div style="font-size: 0.84rem; color: var(--text-dark); margin-top: 0.1rem;">{!! $roleDesc !!}</div>
            </div>
        </div>
    </div>

    {{-- Approval Container Card --}}
    <div class="glass-card" style="padding: 1.5rem; margin-top: 1.25rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h3 style="font-family: var(--font-heading); font-weight: 700; color: var(--text-primary); margin: 0; font-size: 1.15rem;">
                    Daftar Form dan Tahap Approval
                </h3>
                <p style="color: var(--text-muted); font-size: 0.82rem; margin-top: 0.2rem; margin-bottom: 0;">
                    Alur persetujuan: <strong>User (Pembuat)</strong> ➔ <strong>Staff / Section Head</strong> ➔ <strong>Accounting</strong> ➔ <strong>Warehouse Consumable (Registrasi)</strong>.
                </p>
            </div>

            {{-- Quick Stage Filter Pills --}}
            <div style="display: flex; gap: 0.4rem; flex-wrap: wrap;" id="approval-filter-pills">
                <button class="btn btn-sm btn-primary filter-pill-btn active" onclick="filterApprovalStage('', this)" style="border-radius: 20px; font-size: 0.78rem; font-weight: 600; padding: 0.35rem 0.85rem;">
                    Semua Form (Proses)
                </button>
                <button class="btn btn-sm btn-secondary filter-pill-btn" onclick="filterApprovalStage('staff', this)" style="border-radius: 20px; font-size: 0.78rem; font-weight: 600; padding: 0.35rem 0.85rem;">
                    Butuh Staff / Section Head
                </button>
                <button class="btn btn-sm btn-secondary filter-pill-btn" onclick="filterApprovalStage('accounting', this)" style="border-radius: 20px; font-size: 0.78rem; font-weight: 600; padding: 0.35rem 0.85rem;">
                    Butuh Accounting
                </button>
                <button class="btn btn-sm btn-secondary filter-pill-btn" onclick="filterApprovalStage('warehouse', this)" style="border-radius: 20px; font-size: 0.78rem; font-weight: 600; padding: 0.35rem 0.85rem;">
                    Butuh Warehouse
                </button>
            </div>
        </div>

        {{-- Approval Monitoring Table --}}
        <div class="form-reg-table-wrap">
            <table class="form-reg-table">
                <thead>
                    <tr>
                        <th class="th-center" style="width: 40px;">NO</th>
                        <th>NO. CHECKSHEET</th>
                        <th>PEMBUAT (REQUESTOR)</th>
                        <th>TANGGAL</th>
                        <th class="th-center">PROGRESS TAHAP (4-STEP)</th>
                        <th class="th-center">STATUS</th>
                        <th class="th-center" style="width: 200px;">AKSI</th>
                    </tr>
                </thead>
                <tbody id="approval-monitoring-tbody">
                    {{-- Populated by JS renderApprovalMonitoringTable() --}}
                </tbody>
            </table>
        </div>
    </div>

</div>

{{-- ===== MODAL: QUICK APPROVAL ===== --}}
<div class="modal" id="quickApprovalModal">
    <div class="modal-content" style="max-width: 540px; padding: 1.75rem; border-radius: var(--radius-lg);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; border-bottom: 1px solid rgba(0,0,0,0.08); padding-bottom: 0.75rem;">
            <h3 style="font-family: var(--font-heading); font-weight: 700; margin: 0; color: var(--text-primary); font-size: 1.15rem; display: flex; align-items: center; gap: 0.5rem;">
                <svg viewBox="0 0 24 24" width="20" height="20" stroke="var(--color-primary)" stroke-width="2.5" fill="none"><polyline points="20 6 9 17 4 12"></polyline></svg>
                Persetujuan Form Registrasi
            </h3>
            <button class="btn-close" onclick="closeModal('quickApprovalModal')">&times;</button>
        </div>

        <div id="quick-approval-modal-body">
            {{-- Rendered dynamically via openQuickApprovalModal() --}}
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    const serverFormItems = @json($formItems, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    const serverFormApprovals = @json($formApprovals ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    const currentUserRole = '{{ strtolower(trim(Auth::user()->role ?? "user")) }}';
    const currentUserName = '{{ Auth::user()->name ?? "User" }}';
    const currentUserDept = '{{ strtoupper(trim(Auth::user()->department ?? "PRODUCTION")) }}';
    const isMasterUser = {{ in_array(strtoupper(trim(Auth::user()->role ?? '')), ['MASTER', 'ADMIN']) ? 'true' : 'false' }};

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

    function showToast(message, type = 'success') {
        let container = document.querySelector('.toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container';
            document.body.appendChild(container);
        }

        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        
        const iconSvg = type === 'success' 
            ? `<svg viewBox="0 0 24 24" width="20" height="20" stroke="#10b981" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><polyline points="20 6 9 17 4 12"></polyline></svg>`
            : `<svg viewBox="0 0 24 24" width="20" height="20" stroke="#ef4444" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>`;

        toast.innerHTML = `${iconSvg}<span>${message}</span>`;
        container.appendChild(toast);

        setTimeout(() => toast.classList.add('show'), 10);
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 400);
        }, 3500);
    }

    function escapeHtml(text) {
        if (!text) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function getUserAllowedDepartments() {
        const userDept = currentUserDept;
        if (
            (userDept.includes('PRODUCTION') && userDept.includes('DIES ASSY'))
            || userDept === 'PRODUCTION / DIES ASSY'
            || userDept === 'PRODUCTION/DIES ASSY'
        ) {
            return ['PRODUCTION', 'DIES ASSY', 'DIESASSY', 'DIES-ASSY', 'PRODUCTION / DIES ASSY', 'PRODUCTION/DIES ASSY'];
        }
        if (userDept.includes('/')) {
            return userDept.split('/').map(s => s.trim()).filter(Boolean);
        }
        return userDept ? [userDept] : ['PRODUCTION'];
    }

    function isDeptAllowed(dept) {
        if (!dept) return true;
        if (isMasterUser || currentUserRole.includes('warehouse') || currentUserRole.includes('accounting') || currentUserRole.includes('acc')) {
            return true;
        }
        const deptUpper = String(dept).trim().toUpperCase();
        const allowed = getUserAllowedDepartments();
        return allowed.some(a => deptUpper === a || deptUpper.includes(a) || a.includes(deptUpper));
    }

    function getCsDepartment(cs, fNo) {
        let fDept = '';
        if (fNo && fNo.includes('/')) {
            const parts = fNo.split('/');
            if (parts.length >= 2 && parts[1]) fDept = parts[1].trim().toUpperCase();
        }
        if (!fDept && cs && cs.department) fDept = cs.department.trim().toUpperCase();
        if (!fDept && cs && cs.items && cs.items.length > 0) {
            const firstItem = cs.items[0];
            if (firstItem.created_by_dept) fDept = firstItem.created_by_dept.trim().toUpperCase();
        }
        return fDept || 'PRODUCTION';
    }

    function formatApprovalDateStr(val) {
        if (!val) return '-';
        if (typeof val === 'string' && val.includes('T')) {
            const d = new Date(val);
            if (!isNaN(d.getTime())) {
                const dd = String(d.getDate()).padStart(2, '0');
                const mm = String(d.getMonth() + 1).padStart(2, '0');
                const yyyy = d.getFullYear();
                return `${dd}-${mm}-${yyyy}`;
            }
        }
        return val;
    }

    let currentApprovalFilterStage = '';

    function filterApprovalStage(stage, btn) {
        currentApprovalFilterStage = stage;
        document.querySelectorAll('#approval-filter-pills .filter-pill-btn').forEach(b => {
            b.classList.remove('active', 'btn-primary');
            b.classList.add('btn-secondary');
        });
        if (btn) {
            btn.classList.remove('btn-secondary');
            btn.classList.add('active', 'btn-primary');
        }
        renderApprovalMonitoringTable();
    }

    function renderApprovalMonitoringTable() {
        const tbody = document.getElementById('approval-monitoring-tbody');
        if (!tbody) return;

        // Group items by form_number
        const formMap = {};
        serverFormItems.forEach(item => {
            const fNo = item.form_number || '01/PRODUCTION/' + new Date().getFullYear();
            if (!formMap[fNo]) {
                formMap[fNo] = {
                    formNo: fNo,
                    items: [],
                    requestor: item.created_by_name || item.user?.name || 'User',
                    department: item.created_by_dept || item.user?.department || 'Production',
                    date: item.created_at ? formatApprovalDateStr(item.created_at) : '-'
                };
            }
            formMap[fNo].items.push(item);
        });

        // Also add forms from serverFormApprovals if not in formMap
        serverFormApprovals.forEach(appr => {
            const fNo = appr.form_number;
            if (fNo && !formMap[fNo]) {
                formMap[fNo] = {
                    formNo: fNo,
                    items: [],
                    requestor: appr.requestor_name || 'User',
                    department: appr.requestor_dept || 'Production',
                    date: appr.form_date || formatApprovalDateStr(appr.created_at) || '-'
                };
            }
        });

        const checksheets = Object.values(formMap);

        // Stats Counters
        let statTotal = 0;
        let statStaff = 0;
        let statAccounting = 0;
        let statWarehouse = 0;

        checksheets.forEach(cs => {
            const fNo = cs.formNo;
            const dbAppr = serverFormApprovals.find(a => a.form_number === fNo);
            const status = dbAppr?.status || 'Butuh Approval Staff / Section Head';
            statTotal++;
            if (status.includes('Staff') || status.includes('Pending Staff')) statStaff++;
            else if (status.includes('Accounting') || status.includes('Pending Accounting')) statAccounting++;
            else if (status.includes('Warehouse') || status.includes('REGISTRASI')) statWarehouse++;
        });

        const elTotal = document.getElementById('approval-stat-total');
        const elStaff = document.getElementById('approval-stat-staff');
        const elAcc = document.getElementById('approval-stat-accounting');
        const elWh = document.getElementById('approval-stat-warehouse');
        if (elTotal) elTotal.textContent = statTotal;
        if (elStaff) elStaff.textContent = statStaff;
        if (elAcc) elAcc.textContent = statAccounting;
        if (elWh) elWh.textContent = statWarehouse;

        // Apply filter
        let filtered = checksheets;
        if (currentApprovalFilterStage === 'staff') {
            filtered = checksheets.filter(cs => {
                const dbAppr = serverFormApprovals.find(a => a.form_number === cs.formNo);
                const s = dbAppr?.status || 'Butuh Approval Staff / Section Head';
                return s.includes('Staff') || s.includes('Pending Staff');
            });
        } else if (currentApprovalFilterStage === 'accounting') {
            filtered = checksheets.filter(cs => {
                const dbAppr = serverFormApprovals.find(a => a.form_number === cs.formNo);
                const s = dbAppr?.status || '';
                return s.includes('Accounting') || s.includes('Pending Accounting');
            });
        } else if (currentApprovalFilterStage === 'warehouse') {
            filtered = checksheets.filter(cs => {
                const dbAppr = serverFormApprovals.find(a => a.form_number === cs.formNo);
                const s = dbAppr?.status || '';
                return s.includes('Warehouse') || s.includes('REGISTRASI');
            });
        }

        if (filtered.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" style="text-align: center; padding: 2.5rem 1rem; color: var(--text-muted);">
                        <div style="font-size: 2rem; margin-bottom: 0.5rem;">📋</div>
                        <h4 style="font-size: 1rem; font-weight: 700; color: #475569; margin-bottom: 0.25rem;">Tidak Ada Data Formulir</h4>
                        <p style="font-size: 0.82rem; margin: 0;">Tidak ditemukan form registrasi untuk filter tahap yang dipilih.</p>
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = filtered.map((cs, idx) => {
            const fNo = cs.formNo;
            const dbAppr = serverFormApprovals.find(a => a.form_number === fNo);
            const status = dbAppr?.status || 'Butuh Approval Staff / Section Head';
            const fDept = getCsDepartment(cs, fNo);
            const reqName = dbAppr?.requestor_name || cs.requestor || 'User';

            // Steps status
            const userDone = Boolean(dbAppr?.user_signed_at || cs.items.length > 0);
            const staffDone = Boolean(dbAppr?.staff_signed_at);
            const accDone = Boolean(dbAppr?.accounting_signed_at);
            const whDone = Boolean(dbAppr?.warehouse_signed_at || status === 'Item Telah didaftarkan' || status === 'SELESAI');

            // Stepper HTML
            const makeStep = (num, label, isDone, isActive) => `
                <div style="display: flex; flex-direction: column; align-items: center; gap: 2px;">
                    <div style="width: 22px; height: 22px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.72rem; font-weight: 800; ${
                        isDone ? 'background: #10b981; color: #ffffff;' : (isActive ? 'background: #2563eb; color: #ffffff; box-shadow: 0 0 0 3px rgba(37,99,235,0.25);' : 'background: #e2e8f0; color: #64748b;')
                    }">
                        ${isDone ? '✓' : num}
                    </div>
                    <span style="font-size: 0.68rem; font-weight: 700; color: ${isDone ? '#059669' : (isActive ? '#2563eb' : '#94a3b8')};">${label}</span>
                </div>
            `;

            const isStaffActive = userDone && !staffDone;
            const isAccActive = staffDone && !accDone;
            const isWhActive = accDone && !whDone;

            const stepperHtml = `
                <div style="display: flex; align-items: center; justify-content: center; gap: 0.35rem;">
                    ${makeStep('1', 'User', userDone, false)}
                    <span style="width: 14px; height: 2px; background: ${userDone ? '#10b981' : '#cbd5e1'}; margin-bottom: 12px;"></span>
                    ${makeStep('2', 'Staff', staffDone, isStaffActive)}
                    <span style="width: 14px; height: 2px; background: ${staffDone ? '#10b981' : '#cbd5e1'}; margin-bottom: 12px;"></span>
                    ${makeStep('3', 'Acc', accDone, isAccActive)}
                    <span style="width: 14px; height: 2px; background: ${accDone ? '#10b981' : '#cbd5e1'}; margin-bottom: 12px;"></span>
                    ${makeStep('4', 'WH', whDone, isWhActive)}
                </div>
            `;

            // Status Badge
            let statusBadge = '';
            if (whDone) {
                statusBadge = '<span class="status-badge" style="background: rgba(16,185,129,0.15); color: #059669; font-weight: 700; border: 1px solid rgba(16,185,129,0.3); padding: 0.35rem 0.65rem; border-radius: 8px; font-size: 0.78rem;">✓ Telah Diregistrasi (WH)</span>';
            } else if (isWhActive) {
                statusBadge = '<span class="status-badge" style="background: rgba(16,185,129,0.1); color: #059669; font-weight: 700; border: 1px solid rgba(16,185,129,0.25); padding: 0.35rem 0.65rem; border-radius: 8px; font-size: 0.78rem;">⏳ Butuh WH Consumable</span>';
            } else if (isAccActive) {
                statusBadge = '<span class="status-badge" style="background: rgba(59,130,246,0.1); color: #2563eb; font-weight: 700; border: 1px solid rgba(59,130,246,0.25); padding: 0.35rem 0.65rem; border-radius: 8px; font-size: 0.78rem;">⏳ Butuh Accounting</span>';
            } else {
                statusBadge = '<span class="status-badge" style="background: rgba(245,158,11,0.1); color: #d97706; font-weight: 700; border: 1px solid rgba(245,158,11,0.25); padding: 0.35rem 0.65rem; border-radius: 8px; font-size: 0.78rem;">⏳ Butuh Staff / Section</span>';
            }

            // Action Button
            let actionBtn = '';
            const formUrl = `{{ route('saturnus.form_registrasi') }}?form=${encodeURIComponent(fNo)}`;

            if (whDone) {
                actionBtn = `
                    <div style="display: flex; gap: 0.35rem; justify-content: center;">
                        <a href="${formUrl}" class="btn btn-sm btn-secondary" style="font-size: 0.75rem; font-weight: 700; padding: 0.35rem 0.75rem; border-radius: 8px; text-decoration: none;">
                            Lihat Form
                        </a>
                    </div>
                `;
            } else {
                let canApprove = false;
                let targetRole = '';
                let roleLabel = '';

                if (isStaffActive) {
                    targetRole = 'staff';
                    roleLabel = 'Approve (Staff)';
                    canApprove = isMasterUser || (currentUserRole.includes('staff') && isDeptAllowed(fDept));
                } else if (isAccActive) {
                    targetRole = 'accounting';
                    roleLabel = 'Approve (Acc)';
                    canApprove = isMasterUser || currentUserRole.includes('acc') || currentUserRole.includes('accounting');
                } else if (isWhActive) {
                    targetRole = 'warehouse';
                    roleLabel = 'Selesaikan (WH)';
                    canApprove = isMasterUser || currentUserRole.includes('warehouse');
                }

                actionBtn = `
                    <div style="display: flex; gap: 0.35rem; justify-content: center; align-items: center; flex-wrap: wrap;">
                        <a href="${formUrl}" class="btn btn-sm btn-secondary" style="font-size: 0.75rem; font-weight: 700; padding: 0.35rem 0.65rem; border-radius: 8px; text-decoration: none;" title="Buka Lembar Cetak">
                            Lihat
                        </a>
                        ${canApprove ? `
                            <button class="btn btn-sm btn-primary" onclick="openQuickApprovalModal('${escapeHtml(fNo)}')" style="font-size: 0.75rem; font-weight: 700; padding: 0.35rem 0.75rem; border-radius: 8px; background: linear-gradient(135deg, #1a3fa8 0%, #00adef 100%);">
                                ${roleLabel}
                            </button>
                        ` : `
                            <button class="btn btn-sm btn-secondary" disabled style="font-size: 0.75rem; font-weight: 600; padding: 0.35rem 0.65rem; border-radius: 8px; opacity: 0.6;" title="Menunggu wewenang role terkait">
                                ${roleLabel || 'Proses'}
                            </button>
                        `}
                    </div>
                `;
            }

            return `
                <tr>
                    <td class="th-center" style="font-weight: 700; color: var(--text-muted);">${idx + 1}</td>
                    <td>
                        <strong style="color: var(--color-primary); font-family: var(--font-tech); font-size: 0.88rem;">${escapeHtml(fNo)}</strong>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">${escapeHtml(fDept)} · ${cs.items.length} Barang</div>
                    </td>
                    <td>
                        <div style="font-weight: 700; color: #0f172a; font-size: 0.85rem;">${escapeHtml(reqName)}</div>
                        <div style="font-size: 0.75rem; color: #64748b;">${escapeHtml(fDept)}</div>
                    </td>
                    <td style="font-size: 0.82rem; color: #475569; font-weight: 600;">${cs.date}</td>
                    <td class="th-center">${stepperHtml}</td>
                    <td class="th-center">${statusBadge}</td>
                    <td class="th-center">${actionBtn}</td>
                </tr>
            `;
        }).join('');
    }

    function openQuickApprovalModal(csId) {
        const modalBody = document.getElementById('quick-approval-modal-body');
        if (!modalBody) return;

        const dbAppr = serverFormApprovals.find(a => a.form_number === csId);
        const userDone = Boolean(dbAppr?.user_signed_at || true);
        const staffDone = Boolean(dbAppr?.staff_signed_at);
        const accDone = Boolean(dbAppr?.accounting_signed_at);
        const whDone = Boolean(dbAppr?.warehouse_signed_at);

        let activeStepRole = 'staff';
        let roleTitle = 'Staff / Section Head (Tahap 1)';
        let roleDesc = 'Menyetujui pendaftaran barang consumable dari departemen terkait.';

        if (!staffDone) {
            activeStepRole = 'staff';
            roleTitle = 'Staff / Section Head (Tahap 1)';
            roleDesc = 'Menyetujui pendaftaran barang consumable dari departemen terkait.';
        } else if (!accDone) {
            activeStepRole = 'accounting';
            roleTitle = 'Accounting Department (Tahap 2)';
            roleDesc = 'Menyetujui alokasi biaya dan klasifikasi consumable.';
        } else if (!whDone) {
            activeStepRole = 'warehouse';
            roleTitle = 'Warehouse Consumable (Tahap 3 - Final)';
            roleDesc = 'Menyelesaikan pendaftaran barang dan menempatkan ke sistem gudang.';
        }

        modalBody.innerHTML = `
            <div style="background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 1rem 1.25rem; margin-bottom: 1.25rem;">
                <div style="font-size: 0.75rem; color: #64748b; font-weight: 700; text-transform: uppercase;">No. Checksheet:</div>
                <div style="font-size: 1.15rem; font-weight: 800; color: #1a3fa8; font-family: var(--font-tech); margin-top: 0.15rem;">${escapeHtml(csId)}</div>
                <div style="font-size: 0.82rem; color: #334155; margin-top: 0.35rem;">
                    Tahap: <strong>${roleTitle}</strong>
                </div>
            </div>

            <form onsubmit="submitQuickApproval(event, '${escapeHtml(csId)}', '${activeStepRole}')">
                <input type="hidden" name="form_number" value="${escapeHtml(csId)}">
                <input type="hidden" name="role" value="${activeStepRole}">

                <div class="form-group" style="margin-bottom: 1rem;">
                    <label style="display: block; font-size: 0.8rem; font-weight: 700; color: #1e293b; margin-bottom: 0.35rem;">Nama Penyetuju / Verifikator <span style="color: #ef4444;">*</span></label>
                    <input type="text" id="qa_name" name="name" class="form-control" value="${escapeHtml(currentUserName)}" required style="height: 42px; border-radius: 8px; border: 1.5px solid #cbd5e1; padding: 0 0.85rem; font-size: 0.88rem; width: 100%;">
                </div>

                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-size: 0.8rem; font-weight: 700; color: #1e293b; margin-bottom: 0.35rem;">Catatan / Komentar Approval (Opsional)</label>
                    <textarea id="qa_comment" name="comment" class="form-control" rows="2" placeholder="Cth: Disetujui sesuai spesifikasi pengajuan..." style="border-radius: 8px; border: 1.5px solid #cbd5e1; padding: 0.6rem 0.85rem; font-size: 0.88rem; width: 100%; resize: vertical;"></textarea>
                </div>

                <div style="display: flex; gap: 0.75rem; justify-content: flex-end;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('quickApprovalModal')" style="padding: 0.6rem 1.25rem; font-weight: 700; border-radius: 8px;">Batal</button>
                    <button type="submit" class="btn btn-primary" style="padding: 0.6rem 1.5rem; font-weight: 700; border-radius: 8px; display: inline-flex; align-items: center; gap: 0.45rem; background: linear-gradient(135deg, #10b981 0%, #059669 100%); border: none;">
                        <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2.5" fill="none"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        <span>Konfirmasi Setujui</span>
                    </button>
                </div>
            </form>
        `;

        openModal('quickApprovalModal');
    }

    async function submitQuickApproval(event, csId, roleKey) {
        event.preventDefault();
        const formEl = event.target;
        const nameVal = formEl.querySelector('#qa_name')?.value || currentUserName;
        const commentVal = formEl.querySelector('#qa_comment')?.value || 'Disetujui.';

        try {
            const response = await fetch('{{ route("form-registrasi.approve") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    form_number: csId,
                    role: roleKey,
                    name: nameVal,
                    comment: commentVal
                })
            });

            const res = await response.json();
            if (response.ok && res.success) {
                closeModal('quickApprovalModal');
                showToast(res.message || 'Form berhasil disetujui!', 'success');
                
                // Update local model
                const exist = serverFormApprovals.find(a => a.form_number === csId);
                if (exist && res.approval) {
                    Object.assign(exist, res.approval);
                } else if (res.approval) {
                    serverFormApprovals.push(res.approval);
                }
                renderApprovalMonitoringTable();
            } else {
                alert(res.message || 'Gagal menyetujui form. Pastikan Anda memiliki wewenang.');
            }
        } catch (err) {
            console.error(err);
            alert('Terjadi kesalahan jaringan saat mengirim persetujuan.');
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        renderApprovalMonitoringTable();
    });
</script>
@endsection
