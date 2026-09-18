@extends('layouts.app')

@section('title', 'SATURNUS — Smart Asset Tracking, Registration & Unregistration Network Utility System')

@section('content')

@php
    $user = auth()->user();
    $userRole = strtoupper(trim($user->role ?? 'GUEST'));
    $isMasterOrAdmin = in_array($userRole, ['MASTER', 'ADMIN']) || ($user && $user->isMaster());
@endphp

<style>
    /* ==========================================================================
       🪐 DEEP COSMIC BLACK SPACE BACKGROUND (ORIGINAL SATURNUS THEME)
       ========================================================================== */
    html, body, .main-wrapper, .page-wrapper, .page-content {
        background-color: #020617 !important;
        background:
            radial-gradient(ellipse 90% 70% at 50% -10%, rgba(26, 63, 168, 0.35) 0%, transparent 60%),
            radial-gradient(ellipse 60% 50% at 85% 60%, rgba(0, 173, 239, 0.18) 0%, transparent 55%),
            radial-gradient(ellipse 50% 40% at 15% 80%, rgba(168, 85, 247, 0.12) 0%, transparent 50%),
            #020617 !important;
        background-attachment: fixed !important;
        color: #f8fafc !important;
    }

    .page-wrapper {
        background-color: #020617 !important;
    }

    .page-content {
        background: transparent !important;
        padding: 1.5rem 2rem !important;
    }

    .footer, footer {
        background-color: #020617 !important;
        background: #020617 !important;
        border-top: 1px solid rgba(255, 255, 255, 0.08) !important;
        color: #94a3b8 !important;
    }

    .footer p, footer p, .footer span, footer span, .footer a, footer a {
        color: #94a3b8 !important;
    }

    /* ==========================================================================
       🪐 SATURNUS KNOWLEDGE & OPERATIONAL HUB STYLING
       ========================================================================== */
    .saturn-info-hub {
        margin-top: 2.5rem;
        margin-bottom: 3.5rem;
    }

    /* Sticky Sub-Navigation Bar */
    .saturn-hub-nav {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        background: rgba(15, 23, 42, 0.85);
        border: 1px solid rgba(56, 189, 248, 0.22);
        padding: 0.55rem 0.85rem;
        border-radius: 40px;
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.45), 0 0 20px rgba(56, 189, 248, 0.12);
        position: sticky;
        top: 75px;
        z-index: 40;
        margin-bottom: 2.2rem;
        overflow-x: auto;
        white-space: nowrap;
        scrollbar-width: none;
    }

    .saturn-hub-nav::-webkit-scrollbar {
        display: none;
    }

    .hub-nav-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.45rem 1rem;
        border-radius: 25px;
        color: #94a3b8;
        font-size: 0.8rem;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.25s ease;
        border: 1px solid transparent;
    }

    .hub-nav-pill:hover {
        color: #ffffff;
        background: rgba(56, 189, 248, 0.12);
        border-color: rgba(56, 189, 248, 0.3);
    }

    .hub-nav-pill.active {
        color: #ffffff;
        background: linear-gradient(135deg, rgba(26, 63, 168, 0.85), rgba(0, 173, 239, 0.85));
        border-color: rgba(56, 189, 248, 0.6);
        box-shadow: 0 4px 14px rgba(0, 173, 239, 0.35);
    }

    /* Section Cards */
    .saturn-glass-section {
        background: rgba(10, 20, 42, 0.65);
        border: 1px solid rgba(56, 189, 248, 0.16);
        border-radius: 24px;
        padding: 2.2rem;
        margin-bottom: 2.5rem;
        backdrop-filter: blur(18px);
        -webkit-backdrop-filter: blur(18px);
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5), inset 0 0 30px rgba(56, 189, 248, 0.03);
        position: relative;
        overflow: hidden;
        scroll-margin-top: 130px;
    }

    .saturn-glass-section::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 2px;
        background: linear-gradient(90deg, transparent, rgba(56, 189, 248, 0.6), rgba(168, 85, 247, 0.6), transparent);
    }

    /* Section Header */
    .sec-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        margin-bottom: 1.8rem;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .sec-header-main {
        display: flex;
        gap: 1rem;
        align-items: center;
    }

    .sec-icon-circle {
        width: 52px;
        height: 52px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 1.5rem;
        background: rgba(56, 189, 248, 0.1);
        border: 1px solid rgba(56, 189, 248, 0.3);
        box-shadow: 0 0 20px rgba(56, 189, 248, 0.2);
    }

    .sec-icon-circle.cyan {
        background: rgba(56, 189, 248, 0.12);
        border-color: rgba(56, 189, 248, 0.4);
        color: #38bdf8;
    }

    .sec-icon-circle.purple {
        background: rgba(168, 85, 247, 0.12);
        border-color: rgba(168, 85, 247, 0.4);
        color: #c084fc;
    }

    .sec-icon-circle.amber {
        background: rgba(251, 191, 36, 0.12);
        border-color: rgba(251, 191, 36, 0.4);
        color: #fbbf24;
    }

    .sec-icon-circle.rose {
        background: rgba(244, 63, 94, 0.12);
        border-color: rgba(244, 63, 94, 0.4);
        color: #fb7185;
    }

    .sec-icon-circle.emerald {
        background: rgba(52, 211, 153, 0.12);
        border-color: rgba(52, 211, 153, 0.4);
        color: #34d399;
    }

    .sec-title-group h2 {
        font-size: 1.35rem;
        font-weight: 800;
        color: #ffffff;
        margin: 0 0 0.25rem 0;
        letter-spacing: -0.01em;
        display: flex;
        align-items: center;
        gap: 0.6rem;
    }

    .sec-title-group p {
        font-size: 0.85rem;
        color: #94a3b8;
        margin: 0;
    }

    .sec-badge {
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        padding: 0.35rem 0.8rem;
        border-radius: 20px;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.12);
        color: #cbd5e1;
        text-transform: uppercase;
    }

    /* Pillars Grid */
    .pillar-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        gap: 1.25rem;
    }

    .pillar-card {
        background: rgba(15, 23, 42, 0.6);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 18px;
        padding: 1.4rem;
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        position: relative;
        overflow: hidden;
    }

    .pillar-card:hover {
        transform: translateY(-4px);
        border-color: rgba(56, 189, 248, 0.35);
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.4), 0 0 20px rgba(56, 189, 248, 0.15);
        background: rgba(15, 23, 42, 0.8);
    }

    .pillar-card-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        margin-bottom: 1rem;
    }

    .pillar-card h3 {
        font-size: 1.05rem;
        font-weight: 700;
        color: #f1f5f9;
        margin-bottom: 0.5rem;
    }

    .pillar-card p {
        font-size: 0.82rem;
        line-height: 1.55;
        color: #94a3b8;
        margin-bottom: 1rem;
    }

    .pillar-feature-list {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        flex-direction: column;
        gap: 0.4rem;
    }

    .pillar-feature-list li {
        font-size: 0.78rem;
        color: #cbd5e1;
        display: flex;
        align-items: center;
        gap: 0.45rem;
    }

    .pillar-feature-list li svg {
        color: #38bdf8;
        flex-shrink: 0;
    }

    /* Stepper & Workflow Pipeline */
    .workflow-pipeline {
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
        position: relative;
    }

    .pipeline-step-card {
        background: rgba(15, 23, 42, 0.55);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 18px;
        padding: 1.5rem;
        display: grid;
        grid-template-columns: 80px 1fr auto;
        gap: 1.5rem;
        align-items: center;
        transition: all 0.3s ease;
        position: relative;
    }

    .pipeline-step-card:hover {
        background: rgba(15, 23, 42, 0.85);
        border-color: rgba(56, 189, 248, 0.4);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.35);
    }

    .step-number-col {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }

    .step-num-circle {
        width: 54px;
        height: 54px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        font-weight: 800;
        font-family: var(--font-tech, monospace);
        background: rgba(2, 6, 23, 0.85);
        border: 2px solid;
    }

    .step-num-circle.cyan { border-color: #38bdf8; color: #38bdf8; box-shadow: 0 0 15px rgba(56, 189, 248, 0.25); }
    .step-num-circle.blue { border-color: #3b82f6; color: #60a5fa; box-shadow: 0 0 15px rgba(59, 130, 246, 0.25); }
    .step-num-circle.amber { border-color: #f59e0b; color: #fbbf24; box-shadow: 0 0 15px rgba(245, 158, 11, 0.25); }
    .step-num-circle.emerald { border-color: #10b981; color: #34d399; box-shadow: 0 0 15px rgba(16, 185, 129, 0.25); }
    .step-num-circle.rose { border-color: #f43f5e; color: #fb7185; box-shadow: 0 0 15px rgba(244, 63, 94, 0.25); }

    .step-content-col h4 {
        font-size: 1.05rem;
        font-weight: 700;
        color: #ffffff;
        margin: 0 0 0.35rem 0;
        display: flex;
        align-items: center;
        gap: 0.6rem;
    }

    .step-content-col p {
        font-size: 0.83rem;
        color: #94a3b8;
        line-height: 1.5;
        margin: 0 0 0.75rem 0;
    }

    .step-chips {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .step-chip {
        font-size: 0.72rem;
        font-weight: 600;
        padding: 0.25rem 0.65rem;
        border-radius: 12px;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        color: #cbd5e1;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
    }

    .step-action-col {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 0.5rem;
    }

    .step-status-tag {
        font-size: 0.72rem;
        font-weight: 700;
        padding: 0.35rem 0.8rem;
        border-radius: 20px;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .step-status-tag.draft { background: rgba(148, 163, 184, 0.15); color: #cbd5e1; border: 1px solid rgba(148, 163, 184, 0.3); }
    .step-status-tag.staff { background: rgba(59, 130, 246, 0.15); color: #93c5fd; border: 1px solid rgba(59, 130, 246, 0.3); }
    .step-status-tag.acc { background: rgba(245, 158, 11, 0.15); color: #fde68a; border: 1px solid rgba(245, 158, 11, 0.3); }
    .step-status-tag.wh { background: rgba(16, 185, 129, 0.15); color: #a7f3d0; border: 1px solid rgba(16, 185, 129, 0.3); }
    .step-status-tag.discon { background: rgba(244, 63, 94, 0.15); color: #fecdd3; border: 1px solid rgba(244, 63, 94, 0.3); }

    /* Role Matrix Grid */
    .role-matrix-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 1.25rem;
    }

    .role-card {
        background: rgba(15, 23, 42, 0.6);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 20px;
        padding: 1.5rem;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        transition: all 0.25s ease;
    }

    .role-card:hover {
        border-color: rgba(56, 189, 248, 0.4);
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4);
    }

    .role-header {
        display: flex;
        align-items: center;
        gap: 0.85rem;
        margin-bottom: 1rem;
        padding-bottom: 0.85rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    }

    .role-avatar {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
    }

    .role-info h4 {
        font-size: 0.95rem;
        font-weight: 700;
        color: #ffffff;
        margin: 0;
    }

    .role-info span {
        font-size: 0.72rem;
        color: #94a3b8;
    }

    .role-permission-list {
        list-style: none;
        padding: 0;
        margin: 0 0 1rem 0;
        display: flex;
        flex-direction: column;
        gap: 0.45rem;
    }

    .role-permission-list li {
        font-size: 0.78rem;
        display: flex;
        align-items: flex-start;
        gap: 0.45rem;
        line-height: 1.45;
    }

    .role-permission-list li.allow { color: #e2e8f0; }
    .role-permission-list li.allow svg { color: #34d399; flex-shrink: 0; margin-top: 2px; }
    .role-permission-list li.deny { color: #64748b; }
    .role-permission-list li.deny svg { color: #ef4444; flex-shrink: 0; margin-top: 2px; }

    /* SOP & Tips Cards */
    .sop-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 1.25rem;
    }

    .sop-card {
        background: rgba(15, 23, 42, 0.6);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 18px;
        padding: 1.4rem;
    }

    .sop-card h4 {
        font-size: 0.95rem;
        font-weight: 700;
        color: #38bdf8;
        margin: 0 0 0.85rem 0;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .sop-checklist {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        flex-direction: column;
        gap: 0.55rem;
    }

    .sop-checklist li {
        font-size: 0.8rem;
        line-height: 1.45;
        color: #cbd5e1;
        display: flex;
        align-items: flex-start;
        gap: 0.5rem;
    }

    .sop-checklist li strong {
        color: #ffffff;
    }

    .sop-checklist li svg {
        color: #38bdf8;
        margin-top: 2px;
        flex-shrink: 0;
    }

    /* FAQ Collapsible Cards */
    .faq-item {
        background: rgba(15, 23, 42, 0.5);
        border: 1px solid rgba(255, 255, 255, 0.07);
        border-radius: 14px;
        margin-bottom: 0.75rem;
        overflow: hidden;
    }

    .faq-question {
        padding: 1rem 1.25rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        cursor: pointer;
        font-size: 0.88rem;
        font-weight: 600;
        color: #f1f5f9;
        user-select: none;
        transition: background 0.2s;
    }

    .faq-question:hover {
        background: rgba(56, 189, 248, 0.08);
        color: #38bdf8;
    }

    .faq-answer {
        padding: 0 1.25rem 1rem 1.25rem;
        font-size: 0.82rem;
        line-height: 1.55;
        color: #94a3b8;
        border-top: 1px solid rgba(255, 255, 255, 0.04);
        display: none;
    }

    .faq-item.active .faq-answer {
        display: block;
    }

    .faq-item.active .faq-question svg {
        transform: rotate(180deg);
        color: #38bdf8;
    }

    /* Quick Action Launchpad Grid */
    .launchpad-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 1.25rem;
    }

    .launchpad-card {
        background: rgba(15, 23, 42, 0.75);
        border: 1px solid rgba(56, 189, 248, 0.2);
        border-radius: 20px;
        padding: 1.5rem;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        position: relative;
        overflow: hidden;
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .launchpad-card:hover {
        transform: translateY(-4px);
        border-color: rgba(56, 189, 248, 0.5);
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.5), 0 0 25px rgba(56, 189, 248, 0.2);
    }

    .launchpad-card-top {
        display: flex;
        align-items: center;
        gap: 0.85rem;
        margin-bottom: 0.85rem;
    }

    .launchpad-icon-box {
        width: 48px;
        height: 48px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.35rem;
        flex-shrink: 0;
    }

    .launchpad-card h3 {
        font-size: 1.05rem;
        font-weight: 700;
        color: #ffffff;
        margin: 0;
    }

    .launchpad-card p {
        font-size: 0.82rem;
        color: #94a3b8;
        line-height: 1.5;
        margin-bottom: 1.25rem;
        min-height: 2.5rem;
    }

    .launchpad-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 0.65rem 1.2rem;
        border-radius: 12px;
        font-size: 0.82rem;
        font-weight: 700;
        text-decoration: none;
        transition: all 0.2s ease;
        width: 100%;
    }

    .launchpad-btn.primary {
        background: linear-gradient(135deg, #1a3fa8 0%, #00adef 100%);
        color: #ffffff;
        border: 1px solid rgba(56, 189, 248, 0.5);
        box-shadow: 0 4px 15px rgba(0, 173, 239, 0.35);
    }

    .launchpad-btn.primary:hover {
        box-shadow: 0 6px 20px rgba(0, 173, 239, 0.6);
        color: #ffffff;
        transform: translateY(-1px);
    }

    .launchpad-btn.secondary {
        background: rgba(255, 255, 255, 0.07);
        color: #e2e8f0;
        border: 1px solid rgba(255, 255, 255, 0.12);
    }

    .launchpad-btn.secondary:hover {
        background: rgba(56, 189, 248, 0.15);
        border-color: rgba(56, 189, 248, 0.4);
        color: #ffffff;
        transform: translateY(-1px);
    }

    .launchpad-btn.danger {
        background: linear-gradient(135deg, rgba(225, 29, 72, 0.8) 0%, rgba(244, 63, 94, 0.8) 100%);
        color: #ffffff;
        border: 1px solid rgba(244, 63, 94, 0.5);
        box-shadow: 0 4px 15px rgba(225, 29, 72, 0.3);
    }

    .launchpad-btn.danger:hover {
        box-shadow: 0 6px 20px rgba(244, 63, 94, 0.6);
        color: #ffffff;
        transform: translateY(-1px);
    }

    @media (max-width: 768px) {
        .pipeline-step-card {
            grid-template-columns: 1fr;
            gap: 1rem;
        }
        .step-number-col {
            flex-direction: row;
            justify-content: flex-start;
            gap: 1rem;
        }
        .step-action-col {
            align-items: flex-start;
        }
        .saturn-hub-nav {
            top: 60px;
        }
    }
</style>

<!-- Load Three.js 3D Engine from CDN -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>

<!-- ==========================================================================
     GALACTIC COMMAND CENTER HEADER
     ========================================================================== -->
<div class="galactic-header">
    <div class="galactic-header-left">
        <div class="galactic-badge">
            <span class="pulse-beacon"></span>
            <span>MAI 3D SATURN OBSERVATORY · COMMAND CENTER</span>
        </div>
        <h1 class="galactic-title">
            <span>SATURNUS</span>
        </h1>
        <p class="galactic-subtitle"><span class="sat-letter sat-s">S</span>mart <span class="sat-letter sat-a">A</span>sset <span class="sat-letter sat-t">T</span>racking, Registration &amp; Unregistration Network Utility System</p>
    </div>

    <!-- Decorative Telemetry Status Badges -->
    <div class="galactic-telemetry-hud">
        <div class="telemetry-item">
            <div class="telemetry-icon-box green">
                <span class="telemetry-dot"></span>
            </div>
            <div class="telemetry-data">
                <span class="telemetry-label">3D WEBGL ENGINE</span>
                <span class="telemetry-val text-green">ACTIVE · 60 FPS GPU</span>
            </div>
        </div>

        <div class="telemetry-item">
            <div class="telemetry-icon-box cyan">
                <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2.5" fill="none">
                    <circle cx="12" cy="12" r="10"></circle>
                    <path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"></path>
                    <path d="M2 12h20"></path>
                </svg>
            </div>
            <div class="telemetry-data">
                <span class="telemetry-label">CELESTIAL BODY</span>
                <span class="telemetry-val text-cyan">SATURN & 6 MOONS</span>
            </div>
        </div>

        <div class="telemetry-item">
            <div class="telemetry-icon-box purple">
                <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2.5" fill="none">
                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                </svg>
            </div>
            <div class="telemetry-data">
                <span class="telemetry-label">ORBIT SIMULATION</span>
                <span class="telemetry-val text-purple" id="telemetrySpeedVal">REAL-TIME 1.0X</span>
            </div>
        </div>
    </div>
</div>

<!-- ==========================================================================
     🪐 CINEMATIC FULL-SCALE 3D WEBGL SATURNUS HERO (ZERO CLUTTER, PURE 3D)
     ========================================================================== -->
<div class="spatial-orbit-hero saturn-webgl-hero saturn-hero-fullscreen" id="spatialOrbitHero">
    <!-- WebGL Canvas Container for Three.js -->
    <div id="saturnWebglContainer" class="saturn-webgl-canvas-container"></div>

    <!-- HUD Tech Corners -->
    <div class="hud-corner top-left"></div>
    <div class="hud-corner top-right"></div>
    <div class="hud-corner bottom-left"></div>
    <div class="hud-corner bottom-right"></div>

    <!-- Top Camera & Simulation Controls Ribbon -->
    <div class="orbit-top-ribbon">
        <div class="orbit-sector-pills">
            <span class="hud-mono" style="font-size:0.65rem; color:#94a3b8; margin-left:0.35rem; margin-right:0.25rem;">CAMERA VIEW:</span>
            <button type="button" class="sector-pill active" id="camOrbitBtn" onclick="setSaturnCameraView('orbit', this)">🪐 CINEMATIC ORBIT</button>
            <button type="button" class="sector-pill" id="camRingBtn" onclick="setSaturnCameraView('ring', this)">🧭 RING PLANE</button>
            <button type="button" class="sector-pill" id="camPoleBtn" onclick="setSaturnCameraView('pole', this)">🌐 NORTH POLE</button>
            <button type="button" class="sector-pill" id="camTitanBtn" onclick="setSaturnCameraView('titan', this)">🛰️ TITAN VIEW</button>
        </div>

        <div class="orbit-speed-controls">
            <span class="hud-mono" style="font-size:0.65rem; color:#94a3b8; margin-right:0.35rem;">SIM SPEED:</span>
            <button type="button" class="speed-btn active" id="speed1xBtn" onclick="setSimulationSpeed(1.0, this)">1X</button>
            <button type="button" class="speed-btn" id="speed3xBtn" onclick="setSimulationSpeed(3.0, this)">3X WARP</button>
            <button type="button" class="speed-btn" id="speedPauseBtn" onclick="toggleSimulationPause(this)">⏸ PAUSE</button>
        </div>
    </div>

    <!-- Moon Telemetry Info Drawer (Opens on Moon Click) -->
    <div class="orbit-inspector-box" id="orbitInspectorBox">
        <div class="inspector-header">
            <span class="inspector-badge" id="inspBadge">MOON TELEMETRY</span>
            <button type="button" class="inspector-close-btn" onclick="closeInspector()">&times;</button>
        </div>
        <div class="inspector-body">
            <h4 class="inspector-title" id="inspTitle">TITAN (SATURN VI)</h4>
            <p class="inspector-sub" id="inspSub">Largest Moon of Saturn with Dense Nitrogen Atmosphere</p>
            <div class="inspector-metrics">
                <div class="insp-metric">
                    <span class="insp-k">DIAMETER</span>
                    <span class="insp-v text-amber" id="inspDiam">5,149 KM</span>
                </div>
                <div class="insp-metric">
                    <span class="insp-k">ORBIT RADIUS</span>
                    <span class="insp-v" id="inspDist">1,221,870 KM</span>
                </div>
                <div class="insp-metric">
                    <span class="insp-k">ASSOCIATED SECTOR</span>
                    <span class="insp-v text-cyan" id="inspDept">PRODUCTION / USER</span>
                </div>
            </div>
            <a href="{{ route('form-registrasi') }}" class="insp-action-btn" id="inspLink">
                <span>Buka Form Registrasi</span>
                <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2.5" fill="none"><polyline points="9 18 15 12 9 6"></polyline></svg>
            </a>
        </div>
    </div>

    <!-- Floating Sci-Fi Command Dock (Clean Bottom Quick Launcher) -->
    <div class="saturn-floating-dock" style="display: flex; gap: 0.65rem; flex-wrap: wrap; justify-content: center;">
        @if($isMasterOrAdmin || ($user && $user->hasPermission('saturnus.registrasi.view')))
        <a href="{{ route('saturnus.form_registrasi') }}" class="dock-launcher-btn primary">
            <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2.5" fill="none">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            <span>+ Form Registrasi</span>
        </a>
        @endif

        @if($isMasterOrAdmin || ($user && $user->hasPermission('saturnus.unregistrasi.view')))
        <a href="{{ route('saturnus.form_unregistrasi') }}" class="dock-launcher-btn secondary">
            <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2.5" fill="none">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="15" y1="9" x2="9" y2="15"></line>
                <line x1="9" y1="9" x2="15" y2="15"></line>
            </svg>
            <span>- Form Unregistrasi</span>
        </a>
        @endif

        @if($isMasterOrAdmin || ($user && ($user->hasPermission('saturnus.unregistrasi.approve') || $user->hasPermission('saturnus.unregistrasi.view'))))
        <a href="{{ route('saturnus.unregistrasi_approval') }}" class="dock-launcher-btn secondary">
            <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2.5" fill="none">
                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="8.5" cy="7" r="4"></circle>
                <polyline points="17 11 19 13 23 9"></polyline>
            </svg>
            <span>Approval Unregistrasi</span>
        </a>
        @endif

        @if($isMasterOrAdmin || ($user && $user->hasPermission('saturnus.unregistrasi.view')))
        <a href="{{ route('saturnus.unregistrasi_history') }}" class="dock-launcher-btn secondary">
            <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2.5" fill="none">
                <circle cx="12" cy="12" r="10"></circle>
                <polyline points="12 6 12 12 16 14"></polyline>
            </svg>
            <span>History Discontinue</span>
        </a>
        @endif
    </div>
</div>

<!-- ==========================================================================
     🪐 SATURNUS KNOWLEDGE & OPERATIONAL HUB (PANDUAN & INFORMASI LENGKAP)
     ========================================================================== -->
<div class="saturn-info-hub">
    <!-- Sticky Navigation Sub-Header -->
    <div class="saturn-hub-nav">
        <a href="#hub-overview" class="hub-nav-pill active">
            <span>🪐</span>
            <span>Tentang SATURNUS</span>
        </a>
        <a href="#hub-alur-registrasi" class="hub-nav-pill">
            <span>📝</span>
            <span>Alur Registrasi (3-Tahap)</span>
        </a>
        <a href="#hub-alur-unregistrasi" class="hub-nav-pill">
            <span>🗑️</span>
            <span>Alur Unregistrasi / Discontinue</span>
        </a>
        <a href="#hub-matriks-role" class="hub-nav-pill">
            <span>🛡️</span>
            <span>Matriks Peran & Hak Akses</span>
        </a>
        <a href="#hub-sop-panduan" class="hub-nav-pill">
            <span>📋</span>
            <span>SOP & Tips Praktis</span>
        </a>
        <a href="#hub-quick-launch" class="hub-nav-pill">
            <span>🚀</span>
            <span>Pusat Aksi Cepat</span>
        </a>
    </div>

    <!-- SECTION 1: TENTANG SATURNUS -->
    <div class="saturn-glass-section" id="hub-overview">
        <div class="sec-header">
            <div class="sec-header-main">
                <div class="sec-icon-circle cyan">
                    🪐
                </div>
                <div class="sec-title-group">
                    <h2>Tentang Platform SATURNUS</h2>
                    <p>Smart Asset Tracking, Registration & Unregistration Network Utility System</p>
                </div>
            </div>
            <span class="sec-badge">SYSTEM ARCHITECTURE</span>
        </div>

        <p style="color: #cbd5e1; font-size: 0.92rem; line-height: 1.65; margin-bottom: 1.8rem;">
            <strong>SATURNUS</strong> adalah sistem informasi terpadu yang dirancang khusus untuk mengelola seluruh siklus hidup barang consumable (mulai dari pengajuan pendaftaran baru, standardisasi part, verifikasi teknis departemen, validasi anggaran akuntansi, penerbitan kode master, hingga penonaktifan/discontinue barang). Sistem ini memastikan integritas data, transparansi alur persetujuan, dan efisiensi pengelolaan persediaan di seluruh area operasional MAI.
        </p>

        <div class="pillar-grid">
            <div class="pillar-card">
                <div>
                    <div class="pillar-card-icon" style="background: rgba(56, 189, 248, 0.12); color: #38bdf8;">
                        📝
                    </div>
                    <h3>1. Standardisasi Master Item</h3>
                    <p>Mencegah duplikasi part consumable dengan standardisasi penamaan barang, spesifikasi teknis, satuan ukur (UoM), serta penentuan level stok minimum/maksimum yang akurat.</p>
                </div>
                <ul class="pillar-feature-list">
                    <li><svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2.5" fill="none"><polyline points="20 6 9 17 4 12"></polyline></svg> Validasi part number & deskripsi baku</li>
                    <li><svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2.5" fill="none"><polyline points="20 6 9 17 4 12"></polyline></svg> Pengelompokan kategori terstruktur</li>
                </ul>
            </div>

            <div class="pillar-card">
                <div>
                    <div class="pillar-card-icon" style="background: rgba(168, 85, 247, 0.12); color: #c084fc;">
                        🛡️
                    </div>
                    <h3>2. Multi-Tier Approval Workflow</h3>
                    <p>Alur persetujuan 3 tahap berjenjang yang melibatkan Staff Dept, Accounting, dan Warehouse Consumable untuk menjamin keselarasan operasional dan kepatuhan finansial.</p>
                </div>
                <ul class="pillar-feature-list">
                    <li><svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2.5" fill="none"><polyline points="20 6 9 17 4 12"></polyline></svg> Tanda tangan digital & audit timestamp</li>
                    <li><svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2.5" fill="none"><polyline points="20 6 9 17 4 12"></polyline></svg> Fitur revisi & catatan approver interaktif</li>
                </ul>
            </div>

            <div class="pillar-card">
                <div>
                    <div class="pillar-card-icon" style="background: rgba(52, 211, 153, 0.12); color: #34d399;">
                        🗄️
                    </div>
                    <h3>3. Direktori Terpusat & QR Code</h3>
                    <p>Katalog master consumable aktif yang dilengkapi filter cepat, pencarian real-time, kode QR untuk identifikasi fisik di rak/bin, serta export data ke format Excel.</p>
                </div>
                <ul class="pillar-feature-list">
                    <li><svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2.5" fill="none"><polyline points="20 6 9 17 4 12"></polyline></svg> Lokasi Bin & Rak terpetakan</li>
                    <li><svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2.5" fill="none"><polyline points="20 6 9 17 4 12"></polyline></svg> Lampiran Technical Sheet & Foto fisik</li>
                </ul>
            </div>

            <div class="pillar-card">
                <div>
                    <div class="pillar-card-icon" style="background: rgba(244, 63, 94, 0.12); color: #fb7185;">
                        🗑️
                    </div>
                    <h3>4. Tata Kelola Discontinue</h3>
                    <p>Mekanisme penonaktifan barang obsolete, dead stock, atau pergantian model mesin yang tertib melalui pengajuan unregistrasi, approval berjenjang, dan arsip riwayat resmi.</p>
                </div>
                <ul class="pillar-feature-list">
                    <li><svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2.5" fill="none"><polyline points="20 6 9 17 4 12"></polyline></svg> Berita acara penonaktifan otomatis</li>
                    <li><svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2.5" fill="none"><polyline points="20 6 9 17 4 12"></polyline></svg> Rekam jejak permanen di History</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- SECTION 2: ALUR REGISTRASI BARANG (3-STAGE WORKFLOW) -->
    <div class="saturn-glass-section" id="hub-alur-registrasi">
        <div class="sec-header">
            <div class="sec-header-main">
                <div class="sec-icon-circle cyan">
                    📝
                </div>
                <div class="sec-title-group">
                    <h2>Panduan Alur Registrasi Barang Consumable</h2>
                    <p>Siklus pendaftaran item baru melalui 3 tahap validasi dan persetujuan bertingkat</p>
                </div>
            </div>
            <span class="sec-badge" style="color: #38bdf8; border-color: rgba(56, 189, 248, 0.3);">3-STAGE APPROVAL PIPELINE</span>
        </div>

        <div class="workflow-pipeline">
            <!-- Stage 1 -->
            <div class="pipeline-step-card">
                <div class="step-number-col">
                    <div class="step-num-circle cyan">01</div>
                </div>
                <div class="step-content-col">
                    <h4>
                        <span>Pengajuan Formulir Registrasi</span>
                        <span class="step-role-tag" style="background: rgba(56, 189, 248, 0.15); color: #38bdf8; font-size: 0.72rem; padding: 2px 8px; border-radius: 10px; font-weight: 600;">PERAN: USER / PEMBUAT</span>
                    </h4>
                    <p>Pemohon (User departemen) membuat formulir registrasi baru. Mengisi detail teknis: Kategori barang, Nama Item, Spesifikasi/Dimensi, Satuan (UoM), Stock Min/Max, Estimasi Harga, Rekomendasi Vendor, dan melampirkan foto fisik barang / Technical Data Sheet.</p>
                    <div class="step-chips">
                        <span class="step-chip">📌 Format Nomor: REG/[DEPT]/[TAHUN]/[BULAN]/XXX</span>
                        <span class="step-chip">📷 Lampiran Wajib: Foto / Spec Sheet</span>
                        <span class="step-chip">⏱️ Estimasi: Hari ke-1</span>
                    </div>
                </div>
                <div class="step-action-col">
                    <span class="step-status-tag draft">Draft / Submitted</span>
                    @if($isMasterOrAdmin || ($user && $user->hasPermission('saturnus.registrasi.view')))
                    <a href="{{ route('saturnus.form_registrasi') }}" class="dock-launcher-btn primary" style="font-size: 0.75rem; padding: 0.4rem 0.95rem;">
                        <span>Buat Form</span> &rarr;
                    </a>
                    @endif
                </div>
            </div>

            <!-- Stage 2 -->
            <div class="pipeline-step-card">
                <div class="step-number-col">
                    <div class="step-num-circle blue">02</div>
                </div>
                <div class="step-content-col">
                    <h4>
                        <span>Verifikasi Tahap 1: Kebutuhan Teknis Departemen</span>
                        <span class="step-role-tag" style="background: rgba(59, 130, 246, 0.15); color: #60a5fa; font-size: 0.72rem; padding: 2px 8px; border-radius: 10px; font-weight: 600;">PERAN: STAFF / SECTION HEAD</span>
                    </h4>
                    <p>Staff / Section Head dari departemen pemohon memeriksa kewajaran kebutuhan barang, spesifikasi kecocokan mesin/proses di line, serta kelayakan pengadaan. Approver dapat memberikan persetujuan (Approve), meminta perbaikan (Revisi), atau menolak (Reject) disertai alasan tertulis.</p>
                    <div class="step-chips">
                        <span class="step-chip">🔍 Validasi Urgensi & Spesifikasi</span>
                        <span class="step-chip">✍️ Digital Signature Tahap 1</span>
                    </div>
                </div>
                <div class="step-action-col">
                    <span class="step-status-tag staff">Butuh Approval Staff</span>
                    @if($isMasterOrAdmin || ($user && ($user->hasPermission('saturnus.registrasi.approve') || $user->hasPermission('saturnus.registrasi.view'))))
                    <a href="{{ route('saturnus.proses_approval') }}" class="dock-launcher-btn secondary" style="font-size: 0.75rem; padding: 0.4rem 0.95rem;">
                        <span>Review Staff</span> &rarr;
                    </a>
                    @endif
                </div>
            </div>

            <!-- Stage 3 -->
            <div class="pipeline-step-card">
                <div class="step-number-col">
                    <div class="step-num-circle amber">03</div>
                </div>
                <div class="step-content-col">
                    <h4>
                        <span>Verifikasi Tahap 2: Anggaran & Klasifikasi Biaya</span>
                        <span class="step-role-tag" style="background: rgba(245, 158, 11, 0.15); color: #fbbf24; font-size: 0.72rem; padding: 2px 8px; border-radius: 10px; font-weight: 600;">PERAN: ACCOUNTING & FINANCE</span>
                    </h4>
                    <p>Tim Accounting & Finance memverifikasi ketersediaan budget departemen, kesesuaian estimasi biaya pengadaan, klasifikasi akun (Consumable Expense vs Fixed Asset), serta validasi nomor Cost Center pembebanan biaya.</p>
                    <div class="step-chips">
                        <span class="step-chip">💰 Budget & Cost Center Check</span>
                        <span class="step-chip">✍️ Digital Signature Tahap 2</span>
                    </div>
                </div>
                <div class="step-action-col">
                    <span class="step-status-tag acc">Butuh Approval Accounting</span>
                    @if($isMasterOrAdmin || ($user && ($user->hasPermission('saturnus.registrasi.approve') || $user->hasPermission('saturnus.registrasi.view'))))
                    <a href="{{ route('saturnus.proses_approval') }}" class="dock-launcher-btn secondary" style="font-size: 0.75rem; padding: 0.4rem 0.95rem;">
                        <span>Review Acc</span> &rarr;
                    </a>
                    @endif
                </div>
            </div>

            <!-- Stage 4 -->
            <div class="pipeline-step-card">
                <div class="step-number-col">
                    <div class="step-num-circle emerald">04</div>
                </div>
                <div class="step-content-col">
                    <h4>
                        <span>Finalisasi Tahap 3: Penerbitan Kode & Aktivasi Master</span>
                        <span class="step-role-tag" style="background: rgba(16, 185, 129, 0.15); color: #34d399; font-size: 0.72rem; padding: 2px 8px; border-radius: 10px; font-weight: 600;">PERAN: WAREHOUSE CONSUMABLE</span>
                    </h4>
                    <p>Warehouse Consumable melakukan validasi akhir, menetapkan <strong>Kode Barang Resmi</strong>, mengalokasikan nomor Rak/Bin penyimpanan di gudang, mencetak label barcode, dan mempublikasikan data barang ke katalog master aktif (Data View).</p>
                    <div class="step-chips">
                        <span class="step-chip">🏷️ Penerbitan Kode Master & Bin Rak</span>
                        <span class="step-chip">🎉 Status: Terdaftar & Live di Data View</span>
                    </div>
                </div>
                <div class="step-action-col">
                    <span class="step-status-tag wh">Active / Registered</span>
                    @if($isMasterOrAdmin || ($user && ($user->hasPermission('saturnus.directory.view') || $user->hasPermission('saturnus.registrasi.view'))))
                    <a href="{{ route('saturnus.data_view') }}" class="dock-launcher-btn secondary" style="font-size: 0.75rem; padding: 0.4rem 0.95rem;">
                        <span>Lihat Katalog</span> &rarr;
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- SECTION 3: ALUR UNREGISTRASI & DISCONTINUE -->
    <div class="saturn-glass-section" id="hub-alur-unregistrasi">
        <div class="sec-header">
            <div class="sec-header-main">
                <div class="sec-icon-circle rose">
                    🗑️
                </div>
                <div class="sec-title-group">
                    <h2>Panduan Alur Unregistrasi & Discontinue Barang</h2>
                    <p>Prosedur penonaktifan barang consumable obsolete, dead-stock, atau penggantian model</p>
                </div>
            </div>
            <span class="sec-badge" style="color: #fb7185; border-color: rgba(244, 63, 94, 0.3);">2-STAGE DISCONTINUE WORKFLOW</span>
        </div>

        <p style="color: #cbd5e1; font-size: 0.9rem; line-height: 1.6; margin-bottom: 1.5rem;">
            Unregistrasi adalah proses resmi untuk menghapus atau menonaktifkan barang dari daftar katalog aktif gudang consumable. Barang yang telah di-unregistrasi tidak dapat lagi dipesan melalui Purchase Order baru dan akan diarsipkan ke rekam jejak history secara permanen.
        </p>

        <div class="workflow-pipeline">
            <!-- Unreg Step 1 -->
            <div class="pipeline-step-card">
                <div class="step-number-col">
                    <div class="step-num-circle rose">01</div>
                </div>
                <div class="step-content-col">
                    <h4>
                        <span>Pengajuan Form Unregistrasi Barang</span>
                        <span class="step-role-tag" style="background: rgba(244, 63, 94, 0.15); color: #fb7185; font-size: 0.72rem; padding: 2px 8px; border-radius: 10px; font-weight: 600;">PERAN: USER / PEMBUAT</span>
                    </h4>
                    <p>User memilih barang terdaftar yang akan dinonaktifkan, memilih kategori alasan (Obsolete, Mesin Diganti, Dead Stock, Expired, atau Efisiensi Biaya), mencantumkan jumlah sisa stock fisik aktual, dan menyertakan rencana tindakan (Disposal, Scrap, atau Retur Vendor).</p>
                    <div class="step-chips">
                        <span class="step-chip">📌 Format Nomor: UNREG/[DEPT]/[TAHUN]/[BULAN]/XXX</span>
                        <span class="step-chip">📊 Input Sisa Stock Aktual & Alasan</span>
                    </div>
                </div>
                <div class="step-action-col">
                    <span class="step-status-tag draft">Draft Pengajuan</span>
                    @if($isMasterOrAdmin || ($user && $user->hasPermission('saturnus.unregistrasi.view')))
                    <a href="{{ route('saturnus.form_unregistrasi') }}" class="dock-launcher-btn secondary" style="font-size: 0.75rem; padding: 0.4rem 0.95rem;">
                        <span>Form Unreg</span> &rarr;
                    </a>
                    @endif
                </div>
            </div>

            <!-- Unreg Step 2 -->
            <div class="pipeline-step-card">
                <div class="step-number-col">
                    <div class="step-num-circle blue">02</div>
                </div>
                <div class="step-content-col">
                    <h4>
                        <span>Persetujuan Tahap 1: Verifikasi Departemen</span>
                        <span class="step-role-tag" style="background: rgba(59, 130, 246, 0.15); color: #60a5fa; font-size: 0.72rem; padding: 2px 8px; border-radius: 10px; font-weight: 600;">PERAN: STAFF / SECTION HEAD</span>
                    </h4>
                    <p>Staff / Section Head departemen pemohon meninjau dan memastikan bahwa barang memang benar-benar sudah tidak dibutuhkan lagi di line produksi terkait, serta memvalidasi kesesuaian alasan discontinue yang diajukan oleh user.</p>
                    <div class="step-chips">
                        <span class="step-chip">🔍 Verifikasi Tidak Ada Pemakaian Line</span>
                        <span class="step-chip">✍️ Approval Tahap 1</span>
                    </div>
                </div>
                <div class="step-action-col">
                    <span class="step-status-tag staff">Butuh Approval Staff</span>
                    @if($isMasterOrAdmin || ($user && ($user->hasPermission('saturnus.unregistrasi.approve') || $user->hasPermission('saturnus.unregistrasi.view'))))
                    <a href="{{ route('saturnus.unregistrasi_approval') }}" class="dock-launcher-btn secondary" style="font-size: 0.75rem; padding: 0.4rem 0.95rem;">
                        <span>Review Staff</span> &rarr;
                    </a>
                    @endif
                </div>
            </div>

            <!-- Unreg Step 3 -->
            <div class="pipeline-step-card">
                <div class="step-number-col">
                    <div class="step-num-circle emerald">03</div>
                </div>
                <div class="step-content-col">
                    <h4>
                        <span>Finalisasi Tahap 2: Penutupan Bin & Arsip Discontinue</span>
                        <span class="step-role-tag" style="background: rgba(16, 185, 129, 0.15); color: #34d399; font-size: 0.72rem; padding: 2px 8px; border-radius: 10px; font-weight: 600;">PERAN: WAREHOUSE CONSUMABLE</span>
                    </h4>
                    <p>Warehouse Consumable memvalidasi sisa stock di rak/bin gudang, mengosongkan lokasi bin fisik, mencabut status aktif master item, dan memindahkan form ke <strong>History Discontinue</strong> dengan Berita Acara resmi yang tercatat di audit log.</p>
                    <div class="step-chips">
                        <span class="step-chip">📦 Stock Opname & Penutupan Bin</span>
                        <span class="step-chip">📜 Arsip Permanen History</span>
                    </div>
                </div>
                <div class="step-action-col">
                    <span class="step-status-tag discon">Discontinued</span>
                    @if($isMasterOrAdmin || ($user && $user->hasPermission('saturnus.unregistrasi.view')))
                    <a href="{{ route('saturnus.unregistrasi_history') }}" class="dock-launcher-btn secondary" style="font-size: 0.75rem; padding: 0.4rem 0.95rem;">
                        <span>Lihat History</span> &rarr;
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- SECTION 4: MATRIKS PERAN & HAK AKSES -->
    <div class="saturn-glass-section" id="hub-matriks-role">
        <div class="sec-header">
            <div class="sec-header-main">
                <div class="sec-icon-circle purple">
                    🛡️
                </div>
                <div class="sec-title-group">
                    <h2>Matriks Peran & Hak Akses (RBAC Matrix)</h2>
                    <p>Pembagian wewenang, batasan aksi, dan tanggung jawab operasional pada sistem SATURNUS</p>
                </div>
            </div>
            <span class="sec-badge" style="color: #c084fc; border-color: rgba(168, 85, 247, 0.3);">ROLE RESPONSIBILITIES</span>
        </div>

        <div class="role-matrix-grid">
            <!-- Role 1: User / Requester -->
            <div class="role-card">
                <div>
                    <div class="role-header">
                        <div class="role-avatar" style="background: rgba(56, 189, 248, 0.15); color: #38bdf8;">
                            👤
                        </div>
                        <div class="role-info">
                            <h4>User (Pemohon / Requester)</h4>
                            <span>Operator / Staff Lapangan Departemen</span>
                        </div>
                    </div>
                    <ul class="role-permission-list">
                        <li class="allow"><svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2.5" fill="none"><polyline points="20 6 9 17 4 12"></polyline></svg> Membuat form registrasi consumable baru</li>
                        <li class="allow"><svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2.5" fill="none"><polyline points="20 6 9 17 4 12"></polyline></svg> Membuat form unregistrasi / discontinue</li>
                        <li class="allow"><svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2.5" fill="none"><polyline points="20 6 9 17 4 12"></polyline></svg> Melihat status permohonan & history dokumen</li>
                        <li class="deny"><svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2.5" fill="none"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg> Tidak memiliki wewenang approval form</li>
                    </ul>
                </div>
            </div>

            <!-- Role 2: Staff / Section Head -->
            <div class="role-card">
                <div>
                    <div class="role-header">
                        <div class="role-avatar" style="background: rgba(59, 130, 246, 0.15); color: #60a5fa;">
                            👔
                        </div>
                        <div class="role-info">
                            <h4>Staff / Section Head (Tahap 1)</h4>
                            <span>Penanggung Jawab Teknis Departemen</span>
                        </div>
                    </div>
                    <ul class="role-permission-list">
                        <li class="allow"><svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2.5" fill="none"><polyline points="20 6 9 17 4 12"></polyline></svg> Approve / Reject Registrasi Tahap 1 (Dept Terkait)</li>
                        <li class="allow"><svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2.5" fill="none"><polyline points="20 6 9 17 4 12"></polyline></svg> Approve / Reject Unregistrasi Tahap 1</li>
                        <li class="allow"><svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2.5" fill="none"><polyline points="20 6 9 17 4 12"></polyline></svg> Memberikan catatan instruksi revisi ke User</li>
                        <li class="deny"><svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2.5" fill="none"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg> Tidak dapat approve form departemen lain</li>
                    </ul>
                </div>
            </div>

            <!-- Role 3: Accounting & Finance -->
            <div class="role-card">
                <div>
                    <div class="role-header">
                        <div class="role-avatar" style="background: rgba(245, 158, 11, 0.15); color: #fbbf24;">
                            💼
                        </div>
                        <div class="role-info">
                            <h4>Accounting & Finance (Tahap 2)</h4>
                            <span>Pengendali Anggaran & Biaya Perusahaan</span>
                        </div>
                    </div>
                    <ul class="role-permission-list">
                        <li class="allow"><svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2.5" fill="none"><polyline points="20 6 9 17 4 12"></polyline></svg> Approve / Reject Registrasi Tahap 2 (Review Budget)</li>
                        <li class="allow"><svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2.5" fill="none"><polyline points="20 6 9 17 4 12"></polyline></svg> Validasi akun beban consumable vs aset tetap</li>
                        <li class="allow"><svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2.5" fill="none"><polyline points="20 6 9 17 4 12"></polyline></svg> Mengakses seluruh form registrasi lintas departemen</li>
                        <li class="deny"><svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2.5" fill="none"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg> Terkunci dari modul unregistrasi jika tanpa izin</li>
                    </ul>
                </div>
            </div>

            <!-- Role 4: Warehouse Consumable -->
            <div class="role-card">
                <div>
                    <div class="role-header">
                        <div class="role-avatar" style="background: rgba(16, 185, 129, 0.15); color: #34d399;">
                            📦
                        </div>
                        <div class="role-info">
                            <h4>Warehouse Consumable (Tahap 3)</h4>
                            <span>Pengelola Master Data Gudang & Inventori</span>
                        </div>
                    </div>
                    <ul class="role-permission-list">
                        <li class="allow"><svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2.5" fill="none"><polyline points="20 6 9 17 4 12"></polyline></svg> Finalisasi Registrasi & Terbitkan Kode Barang</li>
                        <li class="allow"><svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2.5" fill="none"><polyline points="20 6 9 17 4 12"></polyline></svg> Menentukan lokasi Bin / Rak penyimpanan fisik</li>
                        <li class="allow"><svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2.5" fill="none"><polyline points="20 6 9 17 4 12"></polyline></svg> Finalisasi Unregistrasi & Penutupan Bin Gudang</li>
                        <li class="allow"><svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2.5" fill="none"><polyline points="20 6 9 17 4 12"></polyline></svg> Export katalog master ke Excel & cetak barcode</li>
                    </ul>
                </div>
            </div>

            <!-- Role 5: Administrator / Master -->
            <div class="role-card">
                <div>
                    <div class="role-header">
                        <div class="role-avatar" style="background: rgba(239, 68, 68, 0.15); color: #f87171;">
                            👑
                        </div>
                        <div class="role-info">
                            <h4>Administrator / Master Admin</h4>
                            <span>Pengawas Sistem & Hak Akses Global</span>
                        </div>
                    </div>
                    <ul class="role-permission-list">
                        <li class="allow"><svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2.5" fill="none"><polyline points="20 6 9 17 4 12"></polyline></svg> Bypass wewenang semua tahap approval</li>
                        <li class="allow"><svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2.5" fill="none"><polyline points="20 6 9 17 4 12"></polyline></svg> Manajemen Role & Hak Akses Pengguna (RBAC)</li>
                        <li class="allow"><svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2.5" fill="none"><polyline points="20 6 9 17 4 12"></polyline></svg> Pengawasan audit log & penghapusan checksheet</li>
                        <li class="allow"><svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2.5" fill="none"><polyline points="20 6 9 17 4 12"></polyline></svg> Hak akses penuh tanpa batasan departemen</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- SECTION 5: STANDAR OPERASIONAL (SOP) & PANDUAN PRAKTIS -->
    <div class="saturn-glass-section" id="hub-sop-panduan">
        <div class="sec-header">
            <div class="sec-header-main">
                <div class="sec-icon-circle emerald">
                    📋
                </div>
                <div class="sec-title-group">
                    <h2>Standar Operasional (SOP) & Panduan Praktis</h2>
                    <p>Pedoman pengisian formulir, ketentuan lampiran, dan solusi pertanyaan umum</p>
                </div>
            </div>
            <span class="sec-badge" style="color: #34d399; border-color: rgba(52, 211, 153, 0.3);">OPERATIONAL GUIDELINES</span>
        </div>

        <div class="sop-grid" style="margin-bottom: 2rem;">
            <!-- Guideline 1 -->
            <div class="sop-card">
                <h4>
                    <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2.5" fill="none"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                    <span>Standar Penamaan Barang</span>
                </h4>
                <ul class="sop-checklist">
                    <li><svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2.5" fill="none"><polyline points="20 6 9 17 4 12"></polyline></svg> <span>Gunakan format baku: <strong>[Jenis Part] [Nama Barang] [Dimensi / Tipe] [Brand]</strong></span></li>
                    <li><svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2.5" fill="none"><polyline points="20 6 9 17 4 12"></polyline></svg> <span>Hindari singkatan lokal yang tidak resmi atau ambigu.</span></li>
                    <li><svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2.5" fill="none"><polyline points="20 6 9 17 4 12"></polyline></svg> <span>Pastikan penulisan satuan ukuran (UoM) sesuai standar (PCS, BOX, ROLL, KG, LTR).</span></li>
                </ul>
            </div>

            <!-- Guideline 2 -->
            <div class="sop-card">
                <h4>
                    <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2.5" fill="none"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                    <span>Ketentuan Dokumen Lampiran</span>
                </h4>
                <ul class="sop-checklist">
                    <li><svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2.5" fill="none"><polyline points="20 6 9 17 4 12"></polyline></svg> <span><strong>Foto Fisik:</strong> Lampirkan foto tampak jelas, memperlihatkan label spesifikasi jika ada.</span></li>
                    <li><svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2.5" fill="none"><polyline points="20 6 9 17 4 12"></polyline></svg> <span><strong>Technical Data Sheet (TDS):</strong> Wajib untuk part presisi / sparepart mesin.</span></li>
                    <li><svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2.5" fill="none"><polyline points="20 6 9 17 4 12"></polyline></svg> <span><strong>MSDS:</strong> Wajib untuk cairan kimia, oli, pelumas, sealant, dan lem perekat.</span></li>
                </ul>
            </div>

            <!-- Guideline 3 -->
            <div class="sop-card">
                <h4>
                    <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2.5" fill="none"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                    <span>Syarat Unregistrasi / Discontinue</span>
                </h4>
                <ul class="sop-checklist">
                    <li><svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2.5" fill="none"><polyline points="20 6 9 17 4 12"></polyline></svg> <span>Lakukan penghitungan sisa fisik barang di line sebelum mengajukan form unregistrasi.</span></li>
                    <li><svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2.5" fill="none"><polyline points="20 6 9 17 4 12"></polyline></svg> <span>Pastikan tidak ada pesanan (PO Outstanding) yang masih berjalan di modul MARS.</span></li>
                    <li><svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2.5" fill="none"><polyline points="20 6 9 17 4 12"></polyline></svg> <span>Sertakan rencana pemusnahan (Scrap) atau pemindahan sisa stok yang jelas.</span></li>
                </ul>
            </div>
        </div>

        <!-- FAQ Section -->
        <h3 style="font-size: 1.1rem; font-weight: 700; color: #f1f5f9; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
            <span>❓</span>
            <span>Frequently Asked Questions (FAQ)</span>
        </h3>

        <div class="faq-list">
            <div class="faq-item" onclick="toggleFaq(this)">
                <div class="faq-question">
                    <span>Berapa lama estimasi waktu proses approval formulir registrasi?</span>
                    <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2.5" fill="none"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </div>
                <div class="faq-answer">
                    Proses approval umumnya memakan waktu <strong>1 hingga 3 hari kerja</strong> tergantung pada kecepatan verifikasi di setiap tahap (Staff Dept &rarr; Accounting &rarr; Warehouse Consumable). Anda dapat memantau status real-time langsung melalui menu form.
                </div>
            </div>

            <div class="faq-item" onclick="toggleFaq(this)">
                <div class="faq-question">
                    <span>Apa yang harus saya lakukan jika form registrasi ditandai "Revision Required"?</span>
                    <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2.5" fill="none"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </div>
                <div class="faq-answer">
                    Buka form registrasi terkait, baca catatan atau pesan revisi yang ditinggalkan oleh approver pada panel komentar, ubah data atau lampiran yang diminta, lalu klik tombol simpan perbaikan agar formulir diverifikasi kembali.
                </div>
            </div>

            <div class="faq-item" onclick="toggleFaq(this)">
                <div class="faq-question">
                    <span>Bagaimana cara memeriksa apakah suatu barang sudah pernah terdaftar sebelumnya?</span>
                    <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2.5" fill="none"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </div>
                <div class="faq-answer">
                    Buka menu <strong>Data Registrasi / Direktori Barang</strong> (Data View), manfaatkan kolom pencarian instan dan filter kategori. Anda dapat mencari berdasarkan nama barang, tipe part, atau departemen pemilik sebelum membuat form baru guna menghindari duplikasi master data.
                </div>
            </div>

            <div class="faq-item" onclick="toggleFaq(this)">
                <div class="faq-question">
                    <span>Apakah barang yang sudah di-unregistrasi masih bisa diaktifkan kembali?</span>
                    <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2.5" fill="none"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </div>
                <div class="faq-answer">
                    Barang yang sudah berstatus <em>Discontinued</em> tersimpan secara permanen di menu History Discontinue sebagai audit log. Jika di masa mendatang item tersebut dibutuhkan kembali, user disarankan mengajukan form registrasi baru agar mendapatkan alokasi bin dan verifikasi budget yang terkini.
                </div>
            </div>
        </div>
    </div>

    <!-- SECTION 6: PUSAT AKSI CEPAT (LAUNCHPAD) -->
    <div class="saturn-glass-section" id="hub-quick-launch">
        <div class="sec-header">
            <div class="sec-header-main">
                <div class="sec-icon-circle amber">
                    🚀
                </div>
                <div class="sec-title-group">
                    <h2>Pusat Aksi Cepat SATURNUS</h2>
                    <p>Akses instan ke seluruh fitur utama operasional sistem sesuai hak akses Anda</p>
                </div>
            </div>
            <span class="sec-badge" style="color: #fbbf24; border-color: rgba(251, 191, 36, 0.3);">DIRECT LAUNCHER</span>
        </div>

        <div class="launchpad-grid">
            <!-- Launch 1: Form Registrasi -->
            <div class="launchpad-card">
                <div>
                    <div class="launchpad-card-top">
                        <div class="launchpad-icon-box" style="background: rgba(56, 189, 248, 0.15); color: #38bdf8;">
                            📝
                        </div>
                        <h3>Form Registrasi</h3>
                    </div>
                    <p>Buat pengajuan registrasi barang consumable baru atau kelola draft checksheet departemen Anda.</p>
                </div>
                @if($isMasterOrAdmin || ($user && $user->hasPermission('saturnus.registrasi.view')))
                <a href="{{ route('saturnus.form_registrasi') }}" class="launchpad-btn primary">
                    <span>Buka Form Registrasi</span>
                    <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2.5" fill="none"><polyline points="9 18 15 12 9 6"></polyline></svg>
                </a>
                @else
                <button class="launchpad-btn secondary" disabled style="opacity: 0.5; cursor: not-allowed;">
                    <span>Akses Terbatas</span>
                </button>
                @endif
            </div>

            <!-- Launch 2: Approval Registrasi -->
            <div class="launchpad-card">
                <div>
                    <div class="launchpad-card-top">
                        <div class="launchpad-icon-box" style="background: rgba(59, 130, 246, 0.15); color: #60a5fa;">
                            ✅
                        </div>
                        <h3>Approval Registrasi</h3>
                    </div>
                    <p>Verifikasi & persetujuan pengajuan barang consumable (Staff Dept, Accounting, Warehouse).</p>
                </div>
                @if($isMasterOrAdmin || ($user && ($user->hasPermission('saturnus.registrasi.approve') || $user->hasPermission('saturnus.registrasi.view'))))
                <a href="{{ route('saturnus.proses_approval') }}" class="launchpad-btn secondary">
                    <span>Buka Panel Approval</span>
                    <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2.5" fill="none"><polyline points="9 18 15 12 9 6"></polyline></svg>
                </a>
                @else
                <button class="launchpad-btn secondary" disabled style="opacity: 0.5; cursor: not-allowed;">
                    <span>Akses Terbatas</span>
                </button>
                @endif
            </div>

            <!-- Launch 3: Data Master Terdaftar -->
            <div class="launchpad-card">
                <div>
                    <div class="launchpad-card-top">
                        <div class="launchpad-icon-box" style="background: rgba(52, 211, 153, 0.15); color: #34d399;">
                            🗄️
                        </div>
                        <h3>Katalog Data Terdaftar</h3>
                    </div>
                    <p>Direktori master barang consumable aktif terdaftar lengkap dengan kode QR dan lokasi Bin.</p>
                </div>
                @if($isMasterOrAdmin || ($user && ($user->hasPermission('saturnus.directory.view') || $user->hasPermission('saturnus.registrasi.view'))))
                <a href="{{ route('saturnus.data_view') }}" class="launchpad-btn secondary">
                    <span>Buka Katalog Master</span>
                    <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2.5" fill="none"><polyline points="9 18 15 12 9 6"></polyline></svg>
                </a>
                @else
                <button class="launchpad-btn secondary" disabled style="opacity: 0.5; cursor: not-allowed;">
                    <span>Akses Terbatas</span>
                </button>
                @endif
            </div>

            <!-- Launch 4: Form Unregistrasi -->
            <div class="launchpad-card">
                <div>
                    <div class="launchpad-card-top">
                        <div class="launchpad-icon-box" style="background: rgba(244, 63, 94, 0.15); color: #fb7185;">
                            🗑️
                        </div>
                        <h3>Form Unregistrasi</h3>
                    </div>
                    <p>Ajukan penonaktifan (discontinue / scrap) barang consumable yang sudah tidak digunakan.</p>
                </div>
                @if($isMasterOrAdmin || ($user && $user->hasPermission('saturnus.unregistrasi.view')))
                <a href="{{ route('saturnus.form_unregistrasi') }}" class="launchpad-btn danger">
                    <span>Buka Form Unregistrasi</span>
                    <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2.5" fill="none"><polyline points="9 18 15 12 9 6"></polyline></svg>
                </a>
                @else
                <button class="launchpad-btn secondary" disabled style="opacity: 0.5; cursor: not-allowed;">
                    <span>Akses Terbatas</span>
                </button>
                @endif
            </div>

            <!-- Launch 5: Approval Unregistrasi -->
            <div class="launchpad-card">
                <div>
                    <div class="launchpad-card-top">
                        <div class="launchpad-icon-box" style="background: rgba(168, 85, 247, 0.15); color: #c084fc;">
                            🛡️
                        </div>
                        <h3>Approval Unregistrasi</h3>
                    </div>
                    <p>Review dan persetujuan penonaktifan barang (Staff Dept & Finalisasi Gudang).</p>
                </div>
                @if($isMasterOrAdmin || ($user && ($user->hasPermission('saturnus.unregistrasi.approve') || $user->hasPermission('saturnus.unregistrasi.view'))))
                <a href="{{ route('saturnus.unregistrasi_approval') }}" class="launchpad-btn secondary">
                    <span>Approval Unregistrasi</span>
                    <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2.5" fill="none"><polyline points="9 18 15 12 9 6"></polyline></svg>
                </a>
                @else
                <button class="launchpad-btn secondary" disabled style="opacity: 0.5; cursor: not-allowed;">
                    <span>Akses Terbatas</span>
                </button>
                @endif
            </div>

            <!-- Launch 6: History Discontinue -->
            <div class="launchpad-card">
                <div>
                    <div class="launchpad-card-top">
                        <div class="launchpad-icon-box" style="background: rgba(251, 191, 36, 0.15); color: #fbbf24;">
                            📜
                        </div>
                        <h3>History Discontinue</h3>
                    </div>
                    <p>Rekam jejak audit dan riwayat seluruh barang consumable yang telah di-discontinue.</p>
                </div>
                @if($isMasterOrAdmin || ($user && $user->hasPermission('saturnus.unregistrasi.view')))
                <a href="{{ route('saturnus.unregistrasi_history') }}" class="launchpad-btn secondary">
                    <span>Lihat Riwayat History</span>
                    <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2.5" fill="none"><polyline points="9 18 15 12 9 6"></polyline></svg>
                </a>
                @else
                <button class="launchpad-btn secondary" disabled style="opacity: 0.5; cursor: not-allowed;">
                    <span>Akses Terbatas</span>
                </button>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    // =========================================================================
    // SATURN MOONS METADATA & INSPECTOR DRAWER
    // =========================================================================
    const moonMetadata = {
        titan: {
            badge: 'SATURN VI · TITAN',
            title: 'TITAN (PRODUCTION FLEET)',
            sub: 'Largest Saturnian Moon with Dense Golden Atmosphere and Liquid Methane Lakes',
            diam: '5,149 KM',
            dist: '1,221,870 KM',
            dept: 'Production Requestor Node',
            link: "{{ route('saturnus.form_registrasi') }}"
        },
        enceladus: {
            badge: 'SATURN II · ENCELADUS',
            title: 'ENCELADUS (STAFF VERIFIER)',
            sub: 'Glistening Ice World with Active Cryovolcanic Geysers into Saturn E-Ring',
            diam: '504 KM',
            dist: '238,020 KM',
            dept: 'Staff Verification Node',
            link: "{{ route('saturnus.proses_approval') }}"
        },
        rhea: {
            badge: 'SATURN V · RHEA',
            title: 'RHEA (ACCOUNTING HUB)',
            sub: 'Heavily Cratered Ice Giant with Tenuous Oxygen Atmosphere',
            diam: '1,527 KM',
            dist: '527,108 KM',
            dept: 'Accounting & Budget Hub',
            link: "{{ route('saturnus.proses_approval') }}"
        },
        dione: {
            badge: 'SATURN IV · DIONE',
            title: 'DIONE (WAREHOUSE DOCK)',
            sub: 'Dense Ice Body with Dramatic Glowing Ice Chasm Cliffs',
            diam: '1,122 KM',
            dist: '377,396 KM',
            dept: 'Warehouse Master Dock',
            link: "{{ route('saturnus.data_view') }}"
        },
        tethys: {
            badge: 'SATURN III · TETHYS',
            title: 'TETHYS (INVENTORY FLEET)',
            sub: 'Low Density Pure Water-Ice Body with Ithaca Chasma Trench',
            diam: '1,062 KM',
            dist: '294,619 KM',
            dept: 'Inventory Threshold Controller',
            link: "{{ route('saturnus.data_view') }}"
        },
        mimas: {
            badge: 'SATURN I · MIMAS',
            title: 'MIMAS (CONSUMABLE CORE)',
            sub: 'Inner Ring-Shepherd Moon with Giant Herschel Impact Crater',
            diam: '396 KM',
            dist: '185,539 KM',
            dept: 'Fast-Moving Consumables',
            link: "{{ route('dashboard') }}"
        }
    };

    function inspectMoon(key) {
        const data = moonMetadata[key];
        if (!data) return;

        document.getElementById('inspBadge').innerText = data.badge;
        document.getElementById('inspTitle').innerText = data.title;
        document.getElementById('inspSub').innerText = data.sub;
        document.getElementById('inspDiam').innerText = data.diam;
        document.getElementById('inspDist').innerText = data.dist;
        document.getElementById('inspDept').innerText = data.dept;
        document.getElementById('inspLink').href = data.link;

        document.getElementById('orbitInspectorBox').classList.add('show');
    }

    function closeInspector() {
        document.getElementById('orbitInspectorBox').classList.remove('show');
    }

    // =========================================================================
    // 🪐 PHOTOREALISTIC 3D WEBGL SATURN SIMULATION ENGINE (THREE.JS)
    // =========================================================================
    let simSpeed = 1.0;
    let isSimPaused = false;
    let targetCameraPos = { x: 0, y: 14, z: 34 };
    let activeCameraView = 'orbit';

    function setSimulationSpeed(mult, btn) {
        simSpeed = mult;
        isSimPaused = false;
        document.querySelectorAll('.speed-btn').forEach(b => b.classList.remove('active'));
        if (btn) btn.classList.add('active');
        document.getElementById('telemetrySpeedVal').innerText = mult + 'X WARP SPEED';
    }

    function toggleSimulationPause(btn) {
        isSimPaused = !isSimPaused;
        document.querySelectorAll('.speed-btn').forEach(b => b.classList.remove('active'));
        if (btn) btn.classList.add('active');
        btn.innerText = isSimPaused ? '▶ RESUME' : '⏸ PAUSE';
        document.getElementById('telemetrySpeedVal').innerText = isSimPaused ? 'PAUSED' : (simSpeed + 'X');
    }

    function setSaturnCameraView(viewKey, btn) {
        activeCameraView = viewKey;
        document.querySelectorAll('.sector-pill').forEach(p => p.classList.remove('active'));
        if (btn) btn.classList.add('active');

        if (viewKey === 'orbit') {
            targetCameraPos = { x: 0, y: 14, z: 34 };
        } else if (viewKey === 'ring') {
            targetCameraPos = { x: 28, y: 2.2, z: 12 };
        } else if (viewKey === 'pole') {
            targetCameraPos = { x: 0, y: 38, z: 2 };
        } else if (viewKey === 'titan') {
            targetCameraPos = { x: -24, y: 8, z: 18 };
            inspectMoon('titan');
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const container = document.getElementById('saturnWebglContainer');
        if (!container || typeof THREE === 'undefined') return;

        let width = container.offsetWidth;
        let height = container.offsetHeight;

        // 1. Scene, Camera, Renderer
        const scene = new THREE.Scene();
        const camera = new THREE.PerspectiveCamera(45, width / height, 0.1, 1000);
        camera.position.set(0, 14, 34);

        const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true, powerPreference: "high-performance" });
        renderer.setSize(width, height);
        renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
        renderer.shadowMap.enabled = true;
        renderer.shadowMap.type = THREE.PCFSoftShadowMap;
        container.appendChild(renderer.domElement);

        // 2. Realistic Procedural Textures (Saturn Gas Bands & Rings)
        const planetCanvas = document.createElement('canvas');
        planetCanvas.width = 1024;
        planetCanvas.height = 512;
        const pCtx = planetCanvas.getContext('2d');

        const pGrad = pCtx.createLinearGradient(0, 0, 0, 512);
        pGrad.addColorStop(0.00, '#3a506b');
        pGrad.addColorStop(0.15, '#5c677d');
        pGrad.addColorStop(0.30, '#d4a373');
        pGrad.addColorStop(0.42, '#fefae0');
        pGrad.addColorStop(0.50, '#e9c46a');
        pGrad.addColorStop(0.58, '#fefae0');
        pGrad.addColorStop(0.70, '#dda15e');
        pGrad.addColorStop(0.85, '#bc6c25');
        pGrad.addColorStop(1.00, '#283618');
        pCtx.fillStyle = pGrad;
        pCtx.fillRect(0, 0, 1024, 512);

        for (let y = 0; y < 512; y += 2) {
            pCtx.fillStyle = Math.random() > 0.5 ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)';
            pCtx.fillRect(0, y, 1024, Math.random() * 4 + 1);
        }

        const planetTexture = new THREE.CanvasTexture(planetCanvas);

        const ringCanvas = document.createElement('canvas');
        ringCanvas.width = 1024;
        ringCanvas.height = 64;
        const rCtx = ringCanvas.getContext('2d');

        const rGrad = rCtx.createLinearGradient(0, 0, 1024, 0);
        rGrad.addColorStop(0.00, 'rgba(0,0,0,0)');
        rGrad.addColorStop(0.18, 'rgba(0,0,0,0)');
        rGrad.addColorStop(0.20, 'rgba(180, 160, 130, 0.25)');
        rGrad.addColorStop(0.38, 'rgba(220, 190, 150, 0.45)');
        rGrad.addColorStop(0.40, 'rgba(245, 225, 185, 0.95)');
        rGrad.addColorStop(0.68, 'rgba(230, 205, 165, 0.90)');
        rGrad.addColorStop(0.70, 'rgba(0, 0, 0, 0.05)');
        rGrad.addColorStop(0.74, 'rgba(0, 0, 0, 0.05)');
        rGrad.addColorStop(0.75, 'rgba(210, 185, 145, 0.70)');
        rGrad.addColorStop(0.92, 'rgba(190, 165, 130, 0.60)');
        rGrad.addColorStop(0.93, 'rgba(0,0,0,0.0)');
        rGrad.addColorStop(0.95, 'rgba(200, 175, 140, 0.55)');
        rGrad.addColorStop(0.98, 'rgba(160, 140, 110, 0.35)');
        rGrad.addColorStop(1.00, 'rgba(0,0,0,0)');
        rCtx.fillStyle = rGrad;
        rCtx.fillRect(0, 0, 1024, 64);

        const ringTexture = new THREE.CanvasTexture(ringCanvas);

        // 3. Saturn Planetary Body (Tilted 26.73°)
        const saturnSystem = new THREE.Group();
        saturnSystem.rotation.z = THREE.MathUtils.degToRad(-26.73);
        scene.add(saturnSystem);

        const planetGeo = new THREE.SphereGeometry(6.5, 64, 64);
        planetGeo.scale(1, 0.91, 1);
        const planetMat = new THREE.MeshStandardMaterial({
            map: planetTexture,
            roughness: 0.72,
            metalness: 0.05
        });
        const planetMesh = new THREE.Mesh(planetGeo, planetMat);
        planetMesh.castShadow = true;
        planetMesh.receiveShadow = false; // Prevents self-shadow acne and polygon clipping artifacts
        saturnSystem.add(planetMesh);

        const ringGeo = new THREE.RingGeometry(7.8, 18.2, 128);
        const pos = ringGeo.attributes.position;
        const uvs = ringGeo.attributes.uv;
        for (let i = 0; i < pos.count; i++) {
            const x = pos.getX(i);
            const y = pos.getY(i);
            const r = Math.sqrt(x * x + y * y);
            const u = (r - 7.8) / (18.2 - 7.8);
            uvs.setXY(i, u, 0.5);
        }
        ringGeo.rotateX(Math.PI / 2);

        const ringMat = new THREE.MeshStandardMaterial({
            map: ringTexture,
            side: THREE.DoubleSide,
            transparent: true,
            opacity: 0.95,
            roughness: 0.6
        });
        const ringMesh = new THREE.Mesh(ringGeo, ringMat);
        ringMesh.receiveShadow = true;
        ringMesh.castShadow = false; // Ring is transparent micro-dust; disable solid polygon shadow casting to prevent black block artifacts
        saturnSystem.add(ringMesh);

        // 4. Moons of Saturn
        const moonsData = [
            { name: 'mimas',     r: 0.35, dist: 20.2, speed: 0.024, color: '#e2e8f0', key: 'mimas' },
            { name: 'enceladus', r: 0.42, dist: 23.6, speed: 0.019, color: '#38bdf8', key: 'enceladus' },
            { name: 'tethys',    r: 0.48, dist: 27.2, speed: 0.015, color: '#cbd5e1', key: 'tethys' },
            { name: 'dione',     r: 0.52, dist: 31.0, speed: 0.012, color: '#34d399', key: 'dione' },
            { name: 'rhea',      r: 0.60, dist: 35.0, speed: 0.009, color: '#c084fc', key: 'rhea' },
            { name: 'titan',     r: 1.15, dist: 40.0, speed: 0.005, color: '#fbbf24', key: 'titan' }
        ];

        const moonMeshes = [];

        moonsData.forEach((m, idx) => {
            const orbitCurve = new THREE.EllipseCurve(0, 0, m.dist, m.dist, 0, 2 * Math.PI, false, 0);
            const orbitPoints = orbitCurve.getPoints(96);
            const orbitGeo = new THREE.BufferGeometry().setFromPoints(orbitPoints.map(p => new THREE.Vector3(p.x, 0, p.y)));
            const orbitMat = new THREE.LineBasicMaterial({ color: m.color, transparent: true, opacity: 0.22 });
            const orbitLine = new THREE.Line(orbitGeo, orbitMat);
            saturnSystem.add(orbitLine);

            const moonGeo = new THREE.SphereGeometry(m.r, 24, 24);
            const moonMat = new THREE.MeshStandardMaterial({
                color: m.color,
                roughness: 0.6,
                metalness: 0.2,
                emissive: m.color,
                emissiveIntensity: 0.2
            });
            const moonMesh = new THREE.Mesh(moonGeo, moonMat);
            moonMesh.castShadow = true;
            moonMesh.userData = { ...m, angle: (idx * Math.PI) / 3 };
            saturnSystem.add(moonMesh);
            moonMeshes.push(moonMesh);
        });

        // 5. Dust Ring Particles
        const particleGeo = new THREE.BufferGeometry();
        const particleCount = 700;
        const particlePos = new Float32Array(particleCount * 3);
        const particleColors = new Float32Array(particleCount * 3);

        for (let i = 0; i < particleCount; i++) {
            const rad = THREE.MathUtils.randFloat(8.0, 18.0);
            const theta = Math.random() * Math.PI * 2;
            particlePos[i * 3] = Math.cos(theta) * rad;
            particlePos[i * 3 + 1] = THREE.MathUtils.randFloatSpread(0.25);
            particlePos[i * 3 + 2] = Math.sin(theta) * rad;

            particleColors[i * 3] = 0.85 + Math.random() * 0.15;
            particleColors[i * 3 + 1] = 0.78 + Math.random() * 0.2;
            particleColors[i * 3 + 2] = 0.65 + Math.random() * 0.35;
        }

        particleGeo.setAttribute('position', new THREE.BufferAttribute(particlePos, 3));
        particleGeo.setAttribute('color', new THREE.BufferAttribute(particleColors, 3));

        const particleMat = new THREE.PointsMaterial({
            size: 0.18,
            vertexColors: true,
            transparent: true,
            opacity: 0.75
        });
        const ringParticles = new THREE.Points(particleGeo, particleMat);
        saturnSystem.add(ringParticles);

        // 6. Lighting Setup
        const sunLight = new THREE.DirectionalLight(0xfff3db, 2.2);
        sunLight.position.set(45, 18, 30);
        sunLight.castShadow = true;
        sunLight.shadow.mapSize.width = 2048;
        sunLight.shadow.mapSize.height = 2048;
        sunLight.shadow.camera.near = 0.5;
        sunLight.shadow.camera.far = 150;
        sunLight.shadow.camera.left = -35;
        sunLight.shadow.camera.right = 35;
        sunLight.shadow.camera.top = 35;
        sunLight.shadow.camera.bottom = -35;
        sunLight.shadow.bias = -0.0001;
        sunLight.shadow.normalBias = 0.02;
        scene.add(sunLight);

        const ambientLight = new THREE.AmbientLight(0x0a142c, 0.85);
        scene.add(ambientLight);

        const rimLight = new THREE.DirectionalLight(0x00adef, 0.9);
        rimLight.position.set(-30, -10, -20);
        scene.add(rimLight);

        // 7. Interactive Drag to Rotate
        let isDragging = false;
        let prevMousePos = { x: 0, y: 0 };

        container.addEventListener('mousedown', function (e) {
            isDragging = true;
            prevMousePos = { x: e.clientX, y: e.clientY };
        });

        window.addEventListener('mouseup', function () {
            isDragging = false;
        });

        window.addEventListener('mousemove', function (e) {
            if (!isDragging) return;
            const deltaX = e.clientX - prevMousePos.x;
            const deltaY = e.clientY - prevMousePos.y;

            saturnSystem.rotation.y += deltaX * 0.006;
            saturnSystem.rotation.x += deltaY * 0.004;

            prevMousePos = { x: e.clientX, y: e.clientY };
        });

        // Raycasting on Moon Click
        const raycaster = new THREE.Raycaster();
        const mouse = new THREE.Vector2();

        container.addEventListener('click', function (e) {
            const rect = container.getBoundingClientRect();
            mouse.x = ((e.clientX - rect.left) / rect.width) * 2 - 1;
            mouse.y = -((e.clientY - rect.top) / rect.height) * 2 + 1;

            raycaster.setFromCamera(mouse, camera);
            const intersects = raycaster.intersectObjects(moonMeshes);

            if (intersects.length > 0) {
                const clickedMoon = intersects[0].object;
                inspectMoon(clickedMoon.userData.key);
            }
        });

        // Resize Listener
        window.addEventListener('resize', function () {
            width = container.offsetWidth;
            height = container.offsetHeight;
            camera.aspect = width / height;
            camera.updateProjectionMatrix();
            renderer.setSize(width, height);
        });

        // 8. 60 FPS Render Loop
        let clock = new THREE.Clock();

        function animate() {
            requestAnimationFrame(animate);
            const delta = clock.getDelta();

            camera.position.x += (targetCameraPos.x - camera.position.x) * 0.04;
            camera.position.y += (targetCameraPos.y - camera.position.y) * 0.04;
            camera.position.z += (targetCameraPos.z - camera.position.z) * 0.04;
            camera.lookAt(0, 0, 0);

            if (!isSimPaused) {
                planetMesh.rotation.y += 0.003 * simSpeed;
                ringParticles.rotation.y += 0.0015 * simSpeed;

                if (!isDragging && activeCameraView === 'orbit') {
                    saturnSystem.rotation.y += 0.0012 * simSpeed;
                }

                moonMeshes.forEach(m => {
                    m.userData.angle += m.userData.speed * simSpeed;
                    m.position.x = Math.cos(m.userData.angle) * m.userData.dist;
                    m.position.z = Math.sin(m.userData.angle) * m.userData.dist;
                });
            }

            renderer.render(scene, camera);
        }

        animate();
    });

    // =========================================================================
    // KNOWLEDGE HUB FAQ ACCORDION & SCROLLSPY
    // =========================================================================
    function toggleFaq(el) {
        if (!el) return;
        el.classList.toggle('active');
    }

    document.addEventListener('DOMContentLoaded', function () {
        // Smooth scroll for nav pills
        const navPills = document.querySelectorAll('.hub-nav-pill');
        navPills.forEach(pill => {
            pill.addEventListener('click', function (e) {
                const targetId = this.getAttribute('href');
                if (targetId && targetId.startsWith('#')) {
                    const targetEl = document.querySelector(targetId);
                    if (targetEl) {
                        e.preventDefault();
                        targetEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        navPills.forEach(p => p.classList.remove('active'));
                        this.classList.add('active');
                    }
                }
            });
        });

        // IntersectionObserver for automatic pill activation on scroll
        const sections = document.querySelectorAll('.saturn-glass-section');
        if ('IntersectionObserver' in window && sections.length > 0) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const id = entry.target.getAttribute('id');
                        if (id) {
                            navPills.forEach(pill => {
                                if (pill.getAttribute('href') === '#' + id) {
                                    pill.classList.add('active');
                                } else {
                                    pill.classList.remove('active');
                                }
                            });
                        }
                    }
                });
            }, {
                rootMargin: '-20% 0px -70% 0px'
            });

            sections.forEach(sec => observer.observe(sec));
        }
    });
</script>
@endsection
