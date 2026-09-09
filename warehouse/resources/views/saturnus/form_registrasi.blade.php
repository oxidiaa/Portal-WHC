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
    /* Modal Styling */
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
        width: 92% !important;
        max-width: 660px !important;
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

    @media print {
        @page {
            size: A4 landscape;
            margin: 6mm 8mm 6mm 8mm;
        }
        body {
            background: #ffffff !important;
            color: #000000 !important;
            font-size: 8pt !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        .no-print, .sidebar, .topbar, .footer, .header, .sheet-tabs-container, .sheet-doc-toolbar, .saturn-floating-dock, .theme-customizer-toggle, .form-comments-card, .form-reg-footer-actions {
            display: none !important;
        }
        .page-content, .workspace-light-theme {
            padding: 0 !important;
            margin: 0 !important;
            width: 100% !important;
            background: #ffffff !important;
        }
        .form-reg-card {
            border: 1.5px solid #000000 !important;
            box-shadow: none !important;
            padding: 4mm !important;
            margin: 0 !important;
            width: 100% !important;
            max-width: 100% !important;
            background: #ffffff !important;
        }
        .form-reg-table th, .form-reg-table td {
            border: 1px solid #000000 !important;
            color: #000000 !important;
            padding: 2px 4px !important;
            font-size: 7.5pt !important;
        }
        .sig-box {
            border: 1px solid #000000 !important;
            background: #ffffff !important;
        }
        .sig-qrcode canvas {
            display: none !important;
        }
        .sig-qrcode img {
            width: 36px !important;
            height: 36px !important;
            margin: 0 auto !important;
            display: block !important;
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
            <p class="galactic-subtitle">Lembar kerja resmi pendaftaran barang consumable, tabel item, tanda tangan QR Code, dan cetak A4.</p>
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
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- ===== FORM ACTION BAR (NO PRINT) ===== --}}
        <div class="form-reg-footer-actions no-print" style="margin-top: 1rem; display: flex; justify-content: flex-end;">
            <button type="button" class="btn btn-primary" onclick="openModal('addItemModal')" style="font-weight: 700; display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.65rem 1.4rem; border-radius: var(--radius-md);">
                <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2.5" fill="none">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                <span>+ Tambah Data Barang</span>
            </button>
        </div>

        {{-- ===== SIGNATURE ===== --}}
        <div class="form-reg-signature" style="grid-template-columns: repeat(4, 1fr); margin-top: 1.5rem;">
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
                        Semua role (User, Staff, Accounting, Warehouse, Master) dapat saling memberikan catatan atau konfirmasi di sini.
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

{{-- ===== MODAL: TAMBAH DATA BARANG ===== --}}
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

@endsection

@section('scripts')
<script>
    const serverFormItems = @json($formItems, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    const serverFormApprovals = @json($formApprovals ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    const serverFormComments = @json($formComments ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    const allRegisteredCodes = @json($allRegisteredCodes ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    const allUnregisteredCodes = @json($allUnregisteredCodes ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    const currentUserRole = '{{ strtolower(trim(Auth::user()->role ?? "user")) }}';
    const currentUserName = '{{ Auth::user()->name ?? "User" }}';
    const currentUserDept = '{{ strtoupper(trim(Auth::user()->department ?? "PRODUCTION")) }}';
    const canViewAllDepartments = {{ $canViewAllDept ? 'true' : 'false' }};
    const activeFormNo = '{{ $currentFormNo }}';
    let selectedChecksheetId = '{{ $currentFormNo }}';

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

    function checkRegistrasiKodeBarang(val, showAlert = false) {
        const code = (val || '').trim().toUpperCase();
        const alertBox = document.getElementById('fi_kode_alert_box');
        if (!alertBox) return;

        if (!code) {
            alertBox.style.display = 'none';
            alertBox.innerHTML = '';
            return;
        }

        const unregMatch = allUnregisteredCodes.find(i => (i.kode_barang || '').trim().toUpperCase() === code);
        if (unregMatch) {
            alertBox.style.display = 'block';
            alertBox.style.background = '#fee2e2';
            alertBox.style.color = '#ef4444';
            alertBox.style.border = '1px solid #fecaca';
            alertBox.innerHTML = `🚫 <strong>PERINGATAN:</strong> Item dengan Kode Barang <strong>${escapeHtml(code)}</strong> (${escapeHtml(unregMatch.nama_barang || '')}) telah di-discontinue sebelumnya pada <strong>Form Unregistrasi ${escapeHtml(unregMatch.form_number || '')}</strong>!`;
            if (showAlert) {
                alert(`Peringatan: Item dengan Kode Barang "${code}" (${unregMatch.nama_barang || ''}) telah di-discontinue sebelumnya pada Form Unregistrasi ${unregMatch.form_number || ''}!`);
            }
            return;
        }

        const regMatch = allRegisteredCodes.find(i => (i.kode_barang || '').trim().toUpperCase() === code);
        if (regMatch) {
            alertBox.style.display = 'block';
            alertBox.style.background = '#eff6ff';
            alertBox.style.color = '#2563eb';
            alertBox.style.border = '1px solid #bfdbfe';
            alertBox.innerHTML = `ℹ <strong>Info:</strong> Kode Barang <strong>${escapeHtml(code)}</strong> sudah ada pada <strong>Form Registrasi ${escapeHtml(regMatch.form_number || '')}</strong>: ${escapeHtml(regMatch.nama_barang || '')}`;
            return;
        }

        alertBox.style.display = 'none';
        alertBox.innerHTML = '';
    }

    function printCurrentSheet() {
        window.print();
    }

    function createNewForm() {
        const dateObj = new Date();
        const mm = String(dateObj.getMonth() + 1).padStart(2, '0');
        const yyyy = dateObj.getFullYear();
        const dept = currentUserDept || 'PRODUCTION';

        // Find max sequence in current month
        let maxSeq = 0;
        serverFormItems.forEach(item => {
            if (item.form_number && item.form_number.includes('/')) {
                const parts = item.form_number.split('/');
                const seq = parseInt(parts[0], 10);
                if (!isNaN(seq) && seq > maxSeq) maxSeq = seq;
            }
        });

        const nextSeq = String(maxSeq + 1).padStart(2, '0');
        const nextFormNo = `${nextSeq}/${dept}/${mm}-${yyyy}`;

        selectedChecksheetId = nextFormNo;
        const hiddenFormNo = document.getElementById('modal_form_number');
        if (hiddenFormNo) hiddenFormNo.value = nextFormNo;

        const displayEl = document.getElementById('form-number-display');
        if (displayEl) displayEl.innerText = nextFormNo;

        const toolbarFormNo = document.getElementById('toolbar-form-no');
        if (toolbarFormNo) toolbarFormNo.innerText = nextFormNo;

        const commentsFormNo = document.getElementById('comments-form-no');
        if (commentsFormNo) commentsFormNo.innerText = nextFormNo;

        // Reset table
        const tbody = document.getElementById('preview-table-body');
        if (tbody) {
            tbody.innerHTML = `
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
                                    <h4 class="empty-state-title">Formulir Registrasi Baru (${nextFormNo})</h4>
                                    <p class="empty-state-desc">Formulir baru telah siap. Klik tombol di bawah untuk menambahkan data barang pertama.</p>
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
        }

        renderComments(nextFormNo);
        showToast(`Formulir Registrasi Baru (${nextFormNo}) Berhasil Disiapkan!`, 'success');
        openModal('addItemModal');
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
                    console.warn("QRCode error:", val, e);
                }
            }
        });
    }

    function formatCommentDate(val) {
        if (!val) return 'Baru saja';
        try {
            const d = new Date(val);
            if (isNaN(d.getTime())) return val;
            const day = String(d.getDate()).padStart(2, '0');
            const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
            const month = monthNames[d.getMonth()];
            const year = d.getFullYear();
            const hours = String(d.getHours()).padStart(2, '0');
            const minutes = String(d.getMinutes()).padStart(2, '0');
            return `${day} ${month} ${year}, ${hours}:${minutes} WIB`;
        } catch (e) {
            return val;
        }
    }

    function renderComments(formNo) {
        const listEl = document.getElementById('form-comments-list');
        const badgeEl = document.getElementById('comments-count-badge');
        const toolbarBadge = document.getElementById('toolbar-comment-count');
        if (!listEl) return;

        const comments = serverFormComments.filter(c => c.form_number === formNo);
        const count = comments.length;

        if (badgeEl) badgeEl.textContent = `${count} Komentar`;
        if (toolbarBadge) toolbarBadge.textContent = count;

        if (count === 0) {
            listEl.innerHTML = `
                <div style="text-align: center; padding: 2rem 1rem; color: var(--text-muted); background: #f8fafc; border-radius: 10px; border: 1px dashed #cbd5e1;">
                    <svg viewBox="0 0 24 24" width="28" height="28" stroke="#94a3b8" stroke-width="1.75" fill="none" style="margin-bottom: 0.4rem;"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                    <div style="font-size: 0.85rem; font-weight: 700; color: #475569;">Belum Ada Komentar</div>
                    <div style="font-size: 0.75rem; color: #94a3b8; margin-top: 0.15rem;">Jadilah yang pertama memberikan catatan atau tanggapan untuk formulir ini.</div>
                </div>
            `;
            return;
        }

        listEl.innerHTML = comments.map(c => {
            const isOwn = c.user_id === {{ Auth::id() }} || (c.user_name === '{{ Auth::user()->name }}');
            return `
                <div class="comment-bubble" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 0.9rem 1.15rem; box-shadow: 0 2px 6px rgba(0,0,0,0.03);">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.45rem;">
                        <div style="display: flex; align-items: center; gap: 0.55rem;">
                            <div style="width: 28px; height: 28px; border-radius: 50%; background: #1a3fa8; color: white; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.72rem;">
                                ${escapeHtml(c.user_name ? c.user_name.charAt(0).toUpperCase() : 'U')}
                            </div>
                            <div>
                                <span style="font-weight: 700; font-size: 0.84rem; color: #0f172a;">${escapeHtml(c.user_name)}</span>
                                <span style="font-size: 0.72rem; color: #64748b; margin-left: 0.35rem;">(${escapeHtml(c.user_role || 'User')} · ${escapeHtml(c.user_dept || '')})</span>
                            </div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <span style="font-size: 0.72rem; color: #94a3b8;">${formatCommentDate(c.created_at)}</span>
                            ${(isOwn || {{ $canViewAllDept ? 'true' : 'false' }}) ? `
                                <button type="button" onclick="deleteFormComment(${c.id})" style="background: none; border: none; color: #ef4444; font-size: 0.72rem; cursor: pointer; padding: 0 0.25rem;" title="Hapus komentar">&times;</button>
                            ` : ''}
                        </div>
                    </div>
                    <div style="font-size: 0.86rem; color: #334155; line-height: 1.5; white-space: pre-wrap;">${escapeHtml(c.comment)}</div>
                </div>
            `;
        }).join('');
    }

    function scrollToComments() {
        const sec = document.getElementById('form-comments-section');
        if (sec) sec.scrollIntoView({ behavior: 'smooth' });
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
        const text = textarea.value.trim();
        if (!text) return;

        const currentForm = selectedChecksheetId || activeFormNo;

        try {
            const response = await fetch('{{ route("form-registrasi.comments.store") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    form_number: currentForm,
                    comment: text
                })
            });

            const res = await response.json();
            if (response.ok && res.success) {
                textarea.value = '';
                serverFormComments.push(res.comment);
                renderComments(currentForm);
                showToast('Komentar berhasil dikirim!', 'success');
            } else {
                alert(res.message || 'Gagal mengirim komentar.');
            }
        } catch (e) {
            console.error(e);
            alert('Terjadi kesalahan jaringan saat mengirim komentar.');
        }
    }

    async function deleteFormComment(commentId) {
        if (!confirm('Hapus komentar ini?')) return;
        const currentForm = selectedChecksheetId || activeFormNo;

        try {
            const response = await fetch(`/form-registrasi/comments/${commentId}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });

            const res = await response.json();
            if (response.ok && res.success) {
                const idx = serverFormComments.findIndex(c => c.id === commentId);
                if (idx !== -1) serverFormComments.splice(idx, 1);
                renderComments(currentForm);
                showToast('Komentar berhasil dihapus.', 'success');
            } else {
                alert(res.message || 'Gagal menghapus komentar.');
            }
        } catch (e) {
            console.error(e);
            alert('Terjadi kesalahan saat menghapus komentar.');
        }
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

    document.addEventListener('DOMContentLoaded', function() {
        initAllSignaturesQRCodes();
        renderComments(selectedChecksheetId);
    });
</script>
@endsection
