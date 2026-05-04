// ═══════════════════════════════════════════════════════════════════════════
//  signup.js  —  Logique d'inscription en 3 étapes
//  Après inscription OU connexion → redirection vers supadmin.html
// ═══════════════════════════════════════════════════════════════════════════

const CRUD_USER = '../../controleur/backoffice/user_crud.php';

let currentStep = 1;
const formData  = {};

document.addEventListener('DOMContentLoaded', () => {
  // Vérifier si déjà connecté → rediriger immédiatement
  checkAlreadyLoggedIn();
  showStep(1);
  loadCountMedecins();

  // Mettre à jour le lien "Se connecter" pour pointer vers login.html
  const loginLinks = document.querySelectorAll('a[href="login.html"], .already a');
  loginLinks.forEach(l => { l.href = '../../controleur/backoffice/login.html'; });
});

// ── VÉRIFIER SESSION EXISTANTE ────────────────────────────────────────────────
async function checkAlreadyLoggedIn() {
  try {
    const r = await fetch(CRUD_USER + '?action=checkSession');
    const j = await r.json();
    if (j.success && j.loggedIn) {
      window.location.replace('./medecins.html');
    }
  } catch(e) {}
}

// ── COMPTEUR MÉDECINS ─────────────────────────────────────────────────────────
async function loadCountMedecins() {
  try {
    const r = await fetch(CRUD_USER + '?action=countMedecins');
    const j = await r.json();
    const el = document.getElementById('countMedecins');
    if (el && j.success) el.textContent = j.total.toLocaleString('fr-FR') + '+ médecins';
  } catch (e) {}
}

// ── NAVIGATION ENTRE ÉTAPES ───────────────────────────────────────────────────
function showStep(n) {
  currentStep = n;
  [1, 2, 3].forEach(i => {
    const s = document.getElementById('step' + i);
    if (s) s.style.display = i === n ? 'block' : 'none';
  });
  document.querySelectorAll('.step-bar-item').forEach((el, idx) => {
    el.classList.toggle('active',   idx + 1 === n);
    el.classList.toggle('done',     idx + 1 < n);
    el.classList.toggle('inactive', idx + 1 > n);
  });
  restoreFields(n);
}

function restoreFields(n) {
  if (n === 1) { setVal('nom', formData.nom); setVal('prenom', formData.prenom); setVal('email', formData.email); }
  if (n === 2) { setVal('cin', formData.cin); setVal('service', formData.service); }
  if (n === 3) { setVal('mot_de_passe', formData.mot_de_passe); setVal('confirm_mdp', formData.confirm_mdp); }
}

function setVal(id, val) {
  const el = document.getElementById(id);
  if (el && val !== undefined) el.value = val;
}

// ── ÉTAPE 1 ───────────────────────────────────────────────────────────────────
async function nextStep1() {
  formData.nom    = document.getElementById('nom').value.trim();
  formData.prenom = document.getElementById('prenom').value.trim();
  formData.email  = document.getElementById('email').value.trim();

  clearErrors(['nom', 'prenom', 'email']);
  let ok = true;

  if (!formData.nom) { showErr('nom', 'Le nom est obligatoire.'); ok = false; }
  else if (!/^[a-zA-ZÀ-ÿ\s\-']+$/.test(formData.nom)) { showErr('nom', 'Lettres uniquement.'); ok = false; }

  if (!formData.prenom) { showErr('prenom', 'Le prénom est obligatoire.'); ok = false; }
  else if (!/^[a-zA-ZÀ-ÿ\s\-']+$/.test(formData.prenom)) { showErr('prenom', 'Lettres uniquement.'); ok = false; }

  if (!formData.email) { showErr('email', "L'email est obligatoire."); ok = false; }
  else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.email)) { showErr('email', 'Format invalide.'); ok = false; }
  else {
    try {
      const r = await fetch(CRUD_USER + `?action=checkEmail&email=${encodeURIComponent(formData.email)}`);
      const j = await r.json();
      if (j.success && j.exists) { showErr('email', 'Cet email est déjà utilisé.'); ok = false; }
    } catch (e) {}
  }

  if (ok) showStep(2);
}

// ── ÉTAPE 2 ───────────────────────────────────────────────────────────────────
async function nextStep2() {
  formData.cin     = document.getElementById('cin').value.trim();
  formData.service = document.getElementById('service').value.trim();

  clearErrors(['cin', 'service']);
  let ok = true;

  if (!formData.cin) { showErr('cin', 'Le CIN est obligatoire.'); ok = false; }
  else if (!/^\d{8}$/.test(formData.cin)) { showErr('cin', 'Exactement 8 chiffres.'); ok = false; }
  else {
    try {
      const r = await fetch(CRUD_USER + `?action=checkCin&cin=${encodeURIComponent(formData.cin)}`);
      const j = await r.json();
      if (j.success && j.exists) { showErr('cin', 'CIN déjà enregistré.'); ok = false; }
    } catch (e) {}
  }

  if (ok) showStep(3);
}

// ── RETOUR ────────────────────────────────────────────────────────────────────
function prevStep(n) {
  if (currentStep === 2) { formData.cin = document.getElementById('cin').value.trim(); formData.service = document.getElementById('service').value.trim(); }
  if (currentStep === 3) { formData.mot_de_passe = document.getElementById('mot_de_passe').value; formData.confirm_mdp = document.getElementById('confirm_mdp').value; }
  showStep(n);
}

// ── SOUMETTRE ÉTAPE 3 — avec redirection auto vers supadmin ──────────────────
async function submitSignup() {
  formData.mot_de_passe = document.getElementById('mot_de_passe').value;
  formData.confirm_mdp  = document.getElementById('confirm_mdp').value;

  clearErrors(['mot_de_passe', 'confirm_mdp']);
  hide('msg_signup_error');
  let ok = true;

  const mdp = formData.mot_de_passe;

  if (!mdp) { showErr('mot_de_passe', 'Le mot de passe est obligatoire.'); ok = false; }
  else if (mdp.length < 6) { showErr('mot_de_passe', 'Minimum 6 caractères.'); ok = false; }
  else if (!/[a-zA-Z]/.test(mdp)) { showErr('mot_de_passe', 'Au moins une lettre.'); ok = false; }
  else if (!/[0-9]/.test(mdp)) { showErr('mot_de_passe', 'Au moins un chiffre.'); ok = false; }
  else if (!/[^a-zA-Z0-9]/.test(mdp)) { showErr('mot_de_passe', 'Au moins un caractère spécial (@, !, #...).'); ok = false; }

  if (mdp && formData.confirm_mdp !== mdp) { showErr('confirm_mdp', 'Les mots de passe ne correspondent pas.'); ok = false; }
  if (!ok) return;

  const btn = document.getElementById('btnSubmitSignup');
  btn.disabled = true;
  btn.textContent = 'Création en cours...';

  try {
    const fd = new FormData();
    fd.append('action',       'signup');
    fd.append('nom',          formData.nom);
    fd.append('prenom',       formData.prenom);
    fd.append('email',        formData.email);
    fd.append('cin',          formData.cin);
    fd.append('service',      formData.service || '');
    fd.append('mot_de_passe', mdp);

    const res = await fetch(CRUD_USER, { method: 'POST', body: fd });
    const r   = await res.json();

    if (r.success) {
      localStorage.setItem('jn_user_id',   r.id_user);
      localStorage.setItem('jn_user_role', 1); // nouveau compte = Patient par défaut
      localStorage.setItem('jn_user_name', formData.prenom + ' ' + formData.nom);
      sessionStorage.setItem('jn_logged', '1');

      // Afficher brièvement le succès puis rediriger
      document.getElementById('signupForm').style.display    = 'none';
      document.getElementById('signupSuccess').style.display = 'block';
      document.getElementById('successName').textContent     = formData.prenom + ' ' + formData.nom;

      // Redirection automatique dans 2 secondes → page médecins (front-office)
      setTimeout(() => {
        window.location.replace('./medecins.html');
      }, 2000);

    } else {
      const errEl = document.getElementById('msg_signup_error');
      errEl.textContent   = r.error || 'Une erreur est survenue.';
      errEl.style.display = 'block';
      btn.disabled    = false;
      btn.textContent = 'Créer mon compte';
    }
  } catch (e) {
    const errEl = document.getElementById('msg_signup_error');
    errEl.textContent   = 'Erreur réseau. Vérifiez que XAMPP est démarré.';
    errEl.style.display = 'block';
    btn.disabled    = false;
    btn.textContent = 'Créer mon compte';
  }
}

// ── FORCE MOT DE PASSE ────────────────────────────────────────────────────────
function checkPasswordStrength(val) {
  const bar   = document.getElementById('strengthBar');
  const label = document.getElementById('strengthLabel');
  if (!bar || !label) return;

  let score = 0;
  if (val.length >= 6)          score++;
  if (/[a-zA-Z]/.test(val))     score++;
  if (/[0-9]/.test(val))        score++;
  if (/[^a-zA-Z0-9]/.test(val)) score++;
  if (val.length >= 10)         score++;

  const levels = [
    { w: '20%',  color: '#dc2626', text: 'Très faible' },
    { w: '40%',  color: '#d97706', text: 'Faible'      },
    { w: '60%',  color: '#f59e0b', text: 'Moyen'       },
    { w: '80%',  color: '#16a34a', text: 'Fort'        },
    { w: '100%', color: '#15803d', text: 'Très fort'   },
  ];
  const lvl = levels[Math.min(score - 1, 4)] || levels[0];
  bar.style.width      = val ? lvl.w     : '0';
  bar.style.background = val ? lvl.color : 'transparent';
  label.textContent    = val ? lvl.text  : '';
}

// ── HELPERS ───────────────────────────────────────────────────────────────────
function showErr(fieldId, msg) {
  const el    = document.getElementById(fieldId);
  const msgEl = document.getElementById('err_' + fieldId);
  if (el)    el.classList.add('is-invalid');
  if (msgEl) { msgEl.textContent = msg; msgEl.style.display = 'flex'; }
}

function clearErrors(ids) {
  ids.forEach(id => {
    const el    = document.getElementById(id);
    const msgEl = document.getElementById('err_' + id);
    if (el)    el.classList.remove('is-invalid');
    if (msgEl) { msgEl.textContent = ''; msgEl.style.display = 'none'; }
  });
}

function hide(id) { const el = document.getElementById(id); if (el) el.style.display = 'none'; }