// facture.js  :  Logique JavaScript pour la Gestion des Factures
//
// Charge par index.html via : <script src="facture.js" defer></script>
//
// Chemins PHP :
//   ../../controleur/backoffice/facture_crud.php
//   ../../controleur/backoffice/type_paiement_crud.php

const CRUD_FACTURE = '../../controleur/backoffice/facture_crud.php';
const CRUD_TYPE    = '../../controleur/backoffice/type_paiement_crud.php';

// ETAT GLOBAL
let allFactures       = [];
let allTypes          = [];
let editingId         = null;
let editingTypeId     = null;
let currentPage       = 1;
const PAGE_SIZE       = 8;

// IDs existants en memoire pour le de aleatoire (evite des requetes repetees)
let existingFactureIds = [];
let existingTypeIds    = [];


// INITIALISATION
document.addEventListener('DOMContentLoaded', () => {
  document.getElementById('f_date').value = new Date().toISOString().split('T')[0];
  testConnection();
});


// TEST CONNEXION
// Envoie un ping au PHP pour verifier Apache + MySQL.
// Si OK, charge toutes les donnees. Sinon, affiche une banniere rouge.
async function testConnection() {
  const banner = document.getElementById('connBanner');
  try {
    const res = await fetch(CRUD_FACTURE + '?action=ping');
    const r   = await res.json();
    if (r.success) {
      banner.className = 'conn-banner ok';
      banner.innerHTML = `<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
      <span><strong>jumeaunum</strong></span>`;
      loadFactures();
      loadPatients();
      loadTypes();
      loadRessources();
      loadRdvs();
      prefetchIds();
    } else {
      showConnError(r.error);
    }
  } catch (e) {
    showConnError('Impossible de joindre le serveur PHP. Verifiez que :<br>'
      + '<strong>1.</strong> XAMPP est demarre (Apache + MySQL)<br>'
      + '<strong>2.</strong> Vous ouvrez via <code>localhost</code> et non depuis le systeme de fichiers<br>'
      + '<strong>3.</strong> Le dossier est dans <code>htdocs/projetweb/gestion_paiement/</code>');
  }
}

function showConnError(msg) {
  const banner = document.getElementById('connBanner');
  banner.className = 'conn-banner error';
  banner.innerHTML = `<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M15 9l-6 6M9 9l6 6"/></svg>
    <span>${msg}</span>`;
  document.getElementById('tableBody').innerHTML =
    `<tr><td colspan="8" class="no-data" style="color:var(--red)">En attente de connexion a la base de donnees.</td></tr>`;
}


// TOAST : notification visuelle en bas a droite
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
async function prefetchIds() {
  try {
    const rf = await postData(CRUD_FACTURE, { action: 'getExistingIds' });
    if (rf.success) existingFactureIds = rf.ids.map(Number);
  } catch (e) {}
  try {
    const rt = await postData(CRUD_TYPE, { action: 'getExistingIds' });
    if (rt.success) existingTypeIds = rt.ids.map(Number);
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

// Bouton de aleatoire pour l'ID facture
function rollDiceFacture() {
  const id = genRandomId(existingFactureIds);
  document.getElementById('f_id').value = id;
  clearErrors(['f_id']);
}

// Bouton de aleatoire pour l'ID type paiement
function rollDiceType() {
  const id = genRandomId(existingTypeIds);
  document.getElementById('t_id').value = id;
  clearErrors(['t_id']);
}


// CHARGER LES FACTURES depuis la BDD et mettre a jour le tableau + les stats
async function loadFactures() {
  try {
    const r = await postData(CRUD_FACTURE, { action: 'getAll' });
    if (r.success) {
      allFactures = r.data;
      updateStats();
      renderTable();
    } else {
      document.getElementById('tableBody').innerHTML =
        `<tr><td colspan="8" class="no-data" style="color:var(--red)">Erreur : ${r.error}</td></tr>`;
    }
  } catch (e) {
    document.getElementById('tableBody').innerHTML =
      `<tr><td colspan="8" class="no-data" style="color:var(--red)">Erreur reseau.</td></tr>`;
  }
}


// STATS : compte les factures par statut et affiche les chiffres dans les cartes
function updateStats() {
  const payees  = allFactures.filter(f => f.statut === 'Payee');
  const nonPay  = allFactures.filter(f => f.statut === 'Non payee');
  const rev     = payees.reduce((s, f) => s + parseFloat(f.montant || 0), 0);
  const npSum   = nonPay.reduce((s, f) => s + parseFloat(f.montant || 0), 0);

  document.getElementById('stat-rev').textContent    = rev.toFixed(2) + ' TND';
  document.getElementById('stat-p').textContent      = payees.length;
  document.getElementById('stat-np').textContent     = nonPay.length;
  document.getElementById('stat-total').textContent  = allFactures.length;
  document.getElementById('stat-np-sub').textContent = 'Montant : ' + npSum.toFixed(2) + ' TND';
  document.getElementById('stat-badge-rev').textContent   = payees.length;
  document.getElementById('stat-badge-p').textContent     = payees.length;
  document.getElementById('stat-badge-np').textContent    = nonPay.length;
  document.getElementById('stat-badge-total').textContent = allFactures.length;
}


// RENDER TABLE : filtre, pagine et genere les lignes HTML du tableau
function renderTable() {
  const search = document.getElementById('searchInput').value.toLowerCase();
  const fStat  = document.getElementById('filterStatut').value;

  const filtered = allFactures.filter(f => {
    const ms = !search ||
      String(f.id_facture).includes(search) ||
      (f.patient_nom_complet || '').toLowerCase().includes(search) ||
      (f.nom_type            || '').toLowerCase().includes(search);
    return ms && (!fStat || f.statut === fStat);
  });

  const total = filtered.length;
  const pages = Math.max(1, Math.ceil(total / PAGE_SIZE));
  if (currentPage > pages) currentPage = pages;
  const slice = filtered.slice((currentPage - 1) * PAGE_SIZE, currentPage * PAGE_SIZE);

  document.getElementById('paginCount').textContent = `Affichage de ${slice.length} sur ${total} facture(s)`;
  document.getElementById('pageInfo').textContent   = `${currentPage} / ${pages}`;

  const tbody = document.getElementById('tableBody');
  if (!slice.length) {
    tbody.innerHTML = '<tr><td colspan="8" class="no-data">Aucune facture trouvee.</td></tr>';
    return;
  }

  const sMap = {
    'Payee':     ['badge-payee',   'Payee'],
    'Non payee': ['badge-nonpaye', 'Non payee']
  };

  tbody.innerHTML = slice.map(f => {
    const name    = f.patient_nom_complet ||
                    [f.patient_nom, f.patient_prenom].filter(Boolean).join(' ') ||
                    f.id_patient || 'Inconnu';
    const type    = f.nom_type || f.id_type_paiement || 'Inconnu';
    const res     = f.ressource_nom ? `${f.ressource_nom} (#${f.id_ressource_assignee})` : 'Aucune';
    const rdv     = f.id_rdv ? `#${f.id_rdv}` : 'Aucun';
    const date    = f.date_facture ? String(f.date_facture).split(' ')[0] : 'Inconnu';
    const [sCls, sLabel] = sMap[f.statut] || ['badge-nonpaye', f.statut || 'Inconnu'];
    const montant = parseFloat(f.montant || 0).toFixed(2);

    return `<tr>
      <td class="id-cell">${esc(f.id_facture)}</td>
      <td>${esc(name)}</td>
      <td>${esc(type)}</td>
      <td class="amount-cell">${montant} TND</td>
      <td>${esc(res)}</td>
      <td>${esc(rdv)}</td>
      <td>${esc(date)}</td>
      <td><span class="badge ${sCls}">${esc(sLabel)}</span></td>
      <td>
        <div class="actions-cell">
          <button class="icon-btn" onclick="editFacture(${f.id_facture})" title="Modifier">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
          </button>
          <button class="icon-btn del" onclick="deleteFacture(${f.id_facture})" title="Supprimer">
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
  const f = document.getElementById('filterStatut').value;
  const total = allFactures.filter(x =>
    (!s || String(x.id_facture).includes(s) || (x.patient_nom_complet||'').toLowerCase().includes(s))
    && (!f || x.statut === f)
  ).length;
  if (currentPage < Math.ceil(total / PAGE_SIZE)) { currentPage++; renderTable(); }
}


// CHARGER LES PATIENTS pour le dropdown
async function loadPatients() {
  try {
    const r   = await postData(CRUD_FACTURE, { action: 'getPatients' });
    const sel = document.getElementById('f_patient');
    if (r.success && r.data.length > 0) {
      sel.innerHTML = '<option value="">Selectionner un patient...</option>';
      r.data.forEach(p => {
        const opt   = document.createElement('option');
        opt.value   = p.id_user;
        const label = [p.nom, p.prenom].filter(Boolean).join(' ');
        opt.textContent = label ? `${label} (${p.id_user})` : p.id_user;
        sel.appendChild(opt);
      });
    } else {
      sel.innerHTML = '<option value="">Aucun patient trouve</option>';
    }
  } catch (e) {
    document.getElementById('f_patient').innerHTML = '<option value="">Erreur chargement</option>';
  }
}


// CHARGER LES RESSOURCES pour le dropdown id_ressource_assignee
async function loadRessources() {
  try {
    const r   = await postData(CRUD_FACTURE, { action: 'getRessources' });
    const sel = document.getElementById('f_ressource');
    if (r.success && r.data.length > 0) {
      sel.innerHTML = '<option value="">Aucune (optionnel)...</option>';
      r.data.forEach(rs => {
        const opt   = document.createElement('option');
        opt.value   = rs.id_ressource;
        opt.textContent = `${rs.Nom} (${rs.Type}) #${rs.id_ressource}`;
        sel.appendChild(opt);
      });
    } else {
      sel.innerHTML = '<option value="">Aucune ressource disponible</option>';
    }
  } catch (e) {
    document.getElementById('f_ressource').innerHTML = '<option value="">Erreur chargement</option>';
  }
}


// CHARGER LES RDV pour le dropdown id_rdv
async function loadRdvs() {
  try {
    const r   = await postData(CRUD_FACTURE, { action: 'getRdvs' });
    const sel = document.getElementById('f_rdv');
    if (r.success && r.data.length > 0) {
      sel.innerHTML = '<option value="">Aucun (optionnel)...</option>';
      r.data.forEach(rdv => {
        const opt   = document.createElement('option');
        opt.value   = rdv.id_rdv;
        opt.textContent = `#${rdv.id_rdv} | ${rdv.date_rdv || ''} | ${rdv.type_consultation || ''}`;
        sel.appendChild(opt);
      });
    } else {
      sel.innerHTML = '<option value="">Aucun RDV disponible</option>';
    }
  } catch (e) {
    document.getElementById('f_rdv').innerHTML = '<option value="">Erreur chargement</option>';
  }
}


// CHARGER LES LIGNES ORDONNANCE selon le patient selectionne
// Filtre : statut = 'Non payee' ET connecte au patient via ordonnance
async function loadLignesOrd(idPatient) {
  const sel = document.getElementById('f_ligneOrd');
  if (!idPatient) {
    sel.innerHTML = '<option value="">Selectionnez un patient d\'abord...</option>';
    sel.disabled  = true;
    return;
  }
  sel.innerHTML = '<option value="">Chargement...</option>';
  sel.disabled  = true;
  try {
    const r = await fetch(CRUD_FACTURE + `?action=getLignesOrd&id_patient=${encodeURIComponent(idPatient)}`);
    const j = await r.json();
    if (j.success && j.data.length > 0) {
      sel.innerHTML = '<option value="">Aucune (optionnel)...</option>';
      j.data.forEach(lg => {
        const opt   = document.createElement('option');
        opt.value   = lg.id_ligne;
        opt.textContent = `#${lg.id_ligne} | ${lg.date_ordonnance || ''} | ${lg.details || ''}`;
        sel.appendChild(opt);
      });
      sel.disabled = false;
    } else {
      sel.innerHTML = '<option value="">Aucune ligne Non payee pour ce patient</option>';
      sel.disabled  = false;
    }
  } catch (e) {
    sel.innerHTML = '<option value="">Erreur chargement</option>';
    sel.disabled  = false;
  }
}


// CHARGER LES TYPES pour le dropdown et la liste des types
async function loadTypes() {
  try {
    const r = await postData(CRUD_TYPE, { action: 'getAll' });
    if (r.success) {
      allTypes = r.data;
      renderTypeList();
      syncTypeDropdown();
    }
  } catch (e) {}
}

// Affiche la liste des types dans le panneau droit
function renderTypeList() {
  const container = document.getElementById('typeList');
  if (!allTypes.length) {
    container.innerHTML = '<div style="color:var(--muted);font-size:.8rem;padding:4px 0">Aucun type enregistre.</div>';
    return;
  }
  container.innerHTML = allTypes.map(t => `
    <div class="type-row">
      <span class="type-name">${esc(t.nom_type)}</span>
      <span class="type-amount">${parseFloat(t.montant).toFixed(2)} TND</span>
      <button class="type-edit" onclick="editType('${esc(t.id_type)}')" title="Modifier">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
      </button>
      <button class="type-del" onclick="deleteType('${esc(t.id_type)}')" title="Supprimer">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><path d="M10 11v6M14 11v6M9 6V4h6v2"/></svg>
      </button>
    </div>`).join('');
}

// Met a jour le dropdown du formulaire facture avec les types charges
function syncTypeDropdown() {
  const sel = document.getElementById('f_type');
  const cur = sel.value;
  sel.innerHTML = '<option value="">Selectionner un type...</option>';
  allTypes.forEach(t => {
    const opt           = document.createElement('option');
    opt.value           = t.id_type;
    opt.dataset.montant = t.montant;
    opt.textContent     = `${t.nom_type} : ${parseFloat(t.montant).toFixed(2)} TND`;
    sel.appendChild(opt);
  });
  if (cur) sel.value = cur;
}

// Quand l'utilisateur change le type de paiement, met a jour le montant automatiquement
function autoMontant() {
  const sel = document.getElementById('f_type');
  const opt = sel.options[sel.selectedIndex];
  document.getElementById('f_montant').value =
    opt && opt.dataset.montant ? parseFloat(opt.dataset.montant).toFixed(2) + ' TND' : '';
}


// VALIDATION FACTURE
// Verifie tous les champs obligatoires avant l'envoi.
// Retourne true si tout est OK, false sinon (et affiche les erreurs).
async function validateFacture() {
  let ok = true;

  clearErrors(['f_id', 'f_patient', 'f_type', 'f_date', 'f_statut']);
  hide('msg_form_global'); hide('msg_form_success');

  const inputId = document.getElementById('f_id');
  const val     = String(inputId.value).trim();

  // Validation ID : chiffres uniquement, max 8 digits, superieur a 0, unique en BDD
  if (val === '') {
    showFieldError('f_id', "L'ID facture est obligatoire !"); ok = false;
  } else if (!/^\d+$/.test(val)) {
    showFieldError('f_id', "L'ID doit contenir uniquement des chiffres (entier) !"); ok = false;
  } else if (val.length > 8) {
    showFieldError('f_id', "Maximum 8 chiffres !"); ok = false;
  } else if (parseInt(val) <= 0) {
    showFieldError('f_id', "L'ID doit etre superieur a 0 !"); ok = false;
  } else if (!editingId) {
    // Verifier en BDD seulement en mode ajout
    try {
      const r = await fetch(CRUD_FACTURE + `?action=checkId&id=${encodeURIComponent(val)}`);
      const j = await r.json();
      if (j.success && j.exists) {
        showFieldError('f_id', `L'ID ${val} existe deja dans la base !`); ok = false;
      }
    } catch (e) {}
  }

  if (!document.getElementById('f_patient').value) {
    showFieldError('f_patient', "Veuillez selectionner un patient !"); ok = false;
  }

  if (!document.getElementById('f_type').value) {
    showFieldError('f_type', "Veuillez selectionner un type de paiement !"); ok = false;
  }

  // Validation date : obligatoire, ne peut pas etre dans le futur.
  // La date d'aujourd'hui est acceptee. Seul demain ou apres est refuse.
  if (!document.getElementById('f_date').value) {
    showFieldError('f_date', "La date est obligatoire !"); ok = false;
  } else {
    const chosen = new Date(document.getElementById('f_date').value);
    const today  = new Date();
    today.setHours(23, 59, 59, 999); // On compare jusqu'a la fin de la journee d'aujourd'hui
    if (chosen > today) {
      showFieldError('f_date', "La date ne peut pas etre dans le futur !"); ok = false;
    }
  }

  if (!document.getElementById('f_statut').value) {
    showFieldError('f_statut', "Veuillez selectionner un statut !"); ok = false;
  }

  return ok;
}


// SUBMIT FACTURE : envoie l'ajout ou la modification au PHP
async function submitFacture() {
  if (!(await validateFacture())) return;

  const sel     = document.getElementById('f_type');
  const opt     = sel.options[sel.selectedIndex];
  const montant = opt && opt.dataset.montant ? opt.dataset.montant : 0;

  const data = {
    action:               editingId ? 'update' : 'add',
    id_facture:           document.getElementById('f_id').value.trim(),
    montant,
    statut:               document.getElementById('f_statut').value,
    date_facture:         document.getElementById('f_date').value,
    id_patient:           document.getElementById('f_patient').value,
    id_rdv:               document.getElementById('f_rdv').value,
    id_type_paiement:     document.getElementById('f_type').value,
    id_ligneOrd:          document.getElementById('f_ligneOrd').value,
    id_ressource_assignee: document.getElementById('f_ressource').value
  };

  try {
    const r = await postData(CRUD_FACTURE, data);
    if (r.success) {
      const s = document.getElementById('msg_form_success');
      s.textContent = r.message; s.style.display = 'block';
      showToast(r.message, 'success');
      resetForm();
      loadFactures();
      prefetchIds();
    } else {
      const g = document.getElementById('msg_form_global');
      g.textContent = r.error || 'Erreur lors de l\'enregistrement.';
      g.style.display = 'block';
    }
  } catch (e) {
    document.getElementById('msg_form_global').textContent = 'Erreur reseau.';
    document.getElementById('msg_form_global').style.display = 'block';
  }
}


// EDIT FACTURE : pre-remplit le formulaire pour modification
function editFacture(id) {
  const f = allFactures.find(x => x.id_facture == id);
  if (!f) return;
  editingId = id;

  document.getElementById('formTitle').textContent      = 'Modifier Facture';
  document.getElementById('btnSubmitLabel').textContent = 'Enregistrer';
  document.getElementById('f_id').value                 = f.id_facture;
  document.getElementById('f_id').disabled              = true;
  document.getElementById('f_patient').value            = f.id_patient        || '';
  document.getElementById('f_type').value               = f.id_type_paiement  || '';
  autoMontant();
  document.getElementById('f_ressource').value          = f.id_ressource_assignee || '';
  document.getElementById('f_rdv').value                = f.id_rdv             || '';
  document.getElementById('f_date').value               = f.date_facture ? String(f.date_facture).split(' ')[0] : '';
  document.getElementById('f_statut').value             = f.statut             || '';

  // Charger les lignes ordonnance correspondant au patient
  if (f.id_patient) {
    loadLignesOrd(f.id_patient).then(() => {
      document.getElementById('f_ligneOrd').value = f.id_ligneOrd || '';
    });
  }

  document.querySelector('.right-panel').scrollIntoView({ behavior: 'smooth' });
}


// DELETE FACTURE
async function deleteFacture(id) {
  if (!confirm(`Supprimer la facture #${id} ? Cette action est irreversible.`)) return;
  try {
    const r = await postData(CRUD_FACTURE, { action: 'delete', id_facture: id });
    if (r.success) {
      showToast(r.message, 'success');
      loadFactures();
      prefetchIds();
    } else {
      showToast(r.error || 'Erreur suppression', 'error');
    }
  } catch (e) { showToast('Erreur reseau', 'error'); }
}


// RESET FORMULAIRE FACTURE
function resetForm() {
  editingId = null;
  document.getElementById('formTitle').textContent      = 'Nouvelle Facture';
  document.getElementById('btnSubmitLabel').textContent = 'Ajouter Facture';
  document.getElementById('f_id').value       = '';
  document.getElementById('f_id').disabled    = false;
  document.getElementById('f_patient').value  = '';
  document.getElementById('f_type').value     = '';
  document.getElementById('f_montant').value  = '';
  document.getElementById('f_ressource').value = '';
  document.getElementById('f_rdv').value      = '';
  document.getElementById('f_ligneOrd').innerHTML = '<option value="">Selectionnez un patient d\'abord...</option>';
  document.getElementById('f_ligneOrd').disabled  = true;
  document.getElementById('f_date').value     = new Date().toISOString().split('T')[0];
  document.getElementById('f_statut').value   = '';
  clearErrors(['f_id', 'f_patient', 'f_type', 'f_date', 'f_statut']);
  hide('msg_form_global'); hide('msg_form_success');
}


// VALIDATION TYPE PAIEMENT
// Verifie les champs du formulaire type paiement dans la modale.
async function validateType() {
  let ok = true;
  clearErrors(['t_id', 't_nom', 't_montant']);
  hide('msg_type_global'); hide('msg_type_success');

  const inputId = document.getElementById('t_id');
  const val     = String(inputId.value).trim();

  // ID type : chiffres uniquement, max 8 digits, unique
  if (val === '') {
    showFieldError('t_id', "L'ID type est obligatoire !"); ok = false;
  } else if (!/^\d+$/.test(val)) {
    showFieldError('t_id', "L'ID doit contenir uniquement des chiffres (entier) !"); ok = false;
  } else if (val.length > 8) {
    showFieldError('t_id', "Maximum 8 chiffres !"); ok = false;
  } else if (parseInt(val) <= 0) {
    showFieldError('t_id', "L'ID doit etre superieur a 0 !"); ok = false;
  } else if (!editingTypeId) {
    try {
      const r = await fetch(CRUD_TYPE + `?action=checkId&id=${encodeURIComponent(val)}`);
      const j = await r.json();
      if (j.success && j.exists) {
        showFieldError('t_id', `L'ID ${val} existe deja !`); ok = false;
      }
    } catch (e) {}
  }

  if (document.getElementById('t_nom').value.trim() === '') {
    showFieldError('t_nom', "Le nom est obligatoire !"); ok = false;
  }

  const montantVal = document.getElementById('t_montant').value;
  if (montantVal === '' || isNaN(parseFloat(montantVal))) {
    showFieldError('t_montant', "Montant invalide !"); ok = false;
  } else if (parseFloat(montantVal) < 0) {
    showFieldError('t_montant', "Le montant doit etre superieur ou egal a 0 !"); ok = false;
  }

  return ok;
}


// MODAL TYPE PAIEMENT : ouvre et pre-remplit si edition
function openTypeModal(t) {
  editingTypeId = t ? t.id_type : null;
  document.getElementById('modalTypeTitle').textContent = t ? 'Modifier Type de Paiement' : 'Nouveau Type de Paiement';
  document.getElementById('btnTypeLabel').textContent   = t ? 'Enregistrer' : 'Ajouter Type';
  document.getElementById('t_id').value      = t ? t.id_type     : '';
  document.getElementById('t_id').disabled   = !!t;
  document.getElementById('t_nom').value     = t ? t.nom_type    : '';
  document.getElementById('t_desc').value    = t ? (t.description || '') : '';
  document.getElementById('t_montant').value = t ? t.montant     : '';
  clearErrors(['t_id', 't_nom', 't_montant']);
  hide('msg_type_global'); hide('msg_type_success');
  document.getElementById('typeModal').classList.add('open');
}

function closeTypeModal() {
  document.getElementById('typeModal').classList.remove('open');
  editingTypeId = null;
}

// SUBMIT TYPE PAIEMENT
async function submitType() {
  if (!(await validateType())) return;
  const data = {
    action:      editingTypeId ? 'update' : 'add',
    id_type:     document.getElementById('t_id').value.trim(),
    nom_type:    document.getElementById('t_nom').value.trim(),
    description: document.getElementById('t_desc').value.trim(),
    montant:     document.getElementById('t_montant').value
  };
  try {
    const r = await postData(CRUD_TYPE, data);
    if (r.success) {
      showToast(r.message || 'Succes !', 'success');
      closeTypeModal();
      loadTypes();
      prefetchIds();
    } else {
      const g = document.getElementById('msg_type_global');
      g.textContent = r.error || 'Erreur.'; g.style.display = 'block';
    }
  } catch (e) {
    document.getElementById('msg_type_global').textContent = 'Erreur reseau.';
    document.getElementById('msg_type_global').style.display = 'block';
  }
}

function editType(id) {
  const t = allTypes.find(x => x.id_type == id);
  if (t) openTypeModal(t);
}

async function deleteType(id) {
  if (!confirm(`Supprimer le type #${id} ?`)) return;
  try {
    const r = await postData(CRUD_TYPE, { action: 'delete', id_type: id });
    if (r.success) { showToast(r.message, 'success'); loadTypes(); prefetchIds(); }
    else showToast(r.error || 'Erreur suppression', 'error');
  } catch (e) { showToast('Erreur reseau', 'error'); }
}


// EXPORT CSV : genere un fichier CSV a partir des donnees en memoire
function exportCSV() {
  const headers = ['ID Facture', 'Patient', 'Type Paiement', 'Montant', 'Ressource', 'RDV', 'Date', 'Statut'];
  const rows = allFactures.map(f => [
    f.id_facture,
    f.patient_nom_complet || f.id_patient,
    f.nom_type            || f.id_type_paiement,
    f.montant,
    f.ressource_nom       || f.id_ressource_assignee || '',
    f.id_rdv              || '',
    (f.date_facture       || '').split(' ')[0],
    f.statut
  ]);
  const csv  = [headers, ...rows].map(r => r.map(c => `"${String(c||'').replace(/"/g,'""')}"`).join(',')).join('\n');
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
  const a    = document.createElement('a');
  a.href = URL.createObjectURL(blob); a.download = 'factures.csv'; a.click();
}


// HELPERS
function esc(s) {
  return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// showFieldError : colorie le champ en rouge et affiche le message sous lui
function showFieldError(fieldId, msg) {
  const el    = document.getElementById(fieldId);
  const msgEl = document.getElementById('msg_' + fieldId);
  if (el)    el.classList.add('error');
  if (msgEl) { msgEl.textContent = msg; msgEl.classList.add('visible'); }
}

// clearErrors : retire les bordures rouges et cache les messages pour une liste de champs
function clearErrors(ids) {
  ids.forEach(id => {
    const el    = document.getElementById(id);
    const msgEl = document.getElementById('msg_' + id);
    if (el)    el.classList.remove('error');
    if (msgEl) { msgEl.textContent = ''; msgEl.classList.remove('visible'); }
  });
}

// hide : cache un element HTML par son id
function hide(id) { const el = document.getElementById(id); if (el) el.style.display = 'none'; }

// Ferme la modale si on clique sur l'arriere-plan
document.addEventListener('DOMContentLoaded', () => {
  const modal = document.getElementById('typeModal');
  if (modal) {
    modal.addEventListener('click', function(e) {
      if (e.target === this) closeTypeModal();
    });
  }
});
