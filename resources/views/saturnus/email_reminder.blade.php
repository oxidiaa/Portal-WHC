@extends('layouts.app')

@section('title', 'Kirim Pengingat Email Approval — SATURNUS')

@section('content')
@php
    $user = auth()->user();
    $userRole = strtoupper(trim($user->role ?? 'GUEST'));
    $isMasterOrAdmin = in_array($userRole, ['MASTER', 'ADMIN']) || ($user && $user->isMaster());
@endphp

<style>
    /* ==========================================================================
       🪐 DEEP COSMIC SPACE STYLING (SATURNUS THEME)
       ========================================================================== */
    .email-reminder-page {
        padding: 0.5rem 0.5rem 3rem 0.5rem;
    }

    .rem-card-glass {
        background: rgba(15, 23, 42, 0.75);
        border: 1px solid rgba(56, 189, 248, 0.18);
        border-radius: 20px;
        padding: 1.8rem;
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.45);
        margin-bottom: 1.75rem;
        position: relative;
        overflow: hidden;
    }

    .rem-card-glass::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 2px;
        background: linear-gradient(90deg, transparent, rgba(56, 189, 248, 0.6), rgba(168, 85, 247, 0.6), transparent);
    }

    /* Page Header */
    .rem-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1.5rem;
        margin-bottom: 2rem;
        flex-wrap: wrap;
    }

    .rem-title-group h1 {
        font-size: 1.65rem;
        font-weight: 800;
        color: #ffffff;
        letter-spacing: -0.02em;
        margin: 0 0 0.35rem 0;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .rem-title-group p {
        font-size: 0.88rem;
        color: #94a3b8;
        margin: 0;
    }

    .smtp-live-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        background: rgba(16, 185, 129, 0.12);
        border: 1px solid rgba(16, 185, 129, 0.35);
        padding: 0.5rem 1rem;
        border-radius: 30px;
        font-size: 0.78rem;
        font-weight: 700;
        color: #34d399;
        letter-spacing: 0.03em;
        box-shadow: 0 0 20px rgba(16, 185, 129, 0.15);
    }

    .pulse-dot-green {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background-color: #10b981;
        box-shadow: 0 0 10px #10b981;
        animation: pulseGreen 2s infinite;
    }

    @keyframes pulseGreen {
        0%, 100% { transform: scale(1); opacity: 1; }
        50% { transform: scale(1.3); opacity: 0.6; }
    }

    /* Stat Cards Grid */
    .stat-rem-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.25rem;
        margin-bottom: 2rem;
    }

    .stat-rem-box {
        background: rgba(15, 23, 42, 0.6);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 16px;
        padding: 1.25rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        transition: all 0.25s ease;
    }

    .stat-rem-box:hover {
        border-color: rgba(56, 189, 248, 0.4);
        transform: translateY(-2px);
    }

    .stat-rem-icon {
        width: 48px;
        height: 48px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.35rem;
        flex-shrink: 0;
    }

    .stat-rem-info .num {
        font-size: 1.45rem;
        font-weight: 800;
        color: #ffffff;
        line-height: 1.1;
        font-family: var(--font-tech, monospace);
    }

    .stat-rem-info .lbl {
        font-size: 0.75rem;
        color: #94a3b8;
        font-weight: 600;
        margin-top: 0.2rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    /* Form Layout Grid */
    .composer-grid {
        display: grid;
        grid-template-columns: 1.15fr 1fr;
        gap: 1.75rem;
    }

    @media (max-width: 1024px) {
        .composer-grid {
            grid-template-columns: 1fr;
        }
    }

    /* Form Input Controls */
    .form-group-custom {
        margin-bottom: 1.25rem;
    }

    .form-group-custom label {
        display: block;
        font-size: 0.8rem;
        font-weight: 700;
        color: #e2e8f0;
        margin-bottom: 0.45rem;
        letter-spacing: 0.02em;
    }

    .custom-input, .custom-select, .custom-textarea {
        width: 100%;
        background: rgba(2, 6, 23, 0.7);
        border: 1px solid rgba(56, 189, 248, 0.25);
        border-radius: 12px;
        padding: 0.65rem 0.95rem;
        color: #ffffff;
        font-size: 0.88rem;
        transition: all 0.2s ease;
        outline: none;
    }

    .custom-input:focus, .custom-select:focus, .custom-textarea:focus {
        border-color: #38bdf8;
        box-shadow: 0 0 15px rgba(56, 189, 248, 0.25);
        background: rgba(2, 6, 23, 0.9);
    }

    .custom-select option {
        background-color: #0f172a;
        color: #ffffff;
    }

    /* Target Mode Selector Tabs */
    .target-mode-ribbon {
        display: flex;
        gap: 0.5rem;
        margin-bottom: 1.25rem;
        background: rgba(2, 6, 23, 0.6);
        padding: 0.35rem;
        border-radius: 12px;
        border: 1px solid rgba(255, 255, 255, 0.08);
    }

    .mode-tab-btn {
        flex: 1;
        background: transparent;
        border: none;
        color: #94a3b8;
        font-size: 0.78rem;
        font-weight: 700;
        padding: 0.5rem 0.75rem;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.4rem;
    }

    .mode-tab-btn.active {
        background: linear-gradient(135deg, #1a3fa8 0%, #00adef 100%);
        color: #ffffff;
        box-shadow: 0 2px 10px rgba(0, 173, 239, 0.3);
    }

    /* Priority Pills */
    .priority-pills {
        display: flex;
        gap: 0.5rem;
    }

    .priority-pill {
        flex: 1;
        border: 1px solid rgba(255, 255, 255, 0.1);
        background: rgba(2, 6, 23, 0.6);
        border-radius: 10px;
        padding: 0.55rem;
        text-align: center;
        cursor: pointer;
        font-size: 0.78rem;
        font-weight: 700;
        color: #cbd5e1;
        transition: all 0.2s;
    }

    .priority-pill.active.normal {
        background: rgba(59, 130, 246, 0.2);
        border-color: #3b82f6;
        color: #93c5fd;
    }

    .priority-pill.active.urgent {
        background: rgba(245, 158, 11, 0.2);
        border-color: #f59e0b;
        color: #fde68a;
    }

    .priority-pill.active.final {
        background: rgba(239, 68, 68, 0.2);
        border-color: #ef4444;
        color: #fca5a5;
    }

    /* Quick Template Pills */
    .template-chips {
        display: flex;
        gap: 0.4rem;
        flex-wrap: wrap;
        margin-top: 0.45rem;
    }

    .template-chip {
        font-size: 0.72rem;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 15px;
        padding: 0.25rem 0.65rem;
        color: #cbd5e1;
        cursor: pointer;
        transition: all 0.2s;
    }

    .template-chip:hover {
        background: rgba(56, 189, 248, 0.15);
        border-color: rgba(56, 189, 248, 0.35);
        color: #ffffff;
    }

    /* Pending Forms Card & List */
    .pending-list-container {
        max-height: 480px;
        overflow-y: auto;
        padding-right: 0.35rem;
        scrollbar-width: thin;
        scrollbar-color: rgba(56, 189, 248, 0.3) transparent;
    }

    .pending-list-container::-webkit-scrollbar {
        width: 5px;
    }

    .pending-list-container::-webkit-scrollbar-thumb {
        background: rgba(56, 189, 248, 0.3);
        border-radius: 4px;
    }

    .pending-item-card {
        background: rgba(2, 6, 23, 0.55);
        border: 1px solid rgba(255, 255, 255, 0.07);
        border-radius: 14px;
        padding: 0.95rem 1.1rem;
        margin-bottom: 0.75rem;
        transition: all 0.2s;
        display: flex;
        align-items: flex-start;
        gap: 0.85rem;
        cursor: pointer;
    }

    .pending-item-card:hover {
        background: rgba(2, 6, 23, 0.85);
        border-color: rgba(56, 189, 248, 0.35);
    }

    .pending-item-card.selected {
        background: rgba(10, 30, 70, 0.65);
        border-color: #00adef;
        box-shadow: 0 0 15px rgba(0, 173, 239, 0.2);
    }

    .form-check-custom {
        margin-top: 3px;
        transform: scale(1.2);
        accent-color: #00adef;
        cursor: pointer;
    }

    .form-item-body {
        flex: 1;
        min-width: 0;
    }

    .form-item-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
        margin-bottom: 0.25rem;
    }

    .form-fnumber {
        font-size: 0.88rem;
        font-weight: 700;
        color: #ffffff;
    }

    .form-badge-module {
        font-size: 0.68rem;
        font-weight: 700;
        padding: 0.2rem 0.55rem;
        border-radius: 8px;
    }

    .form-badge-module.reg { background: rgba(2, 132, 199, 0.2); color: #38bdf8; border: 1px solid rgba(2, 132, 199, 0.4); }
    .form-badge-module.unreg { background: rgba(244, 63, 94, 0.2); color: #fb7185; border: 1px solid rgba(244, 63, 94, 0.4); }

    .form-item-meta {
        font-size: 0.76rem;
        color: #94a3b8;
        display: flex;
        gap: 0.75rem;
        flex-wrap: wrap;
        margin-bottom: 0.35rem;
    }

    .form-stage-badge {
        font-size: 0.7rem;
        font-weight: 700;
        padding: 0.15rem 0.55rem;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
    }

    .form-stage-badge.staff { background: rgba(59, 130, 246, 0.15); color: #93c5fd; border: 1px solid rgba(59, 130, 246, 0.3); }
    .form-stage-badge.accounting { background: rgba(245, 158, 11, 0.15); color: #fde68a; border: 1px solid rgba(245, 158, 11, 0.3); }
    .form-stage-badge.warehouse { background: rgba(16, 185, 129, 0.15); color: #a7f3d0; border: 1px solid rgba(16, 185, 129, 0.3); }

    /* Action Buttons */
    .btn-rem-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 0.75rem 1.4rem;
        border-radius: 12px;
        font-size: 0.85rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.2s;
        border: none;
    }

    .btn-rem-action.primary {
        background: linear-gradient(135deg, #1a3fa8 0%, #00adef 100%);
        color: #ffffff;
        box-shadow: 0 4px 15px rgba(0, 173, 239, 0.35);
    }

    .btn-rem-action.primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 20px rgba(0, 173, 239, 0.5);
    }

    .btn-rem-action.secondary {
        background: rgba(255, 255, 255, 0.07);
        color: #e2e8f0;
        border: 1px solid rgba(255, 255, 255, 0.12);
    }

    .btn-rem-action.secondary:hover {
        background: rgba(56, 189, 248, 0.15);
        border-color: rgba(56, 189, 248, 0.4);
        color: #ffffff;
    }

    /* Modal Live Preview */
    .preview-modal-body {
        max-height: 75vh;
        overflow-y: auto;
        padding: 0;
        background-color: #020617;
    }

    .preview-iframe {
        width: 100%;
        height: 600px;
        border: none;
        background: transparent;
    }
</style>

<div class="email-reminder-page">

    <!-- 1. Header & Telemetry Status -->
    <div class="rem-header">
        <div class="rem-title-group">
            <h1>
                <span>✉️</span>
                <span>Kirim Pengingat Email Approval</span>
            </h1>
            <p>Kirimkan notifikasi dan rekap formulir yang belum disetujui secara langsung ke alamat email approver</p>
        </div>

        <div class="smtp-live-badge">
            <span class="pulse-beacon" style="background:#10b981; box-shadow:0 0 10px #10b981;"></span>
            <span>SMTP AKTIF: noreply@metalart-astra.co.id (Office 365 TLS 587)</span>
        </div>
    </div>

    <!-- 2. Statistics Bar -->
    <div class="stat-rem-grid">
        <div class="stat-rem-box">
            <div class="stat-rem-icon" style="background: rgba(244, 63, 94, 0.15); color: #fb7185;">
                ⏳
            </div>
            <div class="stat-rem-info">
                <div class="num text-rose">{{ $stats['total_pending'] }}</div>
                <div class="lbl">Total Form Pending</div>
            </div>
        </div>

        <div class="stat-rem-box">
            <div class="stat-rem-icon" style="background: rgba(59, 130, 246, 0.15); color: #60a5fa;">
                👔
            </div>
            <div class="stat-rem-info">
                <div class="num text-blue">{{ $stats['pending_staff'] }}</div>
                <div class="lbl">Pending Staff Dept</div>
            </div>
        </div>

        <div class="stat-rem-box">
            <div class="stat-rem-icon" style="background: rgba(245, 158, 11, 0.15); color: #fbbf24;">
                💼
            </div>
            <div class="stat-rem-info">
                <div class="num text-amber">{{ $stats['pending_accounting'] }}</div>
                <div class="lbl">Pending Accounting</div>
            </div>
        </div>

        <div class="stat-rem-box">
            <div class="stat-rem-icon" style="background: rgba(16, 185, 129, 0.15); color: #34d399;">
                📦
            </div>
            <div class="stat-rem-info">
                <div class="num text-emerald">{{ $stats['pending_warehouse'] }}</div>
                <div class="lbl">Pending Warehouse</div>
            </div>
        </div>

        <div class="stat-rem-box">
            <div class="stat-rem-icon" style="background: rgba(168, 85, 247, 0.15); color: #c084fc;">
                👥
            </div>
            <div class="stat-rem-info">
                <div class="num text-purple">{{ count($users) }}</div>
                <div class="lbl">Approver Terdaftar</div>
            </div>
        </div>
    </div>

    <!-- 3. Main Composer & Form Selector Grid -->
    <div class="composer-grid">
        
        <!-- Left Box: Compose Form -->
        <div class="rem-card-glass">
            <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom border-secondary border-opacity-25">
                <h3 style="font-size: 1.15rem; font-weight: 700; color: #ffffff; margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                    <span>📝</span>
                    <span>Formulir Pengirim Email</span>
                </h3>
                <span style="font-size: 0.75rem; color: #94a3b8;">Pengirim: <strong>{{ $currentUser->name ?? 'User' }}</strong> ({{ $currentUser->department ?? 'MAI' }})</span>
            </div>

            <!-- Target Selection Mode -->
            <div class="target-mode-ribbon">
                <button type="button" class="mode-tab-btn active" id="btnModeApprover" onclick="setTargetMode('approver')">
                    <span>👤</span>
                    <span>Pilih Approver Terdaftar</span>
                </button>
                <button type="button" class="mode-tab-btn" id="btnModeManual" onclick="setTargetMode('manual')">
                    <span>✏️</span>
                    <span>Input Email Manual</span>
                </button>
                <button type="button" class="mode-tab-btn" id="btnModeBroadcast" onclick="setTargetMode('broadcast')">
                    <span>📢</span>
                    <span>Broadcast Semua Approver</span>
                </button>
            </div>

            <form id="emailReminderForm" onsubmit="handleSendEmail(event)">
                @csrf
                <input type="hidden" name="broadcast_mode" id="broadcastModeInput" value="0">

                <!-- 1. Approver Selection Dropdown (Mode: Approver) -->
                <div class="form-group-custom" id="fieldApproverSelect">
                    <label for="approverSelect">Target Approver Penerima <span class="text-danger">*</span></label>
                    <select id="approverSelect" name="user_id" class="custom-select" onchange="onApproverSelected(this.value)">
                        <option value="">-- Pilih Approver / Pengguna --</option>
                        @foreach($users->groupBy('role') as $roleName => $roleUsers)
                            <optgroup label="ROLE: {{ strtoupper($roleName) }}">
                                @foreach($roleUsers as $u)
                                    <option value="{{ $u->id }}" data-name="{{ $u->name }}" data-email="{{ $u->email }}" data-dept="{{ $u->department }}" data-role="{{ $u->role }}">
                                        {{ $u->name }} ({{ $u->department ?? 'General' }}) — {{ $u->email }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>

                <!-- 2. Manual Recipient Fields (Mode: Manual) -->
                <div class="row g-2" id="fieldManualInputs" style="display: none;">
                    <div class="col-md-6 form-group-custom">
                        <label for="recipientNameInput">Nama Penerima</label>
                        <input type="text" id="recipientNameInput" name="recipient_name" class="custom-input" placeholder="Contoh: Bpk. Hendra">
                    </div>
                    <div class="col-md-6 form-group-custom">
                        <label for="recipientEmailInput">Alamat Email Penerima <span class="text-danger">*</span></label>
                        <input type="email" id="recipientEmailInput" name="recipient_email" class="custom-input" placeholder="penerima@metalart-astra.co.id">
                    </div>
                </div>

                <!-- Broadcast Alert Info (Mode: Broadcast) -->
                <div class="alert alert-info py-2 px-3 mb-3 border-info border-opacity-25 text-white" id="fieldBroadcastInfo" style="display: none; background: rgba(2, 132, 199, 0.15); font-size: 0.8rem; border-radius: 10px;">
                    <strong>📢 Mode Broadcast Aktif:</strong> Sistem akan otomatis memfilter dan mengirimkan email pengingat terpisah ke <strong>masing-masing Approver</strong> (Staff Dept, Accounting, Warehouse) sesuai dengan daftar formulir yang sedang menunggu persetujuan mereka!
                </div>

                <!-- 3. Subject Input -->
                <div class="form-group-custom">
                    <label for="subjectInput">Subjek Email</label>
                    <input type="text" id="subjectInput" name="subject" class="custom-input" value="[PENGINGAT] Formulir Menunggu Persetujuan Anda — SATURNUS MAI">
                </div>

                <!-- 4. Priority Selector -->
                <div class="form-group-custom">
                    <label>Tingkat Prioritas Pengingat</label>
                    <input type="hidden" name="priority" id="priorityInput" value="Normal">
                    <div class="priority-pills">
                        <div class="priority-pill normal active" onclick="setPriority('Normal', this)">
                            <span>🔵 Normal</span>
                        </div>
                        <div class="priority-pill urgent" onclick="setPriority('Urgent', this)">
                            <span>⚠️ Urgent / Mendesak</span>
                        </div>
                        <div class="priority-pill final" onclick="setPriority('Final Notice', this)">
                            <span>🚨 Final Reminder</span>
                        </div>
                    </div>
                </div>

                <!-- 5. Custom Message Textarea & Quick Templates -->
                <div class="form-group-custom">
                    <label for="customMessageInput">Pesan / Catatan Tambahan (Opsional)</label>
                    <textarea id="customMessageInput" name="custom_message" class="custom-textarea" rows="3" placeholder="Tuliskan catatan khusus ke approver..."></textarea>
                    
                    <div class="template-chips">
                        <span class="template-chip" onclick="insertTemplate('Mohon bantuannya untuk segera melakukan approval karena barang consumable ini sangat dibutuhkan untuk kelancaran operasional produksi.')">⚡ Segera Dibutuhkan Produksi</span>
                        <span class="template-chip" onclick="insertTemplate('Pengingat berkala: mohon persetujuan pada formulir terlampir agar proses penerbitan PO dapat dilanjutkan.')">⏱️ Pengingat Rutin PO</span>
                        <span class="template-chip" onclick="insertTemplate('Catatan revisi pada formulir telah diperbaiki oleh user terkait. Mohon kesediaannya untuk melakukan verifikasi ulang.')">✍️ Konfirmasi Selesai Revisi</span>
                    </div>
                </div>

                <!-- Form Action Buttons -->
                <div class="d-flex gap-2 pt-2">
                    <button type="button" class="btn-rem-action secondary" onclick="openPreviewModal()" style="flex: 1;">
                        <span>👁️</span>
                        <span>Preview Email</span>
                    </button>
                    <button type="submit" class="btn-rem-action primary" id="btnSubmitSend" style="flex: 1.5;">
                        <span>🚀</span>
                        <span>Kirim Email Sekarang</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Right Box: Pending Forms Selection List -->
        <div class="rem-card-glass">
            <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom border-secondary border-opacity-25 flex-wrap gap-2">
                <div>
                    <h3 style="font-size: 1.15rem; font-weight: 700; color: #ffffff; margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                        <span>📋</span>
                        <span>Pilih Formulir yang Dilampirkan</span>
                    </h3>
                    <span style="font-size: 0.75rem; color: #94a3b8;" id="selectedCountText">
                        Terpilih: <strong class="text-cyan" id="selectedCountNum">{{ count($allPendingForms) }}</strong> dari {{ count($allPendingForms) }} formulir pending
                    </span>
                </div>

                <div class="d-flex gap-1">
                    <button type="button" class="btn btn-sm btn-outline-info py-1 px-2" style="font-size: 0.72rem; border-radius: 8px;" onclick="toggleSelectAll(true)">Pilih Semua</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2" style="font-size: 0.72rem; border-radius: 8px;" onclick="toggleSelectAll(false)">Batal</button>
                </div>
            </div>

            <!-- Filter Pills for Form List -->
            <div class="d-flex gap-1 mb-3 overflow-auto pb-1" style="scrollbar-width: none;">
                <button type="button" class="btn btn-sm btn-primary py-0 px-2 filter-form-tab active" data-filter="all" onclick="filterFormList('all', this)" style="border-radius: 15px; font-size: 0.75rem;">Semua ({{ count($allPendingForms) }})</button>
                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 filter-form-tab text-white" data-filter="registrasi" onclick="filterFormList('registrasi', this)" style="border-radius: 15px; font-size: 0.75rem;">Registrasi ({{ $stats['pending_reg'] }})</button>
                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 filter-form-tab text-white" data-filter="unregistrasi" onclick="filterFormList('unregistrasi', this)" style="border-radius: 15px; font-size: 0.75rem;">Unregistrasi ({{ $stats['pending_unreg'] }})</button>
            </div>

            <!-- Dynamic List Container -->
            <div class="pending-list-container" id="pendingFormsContainer">
                @forelse($allPendingForms as $f)
                <div class="pending-item-card selected form-item-row" data-module="{{ $f['module_key'] }}" data-fnumber="{{ $f['form_number'] }}" data-dept="{{ $f['department'] }}" data-stage="{{ $f['stage_key'] }}" onclick="toggleFormCard(this, event)">
                    <input type="checkbox" name="selected_forms[]" value="{{ $f['form_number'] }}" class="form-check-custom form-checkbox" checked onclick="event.stopPropagation(); onCheckboxChanged();">
                    
                    <div class="form-item-body">
                        <div class="form-item-header">
                            <span class="form-fnumber">{{ $f['form_number'] }}</span>
                            <span class="form-badge-module {{ $f['module_key'] == 'registrasi' ? 'reg' : 'unreg' }}">
                                {{ $f['module_key'] == 'registrasi' ? 'REG' : 'UNREG' }}
                            </span>
                        </div>
                        
                        <div class="form-item-meta">
                            <span>🏢 {{ $f['department'] }}</span>
                            <span>📅 {{ $f['date'] }}</span>
                            <span>📦 {{ $f['item_count'] }} Item</span>
                        </div>

                        <div class="d-flex align-items-center justify-content-between mt-1">
                            <span class="form-stage-badge {{ $f['stage_key'] }}">
                                ⏳ {{ $f['status_label'] }}
                            </span>

                            <a href="{{ $f['action_url'] }}" target="_blank" class="text-cyan" style="font-size: 0.72rem; text-decoration: none;" onclick="event.stopPropagation();">
                                Buka Form &rarr;
                            </a>
                        </div>
                    </div>
                </div>
                @empty
                <div class="text-center py-5 text-muted">
                    <p style="font-size: 0.9rem;">🎉 Luar biasa! Tidak ada formulir yang sedang pending approval saat ini.</p>
                </div>
                @endforelse
            </div>
        </div>

    </div>

    <!-- 4. Recent Email Activity Log -->
    @if(!empty($recentLogs) && count($recentLogs) > 0)
    <div class="rem-card-glass mt-4">
        <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom border-secondary border-opacity-25">
            <h3 style="font-size: 1.1rem; font-weight: 700; color: #ffffff; margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                <span>📜</span>
                <span>Riwayat Pengiriman Email Pengingat Terakhir (Sesi Ini)</span>
            </h3>
            <span class="badge bg-secondary">{{ count($recentLogs) }} Terkirim</span>
        </div>

        <div class="table-responsive">
            <table class="table table-dark table-hover mb-0" style="font-size: 0.8rem; background: transparent;">
                <thead>
                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.1);">
                        <th>Waktu</th>
                        <th>Target Penerima</th>
                        <th>Email</th>
                        <th>Jumlah Form</th>
                        <th>Prioritas</th>
                        <th>Pengirim</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentLogs as $log)
                    <tr>
                        <td class="text-muted">{{ $log['sent_at'] }}</td>
                        <td class="fw-bold text-white">{{ $log['recipient_name'] }}</td>
                        <td class="text-cyan">{{ $log['recipient_email'] }}</td>
                        <td><span class="badge bg-info text-dark">{{ $log['form_count'] }} Form</span></td>
                        <td><span class="badge bg-secondary">{{ $log['priority'] }}</span></td>
                        <td class="text-muted">{{ $log['sender'] }}</td>
                        <td><span class="badge bg-success">✓ {{ $log['status'] }}</span></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

</div>

<!-- ==========================================================================
     MODAL: LIVE EMAIL PREVIEW
     ========================================================================== -->
<div class="modal fade" id="modalEmailPreview" tabindex="-1" aria-labelledby="modalEmailPreviewLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" style="max-width: 720px;">
        <div class="modal-content" style="background-color: #0f172a; border: 1px solid rgba(56, 189, 248, 0.3); border-radius: 18px; color: #ffffff; box-shadow: 0 20px 60px rgba(0,0,0,0.8);">
            
            <div class="modal-header" style="border-bottom: 1px solid rgba(255,255,255,0.1); padding: 1.2rem 1.5rem;">
                <h5 class="modal-title d-flex align-items-center gap-2" id="modalEmailPreviewLabel" style="font-size: 1.1rem; font-weight: 700;">
                    <span>👁️</span>
                    <span>Live Pratinjau Email HTML</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body preview-modal-body">
                <div id="previewLoadingSpinner" class="text-center py-5">
                    <div class="spinner-border text-cyan" role="status">
                        <span class="visually-hidden">Memuat...</span>
                    </div>
                    <p class="text-muted mt-2" style="font-size: 0.85rem;">Merender tampilan email...</p>
                </div>
                <iframe id="emailPreviewIframe" class="preview-iframe" style="display: none;"></iframe>
            </div>

            <div class="modal-footer" style="border-top: 1px solid rgba(255,255,255,0.1); padding: 0.9rem 1.5rem;">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-primary btn-sm px-3" onclick="triggerSendFromPreview()">
                    <span>🚀 Kirim Email Ini</span>
                </button>
            </div>

        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    let currentTargetMode = 'approver';

    // 1. Switch Target Mode
    function setTargetMode(mode) {
        currentTargetMode = mode;
        document.querySelectorAll('.mode-tab-btn').forEach(b => b.classList.remove('active'));
        
        const btnApprover = document.getElementById('btnModeApprover');
        const btnManual = document.getElementById('btnModeManual');
        const btnBroadcast = document.getElementById('btnModeBroadcast');

        const fieldApprover = document.getElementById('fieldApproverSelect');
        const fieldManual = document.getElementById('fieldManualInputs');
        const fieldBroadcast = document.getElementById('fieldBroadcastInfo');
        const broadcastInput = document.getElementById('broadcastModeInput');

        if (mode === 'approver') {
            btnApprover.classList.add('active');
            fieldApprover.style.display = 'block';
            fieldManual.style.display = 'none';
            fieldBroadcast.style.display = 'none';
            broadcastInput.value = '0';
        } else if (mode === 'manual') {
            btnManual.classList.add('active');
            fieldApprover.style.display = 'none';
            fieldManual.style.display = 'flex';
            fieldBroadcast.style.display = 'none';
            broadcastInput.value = '0';
        } else if (mode === 'broadcast') {
            btnBroadcast.classList.add('active');
            fieldApprover.style.display = 'none';
            fieldManual.style.display = 'none';
            fieldBroadcast.style.display = 'block';
            broadcastInput.value = '1';
        }
    }

    // 2. Approver Selection Handler (Auto-filter relevant pending forms)
    function onApproverSelected(userId) {
        if (!userId) return;

        const select = document.getElementById('approverSelect');
        const selectedOpt = select.options[select.selectedIndex];
        const email = selectedOpt.getAttribute('data-email');
        const name = selectedOpt.getAttribute('data-name');
        const dept = selectedOpt.getAttribute('data-dept');
        const role = selectedOpt.getAttribute('data-role');

        document.getElementById('recipientEmailInput').value = email || '';
        document.getElementById('recipientNameInput').value = name || '';

        // Fetch dynamic relevant pending forms via AJAX
        fetch(`{{ url('/saturnus/email-reminder/pending-for-user') }}/${userId}`)
            .then(res => res.json())
            .then(data => {
                if (data.success && data.forms) {
                    highlightRelevantForms(data.forms);
                }
            })
            .catch(err => console.error('Error fetching user pending:', err));
    }

    // Highlight and select only relevant forms for the user
    function highlightRelevantForms(relevantForms) {
        const relevantNumbers = relevantForms.map(f => f.form_number);
        const rows = document.querySelectorAll('.form-item-row');
        
        rows.forEach(row => {
            const fNum = row.getAttribute('data-fnumber');
            const checkbox = row.querySelector('.form-checkbox');
            
            if (relevantNumbers.includes(fNum)) {
                row.classList.add('selected');
                checkbox.checked = true;
                row.style.display = 'flex';
            } else {
                row.classList.remove('selected');
                checkbox.checked = false;
            }
        });

        updateSelectedCount();
    }

    // 3. Priority Selector
    function setPriority(level, el) {
        document.getElementById('priorityInput').value = level;
        document.querySelectorAll('.priority-pill').forEach(p => p.classList.remove('active'));
        el.classList.add('active');

        // Update default subject template prefix
        const subInput = document.getElementById('subjectInput');
        const prefix = (level.toLowerCase() === 'urgent') ? '[URGENT] ' : ((level.toLowerCase().includes('final')) ? '[FINAL NOTICE] ' : '[PENGINGAT] ');
        subInput.value = prefix + "Formulir Menunggu Persetujuan Anda — SATURNUS MAI";
    }

    // 4. Quick Template Insertion
    function insertTemplate(text) {
        const textarea = document.getElementById('customMessageInput');
        textarea.value = text;
        textarea.focus();
    }

    // 5. Toggle Select All Checkboxes
    function toggleSelectAll(select) {
        const rows = document.querySelectorAll('.form-item-row');
        rows.forEach(row => {
            if (row.style.display !== 'none') {
                const cb = row.querySelector('.form-checkbox');
                cb.checked = select;
                if (select) {
                    row.classList.add('selected');
                } else {
                    row.classList.remove('selected');
                }
            }
        });
        updateSelectedCount();
    }

    // 6. Filter Form List Tabs
    function filterFormList(moduleKey, btn) {
        document.querySelectorAll('.filter-form-tab').forEach(b => {
            b.classList.remove('active', 'btn-primary');
            b.classList.add('btn-outline-secondary');
        });
        btn.classList.add('active', 'btn-primary');
        btn.classList.remove('btn-outline-secondary');

        const rows = document.querySelectorAll('.form-item-row');
        rows.forEach(row => {
            const m = row.getAttribute('data-module');
            if (moduleKey === 'all' || m === moduleKey) {
                row.style.display = 'flex';
            } else {
                row.style.display = 'none';
            }
        });
    }

    // 7. Toggle Individual Form Card
    function toggleFormCard(card, event) {
        const cb = card.querySelector('.form-checkbox');
        cb.checked = !cb.checked;
        if (cb.checked) {
            card.classList.add('selected');
        } else {
            card.classList.remove('selected');
        }
        updateSelectedCount();
    }

    function onCheckboxChanged() {
        const rows = document.querySelectorAll('.form-item-row');
        rows.forEach(row => {
            const cb = row.querySelector('.form-checkbox');
            if (cb.checked) {
                row.classList.add('selected');
            } else {
                row.classList.remove('selected');
            }
        });
        updateSelectedCount();
    }

    function updateSelectedCount() {
        const checked = document.querySelectorAll('.form-checkbox:checked').length;
        document.getElementById('selectedCountNum').innerText = checked;
    }

    // 8. Open Live Email Preview Modal
    function openPreviewModal() {
        const modal = new bootstrap.Modal(document.getElementById('modalEmailPreview'));
        modal.show();

        const spinner = document.getElementById('previewLoadingSpinner');
        const iframe = document.getElementById('emailPreviewIframe');
        spinner.style.display = 'block';
        iframe.style.display = 'none';

        // Prepare preview payload
        let recipientName = 'Approver Terkait';
        let recipientEmail = 'approver@metalart-astra.co.id';

        if (currentTargetMode === 'approver') {
            const select = document.getElementById('approverSelect');
            if (select.selectedIndex > 0) {
                recipientName = select.options[select.selectedIndex].getAttribute('data-name');
                recipientEmail = select.options[select.selectedIndex].getAttribute('data-email');
            }
        } else if (currentTargetMode === 'manual') {
            recipientName = document.getElementById('recipientNameInput').value || 'Penerima';
            recipientEmail = document.getElementById('recipientEmailInput').value || 'penerima@metalart-astra.co.id';
        } else if (currentTargetMode === 'broadcast') {
            recipientName = '[Semua Approver Terkait]';
            recipientEmail = 'broadcast-approvers@metalart-astra.co.id';
        }

        const selectedForms = Array.from(document.querySelectorAll('.form-checkbox:checked')).map(cb => cb.value);

        const formData = new FormData();
        formData.append('_token', '{{ csrf_token() }}');
        formData.append('recipient_name', recipientName);
        formData.append('recipient_email', recipientEmail);
        formData.append('priority', document.getElementById('priorityInput').value);
        formData.append('subject', document.getElementById('subjectInput').value);
        formData.append('custom_message', document.getElementById('customMessageInput').value);
        selectedForms.forEach(f => formData.append('selected_forms[]', f));

        fetch('{{ route("saturnus.email_reminder.preview") }}', {
            method: 'POST',
            body: formData
        })
        .then(res => res.text())
        .then(html => {
            spinner.style.display = 'none';
            iframe.style.display = 'block';
            
            const doc = iframe.contentWindow.document;
            doc.open();
            doc.write(html);
            doc.close();
        })
        .catch(err => {
            spinner.innerHTML = `<div class="text-danger p-4">Gagal memuat preview: ${err.message}</div>`;
        });
    }

    function triggerSendFromPreview() {
        const modalEl = document.getElementById('modalEmailPreview');
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();

        document.getElementById('emailReminderForm').requestSubmit();
    }

    // 9. Send Email Handler
    function handleSendEmail(e) {
        e.preventDefault();

        const btn = document.getElementById('btnSubmitSend');
        const origText = btn.innerHTML;

        // Validation
        if (currentTargetMode === 'approver') {
            const select = document.getElementById('approverSelect');
            if (!select.value) {
                alert('Silakan pilih Approver target penerima terlebih dahulu!');
                select.focus();
                return;
            }
        } else if (currentTargetMode === 'manual') {
            const emailInput = document.getElementById('recipientEmailInput');
            if (!emailInput.value) {
                alert('Silakan masukkan alamat email penerima!');
                emailInput.focus();
                return;
            }
        }

        const selectedForms = Array.from(document.querySelectorAll('.form-checkbox:checked')).map(cb => cb.value);
        if (selectedForms.length === 0) {
            if (!confirm('Perhatian: Anda belum memilih formulir apapun untuk dilampirkan. Apakah ingin tetap mengirimkan pengingat umum?')) {
                return;
            }
        }

        // Disable button & show spinner
        btn.disabled = true;
        btn.innerHTML = `
            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
            <span>Mengirim Email via SMTP...</span>
        `;

        const form = document.getElementById('emailReminderForm');
        const formData = new FormData(form);

        // Append selected forms explicitly
        formData.delete('selected_forms[]');
        selectedForms.forEach(f => formData.append('selected_forms[]', f));

        // Append recipient data
        if (currentTargetMode === 'approver') {
            const select = document.getElementById('approverSelect');
            const opt = select.options[select.selectedIndex];
            formData.set('recipient_name', opt.getAttribute('data-name'));
            formData.set('recipient_email', opt.getAttribute('data-email'));
        }

        fetch('{{ route("saturnus.email_reminder.send") }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = origText;

            if (data.success) {
                alert('✅ ' + data.message);
                window.location.reload();
            } else {
                alert('❌ ' + (data.message || 'Gagal mengirim email. Silakan periksa koneksi SMTP atau email tujuan.'));
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = origText;
            alert('❌ Terjadi kesalahan pengiriman: ' + err.message);
        });
    }
</script>
@endsection
