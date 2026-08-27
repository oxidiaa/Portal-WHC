@extends('layouts.app')

@section('title', 'Executive Overview')
@section('page_title', 'Executive Overview')

@section('content')
<div class="dashboard-unified">
    <!-- Welcome Hero Banner -->
    <div style="background: linear-gradient(135deg, #1e3a8a 0%, #1e293b 50%, #0f172a 100%); border-radius: var(--radius-xl); padding: 2rem 2.25rem; color: #ffffff; margin-bottom: 2rem; position: relative; overflow: hidden; box-shadow: var(--shadow-lg);">
        <div style="position: absolute; right: -20px; bottom: -40px; opacity: 0.08; pointer-events: none;">
            <svg width="340" height="340" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="1.5">
                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                <line x1="12" y1="22.08" x2="12" y2="12"></line>
            </svg>
        </div>

        <div style="max-width: 800px; position: relative; z-index: 2;">
            <div style="display: inline-flex; align-items: center; gap: 0.5rem; background: rgba(255,255,255,0.12); padding: 0.35rem 0.85rem; border-radius: var(--radius-full); font-size: 0.78rem; font-weight: 700; color: #60a5fa; margin-bottom: 1rem; border: 1px solid rgba(255,255,255,0.15);">
                <span>✨ SISTEM PORTAL TERPADU WAREHOUSE &amp; CONSUMABLE</span>
            </div>
            <h1 style="font-family: var(--font-head); font-size: 2rem; font-weight: 800; line-height: 1.2; margin-bottom: 0.75rem;">
                Selamat Datang, {{ auth()->user()->name }}!
            </h1>
            <p style="font-size: 0.95rem; color: #cbd5e1; line-height: 1.6; margin-bottom: 1.5rem;">
                Sistem terpadu ini menggabungkan manajemen stok minim, PO, kedatangan barang (<strong style="color: #fb923c;">MARS</strong>) serta alur pendaftaran, persetujuan bertingkat, dan discontinue consumable (<strong style="color: #c084fc;">SATURNUS</strong>) dalam satu database terintegrasi.
            </p>
            <div style="display: flex; flex-wrap: wrap; gap: 0.75rem;">
                <a href="{{ route('mars.dashboard') }}" class="btn-mai btn-mai-mars">
                    <span>Akses Portal MARS</span>
                    <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2.5" fill="none">
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                        <polyline points="12 5 19 12 12 19"></polyline>
                    </svg>
                </a>
                <a href="{{ route('saturnus.dashboard') }}" class="btn-mai btn-mai-saturnus">
                    <span>Akses Portal SATURNUS</span>
                    <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2.5" fill="none">
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                        <polyline points="12 5 19 12 12 19"></polyline>
                    </svg>
                </a>
            </div>
        </div>
    </div>

    <!-- Executive Stat Cards -->
    <div class="stat-card-grid">
        <div class="stat-card">
            <div class="stat-card-icon blue">
                <svg viewBox="0 0 24 24" width="26" height="26" stroke="currentColor" stroke-width="2" fill="none">
                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                </svg>
            </div>
            <div class="stat-card-details">
                <span class="stat-card-label">Total Master Item</span>
                <span class="stat-card-value">{{ number_format($marsStats['total_items'] ?? 0) }}</span>
                <span class="stat-card-sub">Data Master MARS</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-card-icon orange">
                <svg viewBox="0 0 24 24" width="26" height="26" stroke="currentColor" stroke-width="2" fill="none">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
            </div>
            <div class="stat-card-details">
                <span class="stat-card-label">Item Stok Minim</span>
                <span class="stat-card-value" style="color: #ea580c;">{{ number_format($marsStats['minim_items'] ?? 0) }}</span>
                <span class="stat-card-sub">Perlu Segera Follow Up PO</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-card-icon purple">
                <svg viewBox="0 0 24 24" width="26" height="26" stroke="currentColor" stroke-width="2" fill="none">
                    <circle cx="12" cy="12" r="10"></circle>
                    <path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"></path>
                    <path d="M2 12h20"></path>
                </svg>
            </div>
            <div class="stat-card-details">
                <span class="stat-card-label">Consumables Terdaftar</span>
                <span class="stat-card-value">{{ number_format($saturnusStats['total_consumables'] ?? 0) }}</span>
                <span class="stat-card-sub">Direktori SATURNUS</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-card-icon emerald">
                <svg viewBox="0 0 24 24" width="26" height="26" stroke="currentColor" stroke-width="2" fill="none">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                    <polyline points="10 9 9 9 8 9"></polyline>
                </svg>
            </div>
            <div class="stat-card-details">
                <span class="stat-card-label">Checksheet Aktif</span>
                <span class="stat-card-value">{{ number_format($saturnusStats['pending_registrations'] ?? 0) }}</span>
                <span class="stat-card-sub">Form Registrasi &amp; Unreg</span>
            </div>
        </div>
    </div>

    <!-- Dual Module Quick Access Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
        <!-- MARS Module Card -->
        <div class="card-mai" style="border-top: 4px solid #ea580c;">
            <div class="card-mai-header">
                <div class="card-mai-title">
                    <div style="width: 32px; height: 32px; border-radius: 8px; background: rgba(234, 88, 12, 0.15); display: flex; align-items: center; justify-content: center; color: #ea580c;">
                        <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none">
                            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                        </svg>
                    </div>
                    <span>Modul MARS (Stok Minim &amp; PO)</span>
                </div>
                <span class="badge-mai orange">Operational</span>
            </div>
            <div class="card-mai-body">
                <p style="font-size: 0.88rem; color: var(--text-muted); margin-bottom: 1.25rem;">
                    Monitoring stok barang di bawah titik order point, sinkronisasi schedule receipt PO, verifikasi kedatangan barang, dan histori log penerimaan.
                </p>
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.75rem;">
                    <a href="{{ route('mars.item_minim.index') }}" class="btn-mai btn-mai-secondary btn-mai-sm" style="justify-content: flex-start;">
                        <span>⚠️ Item Minim</span>
                        <span class="badge-mai danger" style="margin-left: auto;">{{ $marsStats['minim_items'] ?? 0 }}</span>
                    </a>
                    <a href="{{ route('mars.data_po.index') }}" class="btn-mai btn-mai-secondary btn-mai-sm" style="justify-content: flex-start;">
                        <span>📑 Data PO</span>
                        <span class="badge-mai primary" style="margin-left: auto;">{{ $marsStats['total_po'] ?? 0 }}</span>
                    </a>
                    <a href="{{ route('mars.kedatangan_barang.index') }}" class="btn-mai btn-mai-secondary btn-mai-sm" style="justify-content: flex-start;">
                        <span>🚚 Kedatangan Barang</span>
                    </a>
                    <a href="{{ route('mars.history.index') }}" class="btn-mai btn-mai-secondary btn-mai-sm" style="justify-content: flex-start;">
                        <span>📜 History Kedatangan</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- SATURNUS Module Card -->
        <div class="card-mai" style="border-top: 4px solid #7c3aed;">
            <div class="card-mai-header">
                <div class="card-mai-title">
                    <div style="width: 32px; height: 32px; border-radius: 8px; background: rgba(124, 58, 237, 0.15); display: flex; align-items: center; justify-content: center; color: #7c3aed;">
                        <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none">
                            <circle cx="12" cy="12" r="10"></circle>
                            <path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"></path>
                        </svg>
                    </div>
                    <span>Modul SATURNUS (Consumables)</span>
                </div>
                <span class="badge-mai purple">Approval Active</span>
            </div>
            <div class="card-mai-body">
                <p style="font-size: 0.88rem; color: var(--text-muted); margin-bottom: 1.25rem;">
                    Alur pendaftaran consumable baru dari User / Production, verifikasi Staff &amp; Accounting, stamping QR / Barcode, dan proses discontinue.
                </p>
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.75rem;">
                    <a href="{{ route('saturnus.dashboard') }}" class="btn-mai btn-mai-secondary btn-mai-sm" style="justify-content: flex-start;">
                        <span>📋 Direktori Consumable</span>
                    </a>
                    <a href="{{ route('saturnus.form_registrasi') }}" class="btn-mai btn-mai-secondary btn-mai-sm" style="justify-content: flex-start;">
                        <span>📝 Form Registrasi Baru</span>
                    </a>
                    <a href="{{ route('saturnus.form_unregistrasi') }}" class="btn-mai btn-mai-secondary btn-mai-sm" style="justify-content: flex-start;">
                        <span>❌ Form Unregistrasi</span>
                    </a>
                    <a href="{{ route('saturnus.dashboard') }}" class="btn-mai btn-mai-secondary btn-mai-sm" style="justify-content: flex-start;">
                        <span>🪐 3D Saturn Observatory</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Alerts & Critical Stock Table -->
    <div class="card-mai">
        <div class="card-mai-header">
            <div class="card-mai-title">
                <svg viewBox="0 0 24 24" width="20" height="20" stroke="#ea580c" stroke-width="2" fill="none">
                    <polygon points="7.86 2 16.14 2 22 7.86 22 16.14 16.14 22 7.86 22 2 16.14 2 7.86 7.86 2"></polygon>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <span>Item Stok Minim Kritis (Prioritas Pengiriman)</span>
            </div>
            <a href="{{ route('mars.item_minim.index') }}" class="btn-mai btn-mai-primary btn-mai-sm">
                <span>Lihat Seluruh Item Minim</span>
            </a>
        </div>
        <div class="card-mai-body" style="padding: 0;">
            <div class="table-mai-responsive" style="border: none; border-radius: 0;">
                <table class="table-mai">
                    <thead>
                        <tr>
                            <th>Kode Item</th>
                            <th>Nama Item</th>
                            <th>Ending Balance</th>
                            <th>Order Point</th>
                            <th>Outstanding</th>
                            <th>Follow Up Status</th>
                            <th>Estimasi Kirim</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($criticalMinimItems ?? [] as $item)
                        <tr>
                            <td><strong style="color: var(--mai-primary);">{{ $item->item_code }}</strong></td>
                            <td>{{ $item->item_name }}</td>
                            <td>
                                <span class="badge-mai {{ $item->ending_balance <= $item->minimal_stock ? 'danger' : 'warning' }}">
                                    {{ number_format($item->ending_balance) }}
                                </span>
                            </td>
                            <td>{{ number_format($item->order_point) }}</td>
                            <td><strong style="color: #ea580c;">{{ number_format($item->outstanding) }}</strong></td>
                            <td>
                                @if($item->sudah_follow === 'YES')
                                    <span class="badge-mai success">Sudah Follow</span>
                                @else
                                    <span class="badge-mai danger">Belum Follow</span>
                                @endif
                            </td>
                            <td>{{ $item->pengiriman_tanggal ? \Carbon\Carbon::parse($item->pengiriman_tanggal)->format('d/m/Y') : '-' }}</td>
                            <td>
                                <a href="{{ route('mars.item_minim.index') }}" class="btn-mai btn-mai-secondary btn-mai-sm">Follow Up</a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                                Tidak ada item minim yang kritis saat ini.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
