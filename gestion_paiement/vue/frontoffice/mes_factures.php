<?php
/**
 * mes_factures.php  (VERSION MISE A JOUR — Stripe.js intégré)
 * Chemin : gestion_paiement/vue/frontoffice/mes_factures.php
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
<title>JumeauNum – Mes Factures</title>

<!-- Stripe.js — doit être chargé depuis stripe.com uniquement -->
<script src="https://js.stripe.com/v3/"></script>

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
    --orange:       #d97706;
    --orange-lt:    #fef3c7;
  }
  body { font-family:'Inter',sans-serif; background-color:var(--bg-main); color:var(--slate-gray); }
  h1,h2,h3,h4,.navbar-brand { font-family:'Manrope',sans-serif; font-weight:700; }

  .navbar { background-color:rgba(255,255,255,0.95)!important; backdrop-filter:blur(10px); border-bottom:1px solid rgba(0,0,0,0.06); padding:.85rem 0; }
  .nav-link { font-weight:500; color:var(--slate-gray)!important; margin:0 6px; transition:color .3s; }
  .nav-link:hover,.nav-link.active-page { color:var(--primary-blue)!important; }
  .welcome-pill { display:flex;align-items:center;gap:8px;background:rgba(37,99,235,.07);border:1px solid rgba(37,99,235,.15);padding:5px 14px 5px 6px;border-radius:50px;font-size:.82rem;font-weight:600;color:var(--primary-blue); }
  .welcome-pill .avatar { width:28px;height:28px;border-radius:50%;background:var(--primary-blue);color:white;display:flex;align-items:center;justify-content:center;font-size:.72rem;font-weight:700; }
  .btn-logout { background:none;border:1.5px solid var(--border);padding:.4rem 1.1rem;border-radius:10px;font-weight:600;font-size:.85rem;color:var(--slate-gray);cursor:pointer;transition:all .2s;display:flex;align-items:center;gap:6px;text-decoration:none; }
  .btn-logout:hover { border-color:var(--red);color:var(--red);background:#fff5f5; }
  .btn-tickets { background:rgba(20,184,166,.08);border:1.5px solid rgba(20,184,166,.25);padding:.4rem 1.1rem;border-radius:10px;font-weight:600;font-size:.85rem;color:var(--dark-teal);cursor:pointer;transition:all .2s;display:flex;align-items:center;gap:6px;text-decoration:none; }
  .btn-tickets:hover { background:rgba(20,184,166,.15);color:var(--dark-teal); }

  .page-content { padding:48px 0 80px; }
  .page-header { margin-bottom:40px; }
  .page-title { font-size:2.2rem;font-weight:800;margin-bottom:6px; }
  .page-subtitle { color:var(--muted);font-size:.95rem; }
  .security-badge { display:inline-flex;align-items:center;gap:6px;background:rgba(37,99,235,.05);border:1px solid rgba(37,99,235,.12);padding:6px 14px;border-radius:50px;font-size:.72rem;font-weight:600;color:var(--muted); }
  .security-badge .material-symbols-outlined { font-size:14px;color:var(--green); }

  .summary-card { background:white;border-radius:20px;padding:28px;border:1px solid var(--border);height:100%;transition:box-shadow .2s; }
  .summary-card:hover { box-shadow:0 8px 32px rgba(37,99,235,.08); }
  .summary-card .card-icon { width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center; }
  .summary-card .card-amount { font-size:2.4rem;font-weight:800;font-family:'Manrope',sans-serif;line-height:1.1;margin:12px 0 4px; }
  .summary-card .card-label { font-size:.8rem;color:var(--muted); }

  .section-label { font-size:1.1rem;font-weight:700;color:var(--slate-gray);display:flex;align-items:center;gap:8px;margin-bottom:20px; }
  .section-label .material-symbols-outlined { font-size:20px;color:var(--muted); }

  .facture-card { background:white;border-radius:16px;padding:24px 28px;border:1px solid var(--border);margin-bottom:16px;position:relative;overflow:hidden;transition:box-shadow .2s,transform .15s; }
  .facture-card:hover { box-shadow:0 8px 28px rgba(0,0,0,.07);transform:translateY(-2px); }
  .facture-card .accent-bar { position:absolute;left:0;top:0;bottom:0;width:4px;background:var(--primary-blue); }
  .facture-card.payee { background:#fafffe; }
  .facture-card.payee .accent-bar { background:var(--green); }
  .facture-card .facture-icon { width:48px;height:48px;border-radius:14px;background:#f0f4ff;display:flex;align-items:center;justify-content:center;flex-shrink:0; }
  .facture-card.payee .facture-icon { background:#f0fdf4; }
  .facture-card .facture-icon .material-symbols-outlined { color:var(--primary-blue);font-size:22px; }
  .facture-card.payee .facture-icon .material-symbols-outlined { color:var(--green); }
  .facture-card .facture-nom { font-weight:700;font-size:1.05rem;margin-bottom:4px; }
  .facture-card .facture-meta { font-size:.8rem;color:var(--muted);display:flex;align-items:center;gap:12px;flex-wrap:wrap; }
  .facture-card .facture-meta .material-symbols-outlined { font-size:14px; }
  .facture-card .facture-id { font-family:monospace;font-size:.75rem;background:#f3f4f6;padding:2px 8px;border-radius:6px;color:var(--muted); }
  .facture-card .facture-montant { font-family:'Manrope',sans-serif;font-size:1.5rem;font-weight:800; }
  .facture-card.payee .facture-montant { color:var(--muted);font-size:1.2rem; }
  .badge-statut-non { font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--red); }
  .badge-statut-oui { display:inline-flex;align-items:center;gap:5px;background:var(--green-lt);color:var(--green);border:1px solid rgba(22,163,74,.2);font-size:.72rem;font-weight:700;padding:4px 12px;border-radius:50px; }
  .badge-statut-oui .material-symbols-outlined { font-size:13px; }
  .btn-payer { background:var(--primary-blue);color:white;border:none;padding:.6rem 1.4rem;border-radius:10px;font-weight:600;font-size:.85rem;display:inline-flex;align-items:center;gap:6px;cursor:pointer;transition:background .2s,transform .15s; }
  .btn-payer:hover { background:#1d4ed8;transform:translateY(-1px); }
  .btn-payer .material-symbols-outlined { font-size:16px; }

  .empty-state { text-align:center;padding:64px 24px;color:var(--muted); }
  .empty-state .material-symbols-outlined { font-size:52px;opacity:.3;margin-bottom:16px; }

  .skeleton { background:linear-gradient(90deg,#f0f0f0 25%,#e0e0e0 50%,#f0f0f0 75%);background-size:200% 100%;animation:shimmer 1.4s infinite;border-radius:8px; }
  @keyframes shimmer { 0%{background-position:200% 0}100%{background-position:-200% 0} }
  .skeleton-line { height:16px;margin-bottom:10px; }
  .skeleton-card { height:90px;margin-bottom:16px;border-radius:16px; }

  /* ── CHECKOUT MODAL ── */
  #checkoutOverlay { display:none;position:fixed;inset:0;z-index:1000;background:rgba(22,26,46,.15);backdrop-filter:blur(8px);align-items:center;justify-content:center;padding:16px; }
  #checkoutOverlay.open { display:flex;animation:fadeIn .2s ease; }
  @keyframes fadeIn { from{opacity:0}to{opacity:1} }

  .checkout-modal { background:rgba(255,255,255,.92);backdrop-filter:blur(24px);border:1px solid rgba(220,225,240,.8);border-radius:24px;width:100%;max-width:480px;box-shadow:0 32px 64px -16px rgba(0,30,80,.18);overflow:hidden;animation:slideUp .22s ease; }
  @keyframes slideUp { from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)} }

  .checkout-header { padding:32px 32px 24px;border-bottom:1px solid #eef0f8; }
  .checkout-title-row { display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:6px; }
  .checkout-title { font-family:'Manrope',sans-serif;font-size:1.5rem;font-weight:800; }
  .stripe-badge { display:inline-flex;align-items:center;gap:5px;background:white;border:1px solid var(--border);border-radius:10px;padding:5px 10px;font-size:.72rem;font-weight:600;color:var(--muted); }
  .stripe-badge .material-symbols-outlined { font-size:14px; }
  .checkout-desc { font-size:.85rem;color:var(--muted);margin-bottom:12px; }
  .checkout-amount { font-family:'Manrope',sans-serif;font-size:2.8rem;font-weight:800;color:var(--primary-blue);line-height:1; }

  .checkout-body { padding:24px 32px; }
  .checkout-field-label { font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);margin-bottom:6px;display:block; }

  /* Conteneur pour l'élément Stripe Card (remplace les inputs manuels) */
  #stripe-card-element {
    background:#f3f4f8;border-radius:12px;border:2px solid var(--border);
    padding:14px 16px;transition:border-color .2s;
  }
  #stripe-card-element.StripeElement--focus { border-color:var(--primary-blue);background:#f8f9fd; }
  #stripe-card-element.StripeElement--invalid { border-color:var(--red); }

  #stripe-card-errors { color:var(--red);font-size:.82rem;margin-top:10px;min-height:20px; }

  .checkout-name-group { background:#f3f4f8;border-radius:12px;border:2px solid var(--border);transition:border-color .2s;margin-bottom:0; }
  .checkout-name-group:focus-within { border-color:var(--primary-blue); }
  .checkout-name-input { width:100%;background:transparent;border:none;outline:none;padding:14px;font-family:'Inter',sans-serif;font-size:.9rem;color:var(--slate-gray); }
  .checkout-name-input::placeholder { color:#b0b8cc; }

  .checkout-footer { padding:8px 32px 32px; }
  .btn-checkout { width:100%;padding:16px;border:none;border-radius:14px;background:var(--primary-blue);color:white;font-family:'Manrope',sans-serif;font-size:1.05rem;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;box-shadow:0 8px 24px -8px rgba(37,99,235,.5);transition:all .25s; }
  .btn-checkout:hover:not(:disabled) { background:#1d4ed8;transform:translateY(-2px); }
  .btn-checkout:disabled { opacity:.7;cursor:not-allowed;transform:none; }
  .encryption-note { display:flex;align-items:center;justify-content:center;gap:6px;margin-top:16px;font-size:.75rem;color:var(--muted); }
  .encryption-note .material-symbols-outlined { font-size:14px;color:var(--green); }
  .btn-close-checkout { background:none;border:none;cursor:pointer;color:var(--muted);padding:4px;display:flex;align-items:center;transition:color .2s; }
  .btn-close-checkout:hover { color:var(--red); }

  /* Écrans résultat à l'intérieur de la modal */
  #checkoutResult { display:none;padding:40px 32px;text-align:center; }
  .result-icon-circle { width:80px;height:80px;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px; }
  .result-icon-circle.success { background:var(--green-lt); }
  .result-icon-circle.warning { background:var(--orange-lt); }
  .result-icon-circle.error   { background:var(--red-lt); }
  .result-icon-circle .material-symbols-outlined { font-size:40px; }
  .result-icon-circle.success .material-symbols-outlined { color:var(--green); }
  .result-icon-circle.warning .material-symbols-outlined { color:var(--orange); }
  .result-icon-circle.error   .material-symbols-outlined { color:var(--red); }
  .result-title { font-family:'Manrope',sans-serif;font-size:1.4rem;font-weight:800;margin-bottom:8px; }
  .result-sub { color:var(--muted);font-size:.9rem;margin-bottom:6px; }
  .result-ticket-box { background:#1f2937;border-radius:12px;padding:14px 24px;display:inline-block;margin:16px 0; }
  .result-ticket-box span { color:var(--dark-teal);font-family:monospace;font-size:1.5rem;font-weight:700;letter-spacing:3px; }
  .result-expiry { font-size:.8rem;color:var(--muted);margin-bottom:20px; }
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
          <a class="nav-link" href="../../../gestion_user/vue/frontoffice/home.php">
            <span class="material-symbols-outlined" style="font-size:16px;vertical-align:middle;">arrow_back</span>
            Accueil
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link active-page" href="mes_factures.php">
            <span class="material-symbols-outlined" style="font-size:16px;vertical-align:middle;">receipt_long</span>
            Mes Factures
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="mes_tickets.php">
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

<!-- CONTENU PRINCIPAL -->
<div class="page-content">
  <div class="container">

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
      <div class="col-md-6"><div class="summary-card"><div class="skeleton skeleton-line" style="width:60%"></div><div class="skeleton" style="height:48px;width:50%;margin:12px 0 6px"></div><div class="skeleton skeleton-line" style="width:80%"></div></div></div>
      <div class="col-md-6"><div class="summary-card"><div class="skeleton skeleton-line" style="width:60%"></div><div class="skeleton" style="height:36px;width:40%;margin:12px 0 6px"></div><div class="skeleton skeleton-line" style="width:70%"></div></div></div>
    </div>

    <div class="section-label">
      <span class="material-symbols-outlined">receipt_long</span>
      Registre des Factures
    </div>
    <div id="facturesContainer">
      <div class="skeleton skeleton-card"></div>
      <div class="skeleton skeleton-card"></div>
      <div class="skeleton skeleton-card"></div>
    </div>

  </div>
</div>

<!-- ══ CHECKOUT MODAL ══ -->
<div id="checkoutOverlay">
  <div class="checkout-modal">

    <!-- Formulaire de paiement Stripe -->
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
        <!-- Nom sur la carte -->
        <div class="mb-3">
          <span class="checkout-field-label">Nom sur la carte</span>
          <div class="checkout-name-group">
            <input class="checkout-name-input" type="text" placeholder="Ex : Ali Ben Salem" id="cardName"/>
          </div>
        </div>
        <!-- Éléments sécurisés Stripe (numéro, expiry, CVC en un seul bloc) -->
        <div>
          <span class="checkout-field-label">Informations de la carte</span>
          <div id="stripe-card-element"></div>
          <div id="stripe-card-errors" role="alert"></div>
        </div>
      </div>

      <div class="checkout-footer">
        <button class="btn-checkout" id="btnPay" onclick="processPayment()">
          <span class="material-symbols-outlined" style="font-size:18px;">payments</span>
          <span id="btnPayText">Confirmer le paiement</span>
          <div class="spinner-border spinner-border-sm text-white ms-1" id="paySpinner" style="display:none;width:16px;height:16px;border-width:2px;"></div>
        </button>
        <div class="encryption-note">
          <span class="material-symbols-outlined">verified_user</span>
          Chiffrement TLS · Sécurisé par Stripe
        </div>
      </div>
    </div>

    <!-- Écran résultat (succès / partiel / erreur) -->
    <div id="checkoutResult">
      <!-- Rempli dynamiquement par JS selon les étapes -->
    </div>

  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Clé publique Stripe (safe à exposer en frontend)
const STRIPE_PK = 'pk_test_51TT74YCWRHNpYscd3ZpV3X2PsrKAtaOY68MnTCkKW2eCiaC1HkTvp4COT9TueRlDrcsvTCDqCwtM2MpzdJzMunrB00oiYYVKEn';
const stripe    = Stripe(STRIPE_PK);
const elements  = stripe.elements();

// Crée l'élément Stripe Card avec un style personnalisé
const cardElement = elements.create('card', {
  style: {
    base: {
      fontFamily: "'Inter', sans-serif",
      fontSize: '15px',
      color: '#1F2937',
      '::placeholder': { color: '#b0b8cc' },
    },
    invalid: { color: '#dc2626', iconColor: '#dc2626' },
  },
  hidePostalCode: true,
});

// Monte l'élément dans le DOM une fois la page chargée
document.addEventListener('DOMContentLoaded', () => {
  cardElement.mount('#stripe-card-element');

  // Affiche les erreurs de carte en temps réel
  cardElement.on('change', (event) => {
    const errorDiv = document.getElementById('stripe-card-errors');
    errorDiv.textContent = event.error ? event.error.message : '';
  });

  chargerFactures();
});

// ── Données de la facture en cours de paiement ──
let factureEnCours = null;

// ── Chargement des factures ──
async function chargerFactures() {
  try {
    const res  = await fetch('../../controleur/frontoffice/get_factures.php');
    const data = await res.json();
    if (!data.success) {
      document.getElementById('facturesContainer').innerHTML =
        '<div class="empty-state"><span class="material-symbols-outlined">error</span><p>Erreur lors du chargement.</p></div>';
      return;
    }
    afficherResume(data);
    afficherFactures(data.factures);
  } catch (e) {
    document.getElementById('facturesContainer').innerHTML =
      '<div class="empty-state"><span class="material-symbols-outlined">wifi_off</span><p>Impossible de contacter le serveur.</p></div>';
  }
}

// ── Cartes résumé ──
function afficherResume(data) {
  const montantFormate = parseFloat(data.total_non_payees).toLocaleString('fr-TN', {minimumFractionDigits:2}) + ' DT';
  const pendingText = data.nb_non_payees === 0
    ? 'Aucune facture en attente'
    : data.nb_non_payees + ' facture' + (data.nb_non_payees > 1 ? 's' : '') + ' en attente';

  let derniereHTML = `
    <div class="card-icon mb-2" style="background:#f0fdf4;"><span class="material-symbols-outlined" style="color:#16a34a;font-size:22px;">check_circle</span></div>
    <div class="text-muted" style="font-size:.85rem;font-weight:600;margin-bottom:4px;">Dernier Paiement</div>`;

  if (data.derniere_payee) {
    const montant = parseFloat(data.derniere_payee.montant).toLocaleString('fr-TN', {minimumFractionDigits:2});
    const date    = new Date(data.derniere_payee.date_facture).toLocaleDateString('fr-TN', {day:'numeric',month:'long',year:'numeric'});
    derniereHTML += `<div class="card-amount" style="color:#1f2937;">${montant} DT</div><div class="card-label">${data.derniere_payee.nom_type} · ${date}</div>`;
  } else {
    derniereHTML += `<div class="card-amount" style="color:#9ca3af;font-size:1.4rem;">—</div><div class="card-label">Aucun paiement enregistré</div>`;
  }

  document.getElementById('summaryCards').innerHTML = `
    <div class="col-md-6">
      <div class="summary-card">
        <div class="d-flex justify-content-between align-items-start">
          <div class="fw-bold" style="font-size:1rem;">Total à Régler</div>
          <div class="card-icon" style="background:#eff4ff;"><span class="material-symbols-outlined" style="color:var(--primary-blue);font-size:22px;">account_balance_wallet</span></div>
        </div>
        <div class="card-amount" style="color:var(--primary-blue);">${montantFormate}</div>
        <div class="card-label">${pendingText}</div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="summary-card">${derniereHTML}</div>
    </div>`;
}

// ── Liste des factures ──
function afficherFactures(factures) {
  const container = document.getElementById('facturesContainer');

  if (factures.length === 0) {
    container.innerHTML = `<div class="empty-state"><span class="material-symbols-outlined">receipt_long</span><p>Aucune facture pour le moment.</p></div>`;
    return;
  }

  const nonPayees = factures.filter(f => f.statut === 'Non payee');
  const payees    = factures.filter(f => f.statut === 'Payee');
  let html = '';

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

// ── Ouvrir la modal de paiement ──
function ouvrirCheckout(id, nom, montant) {
  factureEnCours = { id, nom, montant };
  document.getElementById('checkoutDesc').textContent   = 'Paiement sécurisé — ' + nom;
  document.getElementById('checkoutAmount').textContent = parseFloat(montant).toLocaleString('fr-TN', {minimumFractionDigits:2}) + ' DT';
  document.getElementById('cardName').value             = '';
  document.getElementById('stripe-card-errors').textContent = '';
  document.getElementById('checkoutForm').style.display   = '';
  document.getElementById('checkoutResult').style.display = 'none';
  document.getElementById('btnPay').disabled              = false;
  document.getElementById('btnPayText').textContent       = 'Confirmer le paiement';
  document.getElementById('paySpinner').style.display     = 'none';
  document.getElementById('checkoutOverlay').classList.add('open');
  document.body.style.overflow = 'hidden';
  // Réinitialise l'élément Stripe
  cardElement.clear();
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

// ── Processus de paiement Stripe ──
async function processPayment() {
  const cardName = document.getElementById('cardName').value.trim();
  if (!cardName) {
    document.getElementById('stripe-card-errors').textContent = 'Veuillez saisir le nom sur la carte.';
    return;
  }

  // Désactive le bouton et affiche le spinner
  const btn = document.getElementById('btnPay');
  btn.disabled = true;
  document.getElementById('btnPayText').textContent   = 'Traitement en cours...';
  document.getElementById('paySpinner').style.display = 'inline-block';
  document.getElementById('stripe-card-errors').textContent = '';

  try {
    // Etape 1 : Crée un PaymentIntent côté serveur
    const formData = new FormData();
    formData.append('id_facture', factureEnCours.id);

    const intentRes  = await fetch('../../controleur/frontoffice/create_payment_intent.php', { method:'POST', body:formData });
    const intentData = await intentRes.json();

    if (!intentData.success) {
      afficherResultatErreur(intentData.message || 'Erreur lors de la création du paiement.');
      return;
    }

    // Etape 2 : Confirme le paiement côté Stripe.js (carte débitée ici)
    const { paymentIntent, error } = await stripe.confirmCardPayment(intentData.client_secret, {
      payment_method: {
        card: cardElement,
        billing_details: { name: cardName },
      },
    });

    if (error) {
      // Stripe a refusé le paiement (carte invalide, fonds insuffisants, etc.)
      document.getElementById('stripe-card-errors').textContent = error.message;
      btn.disabled = false;
      document.getElementById('btnPayText').textContent   = 'Confirmer le paiement';
      document.getElementById('paySpinner').style.display = 'none';
      return;
    }

    if (paymentIntent.status !== 'succeeded') {
      afficherResultatErreur('Paiement non finalisé. Statut : ' + paymentIntent.status);
      return;
    }

    // Etape 3 : Notifie le backend pour mettre à jour la BDD, créer le ticket et envoyer l'email
    const confirmData = new FormData();
    confirmData.append('id_facture', factureEnCours.id);
    confirmData.append('payment_intent_id', paymentIntent.id);

    const confirmRes  = await fetch('../../controleur/frontoffice/payer_facture.php', { method:'POST', body:confirmData });
    const confirmJson = await confirmRes.json();

    afficherResultatFinal(confirmJson);

  } catch (e) {
    afficherResultatErreur('Erreur de connexion. Vérifiez votre réseau.');
  }
}

// ── Affichage du résultat dans la modal ──
function afficherResultatFinal(data) {
  document.getElementById('checkoutForm').style.display = 'none';
  const resultDiv = document.getElementById('checkoutResult');
  resultDiv.style.display = 'block';

  let html = '';

  if (data.paiement_ok && data.ticket_ok) {
    // Succès complet
    const dateExp = data.date_expiration
      ? new Date(data.date_expiration).toLocaleDateString('fr-FR', {day:'numeric',month:'long',year:'numeric'})
      : '';
    html = `
      <div class="result-icon-circle success"><span class="material-symbols-outlined">check_circle</span></div>
      <div class="result-title">Paiement réussi !</div>
      <div class="result-sub">Votre paiement a été confirmé par Stripe.</div>
      <div class="result-sub">Ticket numérique généré avec succès.</div>
      <div class="result-ticket-box"><span>#${data.id_ticket}</span></div>
      <div class="result-expiry">Valable jusqu'au ${dateExp}</div>
      ${!data.email_ok ? '<div class="alert alert-warning py-2 px-3 small mb-3">Email de confirmation non envoyé — vérifiez la configuration PHPMailer.</div>' : ''}
      <button class="btn-payer" onclick="closeCheckout(); location.reload();">
        <span class="material-symbols-outlined">refresh</span> Actualiser mes factures
      </button>`;
  } else if (data.paiement_ok && !data.ticket_ok) {
    // Paiement OK mais ticket non généré
    html = `
      <div class="result-icon-circle warning"><span class="material-symbols-outlined">warning</span></div>
      <div class="result-title">Paiement réussi</div>
      <div class="result-sub" style="color:var(--orange);font-weight:600;">Ticket non généré — contactez le support.</div>
      ${data.ticket_error ? `<div class="alert alert-warning py-2 px-3 small my-3">${escHtml(data.ticket_error)}</div>` : ''}
      <button class="btn-payer mt-3" onclick="closeCheckout(); location.reload();">
        <span class="material-symbols-outlined">refresh</span> Actualiser
      </button>`;
  } else {
    // Echec
    afficherResultatErreur(data.message || 'Paiement non abouti.');
    return;
  }

  resultDiv.innerHTML = html;
}

function afficherResultatErreur(message) {
  document.getElementById('checkoutForm').style.display = 'none';
  const resultDiv = document.getElementById('checkoutResult');
  resultDiv.style.display = 'block';
  resultDiv.innerHTML = `
    <div class="result-icon-circle error"><span class="material-symbols-outlined">cancel</span></div>
    <div class="result-title">Paiement non abouti</div>
    <div class="result-sub">${escHtml(message)}</div>
    <button class="btn btn-outline-secondary mt-4" onclick="
      document.getElementById('checkoutResult').style.display='none';
      document.getElementById('checkoutForm').style.display='';
      document.getElementById('btnPay').disabled=false;
      document.getElementById('btnPayText').textContent='Confirmer le paiement';
      document.getElementById('paySpinner').style.display='none';
    ">
      Réessayer
    </button>`;
}

function escHtml(str) {
  return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>
</body>
</html>