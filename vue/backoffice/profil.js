// ═══════════════════════════════════════════════════════════════════════════
//  profil.js  —  Logique de la page Mon Profil
//  PHP : ../../controleur/backoffice/user_crud.php
// ═══════════════════════════════════════════════════════════════════════════

const CRUD_USER_P = '../../controleur/backoffice/user_crud.php';

// Simuler un id_user connecté (en production, récupéré depuis la session PHP)
// Pour les tests, vous pouvez changer cet ID selon votre BDD
const SESSION_USER_ID = 1;

document.addEventListener('DOMContentLoaded', () => {
  loadProfil();
});

// ── CHARGER LE PROFIL ─────────────────────────────────────────────────────────
async function loadProfil() {
  try {
    const r = await fetch(CRUD_USER_P + `?action=getProfil&id_user=${SESSION_USER_ID}`);
    const j = await r.json();
    if (j.success) {
      const u = j.data;
      document.getElementById('p_nom').value     = u.nom     || '';
      document.getElementById('p_prenom').value  = u.prenom  || '';
      document.getElementById('p_email').value   = u.email   || '';
      document.getElementById('p_cin').value     = u.cin     || '';
      document.getElementById('p_service').value = u.service || '';
      // En-tête du profil
      document.getElementById('profilNomComplet').textContent = (u.prenom + ' ' + u.nom).trim();
      document.getElementById('profilEmail').textContent      = u.email || '';
      document.getElementById('profilId').textContent         = '#' + u.id_user;
      // Badge rôle
      const roles = { 1: 'Patient', 2: 'Admin', 3: 'Médecin', 4: 'Super Admin' };
      document.getElementById('profilRole').textContent = roles[u.id_role] || 'Inconnu';
    }
  } catch (e) {
    showToast('Erreur de chargement du profil.', 'error');
  }
}

// ── VALIDER ET ENREGISTRER LE PROFIL ─────────────────────────────────────────
async function saveProfil() {
  clearErrors(['p_nom', 'p_prenom', 'p_email', 'p_cin']);
  hide('msg_profil_global'); hide('msg_profil_success');
  let ok = true;

  const nom    = document.getElementById('p_nom').value.trim();
  const prenom = document.getElementById('p_prenom').value.trim();
  const email  = document.getElementById('p_email').value.trim();
  const cin    = document.getElementById('p_cin').value.trim();

  if (!nom) {
    showErr('p_nom', 'Le nom est obligatoire.'); ok = false;
  } else if (!/^[a-zA-ZÀ-ÿ\s\-']+$/.test(nom)) {
    showErr('p_nom', 'Le nom ne doit pas contenir de symboles ou chiffres.'); ok = false;
  }

  if (!prenom) {
    showErr('p_prenom', 'Le prénom est obligatoire.'); ok = false;
  } else if (!/^[a-zA-ZÀ-ÿ\s\-']+$/.test(prenom)) {
    showErr('p_prenom', 'Le prénom ne doit pas contenir de symboles ou chiffres.'); ok = false;
  }

  if (!email) {
    showErr('p_email', "L'email est obligatoire."); ok = false;
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
    showErr('p_email', 'Format email invalide.'); ok = false;
  } else {
    try {
      const r = await fetch(CRUD_USER_P + `?action=checkEmail&email=${encodeURIComponent(email)}&exclude_id=${SESSION_USER_ID}`);
      // Note : checkEmail standard ne supporte pas exclude_id en GET, on utilise l'action updateProfil qui vérifie côté PHP
    } catch (e) {}
  }

  if (!cin) {
    showErr('p_cin', 'Le CIN est obligatoire.'); ok = false;
  } else if (!/^\d{8}$/.test(cin)) {
    showErr('p_cin', 'Le CIN doit contenir exactement 8 chiffres.'); ok = false;
  } else {
    try {
      const r = await fetch(CRUD_USER_P + `?action=checkCin&cin=${encodeURIComponent(cin)}&exclude_id=${SESSION_USER_ID}`);
      const j = await r.json();
      if (j.success && j.exists) {
        showErr('p_cin', 'Ce CIN est déjà utilisé par un autre compte.'); ok = false;
      }
    } catch (e) {}
  }

  if (!ok) return;

  const fd = new FormData();
  fd.append('action',   'updateProfil');
  fd.append('id_user',  SESSION_USER_ID);
  fd.append('nom',      nom);
  fd.append('prenom',   prenom);
  fd.append('email',    email);
  fd.append('cin',      cin);
  fd.append('service',  document.getElementById('p_service').value.trim());

  try {
    const res = await fetch(CRUD_USER_P, { method: 'POST', body: fd });
    const j   = await res.json();
    if (j.success) {
      showToast(j.message, 'success');
      const s = document.getElementById('msg_profil_success');
      s.textContent = j.message; s.style.display = 'block';
      loadProfil();
    } else {
      const g = document.getElementById('msg_profil_global');
      g.textContent = j.error || 'Erreur.'; g.style.display = 'block';
    }
  } catch (e) {
    showToast('Erreur réseau.', 'error');
  }
}

// ── CHANGER MOT DE PASSE ──────────────────────────────────────────────────────
async function changeMdp() {
  clearErrors(['p_ancien_mdp', 'p_nouveau_mdp', 'p_confirm_nouveau']);
  hide('msg_mdp_global'); hide('msg_mdp_success');
  let ok = true;

  const ancien  = document.getElementById('p_ancien_mdp').value;
  const nouveau = document.getElementById('p_nouveau_mdp').value;
  const confirm = document.getElementById('p_confirm_nouveau').value;

  if (!ancien) {
    showErr('p_ancien_mdp', "L'ancien mot de passe est obligatoire."); ok = false;
  }

  if (!nouveau) {
    showErr('p_nouveau_mdp', 'Le nouveau mot de passe est obligatoire.'); ok = false;
  } else if (nouveau.length < 6) {
    showErr('p_nouveau_mdp', 'Minimum 6 caractères.'); ok = false;
  } else if (!/[a-zA-Z]/.test(nouveau)) {
    showErr('p_nouveau_mdp', 'Doit contenir au moins une lettre.'); ok = false;
  } else if (!/[0-9]/.test(nouveau)) {
    showErr('p_nouveau_mdp', 'Doit contenir au moins un chiffre.'); ok = false;
  } else if (!/[^a-zA-Z0-9]/.test(nouveau)) {
    showErr('p_nouveau_mdp', 'Doit contenir au moins un caractère spécial.'); ok = false;
  }

  if (nouveau && confirm !== nouveau) {
    showErr('p_confirm_nouveau', 'Les mots de passe ne correspondent pas.'); ok = false;
  }

  if (!ok) return;

  const fd = new FormData();
  fd.append('action',      'changeMdp');
  fd.append('id_user',     SESSION_USER_ID);
  fd.append('ancien_mdp',  ancien);
  fd.append('nouveau_mdp', nouveau);

  try {
    const res = await fetch(CRUD_USER_P, { method: 'POST', body: fd });
    const j   = await res.json();
    if (j.success) {
      showToast(j.message, 'success');
      document.getElementById('p_ancien_mdp').value    = '';
      document.getElementById('p_nouveau_mdp').value   = '';
      document.getElementById('p_confirm_nouveau').value = '';
      const s = document.getElementById('msg_mdp_success');
      s.textContent = j.message; s.style.display = 'block';
    } else {
      const g = document.getElementById('msg_mdp_global');
      g.textContent = j.error || 'Erreur.'; g.style.display = 'block';
    }
  } catch (e) {
    showToast('Erreur réseau.', 'error');
  }
}

// ── RÉINITIALISER ─────────────────────────────────────────────────────────────
function resetProfil() { loadProfil(); clearErrors(['p_nom','p_prenom','p_email','p_cin']); }

// ── TOAST ─────────────────────────────────────────────────────────────────────
function showToast(msg, type = 'success') {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.className   = 'toast ' + type + ' show';
  setTimeout(() => { t.className = 'toast'; }, 3200);
}

// ── HELPERS ───────────────────────────────────────────────────────────────────
function showErr(fieldId, msg) {
  const el    = document.getElementById(fieldId);
  const msgEl = document.getElementById('err_' + fieldId);
  if (el)    el.classList.add('error');
  if (msgEl) { msgEl.textContent = msg; msgEl.classList.add('visible'); }
}
function clearErrors(ids) {
  ids.forEach(id => {
    const el    = document.getElementById(id);
    const msgEl = document.getElementById('err_' + id);
    if (el)    el.classList.remove('error');
    if (msgEl) { msgEl.textContent = ''; msgEl.classList.remove('visible'); }
  });
}
function hide(id) { const el = document.getElementById(id); if (el) el.style.display = 'none'; }
