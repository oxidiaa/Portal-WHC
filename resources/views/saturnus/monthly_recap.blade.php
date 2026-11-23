@extends('layouts.app')

@section('title', 'Monthly Asset Recap — SATURNUS')

@section('content')
<style>
    /* ==========================================================================
       SCREEN STYLES — SATURNUS MODERN ENTERPRISE FUTURISTIC SPACE THEME
       ========================================================================== */
    .recap-stat-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 1.15rem;
        margin-bottom: 1.5rem;
    }

    @media (max-width: 1200px) {
        .recap-stat-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 640px) {
        .recap-stat-grid {
            grid-template-columns: 1fr;
        }
    }

    .recap-stat-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 1.2rem 1.35rem;
        box-shadow: 0 4px 18px rgba(15, 23, 42, 0.04);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        position: relative;
        overflow: hidden;
    }

    .recap-stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 26px rgba(15, 23, 42, 0.08);
    }

    .recap-stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
    }

    .recap-stat-card.accent-indigo::before { background: linear-gradient(90deg, #4f46e5, #06b6d4); }
    .recap-stat-card.accent-emerald::before { background: linear-gradient(90deg, #059669, #34d399); }
    .recap-stat-card.accent-amber::before { background: linear-gradient(90deg, #d97706, #f59e0b); }
    .recap-stat-card.accent-purple::before { background: linear-gradient(90deg, #7c3aed, #ec4899); }

    .recap-stat-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 0.75rem;
    }

    .recap-stat-label {
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: #64748b;
        margin-bottom: 0.35rem;
    }

    .recap-stat-value {
        font-size: 1.9rem;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.1;
        font-family: 'Space Grotesk', 'Outfit', sans-serif;
    }

    .recap-stat-sub {
        font-size: 0.78rem;
        color: #64748b;
        margin-top: 0.45rem;
    }

    .recap-stat-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .recap-stat-icon.indigo { background: #eef2ff; color: #4f46e5; }
    .recap-stat-icon.emerald { background: #ecfdf5; color: #059669; }
    .recap-stat-icon.amber { background: #fffbeb; color: #d97706; }
    .recap-stat-icon.purple { background: #f5f3ff; color: #7c3aed; }

    .recap-activity-bar {
        height: 8px;
        border-radius: 999px;
        background: #f1f5f9;
        overflow: hidden;
        display: flex;
        margin-top: 0.5rem;
    }

    .recap-activity-bar .seg-reg {
        background: #10b981;
        height: 100%;
        transition: width 0.35s ease;
    }

    .recap-activity-bar .seg-unreg {
        background: #f59e0b;
        height: 100%;
        transition: width 0.35s ease;
    }

    .recap-toolbar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1.25rem;
    }

    .recap-filter-form {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.65rem;
    }

    .recap-table-wrap {
        overflow-x: auto;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
    }

    .recap-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.85rem;
    }

    .recap-table thead th {
        background: #0f172a;
        color: #f8fafc;
        font-weight: 700;
        font-size: 0.76rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        padding: 0.8rem 0.75rem;
        border-bottom: 2px solid #1e293b;
        white-space: nowrap;
        text-align: left;
    }

    .recap-table tbody td {
        padding: 0.75rem;
        border-bottom: 1px solid #e2e8f0;
        color: #1e293b;
        vertical-align: middle;
    }

    .recap-table tbody tr:hover {
        background-color: #f8fafc;
    }

    .recap-manual-cell {
        background-color: #fcfcfd;
        border-left: 1px dashed #cbd5e1;
        min-height: 32px;
    }

    .recap-period-banner {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        background: linear-gradient(135deg, rgba(15, 23, 42, 0.94), rgba(30, 41, 59, 0.94));
        color: #f8fafc;
        padding: 0.45rem 1rem;
        border-radius: 999px;
        font-size: 0.82rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        border: 1px solid rgba(56, 189, 248, 0.35);
    }

    /* Print container hidden on screen */
    .print-only-report {
        display: none;
    }

    /* ==========================================================================
       PRINT STYLES — A4 LANDSCAPE, INK-SAVING, HANDWRITING-READY
       ========================================================================== */
    @media print {
        @page {
            size: A4 landscape;
            margin: 8mm 8mm 10mm 8mm;
        }

        html, body {
            background: #ffffff !important;
            color: #000000 !important;
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
        }

        .sidebar,
        .navbar,
        .footer,
        footer,
        .no-print,
        .screen-only-dashboard,
        .theme-customizer-fab,
        .mai-toast-container,
        .floating-sidebar-toggle {
            display: none !important;
        }

        .main-wrapper,
        .page-wrapper,
        .page-content {
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
            max-width: 100% !important;
            background: #ffffff !important;
            box-shadow: none !important;
        }

        .print-only-report {
            display: block !important;
            width: 100%;
            background: #ffffff !important;
            color: #000000 !important;
            font-family: Arial, Helvetica, sans-serif !important;
        }

        .print-header-box {
            border-bottom: 2px solid #000000;
            padding-bottom: 8px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }

        .print-brand-title {
            font-size: 15pt;
            font-weight: 800;
            letter-spacing: 0.06em;
            margin: 0;
            color: #000000;
        }

        .print-brand-sub {
            font-size: 7.5pt;
            font-weight: 600;
            margin: 2px 0 6px 0;
            color: #000000;
        }

        .print-report-title {
            font-size: 12pt;
            font-weight: 800;
            margin: 0;
            color: #000000;
        }

        .print-period-text {
            font-size: 9.5pt;
            font-weight: 700;
            margin: 2px 0 0 0;
            color: #000000;
        }

        .print-summary-box {
            text-align: right;
            font-size: 8.5pt;
            line-height: 1.45;
            color: #000000;
        }

        .print-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 8pt;
            color: #000000;
        }

        .print-table thead {
            display: table-header-group;
        }

        .print-table tfoot {
            display: table-footer-group;
        }

        .print-table tr {
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .print-table th {
            background-color: #f1f5f9 !important;
            color: #000000 !important;
            font-weight: 700;
            border: 1px solid #000000;
            padding: 6px 5px;
            text-align: center;
            font-size: 7.8pt;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .print-table td {
            border: 1px solid #000000;
            padding: 9px 5px;
            height: 28px;
            vertical-align: middle;
            word-wrap: break-word;
            overflow-wrap: break-word;
            color: #000000 !important;
            background: #ffffff !important;
        }

        .print-footer-note {
            margin-top: 8px;
            font-size: 7.5pt;
            color: #000000;
            display: flex;
            justify-content: space-between;
        }
    }
</style>

{{-- ==========================================================================
     ON-SCREEN ENTERPRISE DASHBOARD
     ========================================================================== --}}
<div class="workspace-light-theme screen-only-dashboard no-print">

    {{-- Header --}}
    <div class="header" style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;">
        <div class="header-title">
            <div class="galactic-badge" style="margin-bottom: 0.45rem;">
                <span class="pulse-beacon"></span>
                <span>SATURNUS MONTHLY REPORTING HUB</span>
            </div>
            <div style="display: flex; align-items: center; gap: 0.85rem; flex-wrap: wrap;">
                <h1 class="galactic-title" style="font-size: 1.65rem; margin: 0;">MONTHLY ASSET RECAP</h1>
                <span class="recap-period-banner">
                    <i data-feather="calendar" style="width: 14px; height: 14px; color: #38bdf8;"></i>
                    {{ $periodLabel }}
                </span>
            </div>
            <p class="galactic-subtitle" style="margin-top: 0.35rem; margin-bottom: 0;">
                Rekapitulasi bulanan seluruh barang yang telah selesai melalui proses Registration dan Unregistration.
            </p>
        </div>

        {{-- Export & Print Actions --}}
        <div style="display: flex; align-items: center; gap: 0.6rem; flex-wrap: wrap;">
            <a href="{{ route('saturnus.monthly_recap.export_excel', request()->query()) }}"
               class="btn btn-success d-inline-flex align-items-center gap-2"
               style="border-radius: 10px; font-weight: 600; padding: 0.55rem 1rem;">
                <i data-feather="file-spreadsheet" style="width: 16px; height: 16px;"></i>
                <span>Export Excel</span>
            </a>

            <a href="{{ route('saturnus.monthly_recap.export_pdf', request()->query()) }}"
               class="btn btn-danger d-inline-flex align-items-center gap-2"
               style="border-radius: 10px; font-weight: 600; padding: 0.55rem 1rem;">
                <i data-feather="file-text" style="width: 16px; height: 16px;"></i>
                <span>Export PDF</span>
            </a>

            <button type="button"
                    onclick="window.print()"
                    class="btn btn-dark d-inline-flex align-items-center gap-2"
                    style="border-radius: 10px; font-weight: 600; padding: 0.55rem 1rem;">
                <i data-feather="printer" style="width: 16px; height: 16px;"></i>
                <span>Print Monthly Recap</span>
            </button>
        </div>
    </div>

    {{-- 4 Summary Cards --}}
    @php
        $regPct = $totalProcessed > 0 ? round(($totalRegistration / $totalProcessed) * 100, 1) : 0;
        $unregPct = $totalProcessed > 0 ? round(($totalUnregistration / $totalProcessed) * 100, 1) : 0;
    @endphp
    <div class="recap-stat-grid">
        {{-- A. Total Processed Items --}}
        <div class="recap-stat-card accent-indigo">
            <div class="recap-stat-top">
                <div>
                    <div class="recap-stat-label">Total Processed Items</div>
                    <div class="recap-stat-value">{{ number_format($totalProcessed) }}</div>
                </div>
                <div class="recap-stat-icon indigo">
                    <i data-feather="layers" style="width: 22px; height: 22px;"></i>
                </div>
            </div>
            <div class="recap-stat-sub">
                Seluruh item selesai pada <strong>{{ $periodLabel }}</strong>
            </div>
        </div>

        {{-- B. Total Registration --}}
        <div class="recap-stat-card accent-emerald">
            <div class="recap-stat-top">
                <div>
                    <div class="recap-stat-label">Total Registration</div>
                    <div class="recap-stat-value text-success">{{ number_format($totalRegistration) }}</div>
                </div>
                <div class="recap-stat-icon emerald">
                    <i data-feather="plus-circle" style="width: 22px; height: 22px;"></i>
                </div>
            </div>
            <div class="recap-stat-sub">
                Item berhasil diregistrasi ({{ $regPct }}%)
            </div>
        </div>

        {{-- C. Total Unregistration --}}
        <div class="recap-stat-card accent-amber">
            <div class="recap-stat-top">
                <div>
                    <div class="recap-stat-label">Total Unregistration</div>
                    <div class="recap-stat-value" style="color: #d97706;">{{ number_format($totalUnregistration) }}</div>
                </div>
                <div class="recap-stat-icon amber">
                    <i data-feather="minus-circle" style="width: 22px; height: 22px;"></i>
                </div>
            </div>
            <div class="recap-stat-sub">
                Item berhasil diunregistrasi ({{ $unregPct }}%)
            </div>
        </div>

        {{-- D. Monthly Activity --}}
        <div class="recap-stat-card accent-purple">
            <div class="recap-stat-top">
                <div style="width: 100%;">
                    <div class="recap-stat-label">Monthly Activity</div>
                    <div style="display: flex; justify-content: space-between; font-size: 0.82rem; font-weight: 700; color: #0f172a;">
                        <span style="color: #059669;">Reg: {{ $totalRegistration }}</span>
                        <span style="color: #d97706;">Unreg: {{ $totalUnregistration }}</span>
                    </div>
                    <div class="recap-activity-bar" title="Registration {{ $regPct }}% vs Unregistration {{ $unregPct }}%">
                        <div class="seg-reg" style="width: {{ $regPct }}%;"></div>
                        <div class="seg-unreg" style="width: {{ $unregPct }}%;"></div>
                    </div>
                </div>
                <div class="recap-stat-icon purple">
                    <i data-feather="activity" style="width: 22px; height: 22px;"></i>
                </div>
            </div>
            <div class="recap-stat-sub">
                Rasio aktivitas bulanan Registration vs Unregistration
            </div>
        </div>
    </div>

    {{-- Monthly Activity Chart --}}
    <div class="glass-card" style="padding: 1.35rem 1.5rem; margin-bottom: 1.5rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem; margin-bottom: 0.75rem;">
            <div>
                <h3 style="font-weight: 700; font-size: 1.05rem; color: #0f172a; margin: 0;">
                    Monthly Activity Chart — {{ $periodLabel }}
                </h3>
                <p style="font-size: 0.8rem; color: #64748b; margin: 0.15rem 0 0 0;">
                    Perbandingan harian jumlah item Registration dan Unregistration yang berhasil diproses.
                </p>
            </div>
            <div style="display: flex; align-items: center; gap: 1rem; font-size: 0.8rem; font-weight: 600;">
                <span class="d-inline-flex align-items-center gap-1">
                    <span style="width: 10px; height: 10px; border-radius: 3px; background: #10b981; display: inline-block;"></span>
                    Registration
                </span>
                <span class="d-inline-flex align-items-center gap-1">
                    <span style="width: 10px; height: 10px; border-radius: 3px; background: #f59e0b; display: inline-block;"></span>
                    Unregistration
                </span>
            </div>
        </div>
        <div id="monthlyActivityChart" style="min-height: 250px;"></div>
    </div>

    {{-- Main Table Card --}}
    <div class="glass-card" style="padding: 1.5rem;">
        <div class="recap-toolbar">
            <div>
                <h3 style="font-weight: 700; font-size: 1.1rem; color: #0f172a; margin: 0;">
                    MONTHLY ASSET RECAP — {{ $periodLabel }}
                </h3>
                <p style="font-size: 0.8rem; color: #64748b; margin: 0.2rem 0 0 0;">
                    Menampilkan <strong>{{ $items->total() }}</strong> item
                    @if($items->total() !== $allPeriodRowsCount)
                        (difilter dari total {{ $allPeriodRowsCount }} item pada periode {{ $periodLabel }})
                    @endif
                    — Kolom <em>Location</em> dan <em>Qty Saat Ini</em> sengaja dikosongkan untuk pengisian manual setelah dicetak.
                </p>
            </div>

            {{-- Period, Type & Search Filters --}}
            <form method="GET" action="{{ route('saturnus.monthly_recap') }}" class="recap-filter-form">
                <select name="month" class="form-select" style="width: 140px; height: 38px; font-size: 0.85rem;" onchange="this.form.submit()" aria-label="Pilih Bulan">
                    @foreach($monthNames as $mNum => $mName)
                        <option value="{{ $mNum }}" {{ $month === $mNum ? 'selected' : '' }}>{{ $mName }}</option>
                    @endforeach
                </select>

                <select name="year" class="form-select" style="width: 100px; height: 38px; font-size: 0.85rem;" onchange="this.form.submit()" aria-label="Pilih Tahun">
                    @foreach($years as $yOpt)
                        <option value="{{ $yOpt }}" {{ $year === $yOpt ? 'selected' : '' }}>{{ $yOpt }}</option>
                    @endforeach
                </select>

                <select name="type" class="form-select" style="width: 165px; height: 38px; font-size: 0.85rem;" onchange="this.form.submit()" aria-label="Filter Transaction Type">
                    <option value="all" {{ $type === 'all' ? 'selected' : '' }}>Semua Tipe Transaksi</option>
                    <option value="registration" {{ $type === 'registration' ? 'selected' : '' }}>Registration</option>
                    <option value="unregistration" {{ $type === 'unregistration' ? 'selected' : '' }}>Unregistration</option>
                </select>

                <div class="input-group" style="width: 260px;">
                    <input type="text"
                           name="search"
                           value="{{ $search }}"
                           class="form-control"
                           placeholder="Cari No Form, Kode, Nama..."
                           style="height: 38px; font-size: 0.85rem;">
                    <button type="submit" class="btn btn-primary" style="height: 38px; padding: 0 0.85rem;">
                        <i data-feather="search" style="width: 15px; height: 15px;"></i>
                    </button>
                </div>

                @if($search !== '' || $type !== 'all')
                    <a href="{{ route('saturnus.monthly_recap', ['month' => $month, 'year' => $year]) }}"
                       class="btn btn-outline-secondary"
                       style="height: 38px; font-size: 0.82rem; display: inline-flex; align-items: center;">
                        Reset
                    </a>
                @endif
            </form>
        </div>

        {{-- Exact 9-Column Table --}}
        <div class="recap-table-wrap">
            <table class="recap-table">
                <thead>
                    <tr>
                        <th style="min-width: 155px;">No Form</th>
                        <th style="min-width: 95px; text-align: center;">Date</th>
                        <th style="min-width: 130px;">Kode Barang</th>
                        <th style="min-width: 190px;">Nama Barang</th>
                        <th style="min-width: 120px; text-align: right;">Harga</th>
                        <th style="min-width: 130px; text-align: center;">Estimasi Usia Pakai</th>
                        <th style="min-width: 140px;">Department</th>
                        <th style="min-width: 150px;">Location</th>
                        <th style="min-width: 110px; text-align: center;">Qty Saat Ini</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $row)
                        <tr>
                            <td style="font-family: 'JetBrains Mono', monospace; font-size: 0.8rem; font-weight: 600;">
                                <div>{{ $row['no_form'] }}</div>
                                <span style="display: inline-block; margin-top: 3px; padding: 1px 8px; border-radius: 999px; font-family: 'Outfit', sans-serif; font-size: 0.68rem; font-weight: 700; letter-spacing: 0.03em; {{ $row['transaction_type'] === 'registration' ? 'background: #dcfce7; color: #166534; border: 1px solid #bbf7d0;' : 'background: #fef3c7; color: #92400e; border: 1px solid #fde68a;' }}">
                                    {{ $row['transaction_label'] ?? ($row['transaction_type'] === 'unregistration' ? 'Unregistrasi' : 'Registrasi') }}
                                </span>
                            </td>
                            <td style="text-align: center; white-space: nowrap;">
                                {{ $row['date_formatted'] }}
                            </td>
                            <td style="font-family: 'JetBrains Mono', monospace; font-size: 0.8rem;">
                                {{ $row['kode_barang'] }}
                            </td>
                            <td style="font-weight: 600;">
                                {{ $row['nama_barang'] }}
                            </td>
                            <td style="text-align: right; white-space: nowrap; font-weight: 600;">
                                {{ $row['harga_formatted'] }}
                            </td>
                            <td style="text-align: center; white-space: nowrap;">
                                {{ $row['estimasi_usia_pakai'] }}
                            </td>
                            <td>
                                {{ $row['department'] }}
                            </td>
                            <td class="recap-manual-cell">&nbsp;</td>
                            <td class="recap-manual-cell">&nbsp;</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 3rem 1rem; color: #64748b;">
                                <div style="display: flex; flex-direction: column; align-items: center; gap: 0.5rem;">
                                    <i data-feather="inbox" style="width: 36px; height: 36px; color: #94a3b8;"></i>
                                    <div style="font-weight: 700; font-size: 0.95rem; color: #334155;">
                                        Tidak ada transaksi pada periode {{ $periodLabel }}
                                    </div>
                                    <div style="font-size: 0.82rem;">
                                        Belum ada item Registration atau Unregistration yang selesai diproses pada bulan dan filter terpilih.
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Table Footer: Total & Pagination --}}
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem; margin-top: 1.15rem;">
            <div style="font-size: 0.82rem; color: #64748b;">
                Total Data Ditampilkan: <strong>{{ $items->total() }}</strong> item
            </div>
            <div>
                {{ $items->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
</div>

{{-- ==========================================================================
     PRINT-ONLY REPORT (A4 LANDSCAPE, INK-SAVING, MANUAL HANDWRITING SPACE)
     ========================================================================== --}}
<div class="print-only-report">
    <div class="print-header-box">
        <div>
            <p class="print-brand-title">SATURNUS</p>
            <p class="print-brand-sub">SMART ASSET TRACKING, UNREGISTRATION &amp; REGISTRATION NETWORK UTILITY SYSTEM</p>
            <p class="print-report-title">MONTHLY ASSET RECAP</p>
            <p class="print-period-text">Reporting Period: {{ $periodLabel }}</p>
        </div>
        <div class="print-summary-box">
            <div><strong>Total Registration:</strong> {{ number_format($totalRegistration) }}</div>
            <div><strong>Total Unregistration:</strong> {{ number_format($totalUnregistration) }}</div>
            <div><strong>Total Processed Items:</strong> {{ number_format($totalProcessed) }}</div>
        </div>
    </div>

    <table class="print-table">
        <thead>
            <tr>
                <th style="width: 14%;">No Form</th>
                <th style="width: 7%;">Date</th>
                <th style="width: 11%;">Kode Barang</th>
                <th style="width: 16%;">Nama Barang</th>
                <th style="width: 9%;">Harga</th>
                <th style="width: 8%;">Estimasi Usia Pakai</th>
                <th style="width: 9%;">Department</th>
                <th style="width: 16%;">Location</th>
                <th style="width: 10%;">Qty Saat Ini</th>
            </tr>
        </thead>
        <tbody>
            @forelse($printRows as $row)
                <tr>
                    <td>
                        <div>{{ $row['no_form'] }}</div>
                        <div style="font-size: 6.8pt; font-weight: 700; margin-top: 2px;">({{ $row['transaction_label'] ?? ($row['transaction_type'] === 'unregistration' ? 'Unregistrasi' : 'Registrasi') }})</div>
                    </td>
                    <td style="text-align: center;">{{ $row['date_formatted'] }}</td>
                    <td>{{ $row['kode_barang'] }}</td>
                    <td>{{ $row['nama_barang'] }}</td>
                    <td style="text-align: right;">{{ $row['harga_formatted'] }}</td>
                    <td style="text-align: center;">{{ $row['estimasi_usia_pakai'] }}</td>
                    <td>{{ $row['department'] }}</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" style="text-align: center; padding: 18px;">
                        Tidak ada transaksi Registration maupun Unregistration yang selesai diproses pada periode {{ $periodLabel }}.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="print-footer-note">
        <span>SATURNUS — Monthly Asset Recap ({{ $periodLabel }})</span>
        <span>Kolom Location dan Qty Saat Ini diisi manual setelah laporan dicetak.</span>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const chartEl = document.getElementById('monthlyActivityChart');
    if (!chartEl || typeof ApexCharts === 'undefined') {
        return;
    }

    const activity = @json($dailyActivity);

    const options = {
        chart: {
            type: 'bar',
            height: 260,
            toolbar: { show: false },
            fontFamily: 'Outfit, Plus Jakarta Sans, sans-serif'
        },
        series: [
            {
                name: 'Registration',
                data: activity.registrations || []
            },
            {
                name: 'Unregistration',
                data: activity.unregistrations || []
            }
        ],
        colors: ['#10b981', '#f59e0b'],
        plotOptions: {
            bar: {
                horizontal: false,
                columnWidth: '58%',
                borderRadius: 4
            }
        },
        dataLabels: {
            enabled: false
        },
        stroke: {
            show: true,
            width: 2,
            colors: ['transparent']
        },
        xaxis: {
            categories: activity.labels || [],
            title: {
                text: 'Tanggal dalam Bulan ({{ $periodLabel }})',
                style: { color: '#64748b', fontSize: '11px', fontWeight: 600 }
            },
            labels: {
                style: { colors: '#64748b', fontSize: '11px' }
            }
        },
        yaxis: {
            min: 0,
            forceNiceScale: true,
            labels: {
                formatter: function (val) {
                    return Math.floor(val) === val ? val : '';
                },
                style: { colors: '#64748b', fontSize: '11px' }
            },
            title: {
                text: 'Jumlah Item',
                style: { color: '#64748b', fontSize: '11px', fontWeight: 600 }
            }
        },
        grid: {
            borderColor: '#e2e8f0',
            strokeDashArray: 4
        },
        legend: {
            show: false
        },
        tooltip: {
            shared: true,
            intersect: false,
            y: {
                formatter: function (val) {
                    return val + ' item';
                }
            }
        }
    };

    const chart = new ApexCharts(chartEl, options);
    chart.render();
});
</script>
@endsection
