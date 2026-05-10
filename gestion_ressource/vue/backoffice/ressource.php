<?php
require_once __DIR__ . '/../../../inc_session.php';
checkSession([2, 3, 4]);
$user = getCurrentUser();
$nom    = $user['Nom']    ?? $user['nom']    ?? '';
$prenom = $user['Prenom'] ?? $user['prenom'] ?? $user['username'] ?? 'Utilisateur';
$role   = (int)($user['id_role'] ?? 0);
$initial = strtoupper(substr($prenom, 0, 1));
$roleLabel = match ($role) {
    2 => 'Admin', 3 => 'Médecin', 4 => 'Super Admin', default => 'Utilisateur'
};
$base = '/Esprit-PW-2A23-2526-JumeauNumerique';
?>
<!DOCTYPE html>
<html lang="fr" data-theme="light">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>JumeauNum – Gestion des Ressources avec IA</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet"/>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

/* Variables pour les deux thèmes */
:root {
  --sidebar-bg:     #1a3a8f;
  --sidebar-hover:  #2248b0;
  --sidebar-active: #3560d4;
  --sidebar-w:      200px;
  --bg:       #f0f4fb;
  --surface:  #ffffff;
  --surface2: #eef1f8;
  --surface3: #e7eefa;
  --border:   #dde2ef;
  --border-light: #eef2f8;
  --primary:    #2563eb;
  --primary-lt: #eff4ff;
  --text:       #111827;
  --text-2:     #4b5563;
  --text-3:     #9ca3af;
  --muted:      #6b7280;
  --green:    #16a34a;
  --green-lt: #dcfce7;
  --red:      #dc2626;
  --red-lt:   #fee2e2;
  --amber:    #d97706;
  --amber-lt: #fef3c7;
  --purple:   #9333ea;
  --purple-lt: #f3e8ff;
  --pink:     #ec4899;
  --pink-lt:  #fce7f3;
  --cyan:     #06b6d4;
  --cyan-lt:  #cffafe;
  --header-h: 58px;
  --radius:   10px;
  --shadow:   0 1px 4px rgba(0,0,0,.07), 0 4px 14px rgba(0,0,0,.05);
  --shadow-sm: 0 1px 2px rgba(0,0,0,.05);
  --shadow-md: 0 4px 6px -1px rgba(0,0,0,.1);
  --shadow-lg: 0 10px 15px -3px rgba(0,0,0,.1);
}

/* Thème sombre */
[data-theme="dark"] {
  --sidebar-bg:     #0f172a;
  --sidebar-hover:  #1e293b;
  --sidebar-active: #2563eb;
  --bg:       #0f172a;
  --surface:  #1e293b;
  --surface2: #334155;
  --surface3: #1e293b;
  --border:   #334155;
  --border-light: #2d3a4e;
  --primary:    #3b82f6;
  --primary-lt: #1e3a5f;
  --text:       #f1f5f9;
  --text-2:     #94a3b8;
  --text-3:     #64748b;
  --muted:      #94a3b8;
  --green:    #22c55e;
  --green-lt: #14532d;
  --red:      #ef4444;
  --red-lt:   #7f1d1d;
  --amber:    #f59e0b;
  --amber-lt: #78350f;
  --purple:   #a855f7;
  --purple-lt: #4c1d95;
  --pink:     #ec4899;
  --pink-lt:  #831843;
  --cyan:     #06b6d4;
  --cyan-lt:  #164e63;
  --shadow:   0 1px 3px rgba(0,0,0,.3), 0 1px 2px rgba(0,0,0,.2);
  --shadow-sm: 0 1px 2px rgba(0,0,0,.3);
  --shadow-md: 0 4px 6px -1px rgba(0,0,0,.4);
  --shadow-lg: 0 10px 15px -3px rgba(0,0,0,.5);
}

body { font-family: 'DM Sans', sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; display: flex; flex-direction: column; transition: background-color 0.3s ease, color 0.3s ease; }

/* HEADER */
header { height: var(--header-h); background: var(--surface); border-bottom: 1px solid var(--border); display: flex; align-items: center; padding: 0 20px; gap: 12px; position: fixed; top: 0; left: 0; right: 0; z-index: 200; box-shadow: var(--shadow-sm); }
.logo { display: flex; align-items: center; gap: 9px; font-weight: 700; font-size: 1rem; color: var(--primary); }
.logo svg { width: 32px; height: 32px; }
header .spacer { flex: 1; }
.header-user { display: flex; align-items: center; gap: 8px; font-size: .85rem; font-weight: 500; background: var(--surface2); border: 1px solid var(--border); border-radius: 30px; padding: 4px 12px 4px 5px; cursor: pointer; transition: all 0.2s; }
.header-user:hover { background: var(--surface3); }
.avatar-icon { width: 28px; height: 28px; border-radius: 50%; background: var(--primary); display: flex; align-items: center; justify-content: center; color: #fff; font-size: .75rem; font-weight: 700; }
.header-actions { display: flex; gap: 8px; align-items: center; }

/* Bouton mode nuit */
.theme-toggle {
  width: 38px;
  height: 38px;
  border-radius: 40px;
  background: var(--surface2);
  border: 1px solid var(--border);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.25s ease;
  position: relative;
  overflow: hidden;
}
.theme-toggle:hover {
  background: var(--primary);
  transform: scale(1.05);
}
.theme-toggle:hover svg { color: white; }
.theme-toggle svg { width: 18px; height: 18px; color: var(--text-2); transition: all 0.25s ease; }
.theme-toggle .sun-icon, .theme-toggle .moon-icon {
  position: absolute;
  transition: transform 0.3s ease, opacity 0.3s ease;
}
[data-theme="light"] .theme-toggle .moon-icon { transform: translateY(30px); opacity: 0; }
[data-theme="light"] .theme-toggle .sun-icon { transform: translateY(0); opacity: 1; }
[data-theme="dark"] .theme-toggle .sun-icon { transform: translateY(-30px); opacity: 0; }
[data-theme="dark"] .theme-toggle .moon-icon { transform: translateY(0); opacity: 1; }

.header-power {
  width: 38px;
  height: 38px;
  border-radius: 40px;
  background: var(--surface2);
  border: 1px solid var(--border);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: var(--muted);
  transition: all 0.2s;
}
.header-power:hover { background: var(--red-lt); color: var(--red); transform: scale(1.05); }

/* LAYOUT */
.layout { display: flex; margin-top: var(--header-h); min-height: calc(100vh - var(--header-h)); }

/* SIDEBAR */
aside { width: var(--sidebar-w); background: var(--sidebar-bg); position: fixed; top: var(--header-h); bottom: 0; left: 0; display: flex; flex-direction: column; padding: 14px 8px; gap: 2px; overflow-y: auto; z-index: 100; transition: background 0.3s ease; }
.nav-item { display: flex; align-items: center; gap: 9px; padding: 9px 12px; border-radius: 8px; font-size: .84rem; font-weight: 500; color: rgba(255,255,255,.7); cursor: pointer; user-select: none; white-space: nowrap; transition: background .12s, color .12s; }
.nav-item:hover { background: var(--sidebar-hover); color: #fff; }
.nav-item.active { background: var(--sidebar-active); color: #fff; }
.nav-item svg { flex-shrink: 0; width: 17px; height: 17px; }
.nav-bottom { margin-top: auto; }

/* MAIN */
main { margin-left: var(--sidebar-w); flex: 1; padding: 22px 20px 40px; min-width: 0; overflow-x: hidden; }
.page-title { font-size: 1.45rem; font-weight: 700; }
.page-sub { color: var(--muted); font-size: .82rem; margin-top: 2px; }

/* CONNECTION BANNER */
.conn-banner { display: flex; align-items: center; gap: 10px; padding: 10px 14px; border-radius: 8px; font-size: .82rem; font-weight: 500; margin-top: 14px; border: 1px solid var(--border); background: var(--surface2); transition: all 0.3s ease; }
.conn-banner.ok    { background: var(--green-lt); border-color: var(--green); color: var(--green); }
.conn-banner.error { background: var(--red-lt);   border-color: var(--red); color: var(--red); }
.conn-banner svg { width: 16px; height: 16px; flex-shrink: 0; }

/* STATS */
.stat-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-top: 16px; }
.stat-card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); padding: 14px 16px; box-shadow: var(--shadow); position: relative; overflow: hidden; transition: all 0.3s ease; }
.stat-card:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); }
.stat-badge { position: absolute; top: 10px; right: 10px; font-size: .7rem; font-weight: 600; padding: 2px 7px; border-radius: 20px; }
.badge-green { background: var(--green-lt); color: var(--green); }
.badge-red   { background: var(--red-lt);   color: var(--red); }
.badge-amber { background: var(--amber-lt); color: var(--amber); }
.badge-blue  { background: var(--primary-lt); color: var(--primary); }
.stat-icon { width: 34px; height: 34px; border-radius: 8px; display: flex; align-items: center; justify-content: center; margin-bottom: 8px; }
.stat-icon.blue { background: var(--primary-lt); color: var(--primary); }
.stat-icon.pink { background: var(--pink-lt); color: var(--pink); }
.stat-icon.teal { background: var(--cyan-lt); color: var(--cyan); }
.stat-icon.ambr { background: var(--amber-lt); color: var(--amber); }
.stat-label { font-size: .75rem; color: var(--muted); }
.stat-value { font-size: 1.35rem; font-weight: 700; margin-top: 1px; }
.stat-sub { font-size: .7rem; color: var(--muted); margin-top: 2px; }

/* TWIN ZONE */
.twin-zone { display: grid; grid-template-columns: 1fr 300px; gap: 16px; margin-top: 18px; align-items: start; }

/* TABLE */
.toolbar { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 10px; }
.btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; border-radius: 8px; font-family: inherit; font-size: .82rem; font-weight: 600; cursor: pointer; border: none; transition: all 0.2s; }
.btn:hover { opacity: .88; transform: translateY(-1px); }
.btn-primary { background: var(--primary); color: #fff; box-shadow: 0 2px 8px rgba(37,99,235,.22); }
.btn-outline  { background: var(--surface); color: var(--text); border: 1px solid var(--border); }
.btn-ghost    { background: var(--surface2); color: var(--text); border: 1px solid var(--border); }
.btn-ai       { background: linear-gradient(135deg, #9333ea, #6366f1, #ec4899); color: white; border: none; background-size: 200% 200%; transition: all 0.3s ease; }
.btn-ai:hover { background-position: 100% 100%; transform: translateY(-2px); box-shadow: 0 8px 25px rgba(147,51,234,0.3); }
.btn-block    { width: 100%; justify-content: center; margin-top: 3px; }
.toolbar-right { margin-left: auto; display: flex; align-items: center; gap: 8px; }
.filter-label  { font-size: .8rem; color: var(--muted); }
.filter-select { border: 1px solid var(--border); background: var(--surface); border-radius: 8px; padding: 6px 10px; font-family: inherit; font-size: .82rem; color: var(--text); cursor: pointer; transition: all 0.2s; }
.search-row { display: flex; gap: 8px; margin-bottom: 10px; }
.search-wrap { flex: 1; position: relative; }
.search-wrap svg { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: var(--muted); width: 14px; height: 14px; pointer-events: none; }
.search-wrap input { width: 100%; border: 1px solid var(--border); background: var(--surface); border-radius: 8px; padding: 8px 10px 8px 32px; font-family: inherit; font-size: .82rem; color: var(--text); outline: none; transition: all 0.2s; }
.search-wrap input:focus { border-color: var(--primary); box-shadow: 0 0 0 2px var(--primary-lt); }
.table-wrap { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); box-shadow: var(--shadow); overflow: hidden; transition: all 0.3s ease; }
table { width: 100%; border-collapse: collapse; font-size: .8rem; }
thead th { text-align: left; padding: 10px 12px; font-size: .7rem; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; color: var(--muted); background: var(--surface2); border-bottom: 1px solid var(--border); }
tbody tr { border-bottom: 1px solid var(--border); transition: background .1s; }
tbody tr:last-child { border-bottom: none; }
tbody tr:hover { background: var(--surface2); }
tbody td { padding: 10px 12px; vertical-align: middle; }
.id-cell { font-family: 'DM Mono', monospace; font-size: .75rem; color: var(--text-2); }
.badge { display: inline-flex; align-items: center; padding: 2px 9px; border-radius: 20px; font-size: .72rem; font-weight: 600; }
.badge-disponible   { background: var(--green-lt); color: var(--green); }
.badge-occupé       { background: var(--amber-lt); color: var(--amber); }
.badge-maintenance,
.badge-maintenanc   { background: var(--red-lt);   color: var(--red); }
.badge-en_cours     { background: var(--amber-lt); color: var(--amber); }
.badge-résolue      { background: var(--green-lt); color: var(--green); }
.badge-en_attente   { background: var(--red-lt);   color: var(--red); }
.actions-cell { display: flex; gap: 5px; }
.icon-btn { width: 28px; height: 28px; border-radius: 6px; border: 1px solid var(--border); background: var(--surface); display: flex; align-items: center; justify-content: center; cursor: pointer; color: var(--muted); transition: all 0.2s; }
.icon-btn:hover     { background: var(--primary-lt); color: var(--primary); border-color: var(--primary); transform: scale(1.05); }
.icon-btn.del:hover { background: var(--red-lt); color: var(--red); border-color: var(--red); }
.icon-btn.ai:hover  { background: linear-gradient(135deg, var(--purple-lt), var(--pink-lt)); color: var(--purple); border-color: var(--purple); }
.icon-btn svg { width: 12px; height: 12px; }
.no-data { text-align: center; padding: 40px 20px; color: var(--muted); font-size: .85rem; }
.pagination { display: flex; align-items: center; gap: 5px; padding: 10px 14px; border-top: 1px solid var(--border); font-size: .78rem; color: var(--muted); }
.pagination .count { flex: 1; }
.page-btn { width: 28px; height: 28px; border-radius: 6px; border: 1px solid var(--border); background: var(--surface); display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: .78rem; font-weight: 500; color: var(--text); transition: all 0.2s; }
.page-btn:hover { background: var(--primary); color: white; border-color: var(--primary); }
.page-btn.active { background: var(--primary); color: #fff; border-color: var(--primary); }

/* PANEL */
.right-panel { display: flex; flex-direction: column; gap: 14px; }
.panel-card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); padding: 16px; box-shadow: var(--shadow); transition: all 0.3s ease; }
.panel-title { font-size: .92rem; font-weight: 700; margin-bottom: 12px; }
.form-group { margin-bottom: 10px; }
.form-label { font-size: .72rem; font-weight: 600; color: var(--muted); margin-bottom: 3px; display: block; }
.form-control { width: 100%; border: 1px solid var(--border); background: var(--bg); border-radius: 7px; padding: 7px 9px; font-family: inherit; font-size: .8rem; color: var(--text); outline: none; transition: all 0.2s; }
.form-control:focus    { border-color: var(--primary); background: var(--surface); box-shadow: 0 0 0 2px var(--primary-lt); }
.form-control::placeholder { color: var(--text-3); }
.form-control:disabled { background: var(--surface2); color: var(--muted); cursor: not-allowed; }
.form-control.error    { border-color: var(--red); }
.field-msg { font-size: .68rem; color: var(--red); margin-top: 2px; display: none; }
.field-msg.visible { display: block; }

/* MODAL CHATBOT AMÉLIORÉ */
.modal {
  display: none;
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0,0,0,0.7);
  backdrop-filter: blur(8px);
  z-index: 1000;
  align-items: center;
  justify-content: center;
}
.modal.active { display: flex; }
.modal-content {
  background: var(--surface);
  border-radius: 28px;
  width: 90%;
  max-width: 750px;
  max-height: 85vh;
  display: flex;
  flex-direction: column;
  box-shadow: var(--shadow-xl);
  border: 1px solid var(--border);
  animation: modalFadeInUp 0.35s cubic-bezier(0.21, 1.11, 0.35, 1);
}
@keyframes modalFadeInUp {
  from { opacity: 0; transform: translateY(30px) scale(0.95); }
  to { opacity: 1; transform: translateY(0) scale(1); }
}
.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 18px 24px;
  background: linear-gradient(135deg, var(--purple), var(--pink), var(--primary));
  border-radius: 28px 28px 0 0;
  color: white;
}
.modal-header h3 {
  display: flex;
  align-items: center;
  gap: 10px;
  margin: 0;
  font-size: 1.2rem;
}
.ai-badge {
  background: rgba(255,255,255,0.2);
  border-radius: 40px;
  padding: 4px 10px;
  font-size: 0.7rem;
  font-weight: 500;
}
.modal-close {
  background: rgba(255,255,255,0.2);
  border: none;
  color: white;
  font-size: 1.3rem;
  cursor: pointer;
  width: 32px;
  height: 32px;
  border-radius: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}
.modal-close:hover { background: rgba(255,255,255,0.4); transform: scale(1.05); }
.modal-body {
  flex: 1;
  overflow-y: auto;
  padding: 24px;
  background: var(--surface2);
}
.modal-footer {
  padding: 16px 24px;
  border-top: 1px solid var(--border);
  display: flex;
  justify-content: space-between;
  gap: 10px;
  background: var(--surface);
  border-radius: 0 0 28px 28px;
}
.footer-actions { display: flex; gap: 10px; }

/* Solution styling */
.solution-container { animation: fadeIn 0.3s ease; }
@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
.panne-detail-card {
  background: var(--surface);
  border-radius: 16px;
  padding: 16px;
  margin-bottom: 20px;
  border-left: 4px solid var(--purple);
  box-shadow: var(--shadow-sm);
}
.detail-row { display: flex; margin-bottom: 8px; font-size: 0.85rem; }
.detail-label { width: 100px; font-weight: 600; color: var(--muted); }
.detail-value { flex: 1; color: var(--text); }
.solution-card {
  background: linear-gradient(135deg, var(--primary-lt), var(--surface));
  border-radius: 16px;
  padding: 18px;
  margin-bottom: 16px;
  border: 1px solid var(--border);
}
.solution-card h4 { display: flex; align-items: center; gap: 8px; margin-bottom: 14px; font-size: 1rem; color: var(--primary); }
.solution-steps { list-style: none; padding: 0; }
.solution-steps li {
  padding: 8px 0 8px 24px;
  position: relative;
  font-size: 0.85rem;
  line-height: 1.4;
  border-bottom: 1px solid var(--border-light);
}
.solution-steps li:last-child { border-bottom: none; }
.solution-steps li:before {
  content: "▹";
  position: absolute;
  left: 0;
  color: var(--purple);
  font-weight: bold;
}
.checklist-box {
  background: var(--surface);
  border-radius: 12px;
  padding: 14px;
  margin: 12px 0;
  border: 1px solid var(--border);
}
.checklist-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 0;
  border-bottom: 1px solid var(--border-light);
  cursor: pointer;
}
.checklist-item:last-child { border-bottom: none; }
.checklist-item:hover { background: var(--surface2); }
.checklist-item input { width: 18px; height: 18px; cursor: pointer; }
.warning-box {
  background: var(--amber-lt);
  border-left: 4px solid var(--amber);
  padding: 12px 16px;
  border-radius: 12px;
  margin-top: 16px;
  color: var(--amber);
}
.confidence-meter {
  background: var(--surface);
  border-radius: 30px;
  padding: 8px 16px;
  margin-top: 16px;
  display: inline-flex;
  align-items: center;
  gap: 10px;
  font-size: 0.75rem;
}
.confidence-bar {
  width: 100px;
  height: 6px;
  background: var(--border);
  border-radius: 3px;
  overflow: hidden;
}
.confidence-fill {
  height: 100%;
  background: linear-gradient(90deg, var(--green), var(--primary));
  border-radius: 3px;
  transition: width 0.5s ease;
}
.loading-solution {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 20px;
  padding: 40px;
}
.spinner {
  width: 50px;
  height: 50px;
  border: 4px solid var(--border);
  border-top-color: var(--purple);
  border-right-color: var(--pink);
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}
@keyframes spin {
  to { transform: rotate(360deg); }
}
.loading-text { font-size: 0.9rem; color: var(--text-2); }

/* TABS */
.tabs { display: flex; gap: 4px; margin-bottom: 16px; border-bottom: 1px solid var(--border); }
.tab-btn { padding: 8px 16px; background: none; border: none; font-size: .85rem; font-weight: 600; color: var(--muted); cursor: pointer; transition: all .15s; }
.tab-btn.active { color: var(--primary); border-bottom: 2px solid var(--primary); }
.tab-btn:hover { color: var(--primary); }
.tab-content { display: none; }
.tab-content.active { display: block; }

/* TOAST */
.toast { position: fixed; bottom: 24px; right: 24px; z-index: 999; background: var(--text); color: #fff; padding: 12px 20px; border-radius: 12px; font-size: .83rem; font-weight: 500; opacity: 0; transform: translateY(10px); transition: opacity .25s, transform .25s; pointer-events: none; box-shadow: var(--shadow-lg); }
.toast.show    { opacity: 1; transform: translateY(0); }
.toast.success { background: linear-gradient(135deg, var(--green), #059669); }
.toast.error   { background: linear-gradient(135deg, var(--red), #b91c1c); }
.toast.info    { background: linear-gradient(135deg, var(--primary), var(--purple)); }

/* CHATBOT FLOATING STYLES */
.chatbot-floating {
  position: fixed;
  bottom: 24px;
  right: 24px;
  z-index: 1000;
}

.chatbot-button {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  background: linear-gradient(135deg, #9333ea, #6366f1, #ec4899);
  border: none;
  cursor: pointer;
  box-shadow: 0 4px 20px rgba(0,0,0,0.2);
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
}

.chatbot-button:hover {
  transform: scale(1.1);
  box-shadow: 0 8px 30px rgba(147,51,234,0.4);
}

.chatbot-window {
  position: fixed;
  bottom: 90px;
  right: 24px;
  width: 380px;
  height: 550px;
  background: var(--surface);
  border-radius: 20px;
  box-shadow: 0 20px 40px rgba(0,0,0,0.2);
  display: none;
  flex-direction: column;
  overflow: hidden;
  z-index: 1000;
  border: 1px solid var(--border);
}

.chatbot-window.active {
  display: flex;
}

.chatbot-header {
  background: linear-gradient(135deg, var(--purple), var(--pink), var(--primary));
  padding: 16px;
  color: white;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.chatbot-messages {
  flex: 1;
  overflow-y: auto;
  padding: 16px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.message {
  max-width: 80%;
  padding: 10px 14px;
  border-radius: 16px;
  font-size: 0.85rem;
  line-height: 1.4;
}

.message.user {
  background: var(--primary);
  color: white;
  align-self: flex-end;
  border-bottom-right-radius: 4px;
}

.message.bot {
  background: var(--surface2);
  color: var(--text);
  align-self: flex-start;
  border-bottom-left-radius: 4px;
}

.message.bot.error {
  background: var(--red-lt);
  color: var(--red);
}

.chatbot-input-area {
  padding: 12px;
  border-top: 1px solid var(--border);
  display: flex;
  gap: 8px;
}

.chatbot-input {
  flex: 1;
  padding: 8px 12px;
  border: 1px solid var(--border);
  border-radius: 20px;
  background: var(--surface);
  color: var(--text);
  font-family: inherit;
  outline: none;
}

.chatbot-input:focus {
  border-color: var(--primary);
}

.chatbot-send {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: var(--primary);
  border: none;
  color: white;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.chatbot-send:hover {
  transform: scale(1.05);
}

.typing-indicator {
  display: flex;
  gap: 4px;
  padding: 10px 14px;
  background: var(--surface2);
  border-radius: 16px;
  align-self: flex-start;
  border-bottom-left-radius: 4px;
}

.typing-indicator span {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: var(--muted);
  animation: typing 1.4s infinite;
}

.typing-indicator span:nth-child(2) { animation-delay: 0.2s; }
.typing-indicator span:nth-child(3) { animation-delay: 0.4s; }

@keyframes typing {
  0%, 60%, 100% { transform: translateY(0); opacity: 0.5; }
  30% { transform: translateY(-8px); opacity: 1; }
}

/* Status badge pour IA */
.ai-status {
  font-size: 0.7rem;
  padding: 2px 8px;
  border-radius: 12px;
  background: rgba(255,255,255,0.2);
  margin-left: 8px;
}
</style>
</head>
<body>

<!-- HEADER -->
<header>
  <a class="logo" href="<?= $base ?>/bord.php">
    <svg viewBox="0 0 40 40" fill="none"><rect width="40" height="40" rx="10" fill="#2563eb"/><path d="M10 20h4l3-7 4 14 3-10 3 6 3-3h4" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
    JumeauNum
  </a>
  <div class="spacer"></div>
  <div class="header-actions">
    <a class="header-user" href="<?= $base ?>/gestion_user/vue/backoffice/profil.php">
      <div class="avatar-icon"><?= htmlspecialchars($initial) ?></div>
      <?= htmlspecialchars($prenom) ?> · <?= $roleLabel ?>
      <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9l6 6 6-6"/></svg>
    </a>
    <button class="theme-toggle" id="themeToggle" title="Mode jour/nuit">
      <svg class="sun-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <circle cx="12" cy="12" r="5"/>
        <path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/>
      </svg>
      <svg class="moon-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
      </svg>
    </button>
    <a class="header-power" href="<?= $base ?>/gestion_user/controleur/frontoffice/logout.php" title="Déconnexion">
      <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 2v10M4.93 4.93a10 10 0 100 14.14"/></svg>
    </a>
  </div>
</header>

<div class="layout">
  <?php require_once __DIR__ . '/../../../partials/backoffice_sidebar.php'; ?>

  <main>
    <div class="page-title">Gestion des Ressources</div>
    <div class="page-sub">Consultez, gérez et suivez toutes les ressources et pannes de l'hôpital.</div>

    <div class="conn-banner" id="connBanner">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
      <span id="connMsg">Vérification de la connexion...</span>
    </div>

    <div class="stat-grid">
      <div class="stat-card"><span class="stat-badge badge-green" id="stat-badge-dispo">0</span><div class="stat-icon teal"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg></div><div class="stat-label">Disponibles</div><div class="stat-value" id="stat-dispo">0</div><div class="stat-sub">Ressources libres</div></div>
      <div class="stat-card"><span class="stat-badge badge-amber" id="stat-badge-occupe">0</span><div class="stat-icon ambr"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg></div><div class="stat-label">Occupées</div><div class="stat-value" id="stat-occupe">0</div><div class="stat-sub">En utilisation</div></div>
      <div class="stat-card"><span class="stat-badge badge-red" id="stat-badge-maint">0</span><div class="stat-icon pink"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 9v4M12 17h.01M12 2a10 10 0 100 20 10 10 0 000-20z"/></svg></div><div class="stat-label">Maintenance</div><div class="stat-value" id="stat-maint">0</div><div class="stat-sub">En réparation</div></div>
      <div class="stat-card"><span class="stat-badge badge-blue" id="stat-badge-pannes">0</span><div class="stat-icon blue"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 2v10M4.93 4.93a10 10 0 100 14.14"/></svg></div><div class="stat-label">Pannes actives</div><div class="stat-value" id="stat-pannes">0</div><div class="stat-sub">Non résolues</div></div>
    </div>

    <div class="twin-zone">
      <div class="table-section">
        <div class="tabs">
          <button class="tab-btn active" onclick="switchTab('ressources')">📦 Ressources</button>
          <button class="tab-btn" onclick="switchTab('pannes')">⚠️ Pannes</button>
        </div>

        <div id="tab-ressources" class="tab-content active">
          <div class="toolbar">
            <button class="btn btn-outline" onclick="exportRessourcesCSV()">📄 Exporter CSV</button>
            <button class="btn btn-outline" onclick="exportRessourcesPDF()">📑 Exporter PDF</button>
            <div class="toolbar-right"><span class="filter-label">Statut</span><select class="filter-select" id="filterStatut" onchange="renderRessourcesTable()"><option value="">Tous</option><option value="disponible">Disponible</option><option value="occupé">Occupé</option><option value="maintenance">Maintenance</option></select></div>
          </div>
          <div class="search-row"><div class="search-wrap"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg><input type="text" id="searchRessource" placeholder="Rechercher par ID, nom, type..." oninput="renderRessourcesTable()"/></div></div>
          <div class="table-wrap"><table id="ressourcesTable"><thead><tr><th>ID</th><th>Nom</th><th>Type</th><th>Statut</th><th>Localisation</th><th>Dernière maint.</th><th>Actions</th></tr></thead><tbody id="ressourcesTableBody"><tr><td colspan="7" class="no-data">Chargement...</td></tr></tbody></table><div class="pagination"><div class="count" id="paginCountRessource">—</div><button class="page-btn" onclick="prevPageRessource()">‹</button><span id="pageInfoRessource" style="font-size:.78rem;color:var(--muted);padding:0 4px"></span><button class="page-btn" onclick="nextPageRessource()">›</button></div></div>
        </div>

        <div id="tab-pannes" class="tab-content">
          <div class="toolbar">
            <button class="btn btn-outline" onclick="exportPannesCSV()">📄 Exporter CSV</button>
            <button class="btn btn-outline" onclick="exportPannesPDF()">📑 Exporter PDF</button>
            <div class="toolbar-right"><span class="filter-label">Statut panne</span><select class="filter-select" id="filterPanneStatut" onchange="renderPannesTable()"><option value="">Tous</option><option value="en_cours">En cours</option><option value="résolue">Résolue</option><option value="en_attente">En attente</option></select></div>
          </div>
          <div class="search-row"><div class="search-wrap"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg><input type="text" id="searchPanne" placeholder="Rechercher par ID, ressource, description..." oninput="renderPannesTable()"/></div></div>
          <div class="table-wrap"><table id="pannesTable"><thead><tr><th>ID</th><th>Date panne</th><th>Date réparation</th><th>Statut</th><th>Ressource</th><th>Description</th><th>Actions</th></tr></thead><tbody id="pannesTableBody"><tr><td colspan="7" class="no-data">Chargement...</td></tr></tbody></table><div class="pagination"><div class="count" id="paginCountPanne">—</div><button class="page-btn" onclick="prevPagePanne()">‹</button><span id="pageInfoPanne" style="font-size:.78rem;color:var(--muted);padding:0 4px"></span><button class="page-btn" onclick="nextPagePanne()">›</button></div></div>
        </div>
      </div>

      <div class="right-panel">
        <div class="panel-card">
          <div class="panel-title" id="formTitle">Ajouter une ressource</div>
          <div class="form-group"><label class="form-label" for="r_id">ID Ressource * (max 8 car.)</label><input class="form-control" type="text" id="r_id" placeholder="Ex: RES-001" maxlength="8"/><div class="field-msg" id="msg_r_id"></div></div>
          <div class="form-group"><label class="form-label" for="r_nom">Nom *</label><input class="form-control" type="text" id="r_nom" placeholder="Ex: Scanner IRM"/><div class="field-msg" id="msg_r_nom"></div></div>
          <div class="form-group"><label class="form-label" for="r_type">Type *</label><select class="form-control" id="r_type"><option value="">Sélectionner...</option><option value="Imagerie">Imagerie</option><option value="Lit">Lit</option><option value="Équipement">Équipement</option><option value="Urgence">Urgence</option><option value="Dialyse">Dialyse</option><option value="Ambulance">Ambulance</option></select><div class="field-msg" id="msg_r_type"></div></div>
          <div class="form-group"><label class="form-label" for="r_statut">Statut *</label><select class="form-control" id="r_statut"><option value="">Sélectionner...</option><option value="disponible">Disponible</option><option value="occupé">Occupé</option><option value="maintenance">Maintenance</option></select><div class="field-msg" id="msg_r_statut"></div></div>
          <div class="form-group"><label class="form-label" for="r_localisation">Localisation</label><input class="form-control" type="text" id="r_localisation" placeholder="Ex: Salle 102, Bâtiment A"/><div class="field-msg" id="msg_r_localisation"></div></div>
          <div class="form-group"><label class="form-label" for="r_maint">Dernière maintenance</label><input class="form-control" type="date" id="r_maint"/><div class="field-msg" id="msg_r_maint"></div></div>
          <p id="msg_form_global" style="font-size:.72rem;color:var(--red);display:none;margin-bottom:6px"></p>
          <button class="btn btn-primary btn-block" onclick="submitRessource()"><svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg><span id="btnSubmitLabel">Ajouter ressource</span></button>
          <button class="btn btn-ghost btn-block" style="margin-top:5px" onclick="resetForm()">Réinitialiser</button>
        </div>

        <div class="panel-card">
          <div class="panel-title" id="panneFormTitle">Déclarer une panne</div>
          <div class="form-group"><label class="form-label" for="p_id">ID Panne *</label><input class="form-control" type="text" id="p_id" placeholder="Ex: PAN-001" maxlength="8"/><div class="field-msg" id="msg_p_id"></div></div>
          <div class="form-group"><label class="form-label" for="p_ressource">Ressource *</label><select class="form-control" id="p_ressource"><option value="">Chargement des ressources...</option></select><div class="field-msg" id="msg_p_ressource"></div></div>
          <div class="form-group"><label class="form-label" for="p_date_panne">Date de panne *</label><input class="form-control" type="date" id="p_date_panne"/><div class="field-msg" id="msg_p_date_panne"></div></div>
          <div class="form-group"><label class="form-label" for="p_date_rep">Date de réparation</label><input class="form-control" type="date" id="p_date_rep"/><div class="field-msg" id="msg_p_date_rep"></div></div>
          <div class="form-group"><label class="form-label" for="p_statut">Statut *</label><select class="form-control" id="p_statut"><option value="">Sélectionner...</option><option value="en_cours">En cours</option><option value="résolue">Résolue</option><option value="en_attente">En attente</option></select><div class="field-msg" id="msg_p_statut"></div></div>
          <div class="form-group"><label class="form-label" for="p_description">Description *</label><textarea class="form-control" id="p_description" rows="2" placeholder="Description détaillée de la panne..."></textarea><div class="field-msg" id="msg_p_description"></div></div>
          <p id="msg_panne_form_global" style="font-size:.72rem;color:var(--red);display:none;margin-bottom:6px"></p>
          <button class="btn btn-primary btn-block" onclick="submitPanne()"><svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg><span id="panneSubmitLabel">Déclarer panne</span></button>
          <button class="btn btn-ghost btn-block" style="margin-top:5px" onclick="resetPanneForm()">Réinitialiser</button>
        </div>
      </div>
    </div>
  </main>
</div>

<!-- MODAL CHATBOT IA AMÉLIORÉ AVEC CHECKLIST -->
<div id="chatbotModal" class="modal">
  <div class="modal-content">
    <div class="modal-header">
      <h3>🤖 <span>Assistant IA Médical</span> <span class="ai-badge" id="aiModelBadge">Gemini AI</span></h3>
      <button class="modal-close" onclick="closeChatbotModal()">✕</button>
    </div>
    <div class="modal-body" id="chatbotBody">
      <div class="loading-solution">
        <div class="spinner"></div>
        <div class="loading-text">Analyse intelligente de la panne...</div>
      </div>
    </div>
    <div class="modal-footer">
      <div class="footer-actions">
        <button class="btn btn-outline" onclick="exportSolutionToPDF()">📄 Exporter PDF</button>
        <button class="btn btn-outline" onclick="copySolutionToClipboard()">📋 Copier</button>
      </div>
      <div class="footer-actions">
        <button class="btn btn-ai" onclick="speakSolution()">🔊 Lire la solution</button>
        <button class="btn btn-primary" onclick="closeChatbotModal()">Fermer</button>
      </div>
    </div>
  </div>
</div>

<!-- Chatbot Floating Window -->
<div class="chatbot-floating">
  <button class="chatbot-button" onclick="toggleChatbot()" id="chatbotFloatingBtn">
    <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
      <path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/>
      <path d="M8 10h.01M12 10h.01M16 10h.01"/>
    </svg>
  </button>
  <div class="chatbot-window" id="chatbotWindow">
    <div class="chatbot-header">
      <strong>🤖 Assistant IA Médical <span id="chatbotAiStatus" class="ai-status">Gemini</span></strong>
      <button onclick="toggleChatbot()" style="background: none; border: none; color: white; cursor: pointer;">✕</button>
    </div>
    <div class="chatbot-messages" id="chatbotMessages">
      <div class="message bot">Bonjour! Je suis votre assistant IA médical propulsé par <strong>Google Gemini</strong>. Je peux vous aider avec:<br><br>• Diagnostic des pannes d'équipement<br>• Informations sur les ressources<br>• Procédures d'urgence<br>• Recommandations médicales<br><br>Comment puis-je vous aider aujourd'hui?</div>
    </div>
    <div class="chatbot-input-area">
      <input type="text" class="chatbot-input" id="chatbotInput" placeholder="Posez votre question..." onkeypress="if(event.key==='Enter') sendChatMessage()">
      <button class="chatbot-send" onclick="sendChatMessage()">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <line x1="22" y1="2" x2="11" y2="13"/>
          <polygon points="22 2 15 22 11 13 2 9 22 2"/>
        </svg>
      </button>
    </div>
  </div>
</div>

<div class="toast" id="toast"></div>

<script>
// ═══════════════════════════════════════════════════════════
//  CONFIGURATION GEMINI - VOTRE CLÉ API INTÉGRÉE
// ═══════════════════════════════════════════════════════════

// Votre clé API Gemini
const GEMINI_API_KEY = "AIzaSyB6RCum6nsbRs7q8L4NA8sGD7367okHgYs";
const GEMINI_API_URL = "https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent";

// Fonction d'appel à l'API Gemini
async function callGeminiAPI(prompt) {
  try {
    const response = await fetch(`${GEMINI_API_URL}?key=${GEMINI_API_KEY}`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        contents: [
          {
            parts: [
              {
                text: prompt
              }
            ]
          }
        ],
        generationConfig: {
          temperature: 0.7,
          maxOutputTokens: 1000,
          topP: 0.95
        }
      })
    });

    if (!response.ok) {
      const errorData = await response.json();
      throw new Error(`Erreur API: ${errorData.error?.message || response.status}`);
    }

    const data = await response.json();
    return data.candidates[0].content.parts[0].text;
  } catch (error) {
    console.error("Erreur appel Gemini:", error);
    throw error;
  }
}

// ═══════════════════════════════════════════════════════════
//  MODE NUIT - GESTION DU THÈME
// ═══════════════════════════════════════════════════════════

function initTheme() {
  const savedTheme = localStorage.getItem('theme');
  const html = document.documentElement;
  if (savedTheme === 'dark') html.setAttribute('data-theme', 'dark');
  else if (savedTheme === 'light') html.setAttribute('data-theme', 'light');
  else {
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    html.setAttribute('data-theme', prefersDark ? 'dark' : 'light');
  }
}

function setTheme(theme) {
  const html = document.documentElement;
  html.setAttribute('data-theme', theme);
  localStorage.setItem('theme', theme);
}

function toggleTheme() {
  const currentTheme = document.documentElement.getAttribute('data-theme');
  const newTheme = currentTheme === 'light' ? 'dark' : 'light';
  setTheme(newTheme);
  showToast(`Mode ${newTheme === 'dark' ? 'nuit' : 'jour'} activé`, 'info');
}

// ═══════════════════════════════════════════════════════════
//  CHATBOT FLOATING FUNCTIONS
// ═══════════════════════════════════════════════════════════

let isChatbotOpen = false;

function toggleChatbot() {
  const window = document.getElementById('chatbotWindow');
  isChatbotOpen = !isChatbotOpen;
  window.classList.toggle('active');
}

// Ajouter un message dans le chat
function addMessage(text, isUser = false) {
  const messagesDiv = document.getElementById('chatbotMessages');
  const messageDiv = document.createElement('div');
  messageDiv.className = `message ${isUser ? 'user' : 'bot'}`;
  messageDiv.innerHTML = text.replace(/\n/g, '<br>');
  messagesDiv.appendChild(messageDiv);
  messagesDiv.scrollTop = messagesDiv.scrollHeight;
}

// Afficher l'indicateur de frappe
function showTypingIndicator() {
  const messagesDiv = document.getElementById('chatbotMessages');
  const typingDiv = document.createElement('div');
  typingDiv.className = 'typing-indicator';
  typingDiv.id = 'typingIndicator';
  typingDiv.innerHTML = '<span></span><span></span><span></span>';
  messagesDiv.appendChild(typingDiv);
  messagesDiv.scrollTop = messagesDiv.scrollHeight;
}

function hideTypingIndicator() {
  const indicator = document.getElementById('typingIndicator');
  if (indicator) indicator.remove();
}

// Obtenir le contexte médical
function getMedicalContext() {
  const dispo = allRessources.filter(r => normalizeStatut(r.Statut) === 'disponible').length;
  const occupe = allRessources.filter(r => normalizeStatut(r.Statut) === 'occupé').length;
  const maint = allRessources.filter(r => normalizeStatut(r.Statut) === 'maintenance').length;
  const pannesActives = allPannes.filter(p => p.statut === 'en_cours' || p.statut === 'en_attente').length;
  
  const recentPannes = allPannes.slice(0, 5).map(p => 
    `- ${p.id_Panne}: ${p.description.substring(0, 100)} (${p.statut})`
  ).join('\n');
  
  return `Contexte hôpital JumeauNum:
- Total ressources: ${allRessources.length}
- Ressources disponibles: ${dispo}
- Ressources occupées: ${occupe}
- Ressources en maintenance: ${maint}
- Pannes actives non résolues: ${pannesActives}

Dernières pannes enregistrées:
${recentPannes || 'Aucune panne récente'}

Rappel: En cas d'urgence médicale, composez le 15 immédiatement.`;
}

// Envoyer message au chatbot Gemini
async function sendChatMessage() {
  const input = document.getElementById('chatbotInput');
  const message = input.value.trim();
  if (!message) return;
  
  addMessage(message, true);
  input.value = '';
  
  showTypingIndicator();
  
  try {
    const context = getMedicalContext();
    const systemPrompt = `Tu es un assistant IA médical expert pour l'hôpital JumeauNum. Tu réponds toujours en français de manière professionnelle, précise et utile.

${context}

Directives importantes:
1. Pour toute urgence médicale, rappelle d'appeler le 15
2. Pour les pannes d'équipement, donne des conseils pratiques de diagnostic
3. Sois empathique mais professionnel
4. Si tu ne sais pas, indique-le honnêtement et recommande de contacter un spécialiste

Question de l'utilisateur: ${message}

Réponse:`;
    
    const response = await callGeminiAPI(systemPrompt);
    hideTypingIndicator();
    addMessage(response, false);
  } catch (error) {
    hideTypingIndicator();
    addMessage(`❌ Désolé, une erreur est survenue: ${error.message}. Vérifiez votre connexion internet.`, false);
  }
}

// ═══════════════════════════════════════════════════════════
//  PATHS
// ═══════════════════════════════════════════════════════════
const CRUD_RESSOURCE = '../../controleur/backoffice/ressource_crud.php';
const CRUD_PANNE = '../../controleur/backoffice/panne_crud.php';

// ── STATE ───────────────────────────────────────────────────
let allRessources = [];
let allPannes = [];
let editingRessourceId = null;
let editingPanneId = null;
let currentPageRessource = 1;
let currentPagePanne = 1;
const PAGE_SIZE = 8;

// Variables pour le chatbot modal
let currentSolutionText = "";
let currentSolutionHTML = "";
let currentPanneInfo = null;
let currentConfidence = 0;

// ═══════════════════════════════════════════════════════════
//  BASE DE CONNAISSANCE AVANCÉE AVEC CHECKLISTS SPÉCIFIQUES
// ═══════════════════════════════════════════════════════════

// Checklist spécifique pour les ambulances
const ambulanceChecklist = [
  { item: "🔋 Vérifier le niveau de batterie (doit être > 12.5V)", checked: false },
  { item: "⛽ Vérifier le niveau de carburant (essence/diesel)", checked: false },
  { item: "🛞 Vérifier la pression des pneus", checked: false },
  { item: "🚨 Tester les gyrophares et sirènes", checked: false },
  { item: "📡 Vérifier la communication radio", checked: false },
  { item: "🩺 Vérifier l'équipement médical à bord (défibrillateur, respirateur)", checked: false },
  { item: "❄️ Vérifier le système de climatisation/chauffage", checked: false },
  { item: "🔧 Vérifier le moteur (bruits anormaux, fumées)", checked: false }
];

// Checklist spécifique pour les scanners/IRM
const scannerChecklist = [
  { item: "🔌 Vérifier l'alimentation électrique (secteur 230V)", checked: false },
  { item: "⚡ Vérifier l'onduleur et la stabilisation de tension", checked: false },
  { item: "🖥️ Redémarrer la console d'acquisition", checked: false },
  { item: "🌡️ Vérifier la température de la salle (18-22°C)", checked: false },
  { item: "❄️ Vérifier le système de refroidissement (cryogénie)", checked: false },
  { item: "📡 Tester la connexion réseau DICOM", checked: false },
  { item: "🔍 Vérifier les détecteurs de rayonnement", checked: false },
  { item: "📊 Analyser les logs d'erreur système", checked: false }
];

// Checklist générale
const generalChecklist = [
  { item: "🔌 Vérifier l'alimentation électrique", checked: false },
  { item: "🔌 Vérifier le câble d'alimentation", checked: false },
  { item: "🔄 Effectuer un redémarrage complet", checked: false },
  { item: "📟 Vérifier les messages d'erreur affichés", checked: false },
  { item: "📞 Consulter la documentation technique", checked: false }
];

// Base de connaissances enrichie avec diagnostics précis
const knowledgeBase = {
  "ambulance": {
    name: "Ambulance - Véhicule d'urgence",
    icon: "🚑",
    keywords: ["ambulance", "véhicule", "moteur", "essence", "diesel", "carburant", "batterie", "démarre", "panne moteur", "voyant", "frein"],
    priority: "Urgence",
    checklist: ambulanceChecklist,
    diagnostics: [
      { condition: "essence|diesel|carburant|fuel", solution: "⚠️ Vérifiez immédiatement le niveau de carburant. Un réservoir vide est la cause la plus fréquente." },
      { condition: "batterie|démarre pas|ne démarre", solution: "🔋 Testez la batterie (12.5V minimum). Vérifiez les cosses (oxydation)." },
      { condition: "voyant|dashboard|tableau bord", solution: "📊 Identifiez le voyant allumé. Consultez le manuel du véhicule." }
    ],
    steps: [
      "1. ⛽ VÉRIFIER LE CARBURANT - Premier réflexe ! 70% des pannes d'ambulance sont liées au carburant",
      "2. 🔋 Vérifier la batterie (tension à vide > 12.5V)",
      "3. 🛞 Contrôler l'état des pneus et la pression",
      "4. 🔊 Tester les équipements de signalisation",
      "5. 🔧 Vérifier le niveau d'huile moteur",
      "6. 🩺 Contrôler le matériel médical embarqué"
    ],
    recommendations: "Pour toute ambulance en panne, une ambulance de relève doit être dépêchée immédiatement.",
    estimatedTime: "15-45 minutes",
    emergencyContact: "🚨 Dépannage 24/7: 0800 123 456 (urgences)"
  },
  
  "scanner": {
    name: "Scanner / IRM - Imagerie médicale",
    icon: "🖥️",
    keywords: ["scanner", "irm", "ct", "tomodensitomètre", "imagerie", "acquisition", "image", "dicom", "rayon", "détecteur"],
    priority: "Haute",
    checklist: scannerChecklist,
    diagnostics: [
      { condition: "courant|électricité|alimentation|secteur", solution: "⚡ Vérifiez la prise de courant (multimètre 230V). Testez l'onduleur." },
      { condition: "température|chaleur|refroidissement|cryo", solution: "❄️ Vérifiez la température de la salle (doit être entre 18-22°C). Contrôlez le cryostat." },
      { condition: "image|acquisition|flou|artéfact", solution: "📸 Recalibrez le système. Vérifiez les détecteurs." }
    ],
    steps: [
      "1. ⚡ Vérifier l'alimentation électrique et l'onduleur",
      "2. 🌡️ Contrôler la température de la salle (18-22°C)",
      "3. 🔄 Redémarrer la console d'acquisition",
      "4. 📡 Tester la connectivité réseau DICOM",
      "5. 🔧 Vérifier les détecteurs et capteurs"
    ],
    recommendations: "Ne pas tenter d'ouvrir le capot. Contactez le technicien agréé.",
    estimatedTime: "30-90 minutes",
    emergencyContact: "📞 Support technique: 0800 789 012"
  },
  
  "default": {
    name: "Panne Générique",
    icon: "🔧",
    keywords: [],
    priority: "Normale",
    checklist: generalChecklist,
    diagnostics: [],
    steps: [
      "1. 📝 Documenter le problème avec photo",
      "2. 🔄 Effectuer un redémarrage complet",
      "3. 🔌 Vérifier les connexions électriques",
      "4. 📞 Contacter le support technique si persistance"
    ],
    recommendations: "Documentez toutes les actions pour le suivi.",
    estimatedTime: "15-30 minutes",
    emergencyContact: null
  },
  
  "equipement": {
    name: "Équipement Médical Général",
    icon: "🏥",
    keywords: ["moniteur", "respirateur", "ventilateur", "pompe", "perfusion", "defibrillateur", "ecg", "tension"],
    priority: "Critique",
    checklist: generalChecklist,
    diagnostics: [
      { condition: "batterie|autonomie", solution: "🔋 Vérifiez l'autonomie des batteries. Branchez sur secteur." },
      { condition: "alarme|bipe|erreur", solution: "📟 Notez le code d'erreur. Consultez le manuel." }
    ],
    steps: [
      "1. 🚨 Basculer vers équipement de secours immédiatement",
      "2. 🔋 Vérifier l'alimentation et les batteries",
      "3. 📟 Relever les codes d'erreur",
      "4. 🔌 Tester les capteurs et câbles patient"
    ],
    recommendations: "Priorité absolue à la sécurité du patient.",
    estimatedTime: "Intervention immédiate",
    emergencyContact: "🆘 Contacter le biomédical 24/7"
  }
};

// Détection du type d'équipement
function detectEquipmentType(description, ressourceType, ressourceNom) {
  const lowerDesc = (description + " " + (ressourceType || "") + " " + (ressourceNom || "")).toLowerCase();
  
  if (lowerDesc.includes("ambulance") || lowerDesc.includes("véhicule") || lowerDesc.includes("utilitaire")) {
    return "ambulance";
  }
  if (lowerDesc.includes("scanner") || lowerDesc.includes("irm") || lowerDesc.includes("ct") || lowerDesc.includes("tomodensitom")) {
    return "scanner";
  }
  if (lowerDesc.includes("moniteur") || lowerDesc.includes("respirateur") || lowerDesc.includes("ventilateur") || 
      lowerDesc.includes("pompe") || lowerDesc.includes("perfusion") || lowerDesc.includes("defibrillateur")) {
    return "equipement";
  }
  return "default";
}

// Générer la solution avec checklist interactive
function generateEnhancedSolution(description, statut, ressourceNom, ressourceType) {
  const equipmentType = detectEquipmentType(description, ressourceType, ressourceNom);
  const solutionData = knowledgeBase[equipmentType] || knowledgeBase.default;
  
  let specificDiagnostic = null;
  const lowerDesc = description.toLowerCase();
  for (const diag of (solutionData.diagnostics || [])) {
    if (diag.condition && lowerDesc.match(new RegExp(diag.condition, 'i'))) {
      specificDiagnostic = diag;
      break;
    }
  }
  
  currentConfidence = equipmentType !== "default" ? 85 : 60;
  
  const currentChecklist = solutionData.checklist || generalChecklist;
  const checklistHTML = `
    <div class="checklist-box">
      <h4 style="margin-bottom: 10px; display: flex; align-items: center; gap: 8px;">
        <span>✅</span> Checklist de diagnostic
        <span style="font-size: 0.7rem; font-weight: normal; color: var(--muted);">(cliquez pour valider)</span>
      </h4>
      <div id="dynamic-checklist">
        ${currentChecklist.map((item, idx) => `
          <div class="checklist-item" onclick="toggleChecklistItem(${idx})">
            <input type="checkbox" id="check_${idx}" ${item.checked ? 'checked' : ''} onclick="event.stopPropagation(); toggleChecklistItem(${idx})">
            <label for="check_${idx}" style="cursor: pointer; flex: 1;">${item.item}</label>
          </div>
        `).join('')}
      </div>
      <div style="margin-top: 12px;">
        <button class="btn btn-outline" style="padding: 4px 12px; font-size: 0.7rem;" onclick="resetChecklist()">🔄 Réinitialiser</button>
        <span id="checklistProgress" style="float: right; font-size: 0.7rem; color: var(--green);"></span>
      </div>
    </div>
  `;
  
  const specificHTML = specificDiagnostic ? `
    <div class="solution-card" style="border-left: 4px solid var(--green);">
      <h4><span>🎯</span> Diagnostic prioritaire détecté</h4>
      <p><strong>Analyse:</strong> ${specificDiagnostic.solution}</p>
    </div>
  ` : '';
  
  const emergencyHTML = equipmentType === "ambulance" ? `
    <div class="warning-box">
      <strong>🚨 PROCÉDURE D'URGENCE AMBULANCE</strong><br>
      • Si panne sur intervention, contacter immédiatement le régulateur (15)<br>
      • Une ambulance de remplacement doit être dépêchée<br>
      • Ne pas tenter de réparer sur la voie publique sans balisage<br>
      • Contacter le dépanneur agréé: ${solutionData.emergencyContact}
    </div>
  ` : equipmentType === "scanner" ? `
    <div class="warning-box" style="background: var(--primary-lt); border-left-color: var(--primary);">
      <strong>⚠️ PROCÉDURE SPÉCIFIQUE SCANNER/IRM</strong><br>
      • Ne coupez pas l'alimentation sans prévenir (perte calibration)<br>
      • Contactez le support technique: ${solutionData.emergencyContact}<br>
      • Reportez les patients programmés vers un autre créneau
    </div>
  ` : '';
  
  const solutionHTML = `
    <div class="solution-container" id="solutionContainer">
      <div class="panne-detail-card">
        <h4 style="margin-bottom: 12px;">📋 Détails de la panne</h4>
        <div class="detail-row"><div class="detail-label">Description:</div><div class="detail-value">${escapeHtml(description)}</div></div>
        <div class="detail-row"><div class="detail-label">Statut:</div><div class="detail-value"><span class="badge ${statut === 'en_cours' ? 'badge-en_cours' : (statut === 'résolue' ? 'badge-résolue' : 'badge-en_attente')}">${escapeHtml(statut)}</span></div></div>
        <div class="detail-row"><div class="detail-label">Équipement:</div><div class="detail-value"><strong>${escapeHtml(ressourceNom)}</strong> ${solutionData.icon}</div></div>
        <div class="detail-row"><div class="detail-label">Type:</div><div class="detail-value">${escapeHtml(ressourceType || 'Non spécifié')}</div></div>
      </div>
      
      <div class="solution-card">
        <h4><span>🔍</span> Diagnostic IA</h4>
        <div><strong>${solutionData.name}</strong></div>
        <div>Priorité: <strong style="color: ${solutionData.priority === 'Urgence' ? 'var(--red)' : (solutionData.priority === 'Critique' ? 'var(--amber)' : 'var(--green)')}">${solutionData.priority}</strong></div>
        <div>Temps estimé: ${solutionData.estimatedTime}</div>
        <div class="confidence-meter">
          <span>Confiance diagnostic:</span>
          <div class="confidence-bar"><div class="confidence-fill" style="width: ${currentConfidence}%"></div></div>
          <span>${currentConfidence}%</span>
        </div>
      </div>
      
      ${specificHTML}
      
      <div class="solution-card">
        <h4><span>🛠️</span> Procédure de dépannage</h4>
        <ul class="solution-steps">
          ${solutionData.steps.map(step => `<li>${step}</li>`).join('')}
        </ul>
      </div>
      
      ${checklistHTML}
      
      <div class="solution-card">
        <h4><span>💡</span> Recommandations</h4>
        <p>${solutionData.recommendations}</p>
      </div>
      
      ${emergencyHTML}
      
      <div class="warning-box">
        <strong>⚠️ Précautions de sécurité</strong><br>
        • Pour les ambulances: Balisez les lieux, portez le gilet jaune<br>
        • Pour les équipements électriques: Débranchez avant intervention<br>
        • Documentez toutes les actions pour le suivi technique<br>
        • En cas de doute, contactez un technicien qualifié
      </div>
    </div>
  `;
  
  const solutionText = `${solutionData.name}. Priorité: ${solutionData.priority}. ${specificDiagnostic ? `Diagnostic spécifique: ${specificDiagnostic.solution}` : ''} Procédure: ${solutionData.steps.join('. ')}. Recommandations: ${solutionData.recommendations}`;
  
  return { html: solutionHTML, text: solutionText };
}

// Fonctions pour la checklist
let currentChecklistData = null;

function toggleChecklistItem(index) {
  if (!currentChecklistData) return;
  const checkbox = document.getElementById(`check_${index}`);
  if (checkbox) {
    currentChecklistData[index].checked = checkbox.checked;
    updateChecklistProgress();
  }
}

function updateChecklistProgress() {
  if (!currentChecklistData) return;
  const total = currentChecklistData.length;
  const checked = currentChecklistData.filter(item => item.checked).length;
  const progressSpan = document.getElementById('checklistProgress');
  if (progressSpan) {
    progressSpan.innerHTML = `${checked}/${total} validé(s)`;
    if (checked === total && total > 0) {
      progressSpan.style.color = 'var(--green)';
      progressSpan.innerHTML += ' ✅ Diagnostic complet!';
    }
  }
}

function resetChecklist() {
  if (!currentChecklistData) return;
  currentChecklistData.forEach((item, idx) => {
    item.checked = false;
    const checkbox = document.getElementById(`check_${idx}`);
    if (checkbox) checkbox.checked = false;
  });
  updateChecklistProgress();
  showToast("Checklist réinitialisée", "info");
}

// ═══════════════════════════════════════════════════════════
//  CHATBOT MODAL FUNCTIONS
// ═══════════════════════════════════════════════════════════

async function openChatbot(panneId) {
  const panne = allPannes.find(p => p.id_Panne === panneId);
  if (!panne) { showToast("Panne non trouvée", "error"); return; }
  
  currentPanneInfo = panne;
  const ressource = allRessources.find(r => r.id_ressource === panne.id_ressource);
  const ressourceType = ressource ? ressource.Type : null;
  
  const modal = document.getElementById('chatbotModal');
  const body = document.getElementById('chatbotBody');
  modal.classList.add('active');
  
  body.innerHTML = `<div class="loading-solution"><div class="spinner"></div><div class="loading-text">Analyse intelligente de la panne avec Gemini...</div></div>`;
  
  setTimeout(() => {
    const enhanced = generateEnhancedSolution(
      panne.description, panne.statut,
      panne.ressource_nom || panne.id_ressource, ressourceType
    );
    currentSolutionHTML = enhanced.html;
    currentSolutionText = enhanced.text;
    body.innerHTML = currentSolutionHTML;
    
    const equipmentType = detectEquipmentType(panne.description, ressourceType, panne.ressource_nom);
    const solutionData = knowledgeBase[equipmentType] || knowledgeBase.default;
    currentChecklistData = (solutionData.checklist || generalChecklist).map((item, idx) => ({
      ...item,
      checked: false
    }));
    updateChecklistProgress();
    
    if (currentConfidence >= 70) {
      setTimeout(() => {
        if (window.confetti) window.confetti({ particleCount: 50, spread: 60, origin: { y: 0.8 } });
      }, 100);
    }
  }, 1000);
}

function closeChatbotModal() {
  document.getElementById('chatbotModal').classList.remove('active');
  currentSolutionText = "";
  currentPanneInfo = null;
  currentChecklistData = null;
}

function speakSolution() {
  if (!currentSolutionText) { showToast("Aucune solution", "error"); return; }
  if ('speechSynthesis' in window) {
    window.speechSynthesis.cancel();
    const utterance = new SpeechSynthesisUtterance(currentSolutionText);
    utterance.lang = 'fr-FR';
    utterance.rate = 0.85;
    window.speechSynthesis.speak(utterance);
    showToast("🔊 Lecture en cours...", "info");
  } else { showToast("Synthèse vocale non supportée", "error"); }
}

function exportSolutionToPDF() {
  if (!currentSolutionHTML) { showToast("Aucune solution", "error"); return; }
  const { jsPDF } = window.jspdf;
  const doc = new jsPDF();
  const tempDiv = document.createElement('div');
  tempDiv.innerHTML = currentSolutionHTML;
  const textContent = tempDiv.innerText;
  doc.setFontSize(16);
  doc.text("Diagnostic IA - JumeauNum", 14, 15);
  doc.setFontSize(10);
  doc.text(`Date: ${new Date().toLocaleString()}`, 14, 25);
  const lines = doc.splitTextToSize(textContent, 180);
  doc.text(lines, 14, 35);
  doc.save(`diagnostic_${currentPanneInfo?.id_Panne || 'panne'}.pdf`);
  showToast("PDF exporté", "success");
}

function copySolutionToClipboard() {
  if (!currentSolutionText) { showToast("Aucune solution", "error"); return; }
  navigator.clipboard.writeText(currentSolutionText);
  showToast("Copié !", "success");
}

function normalizeStatut(statut) {
  if (!statut) return '';
  if (statut === 'maintenanc') return 'maintenance';
  return statut;
}

// ═══════════════════════════════════════════════════════════
//  RESSOURCES ET PANNES
// ═══════════════════════════════════════════════════════════

document.addEventListener('DOMContentLoaded', () => {
  initTheme();
  document.getElementById('r_maint').value = new Date().toISOString().split('T')[0];
  document.getElementById('p_date_panne').value = new Date().toISOString().split('T')[0];
  
  const themeToggle = document.getElementById('themeToggle');
  if (themeToggle) themeToggle.addEventListener('click', toggleTheme);
  
  showToast("🤖 IA Gemini prête !", "success");
  
  testConnection();
});

async function testConnection() {
  const banner = document.getElementById('connBanner');
  try {
    const res = await fetch(CRUD_RESSOURCE + '?action=ping');
    const r = await res.json();
    if (r.success) {
      banner.className = 'conn-banner ok';
      banner.innerHTML = `<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg><span>Connecté à la base ✓</span>`;
      loadRessources();
      loadPannes();
    } else { showConnError(r.error); }
  } catch (e) { showConnError('Erreur de connexion'); }
}

function showConnError(msg) {
  const banner = document.getElementById('connBanner');
  banner.className = 'conn-banner error';
  banner.innerHTML = `<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M15 9l-6 6M9 9l6 6"/></svg><span>${msg}</span>`;
}

function showToast(msg, type = 'success') {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.className = 'toast ' + type + ' show';
  setTimeout(() => t.className = 'toast', 3000);
}

async function postData(url, data) {
  const fd = new FormData();
  for (const [k, v] of Object.entries(data)) if (v !== null && v !== undefined) fd.append(k, v);
  const res = await fetch(url, { method: 'POST', body: fd });
  return res.json();
}

async function loadRessources() {
  try {
    const r = await postData(CRUD_RESSOURCE, { action: 'getAll' });
    if (r.success) { allRessources = r.data; updateStats(); renderRessourcesTable(); populateRessourceSelect(); }
  } catch (e) { document.getElementById('ressourcesTableBody').innerHTML = '<tr><td colspan="7" class="no-data">Erreur chargement</td></tr>'; }
}

function updateStats() {
  const dispo = allRessources.filter(r => normalizeStatut(r.Statut) === 'disponible').length;
  const occupe = allRessources.filter(r => normalizeStatut(r.Statut) === 'occupé').length;
  const maint = allRessources.filter(r => normalizeStatut(r.Statut) === 'maintenance').length;
  const pannesActives = allPannes.filter(p => p.statut === 'en_cours' || p.statut === 'en_attente').length;
  document.getElementById('stat-dispo').textContent = dispo;
  document.getElementById('stat-occupe').textContent = occupe;
  document.getElementById('stat-maint').textContent = maint;
  document.getElementById('stat-pannes').textContent = pannesActives;
  document.getElementById('stat-badge-dispo').textContent = dispo;
  document.getElementById('stat-badge-occupe').textContent = occupe;
  document.getElementById('stat-badge-maint').textContent = maint;
  document.getElementById('stat-badge-pannes').textContent = pannesActives;
}

function getBadgeClass(statut) {
  const normalized = normalizeStatut(statut);
  if (normalized === 'disponible') return 'badge-disponible';
  if (normalized === 'occupé') return 'badge-occupé';
  if (normalized === 'maintenance') return 'badge-maintenance';
  return 'badge-maintenance';
}

function renderRessourcesTable() {
  const search = document.getElementById('searchRessource').value.toLowerCase();
  const filterStatut = document.getElementById('filterStatut').value;
  const filtered = allRessources.filter(r => {
    const matchSearch = !search || (r.id_ressource || '').toLowerCase().includes(search) || (r.Nom || '').toLowerCase().includes(search) || (r.Type || '').toLowerCase().includes(search);
    const rStatut = normalizeStatut(r.Statut);
    return matchSearch && (!filterStatut || rStatut === filterStatut);
  });
  const total = filtered.length;
  const pages = Math.max(1, Math.ceil(total / PAGE_SIZE));
  if (currentPageRessource > pages) currentPageRessource = pages;
  const slice = filtered.slice((currentPageRessource - 1) * PAGE_SIZE, currentPageRessource * PAGE_SIZE);
  document.getElementById('paginCountRessource').textContent = `Affichage de ${slice.length} sur ${total}`;
  document.getElementById('pageInfoRessource').textContent = `${currentPageRessource} / ${pages}`;
  const tbody = document.getElementById('ressourcesTableBody');
  if (!slice.length) { tbody.innerHTML = '<tr><td colspan="7" class="no-data">Aucune ressource</td></tr>'; return; }
  tbody.innerHTML = slice.map(r => `<tr><td class="id-cell">${escapeHtml(r.id_ressource)}</td><td>${escapeHtml(r.Nom)}</td><td>${escapeHtml(r.Type)}</td><td><span class="badge ${getBadgeClass(r.Statut)}">${escapeHtml(r.Statut)}</span></td><td>${escapeHtml(r.Localisation) || '-'}</td><td>${r.Dernier_Maintenence || '-'}</td><td><div class="actions-cell"><button class="icon-btn" onclick="editRessource('${escapeHtml(r.id_ressource)}')" title="Modifier"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></button><button class="icon-btn del" onclick="deleteRessource('${escapeHtml(r.id_ressource)}')" title="Supprimer"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><path d="M10 11v6M14 11v6M9 6V4h6v2"/></svg></button></div></td></tr>`).join('');
}

function prevPageRessource() { if (currentPageRessource > 1) { currentPageRessource--; renderRessourcesTable(); } }
function nextPageRessource() { currentPageRessource++; renderRessourcesTable(); }

function populateRessourceSelect() {
  const sel = document.getElementById('p_ressource');
  sel.innerHTML = '<option value="">Sélectionner...</option>';
  allRessources.forEach(r => sel.innerHTML += `<option value="${escapeHtml(r.id_ressource)}">${escapeHtml(r.id_ressource)} - ${escapeHtml(r.Nom)} (${escapeHtml(r.Type)})</option>`);
}

function validateRessource() {
  let ok = true;
  const id = document.getElementById('r_id'), nom = document.getElementById('r_nom'), type = document.getElementById('r_type'), statut = document.getElementById('r_statut');
  document.querySelectorAll('.field-msg').forEach(el => el.classList.remove('visible'));
  document.querySelectorAll('.form-control').forEach(el => el.classList.remove('error'));
  if (!id.value.trim()) { showFieldError('r_id', "ID obligatoire"); ok = false; }
  else if (id.value.trim().length > 8) { showFieldError('r_id', "Max 8 caractères"); ok = false; }
  if (!nom.value.trim()) { showFieldError('r_nom', "Nom obligatoire"); ok = false; }
  if (!type.value) { showFieldError('r_type', "Type obligatoire"); ok = false; }
  if (!statut.value) { showFieldError('r_statut', "Statut obligatoire"); ok = false; }
  return ok;
}

function showFieldError(fieldId, msg) {
  const el = document.getElementById(fieldId);
  const msgEl = document.getElementById('msg_' + fieldId);
  if (el) el.classList.add('error');
  if (msgEl) { msgEl.textContent = msg; msgEl.classList.add('visible'); }
}

async function submitRessource() {
  if (!validateRessource()) return;
  const data = { action: editingRessourceId ? 'update' : 'add', id_ressource: document.getElementById('r_id').value.trim(), Nom: document.getElementById('r_nom').value.trim(), Type: document.getElementById('r_type').value, Statut: document.getElementById('r_statut').value, Localisation: document.getElementById('r_localisation').value.trim(), Dernier_Maintenence: document.getElementById('r_maint').value || null };
  try {
    const r = await postData(CRUD_RESSOURCE, data);
    if (r.success) { showToast(r.message, 'success'); resetForm(); loadRessources(); }
    else { document.getElementById('msg_form_global').textContent = r.error; document.getElementById('msg_form_global').style.display = 'block'; }
  } catch (e) { document.getElementById('msg_form_global').textContent = 'Erreur réseau'; document.getElementById('msg_form_global').style.display = 'block'; }
}

function editRessource(id) {
  const r = allRessources.find(x => x.id_ressource === id);
  if (!r) return;
  editingRessourceId = id;
  document.getElementById('formTitle').textContent = 'Modifier ressource';
  document.getElementById('btnSubmitLabel').textContent = 'Enregistrer';
  document.getElementById('r_id').value = r.id_ressource;
  document.getElementById('r_id').disabled = true;
  document.getElementById('r_nom').value = r.Nom;
  document.getElementById('r_type').value = r.Type;
  document.getElementById('r_statut').value = normalizeStatut(r.Statut);
  document.getElementById('r_localisation').value = r.Localisation || '';
  document.getElementById('r_maint').value = r.Dernier_Maintenence || '';
}

async function deleteRessource(id) {
  if (!confirm(`Supprimer "${id}" ?`)) return;
  const r = await postData(CRUD_RESSOURCE, { action: 'delete', id_ressource: id });
  if (r.success) { showToast(r.message, 'success'); loadRessources(); loadPannes(); }
  else { showToast(r.error, 'error'); }
}

function resetForm() {
  editingRessourceId = null;
  document.getElementById('formTitle').textContent = 'Ajouter une ressource';
  document.getElementById('btnSubmitLabel').textContent = 'Ajouter ressource';
  document.getElementById('r_id').value = '';
  document.getElementById('r_id').disabled = false;
  document.getElementById('r_nom').value = '';
  document.getElementById('r_type').value = '';
  document.getElementById('r_statut').value = '';
  document.getElementById('r_localisation').value = '';
  document.getElementById('r_maint').value = new Date().toISOString().split('T')[0];
  document.getElementById('msg_form_global').style.display = 'none';
  document.querySelectorAll('.field-msg').forEach(el => el.classList.remove('visible'));
  document.querySelectorAll('.form-control').forEach(el => el.classList.remove('error'));
}

async function loadPannes() {
  try {
    const r = await postData(CRUD_PANNE, { action: 'getAll' });
    if (r.success) { allPannes = r.data; updateStats(); renderPannesTable(); }
  } catch (e) { document.getElementById('pannesTableBody').innerHTML = '<td><td colspan="7" class="no-data">Erreur chargement</td></tr>'; }
}

function renderPannesTable() {
  const search = document.getElementById('searchPanne').value.toLowerCase();
  const filterStatut = document.getElementById('filterPanneStatut').value;
  const filtered = allPannes.filter(p => {
    const matchSearch = !search || (p.id_Panne || '').toLowerCase().includes(search) || (p.id_ressource || '').toLowerCase().includes(search) || (p.ressource_nom || '').toLowerCase().includes(search);
    return matchSearch && (!filterStatut || p.statut === filterStatut);
  });
  const total = filtered.length;
  const pages = Math.max(1, Math.ceil(total / PAGE_SIZE));
  if (currentPagePanne > pages) currentPagePanne = pages;
  const slice = filtered.slice((currentPagePanne - 1) * PAGE_SIZE, currentPagePanne * PAGE_SIZE);
  document.getElementById('paginCountPanne').textContent = `Affichage de ${slice.length} sur ${total}`;
  document.getElementById('pageInfoPanne').textContent = `${currentPagePanne} / ${pages}`;
  const tbody = document.getElementById('pannesTableBody');
  if (!slice.length) { tbody.innerHTML = '<tr><td colspan="7" class="no-data">Aucune panne</td></tr>'; return; }
  const statutClass = { 'en_cours': 'badge-en_cours', 'résolue': 'badge-résolue', 'en_attente': 'badge-en_attente' };
  tbody.innerHTML = slice.map(p => `<tr><td class="id-cell">${escapeHtml(p.id_Panne)}</td><td>${p.date_de_panne || '-'}</td><td>${p.date_de_reparation || '-'}</td><td><span class="badge ${statutClass[p.statut]}">${escapeHtml(p.statut)}</span></td><td>${escapeHtml(p.id_ressource)} - ${escapeHtml(p.ressource_nom) || '-'}</td><td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis;">${escapeHtml(p.description)}</td><td><div class="actions-cell"><button class="icon-btn" onclick="editPanne('${escapeHtml(p.id_Panne)}')" title="Modifier"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></button><button class="icon-btn del" onclick="deletePanne('${escapeHtml(p.id_Panne)}')" title="Supprimer"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><path d="M10 11v6M14 11v6M9 6V4h6v2"/></svg></button><button class="icon-btn ai" onclick="openChatbot('${escapeHtml(p.id_Panne)}')" title="Diagnostic IA Gemini"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 2a10 10 0 0 1 10 10c0 5.5-4.5 10-10 10S2 17.5 2 12 6.5 2 12 2z"/><path d="M12 6v6l4 2"/><path d="M8 11h.01M16 11h.01"/></svg></button></div></td></tr>`).join('');
}

function prevPagePanne() { if (currentPagePanne > 1) { currentPagePanne--; renderPannesTable(); } }
function nextPagePanne() { currentPagePanne++; renderPannesTable(); }

function validatePanne() {
  let ok = true;
  const id = document.getElementById('p_id'), ressource = document.getElementById('p_ressource'), datePanne = document.getElementById('p_date_panne'), statut = document.getElementById('p_statut'), desc = document.getElementById('p_description');
  document.querySelectorAll('.field-msg').forEach(el => el.classList.remove('visible'));
  document.querySelectorAll('.form-control').forEach(el => el.classList.remove('error'));
  if (!id.value.trim()) { showFieldError('p_id', "ID obligatoire"); ok = false; }
  if (!ressource.value) { showFieldError('p_ressource', "Choisir ressource"); ok = false; }
  if (!datePanne.value) { showFieldError('p_date_panne', "Date obligatoire"); ok = false; }
  if (!statut.value) { showFieldError('p_statut', "Statut obligatoire"); ok = false; }
  if (!desc.value.trim()) { showFieldError('p_description', "Description obligatoire"); ok = false; }
  return ok;
}

async function submitPanne() {
  if (!validatePanne()) return;
  const data = { action: editingPanneId ? 'update' : 'add', id_Panne: document.getElementById('p_id').value.trim(), id_ressource: document.getElementById('p_ressource').value, date_de_panne: document.getElementById('p_date_panne').value, date_de_reparation: document.getElementById('p_date_rep').value || null, statut: document.getElementById('p_statut').value, description: document.getElementById('p_description').value.trim() };
  try {
    const r = await postData(CRUD_PANNE, data);
    if (r.success) { showToast(r.message, 'success'); resetPanneForm(); loadPannes(); }
    else { document.getElementById('msg_panne_form_global').textContent = r.error; document.getElementById('msg_panne_form_global').style.display = 'block'; }
  } catch (e) { document.getElementById('msg_panne_form_global').textContent = 'Erreur réseau'; document.getElementById('msg_panne_form_global').style.display = 'block'; }
}

function editPanne(id) {
  const p = allPannes.find(x => x.id_Panne === id);
  if (!p) return;
  editingPanneId = id;
  document.getElementById('panneFormTitle').textContent = 'Modifier panne';
  document.getElementById('panneSubmitLabel').textContent = 'Enregistrer';
  document.getElementById('p_id').value = p.id_Panne;
  document.getElementById('p_id').disabled = true;
  document.getElementById('p_ressource').value = p.id_ressource;
  document.getElementById('p_date_panne').value = p.date_de_panne;
  document.getElementById('p_date_rep').value = p.date_de_reparation || '';
  document.getElementById('p_statut').value = p.statut;
  document.getElementById('p_description').value = p.description;
}

async function deletePanne(id) {
  if (!confirm(`Supprimer la panne "${id}" ?`)) return;
  const r = await postData(CRUD_PANNE, { action: 'delete', id_Panne: id });
  if (r.success) { showToast(r.message, 'success'); loadPannes(); }
  else { showToast(r.error, 'error'); }
}

function resetPanneForm() {
  editingPanneId = null;
  document.getElementById('panneFormTitle').textContent = 'Déclarer une panne';
  document.getElementById('panneSubmitLabel').textContent = 'Déclarer panne';
  document.getElementById('p_id').value = '';
  document.getElementById('p_id').disabled = false;
  document.getElementById('p_ressource').value = '';
  document.getElementById('p_date_panne').value = new Date().toISOString().split('T')[0];
  document.getElementById('p_date_rep').value = '';
  document.getElementById('p_statut').value = '';
  document.getElementById('p_description').value = '';
  document.getElementById('msg_panne_form_global').style.display = 'none';
  document.querySelectorAll('.field-msg').forEach(el => el.classList.remove('visible'));
  document.querySelectorAll('.form-control').forEach(el => el.classList.remove('error'));
}

function exportRessourcesCSV() {
  const headers = ['ID', 'Nom', 'Type', 'Statut', 'Localisation', 'Dernière Maintenance'];
  const rows = allRessources.map(r => [r.id_ressource, r.Nom, r.Type, r.Statut, r.Localisation || '', r.Dernier_Maintenence || '']);
  const csv = [headers, ...rows].map(r => r.map(c => `"${String(c).replace(/"/g, '""')}"`).join(',')).join('\n');
  const blob = new Blob([csv], { type: 'text/csv' });
  const a = document.createElement('a'); a.href = URL.createObjectURL(blob); a.download = `ressources_${new Date().toISOString().slice(0,19).replace(/:/g, '-')}.csv`; a.click();
  showToast("Export CSV réussi", "success");
}

function exportPannesCSV() {
  const headers = ['ID', 'Date Panne', 'Date Réparation', 'Statut', 'ID Ressource', 'Nom Ressource', 'Description'];
  const rows = allPannes.map(p => [p.id_Panne, p.date_de_panne || '', p.date_de_reparation || '', p.statut, p.id_ressource || '', p.ressource_nom || '', p.description || '']);
  const csv = [headers, ...rows].map(r => r.map(c => `"${String(c).replace(/"/g, '""')}"`).join(',')).join('\n');
  const blob = new Blob([csv], { type: 'text/csv' });
  const a = document.createElement('a'); a.href = URL.createObjectURL(blob); a.download = `pannes_${new Date().toISOString().slice(0,19).replace(/:/g, '-')}.csv`; a.click();
  showToast("Export CSV réussi", "success");
}

function exportRessourcesPDF() {
  if (allRessources.length === 0) { showToast("Aucune ressource", "error"); return; }
  const { jsPDF } = window.jspdf;
  const doc = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });
  doc.setFontSize(18); doc.setFont("helvetica", "bold"); doc.text("Ressources - JumeauNum", 14, 15);
  doc.setFontSize(10); doc.text(`Exporté le : ${new Date().toLocaleString()}`, 14, 25);
  const headers = [["ID", "Nom", "Type", "Statut", "Localisation", "Dernière Maintenance"]];
  const data = allRessources.map(r => [r.id_ressource || '', r.Nom || '', r.Type || '', r.Statut || '', r.Localisation || '-', r.Dernier_Maintenence || '-']);
  doc.autoTable({ head: headers, body: data, startY: 32, theme: 'striped', headStyles: { fillColor: [37, 99, 235], textColor: 255 } });
  doc.save(`ressources_${new Date().toISOString().slice(0,19).replace(/:/g, '-')}.pdf`);
  showToast("Export PDF réussi", "success");
}

function exportPannesPDF() {
  if (allPannes.length === 0) { showToast("Aucune panne", "error"); return; }
  const { jsPDF } = window.jspdf;
  const doc = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });
  doc.setFontSize(18); doc.setFont("helvetica", "bold"); doc.text("Pannes - JumeauNum", 14, 15);
  doc.setFontSize(10); doc.text(`Exporté le : ${new Date().toLocaleString()}`, 14, 25);
  const headers = [["ID", "Date panne", "Date réparation", "Statut", "Ressource", "Description"]];
  const data = allPannes.map(p => [p.id_Panne || '', p.date_de_panne || '-', p.date_de_reparation || '-', p.statut || '', `${p.id_ressource || ''} - ${p.ressource_nom || ''}`, p.description || '']);
  doc.autoTable({ head: headers, body: data, startY: 32, theme: 'striped', headStyles: { fillColor: [37, 99, 235], textColor: 255 } });
  doc.save(`pannes_${new Date().toISOString().slice(0,19).replace(/:/g, '-')}.pdf`);
  showToast("Export PDF réussi", "success");
}

function switchTab(tab) {
  document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
  document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
  if (tab === 'ressources') { document.querySelectorAll('.tab-btn')[0].classList.add('active'); document.getElementById('tab-ressources').classList.add('active'); }
  else { document.querySelectorAll('.tab-btn')[1].classList.add('active'); document.getElementById('tab-pannes').classList.add('active'); loadPannes(); }
}

function escapeHtml(str) { if (!str) return ''; return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;'); }

document.getElementById('chatbotModal').addEventListener('click', function(e) { if (e.target === this) closeChatbotModal(); });
</script>
</body>
</html>