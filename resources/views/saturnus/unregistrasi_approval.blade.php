@extends('layouts.app')

@section('title', 'Proses Approval Form Unregistrasi — SATURNUS')

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
    $roleColor = '#0284c7';

    if (in_array($curRole, ['master', 'admin'])) {
        $roleDesc = 'Anda login sebagai <strong>Administrator / Master</strong> (Wewenang penuh untuk verifikasi dan persetujuan semua tahap).';
        $roleColor = '#0f172a';
    } elseif (str_contains($curRole, 'staff')) {
        $staffDept = Auth::user()->department ?? 'Production';
        if (str_contains(strtoupper($staffDept), 'PRODUCTION') && str_contains(strtoupper($staffDept), 'DIES ASSY')) {
            $roleDesc = 'Anda login sebagai <strong>Staff / Section Head Departemen Production / Dies Assy (Tahap 1)</strong>. Berwenang menyetujui unregistrasi dari departemen <strong>Production</strong> dan <strong>Dies Assy</strong>.';
        } else {
            $roleDesc = 'Anda login sebagai <strong>Staff / Section Head Departemen ' . e($staffDept) . ' (Tahap 1)</strong>. Berwenang menyetujui unregistrasi dari departemen <strong>' . e($staffDept) . '</strong> Anda.';
        }
        $roleColor = '#2563eb';
    } elseif (str_contains($curRole, 'warehouse')) {
        $roleDesc = 'Anda login sebagai <strong>Warehouse Consumable (Tahap 2 / Final)</strong>. Melakukan verifikasi dan finalisasi discontinue barang setelah disetujui oleh Staff.';
        $roleColor = '#059669';
    } else {
        $roleDesc = 'Anda login sebagai <strong>User (Pembuat Form)</strong>. Bertugas membuat formulir pengajuan unregistrasi barang & memantau status persetujuan.';
        $roleColor = '#475569';
    }

    $existingFormNumbers = $formItems->pluck('form_number')->filter()->unique()->values();

    // Group items by form_number
    $groupedForms = [];
    foreach ($existingFormNumbers as $fNo) {
        $items = $formItems->where('form_number', $fNo);
        $approval = $formApprovals->firstWhere('form_number', $fNo);
        $first = $items->first();
        $parts = explode('/', $fNo);
        $dept = (count($parts) >= 2) ? strtoupper(trim($parts[1])) : ($first?->created_by_dept ?? 'Production');
        
        $status = $approval?->status ?? 'Butuh Approval Staff / Section Head';
        $isStaffApproved = (bool)($approval && ($approval->staff_signed_at || $approval->staff_signer_name));
        $isWhApproved = (bool)($approval && ($approval->warehouse_signed_at || $approval->warehouse_signer_name));

        // Determine if action needed by current user
        $needsMyAction = false;
        if (in_array($curRole, ['master', 'admin'])) {
            $needsMyAction = !$isWhApproved;
        } elseif (str_contains($curRole, 'staff')) {
            $isDeptMatch = str_contains(strtoupper($userDeptTag), $dept) || str_contains($dept, strtoupper($userDeptTag));
            $needsMyAction = !$isStaffApproved && $isDeptMatch;
        } elseif (str_contains($curRole, 'warehouse')) {
            $needsMyAction = $isStaffApproved && !$isWhApproved;
        }

        $groupedForms[] = [
            'form_number'     => $fNo,
            'dept'            => $dept,
            'requestor'       => $approval?->requestor_name ?? $first?->created_by_name ?? 'User',
            'date'            => $approval?->form_date ?? ($first?->created_at ? $first->created_at->format('d-m-Y') : date('d-m-Y')),
            'status'          => $status,
            'items'           => $items,
            'item_count'      => $items->count(),
            'approval'        => $approval,
            'is_staff_done'   => $isStaffApproved,
            'is_wh_done'      => $isWhApproved,
            'needs_my_action' => $needsMyAction,
        ];
    }

    $totalForms = count($groupedForms);
    $pendingStaffCount = collect($groupedForms)->where('is_staff_done', false)->count();
    $pendingWhCount = collect($groupedForms)->where('is_staff_done', true)->where('is_wh_done', false)->count();
    $completedCount = collect($groupedForms)->where('is_wh_done', true)->count();
    $myActionCount = collect($groupedForms)->where('needs_my_action', true)->count();
@endphp

<style>
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
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

    .saturnus-page-wrapper {
        padding: 1.5rem 2rem;
    }

    /* Stat Cards */
    .stat-card-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 1.25rem;
        margin-bottom: 1.5rem;
    }

    .stat-card-glass {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 18px;
        padding: 1.25rem 1.4rem;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
        display: flex;
        align-items: center;
        justify-content: space-between;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .stat-card-glass:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.06);
    }

    .stat-card-info .stat-card-label {
        font-size: 0.775rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #64748b;
        margin-bottom: 0.35rem;
    }

    .stat-card-info .stat-card-value {
        font-size: 1.85rem;
        font-weight: 800;
        color: #0f172a;
        line-height: 1;
    }

    .stat-card-icon {
        width: 48px;
        height: 48px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .stat-card-icon.blue { background: #e0f2fe; color: #0284c7; }
    .stat-card-icon.amber { background: #fef3c7; color: #d97706; }
    .stat-card-icon.purple { background: #f3e8ff; color: #9333ea; }
    .stat-card-icon.green { background: #dcfce7; color: #16a34a; }

    /* Role Banner */
    .role-banner-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-left: 5px solid #0284c7;
        border-radius: 16px;
        padding: 1rem 1.25rem;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
    }

    .role-banner-left {
        display: flex;
        align-items: center;
        gap: 0.85rem;
    }

    .role-banner-badge {
        background: #0284c7;
        color: #ffffff;
        font-size: 0.75rem;
        font-weight: 700;
        padding: 0.35rem 0.75rem;
        border-radius: 20px;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    /* Main Table Container */
    .table-container-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 20px;
        padding: 1.5rem;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
    }

    .table-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem;
        margin-bottom: 1.25rem;
    }

    .filter-pills-row {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .filter-pill {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 20px;
        padding: 0.4rem 0.95rem;
        font-size: 0.8rem;
        font-weight: 600;
        color: #475569;
        cursor: pointer;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
    }

    .filter-pill:hover {
        background: #f1f5f9;
        color: #0f172a;
    }

    .filter-pill.active {
        background: #0284c7;
        color: #ffffff;
        border-color: #0284c7;
        box-shadow: 0 2px 8px rgba(2, 132, 199, 0.3);
    }

    .filter-pill .pill-badge {
        background: rgba(0, 0, 0, 0.08);
        padding: 0.1rem 0.45rem;
        border-radius: 10px;
        font-size: 0.725rem;
    }

    .filter-pill.active .pill-badge {
        background: rgba(255, 255, 255, 0.25);
        color: #ffffff;
    }

    .search-input-wrapper {
        position: relative;
        min-width: 280px;
    }

    .search-input-wrapper input {
        width: 100%;
        height: 38px;
        border-radius: 12px;
        border: 1px solid #cbd5e1;
        padding: 0.4rem 0.85rem 0.4rem 2.25rem;
        font-size: 0.85rem;
        outline: none;
        transition: border-color 0.2s;
    }

    .search-input-wrapper input:focus {
        border-color: #0284c7;
        box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15);
    }

    .search-input-wrapper svg {
        position: absolute;
        left: 0.75rem;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
    }

    /* Table Styling */
    .custom-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        font-size: 0.85rem;
    }

    .custom-table th {
        background: #f8fafc;
        color: #475569;
        font-weight: 700;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        padding: 0.85rem 1rem;
        border-bottom: 1.5px solid #e2e8f0;
        text-align: left;
    }

    .custom-table td {
        padding: 1rem;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
        color: #1e293b;
    }

    .custom-table tr:hover td {
        background: #f8fafc;
    }

    /* Stepper mini badge */
    .approval-stepper-mini {
        display: flex;
        align-items: center;
        gap: 0.35rem;
    }

    .step-node {
        display: flex;
        align-items: center;
        gap: 0.25rem;
        font-size: 0.725rem;
        font-weight: 600;
        padding: 0.2rem 0.5rem;
        border-radius: 6px;
    }

    .step-node.done {
        background: #dcfce7;
        color: #15803d;
    }

    .step-node.pending {
        background: #fef3c7;
        color: #b45309;
    }

    .step-node.waiting {
        background: #f1f5f9;
        color: #94a3b8;
    }

    .step-arrow {
        color: #cbd5e1;
        font-size: 0.7rem;
    }

    /* Action Buttons */
    .btn-action-group {
        display: flex;
        align-items: center;
        gap: 0.4rem;
    }

    .btn-appr-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.35rem;
        padding: 0.45rem 0.8rem;
        border-radius: 10px;
        font-size: 0.775rem;
        font-weight: 700;
        cursor: pointer;
        border: none;
        text-decoration: none;
        transition: all 0.2s ease;
    }

    .btn-appr-primary {
        background: #0284c7;
        color: #ffffff;
    }
    .btn-appr-primary:hover {
        background: #0369a1;
        color: #ffffff;
        box-shadow: 0 4px 10px rgba(2, 132, 199, 0.3);
    }

    .btn-appr-outline {
        background: #ffffff;
        border: 1px solid #cbd5e1;
        color: #334155;
    }
    .btn-appr-outline:hover {
        background: #f1f5f9;
        color: #0f172a;
    }

    /* Modal Standard */
    .modal {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: rgba(2, 6, 23, 0.7) !important;
        backdrop-filter: blur(10px) !important;
        display: none !important;
        align-items: center !important;
        justify-content: center !important;
        z-index: 1060 !important;
        opacity: 0;
        transition: opacity 0.25s ease;
    }

    .modal.show {
        display: flex !important;
        opacity: 1 !important;
    }

    .modal-content-custom {
        background: #ffffff;
        border-radius: 20px;
        padding: 2rem;
        width: 90%;
        max-width: 580px;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.25);
        border: 1px solid #e2e8f0;
    }

    @media (max-width: 992px) {
        .stat-card-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 576px) {
        .stat-card-grid {
            grid-template-columns: 1fr;
        }
        .saturnus-page-wrapper {
            padding: 1rem;
        }
    }
</style>

<div class="saturnus-page-wrapper">

    {{-- STAT CARDS --}}
    <div class="stat-card-grid">
        <div class="stat-card-glass">
            <div class="stat-card-info">
                <div class="stat-card-label">Total Pengajuan</div>
                <div class="stat-card-value">{{ $totalForms }}</div>
            </div>
            <div class="stat-card-icon blue">
                <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                </svg>
            </div>
        </div>

        <div class="stat-card-glass">
            <div class="stat-card-info">
                <div class="stat-card-label">Menunggu Staff (Tahap 1)</div>
                <div class="stat-card-value" style="color: #d97706;">{{ $pendingStaffCount }}</div>
            </div>
            <div class="stat-card-icon amber">
                <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
            </div>
        </div>

        <div class="stat-card-glass">
            <div class="stat-card-info">
                <div class="stat-card-label">Menunggu Warehouse (Tahap 2)</div>
                <div class="stat-card-value" style="color: #9333ea;">{{ $pendingWhCount }}</div>
            </div>
            <div class="stat-card-icon purple">
                <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none">
                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                </svg>
            </div>
        </div>

        <div class="stat-card-glass">
            <div class="stat-card-info">
                <div class="stat-card-label">Selesai Discontinue</div>
                <div class="stat-card-value" style="color: #16a34a;">{{ $completedCount }}</div>
            </div>
            <div class="stat-card-icon green">
                <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
            </div>
        </div>
    </div>

    {{-- ROLE BANNER --}}
    <div class="role-banner-card">
        <div class="role-banner-left">
            <span class="role-banner-badge">{{ $roleName }}</span>
            <div style="font-size: 0.875rem; color: #334155;">{!! $roleDesc !!}</div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('saturnus.form_unregistrasi') }}" class="btn-appr-action btn-appr-primary">
                <svg viewBox="0 0 24 24" width="15" height="15" stroke="currentColor" stroke-width="2.5" fill="none">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                <span>+ Buat Form Baru</span>
            </a>
        </div>
    </div>

    {{-- MAIN TABLE CARD --}}
    <div class="table-container-card">
        <div class="table-toolbar">
            <div class="filter-pills-row">
                <button type="button" class="filter-pill active" data-filter="all">
                    <span>Semua Formulir</span>
                    <span class="pill-badge">{{ $totalForms }}</span>
                </button>
                <button type="button" class="filter-pill" data-filter="my-action">
                    <span>⚡ Butuh Tindakan Saya</span>
                    <span class="pill-badge">{{ $myActionCount }}</span>
                </button>
                <button type="button" class="filter-pill" data-filter="staff">
                    <span>Menunggu Staff</span>
                    <span class="pill-badge">{{ $pendingStaffCount }}</span>
                </button>
                <button type="button" class="filter-pill" data-filter="warehouse">
                    <span>Menunggu Warehouse</span>
                    <span class="pill-badge">{{ $pendingWhCount }}</span>
                </button>
                <button type="button" class="filter-pill" data-filter="completed">
                    <span>Selesai Discontinue</span>
                    <span class="pill-badge">{{ $completedCount }}</span>
                </button>
            </div>

            <div class="search-input-wrapper">
                <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <input type="text" id="unregSearchInput" placeholder="Cari nomor form, barang, dept...">
            </div>
        </div>

        <div class="table-responsive">
            <table class="custom-table" id="unregApprovalTable">
                <thead>
                    <tr>
                        <th style="width: 140px;">No. Formulir</th>
                        <th style="width: 100px;">Tanggal</th>
                        <th style="width: 130px;">Departemen</th>
                        <th>Barang Discontinue</th>
                        <th style="width: 250px;">Workflow Status</th>
                        <th style="width: 180px; text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($groupedForms as $form)
                        @php
                            $filterType = 'completed';
                            if (!$form['is_staff_done']) {
                                $filterType = 'staff';
                            } elseif (!$form['is_wh_done']) {
                                $filterType = 'warehouse';
                            }

                            $firstItem = $form['items']->first();
                        @endphp
                        <tr class="form-row" 
                            data-filter-type="{{ $filterType }}"
                            data-needs-action="{{ $form['needs_my_action'] ? '1' : '0' }}"
                            data-search="{{ strtolower($form['form_number'] . ' ' . $form['dept'] . ' ' . $form['requestor'] . ' ' . ($firstItem?->kode_barang ?? '') . ' ' . ($firstItem?->nama_barang ?? '')) }}">
                            
                            {{-- Form Number --}}
                            <td>
                                <a href="{{ route('saturnus.form_unregistrasi', ['form' => $form['form_number']]) }}" style="font-weight: 700; color: #0284c7; text-decoration: none;">
                                    {{ $form['form_number'] }}
                                </a>
                                <div style="font-size: 0.725rem; color: #64748b;">Pembuat: {{ $form['requestor'] }}</div>
                            </td>

                            {{-- Date --}}
                            <td>
                                <span style="font-size: 0.8rem; font-weight: 600; color: #334155;">{{ $form['date'] }}</span>
                            </td>

                            {{-- Dept --}}
                            <td>
                                <span style="display: inline-block; background: #f1f5f9; color: #334155; font-size: 0.75rem; font-weight: 700; padding: 0.25rem 0.65rem; border-radius: 8px;">
                                    {{ $form['dept'] }}
                                </span>
                            </td>

                            {{-- Item Discontinue --}}
                            <td>
                                @if($firstItem)
                                    <div style="font-weight: 700; color: #0f172a; font-size: 0.875rem;">
                                        {{ $firstItem->nama_barang }}
                                    </div>
                                    <div style="font-size: 0.775rem; color: #64748b; margin-top: 0.15rem;">
                                        <span style="font-family: monospace; font-weight: 600; color: #0284c7;">{{ $firstItem->kode_barang }}</span>
                                        @if($firstItem->kategori)
                                            · <span style="color: #475569;">{{ $firstItem->kategori }}</span>
                                        @endif
                                    </div>
                                    @if($firstItem->keterangan)
                                        <div style="font-size: 0.75rem; color: #64748b; font-style: italic; margin-top: 0.25rem;">
                                            "{{ Str::limit($firstItem->keterangan, 60) }}"
                                        </div>
                                    @endif
                                @else
                                    <span style="color: #94a3b8; font-style: italic;">Tidak ada item terdaftar</span>
                                @endif
                            </td>

                            {{-- Stepper Status --}}
                            <td>
                                <div class="approval-stepper-mini mb-1">
                                    {{-- User Step --}}
                                    <span class="step-node done" title="Diajukan oleh {{ $form['requestor'] }}">
                                        ✓ User
                                    </span>
                                    <span class="step-arrow">&rarr;</span>

                                    {{-- Staff Step --}}
                                    <span class="step-node {{ $form['is_staff_done'] ? 'done' : 'pending' }}" title="Staff Approver">
                                        {{ $form['is_staff_done'] ? '✓ Staff' : '⏳ Staff' }}
                                    </span>
                                    <span class="step-arrow">&rarr;</span>

                                    {{-- Warehouse Step --}}
                                    <span class="step-node {{ $form['is_wh_done'] ? 'done' : ($form['is_staff_done'] ? 'pending' : 'waiting') }}" title="Warehouse Consumable">
                                        {{ $form['is_wh_done'] ? '✓ WHC' : ($form['is_staff_done'] ? '⏳ WHC' : '○ WHC') }}
                                    </span>
                                </div>
                                <div style="font-size: 0.75rem; font-weight: 600; color: {{ $form['is_wh_done'] ? '#16a34a' : ($form['is_staff_done'] ? '#9333ea' : '#d97706') }};">
                                    {{ $form['status'] }}
                                </div>
                            </td>

                            {{-- Actions --}}
                            <td style="text-align: center;">
                                <div class="btn-action-group justify-content-center">
                                    {{-- Quick Approve Button if action needed --}}
                                    @if($form['needs_my_action'])
                                        <button type="button" class="btn-appr-action btn-appr-primary" 
                                                onclick="directApproveUnreg('{{ $form['form_number'] }}', '{{ $form['is_staff_done'] ? 'warehouse' : 'staff' }}', this)">
                                            <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2.5" fill="none">
                                                <polyline points="20 6 9 17 4 12"></polyline>
                                            </svg>
                                            <span>Approve</span>
                                        </button>
                                    @endif

                                    {{-- Detail Modal Button --}}
                                    <button type="button" class="btn-appr-action btn-appr-outline" title="Detail Formulir"
                                            onclick="openDetailModal('{{ $form['form_number'] }}', '{{ $form['dept'] }}', '{{ $form['requestor'] }}', '{{ $form['date'] }}', '{{ $form['status'] }}', '{{ $firstItem?->kode_barang ?? '' }}', '{{ addslashes($firstItem?->nama_barang ?? '') }}', '{{ addslashes($firstItem?->spesifikasi ?? '') }}', '{{ $firstItem?->kategori ?? '' }}', '{{ addslashes($firstItem?->keterangan ?? '') }}', '{{ $form['approval']?->staff_signer_name ?? '' }}', '{{ $form['approval']?->staff_signed_at ? Carbon\Carbon::parse($form['approval']->staff_signed_at)->format('d-m-Y H:i') : '' }}', '{{ $form['approval']?->warehouse_signer_name ?? '' }}', '{{ $form['approval']?->warehouse_signed_at ? Carbon\Carbon::parse($form['approval']->warehouse_signed_at)->format('d-m-Y H:i') : '' }}')">
                                        <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                            <circle cx="12" cy="12" r="3"></circle>
                                        </svg>
                                        <span>Detail</span>
                                    </button>

                                    {{-- Cetak Sheet Button --}}
                                    <a href="{{ route('saturnus.form_unregistrasi', ['form' => $form['form_number'], 'print' => 1]) }}" target="_blank" class="btn-appr-action btn-appr-outline" title="Cetak Lembar Dokumen">
                                        <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none">
                                            <polyline points="6 9 6 2 18 2 18 9"></polyline>
                                            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                                            <rect x="6" y="14" width="12" height="8"></rect>
                                        </svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 3rem 1rem; color: #94a3b8;">
                                <svg viewBox="0 0 24 24" width="48" height="48" stroke="currentColor" stroke-width="1.5" fill="none" style="margin-bottom: 0.75rem; color: #cbd5e1;">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="15" y1="9" x2="9" y2="15"></line>
                                    <line x1="9" y1="9" x2="15" y2="15"></line>
                                </svg>
                                <div style="font-weight: 700; color: #475569; font-size: 1rem;">Belum Ada Pengajuan Unregistrasi</div>
                                <p style="font-size: 0.85rem; margin-top: 0.25rem;">Klik tombol "+ Buat Form Baru" untuk memulai pengajuan unregistrasi item consumable.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

{{-- MODAL DETAIL UNREGISTRASI --}}
<div class="modal" id="modalDetailUnreg">
    <div class="modal-content-custom" style="max-width: 650px;">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div class="d-flex align-items-center gap-2">
                <div style="width: 36px; height: 36px; border-radius: 10px; background: #e0f2fe; color: #0284c7; display: flex; align-items: center; justify-content: center;">
                    <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    </svg>
                </div>
                <div>
                    <h5 style="font-weight: 800; color: #0f172a; margin: 0; font-size: 1.15rem;">Detail Pengajuan Unregistrasi</h5>
                    <div style="font-size: 0.775rem; color: #64748b;" id="dtModalFormNo">-</div>
                </div>
            </div>
            <button type="button" class="btn-close" onclick="closeDetailModal()" style="border: none; background: none; font-size: 1.25rem; cursor: pointer; color: #94a3b8;">&times;</button>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.85rem; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1rem; margin-bottom: 1rem;">
            <div>
                <span style="font-size: 0.725rem; color: #64748b; font-weight: 700; text-transform: uppercase;">Departemen:</span>
                <div style="font-weight: 700; color: #0f172a;" id="dtModalDept">-</div>
            </div>
            <div>
                <span style="font-size: 0.725rem; color: #64748b; font-weight: 700; text-transform: uppercase;">Pemohon:</span>
                <div style="font-weight: 700; color: #0f172a;" id="dtModalReq">-</div>
            </div>
            <div>
                <span style="font-size: 0.725rem; color: #64748b; font-weight: 700; text-transform: uppercase;">Tanggal Pengajuan:</span>
                <div style="font-weight: 700; color: #0f172a;" id="dtModalDate">-</div>
            </div>
            <div>
                <span style="font-size: 0.725rem; color: #64748b; font-weight: 700; text-transform: uppercase;">Status Approval:</span>
                <div style="font-weight: 700; color: #0284c7;" id="dtModalStatus">-</div>
            </div>
        </div>

        <h6 style="font-weight: 800; color: #0f172a; margin-bottom: 0.5rem; font-size: 0.9rem;">Informasi Barang Discontinue</h6>
        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1rem; margin-bottom: 1.25rem;">
            <div style="font-size: 0.775rem; color: #64748b;">Kode Barang: <strong style="color: #0284c7; font-family: monospace;" id="dtModalItemCode">-</strong></div>
            <div style="font-size: 1.05rem; font-weight: 800; color: #0f172a; margin-top: 0.2rem;" id="dtModalItemName">-</div>
            <div style="font-size: 0.825rem; color: #475569; margin-top: 0.25rem;" id="dtModalItemSpec">Spesifikasi: -</div>
            <div style="font-size: 0.825rem; color: #475569; margin-top: 0.25rem;" id="dtModalItemCategory">Kategori: -</div>
            
            <div style="margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px dashed #e2e8f0;">
                <span style="font-size: 0.725rem; font-weight: 700; color: #64748b; text-transform: uppercase;">Alasan Unregistrasi / Discontinue:</span>
                <div style="font-size: 0.85rem; color: #334155; font-style: italic; margin-top: 0.2rem;" id="dtModalItemReason">-</div>
            </div>
        </div>

        <h6 style="font-weight: 800; color: #0f172a; margin-bottom: 0.5rem; font-size: 0.9rem;">Riwayat Tanda Tangan &amp; Verifikasi</h6>
        <div style="display: flex; flex-direction: column; gap: 0.5rem; margin-bottom: 1.25rem;">
            <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.65rem 0.85rem; background: #f8fafc; border-radius: 10px; border: 1px solid #e2e8f0;">
                <div>
                    <strong style="color: #0f172a; font-size: 0.825rem;">Staff / Section Head (Tahap 1):</strong>
                    <div style="font-size: 0.75rem; color: #64748b;" id="dtModalStaffSigner">Menunggu Persetujuan</div>
                </div>
                <span style="font-size: 0.75rem; font-weight: 700;" id="dtModalStaffDate">-</span>
            </div>

            <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.65rem 0.85rem; background: #f8fafc; border-radius: 10px; border: 1px solid #e2e8f0;">
                <div>
                    <strong style="color: #0f172a; font-size: 0.825rem;">Warehouse Consumable (Tahap 2):</strong>
                    <div style="font-size: 0.75rem; color: #64748b;" id="dtModalWhSigner">Menunggu Verifikasi</div>
                </div>
                <span style="font-size: 0.75rem; font-weight: 700;" id="dtModalWhDate">-</span>
            </div>
        </div>

        <div class="d-flex align-items-center justify-content-end gap-2">
            <button type="button" class="btn-appr-action btn-appr-outline" onclick="closeDetailModal()">Tutup</button>
            <a href="#" id="dtModalPrintLink" target="_blank" class="btn-appr-action btn-appr-primary">
                <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none">
                    <polyline points="6 9 6 2 18 2 18 9"></polyline>
                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                    <rect x="6" y="14" width="12" height="8"></rect>
                </svg>
                <span>Cetak Lembar Unregistrasi</span>
            </a>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Filter Pills Logic
        const filterPills = document.querySelectorAll('.filter-pill');
        const tableRows = document.querySelectorAll('#unregApprovalTable tbody tr.form-row');

        filterPills.forEach(pill => {
            pill.addEventListener('click', function() {
                filterPills.forEach(p => p.classList.remove('active'));
                this.classList.add('active');

                const filter = this.getAttribute('data-filter');
                applyFilters();
            });
        });

        // Search Input Logic
        const searchInput = document.getElementById('unregSearchInput');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                applyFilters();
            });
        }

        function applyFilters() {
            const activeFilter = document.querySelector('.filter-pill.active')?.getAttribute('data-filter') || 'all';
            const searchTerm = searchInput ? searchInput.value.toLowerCase().trim() : '';

            tableRows.forEach(row => {
                const rowType = row.getAttribute('data-filter-type');
                const rowNeedsAction = row.getAttribute('data-needs-action') === '1';
                const rowSearch = row.getAttribute('data-search') || '';

                let matchesFilter = false;
                if (activeFilter === 'all') {
                    matchesFilter = true;
                } else if (activeFilter === 'my-action') {
                    matchesFilter = rowNeedsAction;
                } else if (activeFilter === rowType) {
                    matchesFilter = true;
                }

                let matchesSearch = true;
                if (searchTerm) {
                    matchesSearch = rowSearch.includes(searchTerm);
                }

                if (matchesFilter && matchesSearch) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
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

    async function directApproveUnreg(formNo, roleKey, btnEl) {
        let originalContent = '';
        if (btnEl) {
            originalContent = btnEl.innerHTML;
            btnEl.disabled = true;
            btnEl.style.opacity = '0.75';
            btnEl.innerHTML = `
                <svg style="animation: spin 1s linear infinite; display: inline-block; vertical-align: middle; margin-right: 3px;" viewBox="0 0 24 24" width="13" height="13" stroke="currentColor" stroke-width="2.5" fill="none"><circle cx="12" cy="12" r="10" stroke-opacity="0.25"></circle><path d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round"></path></svg>
                <span>Menyetujui...</span>
            `;
        }

        try {
            const response = await fetch('{{ route("saturnus.form_unregistrasi.approve") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    form_number: formNo,
                    role: roleKey,
                    name: '{{ Auth::user()->name ?? "User" }}',
                    comment: 'Disetujui.'
                })
            });

            const res = await response.json();
            if (response.ok && res.success) {
                showToast(res.message || 'Formulir berhasil disetujui!', 'success');
                setTimeout(() => {
                    window.location.reload();
                }, 500);
            } else {
                alert(res.message || 'Gagal menyetujui form. Pastikan Anda memiliki wewenang.');
                if (btnEl) {
                    btnEl.disabled = false;
                    btnEl.style.opacity = '1';
                    btnEl.innerHTML = originalContent;
                }
            }
        } catch (err) {
            console.error(err);
            alert('Terjadi kesalahan jaringan saat mengirim persetujuan.');
            if (btnEl) {
                btnEl.disabled = false;
                btnEl.style.opacity = '1';
                btnEl.innerHTML = originalContent;
            }
        }
    }

    // Modal Detail Helpers
    function openDetailModal(formNo, dept, req, date, status, code, name, spec, cat, reason, staffSigner, staffDate, whSigner, whDate) {
        document.getElementById('dtModalFormNo').innerText = 'Formulir: ' + formNo;
        document.getElementById('dtModalDept').innerText = dept;
        document.getElementById('dtModalReq').innerText = req;
        document.getElementById('dtModalDate').innerText = date;
        document.getElementById('dtModalStatus').innerText = status;
        document.getElementById('dtModalItemCode').innerText = code || '-';
        document.getElementById('dtModalItemName').innerText = name || '-';
        document.getElementById('dtModalItemSpec').innerText = 'Spesifikasi: ' + (spec || '-');
        document.getElementById('dtModalItemCategory').innerText = 'Kategori: ' + (cat || '-');
        document.getElementById('dtModalItemReason').innerText = reason ? ('"' + reason + '"') : 'Tidak ada alasan khusus dicantumkan.';

        document.getElementById('dtModalStaffSigner').innerText = staffSigner ? ('Disetujui oleh: ' + staffSigner) : 'Menunggu Persetujuan Staff';
        document.getElementById('dtModalStaffDate').innerText = staffDate || '-';

        document.getElementById('dtModalWhSigner').innerText = whSigner ? ('Discontinue oleh: ' + whSigner) : 'Menunggu Verifikasi Warehouse';
        document.getElementById('dtModalWhDate').innerText = whDate || '-';

        document.getElementById('dtModalPrintLink').href = "{{ url('/saturnus/form-unregistrasi') }}?form=" + encodeURIComponent(formNo) + "&print=1";

        document.getElementById('modalDetailUnreg').classList.add('show');
    }

    function closeDetailModal() {
        document.getElementById('modalDetailUnreg').classList.remove('show');
    }

    // Close on outside click
    window.addEventListener('click', function(e) {
        const dtModal = document.getElementById('modalDetailUnreg');
        if (e.target === dtModal) closeDetailModal();
    });
</script>
@endsection
