// ═══════════════════════════════════════════════════════════════════════════
//  dossier.js  —  Logique JavaScript : Dossiers Médicaux + Consultations
//
//  Chargé par index.html via : <script src="dossier.js" defer></script>
//
//  Communique avec :
//    ../../controleur/backoffice/dossier_crud.php
//    ../../controleur/backoffice/consultation_crud.php
// ═══════════════════════════════════════════════════════════════════════════

const CRUD_DOSSIER      = '../../controleur/backoffice/dossier_crud.php';
const CRUD_CONSULTATION = '../../controleur/backoffice/consultation_crud.php';

// ── ÉTAT GLOBAL ──────────────────────────────────────────────────────────────
let allDossiers      = [];   // tous les dossiers chargés depuis la BDD
let allConsultations = [];   // toutes les consultations chargées depuis la BDD
let editingDossierId      = null;
let editingConsultationId = null;
let currentPageD = 1;        // page courante tableau dossiers
let currentPageC = 1;        // page courante tableau consultations
const PAGE_SIZE  = 8;

// IDs existants en mémoire pour le dé aléatoire (évite des requêtes répétées)
let existingDossierIds      = [];
let existingConsultationIds = [];

// ── TRI PAR DATE ──────────────────────────────────────────────────────────────
let sortDossierOrder      = null; // null | 'asc' | 'desc'
let sortConsultationOrder = null; // null | 'asc' | 'desc'


// ── INITIALISATION ────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  // Pré-remplir les dates du jour
  const today = new Date().toISOString().split('T')[0];
  document.getElementById('d_date').value  = today;
  document.getElementById('c_date').value  = today;

  testConnection();
});


// ── TEST CONNEXION ────────────────────────────────────────────────────────────
async function testConnection() {
  const banner = document.getElementById('connBanner');
  try {
    const res = await fetch(CRUD_DOSSIER + '?action=ping');
    const r   = await res.json();
    if (r.success) {
      banner.className = 'conn-banner ok';
      banner.innerHTML = `<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
        <span>Connecté à la base <strong>jumeaunum</strong> avec succès ✓</span>`;
      loadDossiers();
      loadConsultations();
      loadPatients();
      loadMedecins();
      loadDossiersDropdown();
      prefetchIds();
    } else {
      showConnError(r.error);
    }
  } catch (e) {
    showConnError('Impossible de joindre le serveur PHP. Vérifiez que :<br>'
      + '<strong>1.</strong> XAMPP est démarré (Apache + MySQL)<br>'
      + '<strong>2.</strong> Vous ouvrez via <code>localhost</code> et non via le système de fichiers<br>'
      + '<strong>3.</strong> Le dossier est bien dans <code>htdocs/gestion_des_dossiers/</code>');
  }
}

function showConnError(msg) {
  const banner = document.getElementById('connBanner');
  banner.className = 'conn-banner error';
  banner.innerHTML = `<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M15 9l-6 6M9 9l6 6"/></svg>
    <span>${msg}</span>`;
  document.getElementById('dossierTableBody').innerHTML =
    `<tr><td colspan="6" class="no-data" style="color:var(--red)">En attente de connexion.</td></tr>`;
  document.getElementById('consultationTableBody').innerHTML =
    `<tr><td colspan="6" class="no-data" style="color:var(--red)">En attente de connexion.</td></tr>`;
}


// ── TOAST ─────────────────────────────────────────────────────────────────────
function showToast(msg, type = 'success') {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.className   = 'toast ' + type + ' show';
  setTimeout(() => { t.className = 'toast'; }, 3200);
}


// ── POST HELPER ───────────────────────────────────────────────────────────────
async function postData(url, data) {
  const fd = new FormData();
  for (const [k, v] of Object.entries(data)) fd.append(k, v ?? '');
  const res = await fetch(url, { method: 'POST', body: fd });
  return res.json();
}


// ════════════════════════════════════════════════════════════════════════════
//  SECTION DOSSIERS
// ════════════════════════════════════════════════════════════════════════════

// ── CHARGER DOSSIERS ──────────────────────────────────────────────────────────
async function loadDossiers() {
  try {
    const r = await postData(CRUD_DOSSIER, { action: 'getAll' });
    if (r.success) {
      allDossiers = r.data;
      updateStatsDossiers();
      renderDossierTable();
    } else {
      document.getElementById('dossierTableBody').innerHTML =
        `<tr><td colspan="6" class="no-data" style="color:var(--red)">Erreur : ${r.error}</td></tr>`;
    }
  } catch (e) {
    document.getElementById('dossierTableBody').innerHTML =
      `<tr><td colspan="6" class="no-data" style="color:var(--red)">Erreur réseau.</td></tr>`;
  }
}

// ── STATS DOSSIERS ────────────────────────────────────────────────────────────
function updateStatsDossiers() {
  document.getElementById('stat-total-dossiers').textContent = allDossiers.length;
  document.getElementById('stat-badge-dossiers').textContent = allDossiers.length;
  document.getElementById('stat-total-consultations').textContent = allConsultations.length;
  document.getElementById('stat-badge-consultations').textContent = allConsultations.length;
}

// ── TRI DOSSIERS PAR DATE ─────────────────────────────────────────────────────
function sortDossierAsc() {
  sortDossierOrder = 'asc';
  currentPageD = 1;
  updateSortBtns('D', 'asc');
  renderDossierTable();
}
function sortDossierDesc() {
  sortDossierOrder = 'desc';
  currentPageD = 1;
  updateSortBtns('D', 'desc');
  renderDossierTable();
}

// ── TRI CONSULTATIONS PAR DATE ────────────────────────────────────────────────
function sortConsultationAsc() {
  sortConsultationOrder = 'asc';
  currentPageC = 1;
  updateSortBtns('C', 'asc');
  renderConsultationTable();
}
function sortConsultationDesc() {
  sortConsultationOrder = 'desc';
  currentPageC = 1;
  updateSortBtns('C', 'desc');
  renderConsultationTable();
}

// ── MET À JOUR L'APPARENCE DES BOUTONS DE TRI ────────────────────────────────
function updateSortBtns(which, active) {
  const prefix = which === 'D' ? 'dossier' : 'consultation';
  const btnAsc  = document.getElementById(`${prefix}SortAsc`);
  const btnDesc = document.getElementById(`${prefix}SortDesc`);
  if (!btnAsc || !btnDesc) return;
  btnAsc.classList.toggle('sort-active', active === 'asc');
  btnDesc.classList.toggle('sort-active', active === 'desc');
}

// ── RENDER TABLE DOSSIERS ─────────────────────────────────────────────────────
function renderDossierTable() {
  const search = document.getElementById('dossierSearch').value.toLowerCase();

  const filtered = allDossiers.filter(d =>
    !search ||
    String(d.id_dossier).includes(search) ||
    (d.description             || '').toLowerCase().includes(search) ||
    (d.patient_nom_complet     || '').toLowerCase().includes(search) ||
    (d.medecin_nom_complet     || '').toLowerCase().includes(search)
  );

  const total = filtered.length;
  const pages = Math.max(1, Math.ceil(total / PAGE_SIZE));
  if (currentPageD > pages) currentPageD = pages;

  // Tri par date_creation
  if (sortDossierOrder) {
    filtered.sort((a, b) => {
      const da = new Date(a.date_creation || 0);
      const db = new Date(b.date_creation || 0);
      return sortDossierOrder === 'asc' ? da - db : db - da;
    });
  }

  const slice = filtered.slice((currentPageD - 1) * PAGE_SIZE, currentPageD * PAGE_SIZE);

  document.getElementById('dossierPaginCount').textContent = `Affichage de ${slice.length} sur ${total} dossier(s)`;
  document.getElementById('dossierPageInfo').textContent   = `${currentPageD} / ${pages}`;

  const tbody = document.getElementById('dossierTableBody');
  if (!slice.length) {
    tbody.innerHTML = '<tr><td colspan="6" class="no-data">Aucun dossier trouvé.</td></tr>';
    return;
  }

  tbody.innerHTML = slice.map(d => {
    const date    = d.date_creation ? String(d.date_creation).split(' ')[0] : '—';
    const patient = d.patient_nom_complet || d.id_patient || '—';
    const medecin = d.medecin_nom_complet || d.id_medecin || '—';
    const desc    = (d.description || '').substring(0, 40) + ((d.description || '').length > 40 ? '…' : '');
    return `<tr>
      <td class="id-cell">${esc(d.id_dossier)}</td>
      <td>${esc(desc)}</td>
      <td>${esc(date)}</td>
      <td>${esc(patient)}</td>
      <td>${esc(medecin)}</td>
      <td>
        <div class="actions-cell">
          <button class="icon-btn" onclick="editDossier(${d.id_dossier})" title="Modifier">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
          </button>
          <button class="icon-btn del" onclick="deleteDossier(${d.id_dossier})" title="Supprimer">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><path d="M10 11v6M14 11v6M9 6V4h6v2"/></svg>
          </button>
        </div>
      </td>
    </tr>`;
  }).join('');
}

function prevPageD() { if (currentPageD > 1) { currentPageD--; renderDossierTable(); } }
function nextPageD() {
  const s = document.getElementById('dossierSearch').value.toLowerCase();
  const total = allDossiers.filter(d => !s || String(d.id_dossier).includes(s) || (d.description||'').toLowerCase().includes(s)).length;
  if (currentPageD < Math.ceil(total / PAGE_SIZE)) { currentPageD++; renderDossierTable(); }
}


// ── LOAD PATIENTS & MÉDECINS ──────────────────────────────────────────────────
async function loadPatients() {
  try {
    const r   = await postData(CRUD_DOSSIER, { action: 'getPatients' });
    const sel = document.getElementById('d_patient');
    if (r.success && r.data.length > 0) {
      sel.innerHTML = '<option value="">Sélectionner un patient...</option>';
      r.data.forEach(p => {
        const opt = document.createElement('option');
        opt.value = p.id_user;
        const label = [p.nom, p.prenom].filter(Boolean).join(' ');
        opt.textContent = label ? `${label} (${p.id_user})` : p.id_user;
        sel.appendChild(opt);
      });
    } else {
      sel.innerHTML = '<option value="">Aucun patient trouvé</option>';
    }
  } catch (e) {
    document.getElementById('d_patient').innerHTML = '<option value="">Erreur chargement</option>';
  }
}

async function loadMedecins() {
  try {
    const r   = await postData(CRUD_DOSSIER, { action: 'getMedecins' });
    const sel = document.getElementById('d_medecin');
    if (r.success && r.data.length > 0) {
      sel.innerHTML = '<option value="">Sélectionner un médecin...</option>';
      r.data.forEach(m => {
        const opt = document.createElement('option');
        opt.value = m.id_user;
        const label = [m.nom, m.prenom].filter(Boolean).join(' ');
        opt.textContent = label ? `${label} (${m.id_user})` : m.id_user;
        sel.appendChild(opt);
      });
    } else {
      sel.innerHTML = '<option value="">Aucun médecin trouvé</option>';
    }
  } catch (e) {
    document.getElementById('d_medecin').innerHTML = '<option value="">Erreur chargement</option>';
  }
}


// ── PRÉ-CHARGER LES IDs EXISTANTS (pour le dé) ───────────────────────────────
async function prefetchIds() {
  try {
    const rd = await postData(CRUD_DOSSIER, { action: 'getExistingIds' });
    if (rd.success) existingDossierIds = rd.ids.map(Number);
  } catch (e) {}
  try {
    const rc = await postData(CRUD_CONSULTATION, { action: 'getExistingIds' });
    if (rc.success) existingConsultationIds = rc.ids.map(Number);
  } catch (e) {}
}


// ── DÉ ALÉATOIRE — génère un ID int unique max 8 chiffres ────────────────────
function genRandomId(existingIds) {
  let id;
  let tries = 0;
  do {
    // Génère un entier entre 1 et 99999999 (8 chiffres max)
    id = Math.floor(Math.random() * 99999999) + 1;
    tries++;
  } while (existingIds.includes(id) && tries < 500);
  return id;
}

function rollDiceD() {
  const id = genRandomId(existingDossierIds);
  document.getElementById('d_id').value = id;
  // Déclencher la validation visuelle
  clearErrors(['d_id']);
}

function rollDiceC() {
  const id = genRandomId(existingConsultationIds);
  document.getElementById('c_id').value = id;
  clearErrors(['c_id']);
}


// ── VALIDATION DOSSIER ────────────────────────────────────────────────────────
async function validateDossier() {
  let ok = true;
  clearErrors(['d_id', 'd_date', 'd_patient', 'd_medecin']);
  hide('msg_dossier_global'); hide('msg_dossier_success');

  const inputId = document.getElementById('d_id');
  const val     = inputId.value.trim();

  // 1. Obligatoire
  if (val === '') {
    showFieldError('d_id', "L'ID est obligatoire !"); ok = false;
  } else if (!/^\d+$/.test(val)) {
    // 2. Seulement des chiffres
    showFieldError('d_id', "L'ID doit être un entier (chiffres uniquement) !"); ok = false;
  } else if (val.length > 8) {
    // 3. Max 8 chiffres
    showFieldError('d_id', "Maximum 8 chiffres !"); ok = false;
  } else if (parseInt(val) <= 0) {
    showFieldError('d_id', "L'ID doit être supérieur à 0 !"); ok = false;
  } else if (!editingDossierId) {
    // 4. Vérifier en BDD si l'ID existe déjà (seulement en mode ajout)
    try {
      const r = await fetch(CRUD_DOSSIER + `?action=checkId&id=${encodeURIComponent(val)}`);
      const j = await r.json();
      if (j.success && j.exists) {
        showFieldError('d_id', `L'ID ${val} existe déjà dans la base !`); ok = false;
      }
    } catch (e) { /* si le check échoue, on laisse le PHP gérer */ }
  }

  if (!document.getElementById('d_date').value) {
    showFieldError('d_date', "La date de création est obligatoire !"); ok = false;
  } else {
    const chosen = new Date(document.getElementById('d_date').value);
    const today  = new Date(); today.setHours(0, 0, 0, 0);
    if (chosen > today) {
      showFieldError('d_date', "La date ne peut pas être dans le futur !"); ok = false;
    }
  }

  if (!document.getElementById('d_patient').value) {
    showFieldError('d_patient', "Veuillez sélectionner un patient !"); ok = false;
  }
  if (!document.getElementById('d_medecin').value) {
    showFieldError('d_medecin', "Veuillez sélectionner un médecin !"); ok = false;
  }

  return ok;
}

// ── SUBMIT DOSSIER ────────────────────────────────────────────────────────────
async function submitDossier() {
  if (!(await validateDossier())) return;

  const data = {
    action:        editingDossierId ? 'update' : 'add',
    id_dossier:    document.getElementById('d_id').value.trim(),
    description:   document.getElementById('d_desc').value.trim(),
    date_creation: document.getElementById('d_date').value,
    id_patient:    document.getElementById('d_patient').value,
    id_medecin:    document.getElementById('d_medecin').value
  };

  try {
    const r = await postData(CRUD_DOSSIER, data);
    if (r.success) {
      const s = document.getElementById('msg_dossier_success');
      s.textContent = r.message; s.style.display = 'block';
      showToast(r.message, 'success');
      resetDossierForm();
      loadDossiers();
      loadDossiersDropdown();
      prefetchIds();
    } else {
      const g = document.getElementById('msg_dossier_global');
      g.textContent = r.error || 'Erreur.'; g.style.display = 'block';
    }
  } catch (e) {
    document.getElementById('msg_dossier_global').textContent = 'Erreur réseau.';
    document.getElementById('msg_dossier_global').style.display = 'block';
  }
}

// ── EDIT DOSSIER ──────────────────────────────────────────────────────────────
function editDossier(id) {
  const d = allDossiers.find(x => x.id_dossier == id);
  if (!d) return;
  editingDossierId = id;

  document.getElementById('dossierFormTitle').textContent   = 'Modifier Dossier';
  document.getElementById('dossierBtnLabel').textContent    = 'Enregistrer';
  document.getElementById('d_id').value                     = d.id_dossier;
  document.getElementById('d_id').disabled                  = true;
  document.getElementById('d_desc').value                   = d.description || '';
  document.getElementById('d_date').value                   = d.date_creation ? String(d.date_creation).split(' ')[0] : '';
  document.getElementById('d_patient').value                = d.id_patient || '';
  document.getElementById('d_medecin').value                = d.id_medecin || '';

  document.querySelector('.right-panel-dossier').scrollIntoView({ behavior: 'smooth' });
}

// ── DELETE DOSSIER ────────────────────────────────────────────────────────────
async function deleteDossier(id) {
  if (!confirm(`⚠️ Attention — Supprimer le dossier #${id} ?\n\nToutes les consultations associées à ce dossier seront également supprimées définitivement.\n\nCette action est irréversible. Êtes-vous sûr(e) de vouloir continuer ?`)) return;
  try {
    const r = await postData(CRUD_DOSSIER, { action: 'delete', id_dossier: id });
    if (r.success) {
      showToast(r.message, 'success');
      loadDossiers();
      loadConsultations();
      loadDossiersDropdown();
      prefetchIds();
    } else showToast(r.error || 'Erreur suppression', 'error');
  } catch (e) { showToast('Erreur réseau', 'error'); }
}

// ── RESET DOSSIER FORM ────────────────────────────────────────────────────────
function resetDossierForm() {
  editingDossierId = null;
  document.getElementById('dossierFormTitle').textContent = 'Nouveau Dossier';
  document.getElementById('dossierBtnLabel').textContent  = 'Ajouter Dossier';
  document.getElementById('d_id').value      = '';
  document.getElementById('d_id').disabled   = false;
  document.getElementById('d_desc').value    = '';
  document.getElementById('d_date').value    = new Date().toISOString().split('T')[0];
  document.getElementById('d_patient').value = '';
  document.getElementById('d_medecin').value = '';
  clearErrors(['d_id', 'd_date', 'd_patient', 'd_medecin']);
  hide('msg_dossier_global'); hide('msg_dossier_success');
}


// ════════════════════════════════════════════════════════════════════════════
//  SECTION CONSULTATIONS
// ════════════════════════════════════════════════════════════════════════════

// ── CHARGER CONSULTATIONS ─────────────────────────────────────────────────────
async function loadConsultations() {
  try {
    const r = await postData(CRUD_CONSULTATION, { action: 'getAll' });
    if (r.success) {
      allConsultations = r.data;
      updateStatsDossiers(); // met à jour le compteur de consultations aussi
      renderConsultationTable();
    } else {
      document.getElementById('consultationTableBody').innerHTML =
        `<tr><td colspan="6" class="no-data" style="color:var(--red)">Erreur : ${r.error}</td></tr>`;
    }
  } catch (e) {
    document.getElementById('consultationTableBody').innerHTML =
      `<tr><td colspan="6" class="no-data" style="color:var(--red)">Erreur réseau.</td></tr>`;
  }
}

// ── RENDER TABLE CONSULTATIONS ────────────────────────────────────────────────
function renderConsultationTable() {
  const search = document.getElementById('consultationSearch').value.toLowerCase();

  const filtered = allConsultations.filter(c =>
    !search ||
    String(c.id_consultation).includes(search) ||
    (c.motif      || '').toLowerCase().includes(search) ||
    (c.diagnostic || '').toLowerCase().includes(search) ||
    String(c.id_dossier).includes(search)
  );

  const total = filtered.length;
  const pages = Math.max(1, Math.ceil(total / PAGE_SIZE));
  if (currentPageC > pages) currentPageC = pages;

  // Tri par date_consultation
  if (sortConsultationOrder) {
    filtered.sort((a, b) => {
      const da = new Date(a.date_consultation || 0);
      const db = new Date(b.date_consultation || 0);
      return sortConsultationOrder === 'asc' ? da - db : db - da;
    });
  }

  const slice = filtered.slice((currentPageC - 1) * PAGE_SIZE, currentPageC * PAGE_SIZE);

  document.getElementById('consultationPaginCount').textContent = `Affichage de ${slice.length} sur ${total} consultation(s)`;
  document.getElementById('consultationPageInfo').textContent   = `${currentPageC} / ${pages}`;

  const tbody = document.getElementById('consultationTableBody');
  if (!slice.length) {
    tbody.innerHTML = '<tr><td colspan="6" class="no-data">Aucune consultation trouvée.</td></tr>';
    return;
  }

  tbody.innerHTML = slice.map(c => {
    const date  = c.date_consultation ? String(c.date_consultation).split(' ')[0] : '—';
    const motif = (c.motif || '').substring(0, 35) + ((c.motif || '').length > 35 ? '…' : '');
    const diag  = (c.diagnostic || '').substring(0, 35) + ((c.diagnostic || '').length > 35 ? '…' : '');
    return `<tr>
      <td class="id-cell">${esc(c.id_consultation)}</td>
      <td>${esc(date)}</td>
      <td>${esc(motif)}</td>
      <td>${esc(diag)}</td>
      <td class="id-cell">${esc(c.id_dossier)}</td>
      <td>
        <div class="actions-cell">
          <button class="icon-btn" onclick="editConsultation(${c.id_consultation})" title="Modifier">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
          </button>
          <button class="icon-btn del" onclick="deleteConsultation(${c.id_consultation})" title="Supprimer">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><path d="M10 11v6M14 11v6M9 6V4h6v2"/></svg>
          </button>
        </div>
      </td>
    </tr>`;
  }).join('');
}

function prevPageC() { if (currentPageC > 1) { currentPageC--; renderConsultationTable(); } }
function nextPageC() {
  const s = document.getElementById('consultationSearch').value.toLowerCase();
  const total = allConsultations.filter(c => !s || (c.motif||'').toLowerCase().includes(s) || String(c.id_dossier).includes(s)).length;
  if (currentPageC < Math.ceil(total / PAGE_SIZE)) { currentPageC++; renderConsultationTable(); }
}

// ── LOAD DOSSIERS DROPDOWN (pour le formulaire consultation) ──────────────────
async function loadDossiersDropdown() {
  try {
    const r   = await postData(CRUD_CONSULTATION, { action: 'getDossiers' });
    const sel = document.getElementById('c_dossier');
    if (r.success && r.data.length > 0) {
      sel.innerHTML = '<option value="">Sélectionner un dossier...</option>';
      r.data.forEach(d => {
        const opt = document.createElement('option');
        opt.value = d.id_dossier;
        const desc = d.description ? ` — ${d.description.substring(0, 30)}` : '';
        opt.textContent = `#${d.id_dossier}${desc}`;
        sel.appendChild(opt);
      });
    } else {
      sel.innerHTML = '<option value="">Aucun dossier disponible</option>';
    }
  } catch (e) {
    document.getElementById('c_dossier').innerHTML = '<option value="">Erreur chargement</option>';
  }
}

// ── VALIDATION CONSULTATION ───────────────────────────────────────────────────
async function validateConsultation() {
  let ok = true;
  clearErrors(['c_id', 'c_date', 'c_motif', 'c_dossier']);
  hide('msg_consultation_global'); hide('msg_consultation_success');

  const inputId = document.getElementById('c_id');
  const val     = inputId.value.trim();

  if (val === '') {
    showFieldError('c_id', "L'ID est obligatoire !"); ok = false;
  } else if (!/^\d+$/.test(val)) {
    showFieldError('c_id', "L'ID doit être un entier (chiffres uniquement) !"); ok = false;
  } else if (val.length > 8) {
    showFieldError('c_id', "Maximum 8 chiffres !"); ok = false;
  } else if (parseInt(val) <= 0) {
    showFieldError('c_id', "L'ID doit être supérieur à 0 !"); ok = false;
  } else if (!editingConsultationId) {
    try {
      const r = await fetch(CRUD_CONSULTATION + `?action=checkId&id=${encodeURIComponent(val)}`);
      const j = await r.json();
      if (j.success && j.exists) {
        showFieldError('c_id', `L'ID ${val} existe déjà !`); ok = false;
      }
    } catch (e) {}
  }

  if (!document.getElementById('c_date').value) {
    showFieldError('c_date', "La date de consultation est obligatoire !"); ok = false;
  } else {
    const chosen = new Date(document.getElementById('c_date').value);
    const today  = new Date(); today.setHours(0, 0, 0, 0);
    if (chosen > today) {
      showFieldError('c_date', "La date ne peut pas être dans le futur !"); ok = false;
    }
  }

  if (!document.getElementById('c_motif').value.trim()) {
    showFieldError('c_motif', "Le motif est obligatoire !"); ok = false;
  }

  if (!document.getElementById('c_dossier').value) {
    showFieldError('c_dossier', "Veuillez sélectionner un dossier !"); ok = false;
  }

  return ok;
}

// ── SUBMIT CONSULTATION ───────────────────────────────────────────────────────
async function submitConsultation() {
  if (!(await validateConsultation())) return;

  const data = {
    action:            editingConsultationId ? 'update' : 'add',
    id_consultation:   document.getElementById('c_id').value.trim(),
    date_consultation: document.getElementById('c_date').value,
    motif:             document.getElementById('c_motif').value.trim(),
    diagnostic:        document.getElementById('c_diagnostic').value.trim(),
    notes:             document.getElementById('c_notes').value.trim(),
    id_dossier:        document.getElementById('c_dossier').value
  };

  try {
    const r = await postData(CRUD_CONSULTATION, data);
    if (r.success) {
      const s = document.getElementById('msg_consultation_success');
      s.textContent = r.message; s.style.display = 'block';
      showToast(r.message, 'success');
      resetConsultationForm();
      loadConsultations();
      prefetchIds();
    } else {
      const g = document.getElementById('msg_consultation_global');
      g.textContent = r.error || 'Erreur.'; g.style.display = 'block';
    }
  } catch (e) {
    document.getElementById('msg_consultation_global').textContent = 'Erreur réseau.';
    document.getElementById('msg_consultation_global').style.display = 'block';
  }
}

// ── EDIT CONSULTATION ─────────────────────────────────────────────────────────
function editConsultation(id) {
  const c = allConsultations.find(x => x.id_consultation == id);
  if (!c) return;
  editingConsultationId = id;

  document.getElementById('consultationFormTitle').textContent = 'Modifier Consultation';
  document.getElementById('consultationBtnLabel').textContent  = 'Enregistrer';
  document.getElementById('c_id').value         = c.id_consultation;
  document.getElementById('c_id').disabled      = true;
  document.getElementById('c_date').value        = c.date_consultation ? String(c.date_consultation).split(' ')[0] : '';
  document.getElementById('c_motif').value       = c.motif      || '';
  document.getElementById('c_diagnostic').value  = c.diagnostic || '';
  document.getElementById('c_notes').value       = c.notes      || '';
  document.getElementById('c_dossier').value     = c.id_dossier || '';

  document.querySelector('.right-panel-consultation').scrollIntoView({ behavior: 'smooth' });
}

// ── DELETE CONSULTATION ───────────────────────────────────────────────────────
async function deleteConsultation(id) {
  if (!confirm(`Supprimer la consultation #${id} ?`)) return;
  try {
    const r = await postData(CRUD_CONSULTATION, { action: 'delete', id_consultation: id });
    if (r.success) { showToast(r.message, 'success'); loadConsultations(); prefetchIds(); }
    else showToast(r.error || 'Erreur suppression', 'error');
  } catch (e) { showToast('Erreur réseau', 'error'); }
}

// ── RESET CONSULTATION FORM ───────────────────────────────────────────────────
function resetConsultationForm() {
  editingConsultationId = null;
  document.getElementById('consultationFormTitle').textContent = 'Nouvelle Consultation';
  document.getElementById('consultationBtnLabel').textContent  = 'Ajouter Consultation';
  document.getElementById('c_id').value         = '';
  document.getElementById('c_id').disabled      = false;
  document.getElementById('c_date').value        = new Date().toISOString().split('T')[0];
  document.getElementById('c_motif').value       = '';
  document.getElementById('c_diagnostic').value  = '';
  document.getElementById('c_notes').value       = '';
  document.getElementById('c_dossier').value     = '';
  clearErrors(['c_id', 'c_date', 'c_motif', 'c_dossier']);
  hide('msg_consultation_global'); hide('msg_consultation_success');
}


// ════════════════════════════════════════════════════════════════════════════
//  EXPORTS CSV
// ════════════════════════════════════════════════════════════════════════════

function exportDossierCSV() {
  const headers = ['ID Dossier', 'Description', 'Date Création', 'Patient', 'Médecin'];
  const rows = allDossiers.map(d => [
    d.id_dossier,
    d.description,
    (d.date_creation || '').split(' ')[0],
    d.patient_nom_complet || d.id_patient,
    d.medecin_nom_complet || d.id_medecin
  ]);
  downloadCSV(headers, rows, 'dossiers_medicaux.csv');
}

function exportConsultationCSV() {
  const headers = ['ID Consultation', 'Date', 'Motif', 'Diagnostic', 'Notes', 'ID Dossier'];
  const rows = allConsultations.map(c => [
    c.id_consultation,
    (c.date_consultation || '').split(' ')[0],
    c.motif,
    c.diagnostic,
    c.notes,
    c.id_dossier
  ]);
  downloadCSV(headers, rows, 'consultations.csv');
}

function downloadCSV(headers, rows, filename) {
  const csv  = [headers, ...rows].map(r => r.map(c => `"${String(c||'').replace(/"/g,'""')}"`).join(',')).join('\n');
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
  const a    = document.createElement('a');
  a.href = URL.createObjectURL(blob); a.download = filename; a.click();
}


// ════════════════════════════════════════════════════════════════════════════
//  HELPERS
// ════════════════════════════════════════════════════════════════════════════

function esc(s) {
  return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function showFieldError(fieldId, msg) {
  const el    = document.getElementById(fieldId);
  const msgEl = document.getElementById('msg_' + fieldId);
  if (el)    el.classList.add('error');
  if (msgEl) { msgEl.textContent = msg; msgEl.classList.add('visible'); }
}

function clearErrors(ids) {
  ids.forEach(id => {
    const el    = document.getElementById(id);
    const msgEl = document.getElementById('msg_' + id);
    if (el)    el.classList.remove('error');
    if (msgEl) { msgEl.textContent = ''; msgEl.classList.remove('visible'); }
  });
}

function hide(id) { const el = document.getElementById(id); if (el) el.style.display = 'none'; }