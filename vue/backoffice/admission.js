// ═══════════════════════════════════════════════════════════════════════════
//  admission.js  —  Logique JavaScript de la page Gestion des Admissions
//
//  Ce fichier est chargé par index.html via :
//      <script src="admission.js"></script>
//
//  Il communique avec le serveur PHP via des requêtes fetch() vers :
//      ../../controleur/backoffice/admission_crud.php
// ═══════════════════════════════════════════════════════════════════════════


// ── CHEMIN VERS LE CONTRÔLEUR PHP ────────────────────────────────────────────
// On pointe vers le fichier PHP qui gère toutes les opérations base de données.
// Le chemin est relatif à l'emplacement de index.html.
const CRUD_ADMISSION = '../../controleur/backoffice/admission_crud.php';
// Chemin vers le contrôleur PHP pour les salles
const CRUD_SALLE     = '../../controleur/backoffice/salle_crud.php';


// ── VARIABLES D'ÉTAT (STATE) ─────────────────────────────────────────────────
// Ces variables "mémorisent" l'état courant de la page.

let allAdmissions = [];   // Tableau qui contient TOUTES les admissions chargées depuis la BDD
let editingId     = null; // Si on est en train de modifier une admission, on stocke son ID ici. Sinon null.
let currentPage   = 1;    // Page courante pour la pagination du tableau
const PAGE_SIZE   = 8;    // Nombre de lignes affichées par page dans le tableau


// ── INITIALISATION ────────────────────────────────────────────────────────────
// DOMContentLoaded se déclenche quand la page HTML est entièrement chargée.
// C'est le point de départ : on met la date du jour et on teste la connexion.
document.addEventListener('DOMContentLoaded', () => {
  // Pré-remplir le champ date avec la date d'aujourd'hui (format YYYY-MM-DD)
  document.getElementById('a_date').value = new Date().toISOString().split('T')[0];

  // Lancer le test de connexion à la base de données
  testConnection();
});


// ── TEST DE CONNEXION ─────────────────────────────────────────────────────────
// Envoie un ping au serveur PHP pour vérifier que Apache + MySQL tournent.
// Si ça marche → on charge les données. Sinon → on affiche une bannière rouge.
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
      loadSalles();     // Charger le tableau des salles
      loadMedecins();   // Charger le dropdown des médecins pour le formulaire salle
    } else {
      showConnError(r.error);
    }
  } catch (e) {
    // catch(e) : si fetch() échoue complètement (serveur éteint, mauvais chemin...)
    showConnError('Impossible de joindre le serveur PHP. Vérifiez que :<br>'
      + '<strong>1.</strong> XAMPP est démarré (Apache + MySQL)<br>'
      + '<strong>2.</strong> Vous ouvrez la page via <code>localhost</code> et non depuis le système de fichiers<br>'
      + '<strong>3.</strong> Le fichier est bien dans <code>htdocs/projetweb/gestion_des_admission/</code>');
  }
}

// Affiche la bannière d'erreur de connexion avec le message passé en paramètre
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


// ── HELPER : ENVOYER DES DONNÉES EN POST ─────────────────────────────────────
// Cette fonction mutualisée est utilisée par toutes les opérations CRUD.
// Elle transforme un objet JS en FormData et l'envoie en POST vers l'URL donnée.
// Retourne directement la réponse JSON parsée (objet JS).
async function postData(url, data) {
  const fd = new FormData(); // FormData = équivalent d'un formulaire HTML envoyé en POST
  for (const [k, v] of Object.entries(data)) fd.append(k, v ?? ''); // Ajoute chaque champ
  const res = await fetch(url, { method: 'POST', body: fd });
  return res.json(); // Parse et retourne la réponse JSON
}


// ── CHARGER LES ADMISSIONS DEPUIS LA BDD ─────────────────────────────────────
// Appelle le PHP avec action=getAll, récupère le tableau d'admissions,
// le stocke dans allAdmissions, puis met à jour les stats et le tableau HTML.
async function loadAdmissions() {
  try {
    const r = await postData(CRUD_ADMISSION, { action: 'getAll' });
    if (r.success) {
      allAdmissions = r.data; // On mémorise toutes les admissions dans la variable globale
      updateStats();          // Mettre à jour les 4 cartes de statistiques
      renderTable();          // Afficher les lignes dans le tableau HTML
    } else {
      document.getElementById('tableBody').innerHTML =
        `<tr><td colspan="5" class="no-data" style="color:var(--red)">Erreur : ${r.error}</td></tr>`;
    }
  } catch (e) {
    document.getElementById('tableBody').innerHTML =
      `<tr><td colspan="5" class="no-data" style="color:var(--red)">Erreur réseau.</td></tr>`;
  }
}


// ── METTRE À JOUR LES STATISTIQUES ───────────────────────────────────────────
// Parcourt allAdmissions pour compter par mode_entree,
// puis injecte les chiffres dans les 4 cartes stat en haut de page.
function updateStats() {
  const total      = allAdmissions.length;
  const urgences   = allAdmissions.filter(a => a.mode_entree === 'urgence').length;
  const normales   = allAdmissions.filter(a => a.mode_entree === 'normal').length;
  const transferts = allAdmissions.filter(a => a.mode_entree === 'transfert').length;

  // On met à jour le texte des éléments HTML par leur id
  document.getElementById('stat-total').textContent      = total;
  document.getElementById('stat-urgence').textContent    = urgences;
  document.getElementById('stat-normal').textContent     = normales;
  document.getElementById('stat-transfert').textContent  = transferts;

  // Les badges (petits cercles en haut à droite des cartes) affichent aussi les chiffres
  document.getElementById('stat-badge-total').textContent      = total;
  document.getElementById('stat-badge-urgence').textContent    = urgences;
  document.getElementById('stat-badge-normal').textContent     = normales;
  document.getElementById('stat-badge-transfert').textContent  = transferts;
}


// ── AFFICHER LE TABLEAU (RENDER) ──────────────────────────────────────────────
// Filtre allAdmissions selon la recherche + le filtre de mode,
// applique la pagination, puis génère le HTML des lignes du tableau.
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
  const pages = Math.max(1, Math.ceil(total / PAGE_SIZE)); // Nombre de pages total
  if (currentPage > pages) currentPage = pages;            // Sécurité : si on dépasse, on revient

  // slice() : découpe le tableau pour n'afficher que la page courante
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

  // Correspondance mode_entree → classe CSS du badge + texte affiché
  const modeMap = {
    'urgence':   ['badge-urgence',   'Urgence'],
    'normal':    ['badge-normal',    'Normal'],
    'transfert': ['badge-transfert', 'Transfert'],
    'autre':     ['badge-autre',     'Autre']
  };

  // map() : pour chaque admission, on génère une ligne <tr> en HTML
  // join('') : on colle tous les <tr> ensemble en une seule chaîne
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

// Boutons de navigation entre les pages du tableau
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
// Appelle le PHP avec action=getTickets.
// Le PHP renvoie UNIQUEMENT les tickets dont statut = 'non utilisé'.
// On peuple le <select id="a_ticket"> avec ces résultats.
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

// Affiche ou cache la boîte d'info bleue quand un ticket est sélectionné dans le dropdown
function showTicketInfo() {
  const sel  = document.getElementById('a_ticket');
  const info = document.getElementById('ticketInfo');
  info.className = sel.value ? 'ticket-info visible' : 'ticket-info';
}


// ── VALIDATION DU FORMULAIRE ──────────────────────────────────────────────────
// Vérifie que tous les champs obligatoires sont bien remplis et valides.
// Retourne true si tout est OK, false sinon (et affiche les messages d'erreur).
function validateAdmission() {
  let ok = true;

  // Récupération des éléments du formulaire via leur id HTML
  const inputId   = document.getElementById('a_id');
  const inputDate = document.getElementById('a_date');
  const inputMode = document.getElementById('a_mode');
  const inputTick = document.getElementById('a_ticket');

  // On efface les éventuelles erreurs précédentes avant de revalider
  clearErrors(['a_id', 'a_date', 'a_mode', 'a_ticket']);
  hide('msg_form_global');
  hide('msg_form_success');

  // --- Validation de l'ID admission ---
  if (inputId.value.trim() === '') {
    showFieldError('a_id', "L'ID admission est obligatoire !"); ok = false;
  } else if (inputId.value.trim().length > 8) {
    showFieldError('a_id', "Maximum 8 caractères !"); ok = false;
  } else if (/[^a-zA-Z0-9\-_]/.test(inputId.value.trim())) {
    // Regex : refuse tout caractère qui n'est pas lettre, chiffre, tiret ou underscore
    showFieldError('a_id', "Pas de symboles spéciaux (lettres, chiffres, - ou _ uniquement) !"); ok = false;
  }

  // --- Validation de la date ---
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

  return ok; // true = tout est valide, false = au moins une erreur
}


// ── SOUMETTRE LE FORMULAIRE (AJOUTER OU MODIFIER) ────────────────────────────
// Appelée par le bouton "Ajouter Admission" / "Enregistrer" dans index.html.
// Si editingId est null → on fait un INSERT (add). Sinon → UPDATE (update).
async function submitAdmission() {
  // On stoppe immédiatement si la validation échoue
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
// Appelée quand on clique sur le crayon d'une ligne du tableau.
// Cherche l'admission dans allAdmissions (pas besoin de requête PHP),
// remplit tous les champs, et passe en mode "édition".
async function editAdmission(id) {
  const a = allAdmissions.find(x => x.id_admission === id); // Cherche dans le tableau local
  if (!a) return;

  editingId = id; // On mémorise l'id en cours d'édition

  // Changer le titre et le label du bouton pour indiquer le mode modification
  document.getElementById('formTitle').textContent      = 'Modifier Admission';
  document.getElementById('btnSubmitLabel').textContent = 'Enregistrer';

  // Préremplir les champs
  document.getElementById('a_id').value   = a.id_admission;
  document.getElementById('a_id').disabled = true; // L'ID ne peut pas être modifié (c'est la PK)
  document.getElementById('a_date').value = a.date_arrive_relle
    ? String(a.date_arrive_relle).split(' ')[0] : '';
  document.getElementById('a_mode').value = a.mode_entree || '';

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
// Demande confirmation, puis envoie action=delete au PHP.
// Le PHP supprime l'admission ET remet le ticket à "non utilisé" automatiquement.
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
// Vide tous les champs, retire le mode édition, efface tous les messages d'erreur.
// Appelée après un ajout/modification réussi, ou par le bouton "Réinitialiser".
function resetForm() {
  editingId = null; // On quitte le mode édition

  document.getElementById('formTitle').textContent      = 'Nouvelle Admission';
  document.getElementById('btnSubmitLabel').textContent = 'Ajouter Admission';

  document.getElementById('a_id').value      = '';
  document.getElementById('a_id').disabled   = false; // Réactiver le champ ID
  document.getElementById('a_date').value    = new Date().toISOString().split('T')[0]; // Date du jour
  document.getElementById('a_mode').value    = '';
  document.getElementById('a_ticket').value  = '';
  document.getElementById('ticketInfo').className = 'ticket-info'; // Cacher la boîte info bleue

  clearErrors(['a_id', 'a_date', 'a_mode', 'a_ticket']);
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

// esc() : échappe les caractères HTML dangereux pour éviter les injections XSS
// Ex : si un ID contient "<script>", il sera affiché comme texte et non exécuté
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


// ════════════════════════════════════════════════════════════════════════════
//  SECTION SALLES
// ════════════════════════════════════════════════════════════════════════════

// État salle
let allSalles      = [];
let editingSalleId = null;
let currentPageS   = 1;
const PAGE_SIZE_S  = 8;

// ── CHARGER LES MÉDECINS pour le dropdown salle ───────────────────────────────
async function loadMedecins() {
  try {
    const r   = await fetch(CRUD_SALLE + '?action=getMedecins');
    const j   = await r.json();
    const sel = document.getElementById('s_medecin');
    if (!sel) return;
    if (j.success && j.data.length > 0) {
      sel.innerHTML = '<option value="">Sélectionner un médecin (optionnel)...</option>';
      j.data.forEach(m => {
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
    const sel = document.getElementById('s_medecin');
    if (sel) sel.innerHTML = '<option value="">Erreur chargement médecins</option>';
  }
}

// ── CHARGER LES SALLES depuis la BDD ─────────────────────────────────────────
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
    } else {
      const tb = document.getElementById('salleTableBody');
      if (tb) tb.innerHTML = `<tr><td colspan="5" class="no-data" style="color:var(--red)">Erreur : ${r.error}</td></tr>`;
    }
  } catch (e) {
    const tb = document.getElementById('salleTableBody');
    if (tb) tb.innerHTML = `<tr><td colspan="5" class="no-data" style="color:var(--red)">Erreur réseau.</td></tr>`;
  }
}

// ── STATS SALLES ──────────────────────────────────────────────────────────────
function updateStatsSalles() {
  const total  = allSalles.length;
  const dispo  = allSalles.filter(s => s.statut === 'disponible').length;
  const indispo = allSalles.filter(s => s.statut !== 'disponible').length;

  const elTotal  = document.getElementById('stat-total-salles');
  const elDispo  = document.getElementById('stat-dispo-salles');
  const elIndispo = document.getElementById('stat-indispo-salles');
  const badgeT   = document.getElementById('stat-badge-salles');

  if (elTotal)   elTotal.textContent   = total;
  if (elDispo)   elDispo.textContent   = dispo;
  if (elIndispo) elIndispo.textContent = indispo;
  if (badgeT)    badgeT.textContent    = total;
}

// ── RENDER TABLE SALLES ───────────────────────────────────────────────────────
function renderSalleTable() {
  const search = (document.getElementById('salleSearch')?.value || '').toLowerCase();
  const fStat  = document.getElementById('filterSalleStatut')?.value || '';

  const filtered = allSalles.filter(s => {
    const ms = !search ||
      String(s.id_salle).includes(search) ||
      (s.numero               || '').toLowerCase().includes(search) ||
      (s.medecin_nom_complet  || '').toLowerCase().includes(search);
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
    tbody.innerHTML = '<tr><td colspan="5" class="no-data">Aucune salle trouvée.</td></tr>';
    return;
  }

  const statusMap = {
    'disponible':     ['badge-normal',   'Disponible'],
    'non disponible': ['badge-urgence',  'Non disponible'],
  };

  tbody.innerHTML = slice.map(s => {
    const med = s.medecin_nom_complet || '—';
    const [sCls, sLabel] = statusMap[s.statut] || ['badge-autre', s.statut || '—'];
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
    (!s || String(x.id_salle).includes(s) || (x.numero||'').toLowerCase().includes(s))
    && (!f || x.statut === f)
  ).length;
  if (currentPageS < Math.ceil(total / PAGE_SIZE_S)) { currentPageS++; renderSalleTable(); }
}

// ── VALIDATION SALLE ──────────────────────────────────────────────────────────
async function validateSalle() {
  let ok = true;
  clearErrors(['s_id', 's_numero', 's_statut']);
  hide('msg_salle_global'); hide('msg_salle_success');

  const inputId     = document.getElementById('s_id');
  const inputNumero = document.getElementById('s_numero');
  const inputStatut = document.getElementById('s_statut');
  const val         = inputId.value.trim();

  // ── Validation ID salle (int, max 8 chiffres, unique) ──
  if (val === '') {
    showFieldError('s_id', "L'ID salle est obligatoire !"); ok = false;
  } else if (!/^\d+$/.test(val)) {
    showFieldError('s_id', "L'ID doit contenir uniquement des chiffres (entier) !"); ok = false;
  } else if (val.length > 8) {
    showFieldError('s_id', "Maximum 8 chiffres !"); ok = false;
  } else if (parseInt(val) <= 0) {
    showFieldError('s_id', "L'ID doit être supérieur à 0 !"); ok = false;
  } else if (!editingSalleId) {
    // Vérifier en BDD uniquement en mode ajout
    try {
      const r = await fetch(CRUD_SALLE + `?action=checkId&id=${encodeURIComponent(val)}`);
      const j = await r.json();
      if (j.success && j.exists) {
        showFieldError('s_id', `L'ID ${val} existe déjà dans la base !`); ok = false;
      }
    } catch (e) { /* le PHP double-vérifie de toute façon */ }
  }

  // ── Validation numéro (lettres et chiffres, pas de symboles) ──
  if (!inputNumero.value.trim()) {
    showFieldError('s_numero', "Le numéro est obligatoire !"); ok = false;
  } else if (/[^a-zA-Z0-9À-ÿ\s]/.test(inputNumero.value.trim())) {
    showFieldError('s_numero', "Le numéro ne doit pas contenir de caractères spéciaux !"); ok = false;
  }

  // ── Validation statut ──
  if (!inputStatut.value) {
    showFieldError('s_statut', "Veuillez sélectionner un statut !"); ok = false;
  }

  return ok;
}

// ── SUBMIT SALLE ──────────────────────────────────────────────────────────────
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
    } else {
      const g = document.getElementById('msg_salle_global');
      if (g) { g.textContent = r.error || 'Erreur.'; g.style.display = 'block'; }
    }
  } catch (e) {
    const g = document.getElementById('msg_salle_global');
    if (g) { g.textContent = 'Erreur réseau.'; g.style.display = 'block'; }
  }
}

// ── EDIT SALLE ────────────────────────────────────────────────────────────────
function editSalle(id) {
  const s = allSalles.find(x => x.id_salle == id);
  if (!s) return;
  editingSalleId = id;

  document.getElementById('salleFormTitle').textContent = 'Modifier Salle';
  document.getElementById('salleBtnLabel').textContent  = 'Enregistrer';
  document.getElementById('s_id').value       = s.id_salle;
  document.getElementById('s_id').disabled    = true;
  document.getElementById('s_numero').value   = s.numero    || '';
  document.getElementById('s_statut').value   = s.statut    || '';
  document.getElementById('s_medecin').value  = s.id_medecin || '';

  document.querySelector('.right-panel-salle')?.scrollIntoView({ behavior: 'smooth' });
}

// ── DELETE SALLE ──────────────────────────────────────────────────────────────
async function deleteSalle(id) {
  if (!confirm(`Supprimer la salle #${id} ? Cette action est irréversible.`)) return;
  const fd = new FormData();
  fd.append('action',   'delete');
  fd.append('id_salle', id);
  try {
    const res = await fetch(CRUD_SALLE, { method: 'POST', body: fd });
    const r   = await res.json();
    if (r.success) { showToast(r.message, 'success'); loadSalles(); }
    else showToast(r.error || 'Erreur suppression', 'error');
  } catch (e) { showToast('Erreur réseau', 'error'); }
}

// ── RESET SALLE FORM ──────────────────────────────────────────────────────────
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
  hide('msg_salle_global'); hide('msg_salle_success');
}

// ── EXPORT CSV SALLE ──────────────────────────────────────────────────────────
function exportSalleCSV() {
  const headers = ['ID Salle', 'Numéro', 'Statut', 'Médecin'];
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
