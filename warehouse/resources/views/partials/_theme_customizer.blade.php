<!-- ==========================================================================
     🎨 THEME & APPEARANCE CUSTOMIZER (SIDEBAR & TOPBAR STYLING)
     ========================================================================== -->

<!-- Floating Customizer Trigger Button -->
<div class="theme-customizer-trigger" id="themeCustomizerTrigger" title="Kustomisasi Warna Sidebar & Topbar">
    <div class="trigger-icon-spin">
        <i data-feather="settings" style="width: 20px; height: 20px;"></i>
    </div>
</div>

<!-- Offcanvas Drawer for Theme Settings -->
<div class="theme-customizer-drawer" id="themeCustomizerDrawer">
    <div class="customizer-header">
        <div class="d-flex align-items-center gap-2">
            <div class="customizer-badge-icon">
                <i data-feather="sliders" style="width: 16px; height: 16px;"></i>
            </div>
            <div>
                <h6 class="customizer-title mb-0">Warna Sidebar &amp; Topbar</h6>
                <span class="customizer-subtitle">Ubah tema gelap, terang &amp; animasi navigasi</span>
            </div>
        </div>
        <button type="button" class="customizer-close-btn" id="themeCustomizerClose">&times;</button>
    </div>

    <div class="customizer-body">
        <!-- 1. SIDEBAR THEME CHOICES -->
        <div class="customizer-section">
            <label class="customizer-section-title">
                <i data-feather="layout" class="section-icon"></i>
                <span>TEMA BACKGROUND SIDEBAR</span>
            </label>
            <div class="theme-grid-2x2">
                <!-- Dark Sidebar -->
                <button type="button" class="theme-opt-card active" data-set-sidebar="dark">
                    <div class="opt-preview opt-preview-sidebar-dark">
                        <div class="preview-side"></div>
                        <div class="preview-main"></div>
                    </div>
                    <span class="opt-label">🌑 Dark (Gelap)</span>
                </button>

                <!-- Light Sidebar -->
                <button type="button" class="theme-opt-card" data-set-sidebar="light">
                    <div class="opt-preview opt-preview-sidebar-light">
                        <div class="preview-side"></div>
                        <div class="preview-main"></div>
                    </div>
                    <span class="opt-label">⚪ Light (Terang)</span>
                </button>

                <!-- Cosmic Sidebar -->
                <button type="button" class="theme-opt-card" data-set-sidebar="cosmic">
                    <div class="opt-preview opt-preview-sidebar-cosmic">
                        <div class="preview-side"></div>
                        <div class="preview-main"></div>
                    </div>
                    <span class="opt-label">🪐 Cosmic Space</span>
                </button>

                <!-- Corporate Blue Sidebar -->
                <button type="button" class="theme-opt-card" data-set-sidebar="blue">
                    <div class="opt-preview opt-preview-sidebar-blue">
                        <div class="preview-side"></div>
                        <div class="preview-main"></div>
                    </div>
                    <span class="opt-label">🔵 Blue Navy</span>
                </button>
            </div>
        </div>

        <!-- 2. TOPBAR THEME CHOICES -->
        <div class="customizer-section">
            <label class="customizer-section-title">
                <i data-feather="credit-card" class="section-icon"></i>
                <span>TEMA BACKGROUND TOPBAR (NAVBAR)</span>
            </label>
            <div class="theme-grid-2x2">
                <!-- Light Topbar -->
                <button type="button" class="theme-opt-card active" data-set-topbar="light">
                    <div class="opt-preview opt-preview-topbar-light">
                        <div class="preview-top"></div>
                        <div class="preview-main"></div>
                    </div>
                    <span class="opt-label">⚪ Light (Terang)</span>
                </button>

                <!-- Dark Topbar -->
                <button type="button" class="theme-opt-card" data-set-topbar="dark">
                    <div class="opt-preview opt-preview-topbar-dark">
                        <div class="preview-top"></div>
                        <div class="preview-main"></div>
                    </div>
                    <span class="opt-label">🌑 Dark (Gelap)</span>
                </button>

                <!-- Cosmic Topbar -->
                <button type="button" class="theme-opt-card" data-set-topbar="cosmic">
                    <div class="opt-preview opt-preview-topbar-cosmic">
                        <div class="preview-top"></div>
                        <div class="preview-main"></div>
                    </div>
                    <span class="opt-label">🪐 Cosmic Glass</span>
                </button>

                <!-- Blue Topbar -->
                <button type="button" class="theme-opt-card" data-set-topbar="blue">
                    <div class="opt-preview opt-preview-topbar-blue">
                        <div class="preview-top"></div>
                        <div class="preview-main"></div>
                    </div>
                    <span class="opt-label">🔵 Blue Navy</span>
                </button>
            </div>
        </div>

        <!-- 3. COLOR ACCENT PRESETS -->
        <div class="customizer-section">
            <label class="customizer-section-title">
                <i data-feather="droplet" class="section-icon"></i>
                <span>AKSEN WARNA INDIKATOR</span>
            </label>
            <div class="color-accent-grid">
                <button type="button" class="accent-color-btn active" data-set-accent="blue" title="MAI Corporate Blue" style="--accent-color: #2563eb;">
                    <span class="color-dot"></span>
                    <span class="color-name">Blue</span>
                </button>
                <button type="button" class="accent-color-btn" data-set-accent="orange" title="Mars Amber Orange" style="--accent-color: #ea580c;">
                    <span class="color-dot"></span>
                    <span class="color-name">Mars</span>
                </button>
                <button type="button" class="accent-color-btn" data-set-accent="purple" title="Saturn Cosmic Purple" style="--accent-color: #8b5cf6;">
                    <span class="color-dot"></span>
                    <span class="color-name">Saturn</span>
                </button>
                <button type="button" class="accent-color-btn" data-set-accent="green" title="Emerald Green" style="--accent-color: #10b981;">
                    <span class="color-dot"></span>
                    <span class="color-name">Emerald</span>
                </button>
                <button type="button" class="accent-color-btn" data-set-accent="red" title="Crimson Red" style="--accent-color: #ef4444;">
                    <span class="color-dot"></span>
                    <span class="color-name">Crimson</span>
                </button>
                <button type="button" class="accent-color-btn" data-set-accent="cyan" title="Cyan Ocean" style="--accent-color: #06b6d4;">
                    <span class="color-dot"></span>
                    <span class="color-name">Cyan</span>
                </button>
            </div>
        </div>

        <!-- 4. ANIMATION & MICRO-INTERACTIONS -->
        <div class="customizer-section">
            <label class="customizer-section-title">
                <i data-feather="zap" class="section-icon"></i>
                <span>ANIMASI &amp; INTERAKSI</span>
            </label>
            <div class="effect-toggle-list">
                <div class="effect-toggle-item">
                    <div>
                        <div class="effect-name">Animasi Slide &amp; Hover Navigasi</div>
                        <div class="effect-desc">Transisi halus dan efek pergeseran saat hover menu</div>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input effect-switch" type="checkbox" id="toggleNavAnimations" checked>
                    </div>
                </div>

                <div class="effect-toggle-item">
                    <div>
                        <div class="effect-name">Glow Efek pada Menu Aktif</div>
                        <div class="effect-desc">Efek pendaran cahaya pada menu yang sedang dipilih</div>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input effect-switch" type="checkbox" id="toggleGlowEffect" checked>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="customizer-footer">
        <button type="button" class="btn btn-sm btn-outline-secondary w-100" id="resetThemeDefaultsBtn">
            <i data-feather="rotate-ccw" class="me-1" style="width: 14px; height: 14px;"></i>
            Kembalikan ke Default
        </button>
    </div>
</div>

<!-- Backdrop Overlay -->
<div class="theme-customizer-backdrop" id="themeCustomizerBackdrop"></div>

<style>
/* ==========================================================================
   THEME CUSTOMIZER DRAWER STYLES
   ========================================================================== */
.theme-customizer-trigger {
    position: fixed;
    bottom: 24px;
    right: 24px;
    width: 46px;
    height: 46px;
    background: var(--mai-primary, #2563eb);
    color: #ffffff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 16px rgba(37, 99, 235, 0.4);
    cursor: pointer;
    z-index: 1040;
    transition: all 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.theme-customizer-trigger:hover {
    transform: scale(1.1) rotate(45deg);
    box-shadow: 0 6px 22px rgba(37, 99, 235, 0.55);
}
.trigger-icon-spin {
    display: flex;
    align-items: center;
    justify-content: center;
}

.theme-customizer-drawer {
    position: fixed;
    top: 0;
    right: -360px;
    width: 340px;
    height: 100vh;
    background: #ffffff;
    border-left: 1px solid #e2e8f0;
    box-shadow: -6px 0 24px rgba(0, 0, 0, 0.12);
    z-index: 1070;
    display: flex;
    flex-direction: column;
    transition: right 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    font-family: 'Plus Jakarta Sans', sans-serif;
}
.theme-customizer-drawer.open {
    right: 0;
}
.customizer-header {
    padding: 16px 20px;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #f8fafc;
}
.customizer-badge-icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: #eff6ff;
    color: #2563eb;
    display: flex;
    align-items: center;
    justify-content: center;
}
.customizer-title {
    font-size: 13.5px;
    font-weight: 700;
    color: #0f172a;
}
.customizer-subtitle {
    font-size: 11px;
    color: #64748b;
    display: block;
}
.customizer-close-btn {
    background: none;
    border: none;
    font-size: 24px;
    color: #94a3b8;
    cursor: pointer;
    line-height: 1;
    padding: 0 4px;
}
.customizer-close-btn:hover {
    color: #0f172a;
}
.customizer-body {
    padding: 18px 20px;
    flex: 1;
    overflow-y: auto;
}
.customizer-section {
    margin-bottom: 20px;
}
.customizer-section-title {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.05em;
    color: #64748b;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 6px;
    text-transform: uppercase;
}
.section-icon {
    width: 14px;
    height: 14px;
}

/* 2x2 Grid for Options */
.theme-grid-2x2 {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 8px;
}
.theme-opt-card {
    background: #f8fafc;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    padding: 8px 6px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    transition: all 0.18s ease;
}
.theme-opt-card:hover {
    border-color: #cbd5e1;
    transform: translateY(-2px);
}
.theme-opt-card.active {
    border-color: var(--mai-primary, #2563eb);
    background: #eff6ff;
}

/* Visual Previews */
.opt-preview {
    width: 100%;
    height: 44px;
    border-radius: 6px;
    border: 1px solid #cbd5e1;
    display: flex;
    overflow: hidden;
}

/* Sidebar Previews */
.opt-preview-sidebar-dark .preview-side { width: 35%; background: #0f172a; }
.opt-preview-sidebar-dark .preview-main { flex: 1; background: #f8fafc; }

.opt-preview-sidebar-light .preview-side { width: 35%; background: #ffffff; border-right: 1px solid #e2e8f0; }
.opt-preview-sidebar-light .preview-main { flex: 1; background: #f1f5f9; }

.opt-preview-sidebar-cosmic .preview-side { width: 35%; background: #050b1a; border-right: 1px solid #00adef; }
.opt-preview-sidebar-cosmic .preview-main { flex: 1; background: #020617; }

.opt-preview-sidebar-blue .preview-side { width: 35%; background: linear-gradient(180deg, #1e3a8a, #0f172a); }
.opt-preview-sidebar-blue .preview-main { flex: 1; background: #f8fafc; }

/* Topbar Previews */
.opt-preview-topbar-light { flex-direction: column; }
.opt-preview-topbar-light .preview-top { height: 14px; background: #ffffff; border-bottom: 1px solid #e2e8f0; }
.opt-preview-topbar-light .preview-main { flex: 1; background: #f8fafc; }

.opt-preview-topbar-dark { flex-direction: column; }
.opt-preview-topbar-dark .preview-top { height: 14px; background: #0f172a; }
.opt-preview-topbar-dark .preview-main { flex: 1; background: #f8fafc; }

.opt-preview-topbar-cosmic { flex-direction: column; }
.opt-preview-topbar-cosmic .preview-top { height: 14px; background: #081226; border-bottom: 1px solid #00adef; }
.opt-preview-topbar-cosmic .preview-main { flex: 1; background: #020617; }

.opt-preview-topbar-blue { flex-direction: column; }
.opt-preview-topbar-blue .preview-top { height: 14px; background: #1e3a8a; }
.opt-preview-topbar-blue .preview-main { flex: 1; background: #f8fafc; }

.opt-label {
    font-size: 11px;
    font-weight: 600;
    color: #334155;
    text-align: center;
}

/* Color Accent Grid */
.color-accent-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
}
.accent-color-btn {
    background: #ffffff;
    border: 1.5px solid #e2e8f0;
    border-radius: 8px;
    padding: 7px 6px;
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    transition: all 0.15s ease;
}
.accent-color-btn:hover {
    border-color: var(--accent-color);
}
.accent-color-btn.active {
    border-color: var(--accent-color);
    background: #f8fafc;
    box-shadow: 0 0 0 1px var(--accent-color);
}
.color-dot {
    width: 14px;
    height: 14px;
    border-radius: 50%;
    background: var(--accent-color);
    flex-shrink: 0;
}
.color-name {
    font-size: 11px;
    font-weight: 600;
    color: #334155;
}

/* Effect Toggles */
.effect-toggle-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.effect-toggle-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 12px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
}
.effect-name {
    font-size: 12px;
    font-weight: 600;
    color: #0f172a;
}
.effect-desc {
    font-size: 10.5px;
    color: #64748b;
}

.customizer-footer {
    padding: 14px 20px;
    border-top: 1px solid #e2e8f0;
    background: #f8fafc;
}

.theme-customizer-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(0, 0, 0, 0.4);
    backdrop-filter: blur(2px);
    z-index: 1065;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.25s ease;
}
.theme-customizer-backdrop.show {
    opacity: 1;
    pointer-events: auto;
}

/* ==========================================================================
   DYNAMIC SIDEBAR THEMES
   ========================================================================== */
/* 1. DARK SIDEBAR */
html[data-sidebar="dark"] .simple-tree-sidebar,
html[data-sidebar="dark"] .sidebar {
    background: #0f172a !important;
    border-right: 1px solid #1e293b !important;
}
html[data-sidebar="dark"] .simple-tree-sidebar .sidebar-header-simple {
    background: #0b132b !important;
    border-bottom: 1px solid #1e293b !important;
}
html[data-sidebar="dark"] .simple-tree-sidebar .sidebar-scroll-simple {
    background: #0f172a !important;
}
html[data-sidebar="dark"] .simple-tree-sidebar .nav-tree-item,
html[data-sidebar="dark"] .simple-tree-sidebar .sub-tree-link {
    color: #94a3b8 !important;
}
html[data-sidebar="dark"] .simple-tree-sidebar .nav-tree-item:hover,
html[data-sidebar="dark"] .simple-tree-sidebar .sub-tree-link:hover,
html[data-sidebar="dark"] .simple-tree-sidebar .nav-tree-header:hover {
    background: #1e293b !important;
    color: #f8fafc !important;
}
html[data-sidebar="dark"] .simple-tree-sidebar .group-title {
    color: #64748b !important;
}
html[data-sidebar="dark"] .simple-tree-sidebar .nav-tree-item.active-tree-item,
html[data-sidebar="dark"] .simple-tree-sidebar .sub-tree-link.active-tree-item {
    background: #1e293b !important;
    color: #38bdf8 !important;
    border: 1px solid #334155 !important;
}
html[data-sidebar="dark"] .simple-tree-sidebar .sub-tree-link::before {
    border-color: #334155 !important;
}
html[data-sidebar="dark"] .simple-tree-sidebar .badge-category-reg {
    background: rgba(37, 99, 235, 0.18) !important;
    color: #60a5fa !important;
    border-color: rgba(59, 130, 246, 0.3) !important;
}
html[data-sidebar="dark"] .simple-tree-sidebar .badge-category-unreg {
    background: rgba(234, 88, 12, 0.18) !important;
    color: #fb923c !important;
    border-color: rgba(251, 146, 60, 0.3) !important;
}
html[data-sidebar="dark"] .simple-tree-sidebar .sub-link-reg .sub-link-icon {
    color: #60a5fa !important;
}
html[data-sidebar="dark"] .simple-tree-sidebar .sub-link-unreg .sub-link-icon {
    color: #fb923c !important;
}
html[data-sidebar="dark"] .simple-tree-sidebar .sub-tree-link.active-tree-reg {
    background: #1e293b !important;
    color: #60a5fa !important;
    border: 1px solid rgba(59, 130, 246, 0.4) !important;
}
html[data-sidebar="dark"] .simple-tree-sidebar .sub-tree-link.active-tree-unreg {
    background: #1e293b !important;
    color: #fb923c !important;
    border: 1px solid rgba(251, 146, 60, 0.4) !important;
}

/* 2. LIGHT SIDEBAR */
html[data-sidebar="light"] .simple-tree-sidebar,
html[data-sidebar="light"] .sidebar {
    background: #ffffff !important;
    border-right: 1px solid #e2e8f0 !important;
}
html[data-sidebar="light"] .simple-tree-sidebar .sidebar-header-simple {
    background: #ffffff !important;
    border-bottom: 1px solid #e2e8f0 !important;
}
html[data-sidebar="light"] .simple-tree-sidebar .sidebar-scroll-simple {
    background: #f8fafc !important;
}
html[data-sidebar="light"] .simple-tree-sidebar .nav-tree-item,
html[data-sidebar="light"] .simple-tree-sidebar .sub-tree-link {
    color: #334155 !important;
}
html[data-sidebar="light"] .simple-tree-sidebar .nav-tree-item:hover,
html[data-sidebar="light"] .simple-tree-sidebar .sub-tree-link:hover,
html[data-sidebar="light"] .simple-tree-sidebar .nav-tree-header:hover {
    background: #f1f5f9 !important;
    color: #0f172a !important;
}
html[data-sidebar="light"] .simple-tree-sidebar .nav-tree-item.active-tree-item,
html[data-sidebar="light"] .simple-tree-sidebar .sub-tree-link.active-tree-item {
    background: #ffffff !important;
    border: 1px solid #e2e8f0 !important;
    color: #1d4ed8 !important;
}
html[data-sidebar="light"] .simple-tree-sidebar .badge-category-reg {
    background: #eff6ff !important;
    color: #1d4ed8 !important;
    border: 1px solid #bfdbfe !important;
}
html[data-sidebar="light"] .simple-tree-sidebar .badge-category-unreg {
    background: #fff7ed !important;
    color: #c2410c !important;
    border: 1px solid #fed7aa !important;
}
html[data-sidebar="light"] .simple-tree-sidebar .sub-link-reg .sub-link-icon {
    color: #2563eb !important;
}
html[data-sidebar="light"] .simple-tree-sidebar .sub-link-unreg .sub-link-icon {
    color: #ea580c !important;
}
html[data-sidebar="light"] .simple-tree-sidebar .sub-tree-link.active-tree-reg {
    background: #ffffff !important;
    border: 1px solid #bfdbfe !important;
    color: #1d4ed8 !important;
}
html[data-sidebar="light"] .simple-tree-sidebar .sub-tree-link.active-tree-unreg {
    background: #ffffff !important;
    border: 1px solid #fed7aa !important;
    color: #c2410c !important;
}

/* 3. COSMIC SIDEBAR */
html[data-sidebar="cosmic"] .simple-tree-sidebar,
html[data-sidebar="cosmic"] .sidebar {
    background: #050b1a !important;
    border-right: 1px solid rgba(0, 173, 239, 0.3) !important;
}
html[data-sidebar="cosmic"] .simple-tree-sidebar .sidebar-header-simple {
    background: #020617 !important;
    border-bottom: 1px solid rgba(0, 173, 239, 0.25) !important;
}
html[data-sidebar="cosmic"] .simple-tree-sidebar .sidebar-scroll-simple {
    background: #050b1a !important;
}
html[data-sidebar="cosmic"] .simple-tree-sidebar .nav-tree-item,
html[data-sidebar="cosmic"] .simple-tree-sidebar .sub-tree-link {
    color: #94a3b8 !important;
}
html[data-sidebar="cosmic"] .simple-tree-sidebar .nav-tree-item:hover,
html[data-sidebar="cosmic"] .simple-tree-sidebar .sub-tree-link:hover,
html[data-sidebar="cosmic"] .simple-tree-sidebar .nav-tree-header:hover {
    background: rgba(0, 173, 239, 0.12) !important;
    color: #00adef !important;
}
html[data-sidebar="cosmic"] .simple-tree-sidebar .nav-tree-item.active-tree-item,
html[data-sidebar="cosmic"] .simple-tree-sidebar .sub-tree-link.active-tree-item {
    background: rgba(0, 173, 239, 0.18) !important;
    color: #00adef !important;
    border: 1px solid rgba(0, 173, 239, 0.4) !important;
    box-shadow: 0 0 12px rgba(0, 173, 239, 0.25) !important;
}
html[data-sidebar="cosmic"] .simple-tree-sidebar .badge-category-reg {
    background: rgba(0, 173, 239, 0.15) !important;
    color: #38bdf8 !important;
    border: 1px solid rgba(0, 173, 239, 0.35) !important;
}
html[data-sidebar="cosmic"] .simple-tree-sidebar .badge-category-unreg {
    background: rgba(244, 63, 94, 0.15) !important;
    color: #fb7185 !important;
    border: 1px solid rgba(244, 63, 94, 0.35) !important;
}
html[data-sidebar="cosmic"] .simple-tree-sidebar .sub-link-reg .sub-link-icon {
    color: #38bdf8 !important;
}
html[data-sidebar="cosmic"] .simple-tree-sidebar .sub-link-unreg .sub-link-icon {
    color: #fb7185 !important;
}
html[data-sidebar="cosmic"] .simple-tree-sidebar .sub-tree-link.active-tree-reg {
    background: rgba(0, 173, 239, 0.18) !important;
    color: #38bdf8 !important;
    border: 1px solid rgba(0, 173, 239, 0.5) !important;
    box-shadow: 0 0 10px rgba(0, 173, 239, 0.25) !important;
}
html[data-sidebar="cosmic"] .simple-tree-sidebar .sub-tree-link.active-tree-unreg {
    background: rgba(244, 63, 94, 0.18) !important;
    color: #fb7185 !important;
    border: 1px solid rgba(244, 63, 94, 0.5) !important;
    box-shadow: 0 0 10px rgba(244, 63, 94, 0.25) !important;
}

/* 4. BLUE NAVY SIDEBAR */
html[data-sidebar="blue"] .simple-tree-sidebar,
html[data-sidebar="blue"] .sidebar {
    background: linear-gradient(180deg, #1e3a8a 0%, #0f172a 100%) !important;
    border-right: 1px solid rgba(255, 255, 255, 0.1) !important;
}
html[data-sidebar="blue"] .simple-tree-sidebar .sidebar-header-simple {
    background: rgba(15, 23, 42, 0.6) !important;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
}
html[data-sidebar="blue"] .simple-tree-sidebar .sidebar-scroll-simple {
    background: transparent !important;
}
html[data-sidebar="blue"] .simple-tree-sidebar .nav-tree-item,
html[data-sidebar="blue"] .simple-tree-sidebar .sub-tree-link {
    color: #cbd5e1 !important;
}
html[data-sidebar="blue"] .simple-tree-sidebar .nav-tree-item:hover,
html[data-sidebar="blue"] .simple-tree-sidebar .sub-tree-link:hover,
html[data-sidebar="blue"] .simple-tree-sidebar .nav-tree-header:hover {
    background: rgba(255, 255, 255, 0.1) !important;
    color: #ffffff !important;
}
html[data-sidebar="blue"] .simple-tree-sidebar .nav-tree-item.active-tree-item,
html[data-sidebar="blue"] .simple-tree-sidebar .sub-tree-link.active-tree-item {
    background: rgba(255, 255, 255, 0.2) !important;
    color: #ffffff !important;
    border: 1px solid rgba(255, 255, 255, 0.3) !important;
}
html[data-sidebar="blue"] .simple-tree-sidebar .badge-category-reg {
    background: rgba(147, 197, 253, 0.15) !important;
    color: #93c5fd !important;
    border: 1px solid rgba(147, 197, 253, 0.3) !important;
}
html[data-sidebar="blue"] .simple-tree-sidebar .badge-category-unreg {
    background: rgba(253, 186, 116, 0.15) !important;
    color: #fdba74 !important;
    border: 1px solid rgba(253, 186, 116, 0.3) !important;
}
html[data-sidebar="blue"] .simple-tree-sidebar .sub-link-reg .sub-link-icon {
    color: #93c5fd !important;
}
html[data-sidebar="blue"] .simple-tree-sidebar .sub-link-unreg .sub-link-icon {
    color: #fdba74 !important;
}
html[data-sidebar="blue"] .simple-tree-sidebar .sub-tree-link.active-tree-reg {
    background: rgba(255, 255, 255, 0.15) !important;
    color: #93c5fd !important;
    border: 1px solid rgba(147, 197, 253, 0.4) !important;
}
html[data-sidebar="blue"] .simple-tree-sidebar .sub-tree-link.active-tree-unreg {
    background: rgba(255, 255, 255, 0.15) !important;
    color: #fdba74 !important;
    border: 1px solid rgba(253, 186, 116, 0.4) !important;
}

/* Sidebar Brand Logo Dark/Light Theme Switching */
html[data-sidebar="dark"] .simple-tree-sidebar .brand-logo-simple.logo-dark-version,
html[data-sidebar="cosmic"] .simple-tree-sidebar .brand-logo-simple.logo-dark-version,
html[data-sidebar="blue"] .simple-tree-sidebar .brand-logo-simple.logo-dark-version {
    display: none !important;
}
html[data-sidebar="dark"] .simple-tree-sidebar .brand-logo-simple.logo-light-version,
html[data-sidebar="cosmic"] .simple-tree-sidebar .brand-logo-simple.logo-light-version,
html[data-sidebar="blue"] .simple-tree-sidebar .brand-logo-simple.logo-light-version {
    display: block !important;
}
html[data-sidebar="light"] .simple-tree-sidebar .brand-logo-simple.logo-dark-version {
    display: block !important;
}
html[data-sidebar="light"] .simple-tree-sidebar .brand-logo-simple.logo-light-version {
    display: none !important;
}

/* ==========================================================================
   DYNAMIC TOPBAR (NAVBAR) THEMES
   ========================================================================== */
/* 1. LIGHT TOPBAR */
html[data-topbar="light"] .navbar {
    background: #ffffff !important;
    border-bottom: 1px solid #e2e8f0 !important;
}
html[data-topbar="light"] .navbar .sidebar-toggler,
html[data-topbar="light"] .navbar .text-muted {
    color: #64748b !important;
}

/* 2. DARK TOPBAR */
html[data-topbar="dark"] .navbar {
    background: #0f172a !important;
    border-bottom: 1px solid #1e293b !important;
}
html[data-topbar="dark"] .navbar .sidebar-toggler,
html[data-topbar="dark"] .navbar .text-muted {
    color: #cbd5e1 !important;
}
html[data-topbar="dark"] .navbar .portal-pill {
    background: #1e293b !important;
    color: #94a3b8 !important;
    border-color: #334155 !important;
}
html[data-topbar="dark"] .navbar #topbarThemeQuickToggle {
    background: #1e293b !important;
    border-color: #334155 !important;
}

/* 3. COSMIC TOPBAR */
html[data-topbar="cosmic"] .navbar {
    background: rgba(8, 18, 38, 0.95) !important;
    backdrop-filter: blur(12px) !important;
    -webkit-backdrop-filter: blur(12px) !important;
    border-bottom: 1px solid rgba(0, 173, 239, 0.3) !important;
}
html[data-topbar="cosmic"] .navbar .sidebar-toggler,
html[data-topbar="cosmic"] .navbar .text-muted {
    color: #00adef !important;
}
html[data-topbar="cosmic"] .navbar .portal-pill {
    background: rgba(12, 26, 54, 0.8) !important;
    color: #cbd5e1 !important;
    border-color: rgba(0, 173, 239, 0.3) !important;
}
html[data-topbar="cosmic"] .navbar #topbarThemeQuickToggle {
    background: #0c1a36 !important;
    border-color: rgba(0, 173, 239, 0.4) !important;
}

/* 4. BLUE NAVY TOPBAR */
html[data-topbar="blue"] .navbar {
    background: #1e3a8a !important;
    border-bottom: 1px solid rgba(255, 255, 255, 0.15) !important;
}
html[data-topbar="blue"] .navbar .sidebar-toggler,
html[data-topbar="blue"] .navbar .text-muted {
    color: #ffffff !important;
}
html[data-topbar="blue"] .navbar .portal-pill {
    background: rgba(255, 255, 255, 0.15) !important;
    color: #ffffff !important;
    border-color: rgba(255, 255, 255, 0.25) !important;
}
html[data-topbar="blue"] .navbar #topbarThemeQuickToggle {
    background: rgba(255, 255, 255, 0.2) !important;
    border-color: rgba(255, 255, 255, 0.3) !important;
}

/* Animations ON / OFF */
html.no-nav-animations .simple-tree-sidebar *,
html.no-nav-animations .navbar * {
    transition: none !important;
    animation: none !important;
}

/* Active Bar Indicator */
.active-bar-indicator {
    width: 3px;
    height: 14px;
    background: var(--mai-primary, #2563eb);
    border-radius: 2px;
    display: inline-block;
    margin-right: 6px;
}
</style>

<script>
(function() {
    const trigger = document.getElementById('themeCustomizerTrigger');
    const drawer = document.getElementById('themeCustomizerDrawer');
    const closeBtn = document.getElementById('themeCustomizerClose');
    const backdrop = document.getElementById('themeCustomizerBackdrop');

    function openDrawer() {
        drawer.classList.add('open');
        backdrop.classList.add('show');
        if (typeof feather !== 'undefined') { feather.replace(); }
    }

    function closeDrawer() {
        drawer.classList.remove('open');
        backdrop.classList.remove('show');
    }

    trigger?.addEventListener('click', openDrawer);
    closeBtn?.addEventListener('click', closeDrawer);
    backdrop?.addEventListener('click', closeDrawer);

    const isSaturnusRoute = {{ (request()->is('saturnus*') || request()->is('form-registrasi*') || request()->is('form-unregistrasi*') || request()->is('proses-approval*') || request()->is('data-view*')) ? 'true' : 'false' }};
    const sidebarStorageKey = isSaturnusRoute ? 'saturnus_sidebar_theme' : 'mars_sidebar_theme';
    const topbarStorageKey = isSaturnusRoute ? 'saturnus_topbar_theme' : 'mars_topbar_theme';

    // 1. Sidebar Theme Switcher
    const sidebarBtns = document.querySelectorAll('[data-set-sidebar]');
    sidebarBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const sbTheme = this.dataset.setSidebar;
            setSidebarTheme(sbTheme);
        });
    });

    function setSidebarTheme(theme) {
        document.documentElement.setAttribute('data-sidebar', theme);
        localStorage.setItem(sidebarStorageKey, theme);
        sidebarBtns.forEach(b => {
            b.classList.toggle('active', b.dataset.setSidebar === theme);
        });
    }

    // 2. Topbar Theme Switcher
    const topbarBtns = document.querySelectorAll('[data-set-topbar]');
    topbarBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const tbTheme = this.dataset.setTopbar;
            setTopbarTheme(tbTheme);
        });
    });

    function setTopbarTheme(theme) {
        document.documentElement.setAttribute('data-topbar', theme);
        localStorage.setItem(topbarStorageKey, theme);
        topbarBtns.forEach(b => {
            b.classList.toggle('active', b.dataset.setTopbar === theme);
        });

        const quickIcon = document.getElementById('topbarThemeIcon');
        if (quickIcon) {
            quickIcon.setAttribute('data-feather', theme === 'dark' ? 'moon' : (theme === 'cosmic' ? 'disc' : 'sun'));
            if (typeof feather !== 'undefined') { feather.replace(); }
        }
    }

    // 3. Accent Color Switcher
    const accentBtns = document.querySelectorAll('[data-set-accent]');
    accentBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const accent = this.dataset.setAccent;
            setAccentColor(accent);
        });
    });

    function setAccentColor(accent) {
        document.documentElement.setAttribute('data-accent', accent);
        localStorage.setItem('mai_portal_accent', accent);
        accentBtns.forEach(b => {
            b.classList.toggle('active', b.dataset.setAccent === accent);
        });
    }

    // 4. Animation Toggles
    const navAnimSwitch = document.getElementById('toggleNavAnimations');
    navAnimSwitch?.addEventListener('change', function() {
        if (this.checked) {
            document.documentElement.classList.remove('no-nav-animations');
            localStorage.setItem('mai_nav_animations', 'true');
        } else {
            document.documentElement.classList.add('no-nav-animations');
            localStorage.setItem('mai_nav_animations', 'false');
        }
    });

    // Reset Defaults
    const resetBtn = document.getElementById('resetThemeDefaultsBtn');
    resetBtn?.addEventListener('click', function() {
        const defaultSb = isSaturnusRoute ? 'cosmic' : 'light';
        const defaultTb = isSaturnusRoute ? 'cosmic' : 'light';
        const defaultAc = isSaturnusRoute ? 'purple' : 'blue';

        setSidebarTheme(defaultSb);
        setTopbarTheme(defaultTb);
        setAccentColor(defaultAc);
        if (navAnimSwitch) {
            navAnimSwitch.checked = true;
            document.documentElement.classList.remove('no-nav-animations');
        }
        localStorage.removeItem('saturnus_sidebar_theme');
        localStorage.removeItem('saturnus_topbar_theme');
        localStorage.removeItem('mars_sidebar_theme');
        localStorage.removeItem('mars_topbar_theme');
        localStorage.removeItem('mai_portal_accent');
        localStorage.removeItem('mai_nav_animations');
    });

    // Load active settings on init
    const defaultSb = isSaturnusRoute ? 'cosmic' : 'light';
    const defaultTb = isSaturnusRoute ? 'cosmic' : 'light';
    const defaultAc = isSaturnusRoute ? 'purple' : 'blue';

    const savedSidebar = localStorage.getItem(sidebarStorageKey) || defaultSb;
    const savedTopbar = localStorage.getItem(topbarStorageKey) || defaultTb;
    const savedAccent = localStorage.getItem('mai_portal_accent') || defaultAc;
    const savedNavAnim = localStorage.getItem('mai_nav_animations') !== 'false';

    setSidebarTheme(savedSidebar);
    setTopbarTheme(savedTopbar);
    setAccentColor(savedAccent);
    if (navAnimSwitch) {
        navAnimSwitch.checked = savedNavAnim;
        if (!savedNavAnim) {
            document.documentElement.classList.add('no-nav-animations');
        }
    }
})();
</script>
