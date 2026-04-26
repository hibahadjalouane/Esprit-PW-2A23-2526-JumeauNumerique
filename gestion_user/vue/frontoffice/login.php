<?php
session_start();

// Anti-cache
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Sat, 01 Jan 2000 00:00:00 GMT');

// Si déjà connecté → renvoyer vers son dashboard
if (!empty($_SESSION['logged_in']) && !empty($_SESSION['id_role'])) {
    $roleId = (int) $_SESSION['id_role'];
    $map = [
        1 => 'home.php',
        2 => '../../backoffice/dashboard_admin.php',
        3 => '../../backoffice/dashboard_medecin.php',
        4 => '../../backoffice/dashboard_superadmin.php',
    ];
    header('Location: ' . ($map[$roleId] ?? 'home.php'));
    exit;
}
?>







<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>JumeauNum – Connexion</title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet"/>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --primary:    #2563eb;
      --primary-dk: #1d4ed8;
      --primary-lt: #eff4ff;
      --bg:         #f1f3fb;
      --surface:    #ffffff;
      --border:     #dde2ef;
      --text:       #111827;
      --muted:      #6b7280;
      --red:        #dc2626;
      --red-lt:     #fee2e2;
      --green:      #16a34a;
      --green-lt:   #dcfce7;
      --radius:     12px;
      --shadow:     0 4px 24px rgba(37,99,235,.10);
    }
    body { font-family: 'DM Sans', sans-serif; background: var(--bg); min-height: 100vh; display: flex; flex-direction: column; }

    /* ── PAGE WRAPPER ── */
    .page-wrap { flex: 1; display: flex; align-items: stretch; }

    /* ── LEFT PANEL ── */
    .left-panel {
      width: 48%; position: relative; overflow: hidden;
      background: linear-gradient(145deg, #0f2c7e 0%, #1742b8 50%, #0b2c86 100%);
      display: flex; flex-direction: column; justify-content: flex-end;
      padding: 48px 44px;
    }
    /* Background image overlay — user puts bg1.jpg in /assets/ */
    .left-panel::after {
      content: '';
      position: absolute; inset: 0;
      background: url('../assets/bg1.jpg') center/cover no-repeat;
      z-index: 0;
    }
    .left-panel-img {
      position: absolute; inset: 0;
      background: linear-gradient(to bottom, rgba(10,30,90,.55) 0%, rgba(10,30,90,.85) 100%);
      z-index: 1;
    }
    .left-badge {
      position: relative; z-index: 2;
      display: inline-block; background: rgba(255,255,255,.18);
      border: 1px solid rgba(255,255,255,.3); border-radius: 20px;
      padding: 4px 14px; font-size: .72rem; font-weight: 600;
      letter-spacing: .1em; color: #fff; margin-bottom: 16px;
    }
    .left-title {
      position: relative; z-index: 2;
      font-size: 2.6rem; font-weight: 800; color: #fff; line-height: 1.15;
      margin-bottom: 14px;
    }
    .left-sub {
      position: relative; z-index: 2;
      font-size: .95rem; color: rgba(255,255,255,.75); line-height: 1.6;
      margin-bottom: 32px; max-width: 380px;
    }
    .left-social {
      position: relative; z-index: 2;
      display: flex; align-items: center; gap: 12px;
      font-size: .85rem; color: rgba(255,255,255,.85); font-weight: 500;
    }
    .left-avatars { display: flex; }
    .left-avatars span {
      width: 36px; height: 36px; border-radius: 50%;
      border: 2px solid #fff; background: linear-gradient(135deg,#4b7cff,#0b2c86);
      display: inline-flex; align-items: center; justify-content: center;
      font-size: .7rem; font-weight: 700; color: #fff; margin-left: -8px;
      overflow: hidden;
    }
    .left-avatars span:first-child { margin-left: 0; }
    /* If you place avatar images in /assets/ you can swap these to <img> tags */

    /* ── RIGHT PANEL ── */
    .right-panel {
      flex: 1; background: var(--surface);
      display: flex; flex-direction: column;
      padding: 32px 52px;
    }
    .right-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 40px; }
    .brand-row { display: flex; align-items: center; gap: 10px; font-weight: 700; font-size: 1.05rem; color: var(--text); }
    /* Logo image — place logo.png in /assets/ */
    .brand-logo {
      width: 38px; height: 38px; border-radius: 10px;
      object-fit: contain; background: var(--primary);
    }
    /* Fallback icon shown if logo.png not found */
    .brand-icon { width: 38px; height: 38px; background: var(--primary); border-radius: 10px; display: flex; align-items: center; justify-content: center; }
    .brand-icon svg { width: 20px; height: 20px; }
    .no-account { font-size: .85rem; color: var(--muted); }
    .no-account a { color: var(--primary); font-weight: 600; text-decoration: none; }
    .no-account a:hover { text-decoration: underline; }

    /* ── FORM AREA ── */
    .form-title { font-size: 1.55rem; font-weight: 700; color: var(--text); margin-bottom: 6px; }
    .form-sub   { font-size: .84rem; color: var(--muted); margin-bottom: 32px; line-height: 1.5; }

    .form-group { margin-bottom: 18px; }
    .form-label { font-size: .72rem; font-weight: 600; color: var(--muted); margin-bottom: 5px; display: block; letter-spacing: .06em; text-transform: uppercase; }
    .input-wrap { position: relative; }
    .input-wrap svg.icon { position: absolute; left: 13px; top: 50%; transform: translateY(-50%); width: 16px; height: 16px; color: #9ca3af; pointer-events: none; }
    .form-control {
      width: 100%; height: 48px; border: 1.5px solid var(--border);
      background: #f8f9fd; border-radius: 10px;
      padding: 0 14px 0 40px; font-family: inherit;
      font-size: .88rem; color: var(--text); outline: none;
      transition: border-color .2s, background .2s;
    }
    .form-control:focus    { border-color: var(--primary); background: #fff; }
    .form-control.is-invalid { border-color: var(--red); background: #fff8f9; }
    .form-control::placeholder { color: #b0b8cc; }

    /* Toggle password visibility button */
    .toggle-pass {
      position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
      background: none; border: none; cursor: pointer; padding: 4px;
      color: #9ca3af; display: flex; align-items: center;
    }
    .toggle-pass:hover { color: var(--primary); }
    .form-control.has-toggle { padding-right: 42px; }

    .field-err { display: none; align-items: center; gap: 5px; font-size: .72rem; color: var(--red); margin-top: 4px; }
    .field-err.show { display: flex; }
    .field-err svg { width: 12px; height: 12px; flex-shrink: 0; }

    /* ── REMEMBER + FORGOT ── */
    .form-row-between { display: flex; align-items: center; justify-content: space-between; margin-bottom: 22px; }
    .checkbox-wrap { display: flex; align-items: center; gap: 8px; cursor: pointer; }
    .checkbox-wrap input[type="checkbox"] { width: 16px; height: 16px; accent-color: var(--primary); cursor: pointer; }
    .checkbox-wrap span { font-size: .82rem; color: var(--muted); }
    .forgot-link { font-size: .82rem; color: var(--primary); font-weight: 600; text-decoration: none; }
    .forgot-link:hover { text-decoration: underline; }

    /* ── BUTTONS ── */
    .btn-primary-full {
      width: 100%; height: 50px; border: none; border-radius: 12px;
      background: var(--primary); color: #fff;
      font-family: inherit; font-size: .92rem; font-weight: 600;
      cursor: pointer; display: flex; align-items: center; justify-content: center;
      gap: 8px; transition: background .2s, opacity .2s;
      box-shadow: 0 6px 18px rgba(37,99,235,.25);
    }
    .btn-primary-full:hover    { background: var(--primary-dk); }
    .btn-primary-full:disabled { opacity: .65; cursor: not-allowed; }

    /* ── DIVIDER ── */
    .divider { display: flex; align-items: center; gap: 12px; margin: 22px 0; }
    .divider hr { flex: 1; border: none; border-top: 1px solid var(--border); }
    .divider span { font-size: .72rem; color: var(--muted); white-space: nowrap; letter-spacing: .06em; }

    /* ── ROLE BADGES (shown after login) ── */
    .role-badge {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 4px 12px; border-radius: 20px; font-size: .75rem; font-weight: 600;
    }
    .role-patient  { background: #eff6ff; color: #2563eb; }
    .role-admin    { background: #fef3c7; color: #d97706; }
    .role-medecin  { background: #dcfce7; color: #16a34a; }
    .role-superadmin { background: #fce7f3; color: #db2777; }

    /* ── ERROR / SUCCESS MSG ── */
    .msg-box { border-radius: 8px; padding: 10px 14px; font-size: .8rem; display: none; margin-bottom: 14px; align-items: center; gap: 8px; }
    .msg-box.show { display: flex; }
    .msg-error   { background: var(--red-lt); border: 1px solid #fca5a5; color: var(--red); }
    .msg-success { background: var(--green-lt); border: 1px solid #86efac; color: var(--green); }

    /* ── TRUST BADGES ── */
    .trust-row { display: flex; align-items: center; gap: 20px; margin-top: 20px; justify-content: center; }
    .trust-item { display: flex; align-items: center; gap: 6px; font-size: .75rem; color: var(--muted); }
    .trust-dot  { width: 8px; height: 8px; border-radius: 50%; background: var(--green); }

    /* Spinner inside button */
    .spinner { width: 16px; height: 16px; border: 2px solid rgba(255,255,255,.4); border-top-color: #fff; border-radius: 50%; animation: spin .7s linear infinite; }
    @keyframes spin { to { transform: rotate(360deg); } }

    /* ── FOOTER ── */
    footer { background: var(--surface); border-top: 1px solid var(--border); padding: 16px 52px; display: flex; justify-content: space-between; align-items: center; font-size: .78rem; color: var(--muted); }
    footer a { color: var(--muted); text-decoration: none; margin-left: 20px; }
    footer a:hover { color: var(--primary); }

    @media (max-width: 768px) {
      .left-panel { display: none; }
      .right-panel { padding: 24px 20px; }
    }
  </style>
</head>
<body>

<div class="page-wrap">

  <!-- ── PANNEAU GAUCHE ── -->
  <div class="left-panel">
    <div class="left-panel-img"></div>
    <div class="left-badge">JUMEAU NUMÉRIQUE CLINIQUE</div>
    <div class="left-title">Bienvenue<br>sur JumeauNum.</div>
    <div class="left-sub">Connectez-vous à votre espace sécurisé. Gérez vos dossiers, rendez-vous et ressources en temps réel.</div>
    <div class="left-social">
      <div class="left-avatars">
        <span>M</span><span>A</span><span>D</span>
      </div>
      <span>Rejoins par 2 000+ cliniciens</span>
    </div>
  </div>

  <!-- ── PANNEAU DROIT ── -->
  <div class="right-panel">
    <div class="right-top">
      <div class="brand-row">
        <!-- Logo : si logo.png existe dans assets, on l'affiche; sinon icône fallback -->
        <img src="../assets/logo.png" class="brand-logo" alt="Logo JumeauNum"
             onerror="this.style.display='none'; document.getElementById('brandIconFallback').style.display='flex'"/>
        <div class="brand-icon" id="brandIconFallback" style="display:none;">
          <svg viewBox="0 0 40 40" fill="none"><path d="M10 20h4l3-7 4 14 3-10 3 6 3-3h4" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </div>
        JumeauNum
      </div>
      <div class="no-account">Pas encore membre ? <a href="signup.html">Créer un compte</a></div>
    </div>

    <!-- ══ FORMULAIRE CONNEXION ══ -->
    <div id="loginForm">
      <div class="form-title">Se connecter</div>
      <div class="form-sub">Entrez votre nom d'utilisateur ou adresse email et votre mot de passe.</div>

      <!-- Message d'erreur global -->
      <div class="msg-box msg-error" id="msgError">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
        <span id="msgErrorText"></span>
      </div>

      <!-- Champ identifiant -->
      <div class="form-group">
        <label class="form-label" for="identifiant">Nom d'utilisateur ou Email</label>
        <div class="input-wrap">
          <svg class="icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/>
          </svg>
          <input class="form-control" type="text" id="identifiant" placeholder="Ex : ybenali_42 ou j.doe@hopital.com"
                 autocomplete="username" oninput="clearError('identifiant')"/>
        </div>
        <div class="field-err" id="err_identifiant">
          <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
          <span id="err_identifiant_text"></span>
        </div>
      </div>

      <!-- Champ mot de passe -->
      <div class="form-group">
        <label class="form-label" for="mot_de_passe">Mot de passe</label>
        <div class="input-wrap">
          <svg class="icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/>
          </svg>
          <input class="form-control has-toggle" type="password" id="mot_de_passe"
                 placeholder="••••••••" autocomplete="current-password"
                 oninput="clearError('mot_de_passe')" onkeydown="if(event.key==='Enter') submitLogin()"/>
          <button type="button" class="toggle-pass" onclick="togglePassword()" title="Afficher / Masquer">
            <svg id="eyeIcon" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
            </svg>
          </button>
        </div>
        <div class="field-err" id="err_mot_de_passe">
          <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
          <span id="err_mot_de_passe_text"></span>
        </div>
      </div>

      <!-- Se souvenir + Mot de passe oublié -->
      <div class="form-row-between">
        <label class="checkbox-wrap">
          <input type="checkbox" id="rememberMe"/>
          <span>Se souvenir de moi</span>
        </label>
        <a href="#" class="forgot-link">Mot de passe oublié ?</a>
      </div>

      <!-- Bouton connexion -->
      <button class="btn-primary-full" id="btnLogin" onclick="submitLogin()">
        <span id="btnLoginContent" style="display:flex;align-items:center;gap:8px;">
          <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <path d="M15 3h4a2 2 0 012 2v14a2 2 0 01-2 2h-4M10 17l5-5-5-5M15 12H3"/>
          </svg>
          Se connecter
        </span>
        <div class="spinner" id="btnSpinner" style="display:none;"></div>
      </button>

      <div class="trust-row">
        <div class="trust-item"><div class="trust-dot"></div> Conforme RGPD</div>
        <div class="trust-item">
          <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
          Chiffrement 256 bits
        </div>
      </div>
    </div>

  </div><!-- /right-panel -->
</div><!-- /page-wrap -->

<footer>
  <span>© 2024 JumeauNum. Tous droits réservés.</span>
  <div>
    <a href="#">Politique de confidentialité</a>
    <a href="#">Directives cliniques</a>
    <a href="#">Centre d'assistance</a>
  </div>
</footer>

<script>
  /* ─────────────────────────────────────────────
     Toggle affichage mot de passe
  ───────────────────────────────────────────── */
  function togglePassword() {
    const input = document.getElementById('mot_de_passe');
    const icon  = document.getElementById('eyeIcon');
    if (input.type === 'password') {
      input.type = 'text';
      icon.innerHTML = `
        <path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94"/>
        <path d="M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19"/>
        <line x1="1" y1="1" x2="23" y2="23"/>`;
    } else {
      input.type = 'password';
      icon.innerHTML = `
        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
        <circle cx="12" cy="12" r="3"/>`;
    }
  }

  /* ─────────────────────────────────────────────
     Effacer erreurs inline
  ───────────────────────────────────────────── */
  function clearError(field) {
    document.getElementById(field).classList.remove('is-invalid');
    document.getElementById('err_' + field).classList.remove('show');
    document.getElementById('msgError').classList.remove('show');
  }

  function showFieldError(field, msg) {
    const input = document.getElementById(field);
    input.classList.add('is-invalid');
    const errDiv  = document.getElementById('err_' + field);
    document.getElementById('err_' + field + '_text').textContent = msg;
    errDiv.classList.add('show');
  }

  function showGlobalError(msg) {
    const box = document.getElementById('msgError');
    document.getElementById('msgErrorText').textContent = msg;
    box.classList.add('show');
  }

  /* ─────────────────────────────────────────────
     Soumission du formulaire → appel PHP
  ───────────────────────────────────────────── */
  async function submitLogin() {
    // Récupération des valeurs
    const identifiant  = document.getElementById('identifiant').value.trim();
    const mot_de_passe = document.getElementById('mot_de_passe').value;

    // Validation côté client
    let valid = true;
    if (!identifiant) {
      showFieldError('identifiant', 'Ce champ est requis.');
      valid = false;
    }
    if (!mot_de_passe) {
      showFieldError('mot_de_passe', 'Ce champ est requis.');
      valid = false;
    }
    if (!valid) return;

    // Afficher spinner
    setLoading(true);

    try {
      const formData = new FormData();
      formData.append('identifiant',  identifiant);
      formData.append('mot_de_passe', mot_de_passe);

      // Appel au contrôleur PHP
      // login.html est dans : gestion_user/vue/frontoffice/
      // On remonte 2 niveaux (frontoffice → vue) puis on va dans controleur/frontoffice
      const response = await fetch(
        '../../controleur/frontoffice/login_process.php',
        { method: 'POST', body: formData }
      );

      const result = await response.json();

      if (result.success) {
        // Redirection selon le rôle (gérée aussi côté PHP, mais on peut forcer ici)
        window.location.href = result.redirect;
      } else {
        showGlobalError(result.message || 'Identifiants incorrects.');
      }
    } catch (err) {
      showGlobalError('Erreur de connexion au serveur. Vérifiez que XAMPP est démarré.');
    } finally {
      setLoading(false);
    }
  }

  function setLoading(state) {
    const btn     = document.getElementById('btnLogin');
    const content = document.getElementById('btnLoginContent');
    const spinner = document.getElementById('btnSpinner');
    btn.disabled     = state;
    content.style.display = state ? 'none'  : 'flex';
    spinner.style.display  = state ? 'block' : 'none';
  }
</script>
</body>
</html>