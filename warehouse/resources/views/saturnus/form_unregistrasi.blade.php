@extends('layouts.app')

@section('title', 'Form Unregistrasi Barang Consumable')

@section('content')
@php
    $userDeptTag = strtoupper(Auth::user()->department ?? Auth::user()->name ?? 'PRODUCTION');
    $userRoleRaw = strtoupper(trim(Auth::user()->role ?? 'USER'));
    $canViewAllDept = in_array($userRoleRaw, ['MASTER', 'ADMIN'])
        || str_contains($userRoleRaw, 'ACCOUNTING')
        || str_contains($userRoleRaw, 'ACC')
        || str_contains($userRoleRaw, 'WAREHOUSE');

    $allowedDepts = [strtoupper(trim(Auth::user()->department ?? 'PRODUCTION'))];
    if (
        (str_contains($userDeptTag, 'PRODUCTION') && str_contains($userDeptTag, 'DIES ASSY'))
        || (str_contains($userRoleRaw, 'PRODUCTION') && str_contains($userRoleRaw, 'DIES ASSY'))
        || $userDeptTag === 'PRODUCTION / DIES ASSY'
        || $userDeptTag === 'PRODUCTION/DIES ASSY'
    ) {
        $allowedDepts = ['PRODUCTION', 'DIES ASSY', 'DIESASSY', 'DIES-ASSY', 'PRODUCTION / DIES ASSY', 'PRODUCTION/DIES ASSY'];
    }

    $defaultDeptTag = (str_contains($userDeptTag, 'PRODUCTION') && str_contains($userDeptTag, 'DIES ASSY')) ? 'PRODUCTION' : $userDeptTag;
    $defaultFormNo = '01/' . $defaultDeptTag . '/' . date('m-Y');

    $existingFormNumbers = $formItems->pluck('form_number')->filter()->unique()->values();

    $userForms = $existingFormNumbers->filter(function($fNo) use ($allowedDepts) {
        $parts = explode('/', $fNo);
        $fDept = (count($parts) >= 2) ? strtoupper(trim($parts[1])) : '';
        return in_array($fDept, $allowedDepts);
    });

    if ($activeFormNoParam && $existingFormNumbers->contains($activeFormNoParam)) {
        $currentFormNo = $activeFormNoParam;
    } else if ($userForms->isNotEmpty()) {
        $currentFormNo = $userForms->first();
    } else if ($existingFormNumbers->isNotEmpty() && $canViewAllDept) {
        $currentFormNo = $existingFormNumbers->first();
    } else {
        $currentFormNo = $defaultFormNo;
    }

    $currentFormItems = $formItems->filter(function($item) use ($currentFormNo, $defaultFormNo) {
        $itemFormNo = $item->form_number ?: $defaultFormNo;
        return $itemFormNo === $currentFormNo;
    });

    // Dynamic metadata for current form preview
    $currentApproval = $formApprovals->firstWhere('form_number', $currentFormNo);
    $firstCurrentItem = $currentFormItems->first();
    $currentFormReqName = $currentApproval?->requestor_name ?? $firstCurrentItem?->created_by_name ?? Auth::user()->name ?? 'User';
    $currentFormReqDept = $currentApproval?->requestor_dept ?? $firstCurrentItem?->created_by_dept ?? Auth::user()->department ?? 'Production';
    $currentFormDate = $currentApproval?->form_date ?? ($firstCurrentItem?->created_at ? $firstCurrentItem->created_at->format('d-m-Y') : date('d-m-Y'));

    $userSigDate = $currentApproval?->user_signed_at ? \Carbon\Carbon::parse($currentApproval->user_signed_at)->format('d-m-Y') : $currentFormDate;
    $staffSigDate = $currentApproval?->staff_signed_at ? \Carbon\Carbon::parse($currentApproval->staff_signed_at)->format('d-m-Y') : $currentFormDate;
    $whSigDate = $currentApproval?->warehouse_signed_at ? \Carbon\Carbon::parse($currentApproval->warehouse_signed_at)->format('d-m-Y') : $currentFormDate;

    $userSigner = $currentApproval?->user_signer_name ?? $currentFormReqName;
    $staffSigner = $currentApproval?->staff_signer_name ?? 'Staff / Section Head';
    $whSigner = $currentApproval?->warehouse_signer_name ?? 'Warehouse Consumable';

    $hasUserSig = ($currentApproval && $currentApproval->user_signed_at) || $firstCurrentItem;
    $hasStaffSig = (bool)($currentApproval && ($currentApproval->staff_signed_at || $currentApproval->staff_signer_name));
    $hasWhSig = (bool)($currentApproval && ($currentApproval->warehouse_signed_at || $currentApproval->warehouse_signer_name));
@endphp
<style>
    /* Sheet Tabs & Stepper Enhancements for Form Unregistrasi */
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

    /* Modal Styling - Saturnus Theme */
    .modal {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: rgba(2, 6, 23, 0.75) !important;
        backdrop-filter: blur(12px) !important;
        -webkit-backdrop-filter: blur(12px) !important;
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
        width: 92% !important;
        max-width: 600px !important;
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

    .modal-header {
        background: transparent !important;
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
        margin-bottom: 1.25rem !important;
        padding: 0 !important;
        padding-bottom: 1.15rem !important;
        border-bottom: 1.5px solid #f1f5f9 !important;
    }

    .modal-header h3 {
        font-size: 1.25rem !important;
        font-weight: 800 !important;
        color: #0f172a !important;
        margin: 0 !important;
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

    .workspace-light-theme .modal-content {
        background: #ffffff !important;
        border: 1.5px solid #e2e8f0 !important;
        color: #0f172a !important;
        border-radius: 20px !important;
        padding: 2rem 2.25rem !important;
    }

    .workspace-light-theme .modal-header {
        background: transparent !important;
        border-bottom: 1.5px solid #f1f5f9 !important;
        padding: 0 !important;
        padding-bottom: 1.15rem !important;
        margin-bottom: 1.25rem !important;
    }

    .workspace-light-theme .modal-header h3 {
        color: #0f172a !important;
        font-weight: 800 !important;
    }

    .workspace-light-theme .form-group {
        margin-bottom: 1rem !important;
    }

    .workspace-light-theme .form-group label {
        color: #1e293b !important;
        font-weight: 700 !important;
        font-size: 0.8rem !important;
        margin-bottom: 0.35rem !important;
        display: block !important;
    }

    .workspace-light-theme .form-control {
        height: 42px !important;
        border-radius: 8px !important;
        border: 1.5px solid #e2e8f0 !important;
        padding: 0.5rem 0.85rem !important;
        font-size: 0.85rem !important;
        background: #ffffff !important;
        color: #0f172a !important;
        width: 100% !important;
        transition: border-color 0.2s, box-shadow 0.2s !important;
    }

    .workspace-light-theme .form-control:focus {
        border-color: #00adef !important;
        outline: none !important;
        box-shadow: 0 0 0 3px rgba(0, 173, 239, 0.18) !important;
    }

    .workspace-light-theme .form-control::placeholder {
        color: #94a3b8 !important;
    }

    /* Toast Container & Notification */
    .toast-container {
        position: fixed;
        top: 1.5rem;
        right: 1.5rem;
        z-index: 99999;
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        pointer-events: none;
    }

    .toast {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.875rem 1.25rem;
        border-radius: 12px;
        font-size: 0.875rem;
        font-weight: 600;
        min-width: 280px;
        max-width: 440px;
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border: 1px solid;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.6);
        opacity: 0;
        transform: translateX(30px);
        transition: all 0.35s ease;
        pointer-events: auto;
    }

    .toast.show {
        opacity: 1;
        transform: translateX(0);
    }

    .toast.success {
        background: rgba(8, 28, 22, 0.95);
        border-color: rgba(16, 185, 129, 0.5);
        color: #6ee7b7;
    }

    .toast.error {
        background: rgba(36, 10, 16, 0.95);
        border-color: rgba(239, 68, 68, 0.5);
        color: #fca5a5;
    }

    /* Form Comments & Discussion Styling */
    .form-comments-card {
        background: #ffffff !important;
        border: 1px solid #e2e8f0 !important;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05) !important;
    }
    .comment-item {
        display: flex;
        gap: 0.85rem;
        align-items: flex-start;
        padding: 0.85rem 1rem;
        background: #f8fafc;
        border-radius: 12px;
        border: 1px solid #edf2f7;
        transition: var(--transition-smooth);
    }
    .comment-item:hover {
        background: #f1f5f9;
        border-color: #cbd5e1;
    }
    .comment-avatar {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 0.82rem;
        color: #ffffff;
        flex-shrink: 0;
        box-shadow: 0 2px 6px rgba(0,0,0,0.15);
    }
    .comment-bubble {
        flex: 1;
        min-width: 0;
    }
    .comment-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 0.3rem;
        flex-wrap: wrap;
        gap: 0.4rem;
    }
    .comment-author {
        font-weight: 700;
        font-size: 0.86rem;
        color: #0f172a;
        display: flex;
        align-items: center;
        gap: 0.45rem;
    }
    .comment-role-badge {
        font-size: 0.68rem;
        font-weight: 700;
        padding: 0.12rem 0.45rem;
        border-radius: 4px;
        text-transform: uppercase;
    }
    .comment-time {
        font-size: 0.72rem;
        color: var(--text-muted);
        font-weight: 500;
    }
    .comment-body {
        font-size: 0.85rem;
        color: #334155;
        line-height: 1.5;
        white-space: pre-wrap;
        word-break: break-word;
    }
    .comment-delete-btn {
        background: transparent;
        border: none;
        color: #94a3b8;
        cursor: pointer;
        padding: 0.25rem;
        border-radius: 4px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: color 0.2s ease, background 0.2s ease;
    }
    .comment-delete-btn:hover {
        color: #ef4444;
        background: rgba(239, 68, 68, 0.1);
    }
    .comment-pill {
        cursor: pointer;
        transition: var(--transition-smooth);
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
    }
    .comment-pill:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(0, 173, 239, 0.2);
    }
    
    /* Print optimizations for sheets tabs & A4 Landscape */
    @media print {
        @page {
            size: A4 landscape;
            margin: 5mm 7mm 5mm 7mm;
        }
        .sidebar,
        .header,
        .sheet-tabs-container,
        .sheet-doc-toolbar,
        .toast-container,
        .no-print,
        .tab-pane:not(#print-preview-pane),
        #btn-tambah-data,
        #btn-form-baru,
        .floating-sidebar-toggle,
        .app-bg-grid,
        .app-bg-glow-1,
        .app-bg-glow-2,
        .app-bg-glow-3,
        .empty-state-row,
        .modal {
            display: none !important;
        }
        .main-content {
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
            max-width: 100% !important;
            background: transparent !important;
        }
        #print-preview-pane {
            display: block !important;
            width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        .form-reg-card {
            border: 1.5px solid #000000 !important;
            border-radius: 0 !important;
            box-shadow: none !important;
            background: #ffffff !important;
            margin: 0 auto !important;
            padding: 0 !important;
            width: 100% !important;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }
        .form-reg-header {
            padding: 4px 10px !important;
            border-bottom: 1.5px solid #000000 !important;
        }
        .form-reg-logo img {
            height: 28px !important;
        }
        .form-reg-company {
            font-size: 6.5pt !important;
            line-height: 1.15 !important;
        }
        .form-reg-company strong {
            font-size: 7.5pt !important;
        }
        .form-reg-title {
            font-size: 10.5pt !important;
            margin: 0 0 2px 0 !important;
        }
        .form-reg-nodoc {
            font-size: 6.8pt !important;
        }
        .form-reg-meta {
            padding: 3px 10px !important;
            border-bottom: 1.5px solid #000000 !important;
            background: #f8fafc !important;
        }
        .form-reg-meta-label, .form-reg-meta-line {
            font-size: 7pt !important;
        }
        .form-reg-table {
            width: 100% !important;
            min-width: 100% !important;
            border-collapse: collapse !important;
            border: 1px solid #000000 !important;
            table-layout: fixed !important;
            font-size: 7pt !important;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }
        .form-reg-table thead th {
            background: #e2e8f0 !important;
            color: #000000 !important;
            border: 1px solid #000000 !important;
            padding: 3px 2px !important;
            font-size: 6.5pt !important;
            line-height: 1.1 !important;
        }
        .form-reg-table tbody tr {
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }
        .form-reg-table tbody td {
            border: 1px solid #000000 !important;
            color: #000000 !important;
            background: #ffffff !important;
            padding: 2px 4px !important;
            height: 20px !important;
            font-size: 7pt !important;
            line-height: 1.15 !important;
        }
        .form-reg-table tbody tr.tr-even td {
            background-color: #fbfbfb !important;
        }
        .form-reg-table tbody td * {
            color: #000000 !important;
        }
        .item-code-badge {
            background: transparent !important;
            border: none !important;
            padding: 0 !important;
            font-size: 7pt !important;
            color: #000000 !important;
            box-shadow: none !important;
            border-radius: 0 !important;
        }
        .print-only-row {
            display: table-row !important;
        }
        .form-reg-signature {
            border-top: 1.5px solid #000000 !important;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }
        .sig-box {
            padding: 3px 6px !important;
            border-right: 1px solid #000000 !important;
        }
        .sig-label {
            font-size: 6.5pt !important;
            margin-bottom: 2px !important;
        }
        .sig-space {
            height: auto !important;
            min-height: 44px !important;
            padding: 2px 0 !important;
            font-size: 6.8pt !important;
        }
        .sig-barcode-svg {
            max-height: 20px !important;
            width: auto !important;
            max-width: 95% !important;
            margin: 1px 0 !important;
            display: block !important;
        }
        .sig-qrcode {
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            justify-content: center !important;
            margin: 1px auto !important;
        }
        .sig-qrcode canvas {
            display: none !important;
        }
        .sig-qrcode img {
            width: 32px !important;
            height: 32px !important;
            margin: 0 auto !important;
            display: block !important;
        }
        .sig-line {
            border-bottom: 1px dashed #000000 !important;
            margin: 2px 10px 0 !important;
        }
    }
</style>

<div class="workspace-light-theme">

<div class="header no-print">
    <div class="header-title">
        <div class="galactic-badge" style="margin-bottom: 0.4rem;">
            <span class="pulse-beacon"></span>
            <span>MAI CONSUMABLE REGISTRY & WORKSPACE</span>
        </div>
        <h1 class="galactic-title" style="font-size: 1.6rem; margin-bottom: 0.2rem;">Form Unregistrasi Barang Consumable</h1>
        <p class="galactic-subtitle">Lembar kerja pengajuan discontinue, monitoring approval 3-tahap, data explorer, dan account master.</p>
    </div>
    <div style="display: flex; gap: 0.65rem; align-items: center; flex-wrap: wrap;">
        <button class="btn btn-secondary" id="btn-form-baru" onclick="createNewForm()" style="font-weight: 700; display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.65rem 1.25rem; border-radius: var(--radius-md); cursor: pointer;">
            <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
            </svg>
            <span>+ Form Baru</span>
        </button>
        <button class="btn btn-secondary" onclick="printCurrentSheet()" style="font-weight: 700; display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.65rem 1.25rem; border-radius: var(--radius-md);">
            <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6 9 6 2 18 2 18 9"></polyline>
                <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                <rect x="6" y="14" width="12" height="8"></rect>
            </svg>
            <span>Cetak / Print</span>
        </button>
    </div>
</div>

{{-- ===== TAB PANE: PRINT PREVIEW ===== --}}
<div id="print-preview-pane" class="tab-pane active">
    
    <!-- Quick Document Status Bar (No Print) -->
    <div class="sheet-doc-toolbar no-print">
        <div class="doc-toolbar-left">
            <div class="doc-badge-pill">
                <span class="pulse-beacon-inline green"></span>
                <span>STATUS LEMBAR: <strong>AKTIF / SIAP CETAK</strong></span>
            </div>
            <div class="doc-badge-pill secondary">
                <span>NO FORM: <strong id="toolbar-form-no">{{ $currentFormNo }}</strong></span>
            </div>
            <div class="doc-badge-pill comment-pill" onclick="scrollToComments()" title="Lihat Diskusi & Komentar Form">
                <svg viewBox="0 0 24 24" width="13" height="13" stroke="currentColor" stroke-width="2.5" fill="none">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                </svg>
                <span>KOMENTAR: <strong id="toolbar-comment-count">0</strong></span>
            </div>
        </div>
        <div class="doc-toolbar-right">
            <button type="button" class="btn btn-secondary btn-sm" onclick="printCurrentSheet()">
                <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none">
                    <polyline points="6 9 6 2 18 2 18 9"></polyline>
                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                    <rect x="6" y="14" width="12" height="8"></rect>
                </svg>
                <span>Cetak A4</span>
            </button>
        </div>
    </div>

    <div class="glass-card form-reg-card">

        {{-- ===== FORM HEADER ===== --}}
        <div class="form-reg-header">
            <div class="form-reg-header-left">
                <div class="form-reg-logo">
                    <img src="{{ asset('assets/images/MAI GELAP.png') }}?v={{ file_exists(public_path('assets/images/MAI GELAP.png')) ? filemtime(public_path('assets/images/MAI GELAP.png')) : time() }}" alt="MAI Logo" style="height: 38px; width: auto; object-fit: contain;">
                    <div class="form-reg-company">
                        <strong>PT. METALART ASTRA INDONESIA</strong>
                        <span>Kawasan Industri KIIC, JL. Harapan III Lot- JJ 2A, Desa Sirnabaya, Kecamatan
                            Teluk Jambe Timur, Karawang 41631 Jawa Barat,<br>
                        Telp : (021) 29369960, (0267) 78639862, Fax : (021) 29369965</span>
                    </div>
                </div>
            </div>
            <div class="form-reg-header-center">
                <h2 class="form-reg-title">FORM PENDAFTARAN UNREGISTRASI CONSUMABLE</h2>
                <p class="form-reg-nodoc" id="preview-docno">No Doc : W1-CDS-PP-20/F2 Rev 0 &nbsp;|&nbsp; No. Form: <span id="form-number-display" style="font-weight: 700; color: var(--color-primary);">{{ $currentFormNo }}</span></p>
            </div>
            <div class="form-reg-header-right"></div>
        </div>

        {{-- ===== META INFO ROW ===== --}}
        <div class="form-reg-meta">
            <div class="form-reg-meta-item">
                <span class="form-reg-meta-label">TANGGAL :</span>
                <span class="form-reg-meta-line" id="preview-date">{{ $currentFormDate }}</span>
            </div>
            <div class="form-reg-meta-item">
                <span class="form-reg-meta-label">Requestor / User Dept :</span>
                <span class="form-reg-meta-line" id="preview-requestor">{{ $currentFormReqName }} / {{ $currentFormReqDept }}</span>
            </div>
        </div>

        {{-- ===== TABLE (6 COLUMNS) ===== --}}
        <div class="form-reg-table-wrap">
            <table class="form-reg-table">
                <thead>
                    <tr>
                        <th class="th-center" style="width: 4%;">NO.</th>
                        <th class="th-center" style="width: 18%;">KODE BARANG</th>
                        <th class="th-center" style="width: 25%;">NAMA BARANG</th>
                        <th class="th-center" style="width: 20%;">SPESIFIKASI</th>
                        <th class="th-center" style="width: 15%;">KATEGORI</th>
                        <th class="th-center" style="width: 18%;">KETERANGAN</th>
                    </tr>
                </thead>
                <tbody id="preview-table-body">
                    @forelse($currentFormItems as $index => $item)
                    <tr class="data-row {{ $index % 2 !== 0 ? 'tr-even' : '' }}">
                        <td class="td-center td-no">{{ $index + 1 }}</td>
                        <td class="td-center" style="padding:0 0.4rem;">
                            @if($item->kode_barang)
                                <span class="item-code-badge">{{ $item->kode_barang }}</span>
                            @else
                                <span style="color:var(--text-muted); font-size:0.75rem;">-</span>
                            @endif
                        </td>
                        <td style="padding:0 0.6rem; font-weight:700; font-size:0.82rem; color:#0f172a;">
                            {{ $item->nama_barang }}
                        </td>
                        <td style="padding:0 0.5rem; font-size:0.78rem; color:#334155;">
                            {{ $item->spesifikasi ?? '-' }}
                        </td>
                        <td class="td-center" style="font-size:0.78rem; font-weight:600; color:#0f172a;">
                            {{ $item->kategori ?? 'CONSUMABLE' }}
                        </td>
                        <td style="padding:0 0.5rem; font-size:0.78rem; color:#475569;">
                            {{ $item->keterangan ?? '-' }}
                        </td>
                    </tr>
                    @empty
                    <tr class="empty-state-row">
                        <td colspan="6" style="padding: 0; background: transparent;">
                            <div class="empty-state-wrapper">
                                <div class="empty-state-card">
                                    <div class="empty-state-icon">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                            <polyline points="14 2 14 8 20 8"></polyline>
                                            <line x1="9" y1="13" x2="15" y2="13"></line>
                                            <line x1="9" y1="17" x2="15" y2="17"></line>
                                        </svg>
                                        <div class="empty-state-pulse"></div>
                                    </div>
                                    <div>
                                        <h4 class="empty-state-title">Belum Ada Data Barang Unregistrasi</h4>
                                        <p class="empty-state-desc">Formulir unregistrasi ini masih kosong. Klik tombol di bawah atau gunakan tombol <strong>Tambah Data Unreg</strong> untuk mengisi data discontinue.</p>
                                    </div>
                                    <div class="empty-state-actions">
                                        <button type="button" class="empty-state-btn" onclick="openModal('addItemModal')">
                                            <svg viewBox="0 0 24 24" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                <line x1="12" y1="5" x2="12" y2="19"></line>
                                                <line x1="5" y1="12" x2="19" y2="12"></line>
                                            </svg>
                                            + Tambah Data Pertama
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- ===== SIGNATURE (3 BOXES: User -> Staff -> Warehouse) ===== --}}
        <div class="form-reg-signature" style="grid-template-columns: repeat(3, 1fr);">
            <div class="sig-box">
                <div class="sig-label">Dibuat (User)</div>
                <div class="sig-space" id="preview-sig-dibuat">
                    @if($hasUserSig)
                        <div style="color: var(--color-success); font-weight: 700; font-size: 0.72rem; margin-bottom: 0.15rem;">✓ USER SUBMITTED</div>
                        <div class="sig-qrcode" id="blade-sig-qrcode-dibuat" data-val="{{ $userSigner }} (Tgl: {{ $userSigDate }})"></div>
                        <div style="font-size: 0.65rem; color: var(--text-muted); font-weight: 500;">{{ $userSigner }} (Tgl: {{ $userSigDate }})</div>
                    @else
                        <span style="color: var(--text-muted); font-size: 0.75rem; font-style: italic;">...................</span>
                    @endif
                </div>
                <div class="sig-line"></div>
            </div>
            <div class="sig-box">
                <div class="sig-label">Approved Staff / Section Head</div>
                <div class="sig-space" id="preview-sig-staff">
                    @if($hasStaffSig)
                        <div style="color: var(--color-success); font-weight: 700; font-size: 0.72rem; margin-bottom: 0.15rem;">✓ APPROVED BY STAFF</div>
                        <div class="sig-qrcode" id="blade-sig-qrcode-staff" data-val="{{ $staffSigner }} (Tgl: {{ $staffSigDate }})"></div>
                        <div style="font-size: 0.65rem; color: var(--text-muted); font-weight: 500;">{{ $staffSigner }} (Tgl: {{ $staffSigDate }})</div>
                    @else
                        <span style="color: var(--text-muted); font-size: 0.75rem; font-style: italic;">...................</span>
                    @endif
                </div>
                <div class="sig-line"></div>
            </div>
            <div class="sig-box">
                <div class="sig-label">Telah Discontinue (Warehouse)</div>
                <div class="sig-space" id="preview-sig-warehouse">
                    @if($hasWhSig)
                        <div style="color: var(--color-success); font-weight: 700; font-size: 0.72rem; margin-bottom: 0.15rem;">✓ DISCONTINUED BY WAREHOUSE</div>
                        <div class="sig-qrcode" id="blade-sig-qrcode-warehouse" data-val="{{ $whSigner }} (Tgl: {{ $whSigDate }})"></div>
                        <div style="font-size: 0.65rem; color: var(--text-muted); font-weight: 500;">{{ $whSigner }} (Tgl: {{ $whSigDate }})</div>
                    @else
                        <span style="color: var(--text-muted); font-size: 0.75rem; font-style: italic;">...................</span>
                    @endif
                </div>
                <div class="sig-line"></div>
            </div>
        </div>

    </div>

    {{-- ===== FORM COMMENTS & DISCUSSION SECTION (NO PRINT) ===== --}}
    <div class="glass-card form-comments-card no-print" id="form-comments-section" style="margin-top: 1.5rem; padding: 1.5rem 1.75rem; border-radius: var(--radius-lg);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 0.75rem; border-bottom: 1px solid rgba(0, 173, 239, 0.15); padding-bottom: 1rem;">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <div style="width: 38px; height: 38px; border-radius: 10px; background: rgba(0, 173, 239, 0.12); color: var(--color-primary); display: flex; align-items: center; justify-content: center; font-weight: 700;">
                    <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2.5" fill="none">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                    </svg>
                </div>
                <div>
                    <h3 style="font-family: var(--font-heading); font-size: 1.15rem; font-weight: 700; color: var(--text-primary); margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                        <span>Diskusi & Komentar Form Unregistrasi</span>
                        <span class="badge" id="comments-count-badge" style="background: rgba(0, 173, 239, 0.15); color: var(--color-primary); font-size: 0.75rem; padding: 0.2rem 0.55rem; border-radius: 12px;">0 Komentar</span>
                    </h3>
                    <p style="font-size: 0.8rem; color: var(--text-muted); margin: 0.15rem 0 0 0;">
                        Semua role (User, Staff, Warehouse, Master) dapat saling memberikan catatan, alasan discontinue, atau konfirmasi di sini.
                    </p>
                </div>
            </div>
            <div>
                <span class="badge" style="background: #f1f5f9; color: #475569; font-weight: 700; font-size: 0.78rem; padding: 0.35rem 0.75rem; border-radius: 8px;">
                    Form: <strong id="comments-form-no" style="color: var(--color-primary);">{{ $currentFormNo }}</strong>
                </span>
            </div>
        </div>

        {{-- Comments List Container --}}
        <div id="form-comments-list" style="display: flex; flex-direction: column; gap: 0.85rem; margin-bottom: 1.5rem; max-height: 480px; overflow-y: auto; padding-right: 0.35rem;">
            {{-- Rendered dynamically by JS renderComments() --}}
        </div>

        {{-- Comment Input Box --}}
        <form id="form-comment-input-box" onsubmit="submitFormComment(event)" style="background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 1rem 1.25rem; transition: border-color 0.2s ease;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.6rem; flex-wrap: wrap; gap: 0.5rem;">
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <div class="user-avatar-chip" style="width: 26px; height: 26px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.72rem; font-weight: 800; color: white; background: var(--mai-blue);">
                        {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}
                    </div>
                    <span style="font-size: 0.82rem; font-weight: 700; color: var(--text-dark);">
                        {{ Auth::user()->name ?? 'User' }}
                    </span>
                    <span style="font-size: 0.72rem; color: var(--text-muted);">
                        ({{ Auth::user()->role ?? 'User' }} - {{ Auth::user()->department ?? 'Production' }})
                    </span>
                </div>
                <span style="font-size: 0.72rem; color: var(--text-muted);">
                    Tekan <strong>Ctrl + Enter</strong> untuk mengirim
                </span>
            </div>

            <div style="position: relative;">
                <textarea id="comment-textarea" class="form-control" rows="3" placeholder="Tulis komentar, catatan unregistrasi, atau pertanyaan terkait formulir ini..." style="resize: vertical; min-height: 75px; font-size: 0.875rem; line-height: 1.5; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0.65rem 0.85rem;" required onkeydown="handleCommentKeydown(event)"></textarea>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 0.75rem; flex-wrap: wrap; gap: 0.5rem;">
                <div style="display: flex; align-items: center; gap: 0.4rem; font-size: 0.75rem; color: var(--text-muted);">
                    <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                    Komentar tersimpan permanen & dapat dilihat oleh seluruh pengguna.
                </div>
                <button type="submit" id="btn-submit-comment" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 0.45rem; font-weight: 700; padding: 0.55rem 1.25rem; border-radius: 8px; font-size: 0.85rem;">
                    <svg viewBox="0 0 24 24" width="15" height="15" stroke="currentColor" stroke-width="2.5" fill="none">
                        <line x1="22" y1="2" x2="11" y2="13"></line>
                        <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                    </svg>
                    <span>Kirim Komentar</span>
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ===== TAB PANE: PROSES APPROVAL ===== --}}
<div id="proses-approval-pane" class="tab-pane no-print">
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
            <div style="background-color: var(--color-success-light); color: var(--color-success); padding: 0.75rem; border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center;">
                <svg viewBox="0 0 24 24" width="22" height="22" stroke="currentColor" stroke-width="2.5" fill="none">
                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                </svg>
            </div>
            <div>
                <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">Telah Discontinue (Warehouse)</span>
                <span style="font-size: 1.4rem; font-weight: 800; color: var(--text-primary);" id="approval-stat-warehouse">0</span>
            </div>
        </div>
    </div>

    @php
        $curRole = strtolower(Auth::user()->role ?? 'user');
        $roleName = Auth::user()->role ?? 'User';
        $roleDesc = '';
        $roleColor = '#2563eb';
        if (in_array($curRole, ['master', 'admin'])) {
            $roleDesc = 'Anda login sebagai <strong>Administrator / Master</strong> (Wewenang penuh untuk verifikasi semua tahap).';
            $roleColor = '#0f172a';
        } elseif (str_contains($curRole, 'staff')) {
            $staffDept = Auth::user()->department ?? 'Production';
            if (str_contains(strtoupper($staffDept), 'PRODUCTION') && str_contains(strtoupper($staffDept), 'DIES ASSY')) {
                $roleDesc = 'Anda login sebagai <strong>Staff / Section Head Departemen Production / Dies Assy (Tahap 1)</strong>. Berwenang menyetujui unregistrasi dari departemen <strong>Production</strong> dan <strong>Dies Assy</strong>.';
            } else {
                $roleDesc = 'Anda login sebagai <strong>Staff / Section Head Departemen ' . e($staffDept) . ' (Tahap 1)</strong>. Hanya berwenang menyetujui unregistrasi dari departemen <strong>' . e($staffDept) . '</strong> Anda.';
            }
            $roleColor = '#2563eb';
        } elseif (str_contains($curRole, 'warehouse')) {
            $roleDesc = 'Anda login sebagai <strong>Warehouse Consumable (Tahap 2)</strong>. Bertugas memverifikasi discontinue barang consumable.';
            $roleColor = '#059669';
        } else {
            $roleDesc = 'Anda login sebagai <strong>User (Pembuat Form)</strong>. Bertugas membuat FORM Unregistrasi & mengajukannya ke Staff / Section Head. <em>(Akun User tidak memiliki wewenang approval)</em>.';
            $roleColor = '#d97706';
        }
    @endphp

    {{-- User Role Access Information Banner --}}
    <div style="background: #ffffff; border-left: 4px solid {{ $roleColor }}; border-radius: var(--radius-md); padding: 0.85rem 1.25rem; margin-top: 1rem; box-shadow: var(--shadow-sm); display: flex; justify-content: space-between; align-items: center; flex-wrap: gap; gap: 0.75rem;">
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
                    Daftar Form & Tahap Approval
                </h3>
                <p style="color: var(--text-muted); font-size: 0.82rem; margin-top: 0.2rem; margin-bottom: 0;">
                    Alur persetujuan: <strong>User (Pembuat)</strong> ➔ <strong>Staff / Section Head</strong> ➔ <strong>Warehouse Consumable (Discontinue)</strong>.
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
                <button class="btn btn-sm btn-secondary filter-pill-btn" onclick="filterApprovalStage('warehouse', this)" style="border-radius: 20px; font-size: 0.78rem; font-weight: 600; padding: 0.35rem 0.85rem;">
                    Butuh Warehouse
                </button>
            </div>
        </div>

        {{-- Clean & Clear Approval Monitoring Table --}}
        <div class="form-reg-table-wrap">
            <table class="form-reg-table">
                <thead>
                    <tr>
                        <th class="th-center" style="width: 40px;">NO</th>
                        <th>NO. CHECKSHEET</th>
                        <th>PEMBUAT (REQUESTOR)</th>
                        <th>TANGGAL</th>
                        <th class="th-center">PROGRESS TAHAP (3-STEP)</th>
                        <th class="th-center">STATUS</th>
                        <th class="th-center" style="width: 175px;">AKSI APPROVAL</th>
                    </tr>
                </thead>
                <tbody id="approval-monitoring-tbody">
                    {{-- Populated by JS renderApprovalMonitoringTable() --}}
                </tbody>
            </table>
        </div>
    </div>

</div>

{{-- ===== TAB PANE: DATA VIEW ===== --}}
<div id="data-view-pane" class="tab-pane no-print">
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
                <span style="font-size: 0.8rem; color: var(--text-muted); font-weight: 500; display: block; text-transform: uppercase; letter-spacing: 0.05em;">Selesai (Discontinue)</span>
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
            <h3 style="font-family: var(--font-heading); font-weight: 700; color: var(--text-primary); margin: 0;">Daftar Form Unregistrasi (Print Preview)</h3>
            
            <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
                <span style="font-size: 0.85rem; font-weight: 700; color: var(--text-muted); display: flex; align-items: center; gap: 0.35rem;">
                    <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                    Filter Bulan & Tahun:
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
                        <th class="th-center" style="width: 170px;">AKSI</th>
                        @if(in_array(strtoupper(Auth::user()->role ?? ''), ['MASTER', 'ADMIN']))
                        <th class="th-center" style="width: 140px;">HAPUS</th>
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

@if(in_array(strtoupper(Auth::user()->role ?? ''), ['MASTER', 'ADMIN']))
{{-- ===== TAB PANE: ACCOUNT MASTER ===== --}}
<div id="account-master-pane" class="tab-pane no-print">
    <div style="display: flex; flex-direction: column; gap: 1.5rem; max-width: 1200px; margin: 0 auto;">
        
        {{-- Header Card & Actions --}}
        <div class="glass-card" style="padding: 1.75rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h3 style="font-family: var(--font-heading); font-weight: 700; color: var(--text-primary); margin-bottom: 0.35rem; display: flex; align-items: center; gap: 0.5rem;">
                    <svg viewBox="0 0 24 24" width="22" height="22" stroke="var(--color-primary)" stroke-width="2.5" fill="none"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                    Account Master Management
                </h3>
                <p style="color: var(--text-muted); font-size: 0.875rem;">Kelola data pengguna, hak akses role (Master, User, Staff, Accounting, Warehouse Consumable), dan department.</p>
            </div>
            <button class="btn btn-primary" onclick="openModal('addAccountModal')" style="display: inline-flex; align-items: center; gap: 0.5rem; font-weight: 600; padding: 0.65rem 1.25rem; border-radius: var(--radius-md);">
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
                <div style="background-color: var(--color-warning-light); color: var(--color-warning); padding: 0.75rem; border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center;">
                    <svg viewBox="0 0 24 24" width="22" height="22" stroke="currentColor" stroke-width="2.5" fill="none"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"></path></svg>
                </div>
                <div>
                    <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 600; display: block; text-transform: uppercase;">Akun STAFF</span>
                    <span style="font-size: 1.4rem; font-weight: 800; color: var(--color-warning);" id="stat-staff-accounts-count">{{ isset($users) ? $users->filter(fn($u) => strtolower($u->role) === 'staff')->count() : 0 }} Akun</span>
                </div>
            </div>

            <div class="dataview-stat-card">
                <div style="background-color: var(--color-success-light); color: var(--color-success); padding: 0.75rem; border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center;">
                    <svg viewBox="0 0 24 24" width="22" height="22" stroke="currentColor" stroke-width="2.5" fill="none"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path></svg>
                </div>
                <div>
                    <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 600; display: block; text-transform: uppercase;">WAREHOUSE</span>
                    <span style="font-size: 1.4rem; font-weight: 800; color: var(--color-success);" id="stat-acc-wh-accounts-count">{{ isset($users) ? $users->filter(fn($u) => in_array(strtolower($u->role), ['warehouse consumable', 'warehouse']))->count() : 0 }} Akun</span>
                </div>
            </div>
        </div>

        {{-- Accounts Table --}}
        <div class="glass-card" style="padding: 1.5rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
                <h4 style="font-family: var(--font-heading); font-weight: 700; color: var(--text-primary);">Daftar Pengguna Sistem (Account List)</h4>
            </div>
            
            <div class="form-reg-table-wrap">
                <table class="form-reg-table" id="table-accounts">
                    <thead>
                        <tr>
                            <th class="th-center" style="width: 50px;">NO</th>
                            <th>USER NAME / LOGIN ID</th>
                            <th>DEPARTMENT</th>
                            <th class="th-center">ROLE HAK AKSES</th>
                            <th class="th-center">STATUS AKUN</th>
                            <th class="th-center">TANGGAL DIBUAT</th>
                            <th class="th-center" style="width: 140px;">AKSI</th>
                        </tr>
                    </thead>
                    <tbody id="account-table-body">
                        @forelse($users ?? [] as $index => $u)
                        <tr>
                            <td class="td-center td-no">{{ $index + 1 }}</td>
                            <td style="font-weight: 700; color: var(--text-primary);">{{ $u->name }} <br><span style="font-size: 0.75rem; color: var(--text-muted); font-weight: normal;">{{ $u->email }}</span></td>
                            <td style="font-weight: 600;">{{ $u->department ?? 'Production' }}</td>
                            <td class="td-center">
                                @php
                                    $r = strtoupper($u->role ?? 'USER');
                                    $bg = 'var(--color-primary-light)';
                                    $color = 'var(--color-primary)';
                                    if(str_contains($r, 'MASTER') || str_contains($r, 'ADMIN')) { $bg = 'rgba(15, 23, 42, 0.1)'; $color = '#0f172a'; }
                                    elseif(str_contains($r, 'STAFF')) { $bg = 'rgba(245, 158, 11, 0.15)'; $color = '#d97706'; }
                                    elseif(str_contains($r, 'WAREHOUSE')) { $bg = 'rgba(16, 185, 129, 0.15)'; $color = '#059669'; }
                                @endphp
                                <span style="background-color: {{ $bg }}; color: {{ $color }}; padding: 0.25rem 0.6rem; border-radius: 4px; font-weight: 700; font-size: 0.75rem;">{{ $u->role ?? 'User' }}</span>
                            </td>
                            <td class="td-center"><span class="badge badge-success">Aktif</span></td>
                            <td class="td-center" style="font-size: 0.8rem;">{{ $u->created_at ? $u->created_at->format('d-m-Y') : '-' }}</td>
                            <td class="td-center">
                                <div style="display: flex; gap: 0.35rem; justify-content: center;">
                                    <button class="btn btn-secondary btn-sm" onclick="editAccount({{ $u->id }}, '{{ addslashes($u->name) }}', '{{ addslashes($u->username ?? '') }}', '{{ addslashes($u->department ?? 'Production') }}', '{{ addslashes($u->role ?? 'User') }}')" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">Edit</button>
                                    @if(Auth::id() !== $u->id)
                                    <form action="{{ route('users.delete', $u->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus akun {{ addslashes($u->name) }}?');" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline" style="padding: 0.25rem 0.5rem; font-size: 0.75rem; color: var(--color-danger); border-color: rgba(239,68,68,0.3);">Hapus</button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="td-center" style="padding: 1.5rem; color: var(--text-muted);">Belum ada data user.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

{{-- ===== MODAL: BUAT AKUN BARU ===== --}}
<div class="modal no-print" id="addAccountModal">
    <div class="modal-content" style="max-width: 520px;">
        <div class="modal-header">
            <h3>Buat Akun Pengguna Baru</h3>
            <button class="btn-close" onclick="closeModal('addAccountModal')">&times;</button>
        </div>

        <form id="form-add-account" action="{{ route('users.store') }}" method="POST">
            @csrf
            
            <div class="form-group" style="margin-bottom: 1rem;">
                <label for="acc_add_name" style="font-weight: 600; font-size: 0.85rem; margin-bottom: 0.35rem; display: block;">User Name <span style="color: var(--color-danger);">*</span></label>
                <input type="text" id="acc_add_name" name="name" class="form-control" placeholder="Cth: Budi Santoso" required style="height: 42px;">
            </div>

            <div class="form-group" style="margin-bottom: 1rem;">
                <label for="acc_add_username" style="font-weight: 600; font-size: 0.85rem; margin-bottom: 0.35rem; display: block;">Username / Login ID</label>
                <input type="text" id="acc_add_username" name="username" class="form-control" placeholder="Cth: budi_santoso (opsional)" style="height: 42px;">
            </div>

            <div class="form-group" style="margin-bottom: 1rem;">
                <label for="acc_add_dept" style="font-weight: 600; font-size: 0.85rem; margin-bottom: 0.35rem; display: block;">Department <span style="color: var(--color-danger);">*</span></label>
                <select id="acc_add_dept" name="department" class="form-control" style="height: 42px;" required>
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
                <label for="acc_add_role" style="font-weight: 600; font-size: 0.85rem; margin-bottom: 0.35rem; display: block;">Role Hak Akses <span style="color: var(--color-danger);">*</span></label>
                <select id="acc_add_role" name="role" class="form-control" style="height: 42px;" required>
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
                <label for="acc_add_password" style="font-weight: 600; font-size: 0.85rem; margin-bottom: 0.35rem; display: block;">Password <span style="color: var(--color-danger);">*</span></label>
                <input type="password" id="acc_add_password" name="password" class="form-control" placeholder="Masukkan password akun..." required style="height: 42px;">
            </div>

            <div style="display: flex; gap: 0.75rem; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addAccountModal')">Batal</button>
                <button type="submit" class="btn btn-primary" style="display: flex; align-items: center; gap: 0.4rem;">
                    <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2.5" fill="none"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    Simpan Akun
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ===== MODAL: EDIT AKUN ===== --}}
<div class="modal no-print" id="editAccountModal">
    <div class="modal-content" style="max-width: 520px;">
        <div class="modal-header">
            <h3>Edit Akun Pengguna</h3>
            <button class="btn-close" onclick="closeModal('editAccountModal')">&times;</button>
        </div>

        <form id="form-edit-account" action="" method="POST">
            @csrf
            @method('PUT')
            
            <div class="form-group" style="margin-bottom: 1rem;">
                <label for="acc_edit_name" style="font-weight: 600; font-size: 0.85rem; margin-bottom: 0.35rem; display: block;">User Name <span style="color: var(--color-danger);">*</span></label>
                <input type="text" id="acc_edit_name" name="name" class="form-control" required style="height: 42px;">
            </div>

            <div class="form-group" style="margin-bottom: 1rem;">
                <label for="acc_edit_username" style="font-weight: 600; font-size: 0.85rem; margin-bottom: 0.35rem; display: block;">Username / Login ID</label>
                <input type="text" id="acc_edit_username" name="username" class="form-control" style="height: 42px;">
            </div>

            <div class="form-group" style="margin-bottom: 1rem;">
                <label for="acc_edit_dept" style="font-weight: 600; font-size: 0.85rem; margin-bottom: 0.35rem; display: block;">Department <span style="color: var(--color-danger);">*</span></label>
                <select id="acc_edit_dept" name="department" class="form-control" style="height: 42px;" required>
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
                <label for="acc_edit_role" style="font-weight: 600; font-size: 0.85rem; margin-bottom: 0.35rem; display: block;">Role Hak Akses <span style="color: var(--color-danger);">*</span></label>
                <select id="acc_edit_role" name="role" class="form-control" style="height: 42px;" required>
                    <option value="MASTER">Master (Akses Penuh & Account Master)</option>
                    <option value="User">User</option>
                    <option value="Staff">Staff</option>
                    <option value="Staff (Production / Dies Assy)">Staff (Production / Dies Assy)</option>
                    <option value="Accounting">Accounting</option>
                    <option value="Warehouse Consumable">Warehouse Consumable</option>
                </select>
            </div>

            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label for="acc_edit_password" style="font-weight: 600; font-size: 0.85rem; margin-bottom: 0.35rem; display: block;">Password Baru <span style="color: var(--text-muted); font-size: 0.75rem;">(Opsional)</span></label>
                <input type="password" id="acc_edit_password" name="password" class="form-control" placeholder="Kosongkan jika tidak ingin mengubah password" style="height: 42px;">
            </div>

            <div style="display: flex; gap: 0.75rem; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editAccountModal')">Batal</button>
                <button type="submit" class="btn btn-primary" style="display: flex; align-items: center; gap: 0.4rem;">
                    <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2.5" fill="none"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>
@endif

{{-- ===== MODAL: QUICK APPROVAL ===== --}}
<div class="modal" id="quickApprovalModal">
    <div class="modal-content" style="max-width: 540px; padding: 1.75rem; border-radius: var(--radius-lg);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; border-bottom: 1px solid rgba(0,0,0,0.08); padding-bottom: 0.75rem;">
            <h3 style="font-family: var(--font-heading); font-weight: 700; margin: 0; color: var(--text-primary); font-size: 1.15rem; display: flex; align-items: center; gap: 0.5rem;">
                <svg viewBox="0 0 24 24" width="20" height="20" stroke="var(--color-primary)" stroke-width="2.5" fill="none"><polyline points="20 6 9 17 4 12"></polyline></svg>
                Persetujuan Form Unregistrasi
            </h3>
            <button class="btn-close" onclick="closeModal('quickApprovalModal')">&times;</button>
        </div>

        <div id="quick-approval-modal-body">
            {{-- Rendered dynamically via openQuickApprovalModal() --}}
        </div>
    </div>
</div>

{{-- ===== MODAL: TAMBAH DATA UNREGISTRASI ===== --}}
<div class="modal" id="addItemModal">
    <div class="modal-content" style="max-width: 620px; max-height: 90vh; overflow-y: auto; padding: 2.25rem 2.5rem 2rem; border-radius: 24px; border: none; box-shadow: 0 25px 60px rgba(0, 0, 0, 0.35); background: #ffffff;">
        <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 1.15rem; margin-bottom: 1.35rem; border-bottom: 1.5px solid #f1f5f9;">
            <h3 style="font-family: inherit; font-size: 1.3rem; font-weight: 800; color: #0f172a; margin: 0; letter-spacing: -0.01em;">Tambah Data Barang Unregistrasi</h3>
            <button class="btn-close" onclick="closeModal('addItemModal')" style="background: transparent; border: none; font-size: 1.35rem; line-height: 1; color: #94a3b8; cursor: pointer; padding: 0.25rem; border-radius: 6px; transition: all 0.2s;">&times;</button>
        </div>

        <form action="{{ route('form-unregistrasi.store') }}" method="POST">
            @csrf
            <input type="hidden" name="form_number" id="modal_form_number" value="{{ $currentFormNo }}">

            <div class="form-group" style="margin-bottom: 1.15rem;">
                <label for="fi_kode" style="display: block; font-size: 0.78rem; font-weight: 800; color: #1e293b; margin-bottom: 0.45rem;">Kode Barang <span style="color: #ef4444; margin-left: 2px;">*</span></label>
                <input type="text" id="fi_kode" name="kode_barang" class="form-control @error('kode_barang') is-invalid @enderror" placeholder="Cth: SBM-001" value="{{ old('kode_barang') }}" required oninput="checkUnregistrasiKodeBarang(this.value)" onblur="checkUnregistrasiKodeBarang(this.value, true)" style="height: 46px; border-radius: 12px; border: 1.5px solid #cbd5e1; padding: 0 1rem; font-size: 0.88rem; background: #ffffff; color: #0f172a; width: 100%;">
                <div id="fi_kode_alert_box" style="display: none; margin-top: 0.35rem; font-size: 0.78rem; font-weight: 600; padding: 0.4rem 0.65rem; border-radius: 6px;"></div>
                @error('kode_barang')<div class="error-text" style="color: #ef4444; font-size: 0.75rem; margin-top: 0.25rem;">{{ $message }}</div>@enderror
            </div>

            <div class="form-group" style="margin-bottom: 1.15rem;">
                <label for="fi_nama" style="display: block; font-size: 0.78rem; font-weight: 800; color: #1e293b; margin-bottom: 0.45rem;">Nama Barang <span style="color: #ef4444; margin-left: 2px;">*</span></label>
                <input type="text" id="fi_nama" name="nama_barang" class="form-control @error('nama_barang') is-invalid @enderror" placeholder="Nama barang yang akan di-discontinue" value="{{ old('nama_barang') }}" required style="height: 46px; border-radius: 12px; border: 1.5px solid #cbd5e1; padding: 0 1rem; font-size: 0.88rem; background: #ffffff; color: #0f172a; width: 100%;">
                @error('nama_barang')<div class="error-text" style="color: #ef4444; font-size: 0.75rem; margin-top: 0.25rem;">{{ $message }}</div>@enderror
            </div>

            <div class="form-group" style="margin-bottom: 1.15rem;">
                <label for="fi_spesifikasi" style="display: block; font-size: 0.78rem; font-weight: 800; color: #1e293b; margin-bottom: 0.45rem;">Spesifikasi <span style="color: #ef4444; margin-left: 2px;">*</span></label>
                <input type="text" id="fi_spesifikasi" name="spesifikasi" class="form-control @error('spesifikasi') is-invalid @enderror" placeholder="Cth: Reguler / Tipe A / Ukuran 40x40" value="{{ old('spesifikasi') }}" required style="height: 46px; border-radius: 12px; border: 1.5px solid #cbd5e1; padding: 0 1rem; font-size: 0.88rem; background: #ffffff; color: #0f172a; width: 100%;">
                @error('spesifikasi')<div class="error-text" style="color: #ef4444; font-size: 0.75rem; margin-top: 0.25rem;">{{ $message }}</div>@enderror
            </div>

            <div class="form-group" style="margin-bottom: 1.15rem;">
                <label for="fi_kategori" style="display: block; font-size: 0.78rem; font-weight: 800; color: #1e293b; margin-bottom: 0.45rem;">Kategori <span style="color: #ef4444; margin-left: 2px;">*</span></label>
                <select id="fi_kategori" name="kategori" class="form-control @error('kategori') is-invalid @enderror" required style="height: 46px; border-radius: 12px; border: 1.5px solid #cbd5e1; padding: 0 1rem; font-size: 0.88rem; background: #ffffff; color: #0f172a; width: 100%;">
                    <option value="" disabled {{ old('kategori') ? '' : 'selected' }}>-- Pilih Kategori --</option>
                    <option value="CONSUMABLE" {{ old('kategori') === 'CONSUMABLE' ? 'selected' : '' }}>CONSUMABLE</option>
                    <option value="SPAREPART" {{ old('kategori') === 'SPAREPART' ? 'selected' : '' }}>SPAREPART</option>
                    <option value="SAFETY" {{ old('kategori') === 'SAFETY' ? 'selected' : '' }}>SAFETY / APD</option>
                    <option value="UMUM" {{ old('kategori') === 'UMUM' ? 'selected' : '' }}>UMUM</option>
                </select>
                @error('kategori')<div class="error-text" style="color: #ef4444; font-size: 0.75rem; margin-top: 0.25rem;">{{ $message }}</div>@enderror
            </div>

            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label for="fi_keterangan" style="display: block; font-size: 0.78rem; font-weight: 800; color: #1e293b; margin-bottom: 0.45rem;">Keterangan / Alasan Discontinue <span style="color: #ef4444; margin-left: 2px;">*</span></label>
                <textarea id="fi_keterangan" name="keterangan" class="form-control @error('keterangan') is-invalid @enderror" rows="2" placeholder="Cth: Digantikan tipe baru / Rusak / Tidak digunakan lagi" required style="border-radius: 12px; border: 1.5px solid #cbd5e1; padding: 0.75rem 1rem; font-size: 0.88rem; background: #ffffff; color: #0f172a; width: 100%; resize: vertical;">{{ old('keterangan') }}</textarea>
                @error('keterangan')<div class="error-text" style="color: #ef4444; font-size: 0.75rem; margin-top: 0.25rem;">{{ $message }}</div>@enderror
            </div>

            <div style="display: flex; gap: 0.85rem; justify-content: flex-end; align-items: center; margin-top: 1.75rem;">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addItemModal')" style="background: #ffffff; border: 1.5px solid #cbd5e1; border-radius: 12px; padding: 0.65rem 1.6rem; font-weight: 700; font-size: 0.88rem; color: #1e293b; cursor: pointer; transition: all 0.2s ease;">Batal</button>
                <button type="submit" class="btn btn-primary" style="background: #0084ff; border: none; border-radius: 12px; padding: 0.65rem 1.6rem; font-weight: 700; font-size: 0.88rem; color: #ffffff; cursor: pointer; display: inline-flex; align-items: center; gap: 0.5rem; box-shadow: 0 4px 16px rgba(0, 132, 255, 0.4); transition: all 0.2s ease;">
                    <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                        <polyline points="17 21 17 13 7 13 7 21"></polyline>
                        <polyline points="7 3 7 8 15 8"></polyline>
                    </svg>
                    <span>Simpan Data Unreg</span>
                </button>
            </div>
        </form>
    </div>
</div>

</div> {{-- End .workspace-light-theme --}}

@endsection

@section('scripts')
<script>
    function openModal(id) {
        if (id === 'addItemModal') {
            const hiddenFormNo = document.getElementById('modal_form_number');
            if (hiddenFormNo) {
                hiddenFormNo.value = selectedChecksheetId;
            }
        }
        document.getElementById(id).classList.add('show');
    }
    function closeModal(id) {
        document.getElementById(id).classList.remove('show');
    }
    window.onclick = function(e) {
        if (e.target.classList.contains('modal')) {
            e.target.classList.remove('show');
        }
    }

    function editAccount(id, name, username, department, role) {
        const form = document.getElementById('form-edit-account');
        if (form) {
            form.action = `/users/${id}`;
            document.getElementById('acc_edit_name').value = name;
            document.getElementById('acc_edit_username').value = username;
            document.getElementById('acc_edit_dept').value = department;
            document.getElementById('acc_edit_role').value = role;
            document.getElementById('acc_edit_password').value = '';
            openModal('editAccountModal');
        }
    }

    // TAB SYSTEM & MULTI-FORM ENGINE
    const serverFormItems = @json($formItems, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    const serverFormApprovals = @json($formApprovals ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    const serverFormComments = @json($formComments ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    const allRegisteredCodes = @json($allRegisteredCodes ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    const allUnregisteredCodes = @json($allUnregisteredCodes ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    function checkUnregistrasiKodeBarang(val, showAlert = false) {
        const code = (val || '').trim().toUpperCase();
        const alertBox = document.getElementById('fi_kode_alert_box');
        if (!alertBox) return;

        if (!code) {
            alertBox.style.display = 'none';
            alertBox.innerHTML = '';
            return;
        }

        // Check if already in Unregistrasi
        const unregMatch = allUnregisteredCodes.find(i => (i.kode_barang || '').trim().toUpperCase() === code);
        if (unregMatch) {
            alertBox.style.display = 'block';
            alertBox.style.background = 'rgba(239, 68, 68, 0.12)';
            alertBox.style.border = '1px solid rgba(239, 68, 68, 0.3)';
            alertBox.style.color = '#b91c1c';
            alertBox.innerHTML = `🚫 <strong>PERINGATAN:</strong> Item dengan Kode Barang <strong>${escapeHtml(code)}</strong> (${escapeHtml(unregMatch.nama_barang || '')}) telah di-discontinue sebelumnya pada <strong>Form Unregistrasi ${escapeHtml(unregMatch.form_number || '')}</strong>!`;
            if (showAlert) {
                alert(`Peringatan: Item dengan Kode Barang "${code}" (${unregMatch.nama_barang || ''}) telah di-discontinue sebelumnya pada Form Unregistrasi ${unregMatch.form_number || ''}!`);
            }
            return;
        }

        // Check if registered in FormItem (Informative match & auto-fill)
        const regMatch = allRegisteredCodes.find(i => (i.kode_barang || '').trim().toUpperCase() === code);
        if (regMatch) {
            alertBox.style.display = 'block';
            alertBox.style.background = 'rgba(16, 185, 129, 0.1)';
            alertBox.style.border = '1px solid rgba(16, 185, 129, 0.3)';
            alertBox.style.color = '#047857';
            alertBox.innerHTML = `✓ <strong>Ditemukan pada Form Registrasi ${escapeHtml(regMatch.form_number || '')}</strong>: ${escapeHtml(regMatch.nama_barang || '')}`;
            
            // Auto-fill fields if empty
            const nameInput = document.getElementById('fi_nama');
            const specInput = document.getElementById('fi_spesifikasi');
            if (nameInput && !nameInput.value.trim() && regMatch.nama_barang) {
                nameInput.value = regMatch.nama_barang;
            }
            if (specInput && !specInput.value.trim() && regMatch.spesifikasi) {
                specInput.value = regMatch.spesifikasi;
            }
            return;
        }

        alertBox.style.display = 'none';
        alertBox.innerHTML = '';
    }

    const urlFormParam = '{{ $activeFormNoParam ?? "" }}';
    const userTag = '{{ strtoupper(Auth::user()->department ?? Auth::user()->name ?? "PRODUCTION") }}';
    const monthYearStr = '{{ date("m-Y") }}';
    const defaultDeptTag = (userTag.includes('PRODUCTION') && userTag.includes('DIES ASSY')) ? 'PRODUCTION' : userTag;
    const defaultFormNo = `01/${defaultDeptTag}/${monthYearStr}`;

    const authRoleRaw = '{{ strtolower(trim(Auth::user()->role ?? "User")) }}';
    const authUserName = '{{ Auth::user()->name ?? "User" }}';
    const authUserDept = '{{ Auth::user()->department ?? "Production" }}';
    let userRoleType = 'user';
    if (authRoleRaw.includes('master') || authRoleRaw.includes('admin')) {
        userRoleType = 'admin';
    } else if (authRoleRaw.includes('warehouse')) {
        userRoleType = 'warehouse';
    } else if (authRoleRaw.includes('staff')) {
        userRoleType = 'staff';
    } else {
        userRoleType = 'user';
    }

    const canViewAllDepartments = (userRoleType === 'admin' || userRoleType === 'warehouse');
    
    function getUserAllowedDepartments() {
        const userDept = (authUserDept || '').trim().toUpperCase();
        const userRole = (authRoleRaw || '').trim().toUpperCase();
        if (
            (userDept.includes('PRODUCTION') && userDept.includes('DIES ASSY'))
            || (userRole.includes('PRODUCTION') && userRole.includes('DIES ASSY'))
            || userDept === 'PRODUCTION / DIES ASSY'
            || userDept === 'PRODUCTION/DIES ASSY'
        ) {
            return ['PRODUCTION', 'DIES ASSY', 'DIESASSY', 'DIES-ASSY', 'PRODUCTION / DIES ASSY', 'PRODUCTION/DIES ASSY'];
        }
        if (userDept.includes('/')) {
            return userDept.split('/').map(d => d.trim().toUpperCase()).filter(Boolean);
        }
        return [userDept || 'PRODUCTION'];
    }

    function isDeptAllowed(dept) {
        if (canViewAllDepartments) return true;
        if (!dept) return false;
        const dUpper = dept.trim().toUpperCase();
        const allowed = getUserAllowedDepartments();
        return allowed.includes(dUpper) || allowed.some(a => dUpper.includes(a) || a.includes(dUpper));
    }

    function getCsDepartment(cs, fNo) {
        if (cs && cs.requestorDept) return cs.requestorDept.trim().toUpperCase();
        if (fNo && fNo.includes('/')) {
            const parts = fNo.split('/');
            if (parts.length >= 2) return parts[1].trim().toUpperCase();
        }
        return '';
    }

    // Distinct existing form numbers strictly from server items
    const existingForms = [...new Set(serverFormItems.map(i => i.form_number).filter(Boolean))];

    const deptForms = existingForms.filter(fNo => {
        const parts = fNo.split('/');
        const fDept = parts.length >= 2 ? parts[1].trim().toUpperCase() : '';
        return isDeptAllowed(fDept);
    });
    
    const activeFormNo = (!canViewAllDepartments)
        ? (deptForms[0] || defaultFormNo)
        : ((urlFormParam && (existingForms.includes(urlFormParam) || serverFormItems.length === 0))
            ? urlFormParam
            : (existingForms[0] || defaultFormNo));

    let activeChecksheetHtml = '';
    let selectedChecksheetId = activeFormNo;

    const emptyTableHtml = `
        <tr class="empty-state-row">
            <td colspan="6" style="padding: 0; background: transparent;">
                <div class="empty-state-wrapper">
                    <div class="empty-state-card">
                        <div class="empty-state-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                                <line x1="9" y1="13" x2="15" y2="13"></line>
                                <line x1="9" y1="17" x2="15" y2="17"></line>
                            </svg>
                            <div class="empty-state-pulse"></div>
                        </div>
                        <div>
                            <h4 class="empty-state-title">Belum Ada Data Barang Unregistrasi</h4>
                            <p class="empty-state-desc">Formulir unregistrasi ini masih kosong. Klik tombol di bawah atau gunakan tombol <strong>Tambah Data Unreg</strong> untuk mengisi data discontinue.</p>
                        </div>
                        <div class="empty-state-actions">
                            <button type="button" class="empty-state-btn" onclick="openModal('addItemModal')">
                                <svg viewBox="0 0 24 24" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="12" y1="5" x2="12" y2="19"></line>
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                </svg>
                                + Tambah Data Pertama
                            </button>
                        </div>
                    </div>
                </div>
            </td>
        </tr>
    `;

    function formatApprovalDateStr(val) {
        if (!val) return '';
        try {
            const d = new Date(val);
            if (isNaN(d.getTime())) return val;
            const dd = String(d.getDate()).padStart(2, '0');
            const mm = String(d.getMonth() + 1).padStart(2, '0');
            const yy = d.getFullYear();
            return `${dd}-${mm}-${yy}`;
        } catch (e) {
            return val;
        }
    }

    function formatCommentDate(val) {
        if (!val) return '';
        try {
            const d = new Date(val);
            if (isNaN(d.getTime())) return val;
            const dd = String(d.getDate()).padStart(2, '0');
            const mm = String(d.getMonth() + 1).padStart(2, '0');
            const yyyy = d.getFullYear();
            const hh = String(d.getHours()).padStart(2, '0');
            const min = String(d.getMinutes()).padStart(2, '0');
            return `${dd}-${mm}-${yyyy} ${hh}:${min}`;
        } catch (e) {
            return val;
        }
    }

    const checksheets = {};
    const stepperData = {};
    const formCommentsMap = {};

    // Collect all active form numbers
    const allKnownFormNumbers = [...new Set([
        ...existingForms,
        ...(existingForms.length === 0 ? [defaultFormNo] : [])
    ])];

    allKnownFormNumbers.forEach(fNo => {
        const parts = fNo.split('/');
        const csDept = parts.length >= 2 ? parts[1] : userTag;
        
        const dbAppr = serverFormApprovals.find(a => a.form_number === fNo) || null;
        const firstItem = serverFormItems.find(i => i.form_number === fNo) || null;
        
        const reqName = (dbAppr && dbAppr.requestor_name) 
            || (firstItem && firstItem.created_by_name) 
            || '{{ Auth::user()->name ?? "User" }}';
            
        const reqDept = (dbAppr && dbAppr.requestor_dept) 
            || (firstItem && firstItem.created_by_dept) 
            || csDept;
            
        const formDateStr = (dbAppr && dbAppr.form_date) 
            || (firstItem && firstItem.created_at ? formatApprovalDateStr(firstItem.created_at) : '{{ date("d-m-Y") }}');

        const requestorStr = `${reqName} / ${reqDept}`;

        const staffDone = !!(dbAppr && (dbAppr.staff_signed_at || dbAppr.staff_signer_name));
        const whDone = !!(dbAppr && (dbAppr.warehouse_signed_at || dbAppr.warehouse_signer_name));

        let statusText = 'Butuh Approval Staff / Section Head';
        let statusClass = 'badge-warning';

        if (whDone || (dbAppr && (dbAppr.status === 'Telah Discontinue oleh Warehouse Consumable' || dbAppr.status === 'Telah Discontinue' || dbAppr.status === 'Selesai'))) {
            statusText = 'Telah Discontinue';
            statusClass = 'badge-success';
        } else if (staffDone || (dbAppr && (dbAppr.status === 'Butuh Approval Warehouse Consumable' || dbAppr.status === 'Pending Warehouse'))) {
            statusText = 'Butuh Verifikasi Warehouse Consumable';
            statusClass = 'badge-primary';
        } else {
            statusText = 'Butuh Approval Staff / Section Head';
            statusClass = 'badge-warning';
        }

        const userSigDate = (dbAppr && dbAppr.user_signed_at) ? formatApprovalDateStr(dbAppr.user_signed_at) : formDateStr;
        const staffSigDate = (dbAppr && dbAppr.staff_signed_at) ? formatApprovalDateStr(dbAppr.staff_signed_at) : formDateStr;
        const whSigDate = (dbAppr && dbAppr.warehouse_signed_at) ? formatApprovalDateStr(dbAppr.warehouse_signed_at) : formDateStr;

        const staffSigner = (dbAppr && dbAppr.staff_signer_name) ? dbAppr.staff_signer_name : 'Staff / Section Head';
        const whSigner = (dbAppr && dbAppr.warehouse_signer_name) ? dbAppr.warehouse_signer_name : 'Warehouse Consumable';

        checksheets[fNo] = {
            formNo: fNo,
            date: formDateStr,
            requestor: requestorStr,
            requestorName: reqName,
            requestorDept: reqDept,
            status: statusText,
            items: [],
            signatures: {
                dibuat: `${reqName} (Tgl: ${userSigDate})`,
                staff: staffDone ? `${staffSigner} (Tgl: ${staffSigDate})` : '...................',
                warehouse: whDone ? `${whSigner} (Tgl: ${whSigDate})` : '...................'
            },
            comments: {
                user: (dbAppr && dbAppr.user_comment) || 'Formulir pengajuan discontinue diajukan.',
                staff: (dbAppr && dbAppr.staff_comment) || '',
                warehouse: (dbAppr && dbAppr.warehouse_comment) || ''
            }
        };

        stepperData[fNo] = {
            statusText: statusText,
            statusClass: statusClass,
            steps: [
                {
                    role: 'user',
                    title: '1. User Membuat Form',
                    completed: true,
                    active: false,
                    details: `${reqName} - ${reqDept} (Tanggal: ${userSigDate})`,
                    status: 'Selesai dibuat & diajukan.',
                    color: 'var(--color-success)'
                },
                {
                    role: 'staff',
                    title: '2. Approval Staff / Section Head',
                    completed: staffDone,
                    active: !staffDone,
                    details: staffDone ? `${staffSigner} (Staff / Section Head - Tanggal: ${staffSigDate})` : 'Menunggu persetujuan Staff / Section Head...',
                    status: staffDone ? 'Disetujui oleh Staff / Section Head.' : 'Butuh Approval Staff / Section Head.',
                    color: staffDone ? 'var(--color-success)' : (!staffDone ? 'var(--color-primary)' : 'var(--text-muted)')
                },
                {
                    role: 'warehouse',
                    title: '3. Discontinue Warehouse Consumable',
                    completed: whDone,
                    active: staffDone && !whDone,
                    details: whDone ? `${whSigner} (Warehouse - Tanggal: ${whSigDate})` : (staffDone ? 'Menunggu verifikasi discontinue oleh Warehouse...' : 'Menunggu Approval Staff / Section Head...'),
                    status: whDone ? 'Telah Discontinue.' : 'Menunggu Discontinue Warehouse.',
                    color: whDone ? 'var(--color-success)' : (staffDone && !whDone ? 'var(--color-primary)' : 'var(--text-muted)')
                }
            ]
        };
    });

    // Populate items into checksheets
    serverFormItems.forEach(item => {
        const fNo = item.form_number || defaultFormNo;
        if (checksheets[fNo]) {
            checksheets[fNo].items.push({
                id: item.id,
                no: checksheets[fNo].items.length + 1,
                kode: item.kode_barang || '',
                nama: item.nama_barang || '',
                spesifikasi: item.spesifikasi || '',
                kategori: item.kategori || 'CONSUMABLE',
                keterangan: item.keterangan || ''
            });
        }
    });

    // Populate comments map
    serverFormComments.forEach(c => {
        const fNo = c.form_number;
        if (!formCommentsMap[fNo]) formCommentsMap[fNo] = [];
        formCommentsMap[fNo].push({
            id: c.id,
            form_number: c.form_number,
            user_id: c.user_id,
            user_name: c.user_name,
            user_dept: c.user_dept,
            user_role: c.user_role,
            comment: c.comment,
            created_at: formatCommentDate(c.created_at),
            can_delete: (userRoleType === 'admin')
        });
    });

    function getRoleBadgeConfig(role) {
        const r = (role || 'USER').toUpperCase();
        if (r.includes('MASTER') || r.includes('ADMIN')) {
            return { bg: 'rgba(99, 102, 241, 0.15)', color: '#4f46e5', avatarBg: 'linear-gradient(135deg, #4f46e5, #7c3aed)', label: 'MASTER' };
        } else if (r.includes('STAFF')) {
            return { bg: 'rgba(245, 158, 11, 0.15)', color: '#d97706', avatarBg: 'linear-gradient(135deg, #f59e0b, #d97706)', label: 'STAFF' };
        } else if (r.includes('WAREHOUSE')) {
            return { bg: 'rgba(16, 185, 129, 0.15)', color: '#059669', avatarBg: 'linear-gradient(135deg, #10b981, #059669)', label: 'WAREHOUSE' };
        } else {
            return { bg: 'rgba(59, 130, 246, 0.15)', color: 'rgb(29, 78, 216)', avatarBg: 'linear-gradient(135deg, #0ea5e9, #2563eb)', label: 'USER' };
        }
    }

    function switchSheet(tabId) {
        if (tabId === 'account-master' && userRoleType !== 'admin') {
            alert('Akses Ditolak: Hanya Role Master yang memiliki wewenang untuk mengakses fitur Account Master.');
            return;
        }

        document.querySelectorAll('.tab-pane').forEach(pane => pane.classList.remove('active'));
        document.querySelectorAll('.sheet-tab').forEach(tab => tab.classList.remove('active'));

        const activePane = document.getElementById(tabId + '-pane');
        if (activePane) activePane.classList.add('active');

        const matchingTab = document.querySelector(`.sheet-tab[data-tab="${tabId}"]`) || Array.from(document.querySelectorAll('.sheet-tab')).find(t => {
            const attr = t.getAttribute('onclick') || '';
            return attr.includes(`'${tabId}'`) || attr.includes(`"${tabId}"`);
        });
        if (matchingTab) matchingTab.classList.add('active');
    }

    function printCurrentSheet() {
        switchSheet('print-preview');
        setTimeout(function() { window.print(); }, 150);
    }

    function printChecksheet(csId) {
        viewChecksheet(csId);
        setTimeout(function() { window.print(); }, 150);
    }

    function renderSignatureSlot(containerEl, statusText, val, qrcodeId) {
        if (!containerEl) return;
        if (val && val !== '...................') {
            const safeVal = val.replace(/"/g, '&quot;');
            containerEl.innerHTML = `
                <div style="color: var(--color-success); font-weight: 700; font-size: 0.72rem; margin-bottom: 0.2rem;">${statusText}</div>
                <div class="sig-qrcode" id="${qrcodeId}" data-val="${safeVal}"></div>
                <div style="font-size: 0.65rem; color: var(--text-muted); font-weight: 600; margin-top: 0.2rem; text-align: center;">${val}</div>
            `;
            const qrEl = document.getElementById(qrcodeId);
            if (qrEl && typeof QRCode !== 'undefined') {
                qrEl.innerHTML = '';
                try {
                    new QRCode(qrEl, {
                        text: val,
                        width: 48,
                        height: 48,
                        colorDark: "#000000",
                        colorLight: "#ffffff",
                        correctLevel: QRCode.CorrectLevel.M
                    });
                } catch (e) {
                    console.warn("QRCode error for slot:", qrcodeId, val, e);
                }
            }
        } else {
            containerEl.innerHTML = `<span style="color: var(--text-muted); font-size: 0.75rem; font-style: italic;">...................</span>`;
        }
    }

    function initAllSignaturesQRCodes() {
        if (typeof QRCode === 'undefined') return;
        document.querySelectorAll('.sig-qrcode').forEach(el => {
            const val = el.getAttribute('data-val');
            if (val && val !== '...................' && !el.querySelector('img, canvas')) {
                el.innerHTML = '';
                try {
                    new QRCode(el, {
                        text: val,
                        width: 48,
                        height: 48,
                        colorDark: "#000000",
                        colorLight: "#ffffff",
                        correctLevel: QRCode.CorrectLevel.M
                    });
                } catch (e) {
                    console.warn("QRCode error on init:", val, e);
                }
            }
        });
    }

    function viewChecksheet(csId, switchToPrintPreview = true) {
        if (!canViewAllDepartments) {
            const parts = csId.split('/');
            const csDept = parts.length >= 2 ? parts[1].trim().toUpperCase() : '';
            if (csDept && !isDeptAllowed(csDept)) {
                alert('Akses Ditolak: Anda tidak dapat melihat formulir dari departemen lain.');
                return;
            }
        }

        selectedChecksheetId = csId;
        if (switchToPrintPreview) {
            switchSheet('print-preview');
        }

        const cs = checksheets[csId];
        if (!cs) return;

        const hiddenFormNo = document.getElementById('modal_form_number');
        if (hiddenFormNo) hiddenFormNo.value = csId;

        const displayEl = document.getElementById('form-number-display');
        if (displayEl) displayEl.innerText = csId;

        const toolbarFormNo = document.getElementById('toolbar-form-no');
        if (toolbarFormNo) toolbarFormNo.innerText = csId;

        const commentsFormNo = document.getElementById('comments-form-no');
        if (commentsFormNo) commentsFormNo.innerText = csId;

        const prevReq = document.getElementById('preview-requestor');
        if (prevReq && cs.requestor) prevReq.innerText = cs.requestor;

        const prevDate = document.getElementById('preview-date');
        if (prevDate && cs.date) prevDate.innerText = cs.date;

        const signatureDibuat = document.getElementById('preview-sig-dibuat');
        const signatureStaff = document.getElementById('preview-sig-staff');
        const signatureWarehouse = document.getElementById('preview-sig-warehouse');

        renderSignatureSlot(signatureDibuat, '✓ USER SUBMITTED', cs.signatures.dibuat, 'sig-qrcode-dibuat');
        renderSignatureSlot(signatureStaff, '✓ APPROVED BY STAFF', cs.signatures.staff, 'sig-qrcode-staff');
        renderSignatureSlot(signatureWarehouse, '✓ DISCONTINUED BY WAREHOUSE', cs.signatures.warehouse, 'sig-qrcode-warehouse');

        const tbody = document.getElementById('preview-table-body');
        if (tbody) {
            if (!cs.items || cs.items.length === 0) {
                tbody.innerHTML = emptyTableHtml;
            } else {
                let rowsHtml = '';
                cs.items.forEach((item, index) => {
                    const isEven = index % 2 !== 0;
                    rowsHtml += `
                        <tr class="data-row ${isEven ? 'tr-even' : ''}">
                            <td class="td-center td-no">${item.no}</td>
                            <td class="td-center" style="padding:0 0.4rem;">
                                ${item.kode ? `<span class="item-code-badge">${item.kode}</span>` : '<span style="color:var(--text-muted); font-size:0.75rem;">-</span>'}
                            </td>
                            <td style="padding:0 0.6rem; font-weight:700; font-size:0.82rem; color:#0f172a;">${item.nama}</td>
                            <td style="padding:0 0.5rem; font-size:0.78rem; color:#334155;">${item.spesifikasi || '-'}</td>
                            <td class="td-center" style="font-size:0.78rem; font-weight:600; color:#0f172a;">${item.kategori || 'CONSUMABLE'}</td>
                            <td style="padding:0 0.5rem; font-size:0.78rem; color:#475569;">${item.keterangan || '-'}</td>
                        </tr>
                    `;
                });
                tbody.innerHTML = rowsHtml;
            }
        }

        renderComments(csId);
    }

    function createNewForm() {
        let maxSeq = 0;
        for (const fNo in checksheets) {
            const parts = fNo.split('/');
            const fDept = parts.length >= 2 ? parts[1].trim().toUpperCase() : '';
            const fMY = parts.length >= 3 ? parts[2].trim() : '';
            if (fDept === userTag.toUpperCase() && fMY === monthYearStr) {
                const seq = parseInt(parts[0], 10);
                if (!isNaN(seq) && seq > maxSeq) {
                    maxSeq = seq;
                }
            }
        }

        const nextSeq = String(maxSeq + 1).padStart(2, '0');
        const todayStr = '{{ date("d-m-Y") }}';
        const nextFormNo = `${nextSeq}/${userTag}/${monthYearStr}`;

        checksheets[nextFormNo] = {
            formNo: nextFormNo,
            date: todayStr,
            requestor: '{{ Auth::user()->name ?? "User" }} / {{ Auth::user()->department ?? "Production" }}',
            requestorName: '{{ Auth::user()->name ?? "User" }}',
            requestorDept: '{{ Auth::user()->department ?? "Production" }}',
            status: 'Butuh Approval Staff / Section Head',
            items: [],
            signatures: {
                dibuat: '{{ Auth::user()->name ?? "User" }} (Tgl: ' + todayStr + ')',
                staff: '...................',
                warehouse: '...................'
            },
            comments: {
                user: 'Formulir pengajuan unregistrasi baru.',
                staff: '',
                warehouse: ''
            }
        };

        stepperData[nextFormNo] = {
            statusText: 'Butuh Approval Staff / Section Head',
            statusClass: 'badge-warning',
            steps: [
                { role: 'user', title: '1. User Membuat Form', completed: true, active: false, details: '{{ Auth::user()->name ?? "User" }} (' + todayStr + ')', status: 'Selesai dibuat & diajukan.', color: 'var(--color-success)' },
                { role: 'staff', title: '2. Approval Staff / Section Head', completed: false, active: true, details: 'Menunggu Approval Staff / Section Head...', status: 'Butuh Approval Staff / Section Head.', color: 'var(--color-primary)' },
                { role: 'warehouse', title: '3. Discontinue Warehouse Consumable', completed: false, active: false, details: 'Menunggu Verifikasi Warehouse...', status: 'Menunggu Discontinue Warehouse.', color: 'var(--text-muted)' }
            ]
        };

        // Update form number input inside addItemModal
        const modalFormNoInput = document.getElementById('modal_form_number');
        if (modalFormNoInput) {
            modalFormNoInput.value = nextFormNo;
        }

        populateDateFilterOptions();
        renderDataViewTable();
        renderApprovalMonitoringTable();
        viewChecksheet(nextFormNo);
        renderComments(nextFormNo);
        openModal('addItemModal');

        showToast(`Formulir Unregistrasi Baru (${nextFormNo}) Berhasil Dibuat! Silakan isi data barang.`, 'success');
    }

    // Render Approval Monitoring Table (Tab 2)
    let currentApprovalFilter = '';
    function filterApprovalStage(stage, btnElement) {
        currentApprovalFilter = stage;
        document.querySelectorAll('#approval-filter-pills .filter-pill-btn').forEach(btn => {
            btn.classList.remove('btn-primary', 'active');
            btn.classList.add('btn-secondary');
        });
        if (btnElement) {
            btnElement.classList.remove('btn-secondary');
            btnElement.classList.add('btn-primary', 'active');
        }
        renderApprovalMonitoringTable();
    }

    function renderApprovalMonitoringTable() {
        const tbody = document.getElementById('approval-monitoring-tbody');
        if (!tbody) return;

        let html = '';
        let no = 1;
        let statTotal = 0;
        let statStaff = 0;
        let statWarehouse = 0;

        for (const formNo in checksheets) {
            const cs = checksheets[formNo];
            if (!cs.items || cs.items.length === 0) continue;

            const data = stepperData[formNo];
            if (!data) continue;

            // Form yang sudah discontinue tidak muncul di monitoring approval
            const isCompleted = (cs.status === 'Telah Discontinue' || data.statusText === 'Telah Discontinue' || (data.steps && data.steps[2] && data.steps[2].completed));
            if (isCompleted) {
                continue;
            }

            const formDept = getCsDepartment(cs, formNo);
            if (!canViewAllDepartments && !isDeptAllowed(formDept)) {
                continue;
            }

            statTotal++;

            const activeStepIndex = data.steps.findIndex(s => s.active);
            let stageKey = 'user';
            if (activeStepIndex === 1) {
                stageKey = 'staff';
                statStaff++;
            } else if (activeStepIndex === 2) {
                stageKey = 'warehouse';
                statWarehouse++;
            }

            if (currentApprovalFilter && currentApprovalFilter !== stageKey) {
                continue;
            }

            let badgeBg = 'rgba(245, 158, 11, 0.12)';
            let badgeColor = '#d97706';
            let badgeText = data.statusText;

            if (badgeText === 'Telah Discontinue') {
                badgeBg = 'rgba(16, 185, 129, 0.15)';
                badgeColor = '#059669';
            } else if (badgeText === 'Butuh Verifikasi Warehouse Consumable') {
                badgeBg = 'rgba(59, 130, 246, 0.12)';
                badgeColor = '#2563eb';
            } else {
                badgeBg = 'rgba(245, 158, 11, 0.12)';
                badgeColor = '#d97706';
            }

            // Generate 3-step Horizontal Stepper Visual Pills
            let stepsHtml = '<div style="display: inline-flex; align-items: center; gap: 0.25rem;">';
            data.steps.forEach((st, idx) => {
                let pillStyle = 'background: #f1f5f9; color: #94a3b8; border: 1px solid #cbd5e1;';
                let pillLabel = idx === 0 ? 'User' : (idx === 1 ? 'Staff' : 'WH');
                
                if (st.completed) {
                    pillStyle = 'background: var(--color-success); color: #fff; border: 1px solid var(--color-success);';
                    pillLabel = '✓ ' + pillLabel;
                } else if (st.active) {
                    pillStyle = 'background: var(--color-primary); color: #fff; border: 1px solid var(--color-primary); font-weight: 700; box-shadow: 0 0 0 2px var(--color-primary-light);';
                }

                stepsHtml += `<span style="font-size: 0.68rem; font-weight: 600; padding: 0.15rem 0.45rem; border-radius: 12px; ${pillStyle}">${pillLabel}</span>`;
                if (idx < 2) {
                    stepsHtml += `<span style="color: #cbd5e1; font-size: 0.65rem;">➔</span>`;
                }
            });
            stepsHtml += '</div>';

            // Role-Based Action Button
            let actionBtnHtml = '';
            if (userRoleType === 'user') {
                actionBtnHtml = `
                    <div style="display: inline-flex; flex-direction: column; align-items: center; gap: 2px;">
                        <button class="btn btn-secondary btn-sm" onclick="viewChecksheet('${cs.formNo}')" style="padding: 0.3rem 0.65rem; font-size: 0.75rem; border-radius: 6px; display: inline-flex; align-items: center; gap: 0.25rem;">
                            <svg viewBox="0 0 24 24" width="12" height="12" stroke="currentColor" stroke-width="2.5" fill="none"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                            Lihat Form
                        </button>
                        <span style="font-size: 0.68rem; color: var(--text-muted); font-weight: 500;">(Hanya Pembuat)</span>
                    </div>
                `;
            } else if (userRoleType === 'staff') {
                if (stageKey === 'staff') {
                    actionBtnHtml = `
                        <button class="btn btn-primary btn-sm" onclick="openQuickApprovalModal('${cs.formNo}')" style="padding: 0.35rem 0.75rem; font-size: 0.78rem; font-weight: 700; border-radius: 6px; display: inline-flex; align-items: center; gap: 0.3rem; background: linear-gradient(135deg, #2563eb, #1d4ed8);">
                            <svg viewBox="0 0 24 24" width="13" height="13" stroke="currentColor" stroke-width="2.5" fill="none"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            Setujui (Staff)
                        </button>
                    `;
                } else {
                    actionBtnHtml = `
                        <div style="display: inline-flex; flex-direction: column; align-items: center; gap: 2px;">
                            <button class="btn btn-secondary btn-sm" onclick="viewChecksheet('${cs.formNo}')" style="padding: 0.25rem 0.55rem; font-size: 0.72rem; border-radius: 6px;">Lihat Form</button>
                            <span style="font-size: 0.68rem; color: #0284c7; font-weight: 600;">Menunggu WH</span>
                        </div>
                    `;
                }
            } else if (userRoleType === 'warehouse') {
                if (stageKey === 'staff') {
                    actionBtnHtml = `
                        <div style="display: inline-flex; flex-direction: column; align-items: center; gap: 2px;">
                            <button class="btn btn-secondary btn-sm" onclick="viewChecksheet('${cs.formNo}')" style="padding: 0.25rem 0.55rem; font-size: 0.72rem; border-radius: 6px;">Lihat Form</button>
                            <span style="font-size: 0.68rem; color: #d97706; font-weight: 600;">Menunggu Staff</span>
                        </div>
                    `;
                } else if (stageKey === 'warehouse') {
                    actionBtnHtml = `
                        <button class="btn btn-primary btn-sm" onclick="openQuickApprovalModal('${cs.formNo}')" style="padding: 0.35rem 0.75rem; font-size: 0.78rem; font-weight: 700; border-radius: 6px; display: inline-flex; align-items: center; gap: 0.3rem; background: linear-gradient(135deg, #059669, #047857);">
                            <svg viewBox="0 0 24 24" width="13" height="13" stroke="currentColor" stroke-width="2.5" fill="none"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                            Discontinue
                        </button>
                    `;
                }
            } else {
                let adminBtnLabel = 'Setujui Form';
                let adminBg = 'linear-gradient(135deg, #2563eb, #1d4ed8)';
                if (stageKey === 'staff') {
                    adminBtnLabel = 'Setujui (Staff / Admin)';
                } else if (stageKey === 'warehouse') {
                    adminBtnLabel = 'Discontinue (WH / Admin)';
                    adminBg = 'linear-gradient(135deg, #059669, #047857)';
                }
                actionBtnHtml = `
                    <button class="btn btn-primary btn-sm" onclick="openQuickApprovalModal('${cs.formNo}')" style="padding: 0.35rem 0.75rem; font-size: 0.78rem; font-weight: 700; border-radius: 6px; display: inline-flex; align-items: center; gap: 0.3rem; background: ${adminBg};">
                        <svg viewBox="0 0 24 24" width="13" height="13" stroke="currentColor" stroke-width="2.5" fill="none"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        ${adminBtnLabel}
                    </button>
                `;
            }

            html += `
                <tr>
                    <td class="td-center td-no">${no}</td>
                    <td style="font-weight: 700; color: var(--color-primary); padding: 0 0.8rem;">
                        <a href="javascript:void(0)" onclick="viewChecksheet('${cs.formNo}')" style="color: var(--color-primary); text-decoration: underline;">${cs.formNo}</a>
                    </td>
                    <td style="padding: 0 0.8rem; font-size: 0.85rem; font-weight: 600;">${cs.requestor}</td>
                    <td style="padding: 0 0.8rem; font-size: 0.85rem;">${cs.date}</td>
                    <td class="td-center" style="padding: 0 0.4rem;">${stepsHtml}</td>
                    <td class="td-center">
                        <span style="background-color: ${badgeBg}; color: ${badgeColor}; padding: 0.25rem 0.6rem; border-radius: 4px; font-weight: 700; font-size: 0.75rem; display: inline-block;">${badgeText}</span>
                    </td>
                    <td class="td-center" style="padding: 0 0.5rem;">${actionBtnHtml}</td>
                </tr>
            `;
            no++;
        }

        if (html === '') {
            let emptyMsg = 'Tidak ada formulir dalam proses yang sesuai dengan filter alur approval.';
            if (!canViewAllDepartments) {
                emptyMsg = `Tidak ada formulir pengajuan aktif / dalam proses untuk Departemen ${authUserDept || 'Anda'}.`;
            }
            html = `
                <tr>
                    <td colspan="7" style="text-align: center; padding: 2.5rem 1rem; color: var(--text-muted); font-size: 0.9rem;">
                        ${emptyMsg}
                    </td>
                </tr>
            `;
        }

        tbody.innerHTML = html;

        const elTotal = document.getElementById('approval-stat-total');
        if (elTotal) elTotal.innerText = statTotal;

        const elStaff = document.getElementById('approval-stat-staff');
        if (elStaff) elStaff.innerText = statStaff;

        const elWh = document.getElementById('approval-stat-warehouse');
        if (elWh) elWh.innerText = statWarehouse;
    }

    function openQuickApprovalModal(csId) {
        selectedChecksheetId = csId;
        const cs = checksheets[csId];
        const data = stepperData[csId];
        if (!cs || !data) return;

        if (userRoleType === 'user') {
            alert('Akses Ditolak: Akun dengan role User hanya bertugas membuat & mengajukan FORM dan tidak memiliki wewenang approval.');
            return;
        }

        const activeStepIndex = data.steps.findIndex(s => s.active);

        if (userRoleType === 'staff') {
            const formDept = getCsDepartment(cs, csId);
            if (formDept && !isDeptAllowed(formDept)) {
                alert(`Akses Ditolak: Anda login sebagai Staff Departemen ${authUserDept}. Anda hanya berwenang menyetujui formulir dari departemen Anda sendiri.`);
                return;
            }
            if (activeStepIndex !== 1) {
                alert('Akses Ditolak: Role Staff hanya dapat melakukan approval ketika formulir baru diajukan oleh User.');
                return;
            }
        } else if (userRoleType === 'warehouse') {
            if (activeStepIndex === 0 || activeStepIndex === 1) {
                alert('Akses Ditolak: Warehouse Consumable baru dapat memproses discontinue setelah disetujui oleh Staff.');
                return;
            } else if (activeStepIndex !== 2) {
                alert('Formulir ini tidak sedang berada dalam tahap Discontinue Warehouse.');
                return;
            }
        }

        let currentRoleKey = 'staff';
        let roleBadgeTitle = 'Staff Approver (Approval Tahap 1)';
        let roleBadgeColor = '#2563eb';
        let actionBtnText = '✓ Setujui sebagai Staff';
        let infoHelperText = `Memverifikasi pengajuan discontinue barang yang diajukan oleh <strong>${cs.requestor}</strong>.`;

        if (activeStepIndex === 2) {
            currentRoleKey = 'warehouse';
            roleBadgeTitle = 'Warehouse Consumable (Final Discontinue)';
            roleBadgeColor = '#059669';
            actionBtnText = '✓ Konfirmasi Discontinue (Warehouse)';
            infoHelperText = `Memverifikasi penghapusan / discontinue barang consumable dari master inventaris setelah persetujuan oleh Staff (<strong>${cs.signatures.staff || 'Staff'}</strong>).`;
        }

        const curUserName = authUserName || '{{ Auth::user()->name ?? "User" }}';
        const modalBody = document.getElementById('quick-approval-modal-body');

        if (modalBody) {
            modalBody.innerHTML = `
                <div style="background: rgba(79, 70, 229, 0.04); border: 1px solid rgba(79, 70, 229, 0.12); padding: 0.85rem 1rem; border-radius: var(--radius-md); margin-bottom: 1.25rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-weight: 800; font-size: 1rem; color: var(--color-primary);">${cs.formNo}</span>
                        <span class="badge" style="background-color: var(--color-warning-light); color: var(--color-warning);">${data.statusText}</span>
                    </div>
                    <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.35rem;">
                        <strong>Requestor:</strong> ${cs.requestor} &nbsp;|&nbsp; <strong>Items:</strong> ${cs.items ? cs.items.length : 0} Item
                    </div>
                </div>

                {{-- Horizontal Stepper Visual --}}
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem; background: #f8fafc; padding: 0.75rem; border-radius: var(--radius-md);">
                    ${data.steps.map((st, i) => `
                        <div style="display: flex; flex-direction: column; align-items: center; gap: 0.2rem; flex: 1; text-align: center;">
                            <div style="width: 22px; height: 22px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; font-weight: 700; ${st.completed ? 'background: var(--color-success); color: white;' : (st.active ? 'background: var(--color-primary); color: white; box-shadow: 0 0 0 3px var(--color-primary-light);' : 'background: #e2e8f0; color: #64748b;')}">
                                ${st.completed ? '✓' : (i + 1)}
                            </div>
                            <span style="font-size: 0.7rem; font-weight: 600; color: ${st.active ? 'var(--color-primary)' : (st.completed ? 'var(--color-success)' : 'var(--text-muted)')};">${i === 0 ? 'User' : (i === 1 ? 'Staff' : 'Warehouse')}</span>
                        </div>
                    `).join('<div style="width: 25px; height: 2px; background: #e2e8f0; margin-bottom: 1rem;"></div>')}
                </div>

                <div style="background: #f1f5f9; border-left: 3px solid ${roleBadgeColor}; padding: 0.65rem 0.85rem; border-radius: 6px; margin-bottom: 1.25rem; font-size: 0.8rem; color: #334155;">
                    ${infoHelperText}
                </div>

                <form onsubmit="submitQuickApproval(event, '${csId}', '${currentRoleKey}')">
                    <input type="hidden" id="qa-role" value="${currentRoleKey}">

                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label style="font-weight: 700; font-size: 0.82rem; margin-bottom: 0.3rem; display: block;">Peran Verifikator (Terkunci Sesuai Hak Akses):</label>
                        <div style="background: #f8fafc; border: 1px solid #cbd5e1; padding: 0.6rem 0.85rem; border-radius: 6px; font-size: 0.85rem; font-weight: 700; color: ${roleBadgeColor}; display: flex; align-items: center; gap: 0.4rem;">
                            <span style="width: 8px; height: 8px; border-radius: 50%; background: ${roleBadgeColor}; display: inline-block;"></span>
                            ${roleBadgeTitle}
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label style="font-weight: 700; font-size: 0.82rem; margin-bottom: 0.3rem; display: block;">Nama Penandatangan:</label>
                        <input type="text" id="qa-name" class="form-control" style="height: 38px; font-size: 0.85rem;" value="${curUserName}" required>
                    </div>

                    <div class="form-group" style="margin-bottom: 1.25rem;">
                        <label style="font-weight: 700; font-size: 0.82rem; margin-bottom: 0.3rem; display: block;">Catatan Verifikasi (Opsional):</label>
                        <input type="text" id="qa-comment" class="form-control" style="height: 38px; font-size: 0.85rem;" placeholder="Cth: Disetujui untuk discontinue." value="Disetujui.">
                    </div>

                    <div style="display: flex; gap: 0.75rem; justify-content: flex-end; margin-top: 1.25rem;">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('quickApprovalModal')">Batal</button>
                        <button type="submit" class="btn btn-primary" style="font-weight: 700; padding: 0.6rem 1.2rem; display: flex; align-items: center; gap: 0.4rem; background: ${roleBadgeColor}; border-color: ${roleBadgeColor};">
                            <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2.5" fill="none"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            ${actionBtnText}
                        </button>
                    </div>
                </form>
            `;
        }

        openModal('quickApprovalModal');
    }

    async function submitQuickApproval(event, csId, defaultRoleKey) {
        event.preventDefault();
        const cs = checksheets[csId];
        const steps = stepperData[csId] ? stepperData[csId].steps : null;
        if (!cs || !steps) return;

        const role = document.getElementById('qa-role').value;
        const name = document.getElementById('qa-name').value.trim();
        const comment = document.getElementById('qa-comment').value.trim() || 'Disetujui.';

        if (!name) {
            alert('Harap isi nama penandatangan!');
            return;
        }

        if (role === 'staff' && userRoleType !== 'admin') {
            const formDept = getCsDepartment(cs, csId);
            if (formDept && !isDeptAllowed(formDept)) {
                alert(`Akses Ditolak: Anda login sebagai Staff Departemen ${authUserDept}. Anda hanya berwenang menyetujui formulir dari departemen Anda sendiri.`);
                return;
            }
            if (!steps[0].completed) {
                alert('Akses Gagal: Form harus dibuat dan diajukan oleh User terlebih dahulu!');
                return;
            }
        } else if (role === 'warehouse') {
            if (!steps[1].completed && userRoleType !== 'admin') {
                alert('Akses Gagal: Approval Staff harus diselesaikan terlebih dahulu sebelum Discontinue Warehouse!');
                return;
            }
        }

        const submitBtn = event.target.querySelector('button[type="submit"]');
        const origBtnHtml = submitBtn ? submitBtn.innerHTML : '';
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<svg class="spinner" viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><circle cx="12" cy="12" r="10"></circle></svg> Menyimpan...';
        }

        try {
            const response = await fetch('{{ route("form-unregistrasi.approve") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    form_number: csId,
                    role: role,
                    name: name,
                    comment: comment
                })
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                alert('Gagal menyimpan approval: ' + (data.message || 'Terjadi kesalahan sistem.'));
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = origBtnHtml;
                }
                return;
            }

            const todayStr = '{{ date("d-m-Y") }}';

            if (role === 'staff') {
                cs.signatures.staff = name + ' (Tgl: ' + todayStr + ')';
                cs.comments.staff = comment;

                steps[1].completed = true;
                steps[1].active = false;
                steps[1].details = name + ' (Staff / Section Head - Tanggal: ' + todayStr + ')';
                steps[1].status = 'Disetujui oleh Staff / Section Head.';
                steps[1].color = 'var(--color-success)';

                steps[2].active = true;
                steps[2].details = 'Menunggu verifikasi discontinue oleh Warehouse...';
                steps[2].status = 'Butuh Verifikasi Warehouse Consumable.';
                steps[2].color = 'var(--color-primary)';

                cs.status = 'Butuh Verifikasi Warehouse Consumable';
                stepperData[csId].statusText = 'Butuh Verifikasi Warehouse Consumable';
                stepperData[csId].statusClass = 'badge-primary';
            } else if (role === 'warehouse') {
                cs.signatures.warehouse = name + ' (Tgl: ' + todayStr + ')';
                cs.comments.warehouse = comment;

                steps[2].completed = true;
                steps[2].active = false;
                steps[2].details = name + ' (Warehouse Consumable - Tanggal: ' + todayStr + ')';
                steps[2].status = 'Telah Discontinue.';
                steps[2].color = 'var(--color-success)';

                cs.status = 'Telah Discontinue';
                stepperData[csId].statusText = 'Telah Discontinue';
                stepperData[csId].statusClass = 'badge-success';
            }

            closeModal('quickApprovalModal');
            viewChecksheet(csId, false);
            renderApprovalMonitoringTable();
            renderDataViewTable();
            showToast(`Persetujuan (${role.toUpperCase()}) berhasil disimpan!`, 'success');

        } catch (err) {
            console.error('Approval error:', err);
            alert('Terjadi kesalahan koneksi saat memproses approval.');
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = origBtnHtml;
            }
        }
    }

    // Render Data View Table (Tab 3)
    function renderDataViewTable() {
        const tbody = document.getElementById('dataview-tbody');
        if (!tbody) return;

        const monthSelect = document.getElementById('filter-month-dataview');
        const yearSelect = document.getElementById('filter-year-dataview');
        const selectedMonth = monthSelect ? monthSelect.value : '';
        const selectedYear = yearSelect ? yearSelect.value : '';

        let html = '';
        let no = 1;
        let totalChecksheets = 0;
        let approvedChecksheets = 0;
        let processChecksheets = 0;

        for (const formNo in checksheets) {
            const cs = checksheets[formNo];

            if (!cs.items || cs.items.length === 0) {
                continue;
            }

            const formDept = getCsDepartment(cs, formNo);

            if (!canViewAllDepartments && !isDeptAllowed(formDept)) {
                continue;
            }

            let csM = '';
            let csY = '';
            if (cs.date && cs.date.includes('-')) {
                const parts = cs.date.split('-');
                if (parts.length === 3) {
                    csM = parts[1];
                    csY = parts[2];
                }
            }
            if ((!csM || !csY) && cs.formNo) {
                const parts = cs.formNo.split('/');
                if (parts.length >= 3) {
                    const myParts = parts[2].split('-');
                    if (myParts.length === 2) {
                        if (!csM) csM = myParts[0];
                        if (!csY) csY = myParts[1];
                    }
                }
            }

            if (selectedMonth && csM !== selectedMonth) {
                continue;
            }
            if (selectedYear && csY !== selectedYear) {
                continue;
            }

            totalChecksheets++;

            let statusBg = 'rgba(245, 158, 11, 0.12)';
            let statusColor = '#d97706';
            let displayStatus = cs.status;

            if (cs.status === 'Telah Discontinue' || cs.status === 'Selesai' || cs.status === 'Approved') {
                displayStatus = 'Telah Discontinue';
                statusBg = 'rgba(16, 185, 129, 0.15)';
                statusColor = '#059669';
                approvedChecksheets++;
            } else if (cs.status === 'Butuh Verifikasi Warehouse Consumable' || cs.status === 'Pending Warehouse') {
                displayStatus = 'Butuh Verifikasi Warehouse Consumable';
                statusBg = 'rgba(59, 130, 246, 0.12)';
                statusColor = '#2563eb';
                processChecksheets++;
            } else {
                displayStatus = 'Butuh Approval Staff / Section Head';
                statusBg = 'rgba(245, 158, 11, 0.12)';
                statusColor = '#d97706';
                processChecksheets++;
            }

            const itemCount = cs.items.length + ' Item';

            const actionButtonsHtml = `
                <button class="btn btn-primary btn-sm" onclick="viewChecksheet('${cs.formNo}')" style="padding: 0.35rem 0.75rem; font-size: 0.75rem; font-family: var(--font-body); font-weight: 600; border-radius: 6px;">Lihat Preview</button>
                <button class="btn btn-secondary btn-sm" onclick="printChecksheet('${cs.formNo}')" style="padding: 0.35rem 0.75rem; font-size: 0.75rem; margin-left: 0.25rem; font-family: var(--font-body); font-weight: 600; border-radius: 6px;">Cetak</button>
            `;
            const deleteColumnHtml = (userRoleType === 'admin') ? `
                <td class="td-center" style="padding: 0 0.5rem;">
                    <button class="btn btn-sm" onclick="deleteChecksheetForm('${cs.formNo}')" style="padding: 0.35rem 0.75rem; font-size: 0.75rem; font-family: var(--font-body); font-weight: 600; border-radius: 6px; background-color: #ef4444; color: white; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 0.25rem;">
                        <svg viewBox="0 0 24 24" width="13" height="13" stroke="currentColor" stroke-width="2.5" fill="none"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6l-1 14H6L5 6"></path><path d="M10 11v6M14 11v6"></path><path d="M9 6V4h6v2"></path></svg>
                        Hapus Form
                    </button>
                </td>
            ` : '';

            html += `
                <tr>
                    <td class="td-center td-no">${no}</td>
                    <td style="font-weight: 700; color: var(--color-primary); padding: 0 0.8rem;">${cs.formNo}</td>
                    <td style="padding: 0 0.8rem; font-size: 0.85rem;">${cs.date}</td>
                    <td style="padding: 0 0.8rem; font-size: 0.85rem; font-weight: 600;">${cs.requestor}</td>
                    <td class="td-center" style="font-weight: 700;">${itemCount}</td>
                    <td class="td-center">
                        <span style="background-color: ${statusBg}; color: ${statusColor}; padding: 0.25rem 0.5rem; border-radius: 4px; font-weight: 700; font-size: 0.75rem; display: inline-block;">${displayStatus}</span>
                    </td>
                    <td class="td-center" style="padding: 0 0.5rem;">${actionButtonsHtml}</td>
                    ${deleteColumnHtml}
                </tr>
            `;
            no++;
        }

        const isFiltered = Boolean(selectedMonth || selectedYear);
        const colSpanCount = (userRoleType === 'admin') ? 8 : 7;

        if (totalChecksheets === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="${colSpanCount}" style="padding: 0; border: none;">
                        <div class="empty-state-wrapper" style="margin: 1.25rem 0;">
                            <div class="empty-state-card" style="gap: 0.75rem;">
                                <div class="empty-state-icon-container" style="width: 60px; height: 60px;">
                                    <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.6">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                        <polyline points="14 2 14 8 20 8"></polyline>
                                    </svg>
                                </div>
                                <h4 class="empty-state-title" style="font-size: 1rem;">${isFiltered ? 'Tidak Ada Form Unregistrasi pada Periode Ini' : 'Belum Ada Form Unregistrasi Berisi Data'}</h4>
                                <p class="empty-state-desc" style="font-size: 0.8rem;">${isFiltered ? 'Tidak ditemukan data formulir discontinue untuk filter Bulan / Tahun yang dipilih.' : 'Formulir unregistrasi akan tampil secara otomatis di sini setelah Anda menambahkan item barang.'}</p>
                            </div>
                        </div>
                    </td>
                </tr>
            `;
        } else {
            tbody.innerHTML = html;
        }

        const statTotal = document.getElementById('stat-total-checksheets');
        if (statTotal) statTotal.innerText = totalChecksheets;

        const statApproved = document.getElementById('stat-approved-checksheets');
        if (statApproved) statApproved.innerText = approvedChecksheets;

        const statProcess = document.getElementById('stat-process-checksheets');
        if (statProcess) statProcess.innerText = processChecksheets;
    }

    function deleteChecksheetForm(formNo) {
        if (userRoleType !== 'admin') {
            alert('Akses ditolak: Hanya Role Master yang memiliki wewenang untuk menghapus form.');
            return;
        }

        if (!confirm(`Apakah Anda yakin ingin menghapus PERMANEN seluruh Form Unregistrasi "${formNo}" ini?`)) {
            return;
        }

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route("form-unregistrasi.delete-checksheet") }}';

        const csrf = document.createElement('input');
        csrf.type = 'hidden';
        csrf.name = '_token';
        csrf.value = '{{ csrf_token() }}';
        form.appendChild(csrf);

        const method = document.createElement('input');
        method.type = 'hidden';
        method.name = '_method';
        method.value = 'DELETE';
        form.appendChild(method);

        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'form_number';
        input.value = formNo;
        form.appendChild(input);

        const tabInput = document.createElement('input');
        tabInput.type = 'hidden';
        tabInput.name = 'tab';
        tabInput.value = 'data-view';
        form.appendChild(tabInput);

        document.body.appendChild(form);
        form.submit();
    }

    function populateDateFilterOptions() {
        const yearSelect = document.getElementById('filter-year-dataview');
        if (!yearSelect) return;

        const currentYearVal = yearSelect.value;
        const yearsFound = new Set();

        for (const formNo in checksheets) {
            const cs = checksheets[formNo];
            if (!cs.items || cs.items.length === 0) continue;

            let y = '';
            if (cs.date && cs.date.includes('-')) {
                const parts = cs.date.split('-');
                if (parts.length === 3) y = parts[2];
            }
            if (!y && cs.formNo) {
                const parts = cs.formNo.split('/');
                if (parts.length >= 3) {
                    const myParts = parts[2].split('-');
                    if (myParts.length === 2) y = myParts[1];
                }
            }
            if (y) yearsFound.add(y);
        }

        const nowYr = '{{ date("Y") }}';
        yearsFound.add(nowYr);

        const sortedYears = Array.from(yearsFound).sort((a, b) => b.localeCompare(a));

        yearSelect.innerHTML = '<option value="">Semua Tahun</option>';
        sortedYears.forEach(yr => {
            const opt = document.createElement('option');
            opt.value = yr;
            opt.innerText = yr;
            if (yr === currentYearVal) opt.selected = true;
            yearSelect.appendChild(opt);
        });
    }

    // Comments System
    function renderComments(formNo) {
        const listEl = document.getElementById('form-comments-list');
        const countBadge = document.getElementById('comments-count-badge');
        const toolbarCount = document.getElementById('toolbar-comment-count');
        const formNoEl = document.getElementById('comments-form-no');

        if (formNoEl) formNoEl.innerText = formNo;

        const comments = formCommentsMap[formNo] || [];
        const count = comments.length;

        if (countBadge) countBadge.innerText = `${count} Komentar`;
        if (toolbarCount) toolbarCount.innerText = count;

        if (!listEl) return;

        if (count === 0) {
            listEl.innerHTML = `
                <div style="text-align: center; padding: 2rem 1rem; background: #f8fafc; border-radius: 12px; border: 1.5px dashed #cbd5e1;">
                    <div style="width: 48px; height: 48px; border-radius: 50%; background: rgba(0, 173, 239, 0.1); color: var(--color-primary); display: flex; align-items: center; justify-content: center; margin: 0 auto 0.75rem;">
                        <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                        </svg>
                    </div>
                    <div style="font-weight: 700; color: var(--text-dark); font-size: 0.95rem; margin-bottom: 0.25rem;">Belum Ada Komentar</div>
                    <div style="font-size: 0.8rem; color: var(--text-muted); max-width: 420px; margin: 0 auto;">
                        Belum ada diskusi atau catatan discontinue pada formulir <strong>${formNo}</strong>. Semua role dapat menuliskan catatan di bawah.
                    </div>
                </div>
            `;
            return;
        }

        let html = '';
        comments.forEach(c => {
            const roleCfg = getRoleBadgeConfig(c.user_role);
            const initial = (c.user_name || 'U').charAt(0).toUpperCase();
            const canDelete = (userRoleType === 'admin');
            const deleteBtn = canDelete ? `
                <button type="button" class="comment-delete-btn" onclick="deleteFormComment(${c.id})" title="Hapus Komentar (Khusus Master)">
                    <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    </svg>
                </button>
            ` : '';

            html += `
                <div class="comment-item" id="comment-node-${c.id}">
                    <div class="comment-avatar" style="background: ${roleCfg.avatarBg};">
                        ${initial}
                    </div>
                    <div class="comment-bubble">
                        <div class="comment-header">
                            <div class="comment-author">
                                <span>${c.user_name}</span>
                                <span class="comment-role-badge" style="background: ${roleCfg.bg}; color: ${roleCfg.color};">${roleCfg.label}</span>
                                <span style="font-size: 0.72rem; color: var(--text-muted); font-weight: normal;">• ${c.user_dept || 'Production'}</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <span class="comment-time">${c.created_at}</span>
                                ${deleteBtn}
                            </div>
                        </div>
                        <div class="comment-body">${escapeHtml(c.comment)}</div>
                    </div>
                </div>
            `;
        });

        listEl.innerHTML = html;
    }

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.innerText = text;
        return div.innerHTML;
    }

    function scrollToComments() {
        const el = document.getElementById('form-comments-section');
        if (el) {
            el.scrollIntoView({ behavior: 'smooth', block: 'center' });
            const textarea = document.getElementById('comment-textarea');
            if (textarea) setTimeout(() => textarea.focus(), 400);
        }
    }

    function handleCommentKeydown(event) {
        if ((event.ctrlKey || event.metaKey) && event.key === 'Enter') {
            event.preventDefault();
            submitFormComment(event);
        }
    }

    async function submitFormComment(event) {
        if (event) event.preventDefault();

        const textarea = document.getElementById('comment-textarea');
        if (!textarea) return;

        const commentText = textarea.value.trim();
        if (!commentText) {
            alert('Silakan tulis komentar terlebih dahulu.');
            textarea.focus();
            return;
        }

        const targetForm = selectedChecksheetId || defaultFormNo;
        const submitBtn = document.getElementById('btn-submit-comment');
        const origHtml = submitBtn ? submitBtn.innerHTML : '';

        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<svg class="spinner" viewBox="0 0 24 24" width="15" height="15" stroke="currentColor" stroke-width="2" fill="none"><circle cx="12" cy="12" r="10"></circle></svg> Mengirim...';
        }

        try {
            const response = await fetch('{{ route("form-unregistrasi.comments.store") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    form_number: targetForm,
                    comment: commentText
                })
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                alert('Gagal mengirim komentar: ' + (data.message || 'Terjadi kesalahan sistem.'));
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = origHtml;
                }
                return;
            }

            if (!formCommentsMap[targetForm]) {
                formCommentsMap[targetForm] = [];
            }

            const commentObj = {
                id: data.comment.id,
                form_number: data.comment.form_number,
                user_id: data.comment.user_id,
                user_name: data.comment.user_name,
                user_dept: data.comment.user_dept,
                user_role: data.comment.user_role,
                comment: data.comment.comment,
                created_at: formatCommentDate(data.comment.created_at_raw || data.comment.created_at || new Date()),
                can_delete: (userRoleType === 'admin')
            };

            formCommentsMap[targetForm].push(commentObj);
            textarea.value = '';

            renderComments(targetForm);
            showToast('Komentar berhasil dikirim!', 'success');

            const listEl = document.getElementById('form-comments-list');
            if (listEl) listEl.scrollTop = listEl.scrollHeight;

        } catch (err) {
            console.error('Submit comment error:', err);
            alert('Terjadi kesalahan koneksi saat mengirim komentar.');
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = origHtml;
            }
        }
    }

    async function deleteFormComment(commentId) {
        if (!confirm('Apakah Anda yakin ingin menghapus komentar ini?')) return;

        const targetForm = selectedChecksheetId || defaultFormNo;

        try {
            const response = await fetch(`/form-unregistrasi/comments/${commentId}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                alert('Gagal menghapus komentar: ' + (data.message || 'Terjadi kesalahan sistem.'));
                return;
            }

            if (formCommentsMap[targetForm]) {
                formCommentsMap[targetForm] = formCommentsMap[targetForm].filter(c => c.id !== commentId);
            }

            renderComments(targetForm);
            showToast('Komentar berhasil dihapus.', 'success');

        } catch (err) {
            console.error('Delete comment error:', err);
            alert('Terjadi kesalahan koneksi saat menghapus komentar.');
        }
    }

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

    // DOMContentLoaded Initialization
    document.addEventListener('DOMContentLoaded', function() {
        populateDateFilterOptions();
        renderDataViewTable();
        renderApprovalMonitoringTable();

        const urlParams = new URLSearchParams(window.location.search);
        const activeTabParam = urlParams.get('tab') || '{{ request()->query("tab") }}';

        const initialTargetForm = (urlFormParam && checksheets[urlFormParam]) ? urlFormParam : selectedChecksheetId;
        viewChecksheet(initialTargetForm, false);
        initAllSignaturesQRCodes();

        @if($errors->any())
            openModal('addItemModal');
        @endif

        if (activeTabParam === 'proses-approval' || window.location.hash === '#proses-approval') {
            switchSheet('proses-approval');
        } else if (activeTabParam === 'data-view' || window.location.hash === '#data-view') {
            switchSheet('data-view');
        } else if ((activeTabParam === 'account-master' || window.location.hash === '#account-master') && userRoleType === 'admin') {
            switchSheet('account-master');
        }
    });
</script>
@endsection
