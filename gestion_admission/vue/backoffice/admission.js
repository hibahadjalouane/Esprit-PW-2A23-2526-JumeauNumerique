// admission.js : Logique JavaScript de la page Gestion des Admissions
//
// Charge par index.html via : <script src="admission.js" defer></script>
//
// Communique avec :
//   ../../controleur/backoffice/admission_crud.php
//   ../../controleur/backoffice/salle_crud.php


const CRUD_ADMISSION = '../../controleur/backoffice/admission_crud.php';
const CRUD_SALLE     = '../../controleur/backoffice/salle_crud.php';


// ETAT GLOBAL
let allAdmissions = [];
let editingId     = null;
let currentPage   = 1;
const PAGE_SIZE   = 8;

// IDs existants en memoire pour le de aleatoire
let existingAdmissionIds = [];


// INITIALISATION
document.addEventListener('DOMContentLoaded', () => {
  document.getElementById('a_date').value = new Date().toISOString().split('T')[0];
  testConnection();
});


// TEST DE CONNEXION
// Envoie un ping au PHP pour verifier Apache + MySQL.
// Si OK, charge toutes les donnees. Sinon, affiche une banniere rouge.
async function testConnection() {
  const banner = document.getElementById('connBanner');
  try {
    const res = await fetch(CRUD_ADMISSION + '?action=ping');
    const r   = await res.json();

    if (r.success) {
      banner.className = 'conn-banner ok';
      banner.innerHTML = `<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
        <span>Connecte a la base <strong>jumeaunum</strong> avec succes</span>`;
      loadAdmissions();
      loadTickets();
      loadSallesDropdown();
      loadSalles();
      loadMedecins();
      prefetchAdmissionIds();
    } else {
      showConnError(r.error);
    }
  } catch (e) {
    showConnError('Impossible de joindre le serveur PHP. Verifiez que :<br>'
      + '<strong>1.</strong> XAMPP est demarre (Apache + MySQL)<br>'
      + '<strong>2.</strong> Vous ouvrez via <code>localhost</code> et non depuis le systeme de fichiers<br>'
      + '<strong>3.</strong> Le dossier est dans <code>htdocs/projetweb/gestion_des_admission/</code>');
  }
}

function showConnError(msg) {
  const banner = document.getElementById('connBanner');
  banner.className = 'conn-banner error';
  banner.innerHTML = `<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M15 9l-6 6M9 9l6 6"/></svg>
    <span>${msg}</span>`;
  document.getElementById('tableBody').innerHTML =
    `<tr><td colspan="6" class="no-data" style="color:var(--red)">En attente de connexion a la base de donnees.</td></tr>`;
}


// TOAST : notification en bas a droite pendant 3,2 secondes
function showToast(msg, type = 'success') {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.className   = 'toast ' + type + ' show';
  setTimeout(() => { t.className = 'toast'; }, 3200);
}


// HELPER POST : envoie des donnees en POST vers un PHP et retourne le JSON parse
async function postData(url, data) {
  const fd = new FormData();
  for (const [k, v] of Object.entries(data)) fd.append(k, v ?? '');
  const res = await fetch(url, { method: 'POST', body: fd });
  return res.json();
}


// PRE-CHARGER LES IDS EXISTANTS pour le de aleatoire
async function prefetchAdmissionIds() {
  try {
    const r = await postData(CRUD_ADMISSION, { action: 'getExistingIds' });
    if (r.success) existingAdmissionIds = r.ids.map(Number);
  } catch (e) {}
}


// GENERATEUR D'ID ALEATOIRE : entier unique entre 1 et 99999999 (max 8 chiffres)
function genRandomId(existingIds) {
  let id;
  let tries = 0;
  do {
    id = Math.floor(Math.random() * 99999999) + 1;
    tries++;
  } while (existingIds.includes(id) && tries < 500);
  return id;
}

// Bouton de aleatoire pour l'ID admission
function rollDiceAdmission() {
  const id = genRandomId(existingAdmissionIds);
  document.getElementById('a_id').value = id;
  clearErrors(['a_id']);
}


// CHARGER LES ADMISSIONS depuis la BDD
async function loadAdmissions() {
  try {
    const r = await postData(CRUD_ADMISSION, { action: 'getAll' });
    if (r.success) {
      allAdmissions = r.data;
      updateStats();
      renderTable();
    } else {
      document.getElementById('tableBody').innerHTML =
        `<tr><td colspan="6" class="no-data" style="color:var(--red)">Erreur : ${r.error}</td></tr>`;
    }
  } catch (e) {
    document.getElementById('tableBody').innerHTML =
      `<tr><td colspan="6" class="no-data" style="color:var(--red)">Erreur reseau.</td></tr>`;
  }
}


// STATS : compte par mode_entree et met a jour les cartes
function updateStats() {
  const total      = allAdmissions.length;
  const urgences   = allAdmissions.filter(a => a.mode_entree === 'urgence').length;
  const normales   = allAdmissions.filter(a => a.mode_entree === 'normal').length;
  const transferts = allAdmissions.filter(a => a.mode_entree === 'transfert').length;

  document.getElementById('stat-total').textContent     = total;
  document.getElementById('stat-urgence').textContent   = urgences;
  document.getElementById('stat-normal').textContent    = normales;
  document.getElementById('stat-transfert').textContent = transferts;

  document.getElementById('stat-badge-total').textContent     = total;
  document.getElementById('stat-badge-urgence').textContent   = urgences;
  document.getElementById('stat-badge-normal').textContent    = normales;
  document.getElementById('stat-badge-transfert').textContent = transferts;
}


// RENDER TABLE : filtre, pagine et genere les lignes du tableau
function renderTable() {
  const search = document.getElementById('searchInput').value.toLowerCase();
  const fMode  = document.getElementById('filterMode').value;

  const filtered = allAdmissions.filter(a => {
    const ms = !search ||
      String(a.id_admission).includes(search) ||
      String(a.id_ticket   || '').includes(search) ||
      (a.mode_entree || '').toLowerCase().includes(search);
    return ms && (!fMode || a.mode_entree === fMode);
  });

  const total = filtered.length;
  const pages = Math.max(1, Math.ceil(total / PAGE_SIZE));
  if (currentPage > pages) currentPage = pages;

  const slice = filtered.slice((currentPage - 1) * PAGE_SIZE, currentPage * PAGE_SIZE);

  document.getElementById('paginCount').textContent = `Affichage de ${slice.length} sur ${total} admission(s)`;
  document.getElementById('pageInfo').textContent   = `${currentPage} / ${pages}`;

  const tbody = document.getElementById('tableBody');
  if (!slice.length) {
    tbody.innerHTML = '<tr><td colspan="6" class="no-data">Aucune admission trouvee.</td></tr>';
    return;
  }

  const modeMap = {
    'urgence':   ['badge-urgence',   'Urgence'],
    'normal':    ['badge-normal',    'Normal'],
    'transfert': ['badge-transfert', 'Transfert'],
    'autre':     ['badge-autre',     'Autre']
  };

  tbody.innerHTML = slice.map(a => {
    const date   = a.date_arrive_relle ? String(a.date_arrive_relle).split(' ')[0] : 'Inconnue';
    const ticket = a.id_ticket   ? `#${a.id_ticket}`   : 'Aucun';
    const salle  = a.salle_numero ? `Salle ${a.salle_numero} (#${a.id_salle})` : 'Aucune';
    const [mCls, mLabel] = modeMap[a.mode_entree] || ['badge-autre', a.mode_entree || 'Inconnu'];

    return `<tr>
      <td class="id-cell">${esc(a.id_admission)}</td>
      <td>${esc(date)}</td>
      <td><span class="badge ${mCls}">${esc(mLabel)}</span></td>
      <td class="id-cell">${esc(ticket)}</td>
      <td>${esc(salle)}</td>
      <td>
        <div class="actions-cell">
          <button class="icon-btn" onclick="editAdmission(${a.id_admission})" title="Modifier">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
          </button>
          <button class="icon-btn del" onclick="deleteAdmission(${a.id_admission})" title="Supprimer">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><path d="M10 11v6M14 11v6M9 6V4h6v2"/></svg>
          </button>
        </div>
      </td>
    </tr>`;
  }).join('');
}

function prevPage() { if (currentPage > 1) { currentPage--; renderTable(); } }
function nextPage() {
  const s = document.getElementById('searchInput').value.toLowerCase();
  const f = document.getElementById('filterMode').value;
  const total = allAdmissions.filter(a =>
    (!s || String(a.id_admission).includes(s) || (a.mode_entree||'').toLowerCase().includes(s))
    && (!f || a.mode_entree === f)
  ).length;
  if (currentPage < Math.ceil(total / PAGE_SIZE)) { currentPage++; renderTable(); }
}


// CHARGER LES TICKETS disponibles pour le dropdown
async function loadTickets() {
  try {
    const r   = await postData(CRUD_ADMISSION, { action: 'getTickets' });
    const sel = document.getElementById('a_ticket');
    if (r.success && r.data.length > 0) {
      sel.innerHTML = '<option value="">Selectionner un ticket...</option>';
      r.data.forEach(t => {
        const opt       = document.createElement('option');
        opt.value       = t.id_ticket;
        opt.textContent = t.id_ticket;
        sel.appendChild(opt);
      });
    } else {
      sel.innerHTML = '<option value="">Aucun ticket disponible</option>';
    }
  } catch (e) {
    document.getElementById('a_ticket').innerHTML = '<option value="">Erreur chargement tickets</option>';
  }
}

// Affiche ou cache la boite info bleue quand un ticket est selectionne
function showTicketInfo() {
  const sel  = document.getElementById('a_ticket');
  const info = document.getElementById('ticketInfo');
  info.className = sel.value ? 'ticket-info visible' : 'ticket-info';
}


// CHARGER LES SALLES pour le dropdown du formulaire admission
async function loadSallesDropdown() {
  try {
    const r   = await postData(CRUD_ADMISSION, { action: 'getSalles' });
    const sel = document.getElementById('a_salle');
    if (r.success && r.data.length > 0) {
      sel.innerHTML = '<option value="">Aucune (optionnel)...</option>';
      r.data.forEach(s => {
        const opt       = document.createElement('option');
        opt.value       = s.id_salle;
        opt.textContent = `Salle ${s.numero} (#${s.id_salle}) - ${s.statut}`;
        sel.appendChild(opt);
      });
    } else {
      sel.innerHTML = '<option value="">Aucune salle disponible</option>';
    }
  } catch (e) {
    document.getElementById('a_salle').innerHTML = '<option value="">Erreur chargement salles</option>';
  }
}


// VALIDATION ADMISSION
// Retourne true si tout est OK. Affiche les erreurs sous les champs sinon.
async function validateAdmission() {
  let ok = true;

  clearErrors(['a_id', 'a_date', 'a_mode', 'a_ticket']);
  hide('msg_form_global');
  hide('msg_form_success');

  const inputId = document.getElementById('a_id');
  const val     = String(inputId.value).trim();

  // Validation ID : chiffres uniquement, max 8 digits, superieur a 0, unique en BDD
  if (val === '') {
    showFieldError('a_id', "L'ID admission est obligatoire !"); ok = false;
  } else if (!/^\d+$/.test(val)) {
    showFieldError('a_id', "L'ID doit contenir uniquement des chiffres (entier) !"); ok = false;
  } else if (val.length > 8) {
    showFieldError('a_id', "Maximum 8 chiffres !"); ok = false;
  } else if (parseInt(val) <= 0) {
    showFieldError('a_id', "L'ID doit etre superieur a 0 !"); ok = false;
  } else if (!editingId) {
    // Verifier en BDD seulement en mode ajout
    try {
      const r = await fetch(CRUD_ADMISSION + `?action=checkId&id=${encodeURIComponent(val)}`);
      const j = await r.json();
      if (j.success && j.exists) {
        showFieldError('a_id', `L'ID ${val} existe deja dans la base !`); ok = false;
      }
    } catch (e) {}
  }

  // Validation date : obligatoire, ne peut pas etre dans le futur.
  // La date d'aujourd'hui est acceptee. Seul demain ou apres est refuse.
  if (!document.getElementById('a_date').value) {
    showFieldError('a_date', "La date d'arrivee est obligatoire !"); ok = false;
  } else {
    const chosen = new Date(document.getElementById('a_date').value);
    const today  = new Date();
    today.setHours(23, 59, 59, 999);
    if (chosen > today) {
      showFieldError('a_date', "La date ne peut pas etre dans le futur !"); ok = false;
    }
  }

  if (!document.getElementById('a_mode').value) {
    showFieldError('a_mode', "Veuillez selectionner un mode d'entree !"); ok = false;
  }

  return ok;
}


// SUBMIT ADMISSION : ajoute ou modifie
async function submitAdmission() {
  if (!(await validateAdmission())) return;

  const data = {
    action:            editingId ? 'update' : 'add',
    id_admission:      document.getElementById('a_id').value.trim(),
    date_arrive_relle: document.getElementById('a_date').value,
    mode_entree:       document.getElementById('a_mode').value,
    id_ticket:         document.getElementById('a_ticket').value,
    id_salle:          document.getElementById('a_salle').value
  };

  try {
    const r = await postData(CRUD_ADMISSION, data);
    if (r.success) {
      const s = document.getElementById('msg_form_success');
      s.textContent = r.message; s.style.display = 'block';
      showToast(r.message, 'success');
      resetForm();
      loadAdmissions();
      loadTickets();
      loadSallesDropdown();
      prefetchAdmissionIds();
    } else {
      const g = document.getElementById('msg_form_global');
      g.textContent = r.error || "Erreur lors de l'enregistrement.";
      g.style.display = 'block';
    }
  } catch (e) {
    document.getElementById('msg_form_global').textContent = 'Erreur reseau.';
    document.getElementById('msg_form_global').style.display = 'block';
  }
}


// EDIT ADMISSION : pre-remplit le formulaire pour modification
async function editAdmission(id) {
  const a = allAdmissions.find(x => x.id_admission == id);
  if (!a) return;
  editingId = id;

  document.getElementById('formTitle').textContent      = 'Modifier Admission';
  document.getElementById('btnSubmitLabel').textContent = 'Enregistrer';
  document.getElementById('a_id').value                 = a.id_admission;
  document.getElementById('a_id').disabled              = true;
  document.getElementById('a_date').value               = a.date_arrive_relle ? String(a.date_arrive_relle).split(' ')[0] : '';
  document.getElementById('a_mode').value               = a.mode_entree || '';

  // Le ticket actuel peut etre "utilise" donc absent du dropdown : on l'ajoute manuellement
  const selT = document.getElementById('a_ticket');
  let found = false;
  for (const opt of selT.options) {
    if (opt.value == a.id_ticket) { found = true; break; }
  }
  if (!found && a.id_ticket) {
    const opt       = document.createElement('option');
    opt.value       = a.id_ticket;
    opt.textContent = a.id_ticket + ' (actuel)';
    selT.appendChild(opt);
  }
  selT.value = a.id_ticket || '';
  showTicketInfo();

  document.getElementById('a_salle').value = a.id_salle || '';

  document.querySelector('.right-panel').scrollIntoView({ behavior: 'smooth' });
}


// DELETE ADMISSION
async function deleteAdmission(id) {
  if (!confirm(`Supprimer l'admission #${id} ? Le ticket associe sera remis a disponible.`)) return;
  try {
    const r = await postData(CRUD_ADMISSION, { action: 'delete', id_admission: id });
    if (r.success) {
      showToast(r.message, 'success');
      loadAdmissions();
      loadTickets();
    } else {
      showToast(r.error || 'Erreur suppression', 'error');
    }
  } catch (e) { showToast('Erreur reseau', 'error'); }
}


// RESET FORMULAIRE ADMISSION
function resetForm() {
  editingId = null;
  document.getElementById('formTitle').textContent      = 'Nouvelle Admission';
  document.getElementById('btnSubmitLabel').textContent = 'Ajouter Admission';
  document.getElementById('a_id').value      = '';
  document.getElementById('a_id').disabled   = false;
  document.getElementById('a_date').value    = new Date().toISOString().split('T')[0];
  document.getElementById('a_mode').value    = '';
  document.getElementById('a_ticket').value  = '';
  document.getElementById('a_salle').value   = '';
  document.getElementById('ticketInfo').className = 'ticket-info';
  clearErrors(['a_id', 'a_date', 'a_mode', 'a_ticket']);
  hide('msg_form_global');
  hide('msg_form_success');
}


// EXPORT CSV ADMISSIONS
function exportCSV() {
  const headers = ['ID Admission', "Date d'arrivee", "Mode d'entree", 'ID Ticket', 'ID Salle'];
  const rows = allAdmissions.map(a => [
    a.id_admission,
    (a.date_arrive_relle || '').split(' ')[0],
    a.mode_entree,
    a.id_ticket  || '',
    a.id_salle   || ''
  ]);
  const csv  = [headers, ...rows].map(r => r.map(c => `"${String(c||'').replace(/"/g,'""')}"`).join(',')).join('\n');
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
  const a    = document.createElement('a');
  a.href = URL.createObjectURL(blob); a.download = 'admissions.csv'; a.click();
}


// ============================================================================
// SECTION SALLES
// ============================================================================

let allSalles      = [];
let editingSalleId = null;
let currentPageS   = 1;
const PAGE_SIZE_S  = 8;
let existingSalleIds = [];


// CHARGER LES MEDECINS pour le dropdown du formulaire salle (id_role = 3)
async function loadMedecins() {
  try {
    const r   = await fetch(CRUD_SALLE + '?action=getMedecins');
    const j   = await r.json();
    const sel = document.getElementById('s_medecin');
    if (!sel) return;
    if (j.success && j.data.length > 0) {
      sel.innerHTML = '<option value="">Selectionner un medecin...</option>';
      j.data.forEach(m => {
        const opt   = document.createElement('option');
        opt.value   = m.id_user;
        const label = [m.nom, m.prenom].filter(Boolean).join(' ');
        opt.textContent = label ? `${label} (${m.id_user})` : m.id_user;
        sel.appendChild(opt);
      });
    } else {
      sel.innerHTML = '<option value="">Aucun medecin trouve</option>';
    }
  } catch (e) {
    const sel = document.getElementById('s_medecin');
    if (sel) sel.innerHTML = '<option value="">Erreur chargement medecins</option>';
  }
}


// CHARGER LES SALLES depuis la BDD
async function loadSalles() {
  try {
    const fd = new FormData();
    fd.append('action', 'getAll');
    const res = await fetch(CRUD_SALLE, { method: 'POST', body: fd });
    const r   = await res.json();
    if (r.success) {
      allSalles = r.data;
      updateStatsSalles();
      renderSalleTable();
      prefetchSalleIds();
    } else {
      const tb = document.getElementById('salleTableBody');
      if (tb) tb.innerHTML = `<tr><td colspan="5" class="no-data" style="color:var(--red)">Erreur : ${r.error}</td></tr>`;
    }
  } catch (e) {
    const tb = document.getElementById('salleTableBody');
    if (tb) tb.innerHTML = `<tr><td colspan="5" class="no-data" style="color:var(--red)">Erreur reseau.</td></tr>`;
  }
}

// PRE-CHARGER LES IDS SALLE pour le de aleatoire
async function prefetchSalleIds() {
  try {
    const fd = new FormData();
    fd.append('action', 'getExistingIds');
    const res = await fetch(CRUD_SALLE, { method: 'POST', body: fd });
    const r   = await res.json();
    if (r.success) existingSalleIds = r.ids.map(Number);
  } catch (e) {}
}

// Bouton de aleatoire pour l'ID salle
function rollDiceSalle() {
  const id = genRandomId(existingSalleIds);
  document.getElementById('s_id').value = id;
  clearErrors(['s_id']);
}


// STATS SALLES
function updateStatsSalles() {
  const total   = allSalles.length;
  const dispo   = allSalles.filter(s => s.statut === 'disponible').length;
  const indispo = allSalles.filter(s => s.statut !== 'disponible').length;

  const elTotal   = document.getElementById('stat-total-salles');
  const elDispo   = document.getElementById('stat-dispo-salles');
  const elIndispo = document.getElementById('stat-indispo-salles');
  const badgeT    = document.getElementById('stat-badge-salles');

  if (elTotal)   elTotal.textContent   = total;
  if (elDispo)   elDispo.textContent   = dispo;
  if (elIndispo) elIndispo.textContent = indispo;
  if (badgeT)    badgeT.textContent    = total;
}


// RENDER TABLE SALLES
function renderSalleTable() {
  const search = (document.getElementById('salleSearch')?.value || '').toLowerCase();
  const fStat  = document.getElementById('filterSalleStatut')?.value || '';

  const filtered = allSalles.filter(s => {
    const ms = !search ||
      String(s.id_salle).includes(search) ||
      String(s.numero  || '').includes(search) ||
      (s.medecin_nom_complet || '').toLowerCase().includes(search);
    return ms && (!fStat || s.statut === fStat);
  });

  const total = filtered.length;
  const pages = Math.max(1, Math.ceil(total / PAGE_SIZE_S));
  if (currentPageS > pages) currentPageS = pages;
  const slice = filtered.slice((currentPageS - 1) * PAGE_SIZE_S, currentPageS * PAGE_SIZE_S);

  const countEl = document.getElementById('sallePaginCount');
  const pageEl  = document.getElementById('sallePageInfo');
  if (countEl) countEl.textContent = `Affichage de ${slice.length} sur ${total} salle(s)`;
  if (pageEl)  pageEl.textContent  = `${currentPageS} / ${pages}`;

  const tbody = document.getElementById('salleTableBody');
  if (!tbody) return;

  if (!slice.length) {
    tbody.innerHTML = '<tr><td colspan="5" class="no-data">Aucune salle trouvee.</td></tr>';
    return;
  }

  const statusMap = {
    'disponible':     ['badge-normal',  'Disponible'],
    'non disponible': ['badge-urgence', 'Non disponible']
  };

  tbody.innerHTML = slice.map(s => {
    const med = s.medecin_nom_complet || 'Non assigne';
    const [sCls, sLabel] = statusMap[s.statut] || ['badge-autre', s.statut || 'Inconnu'];
    return `<tr>
      <td class="id-cell">${esc(s.id_salle)}</td>
      <td>${esc(s.numero)}</td>
      <td><span class="badge ${sCls}">${esc(sLabel)}</span></td>
      <td>${esc(med)}</td>
      <td>
        <div class="actions-cell">
          <button class="icon-btn" onclick="editSalle(${s.id_salle})" title="Modifier">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
          </button>
          <button class="icon-btn del" onclick="deleteSalle(${s.id_salle})" title="Supprimer">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><path d="M10 11v6M14 11v6M9 6V4h6v2"/></svg>
          </button>
        </div>
      </td>
    </tr>`;
  }).join('');
}

function prevPageS() { if (currentPageS > 1) { currentPageS--; renderSalleTable(); } }
function nextPageS() {
  const s = (document.getElementById('salleSearch')?.value || '').toLowerCase();
  const f = document.getElementById('filterSalleStatut')?.value || '';
  const total = allSalles.filter(x =>
    (!s || String(x.id_salle).includes(s) || String(x.numero||'').includes(s))
    && (!f || x.statut === f)
  ).length;
  if (currentPageS < Math.ceil(total / PAGE_SIZE_S)) { currentPageS++; renderSalleTable(); }
}


// VALIDATION SALLE
// numero est INT(11) : chiffres uniquement, pas de symboles ni lettres.
async function validateSalle() {
  let ok = true;
  clearErrors(['s_id', 's_numero', 's_statut']);
  hide('msg_salle_global');
  hide('msg_salle_success');

  const inputId     = document.getElementById('s_id');
  const inputNumero = document.getElementById('s_numero');
  const inputStatut = document.getElementById('s_statut');
  const val         = String(inputId.value).trim();

  // Validation ID salle : chiffres uniquement, max 8 digits
  if (val === '') {
    showFieldError('s_id', "L'ID salle est obligatoire !"); ok = false;
  } else if (!/^\d+$/.test(val)) {
    showFieldError('s_id', "L'ID doit contenir uniquement des chiffres !"); ok = false;
  } else if (val.length > 8) {
    showFieldError('s_id', "Maximum 8 chiffres !"); ok = false;
  } else if (parseInt(val) <= 0) {
    showFieldError('s_id', "L'ID doit etre superieur a 0 !"); ok = false;
  } else if (!editingSalleId) {
    try {
      const r = await fetch(CRUD_SALLE + `?action=checkId&id=${encodeURIComponent(val)}`);
      const j = await r.json();
      if (j.success && j.exists) {
        showFieldError('s_id', `L'ID ${val} existe deja dans la base !`); ok = false;
      }
    } catch (e) {}
  }

  // Validation numero : INT(11), donc chiffres uniquement, superieur a 0
  const numVal = String(inputNumero.value).trim();
  if (numVal === '') {
    showFieldError('s_numero', "Le numero est obligatoire !"); ok = false;
  } else if (!/^\d+$/.test(numVal)) {
    showFieldError('s_numero', "Le numero doit contenir uniquement des chiffres !"); ok = false;
  } else if (parseInt(numVal) <= 0) {
    showFieldError('s_numero', "Le numero doit etre superieur a 0 !"); ok = false;
  }

  // Validation statut
  if (!inputStatut.value) {
    showFieldError('s_statut', "Veuillez selectionner un statut !"); ok = false;
  }

  return ok;
}


// SUBMIT SALLE
async function submitSalle() {
  if (!(await validateSalle())) return;

  const fd = new FormData();
  fd.append('action',     editingSalleId ? 'update' : 'add');
  fd.append('id_salle',   document.getElementById('s_id').value.trim());
  fd.append('numero',     document.getElementById('s_numero').value.trim());
  fd.append('statut',     document.getElementById('s_statut').value);
  fd.append('id_medecin', document.getElementById('s_medecin').value);

  try {
    const res = await fetch(CRUD_SALLE, { method: 'POST', body: fd });
    const r   = await res.json();
    if (r.success) {
      const s = document.getElementById('msg_salle_success');
      if (s) { s.textContent = r.message; s.style.display = 'block'; }
      showToast(r.message, 'success');
      resetSalleForm();
      loadSalles();
      loadSallesDropdown();
    } else {
      const g = document.getElementById('msg_salle_global');
      if (g) { g.textContent = r.error || 'Erreur.'; g.style.display = 'block'; }
    }
  } catch (e) {
    const g = document.getElementById('msg_salle_global');
    if (g) { g.textContent = 'Erreur reseau.'; g.style.display = 'block'; }
  }
}


// EDIT SALLE : pre-remplit le formulaire
function editSalle(id) {
  const s = allSalles.find(x => x.id_salle == id);
  if (!s) return;
  editingSalleId = id;

  document.getElementById('salleFormTitle').textContent = 'Modifier Salle';
  document.getElementById('salleBtnLabel').textContent  = 'Enregistrer';
  document.getElementById('s_id').value      = s.id_salle;
  document.getElementById('s_id').disabled   = true;
  document.getElementById('s_numero').value  = s.numero    || '';
  document.getElementById('s_statut').value  = s.statut    || '';
  document.getElementById('s_medecin').value = s.id_medecin || '';

  document.querySelector('.right-panel-salle')?.scrollIntoView({ behavior: 'smooth' });
}


// DELETE SALLE
async function deleteSalle(id) {
  if (!confirm(`Supprimer la salle #${id} ? Cette action est irreversible.`)) return;
  const fd = new FormData();
  fd.append('action',   'delete');
  fd.append('id_salle', id);
  try {
    const res = await fetch(CRUD_SALLE, { method: 'POST', body: fd });
    const r   = await res.json();
    if (r.success) {
      showToast(r.message, 'success');
      loadSalles();
      loadSallesDropdown();
    }
    else showToast(r.error || 'Erreur suppression', 'error');
  } catch (e) { showToast('Erreur reseau', 'error'); }
}


// RESET FORMULAIRE SALLE
function resetSalleForm() {
  editingSalleId = null;
  document.getElementById('salleFormTitle').textContent = 'Nouvelle Salle';
  document.getElementById('salleBtnLabel').textContent  = 'Ajouter Salle';
  document.getElementById('s_id').value      = '';
  document.getElementById('s_id').disabled   = false;
  document.getElementById('s_numero').value  = '';
  document.getElementById('s_statut').value  = '';
  document.getElementById('s_medecin').value = '';
  clearErrors(['s_id', 's_numero', 's_statut']);
  hide('msg_salle_global');
  hide('msg_salle_success');
}


// EXPORT CSV SALLES
function exportSalleCSV() {
  const headers = ['ID Salle', 'Numero', 'Statut', 'Medecin'];
  const rows = allSalles.map(s => [
    s.id_salle,
    s.numero,
    s.statut,
    s.medecin_nom_complet || s.id_medecin || ''
  ]);
  const csv  = [headers, ...rows].map(r => r.map(c => `"${String(c||'').replace(/"/g,'""')}"`).join(',')).join('\n');
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
  const a    = document.createElement('a');
  a.href = URL.createObjectURL(blob); a.download = 'salles.csv'; a.click();
}


// ============================================================================
// HELPERS
// ============================================================================

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
