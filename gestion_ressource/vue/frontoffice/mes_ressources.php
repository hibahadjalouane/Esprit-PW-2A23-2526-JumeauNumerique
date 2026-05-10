<?php
require_once __DIR__ . '/../../../inc_session.php';
checkSession([1, 2, 3, 4]);
$user = getCurrentUser();
$prenom = $user['Prenom'] ?? $user['prenom'] ?? $user['username'] ?? 'Utilisateur';
$nom    = $user['Nom']    ?? $user['nom']    ?? '';
$role   = (int)($user['id_role'] ?? 0);
$initial = strtoupper(substr($prenom, 0, 1));
$roleLabel = match ($role) {
    1 => 'Patient', 2 => 'Admin', 3 => 'Médecin', 4 => 'Super Admin', default => 'Utilisateur'
};
$base = '/Esprit-PW-2A23-2526-JumeauNumerique';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>JumeauNum – Gestion des Ressources</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<!-- Font Awesome 6 (CDN fonctionnel) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
  :root {
    --primary-blue: #2563EB;
    --dark-teal:    #14B8A6;
    --slate-gray:   #1F2937;
    --soft-blue:    #8B5CF6;
    --bg-main:      #f8f9ff;
    --surface:      #ffffff;
    --border:       #e5e7eb;
    --muted:        #64748b;
    --green:        #16a34a;
    --green-lt:     #dcfce7;
    --red:          #dc2626;
    --red-lt:       #fee2e2;
    --orange:       #d97706;
    --orange-lt:    #fef3c7;
    --text-color:   #1F2937;
  }

  body.dark-mode {
    --primary-blue: #60a5fa;
    --dark-teal: #2dd4bf;
    --slate-gray: #f1f5f9;
    --bg-main: #0f172a;
    --surface: #1e293b;
    --border: #334155;
    --muted: #94a3b8;
    --green-lt: #064e3b;
    --red-lt: #7f1d1d;
    --orange-lt: #78350f;
    --text-color: #f1f5f9;
  }

  body {
    font-family: 'Inter', sans-serif;
    background-color: var(--bg-main);
    color: var(--text-color);
    transition: all 0.3s ease;
  }

  body.dark-mode .resource-card,
  body.dark-mode .stat-card,
  body.dark-mode .search-wrapper,
  body.dark-mode .filter-btn {
    background-color: var(--surface);
    color: var(--text-color);
  }

  body.dark-mode .text-muted,
  body.dark-mode .resource-info,
  body.dark-mode .resource-type {
    color: #94a3b8 !important;
  }

  body.dark-mode .bg-light {
    background-color: #0f172a !important;
  }

  body.dark-mode .border-top,
  body.dark-mode .border-bottom {
    border-color: #334155 !important;
  }

  h1, h2, h3, h4, .navbar-brand { font-family: 'Manrope', sans-serif; font-weight: 700; }

  /* Navbar */
  .navbar { background-color: var(--surface)!important; border-bottom: 1px solid var(--border); padding: .85rem 0; }
  .nav-link { font-weight: 500; color: var(--text-color)!important; margin: 0 6px; transition: color .3s; }
  .nav-link:hover, .nav-link.active-page { color: var(--primary-blue)!important; }
  
  .welcome-pill { display: flex; align-items: center; gap: 8px; background: rgba(37,99,235,.07); border: 1px solid rgba(37,99,235,.15); padding: 5px 14px 5px 6px; border-radius: 50px; font-size: .82rem; font-weight: 600; color: var(--primary-blue); }
  .welcome-pill .avatar { width: 28px; height: 28px; border-radius: 50%; background: var(--primary-blue); color: white; display: flex; align-items: center; justify-content: center; font-size: .72rem; font-weight: 700; }
  
  .btn-logout { background: none; border: 1.5px solid var(--border); padding: .4rem 1.1rem; border-radius: 10px; font-weight: 600; font-size: .85rem; color: var(--text-color); cursor: pointer; transition: all .2s; display: flex; align-items: center; gap: 6px; text-decoration: none; }
  .btn-logout:hover { border-color: var(--red); color: var(--red); background: var(--red-lt); }

  .theme-toggle {
    width: 38px;
    height: 38px;
    border-radius: 40px;
    background: var(--surface);
    border: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.25s ease;
    color: var(--text-color);
  }
  .theme-toggle:hover { background: var(--primary-blue); color: white; transform: scale(1.05); }

  .page-content { padding: 30px 0 60px; }

  /* Statistiques */
  .stat-card {
    background: var(--surface);
    border-radius: 16px;
    padding: 20px;
    text-align: center;
    border: 1px solid var(--border);
    transition: all 0.3s ease;
  }
  .stat-card:hover { transform: translateY(-3px); box-shadow: 0 10px 25px -8px rgba(0,0,0,0.1); }
  .stat-number { font-size: 2rem; font-weight: 800; font-family: 'Manrope', sans-serif; }
  .stat-label { font-size: 0.75rem; color: var(--muted); margin-top: 5px; text-transform: uppercase; letter-spacing: 0.5px; }
  .stat-icon { width: 40px; height: 40px; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin: 0 auto 10px; }

  /* Barre de recherche */
  .search-wrapper {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 50px;
    padding: 5px 15px;
    display: flex;
    align-items: center;
    gap: 10px;
    flex: 1;
    max-width: 350px;
  }
  .search-wrapper i { color: var(--muted); font-size: 14px; }
  .search-wrapper input {
    flex: 1;
    border: none;
    background: transparent;
    padding: 10px 0;
    font-size: 0.85rem;
    outline: none;
    color: var(--text-color);
  }
  .search-wrapper input::placeholder { color: var(--muted); }

  /* Filtres */
  .filter-group { display: flex; gap: 10px; flex-wrap: wrap; }
  .filter-btn {
    background: var(--surface);
    border: 1px solid var(--border);
    padding: 8px 18px;
    border-radius: 40px;
    font-size: 0.8rem;
    font-weight: 500;
    color: var(--text-color);
    cursor: pointer;
    transition: all 0.2s;
  }
  .filter-btn.active {
    background: var(--primary-blue);
    color: white;
    border-color: var(--primary-blue);
  }
  .filter-btn:hover:not(.active) { background: rgba(37,99,235,0.1); }

  /* Cartes ressources */
  .resource-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; margin-top: 25px; }
  .resource-card {
    background: var(--surface);
    border-radius: 20px;
    border: 1px solid var(--border);
    padding: 20px;
    transition: all 0.3s ease;
    cursor: pointer;
  }
  .resource-card:hover { transform: translateY(-5px); box-shadow: 0 20px 25px -12px rgba(0,0,0,0.15); }
  
  .status-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 12px;
    border-radius: 30px;
    font-size: 0.7rem;
    font-weight: 600;
  }
  .status-disponible { background: #dcfce7; color: #166534; }
  .status-occupe { background: #fef3c7; color: #92400e; }
  .status-maintenance { background: #fee2e2; color: #991b1b; }
  
  body.dark-mode .status-disponible { background: #064e3b; color: #4ade80; }
  body.dark-mode .status-occupe { background: #78350f; color: #fbbf24; }
  body.dark-mode .status-maintenance { background: #7f1d1d; color: #fca5a5; }

  /* Icônes des ressources */
  .resource-icon {
    width: 50px;
    height: 50px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  .resource-icon.lit { background: #8b5cf6; color: white; }
  .resource-icon.irm { background: #2563eb; color: white; }
  .resource-icon.ambulance { background: #14b8a6; color: white; }
  .resource-icon.equipement { background: #f59e0b; color: white; }
  .resource-icon.dialyse { background: #ec4899; color: white; }
  .resource-icon.default { background: #6b7280; color: white; }
  
  .resource-icon i { font-size: 24px; }

  .resource-title { font-size: 1.1rem; font-weight: 700; margin-bottom: 5px; }
  .resource-type { font-size: 0.75rem; color: var(--muted); margin-bottom: 12px; }
  .resource-info { display: flex; align-items: center; gap: 8px; font-size: 0.75rem; color: var(--muted); margin-bottom: 8px; }
  .resource-info i { width: 18px; font-size: 12px; color: var(--muted); }

  .empty-state { text-align: center; padding: 60px 20px; color: var(--muted); }
  .empty-state i { font-size: 48px; opacity: 0.3; margin-bottom: 15px; }

  .modal-custom { background: var(--surface); border-radius: 24px; max-width: 500px; width: 90%; }
  .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px); z-index: 1000; display: none; align-items: center; justify-content: center; }
  .modal-overlay.open { display: flex; }
  
  body.dark-mode .modal-custom { background: #1e293b; color: #f1f5f9; }
</style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg sticky-top">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center gap-2 fw-bold fs-5" href="<?= $base ?>/bord.php">
      <i class="fas fa-hospital-user text-primary"></i> JumeauNum
    </a>
    <button class="navbar-toggler" data-bs-target="#navbarNav" data-bs-toggle="collapse" type="button">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav mx-auto">
        <li class="nav-item"><a class="nav-link" href="<?= $base ?>/bord.php"><i class="fas fa-home me-1"></i> Accueil</a></li>
        <li class="nav-item"><a class="nav-link active-page" href="#"><i class="fas fa-microscope me-1"></i> Ressources</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= $base ?>/gestion_paiement/vue/frontoffice/mes_tickets.php"><i class="fas fa-ticket-alt me-1"></i> Tickets</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= $base ?>/gestion_paiement/vue/frontoffice/mes_factures.php"><i class="fas fa-receipt me-1"></i> Factures</a></li>
      </ul>
      <div class="d-flex align-items-center gap-3">
        <button class="theme-toggle" id="themeToggle">
          <i class="fas fa-moon" id="themeIcon"></i>
        </button>
        <div class="welcome-pill">
          <div class="avatar">AM</div>
          Ahmed
        </div>
        <a href="<?= $base ?>/gestion_user/controleur/frontoffice/logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i></a>
      </div>
    </div>
  </div>
</nav>

<div class="page-content">
  <div class="container">

    <!-- Statistiques -->
    <div class="row g-4 mb-5">
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="stat-icon mx-auto bg-primary bg-opacity-10"><i class="fas fa-microscope text-primary fs-4"></i></div>
          <div class="stat-number" id="totalCount">0</div>
          <div class="stat-label">Total ressources</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="stat-icon mx-auto bg-success bg-opacity-10"><i class="fas fa-check-circle text-success fs-4"></i></div>
          <div class="stat-number text-success" id="disponibleCount">0</div>
          <div class="stat-label">Disponibles</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="stat-icon mx-auto bg-warning bg-opacity-10"><i class="fas fa-procedures text-warning fs-4"></i></div>
          <div class="stat-number text-warning" id="occupeCount">0</div>
          <div class="stat-label">Occupées</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="stat-icon mx-auto bg-danger bg-opacity-10"><i class="fas fa-tools text-danger fs-4"></i></div>
          <div class="stat-number text-danger" id="maintenanceCount">0</div>
          <div class="stat-label">Maintenance</div>
        </div>
      </div>
    </div>

    <!-- Recherche et Filtres -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3 mb-4">
      <div class="search-wrapper w-100 w-md-auto">
        <i class="fas fa-search"></i>
        <input type="text" id="searchInput" placeholder="Rechercher par ID, nom, type, localisation...">
      </div>
      <div class="filter-group">
        <button class="filter-btn active" data-filter="all" onclick="filterByStatus('all')"><i class="fas fa-th-large me-1"></i>Toutes</button>
        <button class="filter-btn" data-filter="disponible" onclick="filterByStatus('disponible')"><i class="fas fa-check-circle me-1 text-success"></i>Disponibles</button>
        <button class="filter-btn" data-filter="occupé" onclick="filterByStatus('occupé')"><i class="fas fa-procedures me-1 text-warning"></i>Occupées</button>
        <button class="filter-btn" data-filter="maintenance" onclick="filterByStatus('maintenance')"><i class="fas fa-tools me-1 text-danger"></i>Maintenance</button>
      </div>
    </div>

    <!-- Grille Ressources -->
    <div id="resourcesGrid" class="resource-grid">
      <div class="text-center py-5">
        <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
        <p class="mt-3">Chargement des ressources...</p>
      </div>
    </div>

  </div>
</div>

<!-- MODAL -->
<div id="resourceModal" class="modal-overlay">
  <div class="modal-custom">
    <div class="p-4 border-bottom d-flex justify-content-between align-items-center">
      <h5 class="fw-bold m-0" id="modalTitle">Détails ressource</h5>
      <button onclick="closeModal()" class="btn-close" style="background:none; border:none; font-size:24px; cursor:pointer;">&times;</button>
    </div>
    <div class="p-4" id="modalContent"></div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Configuration API
const API_BASE = '../../controleur/backoffice/';
const API_RESSOURCE = API_BASE + 'ressource_crud.php';

let allRessources = [];
let currentFilter = 'all';
let searchTerm = '';

// Mode Nuit
function toggleDarkMode() {
  document.body.classList.toggle('dark-mode');
  const icon = document.getElementById('themeIcon');
  if (document.body.classList.contains('dark-mode')) {
    icon.classList.remove('fa-moon');
    icon.classList.add('fa-sun');
    localStorage.setItem('theme', 'dark');
  } else {
    icon.classList.remove('fa-sun');
    icon.classList.add('fa-moon');
    localStorage.setItem('theme', 'light');
  }
}

function initTheme() {
  if (localStorage.getItem('theme') === 'dark') {
    document.body.classList.add('dark-mode');
    document.getElementById('themeIcon').classList.remove('fa-moon');
    document.getElementById('themeIcon').classList.add('fa-sun');
  }
}

document.getElementById('themeToggle').addEventListener('click', toggleDarkMode);

// Initialisation
document.addEventListener('DOMContentLoaded', () => {
  initTheme();
  loadRessources();
  document.getElementById('searchInput').addEventListener('keyup', (e) => {
    searchTerm = e.target.value.toLowerCase();
    renderResources();
  });
});

async function callAPI(url, data) {
  try {
    const formData = new URLSearchParams();
    for (let key in data) if (data[key]) formData.append(key, data[key]);
    const response = await fetch(url, { method: 'POST', body: formData });
    return await response.json();
  } catch (error) {
    return { success: false, error: error.message };
  }
}

async function loadRessources() {
  try {
    const result = await callAPI(API_RESSOURCE, { action: 'getAll' });
    if (result.success && result.data) {
      allRessources = result.data;
      updateStats();
      renderResources();
    } else {
      document.getElementById('resourcesGrid').innerHTML = '<div class="empty-state"><i class="fas fa-database"></i><p>Erreur de chargement</p></div>';
    }
  } catch (error) {
    document.getElementById('resourcesGrid').innerHTML = '<div class="empty-state"><i class="fas fa-wifi"></i><p>Erreur de connexion</p></div>';
  }
}

function updateStats() {
  document.getElementById('totalCount').textContent = allRessources.length;
  document.getElementById('disponibleCount').textContent = allRessources.filter(r => r.Statut === 'disponible').length;
  document.getElementById('occupeCount').textContent = allRessources.filter(r => r.Statut === 'occupé').length;
  document.getElementById('maintenanceCount').textContent = allRessources.filter(r => r.Statut === 'maintenance').length;
}

function filterByStatus(status) {
  currentFilter = status;
  document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
  const targetBtn = Array.from(document.querySelectorAll('.filter-btn')).find(btn => btn.getAttribute('data-filter') === status);
  if (targetBtn) targetBtn.classList.add('active');
  renderResources();
}

// Fonction pour obtenir l'icône et la classe CSS selon le type de ressource
function getResourceIconAndClass(type) {
  const typeLower = (type || '').toLowerCase();
  
  if (typeLower.includes('lit') || typeLower === 'lit') {
    return { icon: 'fas fa-bed', cssClass: 'lit' };
  }
  if (typeLower.includes('irm') || typeLower.includes('scanner') || typeLower.includes('imagerie') || typeLower === 'irm' || typeLower === 'scanner') {
    return { icon: 'fas fa-x-ray', cssClass: 'irm' };
  }
  if (typeLower.includes('ambulance') || typeLower === 'ambulance') {
    return { icon: 'fas fa-ambulance', cssClass: 'ambulance' };
  }
  if (typeLower.includes('dialyse') || typeLower === 'dialyse') {
    return { icon: 'fas fa-tint', cssClass: 'dialyse' };
  }
  if (typeLower.includes('équipement') || typeLower.includes('equipement') || typeLower.includes('moniteur') || typeLower.includes('respirateur')) {
    return { icon: 'fas fa-microscope', cssClass: 'equipement' };
  }
  return { icon: 'fas fa-microscope', cssClass: 'default' };
}

function renderResources() {
  let filtered = [...allRessources];
  
  if (currentFilter !== 'all') {
    filtered = filtered.filter(r => r.Statut === currentFilter);
  }
  
  if (searchTerm) {
    filtered = filtered.filter(r => 
      (r.id_ressource || '').toLowerCase().includes(searchTerm) ||
      (r.Nom || '').toLowerCase().includes(searchTerm) ||
      (r.Type || '').toLowerCase().includes(searchTerm) ||
      (r.Localisation || '').toLowerCase().includes(searchTerm)
    );
  }
  
  const container = document.getElementById('resourcesGrid');
  
  if (filtered.length === 0) {
    container.innerHTML = `<div class="empty-state"><i class="fas fa-search"></i><p>Aucune ressource trouvée</p></div>`;
    return;
  }
  
  const formatDate = (dateStr) => {
    if (!dateStr) return 'Non effectuée';
    const date = new Date(dateStr);
    return date.toLocaleDateString('fr-FR');
  };
  
  container.innerHTML = filtered.map(r => {
    const statusText = r.Statut === 'disponible' ? 'Disponible' : (r.Statut === 'occupé' ? 'Occupé' : 'Maintenance');
    const statusIcon = r.Statut === 'disponible' ? 'fa-check-circle' : (r.Statut === 'occupé' ? 'fa-procedures' : 'fa-tools');
    const statusClass = `status-${r.Statut}`;
    
    const { icon, cssClass } = getResourceIconAndClass(r.Type);
    
    return `
      <div class="resource-card" onclick="showDetails('${r.id_ressource}')">
        <div class="d-flex justify-content-between align-items-start mb-3">
          <div class="resource-icon ${cssClass}">
            <i class="${icon}"></i>
          </div>
          <span class="status-badge ${statusClass}"><i class="fas ${statusIcon} me-1"></i>${statusText}</span>
        </div>
        <h5 class="resource-title">${escapeHtml(r.Nom || 'Sans nom')}</h5>
        <p class="resource-type">${escapeHtml(r.Type || 'Type non spécifié')}</p>
        <div class="resource-info"><i class="fas fa-map-marker-alt"></i>${escapeHtml(r.Localisation || 'Localisation non définie')}</div>
        <div class="resource-info"><i class="fas fa-calendar-alt"></i>Dernière maintenance: ${formatDate(r.Dernier_Maintenence)}</div>
        <div class="mt-3 pt-2 border-top d-flex justify-content-between">
          <span class="text-primary small fw-medium">Voir détails <i class="fas fa-arrow-right ms-1"></i></span>
          <button onclick="event.stopPropagation(); showReport('${r.id_ressource}')" class="btn btn-sm btn-outline-warning rounded-pill px-3">
            <i class="fas fa-flag me-1"></i>Signaler
          </button>
        </div>
      </div>
    `;
  }).join('');
}

async function showDetails(id) {
  const r = allRessources.find(x => x.id_ressource === id);
  if (!r) return;
  
  const statusText = r.Statut === 'disponible' ? 'Disponible' : (r.Statut === 'occupé' ? 'Occupé' : 'Maintenance');
  const statusClass = `status-${r.Statut}`;
  const { icon, cssClass } = getResourceIconAndClass(r.Type);
  
  document.getElementById('modalContent').innerHTML = `
    <div class="text-center mb-4">
      <div class="resource-icon ${cssClass} mx-auto mb-3" style="width:70px;height:70px;">
        <i class="${icon} fs-2"></i>
      </div>
      <h4 class="fw-bold">${escapeHtml(r.Nom)}</h4>
      <p class="text-muted">${escapeHtml(r.Type || 'Type non spécifié')}</p>
      <span class="status-badge ${statusClass} px-3 py-2"><i class="fas ${r.Statut === 'disponible' ? 'fa-check-circle' : (r.Statut === 'occupé' ? 'fa-procedures' : 'fa-tools')} me-1"></i>${statusText}</span>
    </div>
    <div class="bg-light p-3 rounded-3">
      <div class="d-flex mb-2"><i class="fas fa-hashtag text-muted me-3" style="width:20px;"></i><span class="font-monospace">${escapeHtml(r.id_ressource)}</span></div>
      <div class="d-flex mb-2"><i class="fas fa-map-marker-alt text-muted me-3" style="width:20px;"></i><span>${escapeHtml(r.Localisation || 'Non spécifiée')}</span></div>
      <div class="d-flex"><i class="fas fa-calendar-alt text-muted me-3" style="width:20px;"></i><span>Dernière maintenance: ${r.Dernier_Maintenence ? new Date(r.Dernier_Maintenence).toLocaleDateString('fr-FR') : 'Aucune'}</span></div>
    </div>
  `;
  document.getElementById('modalTitle').textContent = 'Détails ressource';
  document.getElementById('resourceModal').classList.add('open');
}

function showReport(id) {
  const r = allRessources.find(x => x.id_ressource === id);
  Swal.fire({
    title: 'Signaler un problème',
    html: `<div class="text-start">
      <p class="mb-3">Ressource: <strong>${escapeHtml(r?.Nom)}</strong></p>
      <input type="text" id="reportTitle" class="form-control mb-3" placeholder="Titre du problème">
      <textarea id="reportDesc" class="form-control" rows="4" placeholder="Description détaillée..."></textarea>
      <select id="reportPriority" class="form-select mt-3">
        <option value="haute">🔴 Haute priorité</option>
        <option value="moyenne" selected>🟠 Moyenne priorité</option>
        <option value="basse">🟢 Basse priorité</option>
      </select>
    </div>`,
    showCancelButton: true,
    confirmButtonText: 'Envoyer',
    cancelButtonText: 'Annuler',
    confirmButtonColor: '#2563EB',
    preConfirm: () => {
      const title = document.getElementById('reportTitle')?.value;
      const desc = document.getElementById('reportDesc')?.value;
      if (!title || !desc) {
        Swal.showValidationMessage('Veuillez remplir tous les champs');
        return false;
      }
      return { title, description: desc };
    }
  }).then(result => {
    if (result.isConfirmed) {
      Swal.fire({ icon: 'success', title: 'Signalement envoyé!', text: 'L\'équipe technique va traiter votre demande.', timer: 3000, showConfirmButton: false });
    }
  });
}

function closeModal() {
  document.getElementById('resourceModal').classList.remove('open');
}

function escapeHtml(str) {
  if (!str) return '';
  return String(str).replace(/[&<>]/g, function(m) {
    if (m === '&') return '&amp;';
    if (m === '<') return '&lt;';
    if (m === '>') return '&gt;';
    return m;
  });
}

setInterval(() => loadRessources(), 30000);
</script>
</body>
</html>