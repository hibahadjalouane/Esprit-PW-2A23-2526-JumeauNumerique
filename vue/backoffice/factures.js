// ═══════════════════════════════════════════════════════════
//  factures.js
//  Ce fichier est dans : vue/backoffice/factures.js
//  Il est appelé depuis : vue/backoffice/index.html
//
//  Les contrôleurs PHP sont dans : controleur/backoffice/
//  Chemin relatif depuis vue/backoffice/ → ../../controleur/backoffice/
// ═══════════════════════════════════════════════════════════

// ── PATHS vers les fichiers PHP ─────────────────────────────
const CRUD_FACTURE = '../../controleur/backoffice/facture_crud.php';
const CRUD_TYPE    = '../../controleur/backoffice/type_paiement_crud.php';

// ── STATE (variables globales du script) ────────────────────
let allFactures   = [];
let allTypes      = [];
let editingId     = null;   // id_facture en cours de modification
let editingTypeId = null;   // id_type en cours de modification
let currentPage   = 1;
const PAGE_SIZE   = 8;

// ── INIT ─────────────────────────────────────────────────────
// DOMContentLoaded = attendre que tout le HTML soit chargé
// avant d'exécuter le JS
document.addEventListener('DOMContentLoaded', () => {
  document.getElementById('f_date').value = new Date().toISOString().split('T')[0];
  testConnection(); // teste la connexion → si OK → charge les données
});

// ── TEST CONNEXION ───────────────────────────────────────────
async function testConnection() {
  const banner = document.getElementById('connBanner');
  try {
    const res = await fetch(CRUD_FACTURE + '?action=ping');
    const r   = await res.json();
    if (r.success) {
      banner.className = 'conn-banner ok';
      banner.innerHTML = `
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path d="M5 13l4 4L19 7"/>
        </svg>
        <span>Connecté à la base <strong>jumeaunum</strong> avec succès ✓</span>`;
      loadFactures();
      loadPatients();
      loadTypes();
    } else {
      showConnError(r.error);
    }
  } catch (e) {
    showConnError(
      'Impossible de joindre le serveur PHP. Vérifiez que :<br>'
      + '<strong>1.</strong> XAMPP est démarré (Apache + MySQL)<br>'
      + '<strong>2.</strong> Vous ouvrez via <code>http://localhost/...</code> et non depuis le bureau<br>'
      + '<strong>3.</strong> Le projet est bien dans <code>htdocs/gestion_paiement/</code>'
    );
  }
}

function showConnError(msg) {
  const banner = document.getElementById('connBanner');
  banner.className = 'conn-banner error';
  banner.innerHTML = `
    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
      <circle cx="12" cy="12" r="10"/><path d="M15 9l-6 6M9 9l6 6"/>
    </svg>
    <span>${msg}</span>`;
  document.getElementById('tableBody').innerHTML =
    `<tr><td colspan="8" class="no-data" style="color:var(--red)">
       En attente de connexion à la base de données.
     </td></tr>`;
}

// ── TOAST (message temporaire en bas à droite) ───────────────
function showToast(msg, type = 'success') {
  const t   = document.getElementById('toast');
  t.textContent = msg;
  t.className   = 'toast ' + type + ' show';
  setTimeout(() => { t.className = 'toast'; }, 3200);
}

// ── FETCH HELPER ─────────────────────────────────────────────
// Envoie une requête POST avec les données sous forme FormData
async function postData(url, data) {
  const fd = new FormData();
  for (const [k, v] of Object.entries(data)) fd.append(k, v ?? '');
  const res = await fetch(url, { method: 'POST', body: fd });
  return res.json();
}

// ═══════════════════════════════════════════════════════════
//  FACTURES — CRUD
// ═══════════════════════════════════════════════════════════

// ── READ ─────────────────────────────────────────────────────
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
      `<tr><td colspan="8" class="no-data" style="color:var(--red)">Erreur réseau.</td></tr>`;
  }
}

// ── STATS ────────────────────────────────────────────────────
function updateStats() {
  const payees = allFactures.filter(f => f.statut === 'payée');
  const nonPay = allFactures.filter(f => f.statut === 'non payée');
  const retard = allFactures.filter(f => f.statut === 'en retard');
  const rev    = payees.reduce((s, f) => s + parseFloat(f.montant || 0), 0);
  const npSum  = nonPay.reduce((s, f) => s + parseFloat(f.montant || 0), 0);

  document.getElementById('stat-rev').textContent    = rev.toFixed(2) + ' DH';
  document.getElementById('stat-p').textContent      = payees.length;
  document.getElementById('stat-np').textContent     = nonPay.length;
  document.getElementById('stat-r').textContent      = retard.length;
  document.getElementById('stat-np-sub').textContent = 'Montant : ' + npSum.toFixed(2) + ' DH';
  document.getElementById('stat-badge-rev').textContent = payees.length;
  document.getElementById('stat-badge-p').textContent   = payees.length;
  document.getElementById('stat-badge-np').textContent  = nonPay.length;
  document.getElementById('stat-badge-r').textContent   = retard.length;
}

// ── RENDER TABLE ─────────────────────────────────────────────
function renderTable() {
  const search = document.getElementById('searchInput').value.toLowerCase();
  const fStat  = document.getElementById('filterStatut').value;

  const filtered = allFactures.filter(f => {
    const ms = !search ||
      (f.id_facture          || '').toLowerCase().includes(search) ||
      (f.patient_nom         || '').toLowerCase().includes(search) ||
      (f.patient_prenom      || '').toLowerCase().includes(search) ||
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
    tbody.innerHTML = '<tr><td colspan="8" class="no-data">Aucune facture trouvée.</td></tr>';
    return;
  }

  const sMap = {
    'payée':     ['badge-payee',   'Payée'],
    'non payée': ['badge-nonpaye', 'Non payée'],
    'en retard': ['badge-retard',  'En retard']
  };

  tbody.innerHTML = slice.map(f => {
    const name    = f.patient_nom_complet ||
                    [f.patient_nom, f.patient_prenom].filter(Boolean).join(' ') ||
                    f.id_patient || '—';
    const type    = f.nom_type || f.id_type_paiement || '—';
    const res     = f.ressource_assignee || '—';
    const date    = f.date_facture ? String(f.date_facture).split(' ')[0] : '—';
    const [sCls, sLabel] = sMap[f.statut] || ['badge-nonpaye', f.statut || '—'];
    const montant = parseFloat(f.montant || 0).toFixed(2);

    return `<tr>
      <td class="id-cell">${esc(f.id_facture)}</td>
      <td>${esc(name)}</td>
      <td>${esc(type)}</td>
      <td class="amount-cell">${montant} DH</td>
      <td>${esc(res)}</td>
      <td>${esc(date)}</td>
      <td><span class="badge ${sCls}">${esc(sLabel)}</span></td>
      <td>
        <div class="actions-cell">
          <button class="icon-btn" onclick="editFacture('${esc(f.id_facture)}')" title="Modifier">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
              <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
            </svg>
          </button>
          <button class="icon-btn del" onclick="deleteFacture('${esc(f.id_facture)}')" title="Supprimer">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <polyline points="3 6 5 6 21 6"/>
              <path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/>
              <path d="M10 11v6M14 11v6M9 6V4h6v2"/>
            </svg>
          </button>
        </div>
      </td>
    </tr>`;
  }).join('');
}

function prevPage() {
  if (currentPage > 1) { currentPage--; renderTable(); }
}
function nextPage() {
  const s = document.getElementById('searchInput').value.toLowerCase();
  const f = document.getElementById('filterStatut').value;
  const total = allFactures.filter(x =>
    (!s || (x.id_facture||'').toLowerCase().includes(s)
        || (x.patient_nom||'').toLowerCase().includes(s)
        || (x.nom_type||'').toLowerCase().includes(s))
    && (!f || x.statut === f)
  ).length;
  if (currentPage < Math.ceil(total / PAGE_SIZE)) { currentPage++; renderTable(); }
}

// ── LOAD PATIENTS (pour dropdown) ───────────────────────────
async function loadPatients() {
  try {
    const r   = await postData(CRUD_FACTURE, { action: 'getPatients' });
    const sel = document.getElementById('f_patient');
    if (r.success && r.data.length > 0) {
      sel.innerHTML = '<option value="">Sélectionner un patient...</option>';
      r.data.forEach(p => {
        const opt       = document.createElement('option');
        opt.value       = p.id_user;
        const label     = [p.nom, p.prenom].filter(Boolean).join(' ');
        opt.textContent = label ? `${label} (${p.id_user})` : p.id_user;
        sel.appendChild(opt);
      });
    } else {
      sel.innerHTML = '<option value="">Aucun patient trouvé</option>';
    }
  } catch (e) {
    document.getElementById('f_patient').innerHTML = '<option value="">Erreur chargement patients</option>';
  }
}

// ── CONTRÔLE DE SAISIE — FACTURE ─────────────────────────────
function validateFacture() {
  let ok = true;

  // 1️⃣ Get inputs (DOM)
  const inputId      = document.getElementById('f_id');
  const inputPatient = document.getElementById('f_patient');
  const inputType    = document.getElementById('f_type');
  const inputDate    = document.getElementById('f_date');
  const inputStatut  = document.getElementById('f_statut');

  clearErrors(['f_id','f_patient','f_type','f_date','f_statut']);
  hide('msg_form_global');
  hide('msg_form_success');

  // 3️⃣ Check value (if / conditions)
  if (inputId.value.trim() === '') {
    showFieldError('f_id', "L'ID facture est obligatoire !"); ok = false;
  } else if (inputId.value.trim().length > 8) {
    showFieldError('f_id', "Maximum 8 caractères !"); ok = false;
  } else if (/[^a-zA-Z0-9\-_]/.test(inputId.value.trim())) {
    showFieldError('f_id', "Lettres, chiffres, - ou _ uniquement !"); ok = false;
  }

  if (!inputPatient.value) {
    showFieldError('f_patient', "Veuillez sélectionner un patient !"); ok = false;
  }
  if (!inputType.value) {
    showFieldError('f_type', "Veuillez sélectionner un type de paiement !"); ok = false;
  }
  if (!inputDate.value) {
    showFieldError('f_date', "La date est obligatoire !"); ok = false;
  } else {
    const chosen = new Date(inputDate.value);
    const today  = new Date(); today.setHours(0,0,0,0);
    if (chosen > today) {
      showFieldError('f_date', "La date ne peut pas être dans le futur !"); ok = false;
    }
  }
  if (!inputStatut.value) {
    showFieldError('f_statut', "Veuillez sélectionner un statut !"); ok = false;
  }

  return ok;
}

// ── SUBMIT FACTURE (add ou update) ──────────────────────────
async function submitFacture() {
  // 4️⃣ Stop if wrong
  if (!validateFacture()) return;

  const sel     = document.getElementById('f_type');
  const opt     = sel.options[sel.selectedIndex];
  const montant = opt && opt.dataset.montant ? opt.dataset.montant : 0;

  const data = {
    action:             editingId ? 'update' : 'add',
    id_facture:         document.getElementById('f_id').value.trim(),
    montant,
    statut:             document.getElementById('f_statut').value,
    date_facture:       document.getElementById('f_date').value,
    id_patient:         document.getElementById('f_patient').value,
    id_rdv:             '',
    id_type_paiement:   document.getElementById('f_type').value,
    id_ligneOrd:        '',
    ressource_assignee: document.getElementById('f_ressource').value.trim()
  };

  try {
    const r = await postData(CRUD_FACTURE, data);
    if (r.success) {
      // 5️⃣ Show message (DOM)
      const s = document.getElementById('msg_form_success');
      s.textContent = r.message; s.style.display = 'block';
      showToast(r.message, 'success');
      resetForm();
      loadFactures();
    } else {
      const g = document.getElementById('msg_form_global');
      g.textContent = r.error || "Erreur lors de l'enregistrement.";
      g.style.display = 'block';
    }
  } catch (e) {
    const g = document.getElementById('msg_form_global');
    g.textContent = 'Erreur réseau.'; g.style.display = 'block';
  }
}

// ── EDIT FACTURE ─────────────────────────────────────────────
function editFacture(id) {
  const f = allFactures.find(x => x.id_facture === id);
  if (!f) return;
  editingId = id;
  document.getElementById('formTitle').textContent      = 'Modifier Facture';
  document.getElementById('btnSubmitLabel').textContent = 'Enregistrer';
  document.getElementById('f_id').value                 = f.id_facture;
  document.getElementById('f_id').disabled              = true;
  document.getElementById('f_patient').value            = f.id_patient       || '';
  document.getElementById('f_type').value               = f.id_type_paiement || '';
  autoMontant();
  document.getElementById('f_ressource').value          = f.ressource_assignee || '';
  document.getElementById('f_date').value               = f.date_facture ? String(f.date_facture).split(' ')[0] : '';
  document.getElementById('f_statut').value             = f.statut || '';
  document.querySelector('.right-panel').scrollIntoView({ behavior: 'smooth' });
}

// ── DELETE FACTURE ───────────────────────────────────────────
async function deleteFacture(id) {
  if (!confirm(`Supprimer la facture "${id}" ? Cette action est irréversible.`)) return;
  try {
    const r = await postData(CRUD_FACTURE, { action: 'delete', id_facture: id });
    if (r.success) { showToast(r.message, 'success'); loadFactures(); }
    else showToast(r.error || 'Erreur suppression', 'error');
  } catch (e) { showToast('Erreur réseau', 'error'); }
}

// ── RESET FORM ───────────────────────────────────────────────
function resetForm() {
  editingId = null;
  document.getElementById('formTitle').textContent      = 'Nouvelle Facture';
  document.getElementById('btnSubmitLabel').textContent = 'Ajouter Facture';
  document.getElementById('f_id').value      = '';
  document.getElementById('f_id').disabled   = false;
  document.getElementById('f_patient').value = '';
  document.getElementById('f_type').value    = '';
  document.getElementById('f_montant').value = '';
  document.getElementById('f_ressource').value = '';
  document.getElementById('f_date').value    = new Date().toISOString().split('T')[0];
  document.getElementById('f_statut').value  = '';
  clearErrors(['f_id','f_patient','f_type','f_date','f_statut']);
  hide('msg_form_global');
  hide('msg_form_success');
}

// ── AUTO MONTANT ─────────────────────────────────────────────
function autoMontant() {
  const sel = document.getElementById('f_type');
  const opt = sel.options[sel.selectedIndex];
  document.getElementById('f_montant').value =
    opt && opt.dataset.montant ? parseFloat(opt.dataset.montant).toFixed(2) + ' DH' : '';
}

// ═══════════════════════════════════════════════════════════
//  TYPE PAIEMENT — CRUD
// ═══════════════════════════════════════════════════════════

// ── LOAD TYPES ───────────────────────────────────────────────
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

function renderTypeList() {
  const container = document.getElementById('typeList');
  if (!allTypes.length) {
    container.innerHTML = '<div style="color:var(--muted);font-size:.8rem;padding:4px 0">Aucun type enregistré.</div>';
    return;
  }
  container.innerHTML = allTypes.map(t => `
    <div class="type-row">
      <span class="type-name">${esc(t.nom_type)}</span>
      <span class="type-amount">${parseFloat(t.montant).toFixed(2)} DH</span>
      <button class="type-edit" onclick="editType('${esc(t.id_type)}')" title="Modifier">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
          <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
        </svg>
      </button>
      <button class="type-del" onclick="deleteType('${esc(t.id_type)}')" title="Supprimer">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <polyline points="3 6 5 6 21 6"/>
          <path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/>
          <path d="M10 11v6M14 11v6M9 6V4h6v2"/></svg>
      </button>
    </div>`).join('');
}

function syncTypeDropdown() {
  const sel = document.getElementById('f_type');
  const cur = sel.value;
  sel.innerHTML = '<option value="">Sélectionner un type...</option>';
  allTypes.forEach(t => {
    const opt           = document.createElement('option');
    opt.value           = t.id_type;
    opt.dataset.montant = t.montant;
    opt.textContent     = `${t.nom_type} — ${parseFloat(t.montant).toFixed(2)} DH`;
    sel.appendChild(opt);
  });
  if (cur) sel.value = cur;
}

// ── CONTRÔLE DE SAISIE — TYPE PAIEMENT ──────────────────────
function validateType() {
  let ok = true;
  clearErrors(['t_id','t_nom','t_montant']);
  hide('msg_type_global'); hide('msg_type_success');

  // 1️⃣ Get inputs (DOM)
  const inputId      = document.getElementById('t_id');
  const inputNom     = document.getElementById('t_nom');
  const inputMontant = document.getElementById('t_montant');

  // 3️⃣ Check value
  if (inputId.value.trim() === '') {
    showFieldError('t_id', "L'ID type est obligatoire !"); ok = false;
  } else if (inputId.value.trim().length > 8) {
    showFieldError('t_id', "Maximum 8 caractères !"); ok = false;
  } else if (/[^a-zA-Z0-9\-_]/.test(inputId.value.trim())) {
    showFieldError('t_id', "Pas de symboles spéciaux !"); ok = false;
  }
  if (inputNom.value.trim() === '') {
    showFieldError('t_nom', "Le nom est obligatoire !"); ok = false;
  }
  if (inputMontant.value === '' || isNaN(parseFloat(inputMontant.value))) {
    showFieldError('t_montant', "Montant invalide !"); ok = false;
  } else if (parseFloat(inputMontant.value) < 0) {
    showFieldError('t_montant', "Le montant doit être ≥ 0 !"); ok = false;
  }
  return ok;
}

// ── MODAL TYPE PAIEMENT ──────────────────────────────────────
function openTypeModal(t) {
  editingTypeId = t ? t.id_type : null;
  document.getElementById('modalTypeTitle').textContent = t ? 'Modifier Type de Paiement' : 'Nouveau Type de Paiement';
  document.getElementById('btnTypeLabel').textContent   = t ? 'Enregistrer' : 'Ajouter Type';
  document.getElementById('t_id').value      = t ? t.id_type     : '';
  document.getElementById('t_id').disabled   = !!t;
  document.getElementById('t_nom').value     = t ? t.nom_type    : '';
  document.getElementById('t_desc').value    = t ? (t.description || '') : '';
  document.getElementById('t_montant').value = t ? t.montant     : '';
  clearErrors(['t_id','t_nom','t_montant']);
  hide('msg_type_global'); hide('msg_type_success');
  document.getElementById('typeModal').classList.add('open');
}

function closeTypeModal() {
  document.getElementById('typeModal').classList.remove('open');
  editingTypeId = null;
}

// ── SUBMIT TYPE ──────────────────────────────────────────────
async function submitType() {
  // 4️⃣ Stop if wrong
  if (!validateType()) return;
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
      // 5️⃣ Show message (DOM)
      showToast(r.message || 'Succès !', 'success');
      closeTypeModal();
      loadTypes();
    } else {
      const g = document.getElementById('msg_type_global');
      g.textContent = r.error || 'Erreur.'; g.style.display = 'block';
    }
  } catch (e) {
    const g = document.getElementById('msg_type_global');
    g.textContent = 'Erreur réseau.'; g.style.display = 'block';
  }
}

function editType(id) {
  const t = allTypes.find(x => x.id_type === id);
  if (t) openTypeModal(t);
}

async function deleteType(id) {
  if (!confirm(`Supprimer le type "${id}" ?`)) return;
  try {
    const r = await postData(CRUD_TYPE, { action: 'delete', id_type: id });
    if (r.success) { showToast(r.message, 'success'); loadTypes(); }
    else showToast(r.error || 'Erreur suppression', 'error');
  } catch (e) { showToast('Erreur réseau', 'error'); }
}

// ── EXPORT CSV ───────────────────────────────────────────────
function exportCSV() {
  const headers = ['ID Facture','Patient','Type Paiement','Montant','Ressource','Date','Statut'];
  const rows = allFactures.map(f => [
    f.id_facture,
    f.patient_nom_complet || [f.patient_nom, f.patient_prenom].filter(Boolean).join(' ') || f.id_patient,
    f.nom_type || f.id_type_paiement,
    f.montant,
    f.ressource_assignee || '',
    (f.date_facture || '').split(' ')[0],
    f.statut
  ]);
  const csv  = [headers, ...rows]
    .map(r => r.map(c => `"${String(c||'').replace(/"/g,'""')}"`).join(','))
    .join('\n');
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
  const a    = document.createElement('a');
  a.href = URL.createObjectURL(blob); a.download = 'factures.csv'; a.click();
}

// ═══════════════════════════════════════════════════════════
//  HELPERS GÉNÉRAUX
// ═══════════════════════════════════════════════════════════

// Échappe les caractères HTML pour éviter les injections XSS
function esc(s) {
  return String(s || '')
    .replace(/&/g, '&amp;').replace(/</g, '&lt;')
    .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

// Affiche un message d'erreur sous un champ
function showFieldError(fieldId, msg) {
  const el    = document.getElementById(fieldId);
  const msgEl = document.getElementById('msg_' + fieldId);
  if (el)    el.classList.add('error');
  // 5️⃣ Show message (DOM)
  if (msgEl) { msgEl.textContent = msg; msgEl.classList.add('visible'); }
}

// Efface les erreurs d'une liste de champs
function clearErrors(ids) {
  ids.forEach(id => {
    const el    = document.getElementById(id);
    const msgEl = document.getElementById('msg_' + id);
    if (el)    el.classList.remove('error');
    if (msgEl) { msgEl.textContent = ''; msgEl.classList.remove('visible'); }
  });
}

// Cache un élément par son id
function hide(id) {
  const el = document.getElementById(id);
  if (el) el.style.display = 'none';
}

// Ferme le modal si on clique sur le fond sombre
document.getElementById('typeModal').addEventListener('click', function(e) {
  if (e.target === this) closeTypeModal();
});
