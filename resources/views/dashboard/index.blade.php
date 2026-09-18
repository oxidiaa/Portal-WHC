@extends('layouts.app')

@section('title', 'ORBIT — Space Exploration Portal')

@section('content')

@php
    $user = auth()->user();
    $userRole = strtoupper(trim($user->role ?? 'GUEST'));
    $isMasterOrAdmin = in_array($userRole, ['MASTER', 'ADMIN']) || ($user && $user->isMaster());
    
    $canAccessMars = $user && ($isMasterOrAdmin || $user->canAccessModule('mars') || $user->hasPermission('mars.*'));
    $canAccessSaturnus = $user && ($isMasterOrAdmin || $user->canAccessModule('saturnus') || $user->hasPermission('saturnus.*'));
@endphp

<!-- Three.js 3D WebGL Engine from CDN -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>

<style>
/* ==========================================================================
   🪐 ORBIT CINEMATIC SPACE PORTAL DESIGN SYSTEM
   ========================================================================== */
:root {
    --orbit-bg-darkest: #03050a;
    --orbit-bg-darker: #070b14;
    --orbit-bg-dark: #0b1020;
    
    --orbit-mars-primary: #ea580c;
    --orbit-mars-glow: rgba(234, 88, 12, 0.35);
    --orbit-mars-ambient: rgba(194, 65, 12, 0.18);
    
    --orbit-saturn-primary: #fbbf24;
    --orbit-saturn-glow: rgba(251, 191, 36, 0.35);
    --orbit-saturn-ambient: rgba(217, 119, 6, 0.18);

    --orbit-text-white: #f8fafc;
    --orbit-text-muted: #94a3b8;
    --orbit-text-subtle: #64748b;
    --orbit-border-subtle: rgba(255, 255, 255, 0.08);
}

/* Force Fullscreen Space Canvas on ORBIT Dashboard */
html, body, .main-wrapper, .page-wrapper, .page-content {
    background-color: var(--orbit-bg-darkest) !important;
    background: radial-gradient(circle at 50% 30%, #0a1128 0%, #050b18 50%, #03050a 100%) !important;
    color: var(--orbit-text-white) !important;
    overflow-x: hidden !important;
    margin: 0 !important;
    padding: 0 !important;
    min-height: 100vh !important;
}

.page-wrapper {
    background-color: var(--orbit-bg-darkest) !important;
}

.page-content {
    padding: 0 !important;
    background: transparent !important;
    position: relative !important;
    min-height: 100vh !important;
}

/* Hide standard topbar/footer on ORBIT page to give edge-to-edge immersion */
.navbar, footer, .footer {
    display: none !important;
}

.sidebar {
    transform: translateX(-100%) !important;
    transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1) !important;
}
.sidebar.mobile-open {
    transform: translateX(0) !important;
    z-index: 99999 !important;
}

/* Main Orbit Container */
.orbit-universe-container {
    position: relative;
    width: 100vw;
    height: 100vh;
    min-height: 700px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    user-select: none;
    transition: opacity 0.75s cubic-bezier(0.16, 1, 0.3, 1), transform 0.75s cubic-bezier(0.16, 1, 0.3, 1);
}

/* Cinematic Portal Entrance Transition from Galaxy Login */
.orbit-universe-container.portal-entrance-init {
    opacity: 0 !important;
    transform: scale(0.98) !important;
}
.orbit-universe-container.portal-entrance-active {
    opacity: 1 !important;
    transform: scale(1) !important;
}

/* 3D WebGL Three.js Canvas */
.orbit-webgl-canvas {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 1;
    pointer-events: auto;
}

/* Ambient Vignette Overlay for Hover Reactions */
.orbit-ambient-vignette {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    z-index: 2;
    transition: background 0.8s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.8s ease;
    background: radial-gradient(ellipse at 50% 50%, transparent 40%, rgba(3, 5, 10, 0.85) 100%);
    opacity: 0.9;
}

.orbit-ambient-vignette.hover-mars {
    background: radial-gradient(circle at 25% 50%, rgba(194, 65, 12, 0.22) 0%, transparent 60%),
                radial-gradient(ellipse at 50% 50%, transparent 40%, rgba(3, 5, 10, 0.9) 100%);
}

.orbit-ambient-vignette.hover-saturn {
    background: radial-gradient(circle at 75% 50%, rgba(217, 119, 6, 0.22) 0%, transparent 60%),
                radial-gradient(ellipse at 50% 50%, transparent 40%, rgba(3, 5, 10, 0.9) 100%);
}

/* Warp Transition Overlay */
.orbit-warp-curtain {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    z-index: 99999;
    pointer-events: none;
    opacity: 0;
    transition: opacity 0.6s cubic-bezier(0.16, 1, 0.3, 1), transform 0.6s ease;
    background: #03050a;
}
.orbit-warp-curtain.warp-mars {
    background: radial-gradient(circle at center, #ea580c 0%, #7c2d12 40%, #03050a 100%);
    opacity: 1;
}
.orbit-warp-curtain.warp-saturn {
    background: radial-gradient(circle at center, #fbbf24 0%, #b45309 40%, #03050a 100%);
    opacity: 1;
}

/* ==========================================================================
   MINIMALIST TOP NAVIGATION BAR
   ========================================================================== */
.orbit-navbar {
    position: relative;
    z-index: 10;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.75rem 3rem;
    pointer-events: auto;
    opacity: 0;
    transform: translateY(-10px);
    transition: all 1s cubic-bezier(0.16, 1, 0.3, 1) 2.2s;
}
.orbit-navbar.revealed {
    opacity: 1;
    transform: translateY(0);
}

.orbit-brand {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    text-decoration: none;
    color: var(--orbit-text-white);
}

.orbit-logo-ring {
    width: 28px;
    height: 28px;
    border: 2px solid rgba(255, 255, 255, 0.7);
    border-radius: 50%;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 0 15px rgba(255, 255, 255, 0.2);
}
.orbit-logo-ring::after {
    content: '';
    position: absolute;
    width: 38px;
    height: 12px;
    border: 1.5px solid rgba(255, 255, 255, 0.4);
    border-radius: 50%;
    transform: rotate(-25deg);
}
.orbit-logo-dot {
    width: 8px;
    height: 8px;
    background: #ffffff;
    border-radius: 50%;
    box-shadow: 0 0 8px #ffffff;
}

.orbit-brand-text {
    font-family: 'Outfit', 'Space Grotesk', sans-serif;
    font-size: 1.35rem;
    font-weight: 800;
    letter-spacing: 0.22em;
    text-transform: uppercase;
    color: #ffffff;
}

.orbit-nav-links {
    display: flex;
    align-items: center;
    gap: 2.25rem;
    list-style: none;
    margin: 0;
    padding: 0;
}

.orbit-nav-link {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 0.78rem;
    font-weight: 600;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: var(--orbit-text-muted);
    text-decoration: none;
    transition: color 0.2s ease, text-shadow 0.2s ease;
    cursor: pointer;
    background: none;
    border: none;
    padding: 0;
}
.orbit-nav-link:hover {
    color: #ffffff;
    text-shadow: 0 0 12px rgba(255, 255, 255, 0.5);
}

.orbit-nav-right {
    display: flex;
    align-items: center;
    gap: 1.5rem;
}

.orbit-sector-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    font-family: 'JetBrains Mono', monospace;
    font-size: 0.7rem;
    letter-spacing: 0.08em;
    color: #94a3b8;
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid var(--orbit-border-subtle);
    padding: 0.4rem 0.85rem;
    border-radius: 9999px;
    backdrop-filter: blur(8px);
}
.orbit-status-dot {
    width: 6px;
    height: 6px;
    background: #10b981;
    border-radius: 50%;
    box-shadow: 0 0 8px #10b981;
    animation: pulseBeacon 2s infinite ease-in-out;
}
@keyframes pulseBeacon {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.4; transform: scale(0.85); }
}

.orbit-sidebar-toggle-btn {
    background: rgba(255, 255, 255, 0.04);
    border: 1px solid var(--orbit-border-subtle);
    color: var(--orbit-text-muted);
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
}
.orbit-sidebar-toggle-btn:hover {
    color: #ffffff;
    border-color: rgba(255, 255, 255, 0.3);
    background: rgba(255, 255, 255, 0.08);
}

/* ==========================================================================
   CENTER HERO TYPOGRAPHY & DESTINATION PORTALS
   ========================================================================== */
.orbit-hero-content {
    position: relative;
    z-index: 10;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    flex: 1;
    padding: 0 2rem;
    pointer-events: none;
}

/* Opening Text Sequence */
.orbit-hero-header {
    text-align: center;
    margin-bottom: 2rem;
    opacity: 0;
    transform: translateY(16px);
    transition: opacity 1.2s cubic-bezier(0.16, 1, 0.3, 1) 2.6s, transform 1.2s cubic-bezier(0.16, 1, 0.3, 1) 2.6s;
}
.orbit-hero-header.revealed {
    opacity: 1;
    transform: translateY(0);
}

.orbit-welcome-tag {
    font-family: 'JetBrains Mono', 'Space Grotesk', monospace;
    font-size: 0.8rem;
    font-weight: 600;
    letter-spacing: 0.35em;
    text-transform: uppercase;
    color: #94a3b8;
    margin-bottom: 0.6rem;
    display: inline-block;
}

.orbit-main-heading {
    font-family: 'Outfit', 'Space Grotesk', sans-serif;
    font-size: clamp(1.8rem, 3.8vw, 3rem);
    font-weight: 300;
    letter-spacing: 0.18em;
    text-transform: uppercase;
    color: #f8fafc;
    margin: 0;
    line-height: 1.2;
    text-shadow: 0 4px 24px rgba(0, 0, 0, 0.8);
}

/* Dual Planetary Interactive Grid */
.orbit-destinations-row {
    display: flex;
    align-items: center;
    justify-content: space-around;
    width: 100%;
    max-width: 1200px;
    margin-top: 1rem;
    pointer-events: none;
    opacity: 0;
    transform: translateY(20px);
    transition: opacity 1.2s cubic-bezier(0.16, 1, 0.3, 1) 3.2s, transform 1.2s cubic-bezier(0.16, 1, 0.3, 1) 3.2s;
}
.orbit-destinations-row.revealed {
    opacity: 1;
    transform: translateY(0);
}

/* Individual Destination Focal Unit */
.destination-unit {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    pointer-events: auto;
    cursor: pointer;
    position: relative;
    padding: 1.5rem;
    border-radius: 20px;
    width: 360px;
    transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.4s ease;
}

.destination-unit.dimmed {
    opacity: 0.35;
    transform: scale(0.96);
}

/* Space Placeholder for 3D Planets (Canvas Renders in Background at these Coordinates) */
.planet-interactive-anchor {
    width: 220px;
    height: 220px;
    margin-bottom: 1.5rem;
    position: relative;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Orbital Connecting Arc Indicator */
.orbit-connecting-arc {
    position: absolute;
    width: 100%;
    height: 100%;
    border: 1px dashed rgba(255, 255, 255, 0.12);
    border-radius: 50%;
    pointer-events: none;
    transform: rotate(-15deg);
    transition: all 0.4s ease;
}
.destination-unit:hover .orbit-connecting-arc {
    border-color: rgba(255, 255, 255, 0.35);
    border-style: solid;
    box-shadow: 0 0 20px rgba(255, 255, 255, 0.15);
}

/* Planet Labels & Subtext */
.destination-title {
    font-family: 'Outfit', 'Space Grotesk', sans-serif;
    font-size: 2rem;
    font-weight: 700;
    letter-spacing: 0.22em;
    text-transform: uppercase;
    color: #ffffff;
    margin: 0 0 0.35rem 0;
    transition: all 0.3s ease;
}

.destination-unit.unit-mars:hover .destination-title {
    color: #fb923c;
    text-shadow: 0 0 20px rgba(251, 146, 60, 0.6);
}
.destination-unit.unit-saturn:hover .destination-title {
    color: #fde047;
    text-shadow: 0 0 20px rgba(253, 224, 71, 0.6);
}

.destination-subtitle {
    font-family: 'JetBrains Mono', monospace;
    font-size: 0.75rem;
    font-weight: 500;
    letter-spacing: 0.25em;
    text-transform: uppercase;
    color: var(--orbit-text-muted);
    margin-bottom: 1rem;
    transition: color 0.3s ease;
}

/* Telemetry Mini Data Reveal */
.destination-telemetry-hud {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
    margin-bottom: 1.25rem;
    opacity: 0;
    transform: translateY(6px);
    transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1);
    height: 0;
    overflow: hidden;
}
.destination-unit:hover .destination-telemetry-hud {
    opacity: 1;
    transform: translateY(0);
    height: auto;
}

.telemetry-row {
    font-family: 'JetBrains Mono', monospace;
    font-size: 0.68rem;
    letter-spacing: 0.08em;
    color: #94a3b8;
    background: rgba(255, 255, 255, 0.04);
    padding: 0.25rem 0.65rem;
    border-radius: 4px;
    border: 1px solid rgba(255, 255, 255, 0.06);
}
.telemetry-row strong {
    color: #f8fafc;
}

/* Enter Destination Button */
.btn-enter-orbit {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.65rem;
    font-family: 'Space Grotesk', 'Outfit', sans-serif;
    font-size: 0.82rem;
    font-weight: 700;
    letter-spacing: 0.18em;
    text-transform: uppercase;
    text-decoration: none;
    padding: 0.75rem 1.65rem;
    border-radius: 9999px;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    background: rgba(255, 255, 255, 0.04);
    color: #ffffff;
    backdrop-filter: blur(12px);
}

.unit-mars .btn-enter-orbit:hover {
    background: #ea580c;
    border-color: #ea580c;
    color: #ffffff;
    box-shadow: 0 0 25px rgba(234, 88, 12, 0.55);
    transform: translateY(-2px);
}

.unit-saturn .btn-enter-orbit:hover {
    background: #d97706;
    border-color: #d97706;
    color: #ffffff;
    box-shadow: 0 0 25px rgba(217, 119, 6, 0.55);
    transform: translateY(-2px);
}

.btn-arrow-icon {
    width: 14px;
    height: 14px;
    transition: transform 0.25s ease;
}
.btn-enter-orbit:hover .btn-arrow-icon {
    transform: translateX(4px);
}

/* ==========================================================================
   MINIMALIST BOTTOM TELEMETRY DOCK
   ========================================================================== */
.orbit-bottom-dock {
    position: relative;
    z-index: 10;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.5rem 3rem;
    pointer-events: auto;
    opacity: 0;
    transform: translateY(10px);
    transition: all 1s cubic-bezier(0.16, 1, 0.3, 1) 3.6s;
}
.orbit-bottom-dock.revealed {
    opacity: 1;
    transform: translateY(0);
}

.dock-kpi-group {
    display: flex;
    align-items: center;
    gap: 1.75rem;
}

.dock-kpi-item {
    display: flex;
    align-items: center;
    gap: 0.6rem;
}
.dock-kpi-icon {
    width: 28px;
    height: 28px;
    border-radius: 6px;
    background: rgba(255, 255, 255, 0.04);
    border: 1px solid var(--orbit-border-subtle);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #94a3b8;
}
.dock-kpi-details {
    display: flex;
    flex-direction: column;
}
.dock-kpi-label {
    font-family: 'JetBrains Mono', monospace;
    font-size: 0.65rem;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: #64748b;
}
.dock-kpi-val {
    font-family: 'Outfit', sans-serif;
    font-size: 0.88rem;
    font-weight: 700;
    color: #f8fafc;
}

.dock-info-copyright {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 0.72rem;
    color: #64748b;
}

/* ==========================================================================
   SLIDE-OUT INFO MODALS (EXPLORE, MISSIONS, ABOUT)
   ========================================================================== */
.orbit-info-drawer {
    position: fixed;
    top: 0;
    right: -420px;
    width: 380px;
    height: 100vh;
    background: rgba(7, 11, 20, 0.95);
    backdrop-filter: blur(24px);
    border-left: 1px solid rgba(255, 255, 255, 0.12);
    box-shadow: -10px 0 40px rgba(0, 0, 0, 0.8);
    z-index: 10000;
    display: flex;
    flex-direction: column;
    transition: right 0.35s cubic-bezier(0.16, 1, 0.3, 1);
    font-family: 'Plus Jakarta Sans', sans-serif;
    color: #f8fafc;
    padding: 2rem;
}
.orbit-info-drawer.open {
    right: 0;
}

.orbit-drawer-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    padding-bottom: 1.25rem;
    margin-bottom: 1.5rem;
}
.orbit-drawer-title {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.15rem;
    font-weight: 700;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    margin: 0;
    color: #ffffff;
}
.orbit-drawer-close {
    background: none;
    border: none;
    color: #94a3b8;
    font-size: 1.5rem;
    cursor: pointer;
    line-height: 1;
}
.orbit-drawer-close:hover { color: #ffffff; }

.orbit-drawer-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
    z-index: 9999;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.3s ease;
}
.orbit-drawer-backdrop.show {
    opacity: 1;
    pointer-events: auto;
}

/* ==========================================================================
   RESPONSIVE ADJUSTMENTS
   ========================================================================== */
@media (max-width: 992px) {
    .orbit-navbar { padding: 1.25rem 1.5rem; }
    .orbit-bottom-dock { padding: 1.25rem 1.5rem; flex-direction: column; gap: 1rem; text-align: center; }
    .orbit-destinations-row { flex-direction: column; gap: 2rem; margin-top: 0.5rem; }
    .destination-unit { width: 100%; max-width: 320px; padding: 0.75rem; }
    .planet-interactive-anchor { width: 160px; height: 160px; margin-bottom: 0.75rem; }
    .orbit-nav-links { display: none; }
}
</style>

<!-- Main ORBIT Container -->
<div class="orbit-universe-container" id="orbitContainer">
    <!-- WebGL Three.js Planetary Canvas -->
    <div id="orbitWebglCanvas" class="orbit-webgl-canvas"></div>

    <!-- Ambient Vignette Glow Reactive Layer -->
    <div class="orbit-ambient-vignette" id="ambientVignette"></div>

    <!-- Cinematic Warp Curtain -->
    <div class="orbit-warp-curtain" id="warpCurtain"></div>

    <!-- 1. Minimal Top Navigation -->
    <nav class="orbit-navbar" id="orbitNavbar">
        <a href="{{ route('dashboard.index') }}" class="orbit-brand">
            <div class="orbit-logo-ring">
                <div class="orbit-logo-dot"></div>
            </div>
            <span class="orbit-brand-text">ORBIT</span>
        </a>

        <ul class="orbit-nav-links">
            <li><button type="button" class="orbit-nav-link" onclick="openOrbitDrawer('explore')">EXPLORE</button></li>
            <li><button type="button" class="orbit-nav-link" onclick="openOrbitDrawer('missions')">MISSIONS</button></li>
            <li><button type="button" class="orbit-nav-link" onclick="openOrbitDrawer('about')">ABOUT</button></li>
        </ul>

        <div class="orbit-nav-right">
            <div class="orbit-sector-badge">
                <span class="orbit-status-dot"></span>
                <span>SECTOR 01 / SOL SYSTEM</span>
            </div>

            <button type="button" class="orbit-sidebar-toggle-btn" title="Menu Sidebar Portal" onclick="toggleSidebarDrawer()">
                <i data-feather="menu" style="width: 16px; height: 16px;"></i>
            </button>
        </div>
    </nav>

    <!-- 2. Hero Centerpiece & Planetary Destination Choice -->
    <div class="orbit-hero-content">
        <!-- Welcoming Typography -->
        <div class="orbit-hero-header" id="orbitHeader">
            <span class="orbit-welcome-tag">WELCOME TO ORBIT</span>
            <h1 class="orbit-main-heading">CHOOSE YOUR DESTINATION</h1>
        </div>

        <!-- Dual Planetary Selection Row -->
        <div class="orbit-destinations-row" id="destinationsRow">
            
            <!-- MARS DESTINATION (LEFT) -->
            <div class="destination-unit unit-mars {{ !$canAccessMars ? 'access-restricted' : '' }}" id="marsUnit" onclick="navigateToPlanet('mars')">
                <div class="planet-interactive-anchor" id="marsAnchor">
                    <div class="orbit-connecting-arc"></div>
                </div>

                <h2 class="destination-title">MARS</h2>
                <span class="destination-subtitle">THE RED PLANET</span>

                <!-- Telemetry Mini Data -->
                <div class="destination-telemetry-hud">
                    <span class="telemetry-row">ATMOSPHERE: <strong>CO₂ 95.3%</strong></span>
                    <span class="telemetry-row">STOCK MINIM: <strong>{{ number_format($totalItemMinim ?? $marsStats['minim_items'] ?? 0) }} ITEMS</strong></span>
                    <span class="telemetry-row">SYSTEM: <strong>PO &amp; INVENTORY</strong></span>
                </div>

                @if($canAccessMars)
                <a href="javascript:void(0)" class="btn-enter-orbit" onclick="event.stopPropagation(); navigateToPlanet('mars');">
                    <span>ENTER MARS</span>
                    <svg class="btn-arrow-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                        <polyline points="12 5 19 12 12 19"></polyline>
                    </svg>
                </a>
                @else
                <div class="btn-enter-orbit" style="background: rgba(239, 68, 68, 0.2); border-color: rgba(239, 68, 68, 0.4); color: #fca5a5; cursor: not-allowed;">
                    <span>🔒 AKSES TERKUNCI</span>
                </div>
                @endif
            </div>

            <!-- SATURN DESTINATION (RIGHT) -->
            <div class="destination-unit unit-saturn {{ !$canAccessSaturnus ? 'access-restricted' : '' }}" id="saturnUnit" onclick="navigateToPlanet('saturn')">
                <div class="planet-interactive-anchor" id="saturnAnchor">
                    <div class="orbit-connecting-arc"></div>
                </div>

                <h2 class="destination-title">SATURN</h2>
                <span class="destination-subtitle">THE RINGED WORLD</span>

                <!-- Telemetry Mini Data -->
                <div class="destination-telemetry-hud">
                    <span class="telemetry-row">RING SPAN: <strong>282,000 KM</strong></span>
                    <span class="telemetry-row">CONSUMABLES: <strong>{{ number_format($totalConsumables ?? $saturnusStats['total_consumables'] ?? 0) }} ITEMS</strong></span>
                    <span class="telemetry-row">SYSTEM: <strong>REGISTRATION &amp; ASSETS</strong></span>
                </div>

                @if($canAccessSaturnus)
                <a href="javascript:void(0)" class="btn-enter-orbit" onclick="event.stopPropagation(); navigateToPlanet('saturn');">
                    <span>ENTER SATURN</span>
                    <svg class="btn-arrow-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                        <polyline points="12 5 19 12 12 19"></polyline>
                    </svg>
                </a>
                @else
                <div class="btn-enter-orbit" style="background: rgba(239, 68, 68, 0.2); border-color: rgba(239, 68, 68, 0.4); color: #fca5a5; cursor: not-allowed;">
                    <span>🔒 AKSES TERKUNCI</span>
                </div>
                @endif
            </div>

        </div>
    </div>

    <!-- 3. Minimal Bottom Dock -->
    <div class="orbit-bottom-dock" id="orbitBottomDock">
        <div class="dock-kpi-group">
            <div class="dock-kpi-item">
                <div class="dock-kpi-icon">
                    <i data-feather="package" style="width: 14px; height: 14px;"></i>
                </div>
                <div class="dock-kpi-details">
                    <span class="dock-kpi-label">Master Items</span>
                    <span class="dock-kpi-val">{{ number_format($totalItemMaster ?? $marsStats['total_items'] ?? 2898) }}</span>
                </div>
            </div>

            <div class="dock-kpi-item">
                <div class="dock-kpi-icon" style="color: #ea580c;">
                    <i data-feather="alert-circle" style="width: 14px; height: 14px;"></i>
                </div>
                <div class="dock-kpi-details">
                    <span class="dock-kpi-label">Stok Minim</span>
                    <span class="dock-kpi-val" style="color: #ea580c;">{{ number_format($totalItemMinim ?? $marsStats['minim_items'] ?? 0) }}</span>
                </div>
            </div>

            <div class="dock-kpi-item">
                <div class="dock-kpi-icon" style="color: #fbbf24;">
                    <i data-feather="disc" style="width: 14px; height: 14px;"></i>
                </div>
                <div class="dock-kpi-details">
                    <span class="dock-kpi-label">Consumables</span>
                    <span class="dock-kpi-val">{{ number_format($totalConsumables ?? $saturnusStats['total_consumables'] ?? 0) }}</span>
                </div>
            </div>
        </div>

        <div class="dock-info-copyright">
            <span>PT Metalart Astra Indonesia &copy; {{ date('Y') }} · Space Mission Portal v2.0</span>
        </div>
    </div>
</div>

<!-- ==========================================================================
     INTERACTIVE SLIDE-OUT DRAWERS (EXPLORE, MISSIONS, ABOUT)
     ========================================================================== -->
<div class="orbit-info-drawer" id="orbitDrawer">
    <div class="orbit-drawer-header">
        <h4 class="orbit-drawer-title" id="drawerTitle">MISSION BRIEFING</h4>
        <button type="button" class="orbit-drawer-close" onclick="closeOrbitDrawer()">&times;</button>
    </div>
    <div class="orbit-drawer-body" id="drawerContent">
        <!-- Content dynamically injected via JS -->
    </div>
</div>
<div class="orbit-drawer-backdrop" id="orbitDrawerBackdrop" onclick="closeOrbitDrawer()"></div>

<!-- ==========================================================================
     THREE.JS 3D WEBGL ENGINE & CINEMATIC SCRIPT
     ========================================================================== -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof feather !== 'undefined') { feather.replace(); }

    const container = document.getElementById('orbitWebglCanvas');
    if (!container) return;

    // --- 1. Three.js Scene Setup ---
    const scene = new THREE.Scene();
    scene.fog = new THREE.FogExp2(0x03050a, 0.015);

    let width = container.offsetWidth || window.innerWidth;
    let height = container.offsetHeight || window.innerHeight;

    const camera = new THREE.PerspectiveCamera(45, width / height, 0.1, 1000);
    camera.position.set(0, 0, 42); // Start further back for cinematic entry

    const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
    renderer.setSize(width, height);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    renderer.toneMapping = THREE.ACESFilmicToneMapping;
    renderer.toneMappingExposure = 1.2;
    renderer.shadowMap.enabled = true;
    renderer.shadowMap.type = THREE.PCFSoftShadowMap;
    container.appendChild(renderer.domElement);

    // --- 2. Procedural Starfield & Cosmic Dust ---
    const starGeo = new THREE.BufferGeometry();
    const starCount = 1600;
    const starPos = new Float32Array(starCount * 3);
    const starColors = new Float32Array(starCount * 3);

    for (let i = 0; i < starCount; i++) {
        starPos[i * 3] = (Math.random() - 0.5) * 160;
        starPos[i * 3 + 1] = (Math.random() - 0.5) * 120;
        starPos[i * 3 + 2] = -Math.random() * 80 - 10;

        const shade = 0.7 + Math.random() * 0.3;
        starColors[i * 3] = shade * (0.9 + Math.random() * 0.1);
        starColors[i * 3 + 1] = shade * (0.95 + Math.random() * 0.05);
        starColors[i * 3 + 2] = shade;
    }

    starGeo.setAttribute('position', new THREE.BufferAttribute(starPos, 3));
    starGeo.setAttribute('color', new THREE.BufferAttribute(starColors, 3));

    const starMat = new THREE.PointsMaterial({
        size: 0.22,
        vertexColors: true,
        transparent: true,
        opacity: 0.1 // starts very dim, animated to 0.85
    });
    const stars = new THREE.Points(starGeo, starMat);
    scene.add(stars);

    // Subtle Drifting Cosmic Dust
    const dustGeo = new THREE.BufferGeometry();
    const dustCount = 350;
    const dustPos = new Float32Array(dustCount * 3);
    for (let i = 0; i < dustCount; i++) {
        dustPos[i * 3] = (Math.random() - 0.5) * 60;
        dustPos[i * 3 + 1] = (Math.random() - 0.5) * 40;
        dustPos[i * 3 + 2] = (Math.random() - 0.5) * 30;
    }
    dustGeo.setAttribute('position', new THREE.BufferAttribute(dustPos, 3));
    const dustMat = new THREE.PointsMaterial({
        size: 0.12,
        color: 0x94a3b8,
        transparent: true,
        opacity: 0.35
    });
    const cosmicDust = new THREE.Points(dustGeo, dustMat);
    scene.add(cosmicDust);

    // --- 3. Procedural Texture Generators ---
    function createMarsTexture() {
        const canvas = document.createElement('canvas');
        canvas.width = 1024;
        canvas.height = 512;
        const ctx = canvas.getContext('2d');

        // Mars Base Rust Gradient
        const grad = ctx.createLinearGradient(0, 0, 0, 512);
        grad.addColorStop(0, '#7c2d12');
        grad.addColorStop(0.25, '#c2410c');
        grad.addColorStop(0.5, '#ea580c');
        grad.addColorStop(0.75, '#9a3412');
        grad.addColorStop(1, '#7c2d12');
        ctx.fillStyle = grad;
        ctx.fillRect(0, 0, 1024, 512);

        // Terrain Patches
        for (let i = 0; i < 400; i++) {
            const x = Math.random() * 1024;
            const y = Math.random() * 512;
            const r = Math.random() * 45 + 5;
            ctx.fillStyle = Math.random() > 0.5 ? 'rgba(67, 20, 7, 0.28)' : 'rgba(251, 146, 60, 0.18)';
            ctx.beginPath();
            ctx.arc(x, y, r, 0, Math.PI * 2);
            ctx.fill();
        }

        // Polar Ice Cap
        ctx.fillStyle = 'rgba(254, 243, 199, 0.7)';
        ctx.beginPath();
        ctx.arc(512, 18, 45, 0, Math.PI * 2);
        ctx.fill();

        return new THREE.CanvasTexture(canvas);
    }

    function createSaturnTexture() {
        const canvas = document.createElement('canvas');
        canvas.width = 1024;
        canvas.height = 512;
        const ctx = canvas.getContext('2d');

        // Saturn Golden Atmosphere Bands
        const grad = ctx.createLinearGradient(0, 0, 0, 512);
        grad.addColorStop(0, '#78350f');
        grad.addColorStop(0.15, '#b45309');
        grad.addColorStop(0.3, '#d97706');
        grad.addColorStop(0.45, '#fde68a');
        grad.addColorStop(0.55, '#fef3c7');
        grad.addColorStop(0.7, '#d97706');
        grad.addColorStop(0.85, '#92400e');
        grad.addColorStop(1, '#78350f');
        ctx.fillStyle = grad;
        ctx.fillRect(0, 0, 1024, 512);

        // Subtle atmospheric storm stripes
        for (let i = 0; i < 60; i++) {
            const y = Math.random() * 512;
            const h = Math.random() * 6 + 2;
            ctx.fillStyle = Math.random() > 0.5 ? 'rgba(254, 243, 199, 0.25)' : 'rgba(120, 53, 15, 0.22)';
            ctx.fillRect(0, y, 1024, h);
        }

        return new THREE.CanvasTexture(canvas);
    }

    function createSaturnRingTexture() {
        const canvas = document.createElement('canvas');
        canvas.width = 512;
        canvas.height = 1;
        const ctx = canvas.getContext('2d');

        const grad = ctx.createLinearGradient(0, 0, 512, 0);
        grad.addColorStop(0, 'rgba(0,0,0,0)');
        grad.addColorStop(0.1, 'rgba(217, 119, 6, 0.3)');
        grad.addColorStop(0.3, 'rgba(251, 191, 36, 0.85)');
        grad.addColorStop(0.5, 'rgba(254, 243, 199, 0.95)');
        grad.addColorStop(0.65, 'rgba(120, 53, 15, 0.2)'); // Cassini Division Gap
        grad.addColorStop(0.75, 'rgba(251, 191, 36, 0.7)');
        grad.addColorStop(0.95, 'rgba(217, 119, 6, 0.4)');
        grad.addColorStop(1, 'rgba(0,0,0,0)');

        ctx.fillStyle = grad;
        ctx.fillRect(0, 0, 512, 1);

        return new THREE.CanvasTexture(canvas);
    }

    // --- 4. Celestial Bodies Creation ---
    
    // MARS PIVOT & SPHERE (Left Position: x = -11.5)
    const marsGroup = new THREE.Group();
    marsGroup.position.set(-11.5, 0.2, 0);
    scene.add(marsGroup);

    const marsGeo = new THREE.SphereGeometry(3.6, 64, 64);
    const marsMat = new THREE.MeshStandardMaterial({
        map: createMarsTexture(),
        roughness: 0.75,
        metalness: 0.1,
        bumpScale: 0.05
    });
    const marsMesh = new THREE.Mesh(marsGeo, marsMat);
    marsMesh.rotation.z = 0.25; // 25° axial tilt
    marsGroup.add(marsMesh);

    // Mars Atmosphere Rim Glow
    const marsAtmoGeo = new THREE.SphereGeometry(3.75, 48, 48);
    const marsAtmoMat = new THREE.MeshBasicMaterial({
        color: 0xf97316,
        transparent: true,
        opacity: 0.15,
        side: THREE.BackSide
    });
    const marsAtmo = new THREE.Mesh(marsAtmoGeo, marsAtmoMat);
    marsGroup.add(marsAtmo);

    // SATURN PIVOT, SPHERE & RINGS (Right Position: x = 11.5)
    const saturnGroup = new THREE.Group();
    saturnGroup.position.set(11.5, 0.2, 0);
    scene.add(saturnGroup);

    const saturnGeo = new THREE.SphereGeometry(3.8, 64, 64);
    const saturnMat = new THREE.MeshStandardMaterial({
        map: createSaturnTexture(),
        roughness: 0.65,
        metalness: 0.15
    });
    const saturnMesh = new THREE.Mesh(saturnGeo, saturnMat);
    saturnMesh.rotation.z = 0.38; // 26.7° tilt
    saturnGroup.add(saturnMesh);

    // Saturn Ring Geometry
    const ringGeo = new THREE.RingGeometry(4.8, 9.2, 80);
    // Align ring UV mapping radially
    const ringPos = ringGeo.attributes.position;
    const ringUVs = ringGeo.attributes.uv;
    for (let i = 0; i < ringPos.count; i++) {
        const vx = ringPos.getX(i);
        const vy = ringPos.getY(i);
        const dist = Math.sqrt(vx * vx + vy * vy);
        const u = (dist - 4.8) / (9.2 - 4.8);
        ringUVs.setXY(i, u, 0.5);
    }
    ringGeo.attributes.uv.needsUpdate = true;

    const ringMat = new THREE.MeshStandardMaterial({
        map: createSaturnRingTexture(),
        side: THREE.DoubleSide,
        transparent: true,
        opacity: 0.92,
        roughness: 0.4
    });
    const saturnRing = new THREE.Mesh(ringGeo, ringMat);
    saturnRing.rotation.x = Math.PI / 2 + 0.35;
    saturnRing.rotation.y = 0.18;
    saturnGroup.add(saturnRing);

    // Connecting Orbit Spline Path (Gracefully curving between Mars & Saturn)
    const orbitCurve = new THREE.CatmullRomCurve3([
        new THREE.Vector3(-18, -4, -5),
        new THREE.Vector3(-11.5, 0.2, 0),
        new THREE.Vector3(0, 3.5, 3),
        new THREE.Vector3(11.5, 0.2, 0),
        new THREE.Vector3(18, -4, -5)
    ]);
    const orbitPoints = orbitCurve.getPoints(120);
    const orbitGeo = new THREE.BufferGeometry().setFromPoints(orbitPoints);
    const orbitMat = new THREE.LineBasicMaterial({
        color: 0x38bdf8,
        transparent: true,
        opacity: 0.15
    });
    const connectingOrbit = new THREE.Line(orbitGeo, orbitMat);
    scene.add(connectingOrbit);

    // --- 5. Cinematic Dynamic Lighting ---
    const ambientLight = new THREE.AmbientLight(0x070e24, 0.35); // Deep space shadow ambience
    scene.add(ambientLight);

    // Key Sun Light (Left High Angled)
    const sunLight = new THREE.DirectionalLight(0xfff7ed, 0.1); // Starts at 0.1, sweeps up to 2.2
    sunLight.position.set(30, 20, 35);
    scene.add(sunLight);

    // Rim Glow Light for Mars
    const marsRimLight = new THREE.PointLight(0xea580c, 1.2, 25);
    marsRimLight.position.set(-18, 5, 8);
    scene.add(marsRimLight);

    // Rim Glow Light for Saturn
    const saturnRimLight = new THREE.PointLight(0xfbbf24, 1.2, 25);
    saturnRimLight.position.set(18, 5, 8);
    scene.add(saturnRimLight);

    // --- 6. Interaction State & Mouse Parallax ---
    let mouse = { x: 0, y: 0, targetX: 0, targetY: 0 };
    let hoveredPlanet = null;
    let isWarping = false;

    window.addEventListener('mousemove', function (e) {
        mouse.targetX = (e.clientX / window.innerWidth - 0.5) * 2;
        mouse.targetY = -(e.clientY / window.innerHeight - 0.5) * 2;
    });

    // Hover Listeners on Mars / Saturn Destination Units
    const marsUnit = document.getElementById('marsUnit');
    const saturnUnit = document.getElementById('saturnUnit');
    const ambientVignette = document.getElementById('ambientVignette');

    marsUnit?.addEventListener('mouseenter', () => {
        hoveredPlanet = 'mars';
        saturnUnit?.classList.add('dimmed');
        ambientVignette?.classList.add('hover-mars');
    });
    marsUnit?.addEventListener('mouseleave', () => {
        if (hoveredPlanet === 'mars') hoveredPlanet = null;
        saturnUnit?.classList.remove('dimmed');
        ambientVignette?.classList.remove('hover-mars');
    });

    saturnUnit?.addEventListener('mouseenter', () => {
        hoveredPlanet = 'saturn';
        marsUnit?.classList.add('dimmed');
        ambientVignette?.classList.add('hover-saturn');
    });
    saturnUnit?.addEventListener('mouseleave', () => {
        if (hoveredPlanet === 'saturn') hoveredPlanet = null;
        marsUnit?.classList.remove('dimmed');
        ambientVignette?.classList.remove('hover-saturn');
    });

    // --- 7. Warp Transition Execution ---
    const canAccessMars = {{ $canAccessMars ? 'true' : 'false' }};
    const canAccessSaturnus = {{ $canAccessSaturnus ? 'true' : 'false' }};

    window.navigateToPlanet = function (target) {
        if (target === 'mars' && !canAccessMars) {
            alert('Akses Ditolak: Akun role {{ $userRole }} Anda tidak memiliki izin untuk mengakses Modul MARS.');
            return;
        }
        if (target === 'saturn' && !canAccessSaturnus) {
            alert('Akses Ditolak: Akun role {{ $userRole }} Anda tidak memiliki izin untuk mengakses Modul SATURNUS.');
            return;
        }

        if (isWarping) return;
        isWarping = true;

        const warpCurtain = document.getElementById('warpCurtain');
        if (target === 'mars') {
            warpCurtain.classList.add('warp-mars');
            setTimeout(() => {
                window.location.href = "{{ route('mars.dashboard') }}";
            }, 1100);
        } else {
            warpCurtain.classList.add('warp-saturn');
            setTimeout(() => {
                window.location.href = "{{ route('saturnus.dashboard') }}";
            }, 1100);
        }
    };

    // --- 8. Opening Cinematic Sequence Animation ---
    let openingTime = 0;
    const targetCamZ = 28;

    setTimeout(() => {
        document.getElementById('orbitNavbar')?.classList.add('revealed');
        document.getElementById('orbitHeader')?.classList.add('revealed');
        document.getElementById('destinationsRow')?.classList.add('revealed');
        document.getElementById('orbitBottomDock')?.classList.add('revealed');
    }, 400);

    // --- 9. 60 FPS Render Loop with Smooth Easing ---
    let clock = new THREE.Clock();

    function animate() {
        requestAnimationFrame(animate);
        const delta = clock.getDelta();
        openingTime += delta;

        // Cinematic Opening Camera Glide
        if (camera.position.z > targetCamZ + 0.05) {
            camera.position.z += (targetCamZ - camera.position.z) * 0.025;
        }

        // Gradual Sunlight Reveal
        if (sunLight.intensity < 2.0) {
            sunLight.intensity += delta * 0.45;
        }
        if (starMat.opacity < 0.85) {
            starMat.opacity += delta * 0.25;
        }

        // Smooth Mouse Parallax
        mouse.x += (mouse.targetX - mouse.x) * 0.05;
        mouse.y += (mouse.targetY - mouse.y) * 0.05;

        camera.position.x = mouse.x * 2.2;
        camera.position.y = mouse.y * 1.5;
        camera.lookAt(0, 0, 0);

        // Planetary Rotations (Slow & Elegant)
        marsMesh.rotation.y += 0.0035;
        saturnMesh.rotation.y += 0.0025;
        saturnRing.rotation.z += 0.0008;
        cosmicDust.rotation.y += 0.0005;

        // Dynamic Scale Reactions on Hover
        const targetMarsScale = (hoveredPlanet === 'mars') ? 1.06 : (hoveredPlanet === 'saturn' ? 0.95 : 1.0);
        const targetSaturnScale = (hoveredPlanet === 'saturn') ? 1.06 : (hoveredPlanet === 'mars' ? 0.95 : 1.0);

        marsGroup.scale.lerp(new THREE.Vector3(targetMarsScale, targetMarsScale, targetMarsScale), 0.08);
        saturnGroup.scale.lerp(new THREE.Vector3(targetSaturnScale, targetSaturnScale, targetSaturnScale), 0.08);

        // Warp Acceleration Camera Push
        if (isWarping) {
            camera.position.z -= 0.85;
        }

        renderer.render(scene, camera);
    }

    animate();

    // Window Resize Handler
    window.addEventListener('resize', function () {
        width = container.offsetWidth || window.innerWidth;
        height = container.offsetHeight || window.innerHeight;
        camera.aspect = width / height;
        camera.updateProjectionMatrix();
        renderer.setSize(width, height);
    });
});

// --- Info Drawer Handlers (Explore, Missions, About) ---
function openOrbitDrawer(type) {
    const drawer = document.getElementById('orbitDrawer');
    const backdrop = document.getElementById('orbitDrawerBackdrop');
    const title = document.getElementById('drawerTitle');
    const content = document.getElementById('drawerContent');

    if (type === 'explore') {
        title.innerText = 'SOLAR SYSTEM EXPLORATION';
        content.innerHTML = `
            <p style="color: #cbd5e1; font-size: 0.88rem; line-height: 1.6;">
                Sistem <strong>ORBIT</strong> mengintegrasikan dua pilar operasional utama PT Metalart Astra Indonesia:
            </p>
            <div style="margin: 1.5rem 0; padding: 1rem; background: rgba(234,88,12,0.12); border-left: 3px solid #ea580c; border-radius: 6px;">
                <h6 style="color: #fb923c; font-weight: 700; margin-bottom: 0.3rem;">MARS (Stock Minim & PO)</h6>
                <p style="font-size: 0.8rem; color: #cbd5e1; margin: 0;">Pengawasan stok kritis, scheduled receipt PO, kedatangan barang, dan histori alokasi warehouse.</p>
            </div>
            <div style="margin: 1.5rem 0; padding: 1rem; background: rgba(251,191,36,0.12); border-left: 3px solid #fbbf24; border-radius: 6px;">
                <h6 style="color: #fde047; font-weight: 700; margin-bottom: 0.3rem;">SATURNUS (Consumable & Assets)</h6>
                <p style="font-size: 0.8rem; color: #cbd5e1; margin: 0;">Pendaftaran item baru, sistem persetujuan 3 tingkat (Staff &rarr; Accounting &rarr; Warehouse), dan manajemen discontinue.</p>
            </div>
        `;
    } else if (type === 'missions') {
        title.innerText = 'ACTIVE FLEET MISSIONS';
        content.innerHTML = `
            <div style="display: flex; flex-direction: column; gap: 0.85rem; margin-top: 1rem;">
                <div style="background: rgba(255,255,255,0.04); padding: 0.85rem; border-radius: 8px; border: 1px solid rgba(255,255,255,0.08);">
                    <div style="font-size: 0.72rem; color: #94a3b8; text-transform: uppercase;">Total Master Database</div>
                    <div style="font-size: 1.4rem; font-weight: 800; color: #ffffff;">{{ number_format($totalItemMaster ?? $marsStats['total_items'] ?? 2898) }} Items</div>
                </div>
                <div style="background: rgba(234,88,12,0.12); padding: 0.85rem; border-radius: 8px; border: 1px solid rgba(234,88,12,0.3);">
                    <div style="font-size: 0.72rem; color: #fb923c; text-transform: uppercase;">Perhatian Stok Minim</div>
                    <div style="font-size: 1.4rem; font-weight: 800; color: #ea580c;">{{ number_format($totalItemMinim ?? $marsStats['minim_items'] ?? 0) }} Items</div>
                </div>
                <div style="background: rgba(251,191,36,0.12); padding: 0.85rem; border-radius: 8px; border: 1px solid rgba(251,191,36,0.3);">
                    <div style="font-size: 0.72rem; color: #fde047; text-transform: uppercase;">Consumables Terverifikasi</div>
                    <div style="font-size: 1.4rem; font-weight: 800; color: #fbbf24;">{{ number_format($totalConsumables ?? $saturnusStats['total_consumables'] ?? 0) }} Items</div>
                </div>
            </div>
        `;
    } else {
        title.innerText = 'ABOUT ORBIT';
        content.innerHTML = `
            <p style="color: #cbd5e1; font-size: 0.88rem; line-height: 1.7;">
                <strong>ORBIT Portal</strong> dirancang khusus sebagai antarmuka komando terpadu untuk monitoring dan kontrol rantai pasok PT Metalart Astra Indonesia.
            </p>
            <p style="color: #94a3b8; font-size: 0.82rem; line-height: 1.6;">
                Ditenagai oleh arsitektur Laravel 12 & Three.js WebGL Engine, menghadirkan pengalaman visual kelas dunia dengan kinerja responsif, presisi data tinggi, dan kemudahan navigasi operasional.
            </p>
        `;
    }

    drawer?.classList.add('open');
    backdrop?.classList.add('show');
}

function closeOrbitDrawer() {
    document.getElementById('orbitDrawer')?.classList.remove('open');
    document.getElementById('orbitDrawerBackdrop')?.classList.remove('show');
}

function toggleSidebarDrawer() {
    const sidebar = document.querySelector('.sidebar');
    if (sidebar) {
        sidebar.classList.toggle('mobile-open');
    }
}

// ── Cinematic Portal Entrance Reveal ──
document.addEventListener('DOMContentLoaded', function () {
    const universe = document.querySelector('.orbit-universe-container');
    try {
        if (universe && sessionStorage.getItem('mai_portal_transition') === '1') {
            sessionStorage.removeItem('mai_portal_transition');
            universe.classList.add('portal-entrance-init');
            requestAnimationFrame(() => {
                requestAnimationFrame(() => {
                    universe.classList.add('portal-entrance-active');
                    setTimeout(() => {
                        universe.classList.remove('portal-entrance-init', 'portal-entrance-active');
                    }, 800);
                });
            });
        }
    } catch (e) {}
});
</script>
@endsection
