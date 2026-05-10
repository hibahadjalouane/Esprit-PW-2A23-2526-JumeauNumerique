<?php
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Sat, 01 Jan 2000 00:00:00 GMT');

require_once __DIR__ . '/../../../inc_session.php';
checkSession([1]);

$user = getCurrentUser();

$currentPatientId = (int)($user['id_user'] ?? $user['id'] ?? 0);
$currentPatientName = $user['prenom'] ?? $user['username'] ?? 'Patient';

if ($currentPatientId <= 0) {
    die('Erreur session: ID patient introuvable.');
}
?>

<!DOCTYPE html>
<html lang="fr" data-theme="light">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Aurea — Espace Patient</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;500;600;700;800&family=Inter:wght@300;400;500;600&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

  <script src="icons.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/canvas-confetti/1.9.3/confetti.browser.min.js"
          onerror="window.confetti=function(){};"></script>

  <style>
    /* ═══════════════════════════════════════════════════════════
       DESIGN TOKENS — BLUE + TEAL (matching Code B palette)
       ═══════════════════════════════════════════════════════════ */
    :root {
      --primary-blue:   #2563EB;
      --dark-teal:      #14B8A6;
      --slate-gray:     #1F2937;
      --soft-blue:      #8B5CF6;
      --bg-main:        #f8f9ff;
    }

    [data-theme="light"] {
      --bg:           #f8f9ff;
      --bg-mesh-1:    #dbeafe;
      --bg-mesh-2:    #e0f2fe;
      --bg-mesh-3:    #ede9fe;
      --surface:      #ffffff;
      --surface-2:    #f1f5f9;
      --surface-3:    #e2e8f0;
      --glass:        rgba(255, 255, 255, 0.85);
      --glass-border: rgba(37, 99, 235, 0.10);
      --ink:          #1F2937;
      --ink-2:        #374151;
      --cobalt:       #2563EB;
      --cobalt-2:     #3b82f6;
      --cobalt-3:     #60a5fa;
      --ice:          #14B8A6;
      --ice-2:        #99f6e4;
      --ice-glow:     rgba(37, 99, 235, 0.18);
      --gold:         #d97706;
      --success:      #059669;
      --success-bg:   #d1fae5;
      --warning:      #b45309;
      --warning-bg:   #fef3c7;
      --danger:       #dc2626;
      --danger-bg:    #fee2e2;
      --info:         #2563EB;
      --info-bg:      #dbeafe;
      --text:         #1F2937;
      --text-2:       #4B5563;
      --text-3:       #9CA3AF;
      --text-on-dark: #f9fafb;
      --line:         rgba(37, 99, 235, 0.08);
      --line-strong:  rgba(37, 99, 235, 0.16);
      --heat-0:       #f8f9ff;
      --heat-1:       #dbeafe;
      --heat-2:       #93c5fd;
      --heat-3:       #3b82f6;
      --heat-4:       #1d4ed8;
      --heat-full:    #fee2e2;
      --shadow-xs:    0 1px 2px rgba(37, 99, 235, 0.05);
      --shadow-sm:    0 2px 6px rgba(37, 99, 235, 0.06), 0 1px 2px rgba(37, 99, 235, 0.04);
      --shadow-md:    0 8px 24px rgba(37, 99, 235, 0.10), 0 2px 6px rgba(37, 99, 235, 0.06);
      --shadow-lg:    0 24px 60px rgba(37, 99, 235, 0.10), 0 8px 20px rgba(37, 99, 235, 0.06);
      --shadow-xl:    0 40px 100px rgba(37, 99, 235, 0.12), 0 12px 30px rgba(37, 99, 235, 0.08);
      --shadow-glow:  0 8px 30px rgba(37, 99, 235, 0.25);
      --shadow-ice:   0 8px 30px rgba(20, 184, 166, 0.25);
      --shadow-inset: inset 0 1px 0 rgba(255, 255, 255, 0.8);
    }

    [data-theme="dark"] {
      --bg:           #0f172a;
      --bg-mesh-1:    #1e3a5f;
      --bg-mesh-2:    #0f2a4a;
      --bg-mesh-3:    #1a1033;
      --surface:      #1e293b;
      --surface-2:    #0f172a;
      --surface-3:    #334155;
      --glass:        rgba(15, 23, 42, 0.80);
      --glass-border: rgba(96, 165, 250, 0.14);
      --ink:          #f1f5f9;
      --ink-2:        #cbd5e1;
      --cobalt:       #60a5fa;
      --cobalt-2:     #93c5fd;
      --cobalt-3:     #bfdbfe;
      --ice:          #2dd4bf;
      --ice-2:        #99f6e4;
      --ice-glow:     rgba(96, 165, 250, 0.25);
      --gold:         #fbbf24;
      --success:      #34d399;
      --success-bg:   rgba(52, 211, 153, 0.16);
      --warning:      #fbbf24;
      --warning-bg:   rgba(251, 191, 36, 0.16);
      --danger:       #f87171;
      --danger-bg:    rgba(248, 113, 113, 0.16);
      --info:         #60a5fa;
      --info-bg:      rgba(96, 165, 250, 0.16);
      --text:         #f1f5f9;
      --text-2:       #94a3b8;
      --text-3:       #64748b;
      --text-on-dark: #f1f5f9;
      --line:         rgba(96, 165, 250, 0.10);
      --line-strong:  rgba(96, 165, 250, 0.20);
      --heat-0:       #1e293b;
      --heat-1:       #1e3a5f;
      --heat-2:       #1d4ed8;
      --heat-3:       #3b82f6;
      --heat-4:       #60a5fa;
      --heat-full:    rgba(248, 113, 113, 0.20);
      --shadow-xs:    0 1px 2px rgba(0, 0, 0, 0.40);
      --shadow-sm:    0 2px 6px rgba(0, 0, 0, 0.45);
      --shadow-md:    0 8px 24px rgba(0, 0, 0, 0.50);
      --shadow-lg:    0 24px 60px rgba(0, 0, 0, 0.55);
      --shadow-xl:    0 40px 100px rgba(0, 0, 0, 0.65);
      --shadow-glow:  0 8px 30px rgba(96, 165, 250, 0.35);
      --shadow-ice:   0 8px 30px rgba(45, 212, 191, 0.25);
      --shadow-inset: inset 0 1px 0 rgba(255, 255, 255, 0.06);
    }

    *, *::before, *::after { box-sizing: border-box; }
    html, body { margin: 0; padding: 0; }

    body {
      font-family: 'Manrope', 'Inter', -apple-system, BlinkMacSystemFont, system-ui, sans-serif;
      background: var(--bg);
      color: var(--text);
      -webkit-font-smoothing: antialiased;
      min-height: 100vh;
      overflow-x: hidden;
      transition: background-color .4s ease, color .4s ease;
      font-size: 14px;
    }

    /* Subtle background gradient mesh */
    body::before {
      content: '';
      position: fixed;
      inset: 0;
      pointer-events: none;
      z-index: 0;
      background:
        radial-gradient(ellipse 60% 40% at 5% 5%,    var(--bg-mesh-1), transparent 60%),
        radial-gradient(ellipse 50% 35% at 95% 0%,   var(--bg-mesh-2), transparent 55%),
        radial-gradient(ellipse 70% 40% at 50% 100%, var(--bg-mesh-3), transparent 65%);
      opacity: 0.45;
      transition: background .4s ease;
    }

    main, nav, footer { position: relative; z-index: 1; }

    h1, h2, h3, h4 {
      font-family: 'Manrope', sans-serif;
      font-weight: 800;
      letter-spacing: -0.02em;
      line-height: 1.1;
    }

    .mono { font-family: 'JetBrains Mono', 'SF Mono', Menlo, Consolas, monospace; }

    button { font-family: inherit; cursor: pointer; }
    button:focus-visible, a:focus-visible, input:focus-visible, select:focus-visible, textarea:focus-visible {
      outline: 2px solid var(--cobalt);
      outline-offset: 2px;
      border-radius: 4px;
    }

    .container {
      max-width: 1480px;
      margin: 0 auto;
      padding: 0 clamp(1rem, 2.5vw, 2rem);
    }

    ::-webkit-scrollbar { width: 6px; height: 6px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: var(--line-strong); border-radius: 999px; }

    /* ── NAV (Code B style) ── */
    .nav {
      position: sticky; top: 0; z-index: 100;
      background: rgba(255, 255, 255, 0.92);
      backdrop-filter: blur(16px) saturate(180%);
      -webkit-backdrop-filter: blur(16px) saturate(180%);
      border-bottom: 1px solid rgba(0,0,0,0.05);
      box-shadow: none;
      transition: box-shadow .3s;
    }
    [data-theme="dark"] .nav {
      background: rgba(15, 23, 42, 0.90);
      border-bottom: 1px solid var(--glass-border);
    }
    .nav-inner { display: flex; align-items: center; justify-content: space-between; height: 68px; }

    .brand {
      display: flex; align-items: center; gap: 0.6rem;
      font-family: 'Manrope', sans-serif;
      font-size: 1.2rem; font-weight: 800;
      letter-spacing: -0.02em; color: var(--ink);
      text-decoration: none;
    }
    .brand-mark {
      width: 38px; height: 38px;
      border-radius: 12px;
      background: linear-gradient(135deg, var(--cobalt), var(--ice));
      display: grid; place-items: center;
      color: white;
      box-shadow: var(--shadow-glow);
    }
    .brand-mark svg { position: relative; z-index: 1; }

    .nav-links {
      display: none; gap: 0;
      list-style: none; padding: 0; margin: 0;
    }
    @media (min-width: 900px) { .nav-links { display: flex; } }
    .nav-link {
      padding: 0.45rem 1rem; border-radius: 10px;
      color: var(--text-2); text-decoration: none;
      font-size: 0.875rem; font-weight: 500;
      transition: all .2s;
    }
    .nav-link:hover { color: var(--cobalt); }
    .nav-link.active {
      color: var(--cobalt);
      background: rgba(37, 99, 235, 0.08);
      font-weight: 600;
    }

    .nav-actions { display: flex; align-items: center; gap: 0.5rem; }

    .icon-btn {
      width: 36px; height: 36px;
      display: grid; place-items: center;
      border: 1.5px solid var(--line-strong);
      border-radius: 10px;
      background: var(--surface);
      color: var(--text-2);
      transition: all .15s;
      position: relative;
    }
    .icon-btn:hover {
      color: var(--cobalt); border-color: var(--cobalt);
      transform: translateY(-1px); box-shadow: var(--shadow-sm);
    }
    .icon-btn .dot {
      position: absolute; top: 6px; right: 6px;
      width: 7px; height: 7px;
      background: var(--cobalt); border-radius: 999px;
      border: 2px solid var(--surface);
      animation: pulse 2s infinite;
    }
    .theme-toggle .sun, .theme-toggle .moon {
      position: absolute; inset: 0;
      display: grid; place-items: center;
      transition: opacity .3s, transform .3s;
    }
    [data-theme="light"] .theme-toggle .moon { opacity: 0; transform: rotate(90deg); }
    [data-theme="dark"]  .theme-toggle .sun  { opacity: 0; transform: rotate(-90deg); }

    /* Welcome pill — Code B style */
    .avatar-chip {
      display: flex; align-items: center; gap: 0.5rem;
      padding: 4px 14px 4px 4px;
      background: rgba(37, 99, 235, 0.07);
      border: 1px solid rgba(37, 99, 235, 0.15);
      border-radius: 50px;
    }
    .avatar {
      width: 28px; height: 28px; border-radius: 50%;
      background: linear-gradient(135deg, var(--cobalt), var(--ice));
      color: white; display: grid; place-items: center;
      font-weight: 700; font-size: 0.7rem;
    }
    .avatar-chip span {
      font-size: 0.82rem; font-weight: 600;
      color: var(--cobalt);
    }

    /* ── HERO ── */
    .hero { padding: clamp(1.5rem, 4vw, 2.5rem) 0 clamp(1rem, 2.5vw, 1.75rem); }
    .hero-grid {
      display: grid; grid-template-columns: 1fr; gap: 1.5rem; align-items: end;
    }
    @media (min-width: 1024px) {
      .hero-grid { grid-template-columns: 1.2fr 1fr; gap: 2rem; }
    }

    /* Badge — Code B inspired */
    .eyebrow {
      display: inline-flex; align-items: center; gap: 0.5rem;
      padding: 6px 16px;
      border-radius: 50px;
      background: rgba(20, 184, 166, 0.10);
      border: 1px solid rgba(20, 184, 166, 0.20);
      font-size: 0.72rem; font-weight: 800;
      letter-spacing: 0.07em; color: var(--dark-teal, #14B8A6);
      text-transform: uppercase;
    }
    .eyebrow .pulse {
      width: 7px; height: 7px;
      background: var(--dark-teal, #14B8A6); border-radius: 999px;
      animation: pulse 2s infinite;
    }
    @keyframes pulse {
      0%   { box-shadow: 0 0 0 0 rgba(20,184,166,0.4); }
      70%  { box-shadow: 0 0 0 10px transparent; }
      100% { box-shadow: 0 0 0 0 transparent; }
    }

    .hero-title {
      font-size: clamp(2.4rem, 5.5vw, 4rem);
      margin: 1rem 0;
      color: var(--ink);
      font-weight: 800;
      line-height: 1.1;
    }
    .hero-title em {
      font-style: italic;
      color: var(--cobalt);
      -webkit-text-fill-color: var(--cobalt);
    }
    .hero-subtitle {
      font-size: 1rem;
      color: var(--text-2);
      max-width: 30rem;
      line-height: 1.65;
      margin: 0;
      font-weight: 400;
    }

    .hero-panel {
      background: var(--surface);
      border: 1px solid rgba(0,0,0,0.04);
      border-radius: 24px;
      padding: 1.5rem;
      box-shadow: 0 20px 40px rgba(0,0,0,0.06);
      position: relative; overflow: hidden;
    }
    .hero-panel::before {
      content: ''; position: absolute; top: -40%; right: -30%;
      width: 65%; height: 200%;
      background: radial-gradient(ellipse, rgba(37,99,235,0.07), transparent 65%);
      pointer-events: none;
    }
    .hero-panel-head {
      display: flex; justify-content: space-between; align-items: flex-start;
      margin-bottom: 1.25rem;
      position: relative; z-index: 1;
    }
    .hero-panel-title {
      font-size: 0.68rem; font-weight: 700;
      letter-spacing: 0.10em; text-transform: uppercase;
      color: var(--text-3); margin: 0 0 0.2rem;
    }
    .hero-panel-name {
      font-family: 'Manrope', sans-serif;
      font-size: 1.3rem; font-weight: 800;
      margin: 0; color: var(--ink);
    }
    .badge-vital {
      display: inline-flex; align-items: center; gap: 0.35rem;
      padding: 0.3rem 0.65rem;
      background: var(--success-bg); color: var(--success);
      border-radius: 999px;
      font-size: 0.65rem; font-weight: 700;
      letter-spacing: 0.04em; text-transform: uppercase;
    }

    .stats {
      display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.75rem;
      position: relative; z-index: 1;
    }
    .stat {
      padding: 0.85rem;
      background: var(--bg-main, #f8f9ff);
      border: 1px solid rgba(0,0,0,0.03);
      border-radius: 16px;
      transition: all .2s;
    }
    .stat:hover { transform: translateY(-2px); box-shadow: var(--shadow-sm); }
    .stat-label {
      font-size: 0.62rem; font-weight: 700;
      color: var(--text-3);
      text-transform: uppercase; letter-spacing: 0.08em;
      display: block; margin-bottom: 0.15rem;
    }
    .stat-value {
      font-family: 'Manrope', sans-serif;
      font-size: 1.75rem; font-weight: 800;
      color: var(--ink);
      line-height: 1;
      display: block;
    }
    .stat-trend {
      font-size: 0.65rem; color: var(--text-3);
      display: flex; align-items: center; gap: 0.25rem;
      margin-top: 0.2rem;
    }
    .stat-trend.up { color: var(--success); }
    .stat-trend.down { color: var(--danger); }

    .sparkline { width: 100%; height: 26px; margin-top: 0.2rem; display: block; }
    .sparkline path { fill: none; stroke: var(--cobalt); stroke-width: 1.75; stroke-linecap: round; stroke-linejoin: round; }
    .sparkline path.fill { fill: url(#sparkGrad); stroke: none; opacity: 0.3; }

    /* ── LAYOUT ── */
    .main-grid { display: grid; grid-template-columns: 1fr; gap: 1.5rem; padding-bottom: 3rem; }

    .section-head {
      display: flex; justify-content: space-between; align-items: baseline;
      margin-bottom: 0.85rem; gap: 1rem; flex-wrap: wrap;
    }
    .section-title { font-size: 1.5rem; margin: 0; color: var(--ink); font-weight: 800; }
    .section-title em {
      font-style: italic;
      color: var(--cobalt);
      -webkit-text-fill-color: var(--cobalt);
    }
    .section-sub { font-size: 0.78rem; color: var(--text-3); margin: 0.15rem 0 0; }

    /* ── BOOKING CARD ── */
    .booking {
      background: var(--surface);
      border: 1px solid rgba(0,0,0,0.04);
      border-radius: 24px;
      box-shadow: 0 20px 40px rgba(0,0,0,0.05);
      overflow: hidden;
    }

    .stepper {
      display: flex; padding: 0.85rem 1.1rem; gap: 0.4rem;
      border-bottom: 1px solid var(--line);
      background: var(--surface-2);
      overflow-x: auto; scrollbar-width: none;
    }
    .stepper::-webkit-scrollbar { display: none; }
    .step {
      flex: 1; min-width: 100px;
      display: flex; align-items: center; gap: 0.5rem;
      padding: 0.4rem 0.65rem; border-radius: 10px;
      cursor: pointer; transition: all .25s; opacity: 0.5;
    }
    .step.done, .step.active { opacity: 1; }
    .step.active { background: var(--surface); box-shadow: var(--shadow-sm); }
    .step-num {
      width: 24px; height: 24px; border-radius: 7px;
      background: var(--surface-3); color: var(--text-3);
      display: grid; place-items: center;
      font-size: 0.68rem; font-weight: 700;
      font-family: 'JetBrains Mono', monospace;
      flex-shrink: 0; transition: all .25s;
    }
    .step.active .step-num {
      background: linear-gradient(135deg, var(--cobalt), var(--ice));
      color: white; box-shadow: var(--shadow-glow);
    }
    .step.done .step-num { background: var(--success); color: white; }
    .step-label { font-size: 0.74rem; font-weight: 600; color: var(--text-2); white-space: nowrap; }
    .step.active .step-label { color: var(--text); }

    .slide-track { position: relative; overflow: hidden; }
    .slide { padding: clamp(1.1rem, 2.5vw, 1.6rem); transition: opacity .4s, transform .4s cubic-bezier(.4,0,.2,1); }
    .slide.hidden { display: none; }

    .slide-title { font-size: 1.2rem; margin: 0 0 0.25rem; color: var(--ink); font-weight: 800; }
    .slide-title em { font-style: italic; color: var(--cobalt); -webkit-text-fill-color: var(--cobalt); }
    .slide-sub { font-size: 0.8rem; color: var(--text-2); margin: 0 0 1.1rem; }

    /* SPECIALTIES */
    .spec-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 0.55rem; }
    .spec-card {
      padding: 0.85rem;
      background: var(--surface-2);
      border: 1.5px solid rgba(0,0,0,0.04);
      border-radius: 16px;
      text-align: left; cursor: pointer;
      transition: all .25s cubic-bezier(.4,0,.2,1);
      display: flex; flex-direction: column; gap: 0.4rem;
      position: relative; overflow: hidden;
    }
    .spec-card::before {
      content: ''; position: absolute; top: 0; left: 0;
      width: 100%; height: 3px;
      background: linear-gradient(90deg, var(--cobalt), var(--ice));
      transform: scaleX(0); transform-origin: left; transition: transform .3s;
    }
    .spec-card:hover { border-color: rgba(37,99,235,0.2); transform: translateY(-3px); box-shadow: 0 10px 25px rgba(37,99,235,0.08); background: var(--surface); }
    .spec-card.active { border-color: var(--cobalt); background: var(--surface); box-shadow: 0 8px 25px rgba(37,99,235,0.15); }
    .spec-card.active::before { transform: scaleX(1); }
    .spec-icon {
      width: 32px; height: 32px; border-radius: 9px;
      background: rgba(37,99,235,0.08); color: var(--cobalt);
      display: grid; place-items: center; transition: all .25s;
    }
    .spec-card.active .spec-icon {
      background: linear-gradient(135deg, var(--cobalt), var(--ice));
      color: white; box-shadow: var(--shadow-glow);
    }
    .spec-name { font-size: 0.82rem; font-weight: 700; color: var(--text); letter-spacing: -0.01em; }
    .spec-count { font-size: 0.65rem; color: var(--text-3); font-family: 'JetBrains Mono', monospace; font-weight: 600; }

    /* DOCTORS */
    .doc-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 0.7rem; }
    .doc-card {
      background: var(--surface-2);
      border: 1.5px solid rgba(0,0,0,0.04);
      border-radius: 16px; padding: 0.95rem; cursor: pointer;
      transition: all .3s cubic-bezier(.4,0,.2,1); position: relative; overflow: hidden;
    }
    .doc-card:hover { transform: translateY(-3px); border-color: rgba(37,99,235,0.2); box-shadow: 0 12px 30px rgba(37,99,235,0.08); background: var(--surface); }
    .doc-card.active { border-color: var(--cobalt); background: var(--surface); box-shadow: 0 8px 25px rgba(37,99,235,0.15); }
    .doc-card.active::after {
      content: ''; position: absolute; top: 10px; right: 10px;
      width: 22px; height: 22px;
      background: linear-gradient(135deg, var(--cobalt), var(--ice));
      border-radius: 999px;
    }
    .doc-card.active::before {
      content: '✓'; position: absolute; top: 10px; right: 10px;
      width: 22px; height: 22px;
      color: white; font-size: 12px; font-weight: 700;
      z-index: 1; display: grid; place-items: center;
    }
    .doc-head { display: flex; align-items: center; gap: 0.65rem; margin-bottom: 0.7rem; }
    .doc-avatar {
      width: 44px; height: 44px; border-radius: 12px;
      background: linear-gradient(135deg, var(--cobalt), var(--ice));
      color: white; display: grid; place-items: center;
      font-weight: 700; font-size: 0.95rem; flex-shrink: 0;
    }
    .doc-name { font-size: 0.875rem; font-weight: 700; color: var(--text); margin: 0; line-height: 1.2; }
    .doc-spec { font-size: 0.72rem; color: var(--text-2); margin: 0.1rem 0 0; }
    .doc-meta {
      display: flex; align-items: center; gap: 0.7rem;
      padding-top: 0.65rem; border-top: 1px dashed var(--line);
      font-size: 0.7rem; color: var(--text-2);
    }
    .doc-meta > div { display: flex; align-items: center; gap: 0.25rem; }
    .doc-meta .stars { color: var(--gold); }
    .doc-availability {
      margin-top: 0.65rem; padding: 0.45rem 0.6rem;
      background: rgba(37,99,235,0.05); border-radius: 8px;
      font-size: 0.68rem; font-weight: 700; color: var(--cobalt);
      display: flex; align-items: center; gap: 0.35rem;
      border: 1px solid rgba(37,99,235,0.12);
    }
    .doc-availability.none { color: var(--text-3); background: var(--surface); border-color: var(--line); }

    /* CALENDAR */
    .cal-wrap {
      background: var(--surface-2); border: 1px solid rgba(0,0,0,0.04);
      border-radius: 16px; padding: 1rem;
    }
    .cal-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; }
    .cal-month { font-family: 'Manrope', sans-serif; font-size: 1.05rem; font-weight: 800; color: var(--ink); }
    .cal-month em { font-style: italic; color: var(--cobalt); -webkit-text-fill-color: var(--cobalt); }
    .cal-nav { display: flex; gap: 0.3rem; }
    .cal-nav-btn {
      width: 28px; height: 28px; border-radius: 8px;
      border: 1px solid var(--line-strong); background: var(--surface);
      color: var(--text-2); display: grid; place-items: center; transition: all .15s;
    }
    .cal-nav-btn:hover { color: var(--cobalt); border-color: var(--cobalt); transform: translateY(-1px); }

    .cal-weekdays {
      display: grid; grid-template-columns: repeat(7, 1fr); gap: 5px;
      margin-bottom: 5px;
      font-size: 0.6rem; font-weight: 700;
      letter-spacing: 0.08em; text-transform: uppercase;
      color: var(--text-3); text-align: center;
    }
    .cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 5px; }
    .cal-day {
      aspect-ratio: 1; border-radius: 8px;
      background: var(--heat-0);
      display: flex; flex-direction: column;
      align-items: center; justify-content: center;
      font-size: 0.78rem; font-weight: 600; color: var(--text-3);
      position: relative; transition: all .2s; cursor: not-allowed;
    }
    .cal-day.outside { opacity: 0.25; }
    .cal-day.past { opacity: 0.4; }
    .cal-day.full { background: var(--heat-full); color: var(--danger); }
    .cal-day[data-heat="1"] { background: var(--heat-1); color: var(--text); cursor: pointer; }
    .cal-day[data-heat="2"] { background: var(--heat-2); color: var(--text); cursor: pointer; }
    .cal-day[data-heat="3"] { background: var(--heat-3); color: white; cursor: pointer; }
    .cal-day[data-heat="4"] { background: var(--heat-4); color: white; cursor: pointer; box-shadow: var(--shadow-glow); }
    .cal-day[data-heat]:hover { transform: scale(1.1); z-index: 2; box-shadow: var(--shadow-md); }
    .cal-day.selected {
      background: var(--cobalt) !important; color: white !important;
      transform: scale(1.05);
      box-shadow: 0 0 0 2.5px rgba(37,99,235,0.4), var(--shadow-md); z-index: 3;
    }
    .cal-day.today { box-shadow: inset 0 0 0 1.5px var(--cobalt); }
    .cal-day-count { font-size: 0.5rem; font-family: 'JetBrains Mono', monospace; opacity: 0.8; line-height: 1; margin-top: 1px; font-weight: 700; }
    .cal-legend {
      display: flex; align-items: center; gap: 0.45rem;
      margin-top: 0.75rem; font-size: 0.65rem; color: var(--text-3); flex-wrap: wrap;
    }
    .legend-bar { display: flex; gap: 2px; }
    .legend-bar span { width: 14px; height: 10px; border-radius: 2.5px; }

    /* SLOT PILLS */
    .slot-pills { display: flex; flex-wrap: wrap; gap: 0.35rem; margin-top: 0.85rem; }
    .pill {
      padding: 0.4rem 0.85rem; border-radius: 999px;
      border: 1.5px solid var(--line-strong); background: var(--surface);
      color: var(--text); font-size: 0.78rem; font-weight: 600;
      font-family: 'JetBrains Mono', monospace; transition: all .2s;
      display: inline-flex; align-items: center; gap: 0.3rem;
    }
    .pill:hover:not(:disabled) { border-color: var(--cobalt); transform: translateY(-1px); box-shadow: var(--shadow-sm); }
    .pill.active { background: linear-gradient(135deg, var(--cobalt), var(--ice)); color: white; border-color: transparent; box-shadow: var(--shadow-glow); }
    .pill:disabled { opacity: 0.4; cursor: not-allowed; text-decoration: line-through; }

    /* FORM */
    .field { margin-bottom: 0.85rem; }
    .field-label {
      display: block; font-size: 0.66rem; font-weight: 700;
      color: var(--text-2); text-transform: uppercase; letter-spacing: 0.07em; margin-bottom: 0.3rem;
    }
    .field-input, .field-select, .field-textarea {
      width: 100%; padding: 0.6rem 0.8rem;
      background: var(--surface-2); border: 1.5px solid var(--line-strong);
      border-radius: 10px; color: var(--text); font-size: 0.85rem; font-family: inherit; transition: all .15s;
    }
    .field-input:focus, .field-select:focus, .field-textarea:focus {
      outline: 0; border-color: var(--cobalt);
      box-shadow: 0 0 0 3px rgba(37,99,235,0.12); background: var(--surface);
    }
    .field-textarea { resize: vertical; min-height: 70px; }
    .field-hint { font-size: 0.68rem; color: var(--text-3); margin-top: 0.3rem; min-height: 0.95rem; }
    .field-hint.ok { color: var(--success); }
    .field-hint.err { color: var(--danger); }
    .field-input.invalid, .field-select.invalid, .field-textarea.invalid { border-color: var(--danger); }

    /* SLIDE FOOT */
    .slide-foot {
      display: flex; justify-content: space-between; align-items: center;
      gap: 0.85rem; margin-top: 1.25rem; padding-top: 1rem;
      border-top: 1px solid var(--line);
    }
    .summary-pills { display: flex; flex-wrap: wrap; gap: 0.3rem; flex: 1; min-width: 0; }
    .summary-chip {
      display: inline-flex; align-items: center; gap: 0.3rem;
      padding: 0.25rem 0.6rem; background: rgba(37,99,235,0.06);
      border-radius: 999px; font-size: 0.68rem; font-weight: 600;
      color: var(--text-2); border: 1px solid rgba(37,99,235,0.12);
    }
    .summary-chip strong { color: var(--cobalt); font-weight: 700; }

    /* BUTTONS — Code B style */
    .btn {
      padding: 0.6rem 1.2rem; border-radius: 12px; border: 0;
      font-weight: 600; font-size: 0.85rem; transition: all .2s;
      display: inline-flex; align-items: center; gap: 0.4rem; white-space: nowrap;
    }
    .btn-ghost {
      background: var(--surface); color: var(--text-2);
      border: 1.5px solid var(--line-strong);
    }
    .btn-ghost:hover { color: var(--text); border-color: rgba(37,99,235,0.3); transform: translateY(-1px); }
    .btn-primary {
      background: var(--cobalt); color: white;
      box-shadow: 0 4px 14px rgba(37,99,235,0.30);
      position: relative; overflow: hidden;
    }
    .btn-primary::before {
      content: ''; position: absolute; inset: 0;
      background: linear-gradient(135deg, transparent, rgba(255,255,255,.15), transparent);
      transform: translateX(-100%); transition: transform .5s;
    }
    .btn-primary:hover:not(:disabled)::before { transform: translateX(100%); }
    .btn-primary:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 8px 22px rgba(37,99,235,0.40); opacity: 0.95; }
    .btn-primary:disabled { opacity: 0.4; cursor: not-allowed; box-shadow: none; }

    .spinner {
      width: 14px; height: 14px; border: 2px solid currentColor;
      border-top-color: transparent; border-radius: 999px;
      animation: spin .7s linear infinite;
    }
    @keyframes spin { to { transform: rotate(360deg); } }

    /* APPOINTMENTS */
    .panel {
      background: var(--surface);
      border: 1px solid rgba(0,0,0,0.04);
      border-radius: 24px; padding: 1.1rem;
      box-shadow: 0 20px 40px rgba(0,0,0,0.05);
    }
    .panel-toolbar { display: flex; gap: 0.4rem; margin-bottom: 0.85rem; flex-wrap: wrap; }
    .toolbar-input {
      flex: 1; min-width: 130px; padding: 0.45rem 0.75rem;
      background: var(--surface-2); border: 1.5px solid var(--line-strong);
      border-radius: 9px; font-size: 0.8rem; color: var(--text); font-family: inherit;
    }
    .toolbar-input:focus { outline: 0; border-color: var(--cobalt); background: var(--surface); }

    .appt-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 0.7rem; }
    .appt {
      background: var(--surface-2); border: 1px solid rgba(0,0,0,0.03);
      border-radius: 16px; padding: 0.85rem;
      transition: all .25s; position: relative; overflow: hidden;
    }
    .appt::before {
      content: ''; position: absolute;
      left: 0; top: 0; bottom: 0; width: 3px; background: var(--text-3);
    }
    .appt.confirme::before  { background: var(--success); }
    .appt.en-attente::before { background: var(--warning); }
    .appt.annule::before    { background: var(--danger); }
    .appt:hover { transform: translateY(-1px); box-shadow: var(--shadow-sm); background: var(--surface); }

    .appt-row { display: flex; align-items: flex-start; justify-content: space-between; gap: 0.85rem; margin-bottom: 0.55rem; }
    .appt-info { flex: 1; min-width: 0; }
    .appt-type { font-size: 0.85rem; font-weight: 700; color: var(--text); margin: 0 0 0.1rem; }
    .appt-doc { font-size: 0.72rem; color: var(--text-2); margin: 0; display: flex; align-items: center; gap: 0.25rem; }
    .appt-date-block { text-align: right; flex-shrink: 0; }
    .appt-day { font-family: 'Manrope', sans-serif; font-size: 1.3rem; font-weight: 800; color: var(--ink); line-height: 1; }
    .appt-month { font-size: 0.62rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: var(--text-3); }
    .appt-time { font-family: 'JetBrains Mono', monospace; font-size: 0.7rem; color: var(--text-2); margin-top: 0.15rem; font-weight: 600; }
    .appt-foot { display: flex; justify-content: space-between; align-items: center; gap: 0.5rem; flex-wrap: wrap; }
    .badge {
      display: inline-flex; align-items: center; gap: 0.3rem;
      padding: 0.2rem 0.55rem; border-radius: 999px;
      font-size: 0.62rem; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase;
    }
    .badge.confirme   { background: var(--success-bg); color: var(--success); }
    .badge.en-attente { background: var(--warning-bg); color: var(--warning); }
    .badge.annule     { background: var(--danger-bg); color: var(--danger); }
    .badge .ddot { width: 5px; height: 5px; border-radius: 999px; background: currentColor; }

    .appt-actions { display: flex; gap: 0.3rem; }
    .ico-btn {
      width: 28px; height: 28px; border-radius: 7px;
      background: var(--surface); border: 1px solid var(--line-strong);
      color: var(--text-2); display: grid; place-items: center; transition: all .15s;
    }
    .ico-btn:hover { color: var(--cobalt); border-color: var(--cobalt); transform: translateY(-1px); }
    .ico-btn.cancel:hover { color: var(--danger); border-color: var(--danger); background: var(--danger-bg); }

    .empty { text-align: center; padding: 2.25rem 1rem; color: var(--text-3); }
    .empty svg { margin: 0 auto 0.5rem; opacity: 0.5; }
    .empty-title { font-weight: 700; color: var(--text-2); font-size: 0.875rem; margin: 0; }

    .skeleton {
      background: linear-gradient(90deg, var(--surface-2) 25%, var(--surface-3) 50%, var(--surface-2) 75%);
      background-size: 200% 100%; animation: shimmer 1.5s infinite; border-radius: 12px;
    }
    @keyframes shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }

    /* TOASTS */
    .toast-wrap {
      position: fixed; top: 80px; right: 1rem;
      display: flex; flex-direction: column; gap: 0.45rem;
      z-index: 200; max-width: 360px;
    }
    .toast {
      display: flex; align-items: center; gap: 0.6rem;
      padding: 0.75rem 1rem; background: var(--surface);
      border: 1px solid var(--line-strong); border-radius: 14px;
      box-shadow: 0 12px 30px rgba(0,0,0,0.10);
      font-size: 0.82rem; font-weight: 600; color: var(--text);
      animation: slideIn .3s cubic-bezier(.34, 1.56, .64, 1);
    }
    .toast.success { border-left: 3px solid var(--success); }
    .toast.error   { border-left: 3px solid var(--danger); }
    .toast.info    { border-left: 3px solid var(--info); }
    .toast .ico-wrap {
      width: 22px; height: 22px; border-radius: 7px;
      display: grid; place-items: center; flex-shrink: 0;
    }
    .toast.success .ico-wrap { background: var(--success-bg); color: var(--success); }
    .toast.error   .ico-wrap { background: var(--danger-bg); color: var(--danger); }
    .toast.info    .ico-wrap { background: var(--info-bg); color: var(--info); }
    @keyframes slideIn { from { opacity: 0; transform: translateX(20px); } to { opacity: 1; transform: translateX(0); } }

    /* MODAL */
    .modal-overlay {
      position: fixed; inset: 0;
      background: rgba(15, 23, 42, 0.55);
      backdrop-filter: blur(8px); z-index: 300;
      display: grid; place-items: center;
      padding: 1rem; animation: fadeIn .2s;
    }
    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    .modal {
      background: var(--surface); border: 1px solid var(--line);
      border-radius: 24px; max-width: 460px; width: 100%;
      box-shadow: 0 30px 80px rgba(0,0,0,0.15); overflow: hidden;
      animation: scaleIn .3s cubic-bezier(.34, 1.56, .64, 1);
    }
    @keyframes scaleIn { from { opacity: 0; transform: scale(.94); } to { opacity: 1; transform: scale(1); } }
    .modal-head {
      display: flex; justify-content: space-between; align-items: center;
      padding: 1.1rem 1.35rem; border-bottom: 1px solid var(--line);
    }
    .modal-title { font-family: 'Manrope', sans-serif; font-size: 1rem; font-weight: 800; margin: 0; color: var(--ink); }
    .modal-body { padding: 1.25rem; }
    .modal-row {
      display: flex; justify-content: space-between; gap: 1rem;
      padding: 0.6rem 0; border-bottom: 1px solid var(--line); font-size: 0.82rem;
    }
    .modal-row:last-child { border-bottom: 0; }
    .modal-row .k { color: var(--text-3); font-weight: 500; }
    .modal-row .v { color: var(--text); font-weight: 600; text-align: right; }

    @media (max-width: 600px) {
      .stats { gap: 0.4rem; }
      .stat-value { font-size: 1.5rem; }
      .hero-title { font-size: 2.2rem; }
      .toast-wrap { left: 1rem; right: 1rem; max-width: none; }
    }

    .reveal { animation: revealUp .65s cubic-bezier(.4, 0, .2, 1) both; }
    @keyframes revealUp { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
    @media (prefers-reduced-motion: reduce) { .reveal { animation: none; } }
  </style>
</head>
<body>

  <nav class="nav">
    <div class="container nav-inner">

      <!-- Brand — matches Code B structure -->
      <a href="#" class="brand">
        <span class="brand-mark">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 12h4l3-9 4 18 3-9h4"/>
          </svg>
        </span>
        JumeauNum
      </a>

      <!-- Nav links — Accueil + Mes rendez-vous only -->
      <ul class="nav-links">
<li><a href="../../../gestion_user/vue/frontoffice/home.php" class="nav-link">Accueil</a></li>
<li><a href="mes_rdv.php" class="nav-link active">Mes rendez-vous</a></li>
      </ul>

      <div class="nav-actions">
        <button class="icon-btn" aria-label="Notifications">
          <i data-lucide="bell" style="width:16px;height:16px"></i>
          <span class="dot"></span>
        </button>
        <button class="icon-btn theme-toggle" id="themeToggle" aria-label="Thème">
          <span class="sun"><i data-lucide="sun" style="width:16px;height:16px"></i></span>
          <span class="moon"><i data-lucide="moon" style="width:16px;height:16px"></i></span>
        </button>
        <div class="avatar-chip">
          <div class="avatar" id="avatarInitials">PA</div>
          <span id="patientHeaderName">Patient</span>
        </div>
      </div>
    </div>
  </nav>

  <svg width="0" height="0" style="position:absolute">
    <defs>
      <linearGradient id="sparkGrad" x1="0" y1="0" x2="0" y2="1">
        <stop offset="0%" stop-color="var(--cobalt)" stop-opacity="0.5"/>
        <stop offset="100%" stop-color="var(--cobalt)" stop-opacity="0"/>
      </linearGradient>
    </defs>
  </svg>

  <section class="hero">
    <div class="container">
      <div class="hero-grid">

        <div class="reveal" style="animation-delay: .05s">
          <span class="eyebrow"><span class="pulse"></span><span id="todayDate">—</span></span>
          <h1 class="hero-title">
            Bonjour,<br>
            Votre <em>santé</em>,<br>en clair.
          </h1>
          <p class="hero-subtitle">
            Réservez vos consultations, suivez vos rendez-vous et gérez votre parcours de soins —
            le tout depuis un seul espace.
          </p>
        </div>

        <div class="hero-panel reveal" style="animation-delay: .15s">
          <div class="hero-panel-head">
            <div>
              <p class="hero-panel-title">Aperçu Santé</p>
              <p class="hero-panel-name" id="patientFullName">Patient</p>
            </div>
            <span class="badge-vital">
              <i data-lucide="activity" style="width:11px;height:11px"></i>
              Actif
            </span>
          </div>

          <div class="stats">
            <div class="stat">
              <span class="stat-label">Total RDV</span>
              <span class="stat-value mono" data-counter="0" id="statTotal">00</span>
              <span class="stat-trend up">
                <i data-lucide="trending-up" style="width:10px;height:10px"></i>
                Actif
              </span>
              <svg class="sparkline" viewBox="0 0 100 26" preserveAspectRatio="none">
                <path class="fill" d=""/>
                <path d="" id="spark1"/>
              </svg>
            </div>
            <div class="stat">
              <span class="stat-label">Confirmés</span>
              <span class="stat-value mono" data-counter="0" id="statConfirmed">00</span>
              <span class="stat-trend up">
                <i data-lucide="check-circle-2" style="width:10px;height:10px"></i>
                Validés
              </span>
              <svg class="sparkline" viewBox="0 0 100 26" preserveAspectRatio="none">
                <path class="fill" d=""/>
                <path d="" id="spark2"/>
              </svg>
            </div>
            <div class="stat">
              <span class="stat-label">En attente</span>
              <span class="stat-value mono" data-counter="0" id="statPending">00</span>
              <span class="stat-trend">
                <i data-lucide="clock" style="width:10px;height:10px"></i>
                À valider
              </span>
              <svg class="sparkline" viewBox="0 0 100 26" preserveAspectRatio="none">
                <path class="fill" d=""/>
                <path d="" id="spark3"/>
              </svg>
            </div>
          </div>
        </div>

      </div>
    </div>
  </section>

  <main class="container">
    <div class="main-grid">

      <section class="booking reveal" style="animation-delay: .25s">

        <div class="stepper">
          <div class="step active" data-step="1">
            <span class="step-num">01</span>
            <span class="step-label">Spécialité</span>
          </div>
          <div class="step" data-step="2">
            <span class="step-num">02</span>
            <span class="step-label">Médecin</span>
          </div>
          <div class="step" data-step="3">
            <span class="step-num">03</span>
            <span class="step-label">Créneau</span>
          </div>
          <div class="step" data-step="4">
            <span class="step-num">04</span>
            <span class="step-label">Confirmation</span>
          </div>
        </div>

        <div class="slide-track">

          <div class="slide" data-slide="1">
            <h3 class="slide-title">Quelle <em>spécialité</em> ?</h3>
            <p class="slide-sub">Choisissez le domaine médical qui correspond à votre besoin.</p>

            <div class="spec-grid" id="specialtyList">
              <div class="skeleton" style="height: 90px"></div>
              <div class="skeleton" style="height: 90px"></div>
              <div class="skeleton" style="height: 90px"></div>
              <div class="skeleton" style="height: 90px"></div>
            </div>

            <div class="slide-foot">
              <div class="summary-pills" id="summary1"></div>
              <button class="btn btn-primary" data-next>
                Suivant <i data-lucide="arrow-right" style="width:13px;height:13px"></i>
              </button>
            </div>
          </div>

          <div class="slide hidden" data-slide="2">
            <h3 class="slide-title">Choisissez votre <em>médecin</em></h3>
            <p class="slide-sub">Tous nos praticiens sont diplômés et exercent dans des cliniques agréées.</p>

            <div class="doc-grid" id="doctorList">
              <div class="empty">
                <i data-lucide="users" style="width:30px;height:30px"></i>
                <p class="empty-title">Choisissez d'abord une spécialité</p>
              </div>
            </div>

            <div class="slide-foot">
              <button class="btn btn-ghost" data-prev>
                <i data-lucide="arrow-left" style="width:13px;height:13px"></i> Retour
              </button>
              <div class="summary-pills" id="summary2"></div>
              <button class="btn btn-primary" data-next>
                Suivant <i data-lucide="arrow-right" style="width:13px;height:13px"></i>
              </button>
            </div>
          </div>

          <div class="slide hidden" data-slide="3">
            <h3 class="slide-title">Sélectionnez un <em>créneau</em></h3>
            <p class="slide-sub">Plus la couleur est foncée, plus il y a de créneaux disponibles.</p>

            <div class="cal-wrap">
              <div class="cal-head">
                <div class="cal-month" id="calMonth">—</div>
                <div class="cal-nav">
                  <button class="cal-nav-btn" id="prevMonth" aria-label="Mois précédent">
                    <i data-lucide="chevron-left" style="width:14px;height:14px"></i>
                  </button>
                  <button class="cal-nav-btn" id="nextMonth" aria-label="Mois suivant">
                    <i data-lucide="chevron-right" style="width:14px;height:14px"></i>
                  </button>
                </div>
              </div>

              <div class="cal-weekdays">
                <span>Lun</span><span>Mar</span><span>Mer</span>
                <span>Jeu</span><span>Ven</span><span>Sam</span><span>Dim</span>
              </div>
              <div class="cal-grid" id="calGrid"></div>

              <div class="cal-legend">
                <span>Disponibilité :</span>
                <div class="legend-bar">
                  <span style="background: var(--heat-0)"></span>
                  <span style="background: var(--heat-1)"></span>
                  <span style="background: var(--heat-2)"></span>
                  <span style="background: var(--heat-3)"></span>
                  <span style="background: var(--heat-4)"></span>
                </div>
                <span>Faible → Forte</span>
                <span style="margin-left:auto;display:inline-flex;align-items:center;gap:.35rem">
                  <span style="width:12px;height:12px;border-radius:3px;background:var(--heat-full)"></span>
                  Complet
                </span>
              </div>
            </div>

            <div style="margin-top: 0.85rem">
              <div class="field-label">Horaires
                <span id="slotsHint" style="text-transform:none;font-weight:500;color:var(--text-3);margin-left:.4rem;letter-spacing:0">— Sélectionnez une date</span>
              </div>
              <div class="slot-pills" id="slotPills"></div>
            </div>

            <div class="slide-foot">
              <button class="btn btn-ghost" data-prev>
                <i data-lucide="arrow-left" style="width:13px;height:13px"></i> Retour
              </button>
              <div class="summary-pills" id="summary3"></div>
              <button class="btn btn-primary" data-next>
                Suivant <i data-lucide="arrow-right" style="width:13px;height:13px"></i>
              </button>
            </div>
          </div>

          <div class="slide hidden" data-slide="4">
            <h3 class="slide-title">Dernière <em>étape</em></h3>
            <p class="slide-sub">Précisez le motif de votre consultation.</p>

            <div style="display:grid; grid-template-columns: 1fr; gap: 0.75rem;">
              <div class="field">
                <label class="field-label" for="consultationType">Type de consultation *</label>
                <select id="consultationType" class="field-select">
                  <option value="">— Choisir un type —</option>
                  <option value="Première consultation">Première consultation</option>
                  <option value="Suivi">Consultation de suivi</option>
                  <option value="Urgence">Urgence</option>
                  <option value="Téléconsultation">Téléconsultation</option>
                  <option value="Contrôle">Contrôle de routine</option>
                </select>
                <p class="field-hint" id="hintType"></p>
              </div>

              <div class="field">
                <label class="field-label" for="notes">Notes / motif (optionnel)</label>
                <textarea id="notes" class="field-textarea" placeholder="Décrivez brièvement les symptômes ou la raison de votre rendez-vous..."></textarea>
                <div class="field-hint" id="hintNotes" style="display:flex; justify-content:space-between; align-items:center;">
                  <span id="hintNotesMsg">Si vous ajoutez une note, elle doit faire au moins 20 caractères.</span>
                  <span id="hintNotesCount" style="font-family:monospace; font-size:.78rem; color:var(--muted, #888);">0 / 20</span>
                </div>
              </div>

              <div style="background: var(--surface-2); border:1px solid var(--line); border-radius: 16px; padding: 1rem;">
                <div class="field-label" style="margin-bottom: .55rem">Récapitulatif</div>
                <div id="finalSummary" style="display:flex; flex-direction:column; gap:.4rem; font-size:.82rem"></div>
              </div>
            </div>

            <div class="slide-foot">
              <button class="btn btn-ghost" data-prev>
                <i data-lucide="arrow-left" style="width:13px;height:13px"></i> Retour
              </button>
              <button class="btn btn-primary" id="btnBook" disabled>
                <span class="spinner" id="bookSpinner" style="display:none"></span>
                <i data-lucide="calendar-check" id="bookIcon" style="width:13px;height:13px"></i>
                <span id="bookText">Confirmer la réservation</span>
              </button>
            </div>
          </div>

        </div>
      </section>

      <aside class="reveal" style="animation-delay: .35s">
        <div class="section-head">
          <div>
            <h2 class="section-title">Mes <em>rendez-vous</em></h2>
            <p class="section-sub">Suivi de vos consultations</p>
          </div>
        </div>

        <div class="panel">
          <div class="panel-toolbar">
            <input type="text" class="toolbar-input" id="searchRdv" placeholder="Rechercher..." />
            <select class="toolbar-input" id="filterRdv" style="flex:0 0 auto; min-width: 120px">
              <option value="">Tous statuts</option>
              <option value="confirme">Confirmés</option>
              <option value="en_attente">En attente</option>
              <option value="annule">Annulés</option>
            </select>
            <button class="ico-btn" id="refreshAppts" title="Actualiser">
              <i data-lucide="refresh-cw" style="width:13px;height:13px"></i>
            </button>
          </div>

          <div class="appt-list" id="appointmentsList">
            <div class="skeleton" style="height: 95px"></div>
            <div class="skeleton" style="height: 95px"></div>
            <div class="skeleton" style="height: 95px"></div>
          </div>
        </div>
      </aside>

    </div>
  </main>

  <div class="toast-wrap" id="toast-container"></div>
<script>
  window.CURRENT_PATIENT_ID = <?= json_encode((int)($user['id_user'] ?? $user['id'] ?? 0)) ?>;
  window.CURRENT_PATIENT_NAME = <?= json_encode(($user['Prenom'] ?? $user['prenom'] ?? '') . ' ' . ($user['Nom'] ?? $user['nom'] ?? '')) ?>;
</script>
<script src="front.js"></script>
</body>
</html>