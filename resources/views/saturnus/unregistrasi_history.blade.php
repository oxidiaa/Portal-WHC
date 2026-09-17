@extends('layouts.app')

@section('title', 'History & Data Unregistrasi Barang — SATURNUS')

@section('content')
@php
    $userDeptTag = strtoupper(Auth::user()->department ?? Auth::user()->name ?? 'PRODUCTION');
    $userRoleRaw = strtoupper(trim(Auth::user()->role ?? 'USER'));
    $canViewAllDept = in_array($userRoleRaw, ['MASTER', 'ADMIN'])
        || str_contains($userRoleRaw, 'ACCOUNTING')
        || str_contains($userRoleRaw, 'ACC')
        || str_contains($userRoleRaw, 'WAREHOUSE');

    // Aggregate statistics
    $totalItems = $formItems->count();
    $distinctForms = $formItems->pluck('form_number')->filter()->unique()->count();
    
    $completedItems = $formItems->filter(function($item) use ($formApprovals) {
        $appr = $formApprovals->firstWhere('form_number', $item->form_number);
        return (bool)($appr && ($appr->warehouse_signed_at || $appr->warehouse_signer_name));
    })->count();

    $thisMonthCount = $formItems->filter(function($item) {
        return $item->created_at && $item->created_at->isCurrentMonth();
    })->count();

    // Unique departments & categories for filters
    $departments = $formItems->pluck('created_by_dept')->filter()->unique()->values();
    $categories = $formItems->pluck('kategori')->filter()->unique()->values();
@endphp

<style>
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
    .stat-card-icon.green { background: #dcfce7; color: #16a34a; }
    .stat-card-icon.purple { background: #f3e8ff; color: #9333ea; }
    .stat-card-icon.amber { background: #fef3c7; color: #d97706; }

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

    .toolbar-filters-group {
        display: flex;
        align-items: center;
        gap: 0.65rem;
        flex-wrap: wrap;
    }

    .toolbar-select {
        height: 38px;
        border-radius: 10px;
        border: 1px solid #cbd5e1;
        padding: 0.35rem 0.85rem;
        font-size: 0.825rem;
        color: #334155;
        background: #ffffff;
        outline: none;
        cursor: pointer;
    }

    .toolbar-select:focus {
        border-color: #0284c7;
    }

    .search-input-wrapper {
        position: relative;
        min-width: 260px;
    }

    .search-input-wrapper input {
        width: 100%;
        height: 38px;
        border-radius: 10px;
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

    .btn-action-primary {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        background: #0284c7;
        color: #ffffff;
        padding: 0.5rem 1rem;
        border-radius: 10px;
        font-size: 0.825rem;
        font-weight: 700;
        text-decoration: none;
        border: none;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .btn-action-primary:hover {
        background: #0369a1;
        color: #ffffff;
        box-shadow: 0 4px 12px rgba(2, 132, 199, 0.25);
    }

    .btn-action-outline {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        color: #334155;
        padding: 0.5rem 0.85rem;
        border-radius: 10px;
        font-size: 0.825rem;
        font-weight: 600;
        text-decoration: none;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .btn-action-outline:hover {
        background: #f8fafc;
        color: #0f172a;
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
        padding: 0.95rem 1rem;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
        color: #1e293b;
    }

    .custom-table tr:hover td {
        background: #f8fafc;
    }

    .badge-unreg-code {
        font-family: monospace;
        font-weight: 700;
        background: #f1f5f9;
        color: #0284c7;
        padding: 0.2rem 0.45rem;
        border-radius: 6px;
        border: 1px solid #e2e8f0;
    }

    .badge-dept {
        display: inline-block;
        background: #f1f5f9;
        color: #334155;
        font-size: 0.75rem;
        font-weight: 700;
        padding: 0.2rem 0.55rem;
        border-radius: 6px;
    }

    .badge-status-completed {
        background: #dcfce7;
        color: #15803d;
        font-size: 0.75rem;
        font-weight: 700;
        padding: 0.25rem 0.65rem;
        border-radius: 20px;
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
    }

    .badge-status-pending {
        background: #fef3c7;
        color: #b45309;
        font-size: 0.75rem;
        font-weight: 700;
        padding: 0.25rem 0.65rem;
        border-radius: 20px;
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
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
        max-width: 620px;
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
                <div class="stat-card-label">Total Item Discontinue</div>
                <div class="stat-card-value">{{ $totalItems }}</div>
            </div>
            <div class="stat-card-icon blue">
                <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="15" y1="9" x2="9" y2="15"></line>
                    <line x1="9" y1="9" x2="15" y2="15"></line>
                </svg>
            </div>
        </div>

        <div class="stat-card-glass">
            <div class="stat-card-info">
                <div class="stat-card-label">Selesai Diverifikasi</div>
                <div class="stat-card-value" style="color: #16a34a;">{{ $completedItems }}</div>
            </div>
            <div class="stat-card-icon green">
                <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
            </div>
        </div>

        <div class="stat-card-glass">
            <div class="stat-card-info">
                <div class="stat-card-label">Total Formulir</div>
                <div class="stat-card-value" style="color: #9333ea;">{{ $distinctForms }}</div>
            </div>
            <div class="stat-card-icon purple">
                <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                </svg>
            </div>
        </div>

        <div class="stat-card-glass">
            <div class="stat-card-info">
                <div class="stat-card-label">Pengajuan Bulan Ini</div>
                <div class="stat-card-value" style="color: #d97706;">{{ $thisMonthCount }}</div>
            </div>
            <div class="stat-card-icon amber">
                <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
            </div>
        </div>
    </div>

    {{-- TABLE CONTAINER --}}
    <div class="table-container-card">
        <div class="table-toolbar">
            <div class="toolbar-filters-group">
                {{-- Search --}}
                <div class="search-input-wrapper">
                    <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <input type="text" id="historySearchInput" placeholder="Cari kode, nama barang, nomor form...">
                </div>

                {{-- Dept Filter --}}
                <select id="filterDeptSelect" class="toolbar-select">
                    <option value="">Semua Departemen</option>
                    @foreach($departments as $dept)
                        <option value="{{ strtolower($dept) }}">{{ $dept }}</option>
                    @endforeach
                </select>

                {{-- Status Filter --}}
                <select id="filterStatusSelect" class="toolbar-select">
                    <option value="">Semua Status</option>
                    <option value="completed">Selesai Discontinue</option>
                    <option value="pending">Dalam Proses Approval</option>
                </select>
            </div>

            <div class="d-flex align-items-center gap-2">
                {{-- Export Excel --}}
                <a href="{{ route('saturnus.unregistrasi.export') }}" class="btn-action-primary" title="Download Excel">
                    <svg viewBox="0 0 24 24" width="15" height="15" stroke="currentColor" stroke-width="2.5" fill="none">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="7 10 12 15 17 10"></polyline>
                        <line x1="12" y1="15" x2="12" y2="3"></line>
                    </svg>
                    <span>Export Excel</span>
                </a>

                {{-- Print View --}}
                <button type="button" class="btn-action-outline" onclick="window.print()" title="Cetak Tabel">
                    <svg viewBox="0 0 24 24" width="15" height="15" stroke="currentColor" stroke-width="2" fill="none">
                        <polyline points="6 9 6 2 18 2 18 9"></polyline>
                        <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                        <rect x="6" y="14" width="12" height="8"></rect>
                    </svg>
                    <span>Print</span>
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="custom-table" id="historyTable">
                <thead>
                    <tr>
                        <th style="width: 50px;">No</th>
                        <th style="width: 130px;">No. Formulir</th>
                        <th style="width: 130px;">Kode Barang</th>
                        <th>Nama Barang &amp; Spesifikasi</th>
                        <th style="width: 120px;">Departemen</th>
                        <th>Alasan Discontinue</th>
                        <th style="width: 160px;">Status Discontinue</th>
                        <th style="width: 100px; text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($formItems as $index => $item)
                        @php
                            $approval = $formApprovals->firstWhere('form_number', $item->form_number);
                            $isCompleted = (bool)($approval && ($approval->warehouse_signed_at || $approval->warehouse_signer_name));
                            $statusType = $isCompleted ? 'completed' : 'pending';
                        @endphp
                        <tr class="history-row"
                            data-dept="{{ strtolower($item->created_by_dept ?? '') }}"
                            data-status="{{ $statusType }}"
                            data-search="{{ strtolower(($item->form_number ?? '') . ' ' . ($item->kode_barang ?? '') . ' ' . ($item->nama_barang ?? '') . ' ' . ($item->spesifikasi ?? '') . ' ' . ($item->created_by_dept ?? '') . ' ' . ($item->keterangan ?? '') . ' ' . ($item->created_by_name ?? '')) }}">
                            
                            {{-- No --}}
                            <td style="color: #64748b; font-weight: 600;">{{ $index + 1 }}</td>

                            {{-- Form Number --}}
                            <td>
                                <a href="{{ route('saturnus.form_unregistrasi', ['form' => $item->form_number]) }}" style="font-weight: 700; color: #0284c7; text-decoration: none;">
                                    {{ $item->form_number ?? '-' }}
                                </a>
                                <div style="font-size: 0.725rem; color: #64748b;">
                                    {{ $item->created_at ? $item->created_at->format('d M Y') : '-' }}
                                </div>
                            </td>

                            {{-- Kode Barang --}}
                            <td>
                                <span class="badge-unreg-code">{{ $item->kode_barang ?? '-' }}</span>
                            </td>

                            {{-- Nama Barang & Spesifikasi --}}
                            <td>
                                <div style="font-weight: 700; color: #0f172a; font-size: 0.875rem;">
                                    {{ $item->nama_barang }}
                                </div>
                                <div style="font-size: 0.775rem; color: #64748b; margin-top: 0.15rem;">
                                    {{ $item->spesifikasi ?: 'Tidak ada spesifikasi khusus' }}
                                    @if($item->kategori)
                                        · <span style="color: #334155; font-weight: 600;">{{ $item->kategori }}</span>
                                    @endif
                                </div>
                            </td>

                            {{-- Departemen --}}
                            <td>
                                <span class="badge-dept">{{ $item->created_by_dept ?? '-' }}</span>
                                <div style="font-size: 0.725rem; color: #64748b; margin-top: 0.15rem;">
                                    {{ $item->created_by_name ?? '-' }}
                                </div>
                            </td>

                            {{-- Alasan Discontinue --}}
                            <td>
                                <div style="font-size: 0.8rem; color: #334155; max-width: 280px;">
                                    {{ $item->keterangan ?: '-' }}
                                </div>
                            </td>

                            {{-- Status Discontinue --}}
                            <td>
                                @if($isCompleted)
                                    <span class="badge-status-completed">
                                        ✓ Selesai Discontinue
                                    </span>
                                    <div style="font-size: 0.725rem; color: #64748b; margin-top: 0.2rem;">
                                        Oleh: {{ $approval->warehouse_signer_name ?? 'WHC' }}
                                    </div>
                                @else
                                    <span class="badge-status-pending">
                                        ⏳ {{ $approval?->status ?? 'Dalam Proses' }}
                                    </span>
                                @endif
                            </td>

                            {{-- Aksi --}}
                            <td style="text-align: center;">
                                <div class="d-flex align-items-center justify-content-center gap-1">
                                    <button type="button" class="btn-action-outline" style="padding: 0.35rem 0.65rem;" title="Lihat Detail"
                                            onclick="openHistoryDetailModal('{{ $item->form_number }}', '{{ $item->created_by_dept }}', '{{ $item->created_by_name }}', '{{ $item->created_at ? $item->created_at->format('d M Y') : '-' }}', '{{ $item->kode_barang }}', '{{ addslashes($item->nama_barang) }}', '{{ addslashes($item->spesifikasi ?? '') }}', '{{ $item->kategori ?? '' }}', '{{ addslashes($item->keterangan ?? '') }}', '{{ $approval?->status ?? 'Dalam Proses' }}', '{{ $approval?->staff_signer_name ?? '' }}', '{{ $approval?->staff_signed_at ? Carbon\Carbon::parse($approval->staff_signed_at)->format('d M Y, H:i') : '' }}', '{{ $approval?->warehouse_signer_name ?? '' }}', '{{ $approval?->warehouse_signed_at ? Carbon\Carbon::parse($approval->warehouse_signed_at)->format('d M Y, H:i') : '' }}')">
                                        <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                            <circle cx="12" cy="12" r="3"></circle>
                                        </svg>
                                    </button>

                                    <a href="{{ route('saturnus.form_unregistrasi', ['form' => $item->form_number, 'print' => 1]) }}" target="_blank" class="btn-action-outline" style="padding: 0.35rem 0.65rem;" title="Cetak Formulir">
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
                            <td colspan="8" style="text-align: center; padding: 3rem 1rem; color: #94a3b8;">
                                <svg viewBox="0 0 24 24" width="48" height="48" stroke="currentColor" stroke-width="1.5" fill="none" style="margin-bottom: 0.75rem; color: #cbd5e1;">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                                <div style="font-weight: 700; color: #475569; font-size: 1rem;">Belum Ada Riwayat Unregistrasi</div>
                                <p style="font-size: 0.85rem; margin-top: 0.25rem;">Data barang yang di-unregistrasi akan muncul di halaman riwayat ini.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

{{-- MODAL DETAIL HISTORY --}}
<div class="modal" id="modalHistoryDetail">
    <div class="modal-content-custom" style="max-width: 650px;">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div class="d-flex align-items-center gap-2">
                <div style="width: 36px; height: 36px; border-radius: 10px; background: #e0f2fe; color: #0284c7; display: flex; align-items: center; justify-content: center;">
                    <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                </div>
                <div>
                    <h5 style="font-weight: 800; color: #0f172a; margin: 0; font-size: 1.15rem;">Detail Riwayat Discontinue</h5>
                    <div style="font-size: 0.775rem; color: #64748b;" id="hdtFormNo">-</div>
                </div>
            </div>
            <button type="button" class="btn-close" onclick="closeHistoryDetailModal()" style="border: none; background: none; font-size: 1.25rem; cursor: pointer; color: #94a3b8;">&times;</button>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.85rem; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1rem; margin-bottom: 1rem;">
            <div>
                <span style="font-size: 0.725rem; color: #64748b; font-weight: 700; text-transform: uppercase;">Departemen:</span>
                <div style="font-weight: 700; color: #0f172a;" id="hdtDept">-</div>
            </div>
            <div>
                <span style="font-size: 0.725rem; color: #64748b; font-weight: 700; text-transform: uppercase;">Pemohon:</span>
                <div style="font-weight: 700; color: #0f172a;" id="hdtReq">-</div>
            </div>
            <div>
                <span style="font-size: 0.725rem; color: #64748b; font-weight: 700; text-transform: uppercase;">Tanggal Pengajuan:</span>
                <div style="font-weight: 700; color: #0f172a;" id="hdtDate">-</div>
            </div>
            <div>
                <span style="font-size: 0.725rem; color: #64748b; font-weight: 700; text-transform: uppercase;">Status:</span>
                <div style="font-weight: 700; color: #0284c7;" id="hdtStatus">-</div>
            </div>
        </div>

        <h6 style="font-weight: 800; color: #0f172a; margin-bottom: 0.5rem; font-size: 0.9rem;">Informasi Item Consumable</h6>
        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1rem; margin-bottom: 1.25rem;">
            <div style="font-size: 0.775rem; color: #64748b;">Kode Barang: <strong style="color: #0284c7; font-family: monospace;" id="hdtItemCode">-</strong></div>
            <div style="font-size: 1.05rem; font-weight: 800; color: #0f172a; margin-top: 0.2rem;" id="hdtItemName">-</div>
            <div style="font-size: 0.825rem; color: #475569; margin-top: 0.25rem;" id="hdtItemSpec">Spesifikasi: -</div>
            <div style="font-size: 0.825rem; color: #475569; margin-top: 0.25rem;" id="hdtItemCategory">Kategori: -</div>
            
            <div style="margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px dashed #e2e8f0;">
                <span style="font-size: 0.725rem; font-weight: 700; color: #64748b; text-transform: uppercase;">Alasan Discontinue:</span>
                <div style="font-size: 0.85rem; color: #334155; font-style: italic; margin-top: 0.2rem;" id="hdtItemReason">-</div>
            </div>
        </div>

        <h6 style="font-weight: 800; color: #0f172a; margin-bottom: 0.5rem; font-size: 0.9rem;">Audit Trail &amp; Approval Log</h6>
        <div style="display: flex; flex-direction: column; gap: 0.5rem; margin-bottom: 1.25rem;">
            <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.65rem 0.85rem; background: #f8fafc; border-radius: 10px; border: 1px solid #e2e8f0;">
                <div>
                    <strong style="color: #0f172a; font-size: 0.825rem;">Staff Approver (Tahap 1):</strong>
                    <div style="font-size: 0.75rem; color: #64748b;" id="hdtStaffSigner">-</div>
                </div>
                <span style="font-size: 0.75rem; font-weight: 700;" id="hdtStaffDate">-</span>
            </div>

            <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.65rem 0.85rem; background: #f8fafc; border-radius: 10px; border: 1px solid #e2e8f0;">
                <div>
                    <strong style="color: #0f172a; font-size: 0.825rem;">Warehouse Consumable (Tahap 2):</strong>
                    <div style="font-size: 0.75rem; color: #64748b;" id="hdtWhSigner">-</div>
                </div>
                <span style="font-size: 0.75rem; font-weight: 700;" id="hdtWhDate">-</span>
            </div>
        </div>

        <div class="d-flex align-items-center justify-content-end gap-2">
            <button type="button" class="btn-action-outline" onclick="closeHistoryDetailModal()">Tutup</button>
            <a href="#" id="hdtPrintLink" target="_blank" class="btn-action-primary">
                <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none">
                    <polyline points="6 9 6 2 18 2 18 9"></polyline>
                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                    <rect x="6" y="14" width="12" height="8"></rect>
                </svg>
                <span>Cetak Lembar Dokumen</span>
            </a>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('historySearchInput');
        const deptSelect = document.getElementById('filterDeptSelect');
        const statusSelect = document.getElementById('filterStatusSelect');
        const tableRows = document.querySelectorAll('#historyTable tbody tr.history-row');

        function filterHistory() {
            const query = searchInput ? searchInput.value.toLowerCase().trim() : '';
            const selDept = deptSelect ? deptSelect.value.toLowerCase().trim() : '';
            const selStatus = statusSelect ? statusSelect.value.toLowerCase().trim() : '';

            tableRows.forEach(row => {
                const rowDept = row.getAttribute('data-dept') || '';
                const rowStatus = row.getAttribute('data-status') || '';
                const rowSearch = row.getAttribute('data-search') || '';

                const matchesQuery = !query || rowSearch.includes(query);
                const matchesDept = !selDept || rowDept.includes(selDept);
                const matchesStatus = !selStatus || rowStatus === selStatus;

                if (matchesQuery && matchesDept && matchesStatus) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        if (searchInput) searchInput.addEventListener('input', filterHistory);
        if (deptSelect) deptSelect.addEventListener('change', filterHistory);
        if (statusSelect) statusSelect.addEventListener('change', filterHistory);
    });

    function openHistoryDetailModal(formNo, dept, req, date, code, name, spec, cat, reason, status, staffSigner, staffDate, whSigner, whDate) {
        document.getElementById('hdtFormNo').innerText = 'Formulir: ' + formNo;
        document.getElementById('hdtDept').innerText = dept;
        document.getElementById('hdtReq').innerText = req;
        document.getElementById('hdtDate').innerText = date;
        document.getElementById('hdtStatus').innerText = status;
        document.getElementById('hdtItemCode').innerText = code || '-';
        document.getElementById('hdtItemName').innerText = name || '-';
        document.getElementById('hdtItemSpec').innerText = 'Spesifikasi: ' + (spec || '-');
        document.getElementById('hdtItemCategory').innerText = 'Kategori: ' + (cat || '-');
        document.getElementById('hdtItemReason').innerText = reason ? ('"' + reason + '"') : 'Tidak ada alasan khusus dicantumkan.';

        document.getElementById('hdtStaffSigner').innerText = staffSigner ? ('Disetujui oleh: ' + staffSigner) : 'Menunggu Persetujuan Staff';
        document.getElementById('hdtStaffDate').innerText = staffDate || '-';

        document.getElementById('hdtWhSigner').innerText = whSigner ? ('Discontinue oleh: ' + whSigner) : 'Menunggu Verifikasi Warehouse';
        document.getElementById('hdtWhDate').innerText = whDate || '-';

        document.getElementById('hdtPrintLink').href = "{{ url('/saturnus/form-unregistrasi') }}?form=" + encodeURIComponent(formNo) + "&print=1";

        document.getElementById('modalHistoryDetail').classList.add('show');
    }

    function closeHistoryDetailModal() {
        document.getElementById('modalHistoryDetail').classList.remove('show');
    }

    window.addEventListener('click', function(e) {
        const modal = document.getElementById('modalHistoryDetail');
        if (e.target === modal) closeHistoryDetailModal();
    });
</script>
@endsection
