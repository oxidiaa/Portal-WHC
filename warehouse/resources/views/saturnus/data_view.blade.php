@extends('layouts.app')

@section('title', 'Data View Explorer Form Registrasi')

@section('content')
@php
    $userRoleRaw = strtoupper(trim(Auth::user()->role ?? 'USER'));
    $isMaster = in_array($userRoleRaw, ['MASTER', 'ADMIN']) || (Auth::user() && method_exists(Auth::user(), 'isMaster') && Auth::user()->isMaster());
@endphp

<style>
    /* Sheet Tabs Segmented Control */
    .sheet-tabs-container {
        display: flex !important;
        align-items: center !important;
        gap: 0.5rem !important;
        background: #ffffff !important;
        border: 1.5px solid #e2e8f0 !important;
        border-radius: var(--radius-lg) !important;
        padding: 0.45rem !important;
        margin-bottom: 1.5rem !important;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.04) !important;
        overflow-x: auto !important;
    }

    .sheet-tab {
        display: inline-flex !important;
        align-items: center !important;
        gap: 0.55rem !important;
        padding: 0.65rem 1.25rem !important;
        border-radius: var(--radius-md) !important;
        background: #f8fafc !important;
        border: 1px solid #e2e8f0 !important;
        color: #475569 !important;
        font-family: var(--font-tech) !important;
        font-size: 0.88rem !important;
        font-weight: 700 !important;
        cursor: pointer !important;
        text-decoration: none !important;
        transition: var(--transition-smooth) !important;
        white-space: nowrap !important;
        user-select: none !important;
    }

    .sheet-tab:hover {
        color: #0f172a !important;
        background: #f1f5f9 !important;
        border-color: #cbd5e1 !important;
    }

    .sheet-tab.active {
        background: linear-gradient(135deg, var(--mai-blue) 0%, #00adef 100%) !important;
        color: #ffffff !important;
        border-color: var(--mai-sky) !important;
        box-shadow: 0 4px 18px rgba(0, 173, 239, 0.45) !important;
    }
</style>

<div class="workspace-light-theme">

    <!-- Navigation Tabs -->
    @include('saturnus._tabs')

    <!-- Header Section -->
    <div class="header no-print">
        <div class="header-title">
            <div class="galactic-badge" style="margin-bottom: 0.4rem;">
                <span class="pulse-beacon"></span>
                <span>MAI CONSUMABLE REGISTRY & WORKSPACE</span>
            </div>
            <h1 class="galactic-title" style="font-size: 1.6rem; margin-bottom: 0.2rem;">Data View Explorer Form Registrasi</h1>
            <p class="galactic-subtitle">Pusat pencarian, monitoring, dan arsip seluruh formulir pendaftaran barang consumable.</p>
        </div>
        <div style="display: flex; gap: 0.65rem; align-items: center; flex-wrap: wrap;">
            <a href="{{ route('saturnus.form_registrasi') }}" class="btn btn-secondary" style="font-weight: 700; display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.65rem 1.25rem; border-radius: var(--radius-md); text-decoration: none;">
                <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2.5" fill="none">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                <span>+ Buat Form Baru</span>
            </a>
            <a href="{{ route('saturnus.proses_approval') }}" class="btn btn-secondary" style="font-weight: 700; display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.65rem 1.25rem; border-radius: var(--radius-md); text-decoration: none;">
                <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2.5" fill="none">
                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                </svg>
                <span>Proses Approval</span>
            </a>
        </div>
    </div>

    {{-- Stats Cards Row --}}
    <div class="dataview-stats">
        <div class="dataview-stat-card">
            <div style="background-color: var(--color-primary-light); color: var(--color-primary); padding: 0.75rem; border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center;">
                <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2.5" fill="none">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                </svg>
            </div>
            <div>
                <span style="font-size: 0.8rem; color: var(--text-muted); font-weight: 500; display: block; text-transform: uppercase; letter-spacing: 0.05em;">Total Checksheet</span>
                <span style="font-size: 1.5rem; font-weight: 800; color: var(--text-primary);" id="stat-total-checksheets">0</span>
            </div>
        </div>
        
        <div class="dataview-stat-card">
            <div style="background-color: var(--color-success-light); color: var(--color-success); padding: 0.75rem; border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center;">
                <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2.5" fill="none">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
            </div>
            <div>
                <span style="font-size: 0.8rem; color: var(--text-muted); font-weight: 500; display: block; text-transform: uppercase; letter-spacing: 0.05em;">Selesai (Disetujui WH)</span>
                <span style="font-size: 1.5rem; font-weight: 800; color: var(--text-primary);" id="stat-approved-checksheets">0</span>
            </div>
        </div>
        
        <div class="dataview-stat-card">
            <div style="background-color: rgba(59, 130, 246, 0.1); color: rgb(29, 78, 216); padding: 0.75rem; border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center;">
                <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2.5" fill="none">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
            </div>
            <div>
                <span style="font-size: 0.8rem; color: var(--text-muted); font-weight: 500; display: block; text-transform: uppercase; letter-spacing: 0.05em;">Proses Approval</span>
                <span style="font-size: 1.5rem; font-weight: 800; color: var(--text-primary);" id="stat-process-checksheets">0</span>
            </div>
        </div>
    </div>

    {{-- Checksheet list glass container --}}
    <div class="glass-card" style="padding: 1.5rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h3 style="font-family: var(--font-heading); font-weight: 700; color: var(--text-primary); margin: 0; font-size: 1.15rem;">
                    Daftar Formulir Registrasi (Checksheet Explorer)
                </h3>
                <p style="color: var(--text-muted); font-size: 0.82rem; margin-top: 0.2rem; margin-bottom: 0;">
                    Klik tombol <strong>Lihat Lembar</strong> untuk membuka dokumen formulir atau <strong>Cetak</strong> untuk mencetak langsung.
                </p>
            </div>
            
            <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
                <span style="font-size: 0.85rem; font-weight: 700; color: var(--text-muted); display: flex; align-items: center; gap: 0.35rem;">
                    <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                    Filter:
                </span>
                <div style="display: flex; align-items: center; gap: 0.35rem;">
                    <select id="filter-month-dataview" class="form-control" style="width: 140px; height: 38px; font-size: 0.85rem; padding: 0 0.6rem;" onchange="renderDataViewTable()">
                        <option value="">Semua Bulan</option>
                        <option value="01">Januari</option>
                        <option value="02">Februari</option>
                        <option value="03">Maret</option>
                        <option value="04">April</option>
                        <option value="05">Mei</option>
                        <option value="06">Juni</option>
                        <option value="07">Juli</option>
                        <option value="08">Agustus</option>
                        <option value="09">September</option>
                        <option value="10">Oktober</option>
                        <option value="11">November</option>
                        <option value="12">Desember</option>
                    </select>
                </div>
                <div style="display: flex; align-items: center; gap: 0.35rem;">
                    <select id="filter-year-dataview" class="form-control" style="width: 130px; height: 38px; font-size: 0.85rem; padding: 0 0.6rem;" onchange="renderDataViewTable()">
                        <option value="">Semua Tahun</option>
                    </select>
                </div>
            </div>
        </div>
        
        <div class="form-reg-table-wrap">
            <table class="form-reg-table">
                <thead>
                    <tr>
                        <th class="th-center" style="width: 50px;">NO</th>
                        <th>NO. CHECKSHEET</th>
                        <th>TANGGAL BUAT</th>
                        <th>REQUESTOR / DEPT</th>
                        <th class="th-center">JUMLAH BARANG</th>
                        <th class="th-center">STATUS</th>
                        <th class="th-center" style="width: 180px;">AKSI</th>
                        @if($isMaster)
                        <th class="th-center" style="width: 120px;">HAPUS</th>
                        @endif
                    </tr>
                </thead>
                <tbody id="dataview-tbody">
                    <!-- Dynamically rendered via renderDataViewTable() -->
                </tbody>
            </table>
        </div>
    </div>

</div>

@endsection

@section('scripts')
<script>
    const serverFormItems = @json($formItems, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    const serverFormApprovals = @json($formApprovals ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    const isMasterUser = {{ $isMaster ? 'true' : 'false' }};

    function escapeHtml(text) {
        if (!text) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
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

    function populateDateFilterOptions() {
        const yearSelect = document.getElementById('filter-year-dataview');
        if (!yearSelect) return;

        const years = new Set();
        const currentYear = new Date().getFullYear();
        years.add(String(currentYear));

        serverFormItems.forEach(item => {
            if (item.created_at) {
                const y = new Date(item.created_at).getFullYear();
                if (!isNaN(y)) years.add(String(y));
            }
            if (item.form_number && item.form_number.includes('-')) {
                const parts = item.form_number.split('-');
                const possibleYear = parts[parts.length - 1];
                if (/^\d{4}$/.test(possibleYear)) years.add(possibleYear);
            }
        });

        const sortedYears = Array.from(years).sort().reverse();
        yearSelect.innerHTML = '<option value="">Semua Tahun</option>' + 
            sortedYears.map(y => `<option value="${y}">${y}</option>`).join('');
    }

    function renderDataViewTable() {
        const tbody = document.getElementById('dataview-tbody');
        if (!tbody) return;

        const selMonth = document.getElementById('filter-month-dataview')?.value || '';
        const selYear = document.getElementById('filter-year-dataview')?.value || '';

        // Group items into checksheets
        const formMap = {};
        serverFormItems.forEach(item => {
            const fNo = item.form_number || '01/PRODUCTION/' + new Date().getFullYear();
            if (!formMap[fNo]) {
                formMap[fNo] = {
                    formNo: fNo,
                    items: [],
                    requestor: item.created_by_name || item.user?.name || 'User',
                    department: item.created_by_dept || item.user?.department || 'Production',
                    createdAt: item.created_at || null,
                    date: item.created_at ? formatApprovalDateStr(item.created_at) : '-'
                };
            }
            formMap[fNo].items.push(item);
        });

        // Also include forms from approvals
        serverFormApprovals.forEach(appr => {
            const fNo = appr.form_number;
            if (fNo && !formMap[fNo]) {
                formMap[fNo] = {
                    formNo: fNo,
                    items: [],
                    requestor: appr.requestor_name || 'User',
                    department: appr.requestor_dept || 'Production',
                    createdAt: appr.created_at || null,
                    date: appr.form_date || (appr.created_at ? formatApprovalDateStr(appr.created_at) : '-')
                };
            }
        });

        const checksheets = Object.values(formMap);

        // Calculate statistics
        let totalCount = checksheets.length;
        let approvedCount = 0;
        let processCount = 0;

        checksheets.forEach(cs => {
            const dbAppr = serverFormApprovals.find(a => a.form_number === cs.formNo);
            const status = dbAppr?.status || 'Butuh Approval Staff / Section Head';
            if (dbAppr?.warehouse_signed_at || status === 'Item Telah didaftarkan' || status === 'SELESAI') {
                approvedCount++;
            } else {
                processCount++;
            }
        });

        const elTotal = document.getElementById('stat-total-checksheets');
        const elAppr = document.getElementById('stat-approved-checksheets');
        const elProc = document.getElementById('stat-process-checksheets');
        if (elTotal) elTotal.textContent = totalCount;
        if (elAppr) elAppr.textContent = approvedCount;
        if (elProc) elProc.textContent = processCount;

        // Apply Month & Year filter
        const filtered = checksheets.filter(cs => {
            let itemMonth = '';
            let itemYear = '';

            if (cs.createdAt) {
                const d = new Date(cs.createdAt);
                if (!isNaN(d.getTime())) {
                    itemMonth = String(d.getMonth() + 1).padStart(2, '0');
                    itemYear = String(d.getFullYear());
                }
            }

            if (!itemMonth || !itemYear) {
                // Parse from form number format: XX/DEPT/MM-YYYY
                const parts = (cs.formNo || '').split('/');
                if (parts.length >= 3) {
                    const datePart = parts[2].split('-');
                    if (datePart.length === 2) {
                        itemMonth = datePart[0];
                        itemYear = datePart[1];
                    }
                }
            }

            if (selMonth && itemMonth !== selMonth) return false;
            if (selYear && itemYear !== selYear) return false;
            return true;
        });

        if (filtered.length === 0) {
            const isFiltered = Boolean(selMonth || selYear);
            tbody.innerHTML = `
                <tr>
                    <td colspan="${isMasterUser ? 8 : 7}" style="text-align: center; padding: 3rem 1.5rem; color: var(--text-muted);">
                        <div style="font-size: 2.25rem; margin-bottom: 0.65rem;">📂</div>
                        <h4 class="empty-state-title" style="font-size: 1rem; font-weight: 700; color: #475569;">
                            ${isFiltered ? 'Tidak Ada Form Registrasi pada Periode Ini' : 'Belum Ada Form Registrasi Berisi Data'}
                        </h4>
                        <p class="empty-state-desc" style="font-size: 0.82rem; margin: 0;">
                            ${isFiltered ? 'Tidak ditemukan data formulir pendaftaran barang untuk filter Bulan / Tahun yang dipilih.' : 'Formulir registrasi akan tampil secara otomatis di sini setelah Anda menambahkan item barang.'}
                        </p>
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = filtered.map((cs, idx) => {
            const fNo = cs.formNo;
            const dbAppr = serverFormApprovals.find(a => a.form_number === fNo);
            const status = dbAppr?.status || 'Butuh Approval Staff / Section Head';
            const whDone = Boolean(dbAppr?.warehouse_signed_at || status === 'Item Telah didaftarkan' || status === 'SELESAI');

            let statusBadge = '';
            if (whDone) {
                statusBadge = '<span class="status-badge" style="background: rgba(16,185,129,0.15); color: #059669; font-weight: 700; border: 1px solid rgba(16,185,129,0.3); padding: 0.35rem 0.65rem; border-radius: 8px; font-size: 0.78rem;">✓ Telah Diregistrasi (WH)</span>';
            } else if (dbAppr?.accounting_signed_at) {
                statusBadge = '<span class="status-badge" style="background: rgba(16,185,129,0.1); color: #059669; font-weight: 700; border: 1px solid rgba(16,185,129,0.25); padding: 0.35rem 0.65rem; border-radius: 8px; font-size: 0.78rem;">⏳ Butuh WH Consumable</span>';
            } else if (dbAppr?.staff_signed_at) {
                statusBadge = '<span class="status-badge" style="background: rgba(59,130,246,0.1); color: #2563eb; font-weight: 700; border: 1px solid rgba(59,130,246,0.25); padding: 0.35rem 0.65rem; border-radius: 8px; font-size: 0.78rem;">⏳ Butuh Accounting</span>';
            } else {
                statusBadge = '<span class="status-badge" style="background: rgba(245,158,11,0.1); color: #d97706; font-weight: 700; border: 1px solid rgba(245,158,11,0.25); padding: 0.35rem 0.65rem; border-radius: 8px; font-size: 0.78rem;">⏳ Butuh Staff / Section</span>';
            }

            const formUrl = `{{ route('saturnus.form_registrasi') }}?form=${encodeURIComponent(fNo)}`;

            return `
                <tr>
                    <td class="th-center" style="font-weight: 700; color: var(--text-muted);">${idx + 1}</td>
                    <td>
                        <strong style="color: var(--color-primary); font-family: var(--font-tech); font-size: 0.88rem;">${escapeHtml(fNo)}</strong>
                    </td>
                    <td style="font-size: 0.82rem; color: #475569; font-weight: 600;">${cs.date}</td>
                    <td>
                        <div style="font-weight: 700; color: #0f172a; font-size: 0.85rem;">${escapeHtml(cs.requestor)}</div>
                        <div style="font-size: 0.75rem; color: #64748b;">${escapeHtml(cs.department)}</div>
                    </td>
                    <td class="th-center">
                        <span style="display: inline-block; font-weight: 800; padding: 0.2rem 0.65rem; border-radius: 12px; background: rgba(0, 173, 239, 0.1); color: #0284c7; font-size: 0.8rem;">
                            ${cs.items.length} Item
                        </span>
                    </td>
                    <td class="th-center">${statusBadge}</td>
                    <td class="th-center">
                        <div style="display: flex; gap: 0.4rem; justify-content: center; align-items: center;">
                            <a href="${formUrl}" class="btn btn-sm btn-primary" style="font-size: 0.75rem; font-weight: 700; padding: 0.35rem 0.75rem; border-radius: 8px; text-decoration: none;">
                                Lihat Lembar
                            </a>
                        </div>
                    </td>
                    ${isMasterUser ? `
                    <td class="th-center">
                        <button class="btn btn-sm" onclick="deleteChecksheetForm('${escapeHtml(fNo)}')" style="font-size: 0.75rem; font-weight: 700; padding: 0.35rem 0.65rem; border-radius: 8px; background: #fee2e2; color: #ef4444; border: 1px solid #fecaca;">
                            Hapus Form
                        </button>
                    </td>
                    ` : ''}
                </tr>
            `;
        }).join('');
    }

    function deleteChecksheetForm(formNo) {
        if (!isMasterUser) {
            alert('Akses ditolak: Hanya Role Master yang dapat menghapus formulir registrasi secara permanen.');
            return;
        }

        if (!confirm(`Apakah Anda yakin ingin menghapus PERMANEN seluruh Form Registrasi "${formNo}" ini?\n\nPERINGATAN: Seluruh item barang, data approval, dan riwayat diskusi di dalam form ini akan terhapus secara permanen!`)) {
            return;
        }

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route("form-registrasi.delete-checksheet") }}';

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

        const fNoInput = document.createElement('input');
        fNoInput.type = 'hidden';
        fNoInput.name = 'form_number';
        fNoInput.value = formNo;
        form.appendChild(fNoInput);

        document.body.appendChild(form);
        form.submit();
    }

    document.addEventListener('DOMContentLoaded', function() {
        populateDateFilterOptions();
        renderDataViewTable();
    });
</script>
@endsection
