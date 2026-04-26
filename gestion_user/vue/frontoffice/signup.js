// ═══════════════════════════════════════════════════════════════════════════
//  signup.js  —  Logique d'inscription en 3 étapes
//  Chemin PHP : ../../controleur/backoffice/user_crud.php
// ═══════════════════════════════════════════════════════════════════════════

const CRUD_USER = '../../controleur/backoffice/user_crud.php';

// ── ÉTAT ──────────────────────────────────────────────────────────────────────
let currentStep = 1; // étape courante : 1, 2 ou 3
const formData  = {}; // mémorise les données de chaque étape pour ne pas les perdre

// ── INIT ──────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  showStep(1);
  loadCountMedecins();
});

// ── COMPTEUR MÉDECINS (affiché dans la partie gauche) ─────────────────────────
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
  // Afficher/cacher les sections
  [1, 2, 3].forEach(i => {
    const s = document.getElementById('step' + i);
    if (s) s.style.display = i === n ? 'block' : 'none';
  });
  // Mettre à jour les indicateurs
  document.querySelectorAll('.step-bar-item').forEach((el, idx) => {
    el.classList.toggle('active',   idx + 1 === n);
    el.classList.toggle('done',     idx + 1 < n);
    el.classList.toggle('inactive', idx + 1 > n);
  });
  // Restaurer les champs avec les valeurs mémorisées
  restoreFields(n);
}

function restoreFields(n) {
  if (n === 1) {
    setVal('nom',      formData.nom);
    setVal('prenom',   formData.prenom);
    setVal('email',    formData.email);
    setVal('username', formData.username);
  }
  if (n === 2) {
    setVal('cin',     formData.cin);
    setVal('service', formData.service);
  }
  if (n === 3) {
    setVal('mot_de_passe',    formData.mot_de_passe);
    setVal('confirm_mdp',     formData.confirm_mdp);
  }
}

function setVal(id, val) {
  const el = document.getElementById(id);
  if (el && val !== undefined) el.value = val;
}

// ── BOUTON CONTINUER ÉTAPE 1 ──────────────────────────────────────────────────
async function nextStep1() {
  // Sauvegarder les valeurs
  formData.nom      = document.getElementById('nom').value.trim();
  formData.prenom   = document.getElementById('prenom').value.trim();
  formData.email    = document.getElementById('email').value.trim();
  formData.username = document.getElementById('username').value.trim();

  clearErrors(['nom', 'prenom', 'email', 'username']);
  let ok = true;

  // Nom — lettres et espaces uniquement, pas de symboles
  if (!formData.nom) {
    showErr('nom', 'Le nom est obligatoire.'); ok = false;
  } else if (!/^[a-zA-ZÀ-ÿ\s\-']+$/.test(formData.nom)) {
    showErr('nom', 'Le nom ne doit pas contenir de symboles ou chiffres.'); ok = false;
  }

  // Prénom — même règle
  if (!formData.prenom) {
    showErr('prenom', 'Le prénom est obligatoire.'); ok = false;
  } else if (!/^[a-zA-ZÀ-ÿ\s\-']+$/.test(formData.prenom)) {
    showErr('prenom', 'Le prénom ne doit pas contenir de symboles ou chiffres.'); ok = false;
  }

  // Email — format valide
  if (!formData.email) {
    showErr('email', "L'email est obligatoire."); ok = false;
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.email)) {
    showErr('email', "Format invalide. Exemple : j.doe@hopital.com"); ok = false;
  } else {
    // Vérifier si email existe déjà en BDD
    try {
      const r = await fetch(CRUD_USER + `?action=checkEmail&email=${encodeURIComponent(formData.email)}`);
      const j = await r.json();
      if (j.success && j.exists) {
        showErr('email', 'Cet email est déjà utilisé.'); ok = false;
      }
    } catch (e) {}
  }

  // Nom d'utilisateur — min 2 lettres, peut contenir chiffres et symboles
  if (!formData.username) {
    showErr('username', "Le nom d'utilisateur est obligatoire."); ok = false;
  } else if ((formData.username.match(/[a-zA-ZÀ-ÿ]/g) || []).length < 2) {
    // Doit contenir au moins 2 lettres (peu importe le reste)
    showErr('username', "Le nom d'utilisateur doit contenir au moins 2 lettres."); ok = false;
  } else {
    // Vérifier unicité en BDD
    try {
      const r = await fetch(CRUD_USER + `?action=checkUsername&username=${encodeURIComponent(formData.username)}`);
      const j = await r.json();
      if (j.success && j.exists) {
        showErr('username', "Ce nom d'utilisateur est déjà pris."); ok = false;
      }
    } catch (e) {}
  }

  if (ok) showStep(2);
}

// ── BOUTON CONTINUER ÉTAPE 2 ──────────────────────────────────────────────────
async function nextStep2() {
  formData.cin     = document.getElementById('cin').value.trim();
  formData.service = document.getElementById('service').value.trim();

  clearErrors(['cin', 'service']);
  let ok = true;

  // CIN — exactement 8 chiffres
  if (!formData.cin) {
    showErr('cin', 'Le CIN est obligatoire.'); ok = false;
  } else if (!/^\d{8}$/.test(formData.cin)) {
    showErr('cin', 'Le CIN doit contenir exactement 8 chiffres.'); ok = false;
  } else {
    // Vérifier unicité CIN en BDD
    try {
      const r = await fetch(CRUD_USER + `?action=checkCin&cin=${encodeURIComponent(formData.cin)}`);
      const j = await r.json();
      if (j.success && j.exists) {
        showErr('cin', 'Ce CIN est déjà enregistré pour un autre compte.'); ok = false;
      }
    } catch (e) {}
  }

  if (ok) showStep(3);
}

// ── BOUTON RETOUR ─────────────────────────────────────────────────────────────
function prevStep(n) {
  // Sauvegarder les champs avant de reculer
  if (currentStep === 2) {
    formData.cin     = document.getElementById('cin').value.trim();
    formData.service = document.getElementById('service').value.trim();
  }
  if (currentStep === 3) {
    formData.mot_de_passe = document.getElementById('mot_de_passe').value;
    formData.confirm_mdp  = document.getElementById('confirm_mdp').value;
  }
  showStep(n);
}

// ── SOUMETTRE (ÉTAPE 3) ───────────────────────────────────────────────────────
async function submitSignup() {
  formData.mot_de_passe = document.getElementById('mot_de_passe').value;
  formData.confirm_mdp  = document.getElementById('confirm_mdp').value;

  clearErrors(['mot_de_passe', 'confirm_mdp']);
  hide('msg_signup_error');
  let ok = true;

  const mdp = formData.mot_de_passe;

  // Mot de passe : min 6 chars, 1 lettre, 1 chiffre, 1 symbole
  if (!mdp) {
    showErr('mot_de_passe', 'Le mot de passe est obligatoire.'); ok = false;
  } else if (mdp.length < 6) {
    showErr('mot_de_passe', 'Minimum 6 caractères.'); ok = false;
  } else if (!/[a-zA-Z]/.test(mdp)) {
    showErr('mot_de_passe', 'Le mot de passe doit contenir au moins une lettre.'); ok = false;
  } else if (!/[0-9]/.test(mdp)) {
    showErr('mot_de_passe', 'Le mot de passe doit contenir au moins un chiffre.'); ok = false;
  } else if (!/[^a-zA-Z0-9]/.test(mdp)) {
    showErr('mot_de_passe', 'Le mot de passe doit contenir au moins un caractère spécial (@, !, #...).'); ok = false;
  }

  if (mdp && formData.confirm_mdp !== mdp) {
    showErr('confirm_mdp', 'Les mots de passe ne correspondent pas.'); ok = false;
  }

  if (!ok) return;

  // Afficher le bouton en chargement
  const btn = document.getElementById('btnSubmitSignup');
  btn.disabled = true;
  btn.textContent = 'Création en cours...';

  try {
    const fd = new FormData();
    fd.append('action',        'signup');
    fd.append('nom',           formData.nom);
    fd.append('prenom',        formData.prenom);
    fd.append('email',         formData.email);
    fd.append('username',      formData.username);
    fd.append('cin',           formData.cin);
    fd.append('service',       formData.service || '');
    fd.append('mot_de_passe',  mdp);

    const res = await fetch(CRUD_USER, { method: 'POST', body: fd });
    const r   = await res.json();

    if (r.success) {
      // Afficher l'écran de succès
      document.getElementById('signupForm').style.display   = 'none';
      document.getElementById('signupSuccess').style.display = 'block';
      document.getElementById('successName').textContent    = formData.prenom + ' ' + formData.nom;
    } else {
      const errEl = document.getElementById('msg_signup_error');
      errEl.textContent    = r.error || 'Une erreur est survenue.';
      errEl.style.display  = 'block';
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

// ── FORCE MOT DE PASSE (indicateur visuel) ────────────────────────────────────
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
