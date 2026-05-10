<?php
/**
 * mes_tickets.php
 * Chemin : gestion_paiement/vue/frontoffice/mes_tickets.php
 *
 * Affiche tous les tickets numériques du patient connecté.
 */

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Sat, 01 Jan 2000 00:00:00 GMT');

require_once __DIR__ . '/../../../inc_session.php';
checkSession([1]);
$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>JumeauNum – Mes Tickets</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<style>
  :root {
    --primary-blue: #2563EB;
    --dark-teal:    #14B8A6;
    --slate-gray:   #1F2937;
    --bg-main:      #f8f9ff;
    --border:       #e5e7eb;
    --muted:        #64748b;
    --green:        #16a34a;
    --green-lt:     #dcfce7;
    --red:          #dc2626;
    --red-lt:       #fee2e2;
    --orange:       #d97706;
    --orange-lt:    #fef3c7;
  }
  body { font-family:'Inter',sans-serif; background-color:var(--bg-main); color:var(--slate-gray); }
  h1,h2,h3,.navbar-brand { font-family:'Manrope',sans-serif; font-weight:700; }

  .navbar { background:rgba(255,255,255,0.95)!important; backdrop-filter:blur(10px); border-bottom:1px solid rgba(0,0,0,0.06); padding:.85rem 0; }
  .nav-link { font-weight:500; color:var(--slate-gray)!important; margin:0 6px; transition:color .3s; }
  .nav-link:hover,.nav-link.active-page { color:var(--primary-blue)!important; }
  .welcome-pill { display:flex;align-items:center;gap:8px;background:rgba(37,99,235,.07);border:1px solid rgba(37,99,235,.15);padding:5px 14px 5px 6px;border-radius:50px;font-size:.82rem;font-weight:600;color:var(--primary-blue); }
  .welcome-pill .avatar { width:28px;height:28px;border-radius:50%;background:var(--primary-blue);color:white;display:flex;align-items:center;justify-content:center;font-size:.72rem;font-weight:700; }
  .btn-logout { background:none;border:1.5px solid var(--border);padding:.4rem 1.1rem;border-radius:10px;font-weight:600;font-size:.85rem;color:var(--slate-gray);cursor:pointer;transition:all .2s;display:flex;align-items:center;gap:6px;text-decoration:none; }
  .btn-logout:hover { border-color:var(--red);color:var(--red);background:#fff5f5; }

  .page-content { padding:48px 0 80px; }
  .page-title { font-size:2.2rem;font-weight:800;margin-bottom:6px; }
  .page-subtitle { color:var(--muted);font-size:.95rem; }

  /* Ticket card - inspiré d'un vrai ticket physique */
  .ticket-card {
    background:white;
    border-radius:16px;
    border:1px solid var(--border);
    margin-bottom:20px;
    overflow:hidden;
    display:flex;
    transition:box-shadow .2s, transform .15s;
    position:relative;
  }
  .ticket-card:hover { box-shadow:0 10px 32px rgba(37,99,235,.1); transform:translateY(-2px); }

  /* Bande colorée à gauche selon statut */
  .ticket-strip {
    width:6px;
    flex-shrink:0;
    background:var(--primary-blue);
  }
  .ticket-card.expiré .ticket-strip { background:#9ca3af; }
  .ticket-card.utilisé .ticket-strip { background:var(--orange); }
  .ticket-card.valide .ticket-strip { background:var(--green); }

  .ticket-body { padding:24px 28px; flex:1; }
  .ticket-numero {
    font-family:monospace; font-size:1.4rem; font-weight:700;
    letter-spacing:3px; color:var(--primary-blue);
    background:#eff6ff; display:inline-block;
    padding:6px 16px; border-radius:8px; margin-bottom:12px;
  }
  .ticket-card.expiré .ticket-numero { color:#9ca3af; background:#f3f4f6; }
  .ticket-card.utilisé .ticket-numero { color:var(--orange); background:var(--orange-lt); }
  .ticket-card.valide .ticket-numero { color:var(--green); background:var(--green-lt); }

  .ticket-nom { font-weight:700; font-size:1rem; margin-bottom:8px; }
  .ticket-meta { font-size:.8rem; color:var(--muted); display:flex; flex-wrap:wrap; gap:14px; }
  .ticket-meta .material-symbols-outlined { font-size:14px; vertical-align:middle; }

  /* Badge statut */
  .badge-valide { background:var(--green-lt);color:var(--green);border:1px solid rgba(22,163,74,.2); font-size:.72rem;font-weight:700;padding:5px 14px;border-radius:50px;display:inline-flex;align-items:center;gap:5px; }
  .badge-expire { background:#f3f4f6;color:#9ca3af;border:1px solid #e5e7eb; font-size:.72rem;font-weight:700;padding:5px 14px;border-radius:50px;display:inline-flex;align-items:center;gap:5px; }
  .badge-utilise { background:var(--orange-lt);color:var(--orange);border:1px solid rgba(217,119,6,.2); font-size:.72rem;font-weight:700;padding:5px 14px;border-radius:50px;display:inline-flex;align-items:center;gap:5px; }

  .ticket-montant { font-family:'Manrope',sans-serif;font-size:1.4rem;font-weight:800;color:var(--slate-gray);white-space:nowrap; }

  /* Ligne pointillée entre body et côté droit (effet ticket) */
  .ticket-divider {
    width:1px;
    background:repeating-linear-gradient(to bottom,var(--border) 0,var(--border) 8px,transparent 8px,transparent 16px);
    flex-shrink:0; margin:16px 0;
  }
  .ticket-side { padding:24px 24px; display:flex; flex-direction:column; justify-content:center; align-items:center; gap:8px; min-width:140px; }

  /* État vide */
  .empty-state { text-align:center;padding:72px 24px;color:var(--muted); }
  .empty-state .material-symbols-outlined { font-size:56px;opacity:.25;margin-bottom:16px; }

  /* Skeleton */
  .skeleton { background:linear-gradient(90deg,#f0f0f0 25%,#e8e8e8 50%,#f0f0f0 75%);background-size:200% 100%;animation:shimmer 1.4s infinite;border-radius:8px; }
  @keyframes shimmer { 0%{background-position:200% 0}100%{background-position:-200% 0} }
  .skeleton-ticket { height:120px;margin-bottom:20px;border-radius:16px; }

  .section-label { font-size:1.1rem;font-weight:700;color:var(--slate-gray);display:flex;align-items:center;gap:8px;margin-bottom:20px; }
  .section-label .material-symbols-outlined { font-size:20px;color:var(--muted); }
</style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg sticky-top">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center gap-2"
       href="../../../gestion_user/vue/frontoffice/home.php">
      <span class="fs-5 fw-bold">JumeauNum</span>
    </a>
    <button class="navbar-toggler" data-bs-target="#navbarNav" data-bs-toggle="collapse" type="button">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav mx-auto">
        <li class="nav-item">
          <a class="nav-link" href="mes_factures.php">
            <span class="material-symbols-outlined" style="font-size:16px;vertical-align:middle;">receipt_long</span>
            Mes Factures
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link active-page" href="mes_tickets.php">
            <span class="material-symbols-outlined" style="font-size:16px;vertical-align:middle;">confirmation_number</span>
            Mes Tickets
          </a>
        </li>
      </ul>
      <div class="d-flex align-items-center gap-3">
        <div class="welcome-pill">
          <div class="avatar"><?= strtoupper(mb_substr($user['prenom'],0,1)).strtoupper(mb_substr($user['nom'],0,1)) ?></div>
          <?= htmlspecialchars($user['prenom']) ?>
        </div>
        <a href="../../controleur/frontoffice/logout_paiement.php" class="btn-logout">
          <span class="material-symbols-outlined" style="font-size:16px;">logout</span> Déconnexion
        </a>
      </div>
    </div>
  </div>
</nav>

<!-- CONTENU -->
<div class="page-content">
  <div class="container">
    <div class="mb-5">
      <h1 class="page-title">Mes Tickets Numériques</h1>
      <p class="page-subtitle">Tous vos tickets générés après paiement — valides, utilisés ou expirés.</p>
    </div>

    <!-- Skeletons de chargement -->
    <div id="ticketsContainer">
      <div class="skeleton skeleton-ticket"></div>
      <div class="skeleton skeleton-ticket"></div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', chargerTickets);

async function chargerTickets() {
  try {
    const res  = await fetch('../../controleur/frontoffice/get_tickets.php');
    const data = await res.json();

    if (!data.success) {
      afficherErreur('Impossible de charger les tickets.');
      return;
    }
    afficherTickets(data.tickets);
  } catch (e) {
    afficherErreur('Erreur de connexion au serveur.');
  }
}

function afficherTickets(tickets) {
  const container = document.getElementById('ticketsContainer');

  if (tickets.length === 0) {
    container.innerHTML = `
      <div class="empty-state">
        <span class="material-symbols-outlined">confirmation_number</span>
        <p class="fw-semibold mb-1">Aucun ticket pour le moment</p>
        <p class="small">Vos tickets apparaissent ici après chaque paiement.</p>
        <a href="mes_factures.php" class="btn btn-primary btn-sm mt-3">Voir mes factures</a>
      </div>`;
    return;
  }

  let html = '';
  const maintenant = new Date();

  tickets.forEach(t => {
    const dateCreation   = new Date(t.date_creation);
    const dateExpiration = new Date(t.date_expiration);
    const estExpire      = maintenant > dateExpiration;
    const estUtilise     = t.statut === 'utilise';
    const montant        = parseFloat(t.montant).toLocaleString('fr-TN', {minimumFractionDigits:2});

    // Détermine la classe CSS et le badge
    let classeCard, badge;
    if (estUtilise) {
      classeCard = 'utilisé';
      badge = `<span class="badge-utilise"><span class="material-symbols-outlined" style="font-size:13px;">check_circle</span> Utilisé</span>`;
    } else if (estExpire) {
      classeCard = 'expiré';
      badge = `<span class="badge-expire"><span class="material-symbols-outlined" style="font-size:13px;">schedule</span> Expiré</span>`;
    } else {
      classeCard = 'valide';
      badge = `<span class="badge-valide"><span class="material-symbols-outlined" style="font-size:13px;">verified</span> Valide</span>`;
    }

    const dateCreationStr   = dateCreation.toLocaleDateString('fr-FR', {day:'numeric',month:'long',year:'numeric',hour:'2-digit',minute:'2-digit'});
    const dateExpirationStr = dateExpiration.toLocaleDateString('fr-FR', {day:'numeric',month:'long',year:'numeric'});

    html += `
      <div class="ticket-card ${classeCard}">
        <div class="ticket-strip"></div>
        <div class="ticket-body">
          <div class="ticket-numero">#${t.id_ticket}</div>
          <div class="ticket-nom">${escHtml(t.nom_type)}</div>
          <div class="ticket-meta">
            <span><span class="material-symbols-outlined">calendar_today</span> Émis le ${dateCreationStr}</span>
            <span><span class="material-symbols-outlined">event_busy</span> Expire le ${dateExpirationStr}</span>
            <span><span class="material-symbols-outlined">receipt</span> FAC-${String(t.id_facture).padStart(4,'0')}</span>
          </div>
        </div>
        <div class="ticket-divider"></div>
        <div class="ticket-side">
          <div class="ticket-montant">${montant} DT</div>
          ${badge}
        </div>
      </div>`;
  });

  container.innerHTML = html;
}

function afficherErreur(msg) {
  document.getElementById('ticketsContainer').innerHTML =
    `<div class="empty-state"><span class="material-symbols-outlined">error</span><p>${msg}</p></div>`;
}

function escHtml(str) {
  return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>
</body>
</html>
