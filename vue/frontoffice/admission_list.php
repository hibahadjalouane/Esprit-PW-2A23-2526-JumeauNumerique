<?php
// Vue: Frontoffice - Admission List
// Variables expected from controller:
//   $active_admission      → array|null
//   $history_admissions    → array
//   $db_error              → string|null

// ─── Helper: format date to readable French string ───────────────────────
function formatDateFR(?string $date): string {
    if (!$date) return '—';
    $months = [
        '01'=>'Jan','02'=>'Fév','03'=>'Mar','04'=>'Avr',
        '05'=>'Mai','06'=>'Juin','07'=>'Juil','08'=>'Aoû',
        '09'=>'Sep','10'=>'Oct','11'=>'Nov','12'=>'Déc'
    ];
    [$y, $m, $d] = explode('-', $date);
    return intval($d) . ' ' . ($months[$m] ?? $m) . ' ' . $y;
}

// ─── Helper: badge color based on mode_entree ─────────────────────────────
function modeEntreeBadge(string $mode): string {
    return match(strtolower(trim($mode))) {
        'urgence'    => 'badge-urgence',
        'planifiée'  => 'badge-planifiee',
        'transfert'  => 'badge-transfert',
        default      => 'badge-default',
    };
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Admissions | Clinical Architect</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">

<style>
:root {
    --on-tertiary-container: #f6edff;
    --on-tertiary: #ffffff;
    --on-background: #161a2e;
    --on-primary-container: #eeefff;
    --surface-container: #ececff;
    --primary-fixed: #dbe1ff;
    --surface-container-low: #f3f2ff;
    --on-primary: #ffffff;
    --on-primary-fixed: #00174b;
    --inverse-on-surface: #f0efff;
    --on-tertiary-fixed: #23005c;
    --secondary-container: #6df5e1;
    --tertiary-container: #7d4ce7;
    --tertiary: #632ecd;
    --on-secondary: #ffffff;
    --on-surface-variant: #434655;
    --on-tertiary-fixed-variant: #5516be;
    --tertiary-fixed: #e9ddff;
    --on-primary-fixed-variant: #003ea8;
    --surface-tint: #0053db;
    --error: #ba1a1a;
    --primary-container: #2563eb;
    --outline-variant: #c3c6d7;
    --primary-fixed-dim: #b4c5ff;
    --error-container: #ffdad6;
    --surface: #fbf8ff;
    --secondary-fixed: #71f8e4;
    --on-secondary-fixed-variant: #005048;
    --surface-container-high: #e5e7ff;
    --inverse-surface: #2b2f44;
    --on-error-container: #93000a;
    --tertiary-fixed-dim: #d0bcff;
    --surface-variant: #dee1fc;
    --on-secondary-container: #006f64;
    --on-secondary-fixed: #00201c;
    --secondary-fixed-dim: #4fdbc8;
    --surface-container-lowest: #ffffff;
    --primary: #004ac6;
    --inverse-primary: #b4c5ff;
    --surface-container-highest: #dee1fc;
    --surface-dim: #d6d8f3;
    --background: #fbf8ff;
    --secondary: #006b5f;
    --on-error: #ffffff;
    --surface-bright: #fbf8ff;
    --outline: #737686;
    --on-surface: #161a2e;
    --radius-sm: .125rem;
    --radius-md: .25rem;
    --radius-xl: .5rem;
    --radius-pill: .75rem;
}

*, *::before, *::after { box-sizing: border-box; }

body {
    font-family: 'Inter', sans-serif;
    background-color: var(--background);
    color: var(--on-surface);
    padding-top: 96px;
    margin: 0;
}

.font-headline { font-family: 'Plus Jakarta Sans', sans-serif; }

.material-symbols-outlined {
    font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
    line-height: 1;
    vertical-align: middle;
}
.fill-icon {
    font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24;
}

/* ── NAVBAR ─────────────────────────────────────────────────────────────── */
.top-nav {
    position: fixed;
    top: 0; left: 0; right: 0;
    z-index: 1050;
    background: rgba(255,255,255,.60);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    box-shadow: 0 .125rem .25rem rgba(0,0,0,.04);
}
.top-nav-inner {
    min-height: 64px;
    padding: 0 2rem;
}
.brand {
    color: #1d4ed8;
    font-size: 1.125rem;
    font-weight: 900;
    text-decoration: none;
    font-family: 'Plus Jakarta Sans', sans-serif;
}
.nav-link-custom {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-weight: 500;
    font-size: .875rem;
    color: #475569;
    text-decoration: none;
    transition: color .2s;
    padding-bottom: .2rem;
}
.nav-link-custom:hover { color: #3b82f6; }
.nav-link-active { font-weight: 700; color: #2563eb; border-bottom: 2px solid #2563eb; }

.btn-emergency {
    background: var(--primary);
    color: var(--on-primary);
    border: none;
    padding: .5rem 1rem;
    border-radius: .75rem;
    font-size: .875rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: .5rem;
    cursor: pointer;
    transition: opacity .2s;
}
.btn-emergency:hover { opacity: .8; }

.icon-btn {
    color: #475569;
    padding: .5rem;
    border-radius: 999px;
    cursor: pointer;
    transition: background-color .2s;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    user-select: none;
}
.icon-btn:hover { background: var(--surface-container-low); }

.avatar {
    width: 40px; height: 40px;
    object-fit: cover;
    border-radius: 50%;
    box-shadow: 0 0 0 2px rgba(0,74,198,.10);
}

/* ── PAGE LAYOUT ─────────────────────────────────────────────────────────── */
.page-wrap {
    max-width: 1280px;
    margin: 0 auto;
    padding: 0 2rem 3rem;
}

.hero-title {
    font-size: 2.25rem;
    line-height: 1.15;
    letter-spacing: -.025em;
    font-weight: 800;
    margin-bottom: .5rem;
    font-family: 'Plus Jakarta Sans', sans-serif;
}
.hero-subtitle { color: var(--on-surface-variant); margin: 0; }

.content-grid {
    display: grid;
    grid-template-columns: minmax(0, 2fr) minmax(320px, 1fr);
    gap: 2rem;
    align-items: start;
}

/* ── CARDS ───────────────────────────────────────────────────────────────── */
.card-white {
    background: var(--surface-container-lowest);
    border-radius: .75rem;
    box-shadow: 0 .125rem .25rem rgba(0,0,0,.04);
}
.card-surface        { background: var(--surface-container);         border-radius: .75rem; }
.card-surface-low    { background: var(--surface-container-low);     border-radius: .75rem; }
.card-surface-high   { background: var(--surface-container-high);    border-radius: .75rem; }
.card-surface-highest{ background: var(--surface-container-highest); border-radius: .75rem; }
.card-dark {
    background: var(--inverse-surface);
    color: var(--inverse-on-surface);
    border-radius: .75rem;
    box-shadow: 0 .75rem 1.5rem rgba(0,0,0,.14);
}

/* ── ACTIVE ADMISSION CARD ───────────────────────────────────────────────── */
.active-admission-card {
    position: relative;
    overflow: hidden;
    border-left: 8px solid var(--primary);
    padding: 2rem;
}

/* ── STATUS CHIPS ────────────────────────────────────────────────────────── */
.status-chip {
    display: inline-block;
    background: var(--secondary-container);
    color: var(--on-secondary-container);
    padding: .25rem 1rem;
    border-radius: 999px;
    font-size: .75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .1em;
}
.status-chip-muted {
    display: inline-block;
    background: var(--surface-container-highest);
    color: var(--on-surface-variant);
    padding: .25rem .75rem;
    border-radius: 999px;
    font-size: .75rem;
    font-weight: 700;
}

/* ── MODE ENTREE BADGES ───────────────────────────────────────────────────── */
.mode-badge {
    display: inline-flex;
    align-items: center;
    gap: .3rem;
    padding: .2rem .75rem;
    border-radius: 999px;
    font-size: .7rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .08em;
}
.badge-urgence    { background: #fee2e2; color: #991b1b; }
.badge-planifiee  { background: #dbeafe; color: #1e40af; }
.badge-transfert  { background: #fef3c7; color: #92400e; }
.badge-default    { background: var(--surface-container-highest); color: var(--on-surface-variant); }

/* ── MISC ELEMENTS ───────────────────────────────────────────────────────── */
.icon-box {
    width: 96px; height: 96px;
    background: rgba(0,74,198,.05);
    border-radius: .75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 auto;
}
.icon-box .material-symbols-outlined { color: var(--primary); font-size: 2.25rem; }

.section-title { font-size: 1.25rem; font-weight: 700; margin-bottom: 0; font-family: 'Plus Jakarta Sans', sans-serif; }

.subhead {
    font-size: .75rem;
    color: var(--on-surface-variant);
    text-transform: uppercase;
    font-weight: 700;
    letter-spacing: -.02em;
    margin-bottom: .25rem;
}
.value-text {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-weight: 600;
    color: var(--on-surface);
    margin: 0;
}

.btn-primary-soft {
    background: var(--primary-container);
    color: var(--on-primary-container);
    border: none;
    padding: .7rem 1.5rem;
    border-radius: .75rem;
    font-size: .875rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: .5rem;
    cursor: pointer;
    transition: opacity .2s;
    text-decoration: none;
}
.btn-primary-soft:hover { opacity: .9; color: var(--on-primary-container); }

.btn-surface {
    background: var(--surface-container-high);
    color: var(--on-surface-variant);
    border: none;
    padding: .7rem 1.5rem;
    border-radius: .75rem;
    font-size: .875rem;
    font-weight: 700;
    cursor: pointer;
    transition: background-color .2s;
}
.btn-surface:hover { background: var(--surface-variant); color: var(--on-surface-variant); }

.mini-icon-btn {
    width: 40px; height: 40px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 0;
    background: transparent;
    border-radius: .5rem;
    cursor: pointer;
    transition: background-color .2s;
}
.mini-icon-btn:hover { background: var(--surface-container); }

/* ── ADMISSION HISTORY ROWS ─────────────────────────────────────────────── */
.admission-row {
    background: var(--surface-container-low);
    padding: 1.25rem;
    border-radius: .75rem;
    transition: all .2s;
    cursor: pointer;
}
.admission-row:hover { background: var(--surface-container-high); }
.admission-row .chevron { color: var(--outline); transition: color .2s; }
.admission-row:hover .chevron { color: var(--primary); }

.history-icon-box {
    background: var(--surface-container-highest);
    padding: .75rem;
    border-radius: .5rem;
    color: var(--on-surface-variant);
    display: inline-flex;
    flex: 0 0 auto;
}

/* ── SIDEBAR CARDS ───────────────────────────────────────────────────────── */
.insight-card {
    position: relative;
    overflow: hidden;
    padding: 1.5rem;
}
.insight-watermark {
    position: absolute;
    right: -2rem; bottom: -2rem;
    opacity: .1;
    font-size: 9rem;
    line-height: 1;
    pointer-events: none;
}
.insight-tag {
    display: inline-block;
    background: var(--tertiary);
    color: #fff;
    font-size: 10px;
    text-transform: uppercase;
    font-weight: 700;
    padding: .125rem .5rem;
    border-radius: 999px;
    margin-bottom: 1rem;
}
.progress-track {
    width: 100%; height: .5rem;
    background: rgba(255,255,255,.20);
    border-radius: 999px;
    overflow: hidden;
}
.progress-fill { width: 85%; height: 100%; background: var(--secondary); }

.kicker {
    font-size: .75rem;
    text-transform: uppercase;
    font-weight: 900;
    color: var(--on-surface-variant);
    letter-spacing: .15em;
    margin-bottom: 1rem;
}
.quick-link {
    display: flex;
    align-items: center;
    gap: .75rem;
    padding: .75rem;
    background: var(--surface-container-lowest);
    border-radius: .75rem;
    text-decoration: none;
    color: inherit;
    transition: all .2s;
}
.quick-link:hover { background: var(--primary-container); color: var(--on-primary-container); }
.quick-link .icon { color: var(--primary); transition: color .2s; }
.quick-link:hover .icon { color: var(--on-primary-container); }
.quick-link span:last-child { font-size: .875rem; font-weight: 600; }

.map-frame {
    height: 192px;
    position: relative;
    overflow: hidden;
    border-radius: .75rem;
    margin-bottom: 1rem;
}
.map-frame img { width: 100%; height: 100%; object-fit: cover; display: block; }
.map-overlay {
    position: absolute; inset: 0;
    background: rgba(0,74,198,.10);
    display: flex; align-items: center; justify-content: center;
}
.map-pin {
    background: #fff;
    padding: .75rem;
    border-radius: 999px;
    box-shadow: 0 .5rem 1rem rgba(0,0,0,.12);
    color: var(--primary);
    font-size: 2.25rem;
    display: inline-flex;
}

/* ── EMPTY / ERROR STATES ────────────────────────────────────────────────── */
.empty-state {
    padding: 3rem 2rem;
    text-align: center;
    color: var(--on-surface-variant);
}
.empty-state .material-symbols-outlined { font-size: 3rem; opacity: .35; }

.alert-db-error {
    background: var(--error-container);
    color: var(--on-error-container);
    border-radius: .75rem;
    padding: 1rem 1.5rem;
    font-size: .875rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: .75rem;
    margin-bottom: 1.5rem;
}

/* ── STATS BAR ───────────────────────────────────────────────────────────── */
.stats-bar {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
    margin-bottom: 2rem;
}
.stat-card {
    background: var(--surface-container-low);
    border-radius: .75rem;
    padding: 1.25rem 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
}
.stat-icon {
    width: 48px; height: 48px;
    background: var(--primary-fixed);
    border-radius: .75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 auto;
}
.stat-icon .material-symbols-outlined { color: var(--primary); font-size: 1.5rem; }
.stat-value {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 1.75rem;
    font-weight: 800;
    line-height: 1;
    color: var(--on-surface);
}
.stat-label { font-size: .75rem; color: var(--on-surface-variant); font-weight: 600; margin-top: .2rem; }

/* ── PAGINATION / SEARCH BAR ─────────────────────────────────────────────── */
.search-bar {
    display: flex;
    align-items: center;
    gap: .75rem;
    background: var(--surface-container-low);
    border-radius: .75rem;
    padding: .6rem 1rem;
    margin-bottom: 1.5rem;
    border: 1.5px solid transparent;
    transition: border-color .2s;
}
.search-bar:focus-within { border-color: var(--primary); }
.search-bar input {
    border: none;
    background: transparent;
    font-size: .875rem;
    color: var(--on-surface);
    outline: none;
    flex: 1;
    font-family: 'Inter', sans-serif;
}
.search-bar .material-symbols-outlined { color: var(--outline); font-size: 1.25rem; }

/* ── FAB ─────────────────────────────────────────────────────────────────── */
.fab-help {
    position: fixed;
    right: 2rem; bottom: 2rem;
    width: 64px; height: 64px;
    border-radius: 999px;
    border: 0;
    background: var(--primary);
    color: var(--on-primary);
    box-shadow: 0 1rem 2rem rgba(0,0,0,.18);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: transform .2s;
    z-index: 1040;
}
.fab-help:hover  { transform: scale(1.05); }
.fab-help:active { transform: scale(.95); }
.fab-help .material-symbols-outlined { font-size: 1.9rem; }

/* ── HELPERS ─────────────────────────────────────────────────────────────── */
.text-outline         { color: var(--outline); }
.text-primary-custom  { color: var(--primary); }
.text-surface-variant { color: var(--on-surface-variant); }

/* ── RESPONSIVE ──────────────────────────────────────────────────────────── */
@media (max-width: 991.98px) {
    .content-grid { grid-template-columns: 1fr; }
    .stats-bar { grid-template-columns: 1fr 1fr; }
    .page-wrap, .top-nav-inner { padding-left: 1rem; padding-right: 1rem; }
}
@media (max-width: 767.98px) {
    body { padding-top: 88px; }
    .hero-title { font-size: 2rem; }
    .active-admission-card { padding: 1.5rem; }
    .stats-bar { grid-template-columns: 1fr; }
}

/* ── NOTIFICATION BELL ───────────────────────────────────────────────────── */
.notif-btn-wrap {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.notif-badge {
    position: absolute;
    top: 2px; right: 2px;
    min-width: 17px; height: 17px;
    background: #ef4444;
    color: #fff;
    font-size: .6rem;
    font-weight: 700;
    border-radius: 999px;
    display: flex; align-items: center; justify-content: center;
    border: 2px solid #fff;
    line-height: 1;
    pointer-events: none;
}

/* ── NOTIFICATION PANEL ──────────────────────────────────────────────────── */
.notif-overlay {
    position: fixed; inset: 0;
    background: rgba(22, 26, 46, 0.35);
    z-index: 1200;
    opacity: 0; pointer-events: none;
    transition: opacity .22s ease;
}
.notif-overlay.open { opacity: 1; pointer-events: all; }

.notif-panel {
    position: fixed;
    top: 0; right: 0; bottom: 0;
    width: 380px; max-width: 100vw;
    background: var(--surface);
    box-shadow: -8px 0 40px rgba(0,0,0,.13);
    z-index: 1300;
    display: flex; flex-direction: column;
    transform: translateX(100%);
    transition: transform .26s cubic-bezier(.4,0,.2,1);
}
.notif-panel.open { transform: translateX(0); }

.notif-panel-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 20px 20px 16px;
    border-bottom: 1px solid var(--outline-variant);
    flex-shrink: 0;
}
.notif-panel-title {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-weight: 800; font-size: 1.05rem;
    color: var(--on-surface);
    display: flex; align-items: center; gap: 8px;
}
.notif-count-chip {
    background: #ef4444; color: #fff;
    font-size: .65rem; font-weight: 700;
    padding: 2px 7px; border-radius: 999px;
}
.notif-mark-all {
    font-size: .75rem; font-weight: 600;
    color: var(--primary); background: none; border: none;
    cursor: pointer; padding: 4px 8px; border-radius: 6px;
    transition: background .15s;
    font-family: inherit;
}
.notif-mark-all:hover { background: var(--primary-fixed); }

.notif-list {
    flex: 1; overflow-y: auto; padding: 12px 16px;
    display: flex; flex-direction: column; gap: 8px;
}
.notif-list::-webkit-scrollbar { width: 4px; }
.notif-list::-webkit-scrollbar-thumb { background: var(--outline-variant); border-radius: 4px; }

.notif-item {
    display: flex; gap: 12px; align-items: flex-start;
    padding: 13px 14px;
    border-radius: 12px;
    background: var(--surface-container-low);
    border: 1px solid transparent;
    cursor: pointer;
    transition: background .15s, border-color .15s;
    position: relative;
}
.notif-item:hover { background: var(--surface-container); }
.notif-item.unread {
    background: #eff6ff;
    border-color: #bfdbfe;
}
.notif-item.unread:hover { background: #dbeafe; }

.notif-item-icon {
    width: 38px; height: 38px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; font-size: 1.1rem;
}
.notif-icon-accepted { background: #dcfce7; color: #16a34a; }
.notif-icon-info     { background: var(--primary-fixed); color: var(--primary); }
.notif-icon-alerte   { background: #fef3c7; color: #d97706; }

.notif-item-body { flex: 1; min-width: 0; }
.notif-item-titre {
    font-weight: 700; font-size: .82rem;
    color: var(--on-surface); margin-bottom: 2px;
    font-family: 'Plus Jakarta Sans', sans-serif;
}
.notif-item-msg {
    font-size: .775rem; color: var(--on-surface-variant);
    line-height: 1.45; margin-bottom: 4px;
    display: -webkit-box; -webkit-line-clamp: 2;
    -webkit-box-orient: vertical; overflow: hidden;
}
.notif-item-date { font-size: .69rem; color: var(--outline); font-weight: 500; }
.notif-unread-dot {
    width: 8px; height: 8px; border-radius: 50%;
    background: #3b82f6; flex-shrink: 0; margin-top: 5px;
}

.notif-empty {
    text-align: center; padding: 48px 20px;
    color: var(--on-surface-variant); font-size: .85rem;
}
.notif-empty .material-symbols-outlined {
    font-size: 2.5rem; display: block; margin-bottom: 10px;
    opacity: .4;
}

.notif-panel-footer {
    padding: 14px 16px;
    border-top: 1px solid var(--outline-variant);
    flex-shrink: 0;
}
.notif-close-btn {
    width: 34px; height: 34px; border-radius: 50%;
    background: var(--surface-container);
    border: none; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    color: var(--on-surface-variant);
    transition: background .15s;
    font-family: inherit;
}
.notif-close-btn:hover { background: var(--surface-container-high); }
</style>
</head>
<body>

<!-- ═══════════════════════════ NAVBAR ══════════════════════════════════════ -->
<nav class="top-nav">
    <div class="top-nav-inner d-flex justify-content-between align-items-center w-100">
        <div class="d-flex align-items-center gap-4 gap-lg-5">
            <a href="#" class="brand font-headline">Clinical Architect</a>
            <div class="d-none d-md-flex gap-4 align-items-center">
                <a class="nav-link-custom" href="#">Mes Rendez-vous</a>
                <a class="nav-link-custom" href="#">Dossier Médical</a>
                <a class="nav-link-custom nav-link-active" href="#">Admissions</a>
                <a class="nav-link-custom" href="#">Paiements</a>
            </div>
        </div>
        <div class="d-flex align-items-center gap-3">
            <button class="btn-emergency">
                <span class="material-symbols-outlined" style="font-size:.95rem">emergency</span>
                <span>Appel d'urgence</span>
            </button>
            <div class="d-flex gap-1 align-items-center">
                <div class="notif-btn-wrap">
                    <span class="material-symbols-outlined icon-btn"
                          id="notifBell"
                          onclick="openNotifPanel()"
                          title="Notifications"
                          style="cursor:pointer">
                        <?= $nb_notifs_non_lues > 0 ? 'notifications_unread' : 'notifications' ?>
                    </span>
                    <?php if ($nb_notifs_non_lues > 0): ?>
                    <span class="notif-badge" id="notifBadge">
                        <?= $nb_notifs_non_lues > 9 ? '9+' : $nb_notifs_non_lues ?>
                    </span>
                    <?php else: ?>
                    <span class="notif-badge" id="notifBadge" style="display:none">0</span>
                    <?php endif; ?>
                </div>
                <span class="material-symbols-outlined icon-btn">settings</span>
            </div>
            <img class="avatar"
                 src="https://lh3.googleusercontent.com/aida-public/AB6AXuCu3NaKTx8SdiWw0ERiMJzBvA6cfaYLOCYr3GvfDvgAlky48HTp0UPyr0xxWkTGpBGvHyw4KjSb2zfYvitD0yZFoIRYLyl8U2V4woCMr7NYuDk_RDrLLMkSef5kDrvQOPsKRHJkgPEepmDY99ar47V1a0_6Ir3u8Rpz39l7k8hDa4RRml_5wIGMEvSRpuZE5ZbP186aOGjQinJGlSMZFj4HkmiQywum1e_uABMsRfMLJ2ZvnUUAmQ8wzgk5DIYHppnvRKtryOI7w7S0"
                 alt="Avatar">
        </div>
    </div>
</nav>

<!-- ═══════════════════════════ MAIN ════════════════════════════════════════ -->
<main class="page-wrap">

    <!-- Page header -->
    <header class="mb-4 pb-1">
        <h1 class="hero-title font-headline">Mes Admissions</h1>
        <p class="hero-subtitle">Gérez vos séjours hospitaliers et accédez aux détails de vos admissions en temps réel.</p>
    </header>

    <!-- DB Error banner -->
    <?php if (!empty($db_error)): ?>
    <div class="alert-db-error">
        <span class="material-symbols-outlined">error</span>
        <?= $db_error ?>
    </div>
    <?php endif; ?>

    <!-- Stats bar -->
    <?php
        $total     = count($admissions ?? []) + ($active_admission ? 1 : 0);
        $completed = count($history_admissions ?? []);
        $active_count = $active_admission ? 1 : 0;
    ?>
    <div class="stats-bar">
        <div class="stat-card">
            <div class="stat-icon"><span class="material-symbols-outlined">folder_open</span></div>
            <div>
                <div class="stat-value"><?= $total ?></div>
                <div class="stat-label">Total Admissions</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><span class="material-symbols-outlined fill-icon">radio_button_checked</span></div>
            <div>
                <div class="stat-value"><?= $active_count ?></div>
                <div class="stat-label">Active</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><span class="material-symbols-outlined">check_circle</span></div>
            <div>
                <div class="stat-value"><?= $completed ?></div>
                <div class="stat-label">Terminées</div>
            </div>
        </div>
    </div>

    <!-- ── CONTENT GRID ──────────────────────────────────────────────────── -->
    <div class="content-grid">

        <!-- LEFT COLUMN -->
        <section>

            <!-- ── ACTIVE ADMISSION ──────────────────────────────────────── -->
            <?php if ($active_admission): ?>
            <div class="card-white active-admission-card">
                <div class="position-absolute top-0 end-0 p-4">
                    <span class="status-chip">Active</span>
                </div>

                <div class="d-flex flex-column flex-md-row gap-4 gap-md-5 align-items-start">
                    <div class="icon-box">
                        <span class="material-symbols-outlined fill-icon">meeting_room</span>
                    </div>

                    <div class="flex-grow-1 w-100">
                        <div class="mb-4 pb-1">
                            <h2 class="font-headline fw-bold mb-1" style="font-size:1.5rem">Admission en cours</h2>
                            <p class="text-primary-custom fw-bold mb-0" style="font-size:.875rem">
                                ID: #ADM-<?= htmlspecialchars(str_pad($active_admission['id_admission'], 5, '0', STR_PAD_LEFT)) ?>
                            </p>
                        </div>

                        <div class="row row-cols-2 row-cols-md-4 g-4">
                            <div>
                                <p class="subhead">Date d'arrivée</p>
                                <p class="value-text"><?= formatDateFR($active_admission['date_arrive_relle']) ?></p>
                            </div>
                            <div>
                                <p class="subhead">Salle assignée</p>
                                <p class="value-text">Salle <?= htmlspecialchars($active_admission['salle_numero']) ?></p>
                            </div>
                            <div>
                                <p class="subhead">Mode d'entrée</p>
                                <p class="value-text">
                                    <span class="mode-badge <?= modeEntreeBadge($active_admission['mode_entree']) ?>">
                                        <?= htmlspecialchars($active_admission['mode_entree']) ?>
                                    </span>
                                </p>
                            </div>
                            <div>
                                <p class="subhead">Statut Salle</p>
                                <p class="value-text"><?= htmlspecialchars(ucfirst($active_admission['salle_statut'])) ?></p>
                            </div>
                        </div>

                        <div class="mt-4 pt-2 d-flex flex-wrap gap-3">
                            <a href="admission_detail_controller.php?id=<?= $active_admission['id_admission'] ?>"
                               class="btn-primary-soft">
                                <span class="material-symbols-outlined" style="font-size:1.125rem">info</span>
                                <span>Détails complets</span>
                            </a>
                            <button class="btn-surface">Contacter l'unité</button>
                        </div>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="card-white p-4 empty-state">
                <span class="material-symbols-outlined d-block mb-3">bed</span>
                <p class="fw-600 mb-1" style="font-weight:600">Aucune admission active</p>
                <p class="mb-0" style="font-size:.875rem">Vous n'avez pas d'admission hospitalière en cours.</p>
            </div>
            <?php endif; ?>

            <!-- ── ADMISSION HISTORY ─────────────────────────────────────── -->
            <div class="mt-5 pt-1">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h3 class="section-title font-headline">Historique des Admissions</h3>
                    <div class="d-flex gap-1">
                        <button class="mini-icon-btn" title="Filtrer" onclick="toggleFilter()">
                            <span class="material-symbols-outlined">filter_list</span>
                        </button>
                        <button class="mini-icon-btn" title="Trier" onclick="sortRows()">
                            <span class="material-symbols-outlined">sort</span>
                        </button>
                    </div>
                </div>

                <!-- Search bar -->
                <div class="search-bar">
                    <span class="material-symbols-outlined">search</span>
                    <input type="text" id="searchInput" placeholder="Rechercher une admission..." oninput="filterRows()">
                </div>

                <div id="admissionList" class="d-flex flex-column gap-3">
                    <?php if (!empty($history_admissions)): ?>
                        <?php foreach ($history_admissions as $adm): ?>
                        <div class="admission-row"
                             data-id="<?= $adm['id_admission'] ?>"
                             data-date="<?= $adm['date_arrive_relle'] ?>"
                             data-mode="<?= htmlspecialchars(strtolower($adm['mode_entree'])) ?>"
                             onclick="location.href='admission_detail_controller.php?id=<?= $adm['id_admission'] ?>'">
                            <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap flex-md-nowrap">

                                <div class="d-flex align-items-center gap-4">
                                    <div class="history-icon-box">
                                        <span class="material-symbols-outlined">history</span>
                                    </div>
                                    <div>
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <h4 class="font-headline fw-bold mb-0" style="font-size:1rem">
                                                Admission #<?= htmlspecialchars(str_pad($adm['id_admission'], 5, '0', STR_PAD_LEFT)) ?>
                                            </h4>
                                            <span class="mode-badge <?= modeEntreeBadge($adm['mode_entree']) ?>">
                                                <?= htmlspecialchars($adm['mode_entree']) ?>
                                            </span>
                                        </div>
                                        <p class="text-surface-variant mb-0" style="font-size:.875rem">
                                            Salle <?= htmlspecialchars($adm['salle_numero']) ?>
                                            <?php if ($adm['id_ticket']): ?>
                                                &nbsp;•&nbsp; Ticket #<?= htmlspecialchars($adm['id_ticket']) ?>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </div>

                                <div class="d-none d-md-block text-end">
                                    <p class="subhead mb-1">Date d'arrivée</p>
                                    <p class="font-headline fw-semibold mb-0" style="font-size:.875rem">
                                        <?= formatDateFR($adm['date_arrive_relle']) ?>
                                    </p>
                                </div>

                                <div class="d-flex align-items-center gap-3">
                                    <span class="status-chip-muted">Terminée</span>
                                    <span class="material-symbols-outlined chevron">chevron_right</span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <span class="material-symbols-outlined d-block mb-3">history</span>
                            <p class="fw-600 mb-0" style="font-weight:600">Aucune admission dans l'historique</p>
                        </div>
                    <?php endif; ?>

                    <!-- No results message (hidden by default) -->
                    <div id="noResults" class="empty-state" style="display:none">
                        <span class="material-symbols-outlined d-block mb-3">search_off</span>
                        <p class="fw-600 mb-0" style="font-weight:600">Aucun résultat trouvé</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- RIGHT SIDEBAR -->
        <aside class="d-flex flex-column gap-4">

            <!-- Digital Twin Insight -->
            <div class="card-dark insight-card">
                <div class="insight-watermark material-symbols-outlined">monitoring</div>
                <div class="position-relative" style="z-index:1">
                    <span class="insight-tag">Digital Twin Insight</span>
                    <h4 class="font-headline fw-bold mb-2" style="font-size:1.25rem">Suivi de récupération</h4>
                    <p class="mb-4" style="font-size:.875rem; opacity:.8; line-height:1.6">
                        Votre admission actuelle montre une progression de 85% vers vos objectifs de sortie. Continuez vos exercices de rééducation.
                    </p>
                    <div class="progress-track">
                        <div class="progress-fill"></div>
                    </div>
                    <div class="d-flex justify-content-between mt-2"
                         style="font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.14em; opacity:.6">
                        <span>Admission</span>
                        <span>Sortie Prévue</span>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card-surface p-4">
                <h4 class="kicker mb-3">Actions Rapides</h4>
                <div class="d-flex flex-column gap-3">
                    <a class="quick-link" href="#">
                        <span class="material-symbols-outlined icon">file_download</span>
                        <span>Télécharger le livret d'accueil</span>
                    </a>
                    <a class="quick-link" href="#">
                        <span class="material-symbols-outlined icon">person_add</span>
                        <span>Ajouter une personne de confiance</span>
                    </a>
                    <a class="quick-link" href="#">
                        <span class="material-symbols-outlined icon">restaurant</span>
                        <span>Gérer mes préférences repas</span>
                    </a>
                </div>
            </div>

            <!-- Location -->
            <div class="card-surface-highest p-4">
                <h4 class="kicker mb-3">Localisation</h4>
                <div class="map-frame">
                    <img src="https://lh3.googleusercontent.com/aida-public/AB6AXuAB1nlKjV601QaodvMFn8JnuJLe99_YADG3mtCCOYHN5_iUOjUGw9ZgzRBl25cHbC2e-WB_fyHM817uPBdXpPlxICtCgIFyC53mUcbN6Bgv64qqCTNy6LCWO_jOVGx6uJ6R0rZbnRjUSsr9e4kE8gviKPp04UA9BxDkqAqePTERReecuI67aiKtqhorKq8idfowy-8HXMToKOCY1WjuwYmFi7VqSP5OJtHZbQRwy-clvJxsK3iCuI6Hndv-pfWanssg1KNWsPW2HAd_" alt="Plan Hôpital">
                    <div class="map-overlay">
                        <span class="material-symbols-outlined map-pin">location_on</span>
                    </div>
                </div>
                <div class="d-flex align-items-start gap-3">
                    <span class="material-symbols-outlined text-primary-custom">navigation</span>
                    <div>
                        <p class="fw-bold mb-1" style="font-size:.875rem; color:var(--on-surface)">
                            <?php if ($active_admission): ?>
                                Salle <?= htmlspecialchars($active_admission['salle_numero']) ?>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </p>
                        <p class="mb-0" style="font-size:.75rem; color:var(--on-surface-variant)">
                            Suivez le balisage bleu à partir de l'accueil central.
                        </p>
                    </div>
                </div>
            </div>

        </aside>
    </div>
</main>

<!-- FAB -->
<button class="fab-help" aria-label="Aide admission">
    <span class="material-symbols-outlined">question_answer</span>
</button>

<!-- ═══════════════════ NOTIFICATION PANEL ════════════════════════════════ -->

<!-- Overlay (fond sombre) -->
<div class="notif-overlay" id="notifOverlay" onclick="closeNotifPanel()"></div>

<!-- Panel slide-in -->
<div class="notif-panel" id="notifPanel">

    <!-- Header -->
    <div class="notif-panel-header">
        <div class="notif-panel-title">
            <span class="material-symbols-outlined fill-icon" style="color:var(--primary);font-size:1.15rem">notifications</span>
            Notifications
            <?php if ($nb_notifs_non_lues > 0): ?>
            <span class="notif-count-chip" id="notifCountChip">
                <?= $nb_notifs_non_lues ?> non lue<?= $nb_notifs_non_lues > 1 ? 's' : '' ?>
            </span>
            <?php else: ?>
            <span class="notif-count-chip" id="notifCountChip" style="display:none"></span>
            <?php endif; ?>
        </div>
        <div class="d-flex align-items-center gap-2">
            <?php if (!empty($notifications)): ?>
            <button class="notif-mark-all" onclick="toutMarquerLu()">Tout marquer lu</button>
            <?php endif; ?>
            <button class="notif-close-btn" onclick="closeNotifPanel()" title="Fermer">
                <span class="material-symbols-outlined" style="font-size:1.1rem">close</span>
            </button>
        </div>
    </div>

    <!-- Liste des notifications -->
    <div class="notif-list" id="notifList">
        <?php if (empty($notifications)): ?>
        <div class="notif-empty">
            <span class="material-symbols-outlined">notifications_off</span>
            Aucune notification pour le moment.
        </div>
        <?php else: ?>
            <?php foreach ($notifications as $notif): ?>
            <?php
                $isUnread  = !$notif['lu'];
                $isAccept  = $notif['type'] === 'ADMISSION_ACCEPTEE';
                $isAlerte  = $notif['type'] === 'ALERTE';
                $iconClass = $isAccept ? 'notif-icon-accepted' : ($isAlerte ? 'notif-icon-alerte' : 'notif-icon-info');
                $iconName  = $isAccept ? 'check_circle'        : ($isAlerte ? 'warning'           : 'info');

                // Format date relative
                $dateObj  = new DateTime($notif['date_creation']);
                $now      = new DateTime();
                $diff     = $now->diff($dateObj);
                if ($diff->days === 0 && $diff->h === 0)      $dateStr = 'Il y a ' . max(1, $diff->i) . ' min';
                elseif ($diff->days === 0)                     $dateStr = 'Il y a ' . $diff->h . 'h';
                elseif ($diff->days === 1)                     $dateStr = 'Hier';
                else                                           $dateStr = $dateObj->format('d/m/Y');
            ?>
            <div class="notif-item <?= $isUnread ? 'unread' : '' ?>"
                 id="notif-<?= $notif['id_notif'] ?>"
                 onclick="marquerLu(<?= $notif['id_notif'] ?>, this)">
                <div class="notif-item-icon <?= $iconClass ?>">
                    <span class="material-symbols-outlined fill-icon" style="font-size:1.1rem"><?= $iconName ?></span>
                </div>
                <div class="notif-item-body">
                    <div class="notif-item-titre"><?= htmlspecialchars($notif['titre']) ?></div>
                    <div class="notif-item-msg"><?= htmlspecialchars($notif['message']) ?></div>
                    <div class="notif-item-date"><?= $dateStr ?></div>
                </div>
                <?php if ($isUnread): ?>
                <div class="notif-unread-dot" id="dot-<?= $notif['id_notif'] ?>"></div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <div class="notif-panel-footer">
        <p style="font-size:.72rem;color:var(--on-surface-variant);margin:0;text-align:center">
            <?= count($notifications) ?> notification<?= count($notifications) > 1 ? 's' : '' ?> au total
        </p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ── Variables PHP → JS ────────────────────────────────────────────────────
const NOTIF_API  = '../../controleur/backoffice/valider_ticket.php';
const ID_PATIENT = <?= json_encode($id_patient_courant) ?>;
let   nbNonLues  = <?= (int)$nb_notifs_non_lues ?>;

// ── OUVRIR / FERMER ───────────────────────────────────────────────────────
function openNotifPanel() {
    document.getElementById('notifOverlay').classList.add('open');
    document.getElementById('notifPanel').classList.add('open');
    document.body.style.overflow = 'hidden';
}
function closeNotifPanel() {
    document.getElementById('notifOverlay').classList.remove('open');
    document.getElementById('notifPanel').classList.remove('open');
    document.body.style.overflow = '';
}
// Fermer avec Escape
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeNotifPanel(); });

// ── MARQUER UNE NOTIF LUE ────────────────────────────────────────────────
async function marquerLu(idNotif, el) {
    if (!el.classList.contains('unread')) return; // déjà lue

    try {
        const fd = new FormData();
        fd.append('action',   'mark_read');
        fd.append('id_notif', idNotif);
        await fetch(NOTIF_API, { method: 'POST', body: fd });
    } catch(e) {}

    el.classList.remove('unread');
    const dot = document.getElementById('dot-' + idNotif);
    if (dot) dot.remove();

    nbNonLues = Math.max(0, nbNonLues - 1);
    mettreAJourBadge();
}

// ── TOUT MARQUER LU ──────────────────────────────────────────────────────
async function toutMarquerLu() {
    if (!ID_PATIENT) return;
    try {
        const fd = new FormData();
        fd.append('action',   'mark_read');
        fd.append('id_notif', 'all');
        fd.append('id_user',  ID_PATIENT);
        await fetch(NOTIF_API, { method: 'POST', body: fd });
    } catch(e) {}

    document.querySelectorAll('.notif-item.unread').forEach(el => el.classList.remove('unread'));
    document.querySelectorAll('.notif-unread-dot').forEach(el => el.remove());
    nbNonLues = 0;
    mettreAJourBadge();
}

// ── METTRE À JOUR LE BADGE ────────────────────────────────────────────────
function mettreAJourBadge() {
    const badge = document.getElementById('notifBadge');
    const chip  = document.getElementById('notifCountChip');
    const bell  = document.getElementById('notifBell');

    if (nbNonLues > 0) {
        badge.textContent = nbNonLues > 9 ? '9+' : nbNonLues;
        badge.style.display = 'flex';
        chip.textContent    = nbNonLues + ' non lue' + (nbNonLues > 1 ? 's' : '');
        chip.style.display  = 'inline-flex';
        bell.textContent    = 'notifications_unread';
    } else {
        badge.style.display = 'none';
        chip.style.display  = 'none';
        bell.textContent    = 'notifications';
    }
}

// ── Client-side search filter ───────────────────────────────────────────────
function filterRows() {
    const q = document.getElementById('searchInput').value.toLowerCase().trim();
    const rows = document.querySelectorAll('#admissionList .admission-row');
    let visible = 0;

    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        const match = !q || text.includes(q);
        row.style.display = match ? '' : 'none';
        if (match) visible++;
    });

    document.getElementById('noResults').style.display = visible === 0 ? '' : 'none';
}

// ── Sort toggle (date ascending / descending) ───────────────────────────────
let sortAsc = false;
function sortRows() {
    sortAsc = !sortAsc;
    const list  = document.getElementById('admissionList');
    const rows  = [...list.querySelectorAll('.admission-row')];

    rows.sort((a, b) => {
        const da = a.dataset.date, db = b.dataset.date;
        return sortAsc ? da.localeCompare(db) : db.localeCompare(da);
    });

    rows.forEach(r => list.insertBefore(r, document.getElementById('noResults')));
}

// ── Filter toggle (mode_entree) ─────────────────────────────────────────────
const modes     = ['all', 'urgence', 'planifiée', 'transfert'];
let   modeIndex = 0;

function toggleFilter() {
    modeIndex = (modeIndex + 1) % modes.length;
    const current = modes[modeIndex];
    const rows = document.querySelectorAll('#admissionList .admission-row');
    let visible = 0;

    rows.forEach(row => {
        const match = current === 'all' || row.dataset.mode === current;
        row.style.display = match ? '' : 'none';
        if (match) visible++;
    });

    document.getElementById('noResults').style.display = visible === 0 ? '' : 'none';

    const btn = document.querySelector('[onclick="toggleFilter()"]');
    btn.style.background = current !== 'all' ? 'var(--primary-fixed)' : '';
    btn.title = current !== 'all' ? `Filtre : ${current}` : 'Filtrer';
}
</script>
</body>
</html>