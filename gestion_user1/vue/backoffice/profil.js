// ═══════════════════════════════════════════════════════════════════════════
//  profil.js  —  Mon Profil : edit profil + change mdp + enroll visage
// ═══════════════════════════════════════════════════════════════════════════

const CRUD_USER_P = '../../controleur/backoffice/user_crud.php';

// Récupérer l'id_user depuis la session ou localStorage
function getSessionUserId() {
  const params = new URLSearchParams(window.location.search);
  const fromUrl = params.get('id_user');
  if (fromUrl) return parseInt(fromUrl);
  return parseInt(localStorage.getItem('jn_user_id') || '1');
}

document.addEventListener('DOMContentLoaded', () => {
  loadProfil();
});

// ── CHARGER LE PROFIL ─────────────────────────────────────────────────────────
async function loadProfil() {
  const userId = getSessionUserId();
  try {
    const r = await fetch(CRUD_USER_P + `?action=getProfil&id_user=${userId}`);
    const j = await r.json();
    if (j.success) {
      const u = j.data;
      document.getElementById('p_nom').value     = u.nom     || '';
      document.getElementById('p_prenom').value  = u.prenom  || '';
      document.getElementById('p_email').value   = u.email   || '';
      document.getElementById('p_cin').value     = u.cin     || '';
      document.getElementById('p_service').value = u.service || '';

      // En-tête du profil
      const fullName = ((u.prenom||'') + ' ' + (u.nom||'')).trim();
      const nc = document.getElementById('profilNomComplet');
      const ec = document.getElementById('profilEmail');
      const ic = document.getElementById('profilId');
      const rc = document.getElementById('profilRole');
      if (nc) nc.textContent = fullName;
      if (ec) ec.textContent = u.email || '';
      if (ic) ic.textContent = '#' + u.id_user;

      // Badge rôle
      const roles = { 1:'Patient', 2:'Admin', 3:'Médecin', 4:'Super Admin' };
      if (rc) rc.textContent = roles[u.id_role] || 'Inconnu';

      // Initiale avatar
      const avatarEl = document.querySelector('.profil-avatar');
      if (avatarEl) {
        const initial = (u.prenom || u.nom || '?')[0].toUpperCase();
        avatarEl.childNodes[0] && avatarEl.childNodes[0].nodeType === 3
          ? (avatarEl.childNodes[0].textContent = initial)
          : avatarEl.prepend(initial);
      }
    } else {
      showToast(j.error || 'Erreur de chargement.', 'error');
    }
  } catch (e) {
    showToast('Erreur réseau. Vérifiez que XAMPP est démarré.', 'error');
  }
}

// ── ENREGISTRER LE PROFIL ─────────────────────────────────────────────────────
async function saveProfil() {
  clearErrors(['p_nom','p_prenom','p_email','p_cin']);
  hideEl('msg_profil_global'); hideEl('msg_profil_success');
  let ok = true;

  const userId  = getSessionUserId();
  const nom     = document.getElementById('p_nom').value.trim();
  const prenom  = document.getElementById('p_prenom').value.trim();
  const email   = document.getElementById('p_email').value.trim();
  const cin     = document.getElementById('p_cin').value.trim();
  const service = document.getElementById('p_service').value.trim();

  if (!nom) { showErr('p_nom','Le nom est obligatoire.'); ok=false; }
  else if (!/^[a-zA-ZÀ-ÿ\s\-']+$/.test(nom)) { showErr('p_nom','Lettres uniquement.'); ok=false; }

  if (!prenom) { showErr('p_prenom','Le prénom est obligatoire.'); ok=false; }
  else if (!/^[a-zA-ZÀ-ÿ\s\-']+$/.test(prenom)) { showErr('p_prenom','Lettres uniquement.'); ok=false; }

  if (!email) { showErr('p_email',"L'email est obligatoire."); ok=false; }
  else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { showErr('p_email','Format invalide.'); ok=false; }

  if (!cin) { showErr('p_cin','Le CIN est obligatoire.'); ok=false; }
  else if (!/^\d{8}$/.test(cin)) { showErr('p_cin','8 chiffres exacts.'); ok=false; }
  else {
    try {
      const r = await fetch(CRUD_USER_P + `?action=checkCin&cin=${encodeURIComponent(cin)}&exclude_id=${userId}`);
      const j = await r.json();
      if (j.success && j.exists) { showErr('p_cin','CIN déjà utilisé.'); ok=false; }
    } catch(e) {}
  }

  if (!ok) return;

  const fd = new FormData();
  fd.append('action',  'updateProfil');
  fd.append('id_user', userId);
  fd.append('nom',     nom);
  fd.append('prenom',  prenom);
  fd.append('email',   email);
  fd.append('cin',     cin);
  fd.append('service', service);

  try {
    const res = await fetch(CRUD_USER_P, {method:'POST',body:fd});
    const j   = await res.json();
    if (j.success) {
      showToast('✓ ' + j.message, 'success');
      const s = document.getElementById('msg_profil_success');
      if (s) { s.textContent = j.message; s.style.display = 'block'; }
      // Mettre à jour localStorage
      localStorage.setItem('jn_user_name', prenom + ' ' + nom);
      loadProfil();
    } else {
      const g = document.getElementById('msg_profil_global');
      if (g) { g.textContent = j.error || 'Erreur.'; g.style.display = 'block'; }
      showToast(j.error || 'Erreur lors de la mise à jour.', 'error');
    }
  } catch(e) {
    showToast('Erreur réseau.', 'error');
  }
}

// ── CHANGER MOT DE PASSE ──────────────────────────────────────────────────────
async function changeMdp() {
  clearErrors(['p_ancien_mdp','p_nouveau_mdp','p_confirm_nouveau']);
  hideEl('msg_mdp_global'); hideEl('msg_mdp_success');
  let ok = true;

  const userId  = getSessionUserId();
  const ancien  = document.getElementById('p_ancien_mdp').value;
  const nouveau = document.getElementById('p_nouveau_mdp').value;
  const confirm = document.getElementById('p_confirm_nouveau').value;

  if (!ancien) { showErr('p_ancien_mdp',"L'ancien mot de passe est obligatoire."); ok=false; }

  if (!nouveau) { showErr('p_nouveau_mdp','Le nouveau mot de passe est obligatoire.'); ok=false; }
  else if (nouveau.length < 6) { showErr('p_nouveau_mdp','Minimum 6 caractères.'); ok=false; }
  else if (!/[a-zA-Z]/.test(nouveau)) { showErr('p_nouveau_mdp','Au moins une lettre.'); ok=false; }
  else if (!/[0-9]/.test(nouveau)) { showErr('p_nouveau_mdp','Au moins un chiffre.'); ok=false; }
  else if (!/[^a-zA-Z0-9]/.test(nouveau)) { showErr('p_nouveau_mdp','Au moins un caractère spécial.'); ok=false; }

  if (nouveau && confirm !== nouveau) { showErr('p_confirm_nouveau','Les mots de passe ne correspondent pas.'); ok=false; }

  if (!ok) return;

  const fd = new FormData();
  fd.append('action',      'changeMdp');
  fd.append('id_user',     userId);
  fd.append('ancien_mdp',  ancien);
  fd.append('nouveau_mdp', nouveau);

  try {
    const res = await fetch(CRUD_USER_P, {method:'POST',body:fd});
    const j   = await res.json();
    if (j.success) {
      showToast('✓ ' + j.message, 'success');
      document.getElementById('p_ancien_mdp').value    = '';
      document.getElementById('p_nouveau_mdp').value   = '';
      document.getElementById('p_confirm_nouveau').value = '';
      const s = document.getElementById('msg_mdp_success');
      if (s) { s.textContent = j.message; s.style.display = 'block'; }
    } else {
      const g = document.getElementById('msg_mdp_global');
      if (g) { g.textContent = j.error || 'Erreur.'; g.style.display = 'block'; }
      showToast(j.error || 'Erreur.', 'error');
    }
  } catch(e) {
    showToast('Erreur réseau.', 'error');
  }
}

// ── ENREGISTRER SON VISAGE (reconnaissance faciale) ───────────────────────────
let faceEnrollStream = null;
let faceEnrollDescriptor = null;

async function openFaceEnroll() {
  // Créer un modal d'enregistrement facial si inexistant
  if (!document.getElementById('faceEnrollModal')) {
    const html = `
    <div id="faceEnrollModal" style="position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;display:flex;align-items:center;justify-content:center">
      <div style="background:#fff;border-radius:16px;padding:24px;width:360px;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,.3)">
        <div style="font-size:1.1rem;font-weight:700;margin-bottom:6px">📷 Enregistrer votre visage</div>
        <div style="font-size:.82rem;color:#6b7280;margin-bottom:16px">Positionnez votre visage dans le cadre et restez immobile.</div>
        <div style="position:relative;width:100%;aspect-ratio:4/3;background:#000;border-radius:12px;overflow:hidden;margin-bottom:12px">
          <video id="enrollVideo" autoplay muted playsinline style="width:100%;height:100%;object-fit:cover"></video>
          <div id="enrollOverlay" style="position:absolute;inset:0;border:3px solid #2563eb;border-radius:12px;animation:scanPulse 1.5s ease-in-out infinite"></div>
        </div>
        <div id="enrollStatus" style="font-size:.82rem;color:#6b7280;margin-bottom:14px">Démarrage de la caméra...</div>
        <div style="display:flex;gap:8px">
          <button onclick="closeFaceEnroll()" style="flex:1;height:40px;border:1.5px solid #dde2ef;border-radius:8px;background:#fff;font-family:inherit;font-size:.85rem;font-weight:600;cursor:pointer">Annuler</button>
          <button id="enrollSaveBtn" onclick="saveFaceEnroll()" style="flex:1;height:40px;border:none;border-radius:8px;background:#2563eb;color:#fff;font-family:inherit;font-size:.85rem;font-weight:600;cursor:pointer;display:none">✓ Enregistrer</button>
        </div>
      </div>
    </div>
    <style>@keyframes scanPulse{0%,100%{opacity:.4}50%{opacity:1}}</style>`;
    document.body.insertAdjacentHTML('beforeend', html);
  }

  document.getElementById('faceEnrollModal').style.display = 'flex';

  try {
    // Charger les modèles face-api
    if (!window.faceapi) {
      document.getElementById('enrollStatus').textContent = 'Chargement des modèles IA...';
      // Face-api doit être chargé dans le HTML de profil
    }
    const MODEL_URL = '../../controleur/backoffice/model';
    if (window.faceapi && !window._faceModelsLoaded) {
      await faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL);
      await faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL);
      await faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL);
      window._faceModelsLoaded = true;
    }

    faceEnrollStream = await navigator.mediaDevices.getUserMedia({video:{facingMode:'user'}});
    document.getElementById('enrollVideo').srcObject = faceEnrollStream;
    document.getElementById('enrollStatus').textContent = 'Positionnez votre visage...';

    // Détecter en boucle
    const video = document.getElementById('enrollVideo');
    const scanInterval = setInterval(async () => {
      if (!document.getElementById('faceEnrollModal')) { clearInterval(scanInterval); return; }
      try {
        if (!window.faceapi) return;
        const det = await faceapi.detectSingleFace(video, new faceapi.TinyFaceDetectorOptions())
          .withFaceLandmarks().withFaceDescriptor();
        if (det) {
          clearInterval(scanInterval);
          faceEnrollDescriptor = Array.from(det.descriptor);
          document.getElementById('enrollStatus').textContent = '✓ Visage détecté ! Cliquez sur Enregistrer.';
          document.getElementById('enrollStatus').style.color = '#16a34a';
          document.getElementById('enrollSaveBtn').style.display = 'block';
          document.getElementById('enrollOverlay').style.borderColor = '#16a34a';
          document.getElementById('enrollOverlay').style.animation = 'none';
        }
      } catch(e) {}
    }, 600);

  } catch(e) {
    document.getElementById('enrollStatus').textContent = 'Caméra non disponible : ' + e.message;
    document.getElementById('enrollStatus').style.color = '#dc2626';
  }
}

async function saveFaceEnroll() {
  if (!faceEnrollDescriptor) { showToast('Aucun visage détecté.','error'); return; }
  const fd = new FormData();
  fd.append('action',     'saveFaceDescriptor');
  fd.append('id_user',    getSessionUserId());
  fd.append('descriptor', JSON.stringify(faceEnrollDescriptor));
  try {
    const r = await fetch(CRUD_USER_P, {method:'POST',body:fd});
    const j = await r.json();
    closeFaceEnroll();
    if (j.success) showToast('✓ Visage enregistré avec succès !','success');
    else showToast(j.error || 'Erreur.','error');
  } catch(e) { showToast('Erreur réseau.','error'); }
}

function closeFaceEnroll() {
  if (faceEnrollStream) { faceEnrollStream.getTracks().forEach(t=>t.stop()); faceEnrollStream=null; }
  faceEnrollDescriptor = null;
  const m = document.getElementById('faceEnrollModal');
  if (m) m.remove();
}

// ── RÉINITIALISER ─────────────────────────────────────────────────────────────
function resetProfil() {
  loadProfil();
  clearErrors(['p_nom','p_prenom','p_email','p_cin']);
  hideEl('msg_profil_global'); hideEl('msg_profil_success');
}

// ── TOAST ─────────────────────────────────────────────────────────────────────
function showToast(msg, type='success') {
  const t = document.getElementById('toast');
  if (!t) return;
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

function hideEl(id) { const el=document.getElementById(id); if (el) el.style.display='none'; }
function toggleEye(inputId, btn) {
  const input = document.getElementById(inputId);
  const isHidden = input.type === 'password';
  input.type = isHidden ? 'text' : 'password';
  btn.style.color = isHidden ? '#2563eb' : '#9ca3af';
}