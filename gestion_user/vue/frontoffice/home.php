<?php
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
<title>JumeauNum – Accueil</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<style>
  :root { --primary-blue:#2563EB; --dark-teal:#14B8A6; --slate-gray:#1F2937; --soft-blue:#8B5CF6; --bg-main:#f8f9ff; }
  body { font-family:'Inter',sans-serif; background-color:var(--bg-main); color:var(--slate-gray); overflow-x:hidden; }
  h1,h2,h3,h4,.navbar-brand { font-family:'Manrope',sans-serif; font-weight:700; }

  .navbar { background-color:rgba(255,255,255,0.9)!important; backdrop-filter:blur(10px); border-bottom:1px solid rgba(0,0,0,0.05); padding:1rem 0; }
  .nav-link { font-weight:500; color:var(--slate-gray)!important; margin:0 6px; transition:color 0.3s; }
  .nav-link:hover { color:var(--primary-blue)!important; }

  /* Dropdown Services */
  .nav-item.dropdown:hover>.dropdown-menu { display:block; }
  .dropdown-menu { border:none; border-radius:16px; box-shadow:0 12px 40px rgba(37,99,235,.13); padding:8px; min-width:240px; margin-top:8px!important; animation:dropIn .18s ease; }
  @keyframes dropIn { from{opacity:0;transform:translateY(-6px)} to{opacity:1;transform:translateY(0)} }
  .dropdown-item { border-radius:10px; padding:10px 14px; font-size:.87rem; font-weight:500; color:var(--slate-gray); display:flex; align-items:center; gap:10px; transition:background .15s; }
  .dropdown-item:hover { background:rgba(37,99,235,.07); color:var(--primary-blue); }
  .dropdown-item .material-symbols-outlined { font-size:18px; color:var(--primary-blue); }
  .nav-link.dropdown-toggle::after { transition:transform .2s; }
  .nav-item.dropdown:hover .nav-link.dropdown-toggle::after { transform:rotate(180deg); }

  .welcome-pill { display:flex; align-items:center; gap:8px; background:rgba(37,99,235,.07); border:1px solid rgba(37,99,235,.15); padding:5px 14px 5px 6px; border-radius:50px; font-size:.82rem; font-weight:600; color:var(--primary-blue); }
  .welcome-pill .avatar { width:28px; height:28px; border-radius:50%; background:var(--primary-blue); color:white; display:flex; align-items:center; justify-content:center; font-size:.72rem; font-weight:700; }
  .btn-logout { background:none; border:1.5px solid #e5e7eb; padding:.4rem 1.1rem; border-radius:10px; font-weight:600; font-size:.85rem; color:var(--slate-gray); cursor:pointer; transition:all .2s; display:flex; align-items:center; gap:6px; }
  .btn-logout:hover { border-color:#dc2626; color:#dc2626; background:#fff5f5; }

  .hero-section { padding:120px 0 80px; background:linear-gradient(135deg,rgba(37,99,235,.05) 0%,transparent 100%); }
  .badge-innovation { background-color:rgba(20,184,166,.1); color:var(--dark-teal); padding:6px 16px; border-radius:50px; font-size:.75rem; font-weight:800; text-transform:uppercase; letter-spacing:1px; display:inline-flex; align-items:center; margin-bottom:1.5rem; }
  .badge-innovation::before { content:''; width:8px; height:8px; background:var(--dark-teal); border-radius:50%; margin-right:8px; display:inline-block; }
  .hero-title { font-size:3.5rem; line-height:1.1; margin-bottom:1.5rem; }
  .hero-title span { color:var(--primary-blue); font-style:italic; }
  .hero-image-container img { border-radius:2rem; box-shadow:0 25px 50px -12px rgba(0,0,0,.1); width:100%; }
  .btn-primary-custom { background-color:var(--primary-blue); color:white; border:none; padding:.6rem 1.5rem; border-radius:12px; font-weight:600; transition:transform .2s,opacity .2s; }
  .btn-primary-custom:hover { opacity:.9; transform:translateY(-1px); color:white; }
  .feature-section { padding:100px 0; background-color:white; }
  .feature-box { background:var(--bg-main); border-radius:24px; padding:40px; height:100%; display:flex; flex-direction:column; justify-content:flex-end; transition:transform .3s; }
  .feature-box:hover { transform:translateY(-10px); }
  .icon-large { font-size:3rem; color:var(--primary-blue); margin-bottom:1rem; }
  .service-card { background:white; border-radius:24px; padding:40px; border:1px solid rgba(0,0,0,.03); transition:all .3s ease; height:100%; }
  .service-card:hover { box-shadow:0 20px 40px rgba(0,0,0,.05); transform:translateY(-5px); }
  .service-card.dark { background:var(--slate-gray); color:white; }
  .service-icon-wrapper { width:60px; height:60px; border-radius:16px; display:flex; align-items:center; justify-content:center; margin-bottom:24px; }
  .cta-section { padding:80px 0; }
  .cta-gradient-box { background:linear-gradient(to right,var(--primary-blue),var(--soft-blue)); border-radius:48px; padding:80px 40px; color:white; text-align:center; position:relative; overflow:hidden; }
  .cta-gradient-box h2 { font-size:2.5rem; font-weight:800; margin-bottom:1.5rem; }
  .btn-cta { background:white; color:var(--primary-blue); font-weight:700; padding:1rem 2.5rem; border-radius:16px; border:none; font-size:1.25rem; transition:transform .2s; }
  .btn-cta:hover { transform:scale(1.05); }
  footer { background-color:white; padding:60px 0 30px; border-top:1px solid #eee; }
  .footer-heading { font-weight:700; margin-bottom:1.5rem; font-size:1.1rem; }
  .footer-links { list-style:none; padding:0; }
  .footer-links li { margin-bottom:.75rem; }
  .footer-links a { color:#6c757d; text-decoration:none; font-size:.9rem; transition:color .2s; }
  .footer-links a:hover { color:var(--primary-blue); }
  .section-title { font-size:2.5rem; font-weight:800; margin-bottom:1.5rem; }
  .text-muted-custom { color:#64748b; }
  @media (max-width:991.98px) { .hero-title{font-size:2.5rem;} .hero-section{padding-top:80px;} }
</style>
</head>
<body>

<nav class="navbar navbar-expand-lg sticky-top">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center gap-2" href="home.php">
      <img src="../../../../assets/logo.png" height="40" alt="JumeauNum" onerror="this.style.display='none'"/>
      <span class="fs-5 fw-bold">JumeauNum</span>
    </a>
    <button class="navbar-toggler" data-bs-target="#navbarNav" data-bs-toggle="collapse" type="button">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav mx-auto">
        <li class="nav-item"><a class="nav-link" href="home.php">Accueil</a></li>

        <!-- DROPDOWN SERVICES -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            Services
          </a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="../../../gestion_rdv/vue/frontoffice/mes_rdv.php">
              <span class="material-symbols-outlined">calendar_month</span> Mes Rendez-vous
            </a></li>
            <li><a class="dropdown-item" href="../../../gestion_dossier/vue/frontoffice/mon_dossier.php">
              <span class="material-symbols-outlined">clinical_notes</span> Mon Dossier Médical
            </a></li>
            <li><a class="dropdown-item" href="../../../gestion_admission/vue/frontoffice/mes_admissions.php">
              <span class="material-symbols-outlined">local_hospital</span> Mes Admissions
            </a></li>
            <li><hr class="dropdown-divider"/></li>
            <li><a class="dropdown-item" href="../../../gestion_paiement/vue/frontoffice/mes_factures.php">
              <span class="material-symbols-outlined">receipt_long</span> Mes Factures
            </a></li>
            <li><a class="dropdown-item" href="../../../gestion_ressource/vue/frontoffice/les_ressources.php">
              <span class="material-symbols-outlined">inventory_2</span> Les Ressources
            </a></li>
            <li><a class="dropdown-item" href="les_medecins.php">
              <span class="material-symbols-outlined">stethoscope</span> Les Médecins
            </a></li>
          </ul>
        </li>

        <li class="nav-item"><a class="nav-link" href="#">À propos</a></li>
        <li class="nav-item"><a class="nav-link" href="#">Contact</a></li>
      </ul>
      <div class="d-flex align-items-center gap-3">
        <div class="welcome-pill">
          <div class="avatar"><?= strtoupper(mb_substr($user['prenom'],0,1)).strtoupper(mb_substr($user['nom'],0,1)) ?></div>
          Bonjour, <?= htmlspecialchars($user['prenom']) ?> !
        </div>
        <a href="../../controleur/frontoffice/logout.php" class="btn-logout text-decoration-none">
          <span class="material-symbols-outlined" style="font-size:16px;">logout</span> Déconnexion
        </a>
      </div>
    </div>
  </div>
</nav>

<main>
<section class="hero-section">
  <div class="container">
    <div class="row align-items-center">
      <div class="col-lg-6 mb-5 mb-lg-0">
        <div class="badge-innovation">Innovation Hospitalière</div>
        <h1 class="hero-title">Le Jumeau Numérique au Service de <span>Votre Santé</span></h1>
        <p class="lead text-muted-custom mb-4">JumeauNum révolutionne votre parcours patient grâce à une réplique virtuelle précise de l'environnement hospitalier.</p>
        <div class="d-flex gap-3 flex-wrap">
          <a href="../../../gestion_rdv/vue/frontoffice/mes_rdv.php" class="btn btn-primary-custom px-4 py-3 shadow-sm text-decoration-none">Mes rendez-vous</a>
          <a href="../../../gestion_dossier/vue/frontoffice/mon_dossier.php" class="btn btn-outline-secondary px-4 py-3 rounded-4 border-2 fw-bold text-primary border-light bg-white shadow-sm text-decoration-none">Mon dossier médical</a>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="hero-image-container position-relative">
          <img src="../../../../assets/bg1.jpg" alt="JumeauNum" onerror="this.src='https://placehold.co/600x400/2563eb/ffffff?text=JumeauNum'"/>
          <div class="position-absolute bottom-0 start-0 p-4 bg-white rounded-4 shadow-lg m-4 d-none d-md-flex align-items-center gap-3">
            <div class="bg-info bg-opacity-10 p-2 rounded-circle"><span class="material-symbols-outlined text-info">monitoring</span></div>
            <div>
              <small class="text-uppercase text-muted fw-bold d-block" style="font-size:.65rem;letter-spacing:1px;">Activité Temps Réel</small>
              <span class="fw-black fs-5">98.4% Précision</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ESPACE RAPIDE -->
<section class="py-5" style="background:white;">
  <div class="container">
    <h2 class="section-title text-center mb-2">Votre espace, <?= htmlspecialchars($user['prenom']) ?></h2>
    <p class="text-center text-muted-custom mb-5">Tout ce dont vous avez besoin en un clic.</p>
    <div class="row g-4 justify-content-center">
      <div class="col-md-3"><a href="../../../gestion_rdv/vue/frontoffice/mes_rdv.php" class="text-decoration-none">
        <div class="service-card text-center h-100">
          <div class="service-icon-wrapper mx-auto mb-3" style="background:rgba(37,99,235,.1);"><span class="material-symbols-outlined text-primary fs-2">calendar_month</span></div>
          <h5 class="fw-bold mb-2">Mes Rendez-vous</h5><p class="text-muted-custom small">Consulter ou prendre un nouveau rendez-vous.</p>
        </div>
      </a></div>
      <div class="col-md-3"><a href="../../../gestion_dossier/vue/frontoffice/mon_dossier.php" class="text-decoration-none">
        <div class="service-card text-center h-100">
          <div class="service-icon-wrapper mx-auto mb-3" style="background:rgba(20,184,166,.1);"><span class="material-symbols-outlined fs-2" style="color:var(--dark-teal)">clinical_notes</span></div>
          <h5 class="fw-bold mb-2">Mon Dossier</h5><p class="text-muted-custom small">Accéder à vos consultations et historique médical.</p>
        </div>
      </a></div>
      <div class="col-md-3"><a href="../../../gestion_des_paiements/vue/frontoffice/mes_factures.php" class="text-decoration-none">
        <div class="service-card text-center h-100">
          <div class="service-icon-wrapper mx-auto mb-3" style="background:rgba(139,92,246,.1);"><span class="material-symbols-outlined fs-2" style="color:var(--soft-blue)">receipt_long</span></div>
          <h5 class="fw-bold mb-2">Mes Factures</h5><p class="text-muted-custom small">Consulter et payer vos factures en ligne.</p>
        </div>
      </a></div>
      <div class="col-md-3"><a href="../../../gestion_admission/vue/frontoffice/mes_admissions.php" class="text-decoration-none">
        <div class="service-card text-center h-100">
          <div class="service-icon-wrapper mx-auto mb-3" style="background:rgba(16,163,74,.1);"><span class="material-symbols-outlined fs-2 text-success">local_hospital</span></div>
          <h5 class="fw-bold mb-2">Mes Admissions</h5><p class="text-muted-custom small">Suivre vos entrées et séjours hospitaliers.</p>
        </div>
      </a></div>
    </div>
  </div>
</section>

<!-- POURQUOI JUMEAUNUM -->
<section class="feature-section">
  <div class="container">
    <div class="row align-items-center g-5">
      <div class="col-lg-6 order-2 order-lg-1">
        <div class="row g-4">
          <div class="col-6"><div class="feature-box" style="aspect-ratio:1/1;"><span class="material-symbols-outlined icon-large">precision_manufacturing</span><h3>Précision Absolue</h3></div></div>
          <div class="col-6 mt-5"><div class="feature-box shadow-sm" style="aspect-ratio:1/1;background:white;"><span class="material-symbols-outlined icon-large" style="color:var(--dark-teal)">update</span><h3>Sync Temps Réel</h3></div></div>
        </div>
      </div>
      <div class="col-lg-6 order-1 order-lg-2">
        <h2 class="section-title">Pourquoi JumeauNum ?</h2>
        <p class="text-muted-custom fs-5 mb-4">Un moteur intelligent de <strong>monitoring en temps réel</strong> et de <strong>gestion digitale prédictive</strong> des processus hospitaliers.</p>
        <ul class="list-unstyled">
          <li class="d-flex align-items-start gap-3 mb-3"><span class="material-symbols-outlined text-primary">check_circle</span><span class="fw-medium">Optimisation des flux de patients pour réduire l'attente.</span></li>
          <li class="d-flex align-items-start gap-3 mb-3"><span class="material-symbols-outlined text-primary">check_circle</span><span class="fw-medium">Maintenance prédictive des équipements vitaux.</span></li>
          <li class="d-flex align-items-start gap-3"><span class="material-symbols-outlined text-primary">check_circle</span><span class="fw-medium">Simulation de scénarios d'urgence avant leur occurrence.</span></li>
        </ul>
      </div>
    </div>
  </div>
</section>

<!-- CTA -->
<section class="cta-section">
  <div class="container">
    <div class="cta-gradient-box">
      <div class="row justify-content-center">
        <div class="col-lg-8">
          <h2>Prêt à gérer votre santé en ligne ?</h2>
          <p class="fs-5 opacity-75 mb-5">Bienvenue <?= htmlspecialchars($user['prenom'].' '.$user['nom']) ?>, votre espace patient est entièrement disponible.</p>
          <a href="../../../gestion_rdv/vue/frontoffice/mes_rdv.php" class="btn btn-cta shadow-lg text-decoration-none">Prendre un rendez-vous</a>
        </div>
      </div>
      <span class="material-symbols-outlined position-absolute opacity-10 d-none d-lg-block" style="font-size:200px;right:-50px;top:0;">health_and_safety</span>
    </div>
  </div>
</section>
</main>

<footer>
  <div class="container">
    <div class="row g-4">
      <div class="col-md-3"><h5 class="footer-heading">JumeauNum</h5><p class="text-muted-custom small">© 2024 JumeauNum. Le futur de la précision hospitalière.</p></div>
      <div class="col-md-3">
        <h5 class="footer-heading">Services</h5>
        <ul class="footer-links">
          <li><a href="../../../gestion_rdv/vue/frontoffice/mes_rdv.php">Rendez-vous</a></li>
          <li><a href="../../../gestion_dossier/vue/frontoffice/mon_dossier.php">Dossier médical</a></li>
          <li><a href="../../../gestion_des_paiements/vue/frontoffice/mes_factures.php">Factures</a></li>
        </ul>
      </div>
      <div class="col-md-3"><h5 class="footer-heading">Légal</h5><ul class="footer-links"><li><a href="#">Confidentialité</a></li><li><a href="#">Mentions Légales</a></li></ul></div>
      <div class="col-md-3"><h5 class="footer-heading">Contact</h5><p class="text-muted-custom small">Tunis, Tunisie</p></div>
    </div>
  </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
  const navbar = document.querySelector('.navbar');
  window.addEventListener('scroll', () => { navbar.style.boxShadow = window.scrollY > 50 ? '0 10px 30px rgba(0,0,0,0.05)' : 'none'; });
</script>
</body>
</html>
