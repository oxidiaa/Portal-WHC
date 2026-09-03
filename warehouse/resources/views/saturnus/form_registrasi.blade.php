@extends('layouts.app')

@section('title', 'Form Pendaftaran Barang Consumable')

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
    $accSigDate = $currentApproval?->accounting_signed_at ? \Carbon\Carbon::parse($currentApproval->accounting_signed_at)->format('d-m-Y') : $currentFormDate;
    $whSigDate = $currentApproval?->warehouse_signed_at ? \Carbon\Carbon::parse($currentApproval->warehouse_signed_at)->format('d-m-Y') : $currentFormDate;

    $userSigner = $currentApproval?->user_signer_name ?? $currentFormReqName;
    $staffSigner = $currentApproval?->staff_signer_name ?? 'Staff / Section Head';
    $accSigner = $currentApproval?->accounting_signer_name ?? 'Accounting';
    $whSigner = $currentApproval?->warehouse_signer_name ?? 'Warehouse Consumable';

    $hasUserSig = ($currentApproval && $currentApproval->user_signed_at) || $firstCurrentItem;
    $hasStaffSig = (bool)($currentApproval && ($currentApproval->staff_signed_at || $currentApproval->staff_signer_name));
    $hasAccSig = (bool)($currentApproval && ($currentApproval->accounting_signed_at || $currentApproval->accounting_signer_name));
    $hasWhSig = (bool)($currentApproval && ($currentApproval->warehouse_signed_at || $currentApproval->warehouse_signer_name));
@endphp
<style>
    /* Sheet Tabs & Stepper Enhancements for Form Registrasi */
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
        max-width: 640px !important;
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

    .workspace-light-theme .fi-checkbox-label {
        color: #334155 !important;
        font-weight: 600 !important;
        font-size: 0.85rem !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 0.5rem !important;
        cursor: pointer !important;
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
            padding: 1.5px 3px !important;
            height: 19px !important;
            font-size: 6.8pt !important;
            line-height: 1.1 !important;
        }
        .form-reg-table tbody tr.tr-even td {
            background-color: #fbfbfb !important;
        }
        .form-reg-table tbody td * {
            color: #000000 !important;
        }
        .item-code-badge, .item-price-tag, .badge-stock-min, .badge-stock-titik, .badge-stock-max, .badge-asset-yes, .badge-asset-no {
            background: transparent !important;
            border: none !important;
            padding: 0 !important;
            font-size: 6.8pt !important;
            color: #000000 !important;
            box-shadow: none !important;
            border-radius: 0 !important;
        }
        .badge-asset-yes svg {
            display: none !important;
        }
        .check-icon-b3 svg {
            stroke: #000000 !important;
            width: 10px !important;
            height: 10px !important;
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
        <h1 class="galactic-title" style="font-size: 1.6rem; margin-bottom: 0.2rem;">Form Pendaftaran Barang Consumable</h1>
        <p class="galactic-subtitle">Lembar kerja pendaftaran, monitoring approval 4-tahap, data explorer, dan account master.</p>
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

{{-- ===== SHEET TABS SELECTOR (SEGMENTED CONTROL) ===== --}}
<div class="sheet-tabs-container no-print">
    <button type="button" class="sheet-tab active" data-tab="print-preview" onclick="switchSheet('print-preview')">
        <svg viewBox="0 0 24 24" width="17" height="17" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
            <polyline points="14 2 14 8 20 8"></polyline>
            <line x1="16" y1="13" x2="8" y2="13"></line>
            <line x1="16" y1="17" x2="8" y2="17"></line>
            <polyline points="10 9 9 9 8 9"></polyline>
        </svg>
        <span>Print Preview (Lembar Cetak)</span>
    </button>

    <button type="button" class="sheet-tab" data-tab="proses-approval" onclick="switchSheet('proses-approval')">
        <svg viewBox="0 0 24 24" width="17" height="17" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
            <polyline points="22 4 12 14.01 9 11.01"></polyline>
        </svg>
        <span>Proses Approval</span>
    </button>

    <button type="button" class="sheet-tab" data-tab="data-view" onclick="switchSheet('data-view')">
        <svg viewBox="0 0 24 24" width="17" height="17" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
            <line x1="8" y1="6" x2="21" y2="6"></line>
            <line x1="8" y1="12" x2="21" y2="12"></line>
            <line x1="8" y1="18" x2="21" y2="18"></line>
            <line x1="3" y1="6" x2="3.01" y2="6"></line>
            <line x1="3" y1="12" x2="3.01" y2="12"></line>
            <line x1="3" y1="18" x2="3.01" y2="18"></line>
        </svg>
        <span>Data View Explorer</span>
    </button>

    @if(in_array(strtoupper(Auth::user()->role ?? ''), ['MASTER', 'ADMIN']))
    <button type="button" class="sheet-tab" data-tab="account-master" onclick="switchSheet('account-master')">
        <svg viewBox="0 0 24 24" width="17" height="17" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
            <circle cx="9" cy="7" r="4"></circle>
            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
        </svg>
        <span>Account Master</span>
    </button>
    @endif
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
                <h2 class="form-reg-title">FORM PENDAFTARAN BARANG CONSUMABLE</h2>
                <p class="form-reg-nodoc" id="preview-docno">No Doc : W1-CDS-PP-20/F1 Rev 2 &nbsp;|&nbsp; No. Form: <span id="form-number-display" style="font-weight: 700; color: var(--color-primary);">{{ $currentFormNo }}</span></p>
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

        {{-- ===== TABLE ===== --}}
        <div class="form-reg-table-wrap">
            <table class="form-reg-table">
                <thead>
                    <tr>
                        <th rowspan="2" class="th-center" style="width:3.5%;">NO.</th>
                        <th rowspan="2" class="th-center" style="width:13%;">KODE BARANG</th>
                        <th rowspan="2" class="th-center" style="width:19%;">NAMA BARANG</th>
                        <th rowspan="2" class="th-center" style="width:9%;">HARGA</th>
                        <th rowspan="2" class="th-center" style="width:8%;">ESTIMASI USIA PAKAI</th>
                        <th rowspan="2" class="th-center" style="width:8.5%;">KATEGORI PENGGUNAAN</th>
                        <th rowspan="2" class="th-center" style="width:8%;">KATEGORI UKURAN</th>
                        <th rowspan="2" class="th-center" style="width:4.5%;">MIN</th>
                        <th rowspan="2" class="th-center" style="width:5.5%;">TITIK ORDER</th>
                        <th rowspan="2" class="th-center" style="width:4.5%;">MAX</th>
                        <th rowspan="2" class="th-center" style="width:6%;">LEAD TIME</th>
                        <th rowspan="2" class="th-center" style="width:8%;">ASET / NO ASET</th>
                        <th colspan="2" class="th-center" style="width:10%;">KATEGORI</th>
                    </tr>
                    <tr>
                        <th class="th-center" style="width:4.5%;">B3</th>
                        <th class="th-center" style="width:5.5%;">NON B3</th>
                    </tr>
                </thead>
                <tbody id="preview-table-body">
                    @forelse($currentFormItems->values() as $index => $item)
                    <tr class="data-row {{ $index % 2 != 0 ? 'tr-even' : '' }}">
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
                        <td class="td-center">
                            @if($item->harga)
                                <span class="item-price-tag">Rp {{ number_format($item->harga, 0, ',', '.') }}</span>
                            @else
                                <span style="color:#94a3b8; font-size:0.75rem;">-</span>
                            @endif
                        </td>
                        <td class="td-center" style="font-size:0.75rem; font-weight:600; color:#334155;">
                            {{ $item->estimasi_usia_pakai ? (is_numeric(trim($item->estimasi_usia_pakai)) ? $item->estimasi_usia_pakai . ' Hari' : $item->estimasi_usia_pakai) : '-' }}
                        </td>
                        <td class="td-center" style="font-size:0.75rem; font-weight:600; color:#0f172a;">{{ $item->kategori_penggunaan ?? '-' }}</td>
                        <td class="td-center" style="font-size:0.75rem; font-weight:600; color:#0f172a;">{{ $item->kategori_ukuran ?? '-' }}</td>
                        <td class="td-center">
                            @if($item->min !== null && $item->min !== '')
                                <span class="badge-stock-min">{{ $item->min }}</span>
                            @else
                                <span style="color:var(--text-muted); font-size:0.75rem;">-</span>
                            @endif
                        </td>
                        <td class="td-center">
                            @if($item->titik_order !== null && $item->titik_order !== '')
                                <span class="badge-stock-titik">{{ $item->titik_order }}</span>
                            @else
                                <span style="color:var(--text-muted); font-size:0.75rem;">-</span>
                            @endif
                        </td>
                        <td class="td-center">
                            @if($item->max !== null && $item->max !== '')
                                <span class="badge-stock-max">{{ $item->max }}</span>
                            @else
                                <span style="color:var(--text-muted); font-size:0.75rem;">-</span>
                            @endif
                        </td>
                        <td class="td-center" style="font-size:0.75rem; font-weight:600;">
                            {{ $item->lead_time ?? '-' }}
                        </td>
                        <td class="td-center" style="vertical-align: middle;">
                            @if($item->kategori_aset === 'ASET')
                                <span class="badge-asset-yes">
                                    <svg viewBox="0 0 24 24" width="11" height="11" stroke="currentColor" stroke-width="3" fill="none"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                    ASET
                                </span>
                            @else
                                <span class="badge-asset-no">NO ASET</span>
                            @endif
                        </td>
                        <td class="td-center">
                            @if($item->is_b3)
                                <div class="check-icon-b3" title="Bahan Berbahaya Beracun">
                                    <svg viewBox="0 0 24 24" width="13" height="13" stroke="currentColor" stroke-width="3" fill="none"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                </div>
                            @endif
                        </td>
                        <td class="td-center">
                            @if($item->is_non_b3)
                                <div class="check-icon-b3" title="NON B3">
                                    <svg viewBox="0 0 24 24" width="13" height="13" stroke="currentColor" stroke-width="3" fill="none"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                </div>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <!-- Web View Empty State -->
                    <tr class="empty-state-row no-print">
                        <td colspan="14" style="padding: 0; border: none;">
                            <div class="empty-state-wrapper">
                                <div class="empty-state-card">
                                    <div class="empty-state-icon-container">
                                        <svg viewBox="0 0 24 24" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6">
                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                            <polyline points="14 2 14 8 20 8"></polyline>
                                            <line x1="12" y1="18" x2="12" y2="12"></line>
                                            <line x1="9" y1="15" x2="15" y2="15"></line>
                                        </svg>
                                        <div class="empty-state-pulse"></div>
                                    </div>
                                    <div>
                                        <h4 class="empty-state-title">Belum Ada Data Barang Consumable</h4>
                                        <p class="empty-state-desc">Formulir pendaftaran ini masih kosong. Klik tombol di bawah atau gunakan tombol <strong>Tambah Data</strong> untuk mengisi lembar barang.</p>
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
                                    <div class="empty-state-pills">
                                        <div class="empty-state-pill">
                                            <svg viewBox="0 0 24 24" stroke="var(--color-success)" stroke-width="2.5" fill="none"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                            Kategori B3 / Non-B3
                                        </div>
                                        <div class="empty-state-pill">
                                            <svg viewBox="0 0 24 24" stroke="var(--color-primary)" stroke-width="2.5" fill="none"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path></svg>
                                            No. Form Otomatis
                                        </div>
                                        <div class="empty-state-pill">
                                            <svg viewBox="0 0 24 24" stroke="var(--color-warning)" stroke-width="2.5" fill="none"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                                            Approval Real-time
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- ===== SIGNATURE ===== --}}
        <div class="form-reg-signature" style="grid-template-columns: repeat(4, 1fr);">
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
                <div class="sig-label">Approved Accounting</div>
                <div class="sig-space" id="preview-sig-accounting">
                    @if($hasAccSig)
                        <div style="color: var(--color-success); font-weight: 700; font-size: 0.72rem; margin-bottom: 0.15rem;">✓ APPROVED ACCOUNTING</div>
                        <div class="sig-qrcode" id="blade-sig-qrcode-accounting" data-val="{{ $accSigner }} (Tgl: {{ $accSigDate }})"></div>
                        <div style="font-size: 0.65rem; color: var(--text-muted); font-weight: 500;">{{ $accSigner }} (Tgl: {{ $accSigDate }})</div>
                    @else
                        <span style="color: var(--text-muted); font-size: 0.75rem; font-style: italic;">...................</span>
                    @endif
                </div>
                <div class="sig-line"></div>
            </div>
            <div class="sig-box">
                <div class="sig-label">Didaftarkan (Warehouse)</div>
                <div class="sig-space" id="preview-sig-warehouse">
                    @if($hasWhSig)
                        <div style="color: var(--color-success); font-weight: 700; font-size: 0.72rem; margin-bottom: 0.15rem;">✓ REGISTERED WAREHOUSE</div>
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
                        <span>Diskusi & Komentar Form</span>
                        <span class="badge" id="comments-count-badge" style="background: rgba(0, 173, 239, 0.15); color: var(--color-primary); font-size: 0.75rem; padding: 0.2rem 0.55rem; border-radius: 12px;">0 Komentar</span>
                    </h3>
                    <p style="font-size: 0.8rem; color: var(--text-muted); margin: 0.15rem 0 0 0;">
                        Semua role (User, Staff, Accounting, Warehouse, Master) dapat saling memberikan catatan, revisi, atau konfirmasi di sini.
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
                <textarea id="comment-textarea" class="form-control" rows="3" placeholder="Tulis komentar, catatan revisi, atau pertanyaan terkait formulir ini..." style="resize: vertical; min-height: 75px; font-size: 0.875rem; line-height: 1.5; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0.65rem 0.85rem;" required onkeydown="handleCommentKeydown(event)"></textarea>
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
            <div style="background-color: rgba(99, 102, 241, 0.1); color: rgb(79, 70, 229); padding: 0.75rem; border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center;">
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
                <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">Butuh Warehouse</span>
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
                $roleDesc = 'Anda login sebagai <strong>Staff / Section Head Departemen Production / Dies Assy (Tahap 1)</strong>. Berwenang menyetujui formulir yang belum diapprove dari departemen <strong>Production</strong> dan <strong>Dies Assy</strong>.';
            } else {
                $roleDesc = 'Anda login sebagai <strong>Staff / Section Head Departemen ' . e($staffDept) . ' (Tahap 1)</strong>. Hanya berwenang menyetujui formulir yang belum diapprove dari departemen <strong>' . e($staffDept) . '</strong> Anda.';
            }
            $roleColor = '#2563eb';
        } elseif (str_contains($curRole, 'accounting') || str_contains($curRole, 'acc')) {
            $roleDesc = 'Anda login sebagai <strong>Accounting Approver (Tahap 2)</strong>. Hanya dapat menyetujui formulir setelah disetujui oleh Staff / Section Head.';
            $roleColor = '#4f46e5';
        } elseif (str_contains($curRole, 'warehouse')) {
            $roleDesc = 'Anda login sebagai <strong>Warehouse Consumable (Tahap 3)</strong>. Hanya dapat meregistrasi barang setelah formulir disetujui oleh Accounting.';
            $roleColor = '#059669';
        } else {
            $roleDesc = 'Anda login sebagai <strong>User (Pembuat Form)</strong>. Bertugas membuat FORM Baru & mengajukannya ke Staff / Section Head. <em>(Akun User tidak memiliki wewenang approval)</em>.';
            $roleColor = '#d97706';
        }
    @endphp

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

    {{-- Simple & Friendly Approval Container Card --}}
    <div class="glass-card" style="padding: 1.5rem; margin-top: 1.25rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h3 style="font-family: var(--font-heading); font-weight: 700; color: var(--text-primary); margin: 0; font-size: 1.15rem;">
                    Daftar Form & Tahap Approval
                </h3>
                <p style="color: var(--text-muted); font-size: 0.82rem; margin-top: 0.2rem; margin-bottom: 0;">
                    Alur persetujuan: <strong>User (Pembuat)</strong> ➔ <strong>Staff / Section Head</strong> ➔ <strong>Accounting</strong> ➔ <strong>Warehouse Consumable</strong>.
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

        {{-- Clean & Clear Approval Monitoring Table --}}
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
                <span style="font-size: 0.8rem; color: var(--text-muted); font-weight: 500; display: block; text-transform: uppercase; letter-spacing: 0.05em;">Selesai (Approved)</span>
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
            <h3 style="font-family: var(--font-heading); font-weight: 700; color: var(--text-primary); margin: 0;">Daftar Form Registrasi (Print Preview)</h3>
            
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
                    <svg viewBox="0 0 24 24" width="22" height="22" stroke="var(--color-primary)" stroke-width="2.5" fill="none"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
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
                    <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 600; display: block; text-transform: uppercase;">ACCOUNTING & WH</span>
                    <span style="font-size: 1.4rem; font-weight: 800; color: var(--color-success);" id="stat-acc-wh-accounts-count">{{ isset($users) ? $users->filter(fn($u) => in_array(strtolower($u->role), ['accounting', 'warehouse consumable', 'warehouse']))->count() : 0 }} Akun</span>
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
                            @php
                                $rLower = strtolower(trim($u->role ?? ''));
                                if (in_array($rLower, ['master', 'admin'])) {
                                    $roleBadge = '<span style="background-color: rgba(99, 102, 241, 0.15); color: #4f46e5; border: 1px solid rgba(99, 102, 241, 0.3); padding: 0.25rem 0.6rem; border-radius: 4px; font-weight: 700; font-size: 0.75rem;">MASTER</span>';
                                } elseif ($rLower === 'staff') {
                                    $roleBadge = '<span style="background-color: var(--color-warning-light); color: var(--color-warning); padding: 0.25rem 0.6rem; border-radius: 4px; font-weight: 700; font-size: 0.75rem;">Staff</span>';
                                } elseif (str_contains($rLower, 'accounting') || str_contains($rLower, 'acc')) {
                                    $roleBadge = '<span style="background-color: rgba(168, 85, 247, 0.15); color: #9333ea; padding: 0.25rem 0.6rem; border-radius: 4px; font-weight: 700; font-size: 0.75rem;">Accounting</span>';
                                } elseif (str_contains($rLower, 'warehouse')) {
                                    $roleBadge = '<span style="background-color: var(--color-success-light); color: var(--color-success); padding: 0.25rem 0.6rem; border-radius: 4px; font-weight: 700; font-size: 0.75rem;">Warehouse Consumable</span>';
                                } else {
                                    $roleBadge = '<span style="background-color: rgba(59, 130, 246, 0.15); color: rgb(29, 78, 216); padding: 0.25rem 0.6rem; border-radius: 4px; font-weight: 700; font-size: 0.75rem;">User</span>';
                                }
                                $userAccObj = [
                                    'id' => $u->id,
                                    'name' => $u->name,
                                    'username' => $u->email,
                                    'dept' => $u->department ?? 'Production',
                                    'role' => $u->role ?? 'User',
                                    'status' => $u->status ?? 'Aktif',
                                    'date' => $u->created_at ? $u->created_at->format('d-m-Y') : date('d-m-Y')
                                ];
                                $userAccJson = htmlspecialchars(json_encode($userAccObj), ENT_QUOTES, 'UTF-8');
                            @endphp
                            <tr>
                                <td class="td-center td-no">{{ $index + 1 }}</td>
                                <td style="font-weight: 700; color: var(--text-primary); padding: 0 0.8rem;">
                                    {{ $u->name }}
                                    <div style="font-size:0.75rem; color:var(--text-muted); font-weight:normal;">Username / Login ID: <strong>{{ $u->email }}</strong></div>
                                </td>
                                <td style="padding: 0 0.8rem; font-size: 0.85rem; font-weight: 600;">{{ $u->department ?? '-' }}</td>
                                <td class="td-center">{!! $roleBadge !!}</td>
                                <td class="td-center"><span style="background-color: var(--color-success-light); color: var(--color-success); padding: 0.2rem 0.5rem; border-radius: 4px; font-weight: 700; font-size: 0.7rem;">{{ $u->status ?? 'Aktif' }}</span></td>
                                <td class="td-center" style="font-size: 0.85rem;">{{ $u->created_at ? $u->created_at->format('d-m-Y') : '-' }}</td>
                                <td class="td-center" style="padding: 0 0.5rem;">
                                    <button class="btn btn-secondary btn-sm" onclick="openEditUserModal({{ $userAccJson }})" style="padding: 0.25rem 0.55rem; font-size: 0.75rem; border-radius: 4px;">Edit</button>
                                    @if(auth()->id() !== $u->id)
                                    <form action="{{ route('users.delete', $u->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus akun user &quot;{{ $u->name }}&quot;?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm" style="padding: 0.25rem 0.55rem; font-size: 0.75rem; border-radius: 4px; background-color: #ef4444; color: white; border: none; margin-left: 0.2rem;">Hapus</button>
                                    </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 1.5rem; color: var(--text-muted);">Belum ada akun pengguna terdaftar. Klik "+ Buat Akun Baru" untuk menambahkan pengguna.</td>
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
                Persetujuan Form Registrasi
            </h3>
            <button class="btn-close" onclick="closeModal('quickApprovalModal')">&times;</button>
        </div>

        <div id="quick-approval-modal-body">
            {{-- Rendered dynamically via openQuickApprovalModal() --}}
        </div>
    </div>
</div>

{{-- ===== MODAL: TAMBAH DATA ===== --}}
<div class="modal" id="addItemModal">
    <div class="modal-content" style="max-width: 660px; max-height: 90vh; overflow-y: auto; padding: 2.25rem 2.5rem 2rem; border-radius: 24px; border: none; box-shadow: 0 25px 60px rgba(0, 0, 0, 0.35); background: #ffffff;">
        <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 1.15rem; margin-bottom: 1.35rem; border-bottom: 1.5px solid #f1f5f9;">
            <h3 style="font-family: inherit; font-size: 1.3rem; font-weight: 800; color: #0f172a; margin: 0; letter-spacing: -0.01em;">Tambah Data Barang</h3>
            <button class="btn-close" onclick="closeModal('addItemModal')" style="background: transparent; border: none; font-size: 1.35rem; line-height: 1; color: #94a3b8; cursor: pointer; padding: 0.25rem; border-radius: 6px; transition: all 0.2s;">&times;</button>
        </div>

        <form action="{{ route('form-registrasi.store') }}" method="POST">
            @csrf
            <input type="hidden" name="form_number" id="modal_form_number" value="{{ $currentFormNo }}">

            {{-- Row 1: Kode & Nama --}}
            <div style="display: grid; grid-template-columns: 1fr 1.8fr; gap: 1.25rem; margin-bottom: 1.25rem;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="fi_kode" style="display: block; font-size: 0.78rem; font-weight: 800; color: #1e293b; margin-bottom: 0.45rem;">Kode Barang <span style="color: #ef4444; margin-left: 2px;">*</span></label>
                    <input type="text" id="fi_kode" name="kode_barang" class="form-control @error('kode_barang') is-invalid @enderror" placeholder="Cth: SBM-001" value="{{ old('kode_barang') }}" required oninput="checkRegistrasiKodeBarang(this.value)" onblur="checkRegistrasiKodeBarang(this.value, true)" style="height: 46px; border-radius: 12px; border: 1.5px solid #cbd5e1; padding: 0 1rem; font-size: 0.88rem; background: #ffffff; color: #0f172a; width: 100%;">
                    <div id="fi_kode_alert_box" style="display: none; margin-top: 0.35rem; font-size: 0.78rem; font-weight: 600; padding: 0.4rem 0.65rem; border-radius: 6px;"></div>
                    @error('kode_barang')<div class="error-text" style="color: #ef4444; font-size: 0.75rem; margin-top: 0.25rem;">{{ $message }}</div>@enderror
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="fi_nama" style="display: block; font-size: 0.78rem; font-weight: 800; color: #1e293b; margin-bottom: 0.45rem;">Nama Barang <span style="color: #ef4444; margin-left: 2px;">*</span></label>
                    <input type="text" id="fi_nama" name="nama_barang" class="form-control @error('nama_barang') is-invalid @enderror" placeholder="Nama barang" value="{{ old('nama_barang') }}" required style="height: 46px; border-radius: 12px; border: 1.5px solid #cbd5e1; padding: 0 1rem; font-size: 0.88rem; background: #ffffff; color: #0f172a; width: 100%;">
                    @error('nama_barang')<div class="error-text" style="color: #ef4444; font-size: 0.75rem; margin-top: 0.25rem;">{{ $message }}</div>@enderror
                </div>
            </div>

            {{-- Row 2: Harga & Estimasi Usia --}}
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; margin-bottom: 1.25rem;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="fi_harga" style="display: block; font-size: 0.78rem; font-weight: 800; color: #1e293b; margin-bottom: 0.45rem;">Harga (Rp) <span style="color: #ef4444; margin-left: 2px;">*</span></label>
                    <input type="number" id="fi_harga" name="harga" class="form-control @error('harga') is-invalid @enderror" placeholder="0" min="0" value="{{ old('harga') }}" required style="height: 46px; border-radius: 12px; border: 1.5px solid #cbd5e1; padding: 0 1rem; font-size: 0.88rem; background: #ffffff; color: #0f172a; width: 100%;">
                    @error('harga')<div class="error-text" style="color: #ef4444; font-size: 0.75rem; margin-top: 0.25rem;">{{ $message }}</div>@enderror
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="fi_usia" style="display: block; font-size: 0.78rem; font-weight: 800; color: #1e293b; margin-bottom: 0.45rem;">Estimasi Usia Pakai <span style="color: #ef4444; margin-left: 2px;">*</span></label>
                    <input type="text" id="fi_usia" name="estimasi_usia_pakai" class="form-control @error('estimasi_usia_pakai') is-invalid @enderror" placeholder="Cth: 730 Hari, 6 Bulan, 1 Tahun" value="{{ old('estimasi_usia_pakai') }}" required style="height: 46px; border-radius: 12px; border: 1.5px solid #cbd5e1; padding: 0 1rem; font-size: 0.88rem; background: #ffffff; color: #0f172a; width: 100%;">
                    @error('estimasi_usia_pakai')<div class="error-text" style="color: #ef4444; font-size: 0.75rem; margin-top: 0.25rem;">{{ $message }}</div>@enderror
                </div>
            </div>

            {{-- Row 3: Kategori Penggunaan & Ukuran --}}
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; margin-bottom: 1.25rem;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="fi_katpenggunaan" style="display: block; font-size: 0.78rem; font-weight: 800; color: #1e293b; margin-bottom: 0.45rem;">Kategori Penggunaan <span style="color: #ef4444; margin-left: 2px;">*</span></label>
                    <input type="text" id="fi_katpenggunaan" name="kategori_penggunaan" class="form-control @error('kategori_penggunaan') is-invalid @enderror" placeholder="Cth: Produksi, Consumable" value="{{ old('kategori_penggunaan') }}" required style="height: 46px; border-radius: 12px; border: 1.5px solid #cbd5e1; padding: 0 1rem; font-size: 0.88rem; background: #ffffff; color: #0f172a; width: 100%;">
                    @error('kategori_penggunaan')<div class="error-text" style="color: #ef4444; font-size: 0.75rem; margin-top: 0.25rem;">{{ $message }}</div>@enderror
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="fi_katukuran" style="display: block; font-size: 0.78rem; font-weight: 800; color: #1e293b; margin-bottom: 0.45rem;">Kategori Ukuran <span style="color: #ef4444; margin-left: 2px;">*</span></label>
                    <input type="text" id="fi_katukuran" name="kategori_ukuran" class="form-control @error('kategori_ukuran') is-invalid @enderror" placeholder="Cth: Kecil, Sedang, Besar" value="{{ old('kategori_ukuran') }}" required style="height: 46px; border-radius: 12px; border: 1.5px solid #cbd5e1; padding: 0 1rem; font-size: 0.88rem; background: #ffffff; color: #0f172a; width: 100%;">
                    @error('kategori_ukuran')<div class="error-text" style="color: #ef4444; font-size: 0.75rem; margin-top: 0.25rem;">{{ $message }}</div>@enderror
                </div>
            </div>

            {{-- Row 4: Min, Titik Order, Max, Lead Time --}}
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 1.25rem; margin-bottom: 1.25rem;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="fi_min" style="display: block; font-size: 0.78rem; font-weight: 800; color: #1e293b; margin-bottom: 0.45rem;">Min <span style="color: #ef4444; margin-left: 2px;">*</span></label>
                    <input type="number" id="fi_min" name="min" class="form-control @error('min') is-invalid @enderror" placeholder="0" min="0" value="{{ old('min') }}" required style="height: 46px; border-radius: 12px; border: 1.5px solid #cbd5e1; padding: 0 1rem; font-size: 0.88rem; background: #ffffff; color: #0f172a; width: 100%;">
                    @error('min')<div class="error-text" style="color: #ef4444; font-size: 0.75rem; margin-top: 0.25rem;">{{ $message }}</div>@enderror
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="fi_titik" style="display: block; font-size: 0.78rem; font-weight: 800; color: #1e293b; margin-bottom: 0.45rem;">Titik Order <span style="color: #ef4444; margin-left: 2px;">*</span></label>
                    <input type="number" id="fi_titik" name="titik_order" class="form-control @error('titik_order') is-invalid @enderror" placeholder="0" min="0" value="{{ old('titik_order') }}" required style="height: 46px; border-radius: 12px; border: 1.5px solid #cbd5e1; padding: 0 1rem; font-size: 0.88rem; background: #ffffff; color: #0f172a; width: 100%;">
                    @error('titik_order')<div class="error-text" style="color: #ef4444; font-size: 0.75rem; margin-top: 0.25rem;">{{ $message }}</div>@enderror
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="fi_max" style="display: block; font-size: 0.78rem; font-weight: 800; color: #1e293b; margin-bottom: 0.45rem;">Max <span style="color: #ef4444; margin-left: 2px;">*</span></label>
                    <input type="number" id="fi_max" name="max" class="form-control @error('max') is-invalid @enderror" placeholder="0" min="0" value="{{ old('max') }}" required style="height: 46px; border-radius: 12px; border: 1.5px solid #cbd5e1; padding: 0 1rem; font-size: 0.88rem; background: #ffffff; color: #0f172a; width: 100%;">
                    @error('max')<div class="error-text" style="color: #ef4444; font-size: 0.75rem; margin-top: 0.25rem;">{{ $message }}</div>@enderror
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="fi_lead" style="display: block; font-size: 0.78rem; font-weight: 800; color: #1e293b; margin-bottom: 0.45rem;">Lead Time <span style="color: #ef4444; margin-left: 2px;">*</span></label>
                    <input type="text" id="fi_lead" name="lead_time" class="form-control @error('lead_time') is-invalid @enderror" placeholder="Cth: 3 Hari" value="{{ old('lead_time') }}" required style="height: 46px; border-radius: 12px; border: 1.5px solid #cbd5e1; padding: 0 1rem; font-size: 0.88rem; background: #ffffff; color: #0f172a; width: 100%;">
                    @error('lead_time')<div class="error-text" style="color: #ef4444; font-size: 0.75rem; margin-top: 0.25rem;">{{ $message }}</div>@enderror
                </div>
            </div>

            {{-- Row 5: Kategori B3 / NON B3 --}}
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-size: 0.78rem; font-weight: 800; color: #1e293b; margin-bottom: 0.5rem;">Kategori <span style="color: #ef4444; margin-left: 2px;">*</span></label>
                <div style="display: flex; gap: 2.25rem; align-items: center;">
                    <label class="fi-checkbox-label" style="display: inline-flex; align-items: center; gap: 0.5rem; cursor: pointer; font-size: 0.88rem; font-weight: 600; color: #1e293b;">
                        <input type="checkbox" name="is_b3" id="modal_is_b3" value="1" class="fi-checkbox" {{ old('is_b3') ? 'checked' : '' }} onchange="handleB3CategorySelect('b3')" style="width: 17px; height: 17px; accent-color: #00adef; cursor: pointer;">
                        <span>B3 (Bahan Berbahaya Beracun)</span>
                    </label>
                    <label class="fi-checkbox-label" style="display: inline-flex; align-items: center; gap: 0.5rem; cursor: pointer; font-size: 0.88rem; font-weight: 600; color: #1e293b;">
                        <input type="checkbox" name="is_non_b3" id="modal_is_non_b3" value="1" class="fi-checkbox" {{ old('is_non_b3') ? 'checked' : '' }} onchange="handleB3CategorySelect('non_b3')" style="width: 17px; height: 17px; accent-color: #00adef; cursor: pointer;">
                        <span>NON B3</span>
                    </label>
                </div>
                @error('kategori')<div class="error-text" style="margin-top: 0.35rem; color: #ef4444; font-size: 0.75rem;">{{ $message }}</div>@enderror
            </div>

            <div style="display: flex; gap: 0.85rem; justify-content: flex-end; align-items: center; margin-top: 1.75rem;">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addItemModal')" style="background: #ffffff; border: 1.5px solid #cbd5e1; border-radius: 12px; padding: 0.65rem 1.6rem; font-weight: 700; font-size: 0.88rem; color: #1e293b; cursor: pointer; transition: all 0.2s ease;">Batal</button>
                <button type="submit" class="btn btn-primary" style="background: #0084ff; border: none; border-radius: 12px; padding: 0.65rem 1.6rem; font-weight: 700; font-size: 0.88rem; color: #ffffff; cursor: pointer; display: inline-flex; align-items: center; gap: 0.5rem; box-shadow: 0 4px 16px rgba(0, 132, 255, 0.4); transition: all 0.2s ease;">
                    <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                        <polyline points="17 21 17 13 7 13 7 21"></polyline>
                        <polyline points="7 3 7 8 15 8"></polyline>
                    </svg>
                    <span>Simpan Data</span>
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ===== MODAL: KONFIRMASI TAMBAH ITEM LAGI ===== --}}
<div class="modal" id="addMorePromptModal">
    <div class="modal-content" style="max-width: 480px; text-align: center; padding: 2.25rem 1.75rem; border-radius: var(--radius-lg);">
        <div style="width: 68px; height: 68px; border-radius: 50%; background: var(--color-success-light); color: var(--color-success); display: flex; align-items: center; justify-content: center; margin: 0 auto 1.25rem; box-shadow: 0 0 20px rgba(16, 185, 129, 0.2);">
            <svg viewBox="0 0 24 24" width="36" height="36" stroke="currentColor" stroke-width="2.5" fill="none">
                <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
        </div>
        <h3 style="font-family: var(--font-heading); font-weight: 700; color: var(--text-primary); margin-bottom: 0.5rem; font-size: 1.3rem;">
            Data Barang Berhasil Disimpan!
        </h3>
        <p style="color: var(--text-muted); font-size: 0.9rem; line-height: 1.5; margin-bottom: 1.75rem;">
            Apakah Anda ingin menambahkan item barang consumable lainnya ke dalam form ini?
        </p>
        <div style="display: flex; gap: 0.75rem; justify-content: center;">
            <button type="button" class="btn btn-secondary" onclick="closeModal('addMorePromptModal')" style="padding: 0.65rem 1.25rem; font-weight: 600; min-width: 120px;">
                Tidak, Selesai
            </button>
            <button type="button" class="btn btn-primary" onclick="closeModal('addMorePromptModal'); openModal('addItemModal');" style="padding: 0.65rem 1.25rem; font-weight: 700; display: inline-flex; align-items: center; gap: 0.4rem; min-width: 170px;">
                <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2.5" fill="none"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                Ya, Tambah Item Lagi
            </button>
        </div>
    </div>
</div>

</div> {{-- End .workspace-light-theme --}}

@endsection

@section('scripts')
<script>
    function handleB3CategorySelect(selected) {
        const b3Cb = document.getElementById('modal_is_b3');
        const nonB3Cb = document.getElementById('modal_is_non_b3');
        if (!b3Cb || !nonB3Cb) return;

        if (selected === 'b3' && b3Cb.checked) {
            nonB3Cb.checked = false;
        } else if (selected === 'non_b3' && nonB3Cb.checked) {
            b3Cb.checked = false;
        }
    }

    function openModal(id) {
        if (id === 'addItemModal') {
            const hiddenFormNo = document.getElementById('modal_form_number');
            if (hiddenFormNo) {
                hiddenFormNo.value = selectedChecksheetId || activeFormNo;
            }
        }
        const el = document.getElementById(id);
        if (el) {
            el.classList.add('show');
        }
    }

    function closeModal(id) {
        const el = document.getElementById(id);
        if (el) {
            el.classList.remove('show');
        }
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

    // Auto-open prompt modal konfirmasi tambah item lagi setelah simpan
    @if(session('show_add_more_prompt'))
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                openModal('addMorePromptModal');
            }, 300);
        });
    @endif

    // Auto-open modal jika ada validation error dari form tambah data
    @if($errors->any())
        document.addEventListener('DOMContentLoaded', function() {
            openModal('addItemModal');
        });
    @endif

    // TAB SYSTEM & MULTI-FORM ENGINE
    const serverFormItems = @json($formItems, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    const serverFormApprovals = @json($formApprovals ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    const serverFormComments = @json($formComments ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    const allRegisteredCodes = @json($allRegisteredCodes ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    const allUnregisteredCodes = @json($allUnregisteredCodes ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    function checkRegistrasiKodeBarang(val, showAlert = false) {
        const code = (val || '').trim().toUpperCase();
        const alertBox = document.getElementById('fi_kode_alert_box');
        if (!alertBox) return;

        if (!code) {
            alertBox.style.display = 'none';
            alertBox.innerHTML = '';
            return;
        }

        // Check if discontinued in Unregistrasi
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

        // Check if already registered
        const regMatch = allRegisteredCodes.find(i => (i.kode_barang || '').trim().toUpperCase() === code);
        if (regMatch) {
            alertBox.style.display = 'block';
            alertBox.style.background = 'rgba(245, 158, 11, 0.12)';
            alertBox.style.border = '1px solid rgba(245, 158, 11, 0.3)';
            alertBox.style.color = '#b45309';
            alertBox.innerHTML = `⚠️ <strong>PERINGATAN:</strong> Item dengan Kode Barang <strong>${escapeHtml(code)}</strong> (${escapeHtml(regMatch.nama_barang || '')}) telah didaftarkan sebelumnya pada <strong>Form ${escapeHtml(regMatch.form_number || '')}</strong>!`;
            if (showAlert) {
                alert(`Peringatan: Item dengan Kode Barang "${code}" (${regMatch.nama_barang || ''}) telah didaftarkan sebelumnya pada Form ${regMatch.form_number || ''}!`);
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

    // Current Authenticated User & Strict Role Definition
    const authRoleRaw = '{{ strtolower(trim(Auth::user()->role ?? "User")) }}';
    const authUserName = '{{ Auth::user()->name ?? "User" }}';
    const authUserDept = '{{ Auth::user()->department ?? "Production" }}';
    const userDept = (authUserDept || userTag || 'PRODUCTION').trim().toUpperCase();
    let userRoleType = 'user';
    if (authRoleRaw.includes('master') || authRoleRaw.includes('admin')) {
        userRoleType = 'admin';
    } else if (authRoleRaw.includes('warehouse')) {
        userRoleType = 'warehouse';
    } else if (authRoleRaw.includes('accounting') || authRoleRaw.includes('acc')) {
        userRoleType = 'accounting';
    } else if (authRoleRaw.includes('staff')) {
        userRoleType = 'staff';
    } else {
        userRoleType = 'user';
    }

    const canViewAllDepartments = (userRoleType === 'admin' || userRoleType === 'accounting' || userRoleType === 'warehouse');
    
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
        return [userDept];
    }

    function isDeptAllowed(dept) {
        if (canViewAllDepartments) return true;
        if (!dept) return false;
        const dUpper = dept.trim().toUpperCase();
        const allowed = getUserAllowedDepartments();
        return allowed.includes(dUpper) || allowed.some(a => dUpper.includes(a) || a.includes(dUpper));
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
        <tr class="empty-state-row no-print">
            <td colspan="15" style="padding: 0; border: none;">
                <div class="empty-state-wrapper">
                    <div class="empty-state-card">
                        <div class="empty-state-icon-container">
                            <svg viewBox="0 0 24 24" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                                <line x1="12" y1="18" x2="12" y2="12"></line>
                                <line x1="9" y1="15" x2="15" y2="15"></line>
                            </svg>
                            <div class="empty-state-pulse"></div>
                        </div>
                        <div>
                            <h4 class="empty-state-title">Belum Ada Data Barang Consumable</h4>
                            <p class="empty-state-desc">Formulir pendaftaran ini masih kosong. Klik tombol di bawah atau gunakan tombol <strong>Tambah Data</strong> untuk mengisi lembar barang.</p>
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
                        <div class="empty-state-pills">
                            <div class="empty-state-pill">
                                <svg viewBox="0 0 24 24" stroke="var(--color-success)" stroke-width="2.5" fill="none"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                Kategori B3 / Non-B3
                            </div>
                            <div class="empty-state-pill">
                                <svg viewBox="0 0 24 24" stroke="var(--color-primary)" stroke-width="2.5" fill="none"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path></svg>
                                No. Form Otomatis
                            </div>
                            <div class="empty-state-pill">
                                <svg viewBox="0 0 24 24" stroke="var(--color-warning)" stroke-width="2.5" fill="none"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                                Approval Real-time
                            </div>
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

    const checksheets = {};
    const stepperData = {};

    // Collect all active form numbers
    const allKnownFormNumbers = [...new Set([
        ...existingForms,
        ...(existingForms.length === 0 ? [defaultFormNo] : [])
    ])];

    allKnownFormNumbers.forEach(fNo => {
        const parts = fNo.split('/');
        const csDept = parts.length >= 2 ? parts[1] : userTag;
        
        // Find existing DB approval record
        const dbAppr = serverFormApprovals.find(a => a.form_number === fNo) || null;
        
        // Find first item for creator details
        const firstItem = serverFormItems.find(i => i.form_number === fNo) || null;
        
        const reqName = (dbAppr && dbAppr.requestor_name) 
            || (firstItem && firstItem.created_by_name) 
            || (firstItem && firstItem.user ? firstItem.user.name : null) 
            || '{{ Auth::user()->name ?? "User" }}';
            
        const reqDept = (dbAppr && dbAppr.requestor_dept) 
            || (firstItem && firstItem.created_by_dept) 
            || (firstItem && firstItem.user ? firstItem.user.department : null) 
            || csDept;
            
        const formDateStr = (dbAppr && dbAppr.form_date) 
            || (firstItem && firstItem.created_at ? formatApprovalDateStr(firstItem.created_at) : '{{ date("d-m-Y") }}');

        const requestorStr = `${reqName} / ${reqDept}`;

        // Accurate persistent status determination matching the 4 exact stages
        const staffDone = !!(dbAppr && (dbAppr.staff_signed_at || dbAppr.staff_signer_name));
        const accDone = !!(dbAppr && (dbAppr.accounting_signed_at || dbAppr.accounting_signer_name));
        const whDone = !!(dbAppr && (dbAppr.warehouse_signed_at || dbAppr.warehouse_signer_name));

        let statusText = 'Butuh Approval Staff / Section Head';
        let statusClass = 'badge-warning';

        if (whDone || (dbAppr && (dbAppr.status === 'Item Telah didaftarkan' || dbAppr.status === 'TELAH DIDAFTARKAN' || dbAppr.status === 'Item Telah Didaftarkan' || dbAppr.status === 'Selesai'))) {
            statusText = 'Item Telah didaftarkan';
            statusClass = 'badge-success';
        } else if (accDone || (dbAppr && (dbAppr.status === 'Butuh Approval Warehouse Consumable' || dbAppr.status === 'REGISTRASI WAREHOUSE' || dbAppr.status === 'Pending Warehouse'))) {
            statusText = 'Butuh Approval Warehouse Consumable';
            statusClass = 'badge-info';
        } else if (staffDone || (dbAppr && (dbAppr.status === 'Butuh Approval Accounting' || dbAppr.status === 'Butuh Approval Accouting' || dbAppr.status === 'APPROVAL ACCOUNTING' || dbAppr.status === 'Pending Accounting'))) {
            statusText = 'Butuh Approval Accounting';
            statusClass = 'badge-primary';
        } else {
            statusText = 'Butuh Approval Staff / Section Head';
            statusClass = 'badge-warning';
        }

        const userSigDate = (dbAppr && dbAppr.user_signed_at) ? formatApprovalDateStr(dbAppr.user_signed_at) : formDateStr;
        const staffSigDate = (dbAppr && dbAppr.staff_signed_at) ? formatApprovalDateStr(dbAppr.staff_signed_at) : formDateStr;
        const accSigDate = (dbAppr && dbAppr.accounting_signed_at) ? formatApprovalDateStr(dbAppr.accounting_signed_at) : formDateStr;
        const whSigDate = (dbAppr && dbAppr.warehouse_signed_at) ? formatApprovalDateStr(dbAppr.warehouse_signed_at) : formDateStr;

        const staffSigner = (dbAppr && dbAppr.staff_signer_name) ? dbAppr.staff_signer_name : 'Staff / Section Head';
        const accSigner = (dbAppr && dbAppr.accounting_signer_name) ? dbAppr.accounting_signer_name : 'Accounting Approver';
        const whSigner = (dbAppr && dbAppr.warehouse_signer_name) ? dbAppr.warehouse_signer_name : 'Warehouse Consumable';

        checksheets[fNo] = {
            docNo: 'No Doc : W1-CDS-PP-20/F1 Rev 2 &nbsp;|&nbsp; No. Form: <span style="font-weight:700; color:var(--color-primary);">' + fNo + '</span>',
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
                accounting: accDone ? `${accSigner} (Tgl: ${accSigDate})` : '...................',
                warehouse: whDone ? `${whSigner} (Tgl: ${whSigDate})` : '...................'
            },
            comments: {
                user: (dbAppr && dbAppr.user_comment) || 'Formulir pendaftaran barang consumable diajukan.',
                staff: (dbAppr && dbAppr.staff_comment) || '',
                accounting: (dbAppr && dbAppr.accounting_comment) || '',
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
                    role: 'accounting',
                    title: '3. Approval Accounting',
                    completed: accDone,
                    active: staffDone && !accDone,
                    details: accDone ? `${accSigner} (Accounting - Tanggal: ${accSigDate})` : (staffDone ? 'Menunggu persetujuan Accounting...' : 'Menunggu Approval Staff / Section Head...'),
                    status: accDone ? 'Disetujui oleh Accounting.' : 'Butuh Approval Accounting.',
                    color: accDone ? 'var(--color-success)' : (staffDone && !accDone ? 'var(--color-primary)' : 'var(--text-muted)')
                },
                {
                    role: 'warehouse',
                    title: '4. Registrasi Warehouse Consumable',
                    completed: whDone,
                    active: accDone && !whDone,
                    details: whDone ? `${whSigner} (Warehouse - Tanggal: ${whSigDate})` : (accDone ? 'Menunggu registrasi oleh Warehouse...' : 'Menunggu Approval Accounting...'),
                    status: whDone ? 'Item Telah didaftarkan.' : 'Butuh Approval Warehouse Consumable.',
                    color: whDone ? 'var(--color-success)' : (accDone && !whDone ? 'var(--color-primary)' : 'var(--text-muted)')
                }
            ]
        };
    });

    // Populate database items into checksheets
    serverFormItems.forEach(item => {
        const fNo = item.form_number || defaultFormNo;
        if (checksheets[fNo]) {
            checksheets[fNo].items.push({
                no: checksheets[fNo].items.length + 1,
                kode: item.kode_barang || '-',
                nama: item.nama_barang,
                harga: item.harga ? 'Rp ' + Number(item.harga).toLocaleString('id-ID') : '-',
                usia: item.estimasi_usia_pakai ? (!isNaN(Number(item.estimasi_usia_pakai)) ? item.estimasi_usia_pakai + ' Hari' : item.estimasi_usia_pakai) : '-',
                katPeng: item.kategori_penggunaan || '-',
                katUk: item.kategori_ukuran || '-',
                min: item.min || '-',
                titik: item.titik_order || '-',
                max: item.max || '-',
                lead: item.lead_time || '-',
                aset: item.kategori_aset || 'NO ASET',
                b3: item.is_b3,
                non_b3: item.is_non_b3
            });
        }
    });

    const monthNamesIndo = {
        '01': 'Januari', '02': 'Februari', '03': 'Maret', '04': 'April',
        '05': 'Mei', '06': 'Juni', '07': 'Juli', '08': 'Agustus',
        '09': 'September', '10': 'Oktober', '11': 'November', '12': 'Desember'
    };

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

    function updateApprovalSelect() {
        const select = document.getElementById('select-approval-cs');
        if (!select) return;

        select.innerHTML = '';
        for (const formNo in checksheets) {
            const cs = checksheets[formNo];
            const data = stepperData[formNo];
            const isCompleted = (cs.status === 'Selesai' || (data && data.statusText === 'TELAH DIDAFTARKAN') || (data && data.steps && data.steps[3] && data.steps[3].completed));

            // Completed / already-registered forms do not appear in the approval workflow selector
            if (isCompleted && formNo !== selectedChecksheetId) {
                continue;
            }

            const formDept = getCsDepartment(cs, formNo);

            // Restricted roles (User, Staff) only see their allowed department's forms in the selector
            if (!canViewAllDepartments && !isDeptAllowed(formDept)) {
                continue;
            }

            if ((cs.items && cs.items.length > 0) || formNo === selectedChecksheetId) {
                const opt = document.createElement('option');
                opt.value = formNo;
                const itemLabel = (cs.items && cs.items.length > 0) ? `${cs.items.length} Item` : 'Draft Baru';
                const parts = formNo.split('/');
                const csDept = parts.length >= 2 ? parts[1] : userTag;
                opt.innerText = `${formNo} (${csDept} - ${itemLabel})`;
                if (formNo === selectedChecksheetId) {
                    opt.selected = true;
                }
                select.appendChild(opt);
            }
        }
    }

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

            // Filter out empty checksheets (0 items)
            if (!cs.items || cs.items.length === 0) {
                continue;
            }

            const formDept = getCsDepartment(cs, formNo);

            // RULE: Role User and Staff only see forms of their allowed department(s)
            if (!canViewAllDepartments && !isDeptAllowed(formDept)) {
                continue;
            }

            // Extract month and year from cs.date or cs.formNo
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

            // Month and Year filtering
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

            if (cs.status === 'Item Telah didaftarkan' || cs.status === 'TELAH DIDAFTARKAN' || cs.status === 'Selesai' || cs.status === 'Approved') {
                displayStatus = 'Item Telah didaftarkan';
                statusBg = 'rgba(16, 185, 129, 0.15)';
                statusColor = '#059669';
                approvedChecksheets++;
            } else if (cs.status === 'Butuh Approval Warehouse Consumable' || cs.status === 'REGISTRASI WAREHOUSE' || cs.status === 'Pending Warehouse') {
                displayStatus = 'Butuh Approval Warehouse Consumable';
                statusBg = 'rgba(147, 51, 234, 0.12)';
                statusColor = '#7c3aed';
                processChecksheets++;
            } else if (cs.status === 'Butuh Approval Accounting' || cs.status === 'Butuh Approval Accouting' || cs.status === 'APPROVAL ACCOUNTING' || cs.status === 'Pending Accounting') {
                displayStatus = 'Butuh Approval Accounting';
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
                                <h4 class="empty-state-title" style="font-size: 1rem;">${isFiltered ? 'Tidak Ada Form Registrasi pada Periode Ini' : 'Belum Ada Form Registrasi Berisi Data'}</h4>
                                <p class="empty-state-desc" style="font-size: 0.8rem;">${isFiltered ? 'Tidak ditemukan data formulir pendaftaran barang untuk filter Bulan / Tahun yang dipilih.' : 'Formulir registrasi akan tampil secara otomatis di sini setelah Anda menambahkan item barang.'}</p>
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

        if (!confirm(`Apakah Anda yakin ingin menghapus PERMANEN seluruh Form Registrasi "${formNo}" ini?`)) {
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

    document.addEventListener('DOMContentLoaded', function() {
        activeChecksheetHtml = document.getElementById('preview-table-body').innerHTML;
        populateDateFilterOptions();
        renderDataViewTable();
        updateApprovalSelect();
        renderApprovalMonitoringTable();
        renderAccountTable();

        const urlParams = new URLSearchParams(window.location.search);
        const activeTabParam = urlParams.get('tab') || '{{ request()->query("tab") }}';

        const initialTargetForm = (urlFormParam && checksheets[urlFormParam]) ? urlFormParam : selectedChecksheetId;
        viewChecksheet(initialTargetForm, false);
        initAllSignaturesQRCodes();

        if (activeTabParam === 'proses-approval' || window.location.hash === '#proses-approval') {
            switchSheet('proses-approval');
        } else if (activeTabParam === 'data-view' || window.location.hash === '#data-view') {
            switchSheet('data-view');
        } else if ((activeTabParam === 'account-master' || window.location.hash === '#account-master') && userRoleType === 'admin') {
            switchSheet('account-master');
        }
    });

    function createNewForm() {
        // Clean up any empty draft forms that have no items
        for (const fNo in checksheets) {
            if (fNo !== activeFormNo && (!checksheets[fNo].items || checksheets[fNo].items.length === 0)) {
                delete checksheets[fNo];
                delete stepperData[fNo];
            }
        }

        const dept = (authUserDept || userDept || userTag || 'PRODUCTION').trim().toUpperCase();
        let targetDeptTag = dept;
        if (dept.includes('PRODUCTION') || dept.includes('DIES ASSY')) {
            targetDeptTag = 'PRODUCTION';
        } else if (dept.includes('HRGA') || dept.includes('HR/GA')) {
            targetDeptTag = 'HRGA';
        } else if (dept.includes('WAREHOUSE')) {
            targetDeptTag = 'WAREHOUSE';
        } else if (dept.includes('ACCOUNTING')) {
            targetDeptTag = 'ACCOUNTING';
        } else if (dept.includes('IT')) {
            targetDeptTag = 'IT';
        } else if (dept.includes('MAINTENANCE')) {
            targetDeptTag = 'MAINTENANCE';
        } else if (dept.includes('PURCHASING')) {
            targetDeptTag = 'PURCHASING';
        } else if (dept.includes('QUALITY') || dept.includes('QA') || dept.includes('QC')) {
            targetDeptTag = 'QUALITY';
        }

        const now = new Date();
        const monthStr = String(now.getMonth() + 1).padStart(2, '0');
        const yearStr = now.getFullYear();
        const monthYearStr = `${monthStr}-${yearStr}`;

        let maxSeq = 0;
        for (const fNo in checksheets) {
            const parts = fNo.split('/');
            if (parts.length >= 3) {
                const seq = parseInt(parts[0], 10);
                const tag = parts[1];
                const my = parts[2];
                if (!isNaN(seq) && tag === targetDeptTag && my === monthYearStr) {
                    if (seq > maxSeq) maxSeq = seq;
                }
            }
        }

        const nextSeq = String(maxSeq + 1).padStart(2, '0');
        const todayStr = '{{ date("d-m-Y") }}';
        const nextFormNo = `${nextSeq}/${targetDeptTag}/${monthYearStr}`;
        
        checksheets[nextFormNo] = {
            docNo: 'No Doc : W1-CDS-PP-20/F1 Rev 2 &nbsp;|&nbsp; No. Form: <span style="font-weight:700; color:var(--color-primary);">' + nextFormNo + '</span>',
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
                accounting: '...................',
                warehouse: '...................'
            },
            comments: {
                user: 'Formulir pendaftaran barang consumable baru.',
                staff: '',
                accounting: '',
                warehouse: ''
            }
        };

        stepperData[nextFormNo] = {
            statusText: 'Butuh Approval Staff / Section Head',
            statusClass: 'badge-warning',
            steps: [
                { role: 'user', title: '1. User Membuat Form', completed: true, active: false, details: '{{ Auth::user()->name ?? "User" }} - ' + targetDeptTag + ' (Tanggal: ' + todayStr + ')', status: 'Selesai dibuat & diajukan.', color: 'var(--color-success)' },
                { role: 'staff', title: '2. Approval Staff / Section Head', completed: false, active: true, details: 'Menunggu Approval Staff / Section Head...', status: 'Butuh Approval Staff / Section Head.', color: 'var(--color-primary)' },
                { role: 'accounting', title: '3. Approval Accounting', completed: false, active: false, details: 'Menunggu Approval Accounting...', status: 'Pemeriksaan anggaran & persetujuan.', color: 'var(--text-muted)' },
                { role: 'warehouse', title: '4. Registrasi Warehouse Consumable', completed: false, active: false, details: 'Menunggu Registrasi Warehouse...', status: 'Registrasi database ERP.', color: 'var(--text-muted)' }
            ]
        };

        // Update form number input inside addItemModal
        const modalFormNoInput = document.getElementById('modal_form_number');
        if (modalFormNoInput) {
            modalFormNoInput.value = nextFormNo;
        }

        updateApprovalSelect();
        renderDataViewTable();
        renderApprovalMonitoringTable();
        viewChecksheet(nextFormNo);
        renderComments(nextFormNo);
        openModal('addItemModal');

        showToast(`Formulir Baru (${nextFormNo}) Berhasil Dibuat! Silakan isi data barang.`, 'success');
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

        // Clean up previous empty draft form if user switched away without adding items
        if (selectedChecksheetId && selectedChecksheetId !== csId && selectedChecksheetId !== activeFormNo) {
            const prevCs = checksheets[selectedChecksheetId];
            if (prevCs && (!prevCs.items || prevCs.items.length === 0)) {
                delete checksheets[selectedChecksheetId];
                delete stepperData[selectedChecksheetId];
                updateApprovalSelect();
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

        const prevReq = document.getElementById('preview-requestor');
        if (prevReq && cs.requestor) prevReq.innerText = cs.requestor;

        const prevDate = document.getElementById('preview-date');
        if (prevDate && cs.date) prevDate.innerText = cs.date;
        
        const signatureDibuat = document.getElementById('preview-sig-dibuat');
        const signatureStaff = document.getElementById('preview-sig-staff');
        const signatureAccounting = document.getElementById('preview-sig-accounting');
        const signatureWarehouse = document.getElementById('preview-sig-warehouse');
        
        renderSignatureSlot(signatureDibuat, '✓ USER SUBMITTED', cs.signatures.dibuat, 'sig-qrcode-dibuat');
        renderSignatureSlot(signatureStaff, '✓ APPROVED BY STAFF', cs.signatures.staff, 'sig-qrcode-staff');
        renderSignatureSlot(signatureAccounting, '✓ APPROVED ACCOUNTING', cs.signatures.accounting, 'sig-qrcode-accounting');
        renderSignatureSlot(signatureWarehouse, '✓ REGISTERED WAREHOUSE', cs.signatures.warehouse, 'sig-qrcode-warehouse');

        const tbody = document.getElementById('preview-table-body');
        if (!cs.items || cs.items.length === 0) {
            if (csId === activeFormNo) {
                tbody.innerHTML = activeChecksheetHtml;
            } else {
                tbody.innerHTML = emptyTableHtml;
            }
        } else {
            let rowsHtml = '';
            cs.items.forEach((item, index) => {
                const isEven = index % 2 !== 0;
                const rowClass = isEven ? 'tr-even' : '';
                rowsHtml += `
                    <tr class="data-row ${rowClass}">
                        <td class="td-center td-no">${item.no}</td>
                        <td class="td-center" style="padding:0 0.4rem;">
                            ${item.kode ? `<span class="item-code-badge">${item.kode}</span>` : '<span style="color:var(--text-muted); font-size:0.75rem;">-</span>'}
                        </td>
                        <td style="padding:0 0.6rem; font-weight:700; font-size:0.82rem; color:#0f172a;">${item.nama}</td>
                        <td class="td-center">
                            ${item.harga ? `<span class="item-price-tag">${item.harga}</span>` : '<span style="color:#94a3b8; font-size:0.75rem;">-</span>'}
                        </td>
                        <td class="td-center" style="font-size:0.75rem; font-weight:600; color:#334155;">${item.usia || '-'}</td>
                        <td class="td-center" style="font-size:0.75rem; font-weight:600; color:#0f172a;">${item.katPeng || '-'}</td>
                        <td class="td-center" style="font-size:0.75rem; font-weight:600; color:#0f172a;">${item.katUk || '-'}</td>
                        <td class="td-center">${item.min ? `<span class="badge-stock-min">${item.min}</span>` : '-'}</td>
                        <td class="td-center">${item.titik ? `<span class="badge-stock-titik">${item.titik}</span>` : '-'}</td>
                        <td class="td-center">${item.max ? `<span class="badge-stock-max">${item.max}</span>` : '-'}</td>
                        <td class="td-center" style="font-size:0.75rem; font-weight:600; color:#0f172a;">${item.lead || '-'}</td>
                        <td class="td-center" style="vertical-align: middle;">
                            ${item.aset === 'ASET' ? '<span class="badge-asset-yes"><svg viewBox="0 0 24 24" width="11" height="11" stroke="currentColor" stroke-width="3" fill="none"><polyline points="20 6 9 17 4 12"></polyline></svg> ASET</span>' : '<span class="badge-asset-no">NO ASET</span>'}
                        </td>
                        <td class="td-center">
                            ${item.b3 ? `<div class="check-icon-b3" title="B3"><svg viewBox="0 0 24 24" width="13" height="13" stroke="currentColor" stroke-width="3" fill="none"><polyline points="20 6 9 17 4 12"></polyline></svg></div>` : ''}
                        </td>
                        <td class="td-center">
                            ${item.non_b3 ? `<div class="check-icon-b3" title="NON B3"><svg viewBox="0 0 24 24" width="13" height="13" stroke="currentColor" stroke-width="3" fill="none"><polyline points="20 6 9 17 4 12"></polyline></svg></div>` : ''}
                        </td>
                    </tr>
                `;
            });

            tbody.innerHTML = rowsHtml;
        }
        
        const select = document.getElementById('select-approval-cs');
        if (select) {
            select.value = csId;
            updateApprovalStepper(csId);
        }

        renderComments(csId);
    }

    window.addEventListener('beforeprint', function() {
        switchSheet('print-preview');
    });

    function printChecksheet(csId) {
        viewChecksheet(csId);
        setTimeout(function() { window.print(); }, 150);
    }

    function printCurrentSheet() {
        switchSheet('print-preview');
        setTimeout(function() { window.print(); }, 150);
    }

    function onSelectApprovalCs(csId) {
        updateApprovalStepper(csId);
    }

    function submitSimulatedApproval(event) {
        event.preventDefault();
        
        const cs = checksheets[selectedChecksheetId];
        const steps = stepperData[selectedChecksheetId] ? stepperData[selectedChecksheetId].steps : null;

        if (!cs || !steps) {
            alert('Formulir tidak ditemukan.');
            return;
        }

        const role = document.getElementById('sim-role').value;
        const name = document.getElementById('sim-name').value.trim();
        const comment = document.getElementById('sim-comment').value.trim();

        if (!name || !comment) {
            alert('Harap isi nama penandatangan dan catatan!');
            return;
        }

        if (role === 'user') {
            cs.requestor = name + ' / ' + cs.requestorDept;
            cs.requestorName = name;
            cs.signatures.dibuat = name + ' (Tgl: ' + cs.date + ')';
            cs.comments.user = comment;
            
            steps[0].completed = true;
            steps[0].active = false;
            steps[0].details = name + ' - ' + cs.requestorDept + ' (Tanggal: ' + cs.date + ')';
            steps[0].status = 'Selesai dibuat & diajukan.';
            steps[0].color = 'var(--color-success)';
            
            steps[1].active = true;
            steps[1].details = 'Menunggu persetujuan Staff / Section Head...';
            steps[1].status = 'Butuh Approval Staff / Section Head.';
            steps[1].color = 'var(--color-primary)';

            cs.status = 'Butuh Approval Staff / Section Head';
            stepperData[selectedChecksheetId].statusText = 'Butuh Approval Staff / Section Head';
            stepperData[selectedChecksheetId].statusClass = 'badge-warning';
        } 
        else if (role === 'staff') {
            if (!steps[0].completed) {
                alert('Role Pembuat (User) harus menyelesaikan pengajuan form terlebih dahulu!');
                return;
            }
            cs.signatures.staff = name + ' (Tgl: ' + cs.date + ')';
            cs.comments.staff = comment;
            
            steps[1].completed = true;
            steps[1].active = false;
            steps[1].details = name + ' (Staff / Section Head - Tanggal: ' + cs.date + ')';
            steps[1].status = 'Disetujui oleh Staff / Section Head.';
            steps[1].color = 'var(--color-success)';
            
            steps[2].active = true;
            steps[2].details = 'Menunggu persetujuan Accounting...';
            steps[2].status = 'Butuh Approval Accounting.';
            steps[2].color = 'var(--color-primary)';

            cs.status = 'Butuh Approval Accounting';
            stepperData[selectedChecksheetId].statusText = 'Butuh Approval Accounting';
            stepperData[selectedChecksheetId].statusClass = 'badge-primary';
        } 
        else if (role === 'accounting') {
            if (!steps[1].completed) {
                alert('Approval Staff / Section Head harus diselesaikan terlebih dahulu!');
                return;
            }
            cs.signatures.accounting = name + ' (Tgl: ' + cs.date + ')';
            cs.comments.accounting = comment;
            
            steps[2].completed = true;
            steps[2].active = false;
            steps[2].details = name + ' (Accounting - Tanggal: ' + cs.date + ')';
            steps[2].status = 'Disetujui oleh Accounting.';
            steps[2].color = 'var(--color-success)';
            
            steps[3].active = true;
            steps[3].details = 'Menunggu registrasi oleh Warehouse Consumable...';
            steps[3].status = 'Butuh Approval Warehouse Consumable.';
            steps[3].color = 'var(--color-primary)';

            cs.status = 'Butuh Approval Warehouse Consumable';
            stepperData[selectedChecksheetId].statusText = 'Butuh Approval Warehouse Consumable';
            stepperData[selectedChecksheetId].statusClass = 'badge-info';
        }
        else if (role === 'warehouse') {
            if (!steps[2].completed) {
                alert('Approval Accounting harus diselesaikan terlebih dahulu!');
                return;
            }
            cs.signatures.warehouse = name + ' (Tgl: ' + cs.date + ')';
            cs.comments.warehouse = comment;
            
            steps[3].completed = true;
            steps[3].active = false;
            steps[3].details = name + ' (Warehouse Consumable - Tanggal: ' + cs.date + ')';
            steps[3].status = 'Item Telah didaftarkan.';
            steps[3].color = 'var(--color-success)';
            
            cs.status = 'Item Telah didaftarkan';
            stepperData[selectedChecksheetId].statusText = 'Item Telah didaftarkan';
            stepperData[selectedChecksheetId].statusClass = 'badge-success';
        }

        document.getElementById('sim-comment').value = '';
        
        viewChecksheet(selectedChecksheetId);
        updateApprovalStepper(selectedChecksheetId);
        renderApprovalMonitoringTable();
        renderDataViewTable();
        showToast(`Catatan approval (${role.toUpperCase()}) berhasil diproses!`, 'success');
    }

    function updateDataViewTableStatus(csId, text, bgColor, color) {
        const cells = Array.from(document.querySelectorAll('#data-view-pane table td'));
        const csCell = cells.find(c => c.innerText.trim() === csId);
        if (csCell) {
            const row = csCell.parentElement;
            const statusSpan = row.querySelector('td span');
            if (statusSpan) {
                statusSpan.innerText = text;
                statusSpan.style.backgroundColor = bgColor;
                statusSpan.style.color = color;
            }
        }
    }

    function onChangeSimRole(role) {}

    function updateApprovalStepper(csId) {
        const data = stepperData[csId];
        if (!data) return;

        const badge = document.getElementById('approval-status-badge');
        if (badge) {
            badge.innerText = data.statusText;
            badge.className = 'badge';
            if (data.statusText === 'Item Telah didaftarkan' || data.statusText === 'TELAH DIDAFTARKAN' || data.statusText === 'APPROVED') {
                badge.style.backgroundColor = 'rgba(16, 185, 129, 0.15)';
                badge.style.color = '#059669';
            } else if (data.statusText === 'Butuh Approval Warehouse Consumable' || data.statusText === 'REGISTRASI WAREHOUSE') {
                badge.style.backgroundColor = 'rgba(147, 51, 234, 0.12)';
                badge.style.color = '#7c3aed';
            } else if (data.statusText === 'Butuh Approval Accounting' || data.statusText === 'Butuh Approval Accouting' || data.statusText === 'APPROVAL ACCOUNTING') {
                badge.style.backgroundColor = 'rgba(59, 130, 246, 0.12)';
                badge.style.color = '#2563eb';
            } else {
                badge.style.backgroundColor = 'rgba(245, 158, 11, 0.12)';
                badge.style.color = '#d97706';
            }
        }

        for (let i = 1; i <= 4; i++) {
            const stepEl = document.getElementById('step-' + i);
            if (!stepEl) continue;
            const stepData = data.steps[i - 1];
            
            stepEl.classList.remove('completed', 'active', 'pending');
            
            const indicator = stepEl.querySelector('.step-indicator');
            const detailsEl = document.getElementById('step-' + i + '-details');
            const statusEl = document.getElementById('step-' + i + '-status');

            if (detailsEl) detailsEl.innerText = stepData.details;
            if (statusEl) {
                statusEl.innerText = stepData.status;
                statusEl.style.color = stepData.color;
            }

            const commentBox = document.getElementById('step-' + i + '-comment-box');
            const commentText = document.getElementById('step-' + i + '-comment-text');
            const roleKey = i === 1 ? 'user' : (i === 2 ? 'staff' : (i === 3 ? 'accounting' : 'warehouse'));
            const commentVal = checksheets[csId] && checksheets[csId].comments ? checksheets[csId].comments[roleKey] : '';

            if (commentBox && commentText) {
                if (commentVal) {
                    commentBox.style.display = 'block';
                    commentText.innerText = commentVal;
                } else {
                    commentBox.style.display = 'none';
                    commentText.innerText = '';
                }
            }

            if (stepData.completed) {
                stepEl.classList.add('completed');
                if (indicator) indicator.innerHTML = `<svg viewBox="0 0 24 24" width="12" height="12" stroke="currentColor" stroke-width="3" fill="none"><polyline points="20 6 9 17 4 12"></polyline></svg>`;
            } else if (stepData.active) {
                stepEl.classList.add('active');
                if (indicator) indicator.innerHTML = `<svg viewBox="0 0 24 24" width="12" height="12" stroke="currentColor" stroke-width="3" fill="none"><circle cx="12" cy="12" r="10"></circle></svg>`;
            } else {
                stepEl.classList.add('pending');
                if (indicator) indicator.innerHTML = i;
            }
        }
    }

    let currentApprovalFilterStage = '';

    function filterApprovalStage(stage, btn) {
        currentApprovalFilterStage = stage;

        const pills = document.querySelectorAll('#approval-filter-pills .filter-pill-btn');
        pills.forEach(p => {
            p.classList.remove('btn-primary', 'active');
            p.classList.add('btn-secondary');
        });

        if (btn) {
            btn.classList.remove('btn-secondary');
            btn.classList.add('btn-primary', 'active');
        }

        renderApprovalMonitoringTable();
    }

    function getCsDepartment(cs, fNo) {
        if (cs && cs.requestorDept) return cs.requestorDept.trim().toUpperCase();
        if (fNo && fNo.includes('/')) {
            const parts = fNo.split('/');
            if (parts.length >= 2) return parts[1].trim().toUpperCase();
        }
        return '';
    }

    function renderApprovalMonitoringTable() {
        const tbody = document.getElementById('approval-monitoring-tbody');
        if (!tbody) return;

        let html = '';
        let no = 1;

        let statTotal = 0;
        let statStaff = 0;
        let statAccounting = 0;
        let statWarehouse = 0;

        const staffDept = (authUserDept || '').trim().toUpperCase();

        for (const formNo in checksheets) {
            const cs = checksheets[formNo];
            if (!cs.items || cs.items.length === 0) continue;

            const data = stepperData[formNo];
            if (!data) continue;

            // RULE 1: Formulir yang statusnya sudah didaftarkan (Item Telah didaftarkan / Selesai) TIDAK MUNCUL di sheet Proses Approval
            const isCompleted = (cs.status === 'Item Telah didaftarkan' || cs.status === 'TELAH DIDAFTARKAN' || cs.status === 'Selesai' || data.statusText === 'Item Telah didaftarkan' || data.statusText === 'TELAH DIDAFTARKAN' || (data.steps && data.steps[3] && data.steps[3].completed));
            if (isCompleted) {
                continue;
            }

            const formDept = getCsDepartment(cs, formNo);

            // RULE 2: Role User dan Staff HANYA dapat melihat form dari department yang diizinkan
            if (!canViewAllDepartments && !isDeptAllowed(formDept)) {
                continue; // Skip form dari department lain
            }

            statTotal++;

            const activeStepIndex = data.steps.findIndex(s => s.active);
            let stageKey = 'user';
            let currentStageName = 'Pembuatan (User)';

            if (activeStepIndex === 0) {
                stageKey = 'user';
                currentStageName = 'Pembuatan (User)';
            } else if (activeStepIndex === 1) {
                stageKey = 'staff';
                currentStageName = 'Butuh Staff / Section Head';
                statStaff++;
            } else if (activeStepIndex === 2) {
                stageKey = 'accounting';
                currentStageName = 'Accounting';
                statAccounting++;
            } else if (activeStepIndex === 3) {
                stageKey = 'warehouse';
                currentStageName = 'Warehouse';
                statWarehouse++;
            }

            if (currentApprovalFilterStage && currentApprovalFilterStage !== stageKey) {
                continue;
            }

            let badgeBg = 'rgba(245, 158, 11, 0.12)';
            let badgeColor = '#d97706';
            let badgeText = data.statusText;

            if (badgeText === 'Item Telah didaftarkan' || badgeText === 'TELAH DIDAFTARKAN') {
                badgeText = 'Item Telah didaftarkan';
                badgeBg = 'rgba(16, 185, 129, 0.15)';
                badgeColor = '#059669';
            } else if (badgeText === 'Butuh Approval Warehouse Consumable' || badgeText === 'REGISTRASI WAREHOUSE') {
                badgeText = 'Butuh Approval Warehouse Consumable';
                badgeBg = 'rgba(147, 51, 234, 0.12)';
                badgeColor = '#7c3aed';
            } else if (badgeText === 'Butuh Approval Accounting' || badgeText === 'Butuh Approval Accouting' || badgeText === 'APPROVAL ACCOUNTING') {
                badgeText = 'Butuh Approval Accounting';
                badgeBg = 'rgba(59, 130, 246, 0.12)';
                badgeColor = '#2563eb';
            } else {
                badgeText = 'Butuh Approval Staff / Section Head';
                badgeBg = 'rgba(245, 158, 11, 0.12)';
                badgeColor = '#d97706';
            }

            // Generate 4-step Horizontal Stepper Visual Pills
            let stepsHtml = '<div style="display: inline-flex; align-items: center; gap: 0.25rem;">';
            data.steps.forEach((st, idx) => {
                let pillStyle = 'background: #f1f5f9; color: #94a3b8; border: 1px solid #cbd5e1;';
                let pillLabel = idx === 0 ? 'User' : (idx === 1 ? 'Staff' : (idx === 2 ? 'Acc' : 'WH'));
                
                if (st.completed) {
                    pillStyle = 'background: var(--color-success); color: #fff; border: 1px solid var(--color-success);';
                    pillLabel = '✓ ' + pillLabel;
                } else if (st.active) {
                    pillStyle = 'background: var(--color-primary); color: #fff; border: 1px solid var(--color-primary); font-weight: 700; box-shadow: 0 0 0 2px var(--color-primary-light);';
                }

                stepsHtml += `<span style="font-size: 0.68rem; font-weight: 600; padding: 0.15rem 0.45rem; border-radius: 12px; ${pillStyle}">${pillLabel}</span>`;
                if (idx < 3) {
                    stepsHtml += `<span style="color: #cbd5e1; font-size: 0.65rem;">➔</span>`;
                }
            });
            stepsHtml += '</div>';

            // Role-Based Strict Action Button Generator
            let actionBtnHtml = '';

            if (userRoleType === 'user') {
                // Rule: Role User TIDAK BISA APPROVAL (Hanya membuat form)
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
                // Rule: Role Staff HANYA BISA APPROVAL ketika User telah membuat form (Tahap 1 -> Staff) di departmentnya
                if (stageKey === 'staff') {
                    actionBtnHtml = `
                        <button class="btn btn-primary btn-sm" onclick="openQuickApprovalModal('${cs.formNo}')" style="padding: 0.35rem 0.75rem; font-size: 0.78rem; font-weight: 700; border-radius: 6px; display: inline-flex; align-items: center; gap: 0.3rem; background: linear-gradient(135deg, #2563eb, #1d4ed8);">
                            <svg viewBox="0 0 24 24" width="13" height="13" stroke="currentColor" stroke-width="2.5" fill="none"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            Setujui (Staff)
                        </button>
                    `;
                } else if (stageKey === 'accounting') {
                    actionBtnHtml = `
                        <div style="display: inline-flex; flex-direction: column; align-items: center; gap: 2px;">
                            <button class="btn btn-secondary btn-sm" onclick="viewChecksheet('${cs.formNo}')" style="padding: 0.25rem 0.55rem; font-size: 0.72rem; border-radius: 6px;">Lihat Form</button>
                            <span style="font-size: 0.68rem; color: #4f46e5; font-weight: 600;">Menunggu Acc</span>
                        </div>
                    `;
                } else {
                    actionBtnHtml = `
                        <div style="display: inline-flex; flex-direction: column; align-items: center; gap: 2px;">
                            <button class="btn btn-secondary btn-sm" onclick="viewChecksheet('${cs.formNo}')" style="padding: 0.25rem 0.55rem; font-size: 0.72rem; border-radius: 6px;">Lihat Form</button>
                            <span style="font-size: 0.68rem; color: #0284c7; font-weight: 600;">Menunggu WH</span>
                        </div>
                    `;
                }
            } else if (userRoleType === 'accounting') {
                // Rule: Role Accounting HANYA DAPAT APPROVAL setelah Role Staff approval
                if (stageKey === 'staff') {
                    actionBtnHtml = `
                        <div style="display: inline-flex; flex-direction: column; align-items: center; gap: 2px;">
                            <button class="btn btn-secondary btn-sm" onclick="viewChecksheet('${cs.formNo}')" style="padding: 0.25rem 0.55rem; font-size: 0.72rem; border-radius: 6px;">Lihat Form</button>
                            <span style="font-size: 0.68rem; color: #d97706; font-weight: 600;">Menunggu Staff</span>
                        </div>
                    `;
                } else if (stageKey === 'accounting') {
                    actionBtnHtml = `
                        <button class="btn btn-primary btn-sm" onclick="openQuickApprovalModal('${cs.formNo}')" style="padding: 0.35rem 0.75rem; font-size: 0.78rem; font-weight: 700; border-radius: 6px; display: inline-flex; align-items: center; gap: 0.3rem; background: linear-gradient(135deg, #4f46e5, #4338ca);">
                            <svg viewBox="0 0 24 24" width="13" height="13" stroke="currentColor" stroke-width="2.5" fill="none"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            Setujui (Accounting)
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
                // Rule: Role Warehouse Consumable HANYA BISA REGIST / APPROVAL setelah Role Accounting sudah approval
                if (stageKey === 'staff') {
                    actionBtnHtml = `
                        <div style="display: inline-flex; flex-direction: column; align-items: center; gap: 2px;">
                            <button class="btn btn-secondary btn-sm" onclick="viewChecksheet('${cs.formNo}')" style="padding: 0.25rem 0.55rem; font-size: 0.72rem; border-radius: 6px;">Lihat Form</button>
                            <span style="font-size: 0.68rem; color: #d97706; font-weight: 600;">Menunggu Staff</span>
                        </div>
                    `;
                } else if (stageKey === 'accounting') {
                    actionBtnHtml = `
                        <div style="display: inline-flex; flex-direction: column; align-items: center; gap: 2px;">
                            <button class="btn btn-secondary btn-sm" onclick="viewChecksheet('${cs.formNo}')" style="padding: 0.25rem 0.55rem; font-size: 0.72rem; border-radius: 6px;">Lihat Form</button>
                            <span style="font-size: 0.68rem; color: #4f46e5; font-weight: 600;">Menunggu Acc</span>
                        </div>
                    `;
                } else if (stageKey === 'warehouse') {
                    actionBtnHtml = `
                        <button class="btn btn-primary btn-sm" onclick="openQuickApprovalModal('${cs.formNo}')" style="padding: 0.35rem 0.75rem; font-size: 0.78rem; font-weight: 700; border-radius: 6px; display: inline-flex; align-items: center; gap: 0.3rem; background: linear-gradient(135deg, #059669, #047857);">
                            <svg viewBox="0 0 24 24" width="13" height="13" stroke="currentColor" stroke-width="2.5" fill="none"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                            Registrasi Barang
                        </button>
                    `;
                }
            } else {
                // Administrator / Master: can approve whatever stage is currently active
                let adminBtnLabel = 'Setujui Form';
                let adminBg = 'linear-gradient(135deg, #2563eb, #1d4ed8)';
                if (stageKey === 'staff') {
                    adminBtnLabel = 'Setujui (Staff / Admin)';
                } else if (stageKey === 'accounting') {
                    adminBtnLabel = 'Setujui (Acc / Admin)';
                    adminBg = 'linear-gradient(135deg, #4f46e5, #4338ca)';
                } else if (stageKey === 'warehouse') {
                    adminBtnLabel = 'Registrasi (WH / Admin)';
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

        const elAcc = document.getElementById('approval-stat-accounting');
        if (elAcc) elAcc.innerText = statAccounting;

        const elWh = document.getElementById('approval-stat-warehouse') || document.getElementById('approval-stat-registered');
        if (elWh) elWh.innerText = statWarehouse;
    }

    function openQuickApprovalModal(csId) {
        selectedChecksheetId = csId;
        const cs = checksheets[csId];
        const data = stepperData[csId];
        if (!cs || !data) return;

        // Rule 1: Role User TIDAK BISA APPROVAL
        if (userRoleType === 'user') {
            alert('Akses Ditolak: Akun dengan role User hanya bertugas membuat & mengajukan FORM Baru dan tidak memiliki wewenang approval.');
            return;
        }

        const activeStepIndex = data.steps.findIndex(s => s.active);

        // Strict Role Hierarchy & Department Verification
        if (userRoleType === 'staff') {
            // Rule 2: Role Staff HANYA BISA APPROVAL sesuai dengan department dari usernya saja
            const formDept = getCsDepartment(cs, csId);

            if (formDept && !isDeptAllowed(formDept)) {
                alert(`Akses Ditolak: Anda login sebagai Staff Departemen ${authUserDept}. Anda hanya berwenang menyetujui formulir dari departemen Anda sendiri (Formulir ${csId} berasal dari Departemen ${formDept}).`);
                return;
            }

            if (activeStepIndex !== 1) {
                alert('Akses Ditolak: Role Staff hanya dapat melakukan approval ketika formulir baru diajukan oleh User (Tahap Approval Staff).');
                return;
            }
        } else if (userRoleType === 'accounting') {
            // Rule 3: Role Accounting HANYA DAPAT APPROVAL setelah role staff approval
            if (activeStepIndex === 0 || activeStepIndex === 1) {
                alert('Akses Ditolak: Role Accounting hanya dapat melakukan approval setelah formulir disetujui oleh Staff terlebih dahulu.');
                return;
            } else if (activeStepIndex !== 2) {
                alert('Formulir ini tidak sedang berada dalam tahap Approval Accounting.');
                return;
            }
        } else if (userRoleType === 'warehouse') {
            // Rule 4: Role Warehouse Consumable HANYA BISA REGIST / APPROVAL setelah role accounting sudah approval
            if (activeStepIndex === 0 || activeStepIndex === 1) {
                alert('Akses Ditolak: Warehouse Consumable baru dapat meregistrasi barang setelah formulir disetujui oleh Staff dan Accounting.');
                return;
            } else if (activeStepIndex === 2) {
                alert('Akses Ditolak: Warehouse Consumable baru dapat meregistrasi barang setelah formulir disetujui oleh Accounting.');
                return;
            } else if (activeStepIndex !== 3) {
                alert('Formulir ini tidak sedang berada dalam tahap Registrasi Warehouse.');
                return;
            }
        }

        let currentRoleKey = 'staff';
        let roleBadgeTitle = 'Staff Approver (Approval Tahap 1)';
        let roleBadgeColor = '#2563eb';
        let actionBtnText = '✓ Setujui sebagai Staff';
        let infoHelperText = `Memverifikasi spesifikasi data barang yang diajukan oleh <strong>${cs.requestor}</strong>.`;

        if (activeStepIndex === 2) {
            currentRoleKey = 'accounting';
            roleBadgeTitle = 'Accounting Approver (Approval Tahap 2)';
            roleBadgeColor = '#4f46e5';
            actionBtnText = '✓ Setujui sebagai Accounting';
            infoHelperText = `Menyetujui anggaran & klasifikasi aset setelah persetujuan oleh Staff (<strong>${cs.signatures.staff || 'Staff'}</strong>).`;
        } else if (activeStepIndex === 3) {
            currentRoleKey = 'warehouse';
            roleBadgeTitle = 'Warehouse Consumable (Final Registrasi & Input ERP)';
            roleBadgeColor = '#059669';
            actionBtnText = '✓ Registrasi Barang ke Sistem (Warehouse)';
            infoHelperText = `Mendaftarkan barang consumable ke sistem master inventaris setelah persetujuan oleh Accounting (<strong>${cs.signatures.accounting || 'Accounting'}</strong>).`;
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
                            <span style="font-size: 0.7rem; font-weight: 600; color: ${st.active ? 'var(--color-primary)' : (st.completed ? 'var(--color-success)' : 'var(--text-muted)')};">${i === 0 ? 'User' : (i === 1 ? 'Staff' : (i === 2 ? 'Accounting' : 'Warehouse'))}</span>
                        </div>
                    `).join('<div style="width: 15px; height: 2px; background: #e2e8f0; margin-bottom: 1rem;"></div>')}
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
                        <input type="text" id="qa-comment" class="form-control" style="height: 38px; font-size: 0.85rem;" placeholder="Cth: Disetujui sesuai spesifikasi." value="Disetujui.">
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

        // Sequential & Department validation before sending
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
        } else if (role === 'accounting') {
            if (!steps[1].completed && userRoleType !== 'admin') {
                alert('Akses Gagal: Approval Staff harus diselesaikan terlebih dahulu sebelum Accounting!');
                return;
            }
        } else if (role === 'warehouse') {
            if (!steps[2].completed && userRoleType !== 'admin') {
                alert('Akses Gagal: Approval Accounting harus diselesaikan terlebih dahulu sebelum Registrasi Warehouse!');
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
            const response = await fetch('{{ route("form-registrasi.approve") }}', {
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
                steps[2].details = 'Menunggu persetujuan Accounting...';
                steps[2].status = 'Butuh Approval Accounting.';
                steps[2].color = 'var(--color-primary)';

                cs.status = 'Butuh Approval Accounting';
                stepperData[csId].statusText = 'Butuh Approval Accounting';
                stepperData[csId].statusClass = 'badge-primary';
            }
            else if (role === 'accounting') {
                cs.signatures.accounting = name + ' (Tgl: ' + todayStr + ')';
                cs.comments.accounting = comment;

                steps[2].completed = true;
                steps[2].active = false;
                steps[2].details = name + ' (Accounting - Tanggal: ' + todayStr + ')';
                steps[2].status = 'Disetujui oleh Accounting.';
                steps[2].color = 'var(--color-success)';

                steps[3].active = true;
                steps[3].details = 'Menunggu registrasi oleh Warehouse Consumable...';
                steps[3].status = 'Butuh Approval Warehouse Consumable.';
                steps[3].color = 'var(--color-primary)';

                cs.status = 'Butuh Approval Warehouse Consumable';
                stepperData[csId].statusText = 'Butuh Approval Warehouse Consumable';
                stepperData[csId].statusClass = 'badge-info';
            }
            else if (role === 'warehouse') {
                cs.signatures.warehouse = name + ' (Tgl: ' + todayStr + ')';
                cs.comments.warehouse = comment;

                steps[3].completed = true;
                steps[3].active = false;
                steps[3].details = name + ' (Warehouse Consumable - Tanggal: ' + todayStr + ')';
                steps[3].status = 'Item Telah didaftarkan.';
                steps[3].color = 'var(--color-success)';

                cs.status = 'Item Telah didaftarkan';
                stepperData[csId].statusText = 'Item Telah didaftarkan';
                stepperData[csId].statusClass = 'badge-success';
            }

            closeModal('quickApprovalModal');
            renderApprovalMonitoringTable();
            renderDataViewTable();
            if (selectedChecksheetId === csId) {
                viewChecksheet(csId);
            }
            updateApprovalStepper(csId);
            showToast(data.message || `Approval Form ${csId} (${role.toUpperCase()}) Berhasil Disimpan Permanen!`, 'success');

        } catch (err) {
            console.error('Approval Error:', err);
            alert('Terjadi kesalahan koneksi ke server saat menyimpan approval.');
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = origBtnHtml;
            }
        }
    }

    function selectFormForApproval(csId) {
        selectedChecksheetId = csId;
        const select = document.getElementById('select-approval-cs');
        if (select) select.value = csId;
        updateApprovalStepper(csId);
        switchSheet('proses-approval');
    }

    // ACCOUNT MASTER DATA ENGINE - Dynamic from Database
    const userAccounts = [
        @if(isset($users) && $users->count() > 0)
            @foreach($users as $u)
            {
                id: {{ $u->id }},
                username: '{{ $u->email }}',
                name: '{{ $u->name }}',
                dept: '{{ $u->department ?? "Production" }}',
                role: '{{ $u->role ?? "User" }}',
                status: '{{ $u->status ?? "Aktif" }}',
                date: '{{ $u->created_at ? $u->created_at->format("d-m-Y") : date("d-m-Y") }}'
            },
            @endforeach
        @endif
    ];

    function renderAccountTable() {
        const tbody = document.getElementById('account-table-body') || document.getElementById('accounts-tbody');
        if (!tbody) return;

        let html = '';
        let userCount = 0;
        let staffCount = 0;
        let accCount = 0;
        let whCount = 0;

        userAccounts.forEach((acc, index) => {
            let roleBadge = '';
            const rLower = (acc.role || '').toLowerCase();

            if (rLower === 'master' || rLower === 'admin') {
                roleBadge = `<span style="background-color: rgba(99, 102, 241, 0.15); color: #4f46e5; border: 1px solid rgba(99, 102, 241, 0.3); padding: 0.25rem 0.6rem; border-radius: 4px; font-weight: 700; font-size: 0.75rem;">MASTER</span>`;
            } else if (rLower === 'user') {
                userCount++;
                roleBadge = `<span style="background-color: rgba(59, 130, 246, 0.15); color: rgb(29, 78, 216); padding: 0.25rem 0.6rem; border-radius: 4px; font-weight: 700; font-size: 0.75rem;">User</span>`;
            } else if (rLower.includes('staff')) {
                staffCount++;
                roleBadge = `<span style="background-color: var(--color-warning-light); color: var(--color-warning); padding: 0.25rem 0.6rem; border-radius: 4px; font-weight: 700; font-size: 0.75rem;">${acc.role}</span>`;
            } else if (rLower === 'accounting' || rLower.includes('acc')) {
                accCount++;
                roleBadge = `<span style="background-color: rgba(168, 85, 247, 0.15); color: #9333ea; padding: 0.25rem 0.6rem; border-radius: 4px; font-weight: 700; font-size: 0.75rem;">Accounting</span>`;
            } else if (rLower.includes('warehouse')) {
                whCount++;
                roleBadge = `<span style="background-color: var(--color-success-light); color: var(--color-success); padding: 0.25rem 0.6rem; border-radius: 4px; font-weight: 700; font-size: 0.75rem;">Warehouse Consumable</span>`;
            } else {
                roleBadge = `<span style="background-color: rgba(100, 116, 139, 0.15); color: #475569; padding: 0.25rem 0.6rem; border-radius: 4px; font-weight: 700; font-size: 0.75rem;">${acc.role}</span>`;
            }

            const jsonAcc = JSON.stringify(acc).replace(/'/g, "&apos;").replace(/"/g, "&quot;");
            const isSelf = ({{ auth()->id() ?? 0 }} === acc.id);
            const deleteBtnHtml = isSelf ? '' : `
                <form action="/users/${acc.id}" method="POST" style="display:inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus akun user &quot;${acc.name}&quot;?')">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <input type="hidden" name="_method" value="DELETE">
                    <button type="submit" class="btn btn-danger btn-sm" style="padding: 0.25rem 0.55rem; font-size: 0.75rem; border-radius: 4px; background-color: #ef4444; color: white; border: none; margin-left: 0.2rem;">Hapus</button>
                </form>
            `;

            html += `
                <tr>
                    <td class="td-center td-no">${index + 1}</td>
                    <td style="font-weight: 700; color: var(--text-primary); padding: 0 0.8rem;">
                        ${acc.name}
                        <div style="font-size:0.75rem; color:var(--text-muted); font-weight:normal;">Username / Login ID: <strong>${acc.username}</strong></div>
                    </td>
                    <td style="padding: 0 0.8rem; font-size: 0.85rem; font-weight: 600;">${acc.dept}</td>
                    <td class="td-center">${roleBadge}</td>
                    <td class="td-center"><span style="background-color: var(--color-success-light); color: var(--color-success); padding: 0.2rem 0.5rem; border-radius: 4px; font-weight: 700; font-size: 0.7rem;">${acc.status}</span></td>
                    <td class="td-center" style="font-size: 0.85rem;">${acc.date}</td>
                    <td class="td-center" style="padding: 0 0.5rem;">
                        <button class="btn btn-secondary btn-sm" onclick="openEditUserModal(${jsonAcc})" style="padding: 0.25rem 0.55rem; font-size: 0.75rem; border-radius: 4px;">Edit</button>
                        ${deleteBtnHtml}
                    </td>
                </tr>
            `;
        });

        if (userAccounts.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" style="text-align: center; padding: 1.5rem; color: var(--text-muted);">Belum ada akun pengguna terdaftar. Klik "+ Buat Akun Baru" untuk menambahkan pengguna.</td>
                </tr>
            `;
        } else {
            tbody.innerHTML = html;
        }

        const statTotal = document.getElementById('stat-total-accounts');
        if (statTotal) statTotal.innerText = `${userAccounts.length} Akun`;

        const statUser = document.getElementById('stat-user-accounts-count');
        if (statUser) statUser.innerText = `${userCount} Akun`;

        const statStaff = document.getElementById('stat-staff-accounts-count');
        if (statStaff) statStaff.innerText = `${staffCount} Akun`;

        const statAccWh = document.getElementById('stat-acc-wh-accounts-count');
        if (statAccWh) statAccWh.innerText = `${accCount + whCount} Akun`;
    }

    function openEditUserModal(acc) {
        const form = document.getElementById('form-edit-account');
        if (!form) return;
        form.action = `/users/${acc.id}`;
        document.getElementById('acc_edit_name').value = acc.name || '';
        document.getElementById('acc_edit_username').value = acc.username || '';

        const deptSelect = document.getElementById('acc_edit_dept');
        if (deptSelect) deptSelect.value = acc.dept || 'Production';

        const roleSelect = document.getElementById('acc_edit_role');
        if (roleSelect) roleSelect.value = acc.role || 'User';

        document.getElementById('acc_edit_password').value = '';
        openModal('editAccountModal');
    }

    // =========================================================================
    // 💬 FORM COMMENTS & REAL-TIME DISCUSSION ENGINE
    // =========================================================================
    const formCommentsMap = {};

    function formatCommentDate(val) {
        if (!val) return 'Baru saja';
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
            return {
                bg: 'rgba(99, 102, 241, 0.15)',
                color: '#4f46e5',
                avatarBg: 'linear-gradient(135deg, #4f46e5, #7c3aed)',
                label: 'MASTER'
            };
        } else if (r.includes('STAFF')) {
            return {
                bg: 'rgba(245, 158, 11, 0.15)',
                color: '#d97706',
                avatarBg: 'linear-gradient(135deg, #f59e0b, #d97706)',
                label: 'STAFF'
            };
        } else if (r.includes('ACCOUNTING') || r.includes('ACC')) {
            return {
                bg: 'rgba(168, 85, 247, 0.15)',
                color: '#9333ea',
                avatarBg: 'linear-gradient(135deg, #9333ea, #c026d3)',
                label: 'ACCOUNTING'
            };
        } else if (r.includes('WAREHOUSE')) {
            return {
                bg: 'rgba(16, 185, 129, 0.15)',
                color: '#059669',
                avatarBg: 'linear-gradient(135deg, #10b981, #059669)',
                label: 'WAREHOUSE'
            };
        } else {
            return {
                bg: 'rgba(59, 130, 246, 0.15)',
                color: 'rgb(29, 78, 216)',
                avatarBg: 'linear-gradient(135deg, #0ea5e9, #2563eb)',
                label: 'USER'
            };
        }
    }

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
                        Belum ada diskusi atau catatan pada formulir <strong>${formNo}</strong>. Semua role (User, Staff, Accounting, Warehouse, Master) dapat menuliskan komentar di bawah.
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

        const targetForm = selectedChecksheetId || activeFormNo;
        const submitBtn = document.getElementById('btn-submit-comment');
        const origHtml = submitBtn ? submitBtn.innerHTML : '';

        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<svg class="spinner" viewBox="0 0 24 24" width="15" height="15" stroke="currentColor" stroke-width="2" fill="none"><circle cx="12" cy="12" r="10"></circle></svg> Mengirim...';
        }

        try {
            const response = await fetch('{{ route("form-registrasi.comments.store") }}', {
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

            // Scroll comment list to bottom smoothly
            const listEl = document.getElementById('form-comments-list');
            if (listEl) {
                listEl.scrollTop = listEl.scrollHeight;
            }

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
        if (!confirm('Apakah Anda yakin ingin menghapus komentar ini?')) {
            return;
        }

        const targetForm = selectedChecksheetId || activeFormNo;

        try {
            const response = await fetch(`/form-registrasi/comments/${commentId}`, {
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
</script>
@endsection
