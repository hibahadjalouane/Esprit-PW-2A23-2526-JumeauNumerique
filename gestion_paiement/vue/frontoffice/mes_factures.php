<?php
/**
 * mes_factures.php
 * Chemin : projetweb/gestion_des_paiements/vue/frontoffice/mes_factures.php
 */
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Sat, 01 Jan 2000 00:00:00 GMT');

// Remonte de vue/frontoffice → vue → gestion_des_paiements → projetweb
require_once __DIR__ . '/../../../inc_session.php';
checkSession([1]);
$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>JumeauNum – Mes Factures</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
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
  }
  body { font-family:'Inter',sans-serif; background-color:var(--bg-main); color:var(--slate-gray); }
  h1,h2,h3,h4,.navbar-brand { font-family:'Manrope',sans-serif; font-weight:700; }

  /* ── NAVBAR (identique home) ── */
  .navbar { background-color:rgba(255,255,255,0.95)!important; backdrop-filter:blur(10px); border-bottom:1px solid rgba(0,0,0,0.06); padding:.85rem 0; }
  .nav-link { font-weight:500; color:var(--slate-gray)!important; margin:0 6px; transition:color .3s; }
  .nav-link:hover,.nav-link.active-page { color:var(--primary-blue)!important; }
  .welcome-pill { display:flex; align-items:center; gap:8px; background:rgba(37,99,235,.07); border:1px solid rgba(37,99,235,.15); padding:5px 14px 5px 6px; border-radius:50px; font-size:.82rem; font-weight:600; color:var(--primary-blue); }
  .welcome-pill .avatar { width:28px; height:28px; border-radius:50%; background:var(--primary-blue); color:white; display:flex; align-items:center; justify-content:center; font-size:.72rem; font-weight:700; }
  .btn-logout { background:none; border:1.5px solid var(--border); padding:.4rem 1.1rem; border-radius:10px; font-weight:600; font-size:.85rem; color:var(--slate-gray); cursor:pointer; transition:all .2s; display:flex; align-items:center; gap:6px; text-decoration:none; }
  .btn-logout:hover { border-color:var(--red); color:var(--red); background:#fff5f5; }

  /* ── PAGE CONTENT ── */
  .page-content { padding:48px 0 80px; }
  .page-header { margin-bottom:40px; }
  .page-title { font-size:2.2rem; font-weight:800; color:var(--slate-gray); margin-bottom:6px; }
  .page-subtitle { color:var(--muted); font-size:.95rem; }
  .security-badge { display:inline-flex; align-items:center; gap:6px; background:rgba(37,99,235,.05); border:1px solid rgba(37,99,235,.12); padding:6px 14px; border-radius:50px; font-size:.72rem; font-weight:600; color:var(--muted); }
  .security-badge .material-symbols-outlined { font-size:14px; color:var(--green); }

  /* ── SUMMARY CARDS ── */
  .summary-card { background:white; border-radius:20px; padding:28px; border:1px solid var(--border); height:100%; transition:box-shadow .2s; }
  .summary-card:hover { box-shadow:0 8px 32px rgba(37,99,235,.08); }
  .summary-card .card-icon { width:44px; height:44px; border-radius:12px; display:flex; align-items:center; justify-content:center; }
  .summary-card .card-amount { font-size:2.4rem; font-weight:800; font-family:'Manrope',sans-serif; line-height:1.1; margin:12px 0 4px; }
  .summary-card .card-label { font-size:.8rem; color:var(--muted); }

  /* ── SECTION TITLE ── */
  .section-label { font-size:1.1rem; font-weight:700; color:var(--slate-gray); display:flex; align-items:center; gap:8px; margin-bottom:20px; }
  .section-label .material-symbols-outlined { font-size:20px; color:var(--muted); }

  /* ── FACTURE CARD ── */
  .facture-card {
    background:white; border-radius:16px; padding:24px 28px;
    border:1px solid var(--border); margin-bottom:16px;
    position:relative; overflow:hidden;
    transition:box-shadow .2s, transform .15s;
  }
  .facture-card:hover { box-shadow:0 8px 28px rgba(0,0,0,.07); transform:translateY(-2px); }
  .facture-card .accent-bar { position:absolute; left:0; top:0; bottom:0; width:4px; background:var(--primary-blue); border-radius:0 0 0 0; transition:width .2s; }
  .facture-card:hover .accent-bar { width:6px; }
  .facture-card.payee { background:#fafffe; }
  .facture-card.payee .accent-bar { background:var(--green); }
  .facture-card .facture-icon { width:48px; height:48px; border-radius:14px; background:#f0f4ff; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
  .facture-card.payee .facture-icon { background:#f0fdf4; }
  .facture-card .facture-icon .material-symbols-outlined { color:var(--primary-blue); font-size:22px; }
  .facture-card.payee .facture-icon .material-symbols-outlined { color:var(--green); }
  .facture-card .facture-nom { font-weight:700; font-size:1.05rem; color:var(--slate-gray); margin-bottom:4px; }
  .facture-card .facture-meta { font-size:.8rem; color:var(--muted); display:flex; align-items:center; gap:12px; flex-wrap:wrap; }
  .facture-card .facture-meta .material-symbols-outlined { font-size:14px; }
  .facture-card .facture-id { font-family:'DM Mono',monospace; font-size:.75rem; background:#f3f4f6; padding:2px 8px; border-radius:6px; color:var(--muted); }
  .facture-card .facture-montant { font-family:'Manrope',sans-serif; font-size:1.5rem; font-weight:800; color:var(--slate-gray); }
  .facture-card.payee .facture-montant { color:var(--muted); font-size:1.2rem; }
  .badge-statut-non { font-size:.7rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:var(--red); }
  .badge-statut-oui { display:inline-flex; align-items:center; gap:5px; background:var(--green-lt); color:var(--green); border:1px solid rgba(22,163,74,.2); font-size:.72rem; font-weight:700; padding:4px 12px; border-radius:50px; }
  .badge-statut-oui .material-symbols-outlined { font-size:13px; }

  /* Bouton Payer */
  .btn-payer { background:var(--primary-blue); color:white; border:none; padding:.6rem 1.4rem; border-radius:10px; font-weight:600; font-size:.85rem; display:inline-flex; align-items:center; gap:6px; cursor:pointer; transition:background .2s,transform .15s; }
  .btn-payer:hover { background:#1d4ed8; transform:translateY(-1px); }
  .btn-payer .material-symbols-outlined { font-size:16px; }

  /* ── ÉTAT VIDE ── */
  .empty-state { text-align:center; padding:64px 24px; color:var(--muted); }
  .empty-state .material-symbols-outlined { font-size:52px; opacity:.3; margin-bottom:16px; }

  /* Loading skeleton */
  .skeleton { background:linear-gradient(90deg,#f0f0f0 25%,#e0e0e0 50%,#f0f0f0 75%); background-size:200% 100%; animation:shimmer 1.4s infinite; border-radius:8px; }
  @keyframes shimmer { 0%{background-position:200% 0} 100%{background-position:-200% 0} }
  .skeleton-line { height:16px; margin-bottom:10px; }
  .skeleton-card { height:90px; margin-bottom:16px; border-radius:16px; }

  /* ══════════════════════════════════════════════
     CHECKOUT MODAL (overlay sur fond flouté)
  ══════════════════════════════════════════════ */
  #checkoutOverlay {
    display:none; position:fixed; inset:0; z-index:1000;
    background:rgba(22,26,46,.15); backdrop-filter:blur(8px);
    align-items:center; justify-content:center; padding:16px;
  }
  #checkoutOverlay.open { display:flex; animation:fadeIn .2s ease; }
  @keyframes fadeIn { from{opacity:0} to{opacity:1} }

  .checkout-modal {
    background:rgba(255,255,255,.92); backdrop-filter:blur(24px);
    border:1px solid rgba(220,225,240,.8);
    border-radius:24px; width:100%; max-width:460px;
    box-shadow:0 32px 64px -16px rgba(0,30,80,.18);
    overflow:hidden; animation:slideUp .22s ease;
  }
  @keyframes slideUp { from{opacity:0;transform:translateY(20px)} to{opacity:1;transform:translateY(0)} }

  .checkout-header { padding:32px 32px 24px; border-bottom:1px solid #eef0f8; }
  .checkout-title-row { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:6px; }
  .checkout-title { font-family:'Manrope',sans-serif; font-size:1.5rem; font-weight:800; color:var(--slate-gray); }
  .stripe-badge { display:inline-flex; align-items:center; gap:5px; background:white; border:1px solid var(--border); border-radius:10px; padding:5px 10px; font-size:.72rem; font-weight:600; color:var(--muted); }
  .stripe-badge .material-symbols-outlined { font-size:14px; }
  .checkout-desc { font-size:.85rem; color:var(--muted); margin-bottom:12px; }
  .checkout-amount { font-family:'Manrope',sans-serif; font-size:2.8rem; font-weight:800; color:var(--primary-blue); line-height:1; }

  .checkout-body { padding:24px 32px; }
  .checkout-field-label { font-size:.7rem; font-weight:700; text-transform:uppercase; letter-spacing:.08em; color:var(--muted); margin-bottom:6px; display:block; }
  .checkout-input-group { background:#f3f4f8; border-radius:12px 12px 0 0; border-bottom:2px solid var(--border); transition:border-color .2s; position:relative; }
  .checkout-input-group:focus-within { border-bottom-color:var(--primary-blue); background:#f8f9fd; }
  .checkout-input-group .icon { position:absolute; left:14px; top:50%; transform:translateY(-50%); font-size:18px; color:#9ca3af; }
  .checkout-input { width:100%; background:transparent; border:none; outline:none; padding:14px 14px 14px 42px; font-family:'Inter',sans-serif; font-size:.9rem; color:var(--slate-gray); }
  .checkout-input::placeholder { color:#b0b8cc; }
  .checkout-row { display:flex; }
  .checkout-row .checkout-input-group:first-child { flex:1; border-radius:0; border-right:1px solid var(--border); }
  .checkout-row .checkout-input-group:last-child { flex:1; border-radius:0; }
  .checkout-row .checkout-input { padding-left:14px; }
  .checkout-row .checkout-input-group .icon-right { position:absolute; right:12px; top:50%; transform:translateY(-50%); font-size:16px; color:#9ca3af; }
  .checkout-name-group { background:#f3f4f8; border-radius:12px 12px 0 0; border-bottom:2px solid var(--border); transition:border-color .2s; }
  .checkout-name-group:focus-within { border-bottom-color:var(--primary-blue); }
  .checkout-name-input { width:100%; background:transparent; border:none; outline:none; padding:14px; font-family:'Inter',sans-serif; font-size:.9rem; color:var(--slate-gray); }
  .checkout-name-input::placeholder { color:#b0b8cc; }

  .checkout-footer { padding:8px 32px 32px; background:rgba(255,255,255,.6); }
  .btn-checkout {
    width:100%; padding:16px; border:none; border-radius:14px;
    background:var(--primary-blue); color:white;
    font-family:'Manrope',sans-serif; font-size:1.05rem; font-weight:700;
    cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px;
    box-shadow:0 8px 24px -8px rgba(37,99,235,.5);
    transition:all .25s;
  }
  .btn-checkout:hover { background:#1d4ed8; transform:translateY(-2px); box-shadow:0 12px 28px -8px rgba(37,99,235,.6); }
  .encryption-note { display:flex; align-items:center; justify-content:center; gap:6px; margin-top:16px; font-size:.75rem; color:var(--muted); }
  .encryption-note .material-symbols-outlined { font-size:14px; color:var(--green); }
  .btn-close-checkout { background:none; border:none; cursor:pointer; color:var(--muted); padding:4px; display:flex; align-items:center; transition:color .2s; }
  .btn-close-checkout:hover { color:var(--red); }

  /* Success screen inside modal */
  #checkoutSuccess { display:none; text-align:center; padding:40px 32px; }
  .success-circle { width:72px; height:72px; background:var(--green-lt); border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 20px; }
  .success-circle .material-symbols-outlined { font-size:36px; color:var(--green); }
</style>
</head>
<body>

<!-- ══ NAVBAR (même que home, version allégée) ══ -->
<nav class="navbar navbar-expand-lg sticky-top">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center gap-2"
       href="../../../gestion_user/vue/frontoffice/home.php">
      <img src="../../../../assets/logo.png" height="40" alt="JumeauNum" onerror="this.style.display='none'"/>
      <span class="fs-5 fw-bold">JumeauNum</span>
    </a>
    <button class="navbar-toggler" data-bs-target="#navbarNav" data-bs-toggle="collapse" type="button">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav mx-auto">
        <!-- Accueil → retour home -->
        <li class="nav-item">
          <a class="nav-link" href="../../../gestion_user/vue/frontoffice/home.php">
            <span class="material-symbols-outlined" style="font-size:16px;vertical-align:middle;">arrow_back</span>
            Accueil
          </a>
        </li>
        <!-- Page active -->
        <li class="nav-item">
          <a class="nav-link active-page" href="mes_factures.php">
            <span class="material-symbols-outlined" style="font-size:16px;vertical-align:middle;">receipt_long</span>
            Mes Factures
          </a>
        </li>
      </ul>
      <div class="d-flex align-items-center gap-3">
        <div class="welcome-pill">
          <div class="avatar"><?= strtoupper(mb_substr($user['prenom'],0,1)).strtoupper(mb_substr($user['nom'],0,1)) ?></div>
          <?= htmlspecialchars($user['prenom']) ?>
        </div>
        <!-- Déconnexion depuis gestion_des_paiements/vue/frontoffice/ -->
        <a href="../../controleur/frontoffice/logout_paiement.php" class="btn-logout">
          <span class="material-symbols-outlined" style="font-size:16px;">logout</span> Déconnexion
        </a>
      </div>
    </div>
  </div>
</nav>

<!-- ══ CONTENU PRINCIPAL ══ -->
<div class="page-content">
  <div class="container">

    <!-- En-tête page -->
    <div class="page-header d-flex flex-column flex-md-row align-items-md-end justify-content-between gap-3">
      <div>
        <h1 class="page-title">Aperçu Financier</h1>
        <p class="page-subtitle">Gérez vos factures, suivez vos paiements et consultez votre historique.</p>
      </div>
      <div class="security-badge">
        <span class="material-symbols-outlined">verified_user</span>
        Paiements sécurisés par Stripe
      </div>
    </div>

    <!-- Cartes résumé -->
    <div class="row g-4 mb-5" id="summaryCards">
      <!-- Skeleton pendant le chargement -->
      <div class="col-md-6"><div class="summary-card"><div class="skeleton skeleton-line" style="width:60%"></div><div class="skeleton" style="height:48px;width:50%;margin:12px 0 6px"></div><div class="skeleton skeleton-line" style="width:80%"></div></div></div>
      <div class="col-md-6"><div class="summary-card"><div class="skeleton skeleton-line" style="width:60%"></div><div class="skeleton" style="height:36px;width:40%;margin:12px 0 6px"></div><div class="skeleton skeleton-line" style="width:70%"></div></div></div>
    </div>

    <!-- Liste factures -->
    <div class="section-label">
      <span class="material-symbols-outlined">receipt_long</span>
      Registre des Factures
    </div>
    <div id="facturesContainer">
      <!-- Skeletons -->
      <div class="skeleton skeleton-card"></div>
      <div class="skeleton skeleton-card"></div>
      <div class="skeleton skeleton-card"></div>
    </div>

  </div>
</div>

<!-- ══ CHECKOUT MODAL ══ -->
<div id="checkoutOverlay">
  <div class="checkout-modal">

    <!-- Écran formulaire -->
    <div id="checkoutForm">
      <div class="checkout-header">
        <div class="checkout-title-row">
          <span class="checkout-title">Paiement</span>
          <div class="d-flex align-items-center gap-2">
            <div class="stripe-badge"><span class="material-symbols-outlined">lock</span> Stripe</div>
            <button class="btn-close-checkout" onclick="closeCheckout()">
              <span class="material-symbols-outlined">close</span>
            </button>
          </div>
        </div>
        <p class="checkout-desc" id="checkoutDesc">Paiement sécurisé de votre facture</p>
        <div class="checkout-amount" id="checkoutAmount">0,00 DT</div>
      </div>

      <div class="checkout-body">
        <!-- Numéro de carte -->
        <div class="mb-4">
          <span class="checkout-field-label">Informations de la carte</span>
          <div class="checkout-input-group">
            <span class="material-symbols-outlined icon">credit_card</span>
            <input class="checkout-input" type="text" placeholder="0000 0000 0000 0000" maxlength="19" id="cardNumber" oninput="formatCard(this)"/>
          </div>
          <div class="checkout-row">
            <div class="checkout-input-group"><input class="checkout-input" type="text" placeholder="MM / AA" maxlength="7" id="cardExpiry" oninput="formatExpiry(this)"/></div>
            <div class="checkout-input-group" style="position:relative;">
              <input class="checkout-input" type="text" placeholder="CVC" maxlength="4" id="cardCvc"/>
              <span class="material-symbols-outlined icon-right">help</span>
            </div>
          </div>
        </div>
        <!-- Nom -->
        <div>
          <span class="checkout-field-label">Nom sur la carte</span>
          <div class="checkout-name-group"><input class="checkout-name-input" type="text" placeholder="Ex : Ali Ben Salem" id="cardName"/></div>
        </div>
        <!-- Erreur -->
        <div id="checkoutError" style="display:none;margin-top:12px;padding:10px 14px;background:#fee2e2;border:1px solid #fca5a5;border-radius:8px;font-size:.8rem;color:#dc2626;"></div>
      </div>

      <div class="checkout-footer">
        <button class="btn-checkout" id="btnPay" onclick="processPayment()">
          <span class="material-symbols-outlined" style="font-size:18px;">payments</span>
          <span id="btnPayText">Confirmer le paiement</span>
          <div class="spinner-border spinner-border-sm text-white ms-1" id="paySpinner" style="display:none;width:16px;height:16px;border-width:2px;"></div>
        </button>
        <div class="encryption-note">
          <span class="material-symbols-outlined">verified_user</span>
          Chiffrement AES-256 bits actif
        </div>
      </div>
    </div>

    <!-- Écran succès -->
    <div id="checkoutSuccess">
      <div class="success-circle"><span class="material-symbols-outlined">check</span></div>
      <h4 class="fw-bold mb-2" style="font-family:'Manrope',sans-serif;">Paiement effectué !</h4>
      <p class="text-muted small mb-4">Votre facture a été réglée avec succès. Un reçu vous a été envoyé par email.</p>
      <button class="btn-payer" onclick="closeCheckout(); location.reload();">
        <span class="material-symbols-outlined">refresh</span> Actualiser mes factures
      </button>
    </div>

  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ─── Variables globales ────────────────────────────────────────────────────
let factureEnCours = null; // La facture sur laquelle on clique "Payer"

// ─── Chargement des factures au démarrage ──────────────────────────────────
document.addEventListener('DOMContentLoaded', chargerFactures);

async function chargerFactures() {
  try {
    // Chemin relatif depuis vue/frontoffice/ vers controleur/frontoffice/
    const res  = await fetch('../../controleur/frontoffice/get_factures.php');
    const data = await res.json();

    if (!data.success) {
      document.getElementById('facturesContainer').innerHTML =
        '<div class="empty-state"><span class="material-symbols-outlined">error</span><p>Erreur lors du chargement des factures.</p></div>';
      return;
    }

    afficherResume(data);
    afficherFactures(data.factures);

  } catch (e) {
    document.getElementById('facturesContainer').innerHTML =
      '<div class="empty-state"><span class="material-symbols-outlined">wifi_off</span><p>Impossible de contacter le serveur.</p></div>';
  }
}

// ─── Cartes de résumé ──────────────────────────────────────────────────────
function afficherResume(data) {
  const montantFormate = parseFloat(data.total_non_payees).toLocaleString('fr-TN', {minimumFractionDigits:2}) + ' DT';
  const pendingText = data.nb_non_payees === 0
    ? 'Aucune facture en attente'
    : data.nb_non_payees + ' facture' + (data.nb_non_payees > 1 ? 's' : '') + ' en attente de paiement';

  let dernierePayeeHTML = `
    <div class="card-icon mb-2" style="background:#f0fdf4;">
      <span class="material-symbols-outlined" style="color:#16a34a;font-size:22px;">check_circle</span>
    </div>
    <div class="text-muted" style="font-size:.85rem;font-weight:600;margin-bottom:4px;">Dernier Paiement</div>`;

  if (data.derniere_payee) {
    const montant  = parseFloat(data.derniere_payee.montant).toLocaleString('fr-TN', {minimumFractionDigits:2});
    const date     = new Date(data.derniere_payee.date_facture).toLocaleDateString('fr-TN', {day:'numeric',month:'long',year:'numeric'});
    dernierePayeeHTML += `
      <div class="card-amount" style="color:#1f2937;">${montant} DT</div>
      <div class="card-label">${data.derniere_payee.nom_type} • ${date}</div>`;
  } else {
    dernierePayeeHTML += `<div class="card-amount" style="color:#9ca3af;font-size:1.4rem;">—</div><div class="card-label">Aucun paiement enregistré</div>`;
  }

  document.getElementById('summaryCards').innerHTML = `
    <div class="col-md-6">
      <div class="summary-card">
        <div class="d-flex justify-content-between align-items-start">
          <div class="fw-bold" style="font-size:1rem;">Total à Régler</div>
          <div class="card-icon" style="background:#eff4ff;">
            <span class="material-symbols-outlined" style="color:var(--primary-blue);font-size:22px;">account_balance_wallet</span>
          </div>
        </div>
        <div class="card-amount" style="color:var(--primary-blue);">${montantFormate}</div>
        <div class="card-label">${pendingText}</div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="summary-card">${dernierePayeeHTML}</div>
    </div>`;
}

// ─── Liste des factures ────────────────────────────────────────────────────
function afficherFactures(factures) {
  const container = document.getElementById('facturesContainer');

  if (factures.length === 0) {
    container.innerHTML = `
      <div class="empty-state">
        <span class="material-symbols-outlined">receipt_long</span>
        <p>Aucune facture pour le moment.</p>
      </div>`;
    return;
  }

  // Séparer payées / non payées
  const nonPayees = factures.filter(f => f.statut === 'Non payee');
  const payees    = factures.filter(f => f.statut === 'payee');

  let html = '';

  // ── Factures non payées ──
  nonPayees.forEach(f => {
    const montant = parseFloat(f.montant).toLocaleString('fr-TN', {minimumFractionDigits:2});
    const date    = new Date(f.date_facture).toLocaleDateString('fr-FR', {day:'numeric',month:'long',year:'numeric'});
    html += `
      <div class="facture-card">
        <div class="accent-bar"></div>
        <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap ps-2">
          <div class="d-flex align-items-center gap-3">
            <div class="facture-icon"><span class="material-symbols-outlined">receipt</span></div>
            <div>
              <div class="facture-nom">${escHtml(f.nom_type)}</div>
              <div class="facture-meta">
                <span class="d-flex align-items-center gap-1"><span class="material-symbols-outlined">calendar_today</span>${date}</span>
                <span class="facture-id">FAC-${String(f.id_facture).padStart(4,'0')}</span>
                <span class="badge-statut-non">En attente</span>
              </div>
            </div>
          </div>
          <div class="d-flex align-items-center gap-3">
            <div class="facture-montant">${montant} DT</div>
            <button class="btn-payer" onclick="ouvrirCheckout(${f.id_facture}, '${escHtml(f.nom_type)}', ${f.montant})">
              <span class="material-symbols-outlined">payments</span> Payer
            </button>
          </div>
        </div>
      </div>`;
  });

  // ── Factures payées ──
  if (payees.length > 0) {
    html += `<div class="section-label mt-4 mb-3"><span class="material-symbols-outlined">check_circle</span>Factures réglées</div>`;
    payees.forEach(f => {
      const montant = parseFloat(f.montant).toLocaleString('fr-TN', {minimumFractionDigits:2});
      const date    = new Date(f.date_facture).toLocaleDateString('fr-FR', {day:'numeric',month:'long',year:'numeric'});
      html += `
        <div class="facture-card payee">
          <div class="accent-bar"></div>
          <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap ps-2" style="opacity:.85;">
            <div class="d-flex align-items-center gap-3">
              <div class="facture-icon"><span class="material-symbols-outlined">receipt</span></div>
              <div>
                <div class="facture-nom">${escHtml(f.nom_type)}</div>
                <div class="facture-meta">
                  <span class="d-flex align-items-center gap-1"><span class="material-symbols-outlined">calendar_today</span>${date}</span>
                  <span class="facture-id">FAC-${String(f.id_facture).padStart(4,'0')}</span>
                </div>
              </div>
            </div>
            <div class="d-flex align-items-center gap-3">
              <div class="facture-montant">${montant} DT</div>
              <div class="badge-statut-oui"><span class="material-symbols-outlined">check_circle</span> Payée</div>
            </div>
          </div>
        </div>`;
    });
  }

  container.innerHTML = html;
}

// ─── Checkout ──────────────────────────────────────────────────────────────
function ouvrirCheckout(id, nom, montant) {
  factureEnCours = { id, nom, montant };
  document.getElementById('checkoutDesc').textContent = 'Paiement sécurisé — ' + nom;
  document.getElementById('checkoutAmount').textContent =
    parseFloat(montant).toLocaleString('fr-TN', {minimumFractionDigits:2}) + ' DT';
  document.getElementById('checkoutForm').style.display = '';
  document.getElementById('checkoutSuccess').style.display = 'none';
  document.getElementById('checkoutError').style.display = 'none';
  // Vider les champs
  ['cardNumber','cardExpiry','cardCvc','cardName'].forEach(id => document.getElementById(id).value = '');
  document.getElementById('checkoutOverlay').classList.add('open');
  document.body.style.overflow = 'hidden';
}

function closeCheckout() {
  document.getElementById('checkoutOverlay').classList.remove('open');
  document.body.style.overflow = '';
  factureEnCours = null;
}

// Fermer en cliquant sur le fond
document.getElementById('checkoutOverlay').addEventListener('click', function(e) {
  if (e.target === this) closeCheckout();
});

// ─── Formatage champs carte ────────────────────────────────────────────────
function formatCard(input) {
  let v = input.value.replace(/\D/g,'').substring(0,16);
  input.value = v.replace(/(.{4})/g,'$1 ').trim();
}
function formatExpiry(input) {
  let v = input.value.replace(/\D/g,'').substring(0,4);
  if (v.length >= 2) v = v.substring(0,2) + ' / ' + v.substring(2);
  input.value = v;
}

// ─── Traitement paiement ───────────────────────────────────────────────────
async function processPayment() {
  const cardNumber = document.getElementById('cardNumber').value.replace(/\s/g,'');
  const cardExpiry = document.getElementById('cardExpiry').value;
  const cardCvc    = document.getElementById('cardCvc').value;
  const cardName   = document.getElementById('cardName').value.trim();
  const errorDiv   = document.getElementById('checkoutError');

  // Validation simple
  if (cardNumber.length < 16) return showCheckoutError('Numéro de carte invalide.');
  if (!cardExpiry.includes('/'))  return showCheckoutError('Date d\'expiration invalide.');
  if (cardCvc.length < 3)        return showCheckoutError('CVC invalide.');
  if (!cardName)                 return showCheckoutError('Veuillez saisir le nom sur la carte.');

  // Afficher spinner
  document.getElementById('btnPayText').textContent = 'Traitement...';
  document.getElementById('paySpinner').style.display = 'inline-block';
  document.getElementById('btnPay').disabled = true;
  errorDiv.style.display = 'none';

  try {
    // Appel au contrôleur qui met à jour le statut en BDD
    const formData = new FormData();
    formData.append('id_facture', factureEnCours.id);

    const res  = await fetch('../../controleur/frontoffice/payer_facture.php', { method:'POST', body:formData });
    const data = await res.json();

    if (data.success) {
      // Montrer l'écran succès
      document.getElementById('checkoutForm').style.display = 'none';
      document.getElementById('checkoutSuccess').style.display = 'block';
    } else {
      showCheckoutError(data.message || 'Erreur lors du paiement.');
    }
  } catch (e) {
    showCheckoutError('Erreur de connexion au serveur.');
  } finally {
    document.getElementById('btnPayText').textContent = 'Confirmer le paiement';
    document.getElementById('paySpinner').style.display = 'none';
    document.getElementById('btnPay').disabled = false;
  }
}

function showCheckoutError(msg) {
  const div = document.getElementById('checkoutError');
  div.textContent = msg;
  div.style.display = 'block';
}

// ─── Utilitaire anti-XSS ──────────────────────────────────────────────────
function escHtml(str) {
  return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>
</body>
</html>
