
const CRUD_ADMISSION = '../../controleur/backoffice/admission_crud.php';


// ── VARIABLES D'ÉTAT (STATE) ─────────────────────────────────────────────────
// Ces variables "mémorisent" l'état courant de la page.

let allAdmissions = [];   // Tableau qui contient TOUTES les admissions chargées depuis la BDD
let editingId     = null; // Si on est en train de modifier une admission, on stocke son ID ici. Sinon null.
let currentPage   = 1;    // Page courante pour la pagination du tableau
const PAGE_SIZE   = 8;    // Nombre de lignes affichées par page dans le tableau


// ── INITIALISATION ────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', () => {
  // Pré-remplir le champ date avec la date d'aujourd'hui (format YYYY-MM-DD)
  document.getElementById('a_date').value = new Date().toISOString().split('T')[0];

  
  testConnection();
  loadSallesDropdown();
});




async function testConnection() {
  const banner = document.getElementById('connBanner');
  try {
    // fetch() envoie une requête HTTP GET vers le PHP avec action=ping
    const res = await fetch(CRUD_ADMISSION + '?action=ping');
    const r   = await res.json(); // On transforme la réponse JSON en objet JS

    if (r.success) {
      // Connexion OK : bannière verte + chargement des données
      banner.className = 'conn-banner ok';
      banner.innerHTML = `<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
        <span>Connecté à la base <strong>jumeaunum</strong> avec succès ✓</span>`;
      loadAdmissions(); // Charger le tableau des admissions
      loadTickets();    // Charger le dropdown des tickets disponibles
    } else {
      showConnError(r.error);
    }
  } catch (e) {
    // catch(e) : si fetch() échoue complètement (serveur éteint, mauvais chemin...)
    showConnError('Impossible de joindre le serveur PHP. Vérifiez que :<br>'
      + '<strong>1.</strong> XAMPP est démarré (Apache + MySQL)<br>'
      + '<strong>2.</strong> Vous ouvrez la page via <code>localhost</code> et non depuis le système de fichiers<br>'
      + '<strong>3.</strong> Le fichier est bien dans <code>htdocs/gestion_des_admission/</code>');
  }
}

// Aff l'erreur de connexion avec le message passé en paramètre
function showConnError(msg) {
  const banner = document.getElementById('connBanner');
  banner.className = 'conn-banner error';
  banner.innerHTML = `<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M15 9l-6 6M9 9l6 6"/></svg>
    <span>${msg}</span>`;
  document.getElementById('tableBody').innerHTML =
    `<tr><td colspan="5" class="no-data" style="color:var(--red)">En attente de connexion à la base de données.</td></tr>`;
}


// ── TOAST (NOTIFICATION) ──────────────────────────────────────────────────────
// Affiche une petite notification en bas à droite de l'écran pendant 3,2 secondes.
// type peut être 'success' (vert) ou 'error' (rouge).
function showToast(msg, type = 'success') {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.className   = 'toast ' + type + ' show'; // Ajoute la classe CSS pour l'animation d'apparition
  setTimeout(() => { t.className = 'toast'; }, 3200); // Après 3,2s : retire la classe → disparaît
}

async function postData(url, data) {
  const fd = new FormData(); 
  for (const [k, v] of Object.entries(data)) fd.append(k, v ?? ''); 
  const res = await fetch(url, { method: 'POST', body: fd });
  return res.json(); 
}



async function loadAdmissions() {
  try {
    const r = await postData(CRUD_ADMISSION, { action: 'getAll' });
    if (r.success) {
      allAdmissions = r.data; 
      updateStats();          
      renderTable();          
    } else {
      document.getElementById('tableBody').innerHTML =
        `<tr><td colspan="5" class="no-data" style="color:var(--red)">Erreur : ${r.error}</td></tr>`;
    }
  } catch (e) {
    document.getElementById('tableBody').innerHTML =
      `<tr><td colspan="5" class="no-data" style="color:var(--red)">Erreur réseau.</td></tr>`;
  }
}



function updateStats() {
  const total      = allAdmissions.length;
  const urgences   = allAdmissions.filter(a => a.mode_entree === 'urgence').length;
  const normales   = allAdmissions.filter(a => a.mode_entree === 'normal').length;
  const transferts = allAdmissions.filter(a => a.mode_entree === 'transfert').length;

 
  document.getElementById('stat-total').textContent      = total;
  document.getElementById('stat-urgence').textContent    = urgences;
  document.getElementById('stat-normal').textContent     = normales;
  document.getElementById('stat-transfert').textContent  = transferts;

 
  document.getElementById('stat-badge-total').textContent      = total;
  document.getElementById('stat-badge-urgence').textContent    = urgences;
  document.getElementById('stat-badge-normal').textContent     = normales;
  document.getElementById('stat-badge-transfert').textContent  = transferts;
}



// Filtre allAdmissions selon la recherche + le filtre de mode,

function renderTable() {
  const search = document.getElementById('searchInput').value.toLowerCase();
  const fMode  = document.getElementById('filterMode').value;

  // filter() : garde uniquement les admissions qui correspondent à la recherche ET au filtre
  const filtered = allAdmissions.filter(a => {
    const ms = !search ||
      (a.id_admission || '').toLowerCase().includes(search) ||
      (a.id_ticket    || '').toLowerCase().includes(search) ||
      (a.mode_entree  || '').toLowerCase().includes(search);
    return ms && (!fMode || a.mode_entree === fMode);
  });

  // Calcul de la pagination
  const total = filtered.length;
  const pages = Math.max(1, Math.ceil(total / PAGE_SIZE)); 
  if (currentPage > pages) currentPage = pages;            

 
  const slice = filtered.slice((currentPage - 1) * PAGE_SIZE, currentPage * PAGE_SIZE);

  // Mise à jour du texte de pagination
  document.getElementById('paginCount').textContent = `Affichage de ${slice.length} sur ${total} admission(s)`;
  document.getElementById('pageInfo').textContent   = `${currentPage} / ${pages}`;

  const tbody = document.getElementById('tableBody');

  // Si aucun résultat après filtrage → message "aucune admission"
  if (!slice.length) {
    tbody.innerHTML = '<tr><td colspan="5" class="no-data">Aucune admission trouvée.</td></tr>';
    return;
  }

  
  const modeMap = {
    'urgence':   ['badge-urgence',   'Urgence'],
    'normal':    ['badge-normal',    'Normal'],
    'transfert': ['badge-transfert', 'Transfert'],
    'autre':     ['badge-autre',     'Autre']
  };

  
  tbody.innerHTML = slice.map(a => {
    const date = a.date_arrive_relle ? String(a.date_arrive_relle).split(' ')[0] : '—';
    const [mCls, mLabel] = modeMap[a.mode_entree] || ['badge-autre', a.mode_entree || '—'];

    return `<tr>
      <td class="id-cell">${esc(a.id_admission)}</td>
      <td>${esc(date)}</td>
      <td><span class="badge ${mCls}">${esc(mLabel)}</span></td>
      <td class="id-cell">${esc(a.id_ticket)}</td>
      <td>
        <div class="actions-cell">
          <button class="icon-btn" onclick="editAdmission('${esc(a.id_admission)}')" title="Modifier">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
          </button>
          <button class="icon-btn del" onclick="deleteAdmission('${esc(a.id_admission)}')" title="Supprimer">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><path d="M10 11v6M14 11v6M9 6V4h6v2"/></svg>
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
  const s     = document.getElementById('searchInput').value.toLowerCase();
  const f     = document.getElementById('filterMode').value;
  const total = allAdmissions.filter(a =>
    (!s || (a.id_admission||'').toLowerCase().includes(s) ||
           (a.id_ticket||'').toLowerCase().includes(s)    ||
           (a.mode_entree||'').toLowerCase().includes(s))
    && (!f || a.mode_entree === f)
  ).length;
  if (currentPage < Math.ceil(total / PAGE_SIZE)) { currentPage++; renderTable(); }
}


// ── CHARGER LES TICKETS DISPONIBLES ──────────────────────────────────────────

async function loadTickets() {
  try {
    const r   = await postData(CRUD_ADMISSION, { action: 'getTickets' });
    const sel = document.getElementById('a_ticket');
    if (r.success && r.data.length > 0) {
      sel.innerHTML = '<option value="">Sélectionner un ticket...</option>';
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

// ── CHARGER LES SALLES DISPONIBLES pour le formulaire admission ───────────────
async function loadSallesDropdown() {
  try {
    const r = await postData(CRUD_SALLE, { action: 'getAll' });
    const sel = document.getElementById('a_salle');
    if (!sel) return;
    sel.innerHTML = '<option value="">-- Sélectionner une salle --</option>';
    if (r.success && r.data.length > 0) {
      r.data.forEach(s => {
        const opt = document.createElement('option');
        opt.value = s.id_salle;
        opt.textContent = s.numero + ' — ' + s.statut;
        sel.appendChild(opt);
      });
    }
  } catch (e) { console.error('Erreur chargement salles:', e); }
}

// Affiche ou cache la boîte d'info bleue quand un ticket est sélectionné dans le dropdown
function showTicketInfo() {
  const sel  = document.getElementById('a_ticket');
  const info = document.getElementById('ticketInfo');
  info.className = sel.value ? 'ticket-info visible' : 'ticket-info';
}


// ── VALIDATION DU FORMULAIRE ──────────────────────────────────────────────────

function validateAdmission() {
  let ok = true;

  // Récupération des éléments du formulaire via leur id 
  const inputId   = document.getElementById('a_id');
  const inputDate = document.getElementById('a_date');
  const inputMode = document.getElementById('a_mode');
  const inputTick = document.getElementById('a_ticket');

  // On efface les éventuelles erreurs précédentes avant de revalider
  clearErrors(['a_id', 'a_date', 'a_mode', 'a_ticket', 'a_salle']);
  hide('msg_form_global');
  hide('msg_form_success');

 
  if (inputId.value.trim() === '') {
    showFieldError('a_id', "L'ID admission est obligatoire !"); ok = false;
  } else if (inputId.value.trim().length > 8) {
    showFieldError('a_id', "Maximum 8 caractères !"); ok = false;
  } else if (/[^a-zA-Z0-9\-_]/.test(inputId.value.trim())) {
    // Regex : refuse tout caractère qui n'est pas lettre, chiffre, tiret ou underscore
    showFieldError('a_id', "Pas de symboles spéciaux (lettres, chiffres, - ou _ uniquement) !"); ok = false;
  }

  
  if (!inputDate.value) {
    showFieldError('a_date', "La date d'arrivée est obligatoire !"); ok = false;
  } else {
    const chosen = new Date(inputDate.value);
    const today  = new Date();
    today.setHours(0, 0, 0, 0); // On compare seulement les dates, pas les heures
    if (chosen > today) {
      showFieldError('a_date', "La date ne peut pas être dans le futur !"); ok = false;
    }
  }

  // --- Validation du mode d'entrée ---
  if (!inputMode.value) {
    showFieldError('a_mode', "Veuillez sélectionner un mode d'entrée !"); ok = false;
  }

  // --- Validation du ticket ---
  if (!inputTick.value) {
    showFieldError('a_ticket', "Veuillez sélectionner un ticket !"); ok = false;
  }

  // --- Validation de la salle ---
  const inputSalle = document.getElementById('a_salle');
  if (inputSalle && inputSalle.selectedIndex <= 0) {
    showFieldError('a_salle', "Veuillez sélectionner une salle !"); ok = false;
  }

  return ok; 
}



// Appelée par le bouton "Ajouter Admission" / "Enregistrer" dans index.html.
// Si editingId est null → on fait un INSERT (add). Sinon → UPDATE (update).
async function submitAdmission() {
  // On stopp immédiatement si la validation échoue
  if (!validateAdmission()) return;

  const data = {
    action:            editingId ? 'update' : 'add', // Détermine l'action PHP selon le mode
    id_admission:      document.getElementById('a_id').value.trim(),
    date_arrive_relle: document.getElementById('a_date').value,
    mode_entree:       document.getElementById('a_mode').value,
    id_ticket:         document.getElementById('a_ticket').value
  };

  try {
    const r = await postData(CRUD_ADMISSION, data);
    if (r.success) {
      // Afficher le message de succès dans le formulaire
      const s = document.getElementById('msg_form_success');
      s.textContent = r.message;
      s.style.display = 'block';

      showToast(r.message, 'success'); // Notification toast verte
      resetForm();       // Vider le formulaire
      loadAdmissions();  // Recharger le tableau
      loadTickets();     // Recharger le dropdown (le ticket utilisé doit disparaître)
    } else {
      // Afficher le message d'erreur retourné par le PHP
      const g = document.getElementById('msg_form_global');
      g.textContent = r.error || "Erreur lors de l'enregistrement.";
      g.style.display = 'block';
    }
  } catch (e) {
    const g = document.getElementById('msg_form_global');
    g.textContent = 'Erreur réseau.';
    g.style.display = 'block';
  }
}


// ── PRÉREMPLIR LE FORMULAIRE POUR MODIFICATION ───────────────────────────────

async function editAdmission(id) {
  const a = allAdmissions.find(x => x.id_admission === id); // Cherche dans le tableau local
  if (!a) return;

  editingId = id; 

  document.getElementById('formTitle').textContent      = 'Modifier Admission';
  document.getElementById('btnSubmitLabel').textContent = 'Enregistrer';

  // Préremplir les champs
  document.getElementById('a_id').value   = a.id_admission;
  document.getElementById('a_id').disabled = true; // L'ID ne peut pas être modifié (c'est la PK)
  document.getElementById('a_date').value = a.date_arrive_relle
    ? String(a.date_arrive_relle).split(' ')[0] : '';
  document.getElementById('a_mode').value = a.mode_entree || '';
  const selSalle = document.getElementById('a_salle');
  if (selSalle && a.id_salle) selSalle.value = a.id_salle;

  // Le ticket actuel est "utilisé" donc absent du dropdown → on l'ajoute manuellement
  const sel = document.getElementById('a_ticket');
  let found = false;
  for (const opt of sel.options) {
    if (opt.value === a.id_ticket) { found = true; break; }
  }
  if (!found && a.id_ticket) {
    const opt = document.createElement('option');
    opt.value       = a.id_ticket;
    opt.textContent = a.id_ticket + ' (actuel)';
    sel.appendChild(opt);
  }
  sel.value = a.id_ticket || '';
  showTicketInfo(); // Afficher la boîte d'info bleue

  // Scroll automatique vers le formulaire pour que l'utilisateur le voie
  document.querySelector('.right-panel').scrollIntoView({ behavior: 'smooth' });
}


// ── SUPPRIMER UNE ADMISSION ───────────────────────────────────────────────────

async function deleteAdmission(id) {
  // confirm() affiche une boîte de dialogue native du navigateur
  if (!confirm(`Supprimer l'admission "${id}" ? Le ticket associé sera remis à disponible.`)) return;

  try {
    const r = await postData(CRUD_ADMISSION, { action: 'delete', id_admission: id });
    if (r.success) {
      showToast(r.message, 'success');
      loadAdmissions(); // Recharger le tableau
      loadTickets();    // Le ticket est redevenu disponible → il réapparaît dans le dropdown
    } else {
      showToast(r.error || 'Erreur suppression', 'error');
    }
  } catch (e) {
    showToast('Erreur réseau', 'error');
  }
}


// ── RÉINITIALISER LE FORMULAIRE ───────────────────────────────────────────────

function resetForm() {
  editingId = null; // On quitte le mode édition

  document.getElementById('formTitle').textContent      = 'Nouvelle Admission';
  document.getElementById('btnSubmitLabel').textContent = 'Ajouter Admission';

  document.getElementById('a_id').value      = '';
  document.getElementById('a_id').disabled   = false; // Réactiver le champ ID
  document.getElementById('a_date').value    = new Date().toISOString().split('T')[0]; // Date du jour
  document.getElementById('a_mode').value    = '';
  document.getElementById('a_ticket').value  = '';
  const selSalle2 = document.getElementById('a_salle');
  if (selSalle2) selSalle2.value = '';
  document.getElementById('ticketInfo').className = 'ticket-info'; // Cacher la boîte info bleue

  clearErrors(['a_id', 'a_date', 'a_mode', 'a_ticket', 'a_salle']);
  hide('msg_form_global');
  hide('msg_form_success');
}


// ── EXPORT CSV ────────────────────────────────────────────────────────────────
// Génère un fichier CSV à partir de allAdmissions et le télécharge dans le navigateur.
// Aucune requête PHP nécessaire : on utilise les données déjà en mémoire.
function exportCSV() {
  const headers = ['ID Admission', 'Date Arrivée', "Mode d'Entrée", 'ID Ticket'];

  // map() : transforme chaque objet admission en tableau de valeurs
  const rows = allAdmissions.map(a => [
    a.id_admission,
    (a.date_arrive_relle || '').split(' ')[0],
    a.mode_entree,
    a.id_ticket
  ]);

  // Génération du contenu CSV : chaque valeur est entourée de guillemets (gestion des virgules)
  const csv = [headers, ...rows]
    .map(r => r.map(c => `"${String(c || '').replace(/"/g, '""')}"`).join(','))
    .join('\n');

  // Création d'un lien de téléchargement invisible et déclenchement du clic
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
  const a    = document.createElement('a');
  a.href     = URL.createObjectURL(blob);
  a.download = 'admissions.csv';
  a.click(); // Déclenche le téléchargement
}


// ── FONCTIONS UTILITAIRES (HELPERS) ──────────────────────────────────────────

function esc(s) {
  return String(s || '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

// showFieldError() : colorie le champ en rouge et affiche son message d'erreur sous lui
function showFieldError(fieldId, msg) {
  const el    = document.getElementById(fieldId);
  const msgEl = document.getElementById('msg_' + fieldId);
  if (el)    el.classList.add('error');                    // Bordure rouge sur le champ
  if (msgEl) { msgEl.textContent = msg; msgEl.classList.add('visible'); } // Texte d'erreur visible
}

// clearErrors() : supprime les bordures rouges et cache les messages d'erreur pour une liste de champs
function clearErrors(ids) {
  ids.forEach(id => {
    const el    = document.getElementById(id);
    const msgEl = document.getElementById('msg_' + id);
    if (el)    el.classList.remove('error');
    if (msgEl) { msgEl.textContent = ''; msgEl.classList.remove('visible'); }
  });
}

// hide() : cache un élément HTML par son id (display: none)
function hide(id) {
  const el = document.getElementById(id);
  if (el) el.style.display = 'none';
}


// ══════════════════════════════════════════════════════════════════════════════
// GESTION DES SALLES — CRUD COMPLET
// ══════════════════════════════════════════════════════════════════════════════

const CRUD_SALLE = '../../controleur/backoffice/salle_crud.php';

let allSalles      = [];
let salleEditingId = null;
let salleDeleteId  = null;
let sallePage      = 1;
const SALLE_PAGE_SIZE = 8;

// ── Chargement initial des salles ─────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  loadSalles();
  loadMedecinsForSalle();
});

async function loadSalles() {
  try {
    const r = await postData(CRUD_SALLE, { action: 'getAll' });
    if (r.success) {
      allSalles = r.data;
      updateSalleStats();
      renderSalles();
    } else {
      document.getElementById('salleTableBody').innerHTML =
        `<tr><td colspan="4" class="no-data" style="color:var(--red)">Erreur : ${r.error}</td></tr>`;
    }
  } catch (e) {
    document.getElementById('salleTableBody').innerHTML =
      `<tr><td colspan="4" class="no-data" style="color:var(--red)">Erreur réseau.</td></tr>`;
  }
}

// ── Stats salles ──────────────────────────────────────────────────────────────
function updateSalleStats() {
  document.getElementById('salle-stat-total').textContent =
    allSalles.length;
  document.getElementById('salle-stat-dispo').textContent =
    allSalles.filter(s => s.statut === 'disponible').length;
  document.getElementById('salle-stat-occupee').textContent =
    allSalles.filter(s => s.statut === 'occupée').length;
  document.getElementById('salle-stat-maint').textContent =
    allSalles.filter(s => s.statut === 'maintenance').length;
}

// ── Rendu du tableau des salles ───────────────────────────────────────────────
function renderSalles() {
  const search = (document.getElementById('salleSearch')?.value || '').toLowerCase();
  const fStat  = document.getElementById('salleFilterStatut')?.value || '';

  const filtered = allSalles.filter(s => {
    const ms = !search ||
      (s.id_salle || '').toLowerCase().includes(search) ||
      (s.numero     || '').toLowerCase().includes(search) ||
      (s.statut     || '').toLowerCase().includes(search);
    return ms && (!fStat || s.statut === fStat);
  });

  const total = filtered.length;
  const pages = Math.max(1, Math.ceil(total / SALLE_PAGE_SIZE));
  if (sallePage > pages) sallePage = pages;

  const slice = filtered.slice((sallePage - 1) * SALLE_PAGE_SIZE, sallePage * SALLE_PAGE_SIZE);

  document.getElementById('sallePaginCount').textContent =
    `Affichage de ${slice.length} sur ${total} salle(s)`;
  document.getElementById('sallePageInfo').textContent = `${sallePage} / ${pages}`;

  const tbody = document.getElementById('salleTableBody');

  if (!slice.length) {
    tbody.innerHTML = '<tr><td colspan="5" class="no-data">Aucune salle trouvée.</td></tr>';
    return;
  }

  const statutMap = {
    'disponible':  ['badge-disponible',  'Disponible'],
    'occupée':     ['badge-occupee',     'Occupée'],
    'maintenance': ['badge-maintenance', 'Maintenance']
  };

  tbody.innerHTML = slice.map(s => {
    const [sCls, sLabel] = statutMap[s.statut] || ['badge-autre', s.statut || '—'];
    return `<tr>
      <td class="id-cell">${esc(s.id_salle)}</td>
      <td><strong>${esc(s.numero)}</strong></td>
      <td><span class="badge ${sCls}">${esc(sLabel)}</span></td>
      <td style="font-size:.78rem">${esc(s.nom_medecin || '—')}</td>
      <td>
        <div class="actions-cell">
          <button class="icon-btn" onclick="editSalle('${esc(s.id_salle)}')" title="Modifier">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
              <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
            </svg>
          </button>
          <button class="icon-btn del" onclick="openSalleDeleteModal('${esc(s.id_salle)}')" title="Supprimer">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <polyline points="3 6 5 6 21 6"/>
              <path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/>
              <path d="M10 11v6M14 11v6M9 6V4h6v2"/>
            </svg>
          </button>
          <button class="icon-btn" onclick="toggleSalleStatut('${esc(s.id_salle)}')" title="Changer statut" style="font-size:11px;font-weight:600;width:auto;padding:0 7px">
            ⇄
          </button>
        </div>
      </td>
    </tr>`;
  }).join('');
}

function sallePrevPage() {
  if (sallePage > 1) { sallePage--; renderSalles(); }
}
function salleNextPage() {
  const total = allSalles.length;
  if (sallePage < Math.ceil(total / SALLE_PAGE_SIZE)) { sallePage++; renderSalles(); }
}

// ── Validation formulaire salle ───────────────────────────────────────────────
function validateSalle() {
  let ok = true;
  clearErrors(['s_id', 's_numero', 's_statut', 's_medecin']);
  hideSalleMsg();

  const inputId     = document.getElementById('s_id');
  const inputNumero = document.getElementById('s_numero');
  const inputStatut = document.getElementById('s_statut');

  if (!salleEditingId) {
    if (!inputId.value.trim()) {
      showFieldError('s_id', "L'ID chambre est obligatoire !"); ok = false;
    } else if (inputId.value.trim().length > 10) {
      showFieldError('s_id', "Maximum 10 caractères !"); ok = false;
    } else if (/[^a-zA-Z0-9\-_]/.test(inputId.value.trim())) {
      showFieldError('s_id', "Lettres, chiffres, - ou _ uniquement !"); ok = false;
    }
  }

  if (!inputNumero.value.trim()) {
    showFieldError('s_numero', "Le numéro est obligatoire !"); ok = false;
  } else if (inputNumero.value.trim().length > 20) {
    showFieldError('s_numero', "Maximum 20 caractères !"); ok = false;
  }

  if (!inputStatut.value) {
    showFieldError('s_statut', "Veuillez sélectionner un statut !"); ok = false;
  }

  const inputMed = document.getElementById('s_medecin');
  if (inputMed && inputMed.selectedIndex <= 0) {
    showFieldError('s_medecin', "Veuillez sélectionner un médecin !"); ok = false;
  }

  return ok;
}

// ── Soumettre (add / update) ──────────────────────────────────────────────────
async function submitSalle() {
  if (!validateSalle()) return;

  const data = {
    action:     salleEditingId ? 'update' : 'add',
    id_salle: document.getElementById('s_id').value.trim(),
    numero:     document.getElementById('s_numero').value.trim(),
    statut:     document.getElementById('s_statut').value
  };

  try {
    const r = await postData(CRUD_SALLE, data);
    if (r.success) {
      showSalleSuccess(r.message);
      showToast(r.message, 'success');
      resetSalleForm();
      loadSalles();
      loadMedecinsForSalle();
    } else {
      showSalleError(r.error || "Erreur lors de l'enregistrement.");
    }
  } catch (e) {
    showSalleError('Erreur réseau.');
  }
}

// ── Préremplir pour modification ──────────────────────────────────────────────
function editSalle(id) {
  const s = allSalles.find(x => x.id_salle === id);
  if (!s) return;

  salleEditingId = id;
  document.getElementById('salleFormTitle').textContent  = 'Modifier Salle';
  document.getElementById('btnSalleLabel').textContent   = 'Enregistrer';

  const inputId = document.getElementById('s_id');
  inputId.value    = s.id_salle;
  inputId.disabled = true;

  document.getElementById('s_numero').value = s.numero;
  document.getElementById('s_statut').value = s.statut;
  // Ajouter l'admission actuelle dans le select si elle n'y est pas
  const selMed = document.getElementById('s_medecin');
  if (selMed && s.id_medecin) {
    selMed.value = s.id_medecin;
  }

  hideSalleMsg();
  document.querySelector('.salle-zone .right-panel').scrollIntoView({ behavior: 'smooth' });
}

// ── Basculer rapidement le statut ─────────────────────────────────────────────
async function toggleSalleStatut(id) {
  const s = allSalles.find(x => x.id_salle === id);
  if (!s) return;

  const cycle = { 'disponible': 'occupée', 'occupée': 'maintenance', 'maintenance': 'disponible' };
  const next  = cycle[s.statut] || 'disponible';

  try {
    const r = await postData(CRUD_SALLE, {
      action: 'update', id_salle: id, numero: s.numero, statut: next
    });
    if (r.success) {
      showToast(`Statut → ${next}`, 'success');
      loadSalles();
    } else {
      showToast(r.error || 'Erreur', 'error');
    }
  } catch (e) {
    showToast('Erreur réseau', 'error');
  }
}

// ── Modal suppression ─────────────────────────────────────────────────────────
function openSalleDeleteModal(id) {
  salleDeleteId = id;
  document.getElementById('modalSalleId').textContent = id;
  document.getElementById('salleDeleteModal').classList.add('open');
}
function closeSalleDeleteModal() {
  salleDeleteId = null;
  document.getElementById('salleDeleteModal').classList.remove('open');
}
async function confirmDeleteSalle() {
  if (!salleDeleteId) return;
  closeSalleDeleteModal();
  try {
    const r = await postData(CRUD_SALLE, { action: 'delete', id_salle: salleDeleteId });
    if (r.success) {
      showToast(r.message, 'success');
      loadSalles();
    } else {
      showToast(r.error || 'Erreur suppression', 'error');
    }
  } catch (e) {
    showToast('Erreur réseau', 'error');
  }
}

// ── Réinitialiser le formulaire ───────────────────────────────────────────────
function resetSalleForm() {
  salleEditingId = null;
  document.getElementById('salleFormTitle').textContent = 'Nouvelle Salle';
  document.getElementById('btnSalleLabel').textContent  = '+ Ajouter Salle';
  document.getElementById('s_id').value      = '';
  document.getElementById('s_id').disabled   = false;
  document.getElementById('s_numero').value  = '';
  document.getElementById('s_statut').value  = '';
  const selMed2 = document.getElementById('s_medecin');
  if (selMed2) selMed2.value = '';
  clearErrors(['s_id', 's_numero', 's_statut', 's_medecin']);
  hideSalleMsg();
}

// ── Export CSV salles ─────────────────────────────────────────────────────────
function exportSallesCSV() {
  const headers = ['ID Chambre', 'Numéro', 'Statut'];
  const rows    = allSalles.map(s => [s.id_salle, s.numero, s.statut]);
  const csv = [headers, ...rows]
    .map(r => r.map(c => `"${String(c || '').replace(/"/g, '""')}"`).join(','))
    .join('\n');
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
  const a    = document.createElement('a');
  a.href     = URL.createObjectURL(blob);
  a.download = 'salles.csv';
  a.click();
}

// ── Helpers messages salle ────────────────────────────────────────────────────
function hideSalleMsg() {
  const g = document.getElementById('msg_salle_global');
  const s = document.getElementById('msg_salle_success');
  if (g) g.style.display = 'none';
  if (s) s.style.display = 'none';
}
function showSalleError(msg) {
  const el = document.getElementById('msg_salle_global');
  if (el) { el.textContent = msg; el.style.display = 'block'; }
}
function showSalleSuccess(msg) {
  const el = document.getElementById('msg_salle_success');
  if (el) { el.textContent = msg; el.style.display = 'block'; }
}

// Fermer le modal en cliquant sur le fond
document.addEventListener('DOMContentLoaded', () => {
  const modal = document.getElementById('salleDeleteModal');
  if (modal) {
    modal.addEventListener('click', e => {
      if (e.target === modal) closeSalleDeleteModal();
    });
  }
});
async function loadMedecinsForSalle(keepValue = '') {
  try {
    const r = await postData(CRUD_SALLE, { action: 'getMedecins' });
    const sel = document.getElementById('s_medecin');
    if (!sel) return;
    const prev = keepValue || sel.value;
    sel.innerHTML = '<option value="">-- Sélectionner un médecin --</option>';
    if (r.success && r.data.length > 0) {
      r.data.forEach(m => {
        const opt = document.createElement('option');
        opt.value = m.id_user;
        opt.textContent = m.nom_complet;
        sel.appendChild(opt);
      });
    }
    if (prev) sel.value = prev;
  } catch (e) { console.error('Erreur chargement médecins:', e); }
}


