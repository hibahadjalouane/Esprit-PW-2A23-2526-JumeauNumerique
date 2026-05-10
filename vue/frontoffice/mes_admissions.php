<?php
/**
 * mes_admissions.php
 * Vue frontoffice : page Mes Admissions du patient
 *
 * Placement : gestion_admission/vue/frontoffice/mes_admissions.php
 */

require_once __DIR__ . '/../../controleur/frontoffice/admission_patient.php';
// Les variables $user, $admissionActive, $historique, $stats, $totalAdmissions
// sont injectees par le controleur.

$prenom = htmlspecialchars($user['prenom']);
$nom    = htmlspecialchars($user['nom']);

// Formater la date d'arrivee pour l'affichage
function formatDate(string $dateStr, string $format = 'd M Y'): string {
    if (empty($dateStr)) return 'N/A';
    $months = [
        '01' => 'Jan', '02' => 'Fev', '03' => 'Mar', '04' => 'Avr',
        '05' => 'Mai', '06' => 'Jun', '07' => 'Jul', '08' => 'Aou',
        '09' => 'Sep', '10' => 'Oct', '11' => 'Nov', '12' => 'Dec'
    ];
    try {
        $dt  = new DateTime($dateStr);
        $m   = $dt->format('m');
        return $dt->format('d') . ' ' . ($months[$m] ?? $m) . ' ' . $dt->format('Y');
    } catch (Exception $e) {
        return $dateStr;
    }
}

// Pourcentage fictif mais lie aux stats : si total > 0, calcule progression
$progressPct = 0;
if ($totalAdmissions > 0 && $admissionActive) {
    $progressPct = min(100, (int)(($stats['normal'] / max(1, $totalAdmissions)) * 100 + 30));
    $progressPct = max(20, $progressPct);
}

$hasActive = !empty($admissionActive);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>JumeauNum – Mes Admissions</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>

<style>
/* ================================================================
   TOKENS DE DESIGN
================================================================ */
:root {
  --primary:            #2563eb;
  --primary-dk:         #1d4ed8;
  --primary-lt:         #eff4ff;
  --primary-ring:       rgba(37, 99, 235, .12);
  --teal:               #14b8a6;
  --teal-lt:            #f0fdfa;
  --violet:             #7c3aed;
  --bg:                 #f5f6ff;
  --surface:            #ffffff;
  --surface2:           #f0f2ff;
  --border:             #e4e7f8;
  --text:               #0f172a;
  --muted:              #64748b;
  --muted-lt:           #94a3b8;
  --green:              #16a34a;
  --green-lt:           #dcfce7;
  --red:                #dc2626;
  --red-lt:             #fee2e2;
  --amber:              #d97706;
  --amber-lt:           #fef3c7;
  --dark-card:          #0f1e3d;
  --dark-card-text:     rgba(255,255,255,.85);
  --radius-sm:          8px;
  --radius-md:          14px;
  --radius-lg:          20px;
  --radius-xl:          28px;
  --shadow-sm:          0 1px 3px rgba(0,0,0,.06), 0 1px 2px rgba(0,0,0,.04);
  --shadow-md:          0 4px 16px rgba(37,99,235,.10);
  --shadow-lg:          0 12px 40px rgba(37,99,235,.14);
}

/* ================================================================
   RESET & BASE
================================================================ */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
  font-family: 'DM Sans', sans-serif;
  background: var(--bg);
  color: var(--text);
  min-height: 100vh;
  padding-top: 72px;
}

.font-display { font-family: 'Plus Jakarta Sans', sans-serif; }

.material-symbols-outlined {
  font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
  line-height: 1;
  vertical-align: middle;
  display: inline-flex;
}
.fill-icon { font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24; }

/* ================================================================
   TOP NAV
================================================================ */
.top-nav {
  position: fixed;
  top: 0; left: 0; right: 0;
  z-index: 1050;
  height: 72px;
  background: rgba(255,255,255,.82);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  border-bottom: 1px solid var(--border);
  display: flex;
  align-items: center;
  padding: 0 2rem;
  gap: 1rem;
}

.brand {
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-weight: 800;
  font-size: 1.15rem;
  color: var(--primary);
  text-decoration: none;
  letter-spacing: -.02em;
}

.nav-sep {
  width: 1px;
  height: 22px;
  background: var(--border);
  flex-shrink: 0;
}

.nav-link-pill {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 14px;
  border-radius: 50px;
  font-size: .875rem;
  font-weight: 600;
  color: var(--muted);
  text-decoration: none;
  transition: all .2s ease;
  white-space: nowrap;
}
.nav-link-pill:hover {
  color: var(--primary);
  background: var(--primary-lt);
}
.nav-link-pill.active {
  color: var(--primary);
  background: var(--primary-lt);
}
.nav-link-pill .material-symbols-outlined { font-size: 1rem; }

.nav-back {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px 6px 8px;
  border-radius: 50px;
  font-size: .875rem;
  font-weight: 600;
  color: var(--muted);
  text-decoration: none;
  border: 1px solid var(--border);
  background: var(--surface);
  transition: all .2s ease;
}
.nav-back:hover {
  color: var(--primary);
  border-color: var(--primary);
  background: var(--primary-lt);
}
.nav-back .material-symbols-outlined { font-size: 1rem; }

.nav-spacer { flex: 1; }

.user-chip {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 5px 14px 5px 5px;
  border-radius: 50px;
  border: 1px solid var(--border);
  background: var(--surface);
  font-size: .82rem;
  font-weight: 600;
  color: var(--text);
  white-space: nowrap;
}
.user-chip .avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: var(--primary);
  color: #fff;
  font-size: .72rem;
  font-weight: 800;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  letter-spacing: .02em;
}

.btn-logout-nav {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 7px 14px;
  border-radius: 10px;
  border: 1px solid var(--border);
  background: var(--surface);
  font-size: .82rem;
  font-weight: 600;
  color: var(--muted);
  text-decoration: none;
  transition: all .2s ease;
  white-space: nowrap;
}
.btn-logout-nav:hover {
  border-color: var(--red);
  color: var(--red);
  background: var(--red-lt);
}
.btn-logout-nav .material-symbols-outlined { font-size: .95rem; }

/* ================================================================
   PAGE WRAPPER
================================================================ */
.page-wrap {
  max-width: 1260px;
  margin: 0 auto;
  padding: 2.5rem 2rem 4rem;
}

.page-header {
  margin-bottom: 2.5rem;
}
.page-header h1 {
  font-size: 2rem;
  font-weight: 800;
  letter-spacing: -.03em;
  margin-bottom: .3rem;
}
.page-header p {
  color: var(--muted);
  font-size: .95rem;
}

/* ================================================================
   CONTENT GRID
================================================================ */
.content-grid {
  display: grid;
  grid-template-columns: minmax(0, 2fr) 340px;
  gap: 1.75rem;
  align-items: start;
}

/* ================================================================
   CARDS
================================================================ */
.card-white {
  background: var(--surface);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--border);
}

.card-dark {
  background: var(--dark-card);
  border-radius: var(--radius-lg);
  color: var(--dark-card-text);
  position: relative;
  overflow: hidden;
}

.card-teal {
  background: var(--teal-lt);
  border: 1px solid rgba(20,184,166,.18);
  border-radius: var(--radius-lg);
}

.card-surface {
  background: var(--surface2);
  border-radius: var(--radius-lg);
  border: 1px solid var(--border);
}

/* ================================================================
   ACTIVE ADMISSION CARD
================================================================ */
.active-card {
  padding: 2rem;
  border-left: 5px solid var(--primary);
  position: relative;
}

.status-badge-active {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: var(--green-lt);
  color: var(--green);
  padding: 4px 12px;
  border-radius: 50px;
  font-size: .72rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: .1em;
}
.status-badge-active::before {
  content: '';
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: var(--green);
  animation: pulse-dot 1.8s infinite;
}
@keyframes pulse-dot {
  0%, 100% { opacity: 1; transform: scale(1); }
  50% { opacity: .5; transform: scale(.7); }
}

.icon-box-primary {
  width: 72px;
  height: 72px;
  background: var(--primary-lt);
  border-radius: var(--radius-md);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.icon-box-primary .material-symbols-outlined {
  font-size: 2rem;
  color: var(--primary);
}

.meta-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
  gap: 1rem;
}
.meta-item .label {
  font-size: .68rem;
  text-transform: uppercase;
  font-weight: 700;
  letter-spacing: .1em;
  color: var(--muted-lt);
  margin-bottom: .25rem;
}
.meta-item .value {
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-weight: 600;
  font-size: .92rem;
  color: var(--text);
}

.btn-primary-soft {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  padding: .65rem 1.4rem;
  background: var(--primary);
  color: #fff;
  border: none;
  border-radius: var(--radius-sm);
  font-size: .875rem;
  font-weight: 700;
  cursor: pointer;
  transition: opacity .2s;
  text-decoration: none;
}
.btn-primary-soft:hover { opacity: .88; color: #fff; }
.btn-primary-soft .material-symbols-outlined { font-size: 1rem; }

.btn-surface-soft {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  padding: .65rem 1.4rem;
  background: var(--surface2);
  color: var(--muted);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  font-size: .875rem;
  font-weight: 600;
  cursor: pointer;
  transition: all .2s;
  text-decoration: none;
}
.btn-surface-soft:hover { background: var(--border); color: var(--text); }

/* ================================================================
   NO ACTIVE ADMISSION
================================================================ */
.no-active-card {
  padding: 3rem 2rem;
  text-align: center;
  background: var(--surface);
  border-radius: var(--radius-lg);
  border: 2px dashed var(--border);
}
.no-active-icon {
  width: 72px;
  height: 72px;
  background: var(--surface2);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1rem;
}
.no-active-icon .material-symbols-outlined {
  font-size: 2rem;
  color: var(--muted-lt);
}

/* ================================================================
   HISTORIQUE
================================================================ */
.section-heading {
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-size: 1.1rem;
  font-weight: 800;
  color: var(--text);
  letter-spacing: -.02em;
}

.history-row {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1.1rem 1.25rem;
  background: var(--surface);
  border-radius: var(--radius-md);
  border: 1px solid var(--border);
  transition: all .2s ease;
  cursor: pointer;
  text-decoration: none;
  color: inherit;
}
.history-row:hover {
  border-color: var(--primary);
  box-shadow: var(--shadow-md);
  transform: translateY(-1px);
}
.history-icon {
  width: 44px;
  height: 44px;
  border-radius: var(--radius-sm);
  background: var(--surface2);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  color: var(--muted);
}
.history-icon .material-symbols-outlined { font-size: 1.25rem; }

.history-info { flex: 1; min-width: 0; }
.history-info .h-title {
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-weight: 700;
  font-size: .9rem;
  color: var(--text);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.history-info .h-sub {
  font-size: .78rem;
  color: var(--muted);
  margin-top: 2px;
}

.chip-mode {
  display: inline-block;
  padding: 3px 10px;
  border-radius: 50px;
  font-size: .7rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .06em;
}
.chip-urgence   { background: var(--red-lt);   color: var(--red); }
.chip-normal    { background: var(--green-lt);  color: var(--green); }
.chip-transfert { background: var(--amber-lt);  color: var(--amber); }
.chip-autre     { background: var(--primary-lt);color: var(--primary); }

.chevron-btn {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--muted-lt);
  flex-shrink: 0;
  transition: all .2s;
}
.history-row:hover .chevron-btn {
  background: var(--primary-lt);
  color: var(--primary);
}
.chevron-btn .material-symbols-outlined { font-size: 1.1rem; }

.empty-history {
  padding: 2rem;
  text-align: center;
  color: var(--muted);
  font-size: .875rem;
}

/* ================================================================
   ASIDE CARDS
================================================================ */

/* INSIGHT CARD */
.insight-card {
  padding: 1.75rem;
  position: relative;
  overflow: hidden;
}
.insight-card .watermark {
  position: absolute;
  right: -1.5rem;
  bottom: -1.5rem;
  font-size: 8rem;
  opacity: .07;
  color: #fff;
  line-height: 1;
  pointer-events: none;
}
.insight-tag {
  display: inline-block;
  background: rgba(255,255,255,.15);
  color: rgba(255,255,255,.9);
  font-size: .68rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .12em;
  padding: 3px 10px;
  border-radius: 50px;
  margin-bottom: 1rem;
  border: 1px solid rgba(255,255,255,.2);
}
.insight-title {
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-size: 1.1rem;
  font-weight: 800;
  color: #fff;
  margin-bottom: .6rem;
}
.insight-body {
  font-size: .82rem;
  line-height: 1.6;
  opacity: .75;
  margin-bottom: 1.25rem;
}

.stat-row {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: .5rem;
  margin-bottom: 1.25rem;
}
.stat-pill {
  background: rgba(255,255,255,.1);
  border: 1px solid rgba(255,255,255,.12);
  border-radius: 10px;
  padding: .6rem .5rem;
  text-align: center;
}
.stat-pill .num {
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-size: 1.15rem;
  font-weight: 800;
  color: #fff;
  display: block;
}
.stat-pill .lbl {
  font-size: .62rem;
  text-transform: uppercase;
  letter-spacing: .08em;
  opacity: .6;
  display: block;
  margin-top: 2px;
}

.progress-track {
  width: 100%;
  height: 6px;
  background: rgba(255,255,255,.15);
  border-radius: 99px;
  overflow: hidden;
  margin-bottom: .5rem;
}
.progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #6ee7b7, #14b8a6);
  border-radius: 99px;
  transition: width .6s ease;
}
.progress-labels {
  display: flex;
  justify-content: space-between;
  font-size: .62rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .1em;
  opacity: .5;
  color: #fff;
}

/* QUICK LINKS */
.kicker {
  font-size: .68rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: .14em;
  color: var(--muted-lt);
  margin-bottom: .75rem;
}
.quick-link {
  display: flex;
  align-items: center;
  gap: .75rem;
  padding: .8rem .9rem;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  text-decoration: none;
  color: var(--text);
  font-size: .875rem;
  font-weight: 600;
  transition: all .2s ease;
}
.quick-link:hover {
  background: var(--primary-lt);
  border-color: var(--primary);
  color: var(--primary);
}
.quick-link .ql-icon {
  width: 34px;
  height: 34px;
  background: var(--primary-lt);
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  color: var(--primary);
  transition: background .2s;
}
.quick-link:hover .ql-icon {
  background: rgba(37,99,235,.2);
}
.quick-link .ql-icon .material-symbols-outlined { font-size: 1.1rem; }
.quick-link .ql-chevron {
  margin-left: auto;
  color: var(--muted-lt);
  font-size: .9rem;
}

/* LOCALISATION CARD */
.loc-card {
  padding: 1.5rem;
}
.loc-map-placeholder {
  height: 140px;
  background: linear-gradient(135deg, #dbeafe 0%, #eff4ff 100%);
  border-radius: var(--radius-md);
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 1rem;
  position: relative;
  overflow: hidden;
}
.loc-map-placeholder::before {
  content: '';
  position: absolute;
  inset: 0;
  background: repeating-linear-gradient(
    45deg,
    transparent,
    transparent 20px,
    rgba(37,99,235,.04) 20px,
    rgba(37,99,235,.04) 40px
  );
}
.loc-pin {
  width: 56px;
  height: 56px;
  background: #fff;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 8px 24px rgba(37,99,235,.2);
  position: relative;
  z-index: 1;
}
.loc-pin .material-symbols-outlined {
  font-size: 1.8rem;
  color: var(--primary);
}
.loc-detail { display: flex; align-items: flex-start; gap: .75rem; }
.loc-detail .material-symbols-outlined { color: var(--primary); font-size: 1.2rem; margin-top: 1px; }
.loc-detail .loc-addr { font-weight: 700; font-size: .875rem; color: var(--text); margin-bottom: 2px; }
.loc-detail .loc-hint { font-size: .75rem; color: var(--muted); }

/* ================================================================
   RESPONSIVE
================================================================ */
@media (max-width: 991.98px) {
  .content-grid { grid-template-columns: 1fr; }
  .top-nav { padding: 0 1rem; }
  .page-wrap { padding: 2rem 1rem 3rem; }
}

@media (max-width: 767.98px) {
  body { padding-top: 64px; }
  .top-nav { height: 64px; }
  .page-header h1 { font-size: 1.5rem; }
  .active-card { padding: 1.5rem; }
  .meta-grid { grid-template-columns: repeat(2, 1fr); }
  .stat-row { grid-template-columns: repeat(2, 1fr); }
  .user-chip span.name { display: none; }
}

/* ================================================================
   ANIMATIONS
================================================================ */
@keyframes fadeUp {
  from { opacity: 0; transform: translateY(12px); }
  to   { opacity: 1; transform: translateY(0); }
}
.fade-up {
  animation: fadeUp .4s ease both;
}
.fade-up-1 { animation-delay: .05s; }
.fade-up-2 { animation-delay: .12s; }
.fade-up-3 { animation-delay: .20s; }
.fade-up-4 { animation-delay: .28s; }
</style>
</head>
<body>

<!-- ================================================================
     TOP NAV
================================================================ -->
<nav class="top-nav">
  <!-- Retour accueil -->
  <a href="../../../gestion_user/vue/frontoffice/home.php" class="nav-back">
    <span class="material-symbols-outlined">arrow_back</span>
    Accueil
  </a>

  <div class="nav-sep"></div>

  <!-- Lien actif : Mes Admissions -->
  <a href="mes_admissions.php" class="nav-link-pill active">
    <span class="material-symbols-outlined fill-icon">local_hospital</span>
    Mes Admissions
  </a>

  <div class="nav-spacer"></div>

  <!-- User chip -->
  <div class="user-chip">
    <div class="avatar">
      <?= strtoupper(mb_substr($user['prenom'],0,1)) . strtoupper(mb_substr($user['nom'],0,1)) ?>
    </div>
    <span class="name"><?= $prenom ?></span>
  </div>

  <!-- Deconnexion -->
  <a href="../../controleur/frontoffice/logout.php" class="btn-logout-nav">
    <span class="material-symbols-outlined">logout</span>
    Deconnexion
  </a>
</nav>

<!-- ================================================================
     PAGE CONTENT
================================================================ -->
<main class="page-wrap">

  <!-- Page header -->
  <header class="page-header fade-up">
    <h1 class="font-display">Mes Admissions</h1>
    <p>Gerez vos sejours hospitaliers et consultez vos admissions en temps reel.</p>
  </header>

  <div class="content-grid">

    <!-- ============================================================
         COLONNE GAUCHE
    ============================================================ -->
    <section>

      <!-- ADMISSION ACTIVE -->
      <?php if ($hasActive): ?>
        <?php
          $activeDate  = formatDate($admissionActive['date_arrive_relle']);
          $activeMode  = htmlspecialchars(ucfirst($admissionActive['mode_entree']));
          $activeSalle = $admissionActive['salle_numero']
                         ? 'Salle ' . htmlspecialchars($admissionActive['salle_numero'])
                         : 'Non assignee';
          $activeId    = htmlspecialchars($admissionActive['id_admission']);
        ?>
        <div class="card-white active-card fade-up fade-up-1">
          <!-- Badge statut -->
          <div class="d-flex justify-content-between align-items-start mb-4">
            <span class="status-badge-active">Admission Active</span>
          </div>

          <div class="d-flex gap-4 align-items-start flex-wrap flex-md-nowrap">
            <div class="icon-box-primary">
              <span class="material-symbols-outlined fill-icon">meeting_room</span>
            </div>

            <div class="flex-grow-1">
              <h2 class="font-display fw-bold mb-1" style="font-size:1.35rem;">Admission en cours</h2>
              <p style="font-size:.85rem;color:var(--primary);font-weight:700;margin-bottom:1.5rem;">
                ID: #ADM-<?= str_pad($activeId, 5, '0', STR_PAD_LEFT) ?>
              </p>

              <div class="meta-grid mb-4">
                <div class="meta-item">
                  <div class="label">Date d'arrivee</div>
                  <div class="value"><?= $activeDate ?></div>
                </div>
                <div class="meta-item">
                  <div class="label">Salle assignee</div>
                  <div class="value"><?= $activeSalle ?></div>
                </div>
                <div class="meta-item">
                  <div class="label">Mode d'entree</div>
                  <div class="value"><?= $activeMode ?></div>
                </div>
                <?php if (!empty($admissionActive['id_ticket'])): ?>
                <div class="meta-item">
                  <div class="label">Ticket</div>
                  <div class="value">#<?= htmlspecialchars($admissionActive['id_ticket']) ?></div>
                </div>
                <?php endif; ?>
              </div>

              <div class="d-flex gap-3 flex-wrap">
                <button class="btn-primary-soft">
                  <span class="material-symbols-outlined">info</span>
                  Details complets
                </button>
                <button class="btn-surface-soft">Contacter l'unite</button>
              </div>
            </div>
          </div>
        </div>
      <?php else: ?>
        <!-- Pas d'admission active -->
        <div class="no-active-card fade-up fade-up-1">
          <div class="no-active-icon">
            <span class="material-symbols-outlined">local_hospital</span>
          </div>
          <h3 class="font-display fw-bold mb-2" style="font-size:1.1rem;">Aucune admission active</h3>
          <p style="font-size:.875rem;color:var(--muted);max-width:340px;margin:0 auto;">
            Vous n'avez pas d'admission en cours pour le moment. Consultez votre historique ci-dessous.
          </p>
        </div>
      <?php endif; ?>

      <!-- HISTORIQUE -->
      <div class="mt-5 fade-up fade-up-2">
        <div class="d-flex align-items-center justify-content-between mb-3">
          <span class="section-heading">Historique des Admissions</span>
          <span style="font-size:.78rem;color:var(--muted);font-weight:600;">
            <?= $totalAdmissions ?> admission<?= $totalAdmissions > 1 ? 's' : '' ?>
          </span>
        </div>

        <?php if (empty($historique)): ?>
          <div class="card-white">
            <div class="empty-history">
              <span class="material-symbols-outlined" style="font-size:2rem;color:var(--muted-lt);display:block;margin-bottom:.5rem;">history</span>
              Aucun historique d'admission disponible.
            </div>
          </div>
        <?php else: ?>
          <div class="d-flex flex-column gap-2">
            <?php foreach ($historique as $idx => $adm):
              $mode  = strtolower(trim($adm['mode_entree']));
              $chips = ['urgence' => 'chip-urgence', 'normal' => 'chip-normal', 'transfert' => 'chip-transfert'];
              $chipCls = $chips[$mode] ?? 'chip-autre';
              $modeLabel = ucfirst($adm['mode_entree']);
              $dateAdm = formatDate($adm['date_arrive_relle']);
              $salleAdm = $adm['salle_numero'] ? 'Salle ' . htmlspecialchars($adm['salle_numero']) : 'Salle non assignee';
              $admIdPad = str_pad($adm['id_admission'], 5, '0', STR_PAD_LEFT);
              $isActive = ($hasActive && $adm['id_admission'] == $admissionActive['id_admission']);
            ?>
            <div class="history-row" style="animation: fadeUp .35s ease <?= (.08 * $idx) ?>s both;">
              <div class="history-icon">
                <span class="material-symbols-outlined"><?= $isActive ? 'meeting_room' : 'history' ?></span>
              </div>
              <div class="history-info">
                <div class="h-title">
                  Admission <?= $isActive ? '<span style="color:var(--green);font-size:.7em;font-weight:800;text-transform:uppercase;">• Active</span>' : '' ?>
                </div>
                <div class="h-sub">ID: #ADM-<?= $admIdPad ?> &nbsp;•&nbsp; <?= $salleAdm ?></div>
              </div>
              <div class="d-none d-md-block text-end" style="min-width:90px;">
                <div style="font-size:.68rem;text-transform:uppercase;letter-spacing:.08em;color:var(--muted-lt);font-weight:700;">Date</div>
                <div style="font-size:.82rem;font-weight:700;color:var(--text);"><?= $dateAdm ?></div>
              </div>
              <span class="chip-mode <?= $chipCls ?>"><?= $modeLabel ?></span>
              <div class="chevron-btn">
                <span class="material-symbols-outlined">chevron_right</span>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </section>

    <!-- ============================================================
         COLONNE DROITE (aside)
    ============================================================ -->
    <aside class="d-flex flex-column gap-4">

      <!-- SUIVI / STATS -->
      <div class="card-dark insight-card fade-up fade-up-2">
        <span class="material-symbols-outlined watermark">monitoring</span>
        <div class="position-relative" style="z-index:1;">
          <span class="insight-tag">Suivi de Recuperation</span>
          <h3 class="insight-title">Vos statistiques</h3>
          <p class="insight-body">
            <?php if ($totalAdmissions > 0): ?>
              Vous avez un total de <strong><?= $totalAdmissions ?></strong> admission<?= $totalAdmissions > 1 ? 's' : '' ?> enregistree<?= $totalAdmissions > 1 ? 's' : '' ?>.
              <?php if ($hasActive): ?> Une admission est actuellement active.<?php endif; ?>
            <?php else: ?>
              Aucune admission enregistree pour le moment.
            <?php endif; ?>
          </p>

          <?php if ($totalAdmissions > 0): ?>
          <div class="stat-row">
            <div class="stat-pill">
              <span class="num"><?= $totalAdmissions ?></span>
              <span class="lbl">Total</span>
            </div>
            <div class="stat-pill">
              <span class="num"><?= $stats['urgence'] ?></span>
              <span class="lbl">Urgences</span>
            </div>
            <div class="stat-pill">
              <span class="num"><?= $stats['normal'] ?></span>
              <span class="lbl">Normales</span>
            </div>
          </div>

          <?php if ($hasActive): ?>
          <div class="progress-track">
            <div class="progress-fill" style="width: <?= $progressPct ?>%;"></div>
          </div>
          <div class="progress-labels">
            <span>Admission</span>
            <span><?= $progressPct ?>% suivi</span>
            <span>Sortie</span>
          </div>
          <?php endif; ?>
          <?php endif; ?>
        </div>
      </div>

      <!-- ACTIONS RAPIDES -->
      <div class="card-surface p-4 fade-up fade-up-3">
        <div class="kicker">Actions Rapides</div>
        <div class="d-flex flex-column gap-2">
          <a class="quick-link" href="../../../gestion_rdv/vue/frontoffice/mes_rdv.php">
            <div class="ql-icon">
              <span class="material-symbols-outlined">calendar_month</span>
            </div>
            <span>Mes Rendez-vous</span>
            <span class="material-symbols-outlined ql-chevron">chevron_right</span>
          </a>
          <a class="quick-link" href="../../../gestion_dossier/vue/frontoffice/mon_dossier.php">
            <div class="ql-icon">
              <span class="material-symbols-outlined">clinical_notes</span>
            </div>
            <span>Mon Dossier Medical</span>
            <span class="material-symbols-outlined ql-chevron">chevron_right</span>
          </a>
          <a class="quick-link" href="../../../gestion_paiement/vue/frontoffice/mes_factures.php">
            <div class="ql-icon">
              <span class="material-symbols-outlined">receipt_long</span>
            </div>
            <span>Mes Factures</span>
            <span class="material-symbols-outlined ql-chevron">chevron_right</span>
          </a>
        </div>
      </div>

      <!-- LOCALISATION -->
      <div class="card-white loc-card fade-up fade-up-4">
        <div class="kicker">Localisation</div>
        <div class="loc-map-placeholder">
          <div class="loc-pin">
            <span class="material-symbols-outlined">location_on</span>
          </div>
        </div>

        <?php if ($hasActive && !empty($admissionActive['salle_numero'])): ?>
          <div class="loc-detail">
            <span class="material-symbols-outlined">navigation</span>
            <div>
              <div class="loc-addr">Salle <?= htmlspecialchars($admissionActive['salle_numero']) ?></div>
              <div class="loc-hint">Suivez le balisage bleu a partir de l'accueil central.</div>
            </div>
          </div>
        <?php elseif ($hasActive): ?>
          <div class="loc-detail">
            <span class="material-symbols-outlined">info</span>
            <div>
              <div class="loc-addr">Salle non encore assignee</div>
              <div class="loc-hint">Contactez le personnel soignant pour connaitre votre emplacement.</div>
            </div>
          </div>
        <?php else: ?>
          <div class="loc-detail">
            <span class="material-symbols-outlined">location_off</span>
            <div>
              <div class="loc-addr" style="color:var(--muted);">Aucune admission active</div>
              <div class="loc-hint">La localisation sera disponible lors de votre prochaine admission.</div>
            </div>
          </div>
        <?php endif; ?>
      </div>

    </aside>
  </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>