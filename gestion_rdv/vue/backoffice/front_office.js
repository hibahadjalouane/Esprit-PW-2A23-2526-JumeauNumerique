function bind(id, event, fn) {
  const el = document.getElementById(id);
  if (el) el.addEventListener(event, fn);
}

function showToast(message, type = "success") {
  const container = document.getElementById("toast-container");
  if (!container) return;
  const toast = document.createElement("div");
  toast.className = "toast " + type;
  toast.innerHTML = `<span>${message}</span>`;
  container.appendChild(toast);
  setTimeout(() => toast.remove(), 3000);
}

function todayStr() {
  return new Date().toISOString().split("T")[0];
}

function normaliserStatut(value) {
  const v = String(value || '').trim().toLowerCase()
    .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
    .replace(/\s+/g, '_');
  const map = {
    'confirme': 'confirme', 'confirmee': 'confirme',
    'en_attente': 'en_attente', 'attente': 'en_attente',
    'refuse': 'refuse', 'refusee': 'refuse',
    'annule': 'annule', 'annulee': 'annule',
    'a_confirmer': 'a_confirmer',
    'a_reporter': 'a_reporter', 'reporter': 'a_reporter'
  };
  return map[v] || v;
}

function escapeHtml(value) {
  return String(value ?? "")
    .replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;").replace(/'/g, "&#039;");
}

function formatRdvStatus(statut) {
  const s = String(statut || "").toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
  if (s === "confirme" || s === "confirmee")    return { text: "Confirmé",  cls: "confirme"  };
  if (["refuse","refusee","annule","annulee","rejete","rejetee"].includes(s))
                                                return { text: "Refusé",    cls: "refuse"    };
  if (s === "a_confirmer")                      return { text: "À confirmer", cls: "en-attente" };
  if (s === "a_reporter")                       return { text: "À reporter",  cls: "en-attente" };
  return { text: s.replace(/_/g, " ") || "En attente", cls: "en-attente" };
}

function formatCreneauStatus(statut) {
  const s = String(statut || "").toLowerCase();
  return s === "disponible"
    ? { text: "Disponible", cls: "available" }
    : { text: "Réservé",   cls: "reserved"  };
}

/* ── Validation formulaire RDV ────────────────────────────
   L'id_rdv est désormais généré automatiquement côté serveur
   (entier), donc le champ idRdv est supprimé du formulaire.
   On valide uniquement les champs métier.                 */
function validerFormulaireRdv() {
  return document.getElementById("idPatient").value &&
         document.getElementById("dateDemande").value &&
         document.getElementById("typeConsultation").value &&
         document.getElementById("idMedecin").value &&
         document.getElementById("idCreneau").value;
}

function validerFormulaireCreneau() {
  const d   = document.getElementById("dateCreneau").value;
  const hd  = document.getElementById("heureDebut").value;
  const hf  = document.getElementById("heureFin").value;
  const med = document.getElementById("medecinCreneau").value;
  return d && hd && hf && med && hf > hd;
}

/* ── Sélects médecins ───────────────────────────────────── */
function remplirSelectMedecins(medecins) {
  window._medecinsData = medecins;
  ["idMedecin", "medecinCreneau"].forEach(id => {
    const select = document.getElementById(id);
    if (!select) return;
    const current = select.value;
    select.innerHTML = '<option value="">— Choisir un médecin —</option>';
    medecins.forEach(m => {
      const opt = document.createElement("option");
      opt.value = m.id_user;
      opt.textContent = `${m.nom_complet} (ID : ${m.id_user})`;
      select.appendChild(opt);
    });
    if (medecins.some(m => String(m.id_user) === String(current))) select.value = current;
  });
}

function chargerMedecinsDepuisBD() {
  return fetch("creneau.php?action=medecins")
    .then(r => r.json())
    .then(res => {
      if (!res.success) throw new Error(res.message || "Erreur chargement médecins");
      remplirSelectMedecins(res.data ?? []);
    })
    .catch(err => {
      console.error(err);
      showToast("Impossible de charger les médecins", "error");
    });
}

/* ── Sélect patients ────────────────────────────────────── */
function remplirSelectPatients(patients) {
  const select = document.getElementById("idPatient");
  if (!select) return;
  const current = select.value;
  select.innerHTML = '<option value="">— Choisir un patient —</option>';
  patients.forEach(p => {
    const opt = document.createElement("option");
    opt.value = p.id_user;
    const nom = (p.nom_complet || "").trim();
    opt.textContent = `${nom || "Patient"} (ID : ${p.id_user})`;
    select.appendChild(opt);
  });
  if (patients.some(p => String(p.id_user) === String(current))) select.value = current;
}

function chargerPatientsDepuisBD() {
  return fetch("rdv.php?action=patients")
    .then(r => r.json())
    .then(res => {
      if (!res.success) throw new Error(res.message || "Erreur chargement patients");
      remplirSelectPatients(res.data ?? []);
    })
    .catch(err => {
      console.error(err);
      showToast("Impossible de charger les patients", "error");
    });
}

/* ── Chargement créneaux ────────────────────────────────── */
function chargerCreneauxDepuisBD() {
  const medecin = document.getElementById("idMedecin")?.value ?? "";
  const tbody   = document.getElementById("creneauTableBody");
  const url = medecin
    ? `creneau.php?action=charger_medecin&medecin=${encodeURIComponent(medecin)}`
    : "creneau.php?action=charger";

  fetch(url)
    .then(r => { if (!r.ok) throw new Error(`HTTP ${r.status}`); return r.json(); })
    .then(res => {
      if (!res.success) throw new Error(res.message || "Erreur chargement créneaux");
      const creneaux = res.data ?? res.creneaux ?? [];

      if (tbody) {
        tbody.innerHTML = "";
        if (!creneaux.length) {
          tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:#7d84a4;">Aucun créneau trouvé dans la base de données.</td></tr>';
        } else {
          creneaux.forEach(c => {
            const status = formatCreneauStatus(c.statut);
            const tr = document.createElement("tr");
            tr.dataset.id = c.id_creneau;
            tr.innerHTML = `
              <td>${escapeHtml(c.id_creneau)}</td>
              <td>${escapeHtml(c.date)}</td>
              <td>${escapeHtml(c.heure_debut)}</td>
              <td>${escapeHtml(c.heure_fin)}</td>
              <td><span class="status-badge ${status.cls}">${status.text}</span></td>
              <td><div class="actions">
                <i class="fa-solid fa-pen"   title="Modifier"></i>
                <i class="fa-solid fa-trash" title="Supprimer"></i>
              </div></td>`;
            tbody.appendChild(tr);
          });
        }
      }

      const select = document.getElementById("idCreneau");
      if (select) {
        select.innerHTML = '<option value="">— Choisir un créneau —</option>';
        const disponibles = creneaux.filter(c => String(c.statut).toLowerCase() === "disponible");
        disponibles.forEach(c => {
          const opt = document.createElement("option");
          opt.value = c.id_creneau;
          opt.textContent = `ID ${c.id_creneau} | ${c.date} | ${c.heure_debut}-${c.heure_fin}`;
          select.appendChild(opt);
        });
        select.disabled = !medecin || !disponibles.length;
        if (!medecin) {
          select.innerHTML = '<option value="">— Sélectionnez un médecin d\'abord —</option>';
          select.disabled = true;
        }
      }

      if (typeof appliquerFiltresCreneau === 'function') appliquerFiltresCreneau();
      if (typeof mettreAJourStats      === 'function') mettreAJourStats();
    })
    .catch(err => {
      console.error(err);
      showToast("Erreur de connexion au serveur", "error");
    });
}

/* ── Chargement RDV ─────────────────────────────────────── */
function chargerRdvDepuisBD() {
  fetch("rdv.php?action=charger")
    .then(r => { if (!r.ok) throw new Error(`HTTP ${r.status}`); return r.json(); })
    .then(res => {
      if (!res.success) throw new Error(res.message || "Erreur chargement RDV");
      const tbody = document.getElementById("rdvTableBody");
      if (!tbody) return;
      tbody.innerHTML = "";
      const rdvs = res.data ?? [];
      if (!rdvs.length) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;color:#7d84a4;">Aucun rendez-vous trouvé dans la base de données.</td></tr>';
      } else {
        rdvs.forEach(rdv => {
          const status = formatRdvStatus(rdv.statut);
          const tr = document.createElement("tr");
          tr.dataset.id = rdv.id_rdv;
          tr.innerHTML = `
            <td>${escapeHtml(rdv.id_rdv)}</td>
            <td>${escapeHtml(rdv.date_demande)}</td>
            <td>${escapeHtml(rdv.date_rdv ?? "—")}</td>
            <td>${escapeHtml(rdv.type_consultation)}</td>
            <td>${escapeHtml(rdv.nom_medecin ?? rdv.id_medecin ?? "—")}</td>
            <td><span class="status-badge ${status.cls}">${status.text}</span></td>
            <td><div class="actions">
              <i class="fa-solid fa-check"   title="Confirmer"></i>
              <i class="fa-solid fa-ban"     title="Refuser"></i>
              <i class="fa-solid fa-pen"     title="Modifier"></i>
              <i class="fa-solid fa-ellipsis" title="Supprimer"></i>
            </div></td>`;
          tbody.appendChild(tr);
        });
      }
      mettreAJourTableau();
    })
    .catch(err => {
      console.error(err);
      showToast("Erreur de connexion au serveur", "error");
    });
}

/* ── Bouton : ajouter créneau ───────────────────────────── */
bind("btnAjouterCreneau", "click", function () {
  if (!validerFormulaireCreneau()) {
    showToast("Tous les champs sont requis", "error");
    return;
  }

  const body = new URLSearchParams({
    action:  "ajouter",
    date:    document.getElementById("dateCreneau").value,
    debut:   document.getElementById("heureDebut").value,
    fin:     document.getElementById("heureFin").value,
    medecin: document.getElementById("medecinCreneau").value
  });

  fetch("creneau.php", { method: "POST", body })
    .then(r => r.json())
    .then(res => {
      if (!res.success) {
        const msgs = {
          champs_manquants: "Tous les champs sont requis",
          medecin_invalide: "Identifiant médecin invalide",
          date_invalide:    "La date ne peut pas être dans le passé",
          heure_invalide:   "L'heure de fin doit être après l'heure de début",
          creneau_existe:   "Ce créneau chevauche un créneau existant"
        };
        showToast(msgs[res.message] ?? res.message, "error");
        return;
      }
      showToast(`Créneau ajouté (ID : ${res.id}) !`);
      chargerCreneauxDepuisBD();
      chargerRdvDepuisBD();
    })
    .catch(err => { console.error(err); showToast("Erreur serveur", "error"); });
});

/* ── Bouton : ajouter RDV ───────────────────────────────── */
bind("btnAjouterRdv", "click", function () {
  if (!validerFormulaireRdv()) {
    showToast("Champs invalides", "error");
    return;
  }

  /* L'id_rdv est généré automatiquement côté serveur — pas de champ à envoyer */
  const body = new URLSearchParams({
    action:       "ajouter",
    patient:      document.getElementById("idPatient").value,
    date_demande: document.getElementById("dateDemande").value,
    type:         document.getElementById("typeConsultation").value,
    medecin:      document.getElementById("idMedecin").value,
    creneau:      document.getElementById("idCreneau").value
  });

  fetch("rdv.php", { method: "POST", body })
    .then(r => r.json())
    .then(res => {
      if (!res.success) {
        const msgs = {
          champs_manquants:        "Tous les champs sont requis",
          patient_invalide:        "Identifiant patient invalide",
          medecin_invalide:        "Identifiant médecin invalide",
          creneau_invalide:        "Identifiant créneau invalide",
          creneau_introuvable:     "Créneau introuvable",
          creneau_pris:            "Ce créneau est déjà réservé",
          patient_introuvable:     "Patient introuvable",
          medecin_introuvable:     "Médecin introuvable",
          creneau_medecin_invalide:"Ce créneau n'appartient pas au médecin choisi"
        };
        showToast(msgs[res.message] ?? res.message, "error");
        return;
      }
      showToast(`RDV ajouté (ID : ${res.id}) !`);
      chargerRdvDepuisBD();
      chargerCreneauxDepuisBD();
    })
    .catch(err => { console.error(err); showToast("Erreur serveur", "error"); });
});

/* ── Délégation de clics ─────────────────────────────────── */
document.addEventListener("click", function (e) {

  /* Remplissage médecins si vide */
  if (window._medecinsData && window._medecinsData.length > 0) {
    const selMed = document.getElementById("idMedecin");
    if (selMed && selMed.options.length <= 1) remplirSelectMedecins(window._medecinsData);
  }

  /* Supprimer créneau */
  if (e.target.closest("#creneauTableBody .fa-trash")) {
    const tr = e.target.closest("tr");
    const id = tr?.dataset.id;
    if (!id) return;
    if (!confirm("Supprimer ce créneau ?")) return;
    fetch("creneau.php", { method: "POST", body: new URLSearchParams({ action: "supprimer", id }) })
      .then(r => r.json())
      .then(res => {
        if (!res.success) {
          const msgs = { creneau_reserve: "Impossible : ce créneau est réservé", id_manquant: "ID manquant ou invalide" };
          showToast(msgs[res.message] ?? res.message, "error"); return;
        }
        showToast("Créneau supprimé");
        chargerCreneauxDepuisBD();
      });
  }

  /* Confirmer RDV */
  if (e.target.closest("#rdvTableBody .fa-check") && !e.target.closest("tr")?.classList.contains("edit")) {
    const tr = e.target.closest("tr");
    const id = tr?.dataset.id;
    if (!id) return;
    fetch("rdv.php", { method: "POST", body: new URLSearchParams({ action: "changer_statut", id, statut: "confirme" }) })
      .then(r => r.json())
      .then(res => {
        if (!res.success) { showToast(res.message, "error"); return; }
        showToast("RDV confirmé");
        chargerRdvDepuisBD(); chargerCreneauxDepuisBD();
      })
      .catch(err => { console.error(err); showToast("Erreur serveur", "error"); });
    return;
  }

  /* Refuser RDV */
  if (e.target.closest("#rdvTableBody .fa-ban")) {
    const tr = e.target.closest("tr");
    const id = tr?.dataset.id;
    if (!id) return;
    if (!confirm("Refuser ce RDV ?")) return;
    fetch("rdv.php", { method: "POST", body: new URLSearchParams({ action: "changer_statut", id, statut: "refuse" }) })
      .then(r => r.json())
      .then(res => {
        if (!res.success) { showToast(res.message, "error"); return; }
        showToast("RDV refusé");
        chargerRdvDepuisBD(); chargerCreneauxDepuisBD();
      })
      .catch(err => { console.error(err); showToast("Erreur serveur", "error"); });
    return;
  }

  /* Supprimer RDV */
  if (e.target.closest("#rdvTableBody .fa-ellipsis")) {
    const tr = e.target.closest("tr");
    const id = tr?.dataset.id;
    if (!id) return;
    if (!confirm("Supprimer ce RDV ?")) return;
    fetch("rdv.php", { method: "POST", body: new URLSearchParams({ action: "supprimer", id }) })
      .then(r => r.json())
      .then(res => {
        if (!res.success) { showToast(res.message, "error"); return; }
        showToast("RDV supprimé");
        chargerRdvDepuisBD(); chargerCreneauxDepuisBD();
      });
  }

  /* Passer en mode édition inline */
  if (e.target.closest(".fa-pen")) {
    const tr = e.target.closest("tr");
    if (!tr || tr.classList.contains("edit") || !tr.dataset.id) return;
    tr.classList.add("edit");
    const tds = tr.children;
    for (let i = 1; i < tds.length - 1; i++) {
      tds[i].innerHTML = `<input value="${escapeHtml(tds[i].textContent.trim())}">`;
    }
    tds[tds.length - 1].innerHTML =
      '<i class="fa-solid fa-check" title="Enregistrer"></i>' +
      '<i class="fa-solid fa-xmark" title="Annuler"></i>';
  }

  /* Enregistrer édition créneau */
  if (e.target.closest("#creneauTableBody .fa-check")) {
    const tr     = e.target.closest("tr");
    const id     = tr?.dataset.id;
    const inputs = tr?.querySelectorAll("input") ?? [];
    fetch("creneau.php", {
      method: "POST",
      body: new URLSearchParams({ action:"modifier", id, date:inputs[0]?.value, debut:inputs[1]?.value, fin:inputs[2]?.value })
    })
      .then(r => r.json())
      .then(res => {
        if (!res.success) { showToast(res.message, "error"); return; }
        showToast("Créneau modifié");
        chargerCreneauxDepuisBD();
      });
  }

  /* Enregistrer édition RDV */
  if (e.target.closest("#rdvTableBody .fa-check")) {
    const tr     = e.target.closest("tr");
    const id     = tr?.dataset.id;
    const inputs = tr?.querySelectorAll("input") ?? [];
    fetch("rdv.php", {
      method: "POST",
      body: new URLSearchParams({
        action: "modifier", id,
        date: inputs[0]?.value, date_rdv: inputs[1]?.value,
        type: inputs[2]?.value, statut: normaliserStatut(inputs[4]?.value)
      })
    })
      .then(r => r.json())
      .then(res => {
        if (!res.success) { showToast(res.message, "error"); return; }
        showToast("RDV modifié");
        chargerRdvDepuisBD();
      });
  }

  if (e.target.closest(".fa-xmark")) {
    chargerRdvDepuisBD();
    chargerCreneauxDepuisBD();
  }
});

/* ═══════════════════════════════════════════════════════════
   FILTRES + RECHERCHE + PAGINATION
   ═══════════════════════════════════════════════════════════ */

let pageRdv = 1, pageCreneau = 1;
const ROWS  = 4;

function normalizeText(s) {
  return String(s || "").normalize("NFD").replace(/\p{Diacritic}/gu,"").toLowerCase().trim();
}
function rowMatchesQuery(row, query) {
  return !query || normalizeText(row.textContent).includes(normalizeText(query));
}
function rowMatchesStatus(row, statusValue) {
  if (!statusValue) return true;
  const badge = row.querySelector(".status-badge");
  if (!badge) return false;
  const target = normalizeText(statusValue);
  const badgeText = normalizeText(badge.textContent);
  const equivalents = {
    'reserve':    ['reserve','reserved','reservé'],
    'disponible': ['disponible','available','libre'],
    'confirme':   ['confirme','confirmed','confirmé','valide','validé'],
    'en-attente': ['en attente','en-attente','pending','en_attente'],
    'annule':     ['annule','annulé','cancelled','canceled'],
    'refuse':     ['refuse','refusé','refusee','refusée','rejete','rejeté']
  };
  const candidates = equivalents[target] || [target];
  if (candidates.some(c => badgeText === normalizeText(c))) return true;
  return badge.classList.contains(statusValue)
      || row.classList.contains(statusValue)
      || candidates.some(c => badge.classList.contains(c));
}

function appliquerFiltresRdv() {
  const tbody  = document.getElementById("rdvTableBody");
  if (!tbody) return;
  const query  = document.getElementById("searchRdv")?.value   || "";
  const statut = document.getElementById("filterRdv")?.value   || "";
  const allRows = Array.from(tbody.querySelectorAll("tr")).filter(r => r.dataset.id);
  const visibles = allRows.filter(r => rowMatchesQuery(r, query) && rowMatchesStatus(r, statut));
  allRows.forEach(r => { r.style.display = "none"; });
  const total = visibles.length;
  const maxPage = Math.max(1, Math.ceil(total / ROWS));
  if (pageRdv > maxPage) pageRdv = maxPage;
  visibles.forEach((r, i) => {
    r.style.display = (i >= (pageRdv-1)*ROWS && i < pageRdv*ROWS) ? "" : "none";
  });
  const countEl = document.getElementById("rdvCount");
  if (countEl) countEl.textContent = total === 0
    ? "Aucun rendez-vous ne correspond"
    : `Affichage de ${total} entr${total > 1 ? "ées" : "ée"}`;
  document.querySelectorAll("#rdvSection .page-number, .page-number").forEach((btn, idx) => {
    btn.classList.toggle("active", idx + 1 === pageRdv);
  });
}

function appliquerFiltresCreneau() {
  const tbody  = document.getElementById("creneauTableBody");
  if (!tbody) return;
  const query  = document.getElementById("searchCreneau")?.value || "";
  const statut = document.getElementById("filterCreneau")?.value || "";
  const allRows = Array.from(tbody.querySelectorAll("tr")).filter(r => r.dataset.id);
  const visibles = allRows.filter(r => rowMatchesQuery(r, query) && rowMatchesStatus(r, statut));
  allRows.forEach(r => { r.style.display = "none"; });
  const total = visibles.length;
  const maxPage = Math.max(1, Math.ceil(total / ROWS));
  if (pageCreneau > maxPage) pageCreneau = maxPage;
  visibles.forEach((r, i) => {
    r.style.display = (i >= (pageCreneau-1)*ROWS && i < pageCreneau*ROWS) ? "" : "none";
  });
  const countEl = document.getElementById("creneauCount");
  if (countEl) countEl.textContent = total === 0
    ? "Aucun créneau ne correspond"
    : `Affichage de ${total} entr${total > 1 ? "ées" : "ée"}`;
}

function mettreAJourTableau() {
  appliquerFiltresRdv();
  appliquerFiltresCreneau();
  mettreAJourStats();
}

function mettreAJourStats() {
  const rdvRows     = Array.from(document.querySelectorAll("#rdvTableBody tr")).filter(r => r.dataset.id);
  const creneauRows = Array.from(document.querySelectorAll("#creneauTableBody tr")).filter(r => r.dataset.id);
  let confirmes = 0, enAttente = 0, refuses = 0;
  rdvRows.forEach(row => {
    const badge = row.querySelector(".status-badge");
    if (!badge) return;
    if (badge.classList.contains("confirme"))   confirmes++;
    else if (badge.classList.contains("refuse")) refuses++;
    else if (badge.classList.contains("en-attente")) enAttente++;
  });
  let dispos = 0;
  creneauRows.forEach(row => {
    const badge = row.querySelector(".status-badge");
    if (!badge) return;
    if (badge.classList.contains("available") || badge.classList.contains("disponible")) dispos++;
  });
  const setText = (id, value) => { const el = document.getElementById(id); if (el) el.textContent = value; };
  setText("rdvConfirmes",  confirmes);
  setText("rdvEnAttente",  enAttente);
  setText("rdvRefuses",    refuses);
  setText("creneauxDispo", dispos);
  const total = rdvRows.length;
  setText("stat-badge-confirme",  total > 0 ? Math.round((confirmes/total)*100)+"%" : "—");
  setText("stat-badge-attente",   total > 0 ? Math.round((enAttente/total)*100)+"%"  : "—");
  setText("stat-badge-refuse",    total > 0 ? Math.round((refuses/total)*100)+"%"    : "—");
  setText("stat-badge-creneaux",  creneauRows.length > 0 ? dispos+"/"+creneauRows.length : "—");
}

function brancherFiltres() {
  const searchRdv = document.getElementById("searchRdv");
  const filterRdv = document.getElementById("filterRdv");
  if (searchRdv) searchRdv.addEventListener("input",  () => { pageRdv = 1; appliquerFiltresRdv(); });
  if (filterRdv) filterRdv.addEventListener("change", () => { pageRdv = 1; appliquerFiltresRdv(); });

  const searchCre = document.getElementById("searchCreneau");
  const filterCre = document.getElementById("filterCreneau");
  if (searchCre) searchCre.addEventListener("input",  () => { pageCreneau = 1; appliquerFiltresCreneau(); });
  if (filterCre) filterCre.addEventListener("change", () => { pageCreneau = 1; appliquerFiltresCreneau(); });
}

function activerPagination() {
  document.querySelectorAll(".page-number").forEach((btn, index) => {
    btn.addEventListener("click", function () {
      pageRdv = index + 1;
      appliquerFiltresRdv();
      document.querySelectorAll(".page-number").forEach(b => b.classList.remove("active"));
      this.classList.add("active");
    });
  });
}

activerPagination();
brancherFiltres();

document.getElementById("dateDemande").min = todayStr();
document.getElementById("dateCreneau").min = todayStr();

Promise.all([chargerMedecinsDepuisBD(), chargerPatientsDepuisBD()]).finally(() => {
  chargerCreneauxDepuisBD();
  chargerRdvDepuisBD();
});

bind("idMedecin", "change", chargerCreneauxDepuisBD);


(function () {
  const ENDPOINT = "alertes.php";
  const REFRESH_MS = 30000;

  const NIVEAUX_LIB = {
    critique: "Critique",
    attention: "Attention",
    prediction: "Prédiction",
    optimisation: "Optimisation"
  };

  const $ = (id) => document.getElementById(id);

  function setScore(value) {
    const v = Math.max(0, Math.min(100, Number(value) || 0));
    const deg = (v / 100) * 360;
    const ring = $("jnScore");
    const out = $("jnScoreVal");

    if (ring) {
      ring.style.setProperty("--score-deg", deg + "deg");

      let color = "#22ef8b";
      if (v <= 70) color = "#ffb547";
      if (v <= 40) color = "#ff5a5a";

      ring.style.background =
        `conic-gradient(${color} 0deg, ${color} ${deg}deg, rgba(255,255,255,.18) ${deg}deg)`;
    }

    if (out) out.textContent = v;
  }

  function fmtDate(s) {
    if (!s) return "—";
    const d = new Date(s);
    return d.toLocaleDateString("fr-FR", {
      day: "2-digit",
      month: "2-digit",
      year: "numeric"
    });
  }

  function fmtHeure(s) {
    return s ? String(s).substring(0, 5) : "—";
  }

  function tagClass(type) {
    const t = String(type || "").toLowerCase();

    if (t.includes("urgence")) return "urgence";
    if (t.includes("suivi")) return "suivi";
    if (t.includes("special")) return "specialisee";

    return "generale";
  }

  function openAnyModale(id) {
    const modal = $(id);
    if (modal) modal.classList.add("open");
    document.body.style.overflow = "hidden";
  }

  function closeAnyModale(id) {
    const modal = $(id);
    if (modal) modal.classList.remove("open");
    document.body.style.overflow = "";
  }

  function afficherToast(message, type = "success") {
    if (typeof showToast === "function") {
      showToast(message, type);
      return;
    }

    alert(message);
  }

  function renderError(msg) {
    const grid = $("jnGrid");
    if (!grid) return;

    grid.innerHTML = `
      <div class="jn-empty" style="border-color:var(--red); color:var(--red);">
        <div><strong>Impossible de charger les alertes</strong></div>
        <div>${escapeHtml(msg)}</div>
      </div>
    `;

    const sub = $("jnSub");
    if (sub) sub.textContent = "Erreur de communication avec le serveur";
  }

  function renderAlertes(payload) {
    const grid = $("jnGrid");
    if (!grid) return;

    const c = payload.compteurs || {
      critique: 0,
      attention: 0,
      prediction: 0,
      optimisation: 0
    };

    if ($("jnCntCritique")) $("jnCntCritique").textContent = c.critique || 0;
    if ($("jnCntAttention")) $("jnCntAttention").textContent = c.attention || 0;
    if ($("jnCntPrediction")) $("jnCntPrediction").textContent = c.prediction || 0;
    if ($("jnCntOptim")) $("jnCntOptim").textContent = c.optimisation || 0;

    setScore(payload.score);

    if ($("jnSub")) {
      $("jnSub").textContent =
        `Dernière analyse à ${payload.horodate || "—"} · ${payload.total || 0} alerte(s) active(s)`;
    }

    if (!payload.alertes || payload.alertes.length === 0) {
      grid.innerHTML = `
        <div class="jn-empty">
          <div><strong>Aucune anomalie détectée.</strong></div>
          <div>Le jumeau numérique est en bonne santé.</div>
        </div>
      `;
      return;
    }

    grid.innerHTML = payload.alertes.map((a, i) => {
      const payloadAttr = a.payload
        ? `data-payload='${escapeHtml(JSON.stringify(a.payload))}'`
        : "";

      const clickable = a.payload && a.payload.type;

      return `
        <div class="jn-card ${escapeHtml(a.niveau || "")}" style="animation-delay:${i * 60}ms">
          <div class="jn-card-top">
            <span class="jn-pill ${escapeHtml(a.niveau || "")}">
              ${escapeHtml(NIVEAUX_LIB[a.niveau] || a.niveau || "")}
            </span>

            <span class="jn-cat">
              ${escapeHtml(a.categorie || "")}
            </span>
          </div>

          <div class="jn-card-title">
            ${escapeHtml(a.titre || "")}
          </div>

          <div class="jn-card-detail">
            ${escapeHtml(a.detail || "")}
          </div>

          ${
            a.action
              ? `
                <div class="jn-card-action ${clickable ? "clickable" : ""}" ${payloadAttr}>
                  → ${escapeHtml(a.action)}
                </div>
              `
              : ""
          }
        </div>
      `;
    }).join("");

    grid.querySelectorAll(".jn-card-action.clickable").forEach(el => {
      el.addEventListener("click", () => {
        let payload = {};

        try {
          payload = JSON.parse(el.dataset.payload || "{}");
        } catch (err) {
          console.error("Payload invalide", err);
          return;
        }

        switch (payload.type) {
          case "generer_creneaux":
            openModaleGeneration(payload);
            break;

          case "marquer_a_confirmer":
            openModaleConfirm(payload);
            break;

          case "regenerer_factures":
            openModaleFacture(payload);
            break;

          case "supprimer_creneaux_morts":
            openModaleDelete(payload);
            break;

          case "redistribuer_rdv":
            openModaleRedist(payload);
            break;

          case "voir_profil_medecin":
            openModaleProfil(payload);
            break;

          default:
            console.warn("Action inconnue", payload);
            afficherToast("Action inconnue", "error");
        }
      });
    });
  }

  function chargerAlertes() {
    const btn = $("jnRefresh");
    if (btn) btn.classList.add("spin");

    fetch(ENDPOINT, { cache: "no-store" })
      .then(r => {
        if (!r.ok) throw new Error("HTTP " + r.status);
        return r.json();
      })
      .then(res => {
        if (!res.success) throw new Error(res.message || "Réponse invalide");
        renderAlertes(res);
      })
      .catch(err => {
        console.error("[Jumeau Numérique]", err);
        renderError(err.message || String(err));
      })
      .finally(() => {
        if (btn) {
          setTimeout(() => btn.classList.remove("spin"), 800);
        }
      });
  }

  let currentSpecialite = null;

  function openModaleGeneration(payload) {
    currentSpecialite = payload.specialite || "—";

    if ($("jnModalSub")) {
      $("jnModalSub").textContent = `Spécialité : ${currentSpecialite}`;
    }

    if ($("jnModalInfo")) {
      $("jnModalInfo").innerHTML =
        `Le Jumeau Numérique a détecté un <strong>engorgement</strong> sur la spécialité ` +
        `<strong>« ${escapeHtml(currentSpecialite)} »</strong>. Sélectionnez un médecin et configurez une plage horaire.`;
    }

    const demain = new Date();
    demain.setDate(demain.getDate() + 1);

    if ($("jnInputDate")) {
      $("jnInputDate").value = demain.toISOString().split("T")[0];
      $("jnInputDate").min = new Date().toISOString().split("T")[0];
    }

    const select = $("jnSelectMedecin");
    if (select) {
      select.innerHTML = '<option value="">— Chargement des médecins —</option>';

      fetch(`${ENDPOINT}?action=medecins_par_specialite&specialite=${encodeURIComponent(currentSpecialite)}`)
        .then(r => r.json())
        .then(res => {
          if (!res.success) throw new Error(res.message);

          if (!res.data || res.data.length === 0) {
            select.innerHTML = '<option value="">Aucun médecin trouvé</option>';
          } else {
            select.innerHTML = '<option value="">— Sélectionner un médecin —</option>';

            res.data.forEach(m => {
              const opt = document.createElement("option");
              opt.value = m.id_user;
              opt.textContent = `${m.nom_complet} (${m.id_user})`;
              select.appendChild(opt);
            });
          }

          recalculerApercu();
        })
        .catch(err => {
          select.innerHTML = `<option value="">Erreur : ${escapeHtml(err.message)}</option>`;
        });
    }

    openAnyModale("jnModalBackdrop");
  }

  function closeModaleGeneration() {
    closeAnyModale("jnModalBackdrop");
  }

  function recalculerApercu() {
    if (!$("jnInputDebut") || !$("jnInputFin") || !$("jnInputDuree")) return;

    const debut = $("jnInputDebut").value;
    const fin = $("jnInputFin").value;
    const duree = parseInt($("jnInputDuree").value, 10);
    const pD = $("jnInputPauseDebut")?.value;
    const pF = $("jnInputPauseFin")?.value;
    const med = $("jnSelectMedecin")?.value;

    if (!debut || !fin || !duree || debut >= fin) {
      if ($("jnPreviewNum")) $("jnPreviewNum").textContent = "0";
      if ($("jnPreviewText")) $("jnPreviewText").innerHTML = "Configurez la plage horaire.";
      if ($("jnBtnConfirm")) $("jnBtnConfirm").disabled = true;
      return;
    }

    const toMin = (s) => {
      const [h, m] = s.split(":").map(Number);
      return h * 60 + m;
    };

    const debutM = toMin(debut);
    const finM = toMin(fin);
    const pDM = pD ? toMin(pD) : null;
    const pFM = pF ? toMin(pF) : null;

    let nb = 0;
    let t = debutM;

    while (t + duree <= finM) {
      const dansPause = pDM !== null && pFM !== null && t >= pDM && t < pFM;
      if (!dansPause) nb++;
      t += duree;
    }

    if ($("jnPreviewNum")) $("jnPreviewNum").textContent = nb;

    if ($("jnPreviewText")) {
      $("jnPreviewText").innerHTML =
        nb > 0
          ? `<strong>${nb} créneau(x)</strong> de ${duree} min seront générés.`
          : "Aucun créneau ne sera généré.";
    }

    if ($("jnBtnConfirm")) $("jnBtnConfirm").disabled = !med || nb === 0;

    if ($("jnBtnLabel")) {
      $("jnBtnLabel").textContent =
        nb > 0 ? `Générer ${nb} créneau${nb > 1 ? "x" : ""}` : "Générer les créneaux";
    }
  }

  function genererCreneaux() {
    const btn = $("jnBtnConfirm");

    if (btn) btn.disabled = true;
    if ($("jnBtnLabel")) $("jnBtnLabel").textContent = "Génération en cours…";

    const formData = new FormData();

    formData.append("medecin", $("jnSelectMedecin")?.value || "");
    formData.append("date", $("jnInputDate")?.value || "");
    formData.append("debut", $("jnInputDebut")?.value || "");
    formData.append("fin", $("jnInputFin")?.value || "");
    formData.append("duree", $("jnInputDuree")?.value || "");
    formData.append("pause_debut", $("jnInputPauseDebut")?.value || "");
    formData.append("pause_fin", $("jnInputPauseFin")?.value || "");

    fetch(`${ENDPOINT}?action=generer_creneaux`, {
      method: "POST",
      body: formData
    })
      .then(r => r.json())
      .then(res => {
        if (!res.success) throw new Error(res.message || "Erreur inconnue");

        afficherToast(`✓ ${res.crees} créneau(x) créé(s)`, "success");

        closeModaleGeneration();
        chargerAlertes();

        if (typeof chargerCreneauxDepuisBD === "function") {
          chargerCreneauxDepuisBD();
        }
      })
      .catch(err => {
        afficherToast("✗ " + (err.message || "Erreur"), "error");

        if (btn) btn.disabled = false;
        recalculerApercu();
      });
  }

  function openModaleConfirm(payload) {
    if ($("jnConfirmSub")) {
      $("jnConfirmSub").textContent =
        `Patient : ${payload.patient_nom || "—"} (${payload.id_patient || "—"})`;
    }

    if ($("jnConfirmInfo")) {
      $("jnConfirmInfo").innerHTML =
        `Le patient <strong>${escapeHtml(payload.patient_nom || "—")}</strong> a accumulé ` +
        `<strong>${escapeHtml(payload.nb_annul || 0)}</strong> annulation(s).`;
    }

    if ($("jnConfirmList")) {
      $("jnConfirmList").innerHTML = `<div class="jn-list-empty">Chargement des RDV…</div>`;
    }

    if ($("jnConfirmCount")) $("jnConfirmCount").textContent = "0";
    if ($("jnBtnConfirmAction")) $("jnBtnConfirmAction").disabled = true;
    if ($("jnBtnConfirmLabel")) $("jnBtnConfirmLabel").textContent = "Marquer 0 RDV";

    fetch(`${ENDPOINT}?action=rdv_du_patient&patient=${encodeURIComponent(payload.id_patient || "")}`)
      .then(r => r.json())
      .then(res => {
        if (!res.success) throw new Error(res.message);

        const list = $("jnConfirmList");
        if (!list) return;

        if (!res.data || res.data.length === 0) {
          list.innerHTML = `<div class="jn-list-empty">Aucun RDV à venir.</div>`;
          return;
        }

        list.innerHTML = res.data.map(rdv => {
          const tag = tagClass(rdv.type_consultation);

          return `
            <label class="jn-list-item">
              <input type="checkbox" value="${escapeHtml(rdv.id_rdv)}" />

              <div class="jn-list-main">
                <div class="jn-list-title">
                  ${escapeHtml(rdv.id_rdv)} · ${fmtDate(rdv.date_rdv)}
                </div>

                <div class="jn-list-meta">
                  ${escapeHtml(rdv.medecin || "—")} ·
                  ${escapeHtml(rdv.specialite || "—")} ·
                  Statut : ${escapeHtml(rdv.statut || "—")}
                </div>
              </div>

              <span class="jn-list-tag ${tag}">
                ${escapeHtml(rdv.type_consultation || "—")}
              </span>
            </label>
          `;
        }).join("");

        list.querySelectorAll('input[type="checkbox"]').forEach(cb => {
          cb.addEventListener("change", updateConfirmCount);
        });
      })
      .catch(err => {
        if ($("jnConfirmList")) {
          $("jnConfirmList").innerHTML =
            `<div class="jn-list-empty" style="color:var(--red);">Erreur : ${escapeHtml(err.message)}</div>`;
        }
      });

    openAnyModale("jnModalConfirmBackdrop");
  }

  function updateConfirmCount() {
    const checked = document.querySelectorAll('#jnConfirmList input:checked').length;

    if ($("jnConfirmCount")) $("jnConfirmCount").textContent = checked;
    if ($("jnBtnConfirmAction")) $("jnBtnConfirmAction").disabled = checked === 0;
    if ($("jnBtnConfirmLabel")) $("jnBtnConfirmLabel").textContent = `Marquer ${checked} RDV`;
  }

  function executerMarquageConfirm() {
    const ids = Array.from(document.querySelectorAll('#jnConfirmList input:checked'))
      .map(cb => cb.value);

    if (ids.length === 0) return;

    const btn = $("jnBtnConfirmAction");
    if (btn) btn.disabled = true;
    if ($("jnBtnConfirmLabel")) $("jnBtnConfirmLabel").textContent = "Traitement…";

    const fd = new FormData();
    ids.forEach(id => fd.append("ids[]", id));

    fetch(`${ENDPOINT}?action=marquer_a_confirmer`, {
      method: "POST",
      body: fd
    })
      .then(r => r.json())
      .then(res => {
        if (!res.success) throw new Error(res.message);

        afficherToast(`✓ ${res.nb} RDV marqué(s) à confirmer`, "success");

        closeAnyModale("jnModalConfirmBackdrop");
        chargerAlertes();

        if (typeof chargerRdvDepuisBD === "function") {
          chargerRdvDepuisBD();
        }
      })
      .catch(err => {
        afficherToast("✗ " + (err.message || "Erreur"), "error");

        if (btn) btn.disabled = false;
        updateConfirmCount();
      });
  }

  function openModaleFacture(payload) {
    if ($("jnFactureSub")) {
      $("jnFactureSub").textContent =
        `${payload.nb || 0} facture(s) manquante(s) à régénérer`;
    }

    if ($("jnFactureNbInfo")) $("jnFactureNbInfo").textContent = payload.nb || 0;
    if ($("jnFactureNbList")) $("jnFactureNbList").textContent = `(${payload.nb || 0})`;
    if ($("jnFactureNbTotal")) $("jnFactureNbTotal").textContent = payload.nb || 0;

    if ($("jnFactureList")) {
      $("jnFactureList").innerHTML = `<div class="jn-list-empty">Chargement…</div>`;
    }

    fetch(`${ENDPOINT}?action=rdv_a_facturer`)
      .then(r => r.json())
      .then(res => {
        if (!res.success) throw new Error(res.message);

        const list = $("jnFactureList");
        if (!list) return;

        if (!res.data || res.data.length === 0) {
          list.innerHTML = `<div class="jn-list-empty">Aucun RDV à facturer.</div>`;
          return;
        }

        list.innerHTML = res.data.map(rdv => {
          const tag = tagClass(rdv.type_consultation);
          const nom = (rdv.patient || "").trim() || ("Patient #" + rdv.id_patient);

          return `
            <div class="jn-list-item" style="cursor:default;">
              <div class="jn-list-main">
                <div class="jn-list-title">
                  ${escapeHtml(rdv.id_rdv)} · ${fmtDate(rdv.date_rdv)}
                </div>

                <div class="jn-list-meta">
                  ${escapeHtml(nom)}
                </div>
              </div>

              <span class="jn-list-tag ${tag}">
                ${escapeHtml(rdv.type_consultation || "—")}
              </span>
            </div>
          `;
        }).join("");
      })
      .catch(err => {
        if ($("jnFactureList")) {
          $("jnFactureList").innerHTML =
            `<div class="jn-list-empty" style="color:var(--red);">Erreur : ${escapeHtml(err.message)}</div>`;
        }
      });

    recalculerCoutFacture();
    openAnyModale("jnModalFactureBackdrop");
  }

  function recalculerCoutFacture() {
    if (!$("jnFactureMontant") || !$("jnFactureNbTotal") || !$("jnFactureTotal")) return;

    const m = parseFloat($("jnFactureMontant").value) || 0;
    const nb = parseInt($("jnFactureNbTotal").textContent, 10) || 0;

    $("jnFactureTotal").textContent = (m * nb).toFixed(0) + " DT";
  }

  function executerRegenerationFactures() {
    const m = parseFloat($("jnFactureMontant")?.value) || 0;

    if (m <= 0) {
      afficherToast("✗ Montant invalide", "error");
      return;
    }

    const btn = $("jnBtnFactureAction");
    if (btn) btn.disabled = true;
    if ($("jnBtnFactureLabel")) $("jnBtnFactureLabel").textContent = "Génération…";

    const fd = new FormData();
    fd.append("montant", m);

    fetch(`${ENDPOINT}?action=regenerer_factures`, {
      method: "POST",
      body: fd
    })
      .then(r => r.json())
      .then(res => {
        if (!res.success) throw new Error(res.message);

        afficherToast(`✓ ${res.crees} facture(s) générée(s)`, "success");

        closeAnyModale("jnModalFactureBackdrop");
        chargerAlertes();
      })
      .catch(err => {
        afficherToast("✗ " + (err.message || "Erreur"), "error");

        if (btn) btn.disabled = false;
        if ($("jnBtnFactureLabel")) $("jnBtnFactureLabel").textContent = "Générer les factures";
      });
  }

  function openModaleDelete(payload) {
    if ($("jnDeleteSub")) {
      $("jnDeleteSub").textContent =
        `Heure ${payload.heure || "—"} · ${payload.nb_total || 0} occurrence(s)`;
    }

    if ($("jnDeleteHeure")) $("jnDeleteHeure").textContent = payload.heure || "—";
    if ($("jnDeleteList")) $("jnDeleteList").innerHTML = `<div class="jn-list-empty">Chargement…</div>`;
    if ($("jnDeleteCount")) $("jnDeleteCount").textContent = "0";
    if ($("jnBtnDeleteAction")) $("jnBtnDeleteAction").disabled = true;
    if ($("jnBtnDeleteLabel")) $("jnBtnDeleteLabel").textContent = "Supprimer 0 créneau";

    fetch(`${ENDPOINT}?action=creneaux_morts&heure=${encodeURIComponent(payload.heure || "")}`)
      .then(r => r.json())
      .then(res => {
        if (!res.success) throw new Error(res.message);

        const list = $("jnDeleteList");
        if (!list) return;

        if (!res.data || res.data.length === 0) {
          list.innerHTML = `<div class="jn-list-empty">Aucun créneau à supprimer.</div>`;
          return;
        }

        list.innerHTML = res.data.map(c => `
          <label class="jn-list-item">
            <input type="checkbox" value="${escapeHtml(c.id_creneau)}" checked />

            <div class="jn-list-main">
              <div class="jn-list-title">
                ${escapeHtml(c.id_creneau)} · ${fmtDate(c.date_creneau)}
              </div>

              <div class="jn-list-meta">
                ${fmtHeure(c.heure_debut)} – ${fmtHeure(c.heure_fin)} ·
                ${escapeHtml((c.medecin || "—").trim() || "—")}
              </div>
            </div>
          </label>
        `).join("");

        list.querySelectorAll('input[type="checkbox"]').forEach(cb => {
          cb.addEventListener("change", updateDeleteCount);
        });

        updateDeleteCount();
      })
      .catch(err => {
        if ($("jnDeleteList")) {
          $("jnDeleteList").innerHTML =
            `<div class="jn-list-empty" style="color:var(--red);">Erreur : ${escapeHtml(err.message)}</div>`;
        }
      });

    openAnyModale("jnModalDeleteBackdrop");
  }

  function updateDeleteCount() {
    const checked = document.querySelectorAll('#jnDeleteList input:checked').length;

    if ($("jnDeleteCount")) $("jnDeleteCount").textContent = checked;
    if ($("jnBtnDeleteAction")) $("jnBtnDeleteAction").disabled = checked === 0;
    if ($("jnBtnDeleteLabel")) {
      $("jnBtnDeleteLabel").textContent =
        `Supprimer ${checked} créneau${checked > 1 ? "x" : ""}`;
    }
  }

  function executerSuppressionCreneaux() {
    const ids = Array.from(document.querySelectorAll('#jnDeleteList input:checked'))
      .map(cb => cb.value);

    if (ids.length === 0) return;

    const btn = $("jnBtnDeleteAction");
    if (btn) btn.disabled = true;
    if ($("jnBtnDeleteLabel")) $("jnBtnDeleteLabel").textContent = "Suppression…";

    const fd = new FormData();
    ids.forEach(id => fd.append("ids[]", id));

    fetch(`${ENDPOINT}?action=supprimer_creneaux_morts`, {
      method: "POST",
      body: fd
    })
      .then(r => r.json())
      .then(res => {
        if (!res.success) throw new Error(res.message);

        afficherToast(`✓ ${res.nb} créneau(x) supprimé(s)`, "success");

        closeAnyModale("jnModalDeleteBackdrop");
        chargerAlertes();

        if (typeof chargerCreneauxDepuisBD === "function") {
          chargerCreneauxDepuisBD();
        }
      })
      .catch(err => {
        afficherToast("✗ " + (err.message || "Erreur"), "error");

        if (btn) btn.disabled = false;
        updateDeleteCount();
      });
  }

  function openModaleRedist(payload) {
    const dateIso = payload.date;
    const dateFr = fmtDate(payload.date);

    if ($("jnRedistSub")) {
      $("jnRedistSub").textContent =
        `${payload.medecin_nom || "—"} · ${payload.nb_reserves || 0} RDV le ${dateFr}`;
    }

    if ($("jnRedistMedecin")) $("jnRedistMedecin").textContent = payload.medecin_nom || "—";
    if ($("jnRedistList")) $("jnRedistList").innerHTML = `<div class="jn-list-empty">Chargement…</div>`;
    if ($("jnRedistCount")) $("jnRedistCount").textContent = "0";
    if ($("jnBtnRedistAction")) $("jnBtnRedistAction").disabled = true;
    if ($("jnBtnRedistLabel")) $("jnBtnRedistLabel").textContent = "Reporter 0 RDV";

    fetch(`${ENDPOINT}?action=rdv_redistribuables&medecin=${encodeURIComponent(payload.id_medecin || "")}&date=${encodeURIComponent(dateIso || "")}`)
      .then(r => r.json())
      .then(res => {
        if (!res.success) throw new Error(res.message);

        const list = $("jnRedistList");
        if (!list) return;

        if (!res.data || res.data.length === 0) {
          list.innerHTML = `<div class="jn-list-empty">Aucun RDV non urgent reportable trouvé.</div>`;
          return;
        }

        list.innerHTML = res.data.map(rdv => {
          const tag = tagClass(rdv.type_consultation);
          const nom = (rdv.patient || "").trim() || ("Patient #" + rdv.id_patient);

          return `
            <label class="jn-list-item">
              <input type="checkbox" value="${escapeHtml(rdv.id_rdv)}" />

              <div class="jn-list-main">
                <div class="jn-list-title">
                  ${escapeHtml(rdv.id_rdv)} · ${fmtHeure(rdv.heure_debut)}–${fmtHeure(rdv.heure_fin)}
                </div>

                <div class="jn-list-meta">
                  ${escapeHtml(nom)}
                </div>
              </div>

              <span class="jn-list-tag ${tag}">
                ${escapeHtml(rdv.type_consultation || "—")}
              </span>
            </label>
          `;
        }).join("");

        list.querySelectorAll('input[type="checkbox"]').forEach(cb => {
          cb.addEventListener("change", updateRedistCount);
        });
      })
      .catch(err => {
        if ($("jnRedistList")) {
          $("jnRedistList").innerHTML =
            `<div class="jn-list-empty" style="color:var(--red);">Erreur : ${escapeHtml(err.message)}</div>`;
        }
      });

    openAnyModale("jnModalRedistBackdrop");
  }

  function updateRedistCount() {
    const checked = document.querySelectorAll('#jnRedistList input:checked').length;

    if ($("jnRedistCount")) $("jnRedistCount").textContent = checked;
    if ($("jnBtnRedistAction")) $("jnBtnRedistAction").disabled = checked === 0;
    if ($("jnBtnRedistLabel")) $("jnBtnRedistLabel").textContent = `Reporter ${checked} RDV`;
  }

  function executerRedistribution() {
    const ids = Array.from(document.querySelectorAll('#jnRedistList input:checked'))
      .map(cb => cb.value);

    if (ids.length === 0) return;

    const btn = $("jnBtnRedistAction");
    if (btn) btn.disabled = true;
    if ($("jnBtnRedistLabel")) $("jnBtnRedistLabel").textContent = "Report en cours…";

    const fd = new FormData();
    ids.forEach(id => fd.append("ids[]", id));

    fetch(`${ENDPOINT}?action=redistribuer_rdv`, {
      method: "POST",
      body: fd
    })
      .then(r => r.json())
      .then(res => {
        if (!res.success) throw new Error(res.message);

        afficherToast(`✓ ${res.nb_rdv} RDV reporté(s)`, "success");

        closeAnyModale("jnModalRedistBackdrop");
        chargerAlertes();

        if (typeof chargerRdvDepuisBD === "function") chargerRdvDepuisBD();
        if (typeof chargerCreneauxDepuisBD === "function") chargerCreneauxDepuisBD();
      })
      .catch(err => {
        afficherToast("✗ " + (err.message || "Erreur"), "error");

        if (btn) btn.disabled = false;
        updateRedistCount();
      });
  }

  function openModaleProfil(payload) {
    if ($("jnProfilTitle")) {
      $("jnProfilTitle").textContent = `Profil de ${payload.medecin_nom || "—"}`;
    }

    if ($("jnProfilSub")) {
      $("jnProfilSub").textContent = `Spécialité : ${payload.specialite || "—"}`;
    }

    if ($("jnProfilBody")) {
      $("jnProfilBody").innerHTML =
        `<div class="jn-list-empty">Analyse en cours…</div>`;
    }

    openAnyModale("jnModalProfilBackdrop");

    fetch(`${ENDPOINT}?action=profil_medecin&id=${encodeURIComponent(payload.id_medecin || "")}`)
      .then(r => r.json())
      .then(res => {
        if (!res.success) throw new Error(res.message);
        renderProfil(res);
      })
      .catch(err => {
        if ($("jnProfilBody")) {
          $("jnProfilBody").innerHTML =
            `<div class="jn-list-empty" style="color:var(--red);">Erreur : ${escapeHtml(err.message)}</div>`;
        }
      });
  }

  function renderProfil(data) {
    const m = data.medecin || {};
    const s = data.stats || {};
    const ev = data.evolution || [];
    const cr = data.creneaux_libres || [];
    const sg = data.suggestions || [];

    const classTaux =
      Number(s.taux || 0) < 20 ? "bad" :
      Number(s.taux || 0) < 50 ? "warn" :
      "good";

    const crHtml = cr.length === 0
      ? `<div class="jn-list-empty">Aucun créneau libre dans les 7 prochains jours.</div>`
      : cr.map(c => `
        <div class="jn-list-item" style="cursor:default;">
          <div class="jn-list-main">
            <div class="jn-list-title">
              ${escapeHtml(c.id_creneau)} · ${fmtDate(c.date_creneau)}
            </div>

            <div class="jn-list-meta">
              ${fmtHeure(c.heure_debut)} – ${fmtHeure(c.heure_fin)}
            </div>
          </div>
        </div>
      `).join("");

    const sgHtml = sg.map(sug => `
      <div class="jn-suggestion">
        <div class="jn-suggestion-text">
          <div class="jn-suggestion-title">
            ${escapeHtml(sug.titre || "")}
          </div>

          <div class="jn-suggestion-detail">
            ${escapeHtml(sug.detail || "")}
          </div>
        </div>
      </div>
    `).join("");

    if (!$("jnProfilBody")) return;

    $("jnProfilBody").innerHTML = `
      <div class="jn-modal-info">
        <strong>${escapeHtml((m.Prenom || "") + " " + (m.Nom || ""))}</strong>
        — ${escapeHtml(m.Service || "—")}
        <br>
        ${escapeHtml(m.Email || "")} · ID : ${escapeHtml(m.id_user || "—")}
      </div>

      <div class="jn-profil-grid">
        <div class="jn-stat-card">
          <span class="jn-stat-label">Taux d'occupation</span>
          <span class="jn-stat-big ${classTaux}">${escapeHtml(s.taux || 0)}%</span>
          <span class="jn-stat-detail">
            ${escapeHtml(s.nb_reserves || 0)} réservés / ${escapeHtml(s.nb_total || 0)} créneaux
          </span>
        </div>

        <div class="jn-stat-card">
          <span class="jn-stat-label">Créneaux libres</span>
          <span class="jn-stat-big">${escapeHtml(s.nb_libres || 0)}</span>
          <span class="jn-stat-detail">Sur 30 derniers jours</span>
        </div>
      </div>

      <div class="jn-section-title">Créneaux libres à venir</div>

      <div class="jn-list" style="max-height:160px;">
        ${crHtml}
      </div>

      <div class="jn-section-title">Recommandations</div>

      ${sgHtml}
    `;
  }

  document.addEventListener("DOMContentLoaded", () => {
    chargerAlertes();
    setInterval(chargerAlertes, REFRESH_MS);

    if ($("jnRefresh")) {
      $("jnRefresh").addEventListener("click", chargerAlertes);
    }

    if ($("jnModalClose")) $("jnModalClose").addEventListener("click", closeModaleGeneration);
    if ($("jnBtnCancel")) $("jnBtnCancel").addEventListener("click", closeModaleGeneration);

    if ($("jnModalBackdrop")) {
      $("jnModalBackdrop").addEventListener("click", e => {
        if (e.target === $("jnModalBackdrop")) closeModaleGeneration();
      });
    }

    [
      "jnSelectMedecin",
      "jnInputDate",
      "jnInputDebut",
      "jnInputFin",
      "jnInputDuree",
      "jnInputPauseDebut",
      "jnInputPauseFin"
    ].forEach(id => {
      const el = $(id);
      if (el) {
        el.addEventListener("input", recalculerApercu);
        el.addEventListener("change", recalculerApercu);
      }
    });

    if ($("jnBtnConfirm")) {
      $("jnBtnConfirm").addEventListener("click", genererCreneaux);
    }

    document.querySelectorAll("[data-close]").forEach(el => {
      el.addEventListener("click", () => closeAnyModale(el.dataset.close));
    });

    [
      "jnModalConfirmBackdrop",
      "jnModalFactureBackdrop",
      "jnModalDeleteBackdrop",
      "jnModalRedistBackdrop",
      "jnModalProfilBackdrop"
    ].forEach(id => {
      const m = $(id);
      if (m) {
        m.addEventListener("click", e => {
          if (e.target === m) closeAnyModale(id);
        });
      }
    });

    document.addEventListener("keydown", e => {
      if (e.key === "Escape") {
        [
          "jnModalBackdrop",
          "jnModalConfirmBackdrop",
          "jnModalFactureBackdrop",
          "jnModalDeleteBackdrop",
          "jnModalRedistBackdrop",
          "jnModalProfilBackdrop"
        ].forEach(closeAnyModale);
      }
    });

    if ($("jnConfirmAll")) {
      $("jnConfirmAll").addEventListener("click", () => {
        document.querySelectorAll("#jnConfirmList input").forEach(cb => cb.checked = true);
        updateConfirmCount();
      });
    }

    if ($("jnConfirmNone")) {
      $("jnConfirmNone").addEventListener("click", () => {
        document.querySelectorAll("#jnConfirmList input").forEach(cb => cb.checked = false);
        updateConfirmCount();
      });
    }

    if ($("jnBtnConfirmAction")) {
      $("jnBtnConfirmAction").addEventListener("click", executerMarquageConfirm);
    }

    if ($("jnFactureMontant")) {
      $("jnFactureMontant").addEventListener("input", recalculerCoutFacture);
    }

    if ($("jnBtnFactureAction")) {
      $("jnBtnFactureAction").addEventListener("click", executerRegenerationFactures);
    }

    if ($("jnDeleteAll")) {
      $("jnDeleteAll").addEventListener("click", () => {
        document.querySelectorAll("#jnDeleteList input").forEach(cb => cb.checked = true);
        updateDeleteCount();
      });
    }

    if ($("jnDeleteNone")) {
      $("jnDeleteNone").addEventListener("click", () => {
        document.querySelectorAll("#jnDeleteList input").forEach(cb => cb.checked = false);
        updateDeleteCount();
      });
    }

    if ($("jnBtnDeleteAction")) {
      $("jnBtnDeleteAction").addEventListener("click", executerSuppressionCreneaux);
    }

    if ($("jnRedistAll")) {
      $("jnRedistAll").addEventListener("click", () => {
        document.querySelectorAll("#jnRedistList input").forEach(cb => cb.checked = true);
        updateRedistCount();
      });
    }

    if ($("jnRedistNone")) {
      $("jnRedistNone").addEventListener("click", () => {
        document.querySelectorAll("#jnRedistList input").forEach(cb => cb.checked = false);
        updateRedistCount();
      });
    }

    if ($("jnBtnRedistAction")) {
      $("jnBtnRedistAction").addEventListener("click", executerRedistribution);
    }
  });
})();