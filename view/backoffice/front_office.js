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

function escapeHtml(value) {
  return String(value ?? "")
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/\"/g, "&quot;")
    .replace(/'/g, "&#039;");
}

function formatRdvStatus(statut) {
  const s = String(statut || "").toLowerCase();
  if (s === "confirme" || s === "confirmé") return { text: "Confirmé", cls: "confirme" };
  return { text: s.replace(/_/g, " ") || "En attente", cls: "en-attente" };
}

function formatCreneauStatus(statut) {
  const s = String(statut || "").toLowerCase();
  if (s === "disponible") 
    return { text: "Disponible", cls: "available" };
  return { text: "Réservé", cls: "reserved" };
}

function validerFormulaireRdv() {
  return document.getElementById("idRdv").value &&
         document.getElementById("idPatient").value &&
         document.getElementById("dateDemande").value &&
         document.getElementById("typeConsultation").value &&
         document.getElementById("idMedecin").value &&
         document.getElementById("idCreneau").value;
}

function validerFormulaireCreneau() {
  const d = document.getElementById("dateCreneau").value;
  const hd = document.getElementById("heureDebut").value;
  const hf = document.getElementById("heureFin").value;
  const med = document.getElementById("medecinCreneau").value;
  return d && hd && hf && med && hf > hd;
}

function remplirSelectMedecins(medecins) {
  window._medecinsData = medecins;
  const ids = ["idMedecin", "medecinCreneau"];
  ids.forEach(id => {
    const select = document.getElementById(id);
    if (!select) return;
    const current = select.value;
    select.innerHTML = '<option value="">— Choisir un médecin —</option>';
    medecins.forEach(m => {
      const opt = document.createElement("option");
      opt.value = m.id_user;
      opt.textContent = `${m.nom_complet} (${m.id_user})`;
      select.appendChild(opt);
    });
    if (medecins.some(m => m.id_user === current)) select.value = current;
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

function chargerCreneauxDepuisBD() {
  const medecin = document.getElementById("idMedecin")?.value ?? "";
  const tbody = document.getElementById("creneauTableBody");
  const url = medecin
    ? `creneau.php?action=charger_medecin&medecin=${encodeURIComponent(medecin)}`
    : "creneau.php?action=charger";

  fetch(url)
    .then(r => {
      if (!r.ok) throw new Error(`HTTP ${r.status}`);
      return r.json();
    })
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
              <td><div class="actions"><i class="fa-solid fa-pen" title="Modifier"></i><i class="fa-solid fa-trash" title="Supprimer"></i></div></td>
            `;
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
          opt.textContent = `${c.id_creneau} | ${c.date} | ${c.heure_debut}-${c.heure_fin}`;
          select.appendChild(opt);
        });
        select.disabled = !medecin || !disponibles.length;
        if (!medecin) {
          select.innerHTML = '<option value="">— Sélectionnez un médecin d\'abord —</option>';
          select.disabled = true;
        }
      }
      // Appliquer la recherche / le filtre statut sur la table des créneaux
      if (typeof appliquerFiltresCreneau === 'function') appliquerFiltresCreneau();
      if (typeof mettreAJourStats === 'function') mettreAJourStats();
    })
    .catch(err => {
      console.error(err);
      showToast("Erreur de connexion au serveur", "error");
    });
}
function chargerRdvDepuisBD() {
  fetch("rdv.php?action=charger")
    .then(r => {
      if (!r.ok) throw new Error(`HTTP ${r.status}`);
      return r.json();
    })
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
            <td><div class="actions"><i class="fa-solid fa-pen" title="Modifier"></i><i class="fa-solid fa-ellipsis" title="Supprimer"></i></div></td>
          `;
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

bind("btnAjouterCreneau", "click", function () {
  if (!validerFormulaireCreneau()) {
    showToast("Tous les champs sont requis", "error");
    return;
  }

  const body = new URLSearchParams({
    action: "ajouter",
    date: document.getElementById("dateCreneau").value,
    debut: document.getElementById("heureDebut").value,
    fin: document.getElementById("heureFin").value,
    medecin: document.getElementById("medecinCreneau").value
  });

  fetch("creneau.php", { method: "POST", body })
    .then(r => r.json())
    .then(res => {
      if (!res.success) {
        const msgs = {
          champs_manquants: "Tous les champs sont requis",
          date_invalide: "La date ne peut pas être dans le passé",
          heure_invalide: "L'heure de fin doit être après l'heure de début",
          creneau_existe: "Ce créneau chevauche un créneau existant"
        };
        showToast(msgs[res.message] ?? res.message, "error");
        return;
      }
      showToast("Créneau ajouté avec succès !");
      chargerCreneauxDepuisBD();
    })
    .catch(err => {
      console.error(err);
      showToast("Erreur serveur", "error");
    });
});

bind("btnAjouterRdv", "click", function () {
  if (!validerFormulaireRdv()) {
    showToast("Champs invalides", "error");
    return;
  }

  const body = new URLSearchParams({
    action: "ajouter",
    id: document.getElementById("idRdv").value,
    patient: document.getElementById("idPatient").value,
    date_demande: document.getElementById("dateDemande").value,
    type: document.getElementById("typeConsultation").value,
    medecin: document.getElementById("idMedecin").value,
    creneau: document.getElementById("idCreneau").value
  });

  fetch("rdv.php", { method: "POST", body })
    .then(r => r.json())
    .then(res => {
      if (!res.success) {
        const msgs = {
          champs_manquants: "Tous les champs sont requis",
          id_invalide: "Format ID invalide (ex: RDV-001)",
          id_existe: "Cet ID de RDV existe déjà",
          creneau_introuvable: "Créneau introuvable",
          creneau_pris: "Ce créneau est déjà réservé"
        };
        showToast(msgs[res.message] ?? res.message, "error");
        return;
      }
      showToast("RDV ajouté avec succès !");
      chargerRdvDepuisBD();
      chargerCreneauxDepuisBD();
    })
    .catch(err => {
      console.error(err);
      showToast("Erreur serveur", "error");
    });
});

document.addEventListener("click", function (e) {

  if (window._medecinsData && window._medecinsData.length > 0) {
    const selMed = document.getElementById("idMedecin");
    if (selMed && selMed.options.length <= 1) {
      remplirSelectMedecins(window._medecinsData);
    }
  }

  if (e.target.closest("#creneauTableBody .fa-trash")) {
    const tr = e.target.closest("tr");
    const id = tr?.dataset.id;
    if (!id) return;
    if (!confirm("Supprimer ce créneau ?")) return;

    fetch("creneau.php", {
      method: "POST",
      body: new URLSearchParams({ action: "supprimer", id })
    })
      .then(r => r.json())
      .then(res => {
        if (!res.success) {
          const msgs = { creneau_reserve: "Impossible : ce créneau est réservé" };
          showToast(msgs[res.message] ?? res.message, "error");
          return;
        }
        showToast("Créneau supprimé");
        chargerCreneauxDepuisBD();
      });
  }
  if (e.target.closest("#rdvTableBody .fa-ellipsis")) {
    const tr = e.target.closest("tr");
    const id = tr?.dataset.id;
    if (!id) return;
    if (!confirm("Supprimer ce RDV ?")) return;

    fetch("rdv.php", {
      method: "POST",
      body: new URLSearchParams({ action: "supprimer", id })
    })
      .then(r => r.json())
      .then(res => {
        if (!res.success) {
          showToast(res.message, "error");
          return;
        }
        showToast("RDV supprimé");
        chargerRdvDepuisBD();
        chargerCreneauxDepuisBD();
      });
  }

  if (e.target.closest(".fa-pen")) {
    const tr = e.target.closest("tr");
    if (!tr || tr.classList.contains("edit") || !tr.dataset.id) return;
    tr.classList.add("edit");
    const tds = tr.children;
    for (let i = 1; i < tds.length - 1; i++) {
      tds[i].innerHTML = `<input value="${escapeHtml(tds[i].textContent.trim())}">`;
    }
    tds[tds.length - 1].innerHTML = '<i class="fa-solid fa-check" title="Enregistrer"></i><i class="fa-solid fa-xmark" title="Annuler"></i>';
  }

  if (e.target.closest("#creneauTableBody .fa-check")) {
    const tr = e.target.closest("tr");
    const id = tr?.dataset.id;
    const inputs = tr?.querySelectorAll("input") ?? [];

    fetch("creneau.php", {
      method: "POST",
      body: new URLSearchParams({
        action: "modifier",
        id,
        date: inputs[0]?.value,
        debut: inputs[1]?.value,
        fin: inputs[2]?.value
      })
    })
      .then(r => r.json())
      .then(res => {
        if (!res.success) {
          showToast(res.message, "error");
          return;
        }
        showToast("Créneau modifié");
        chargerCreneauxDepuisBD();
      });
  }

  if (e.target.closest("#rdvTableBody .fa-check")) {
    const tr = e.target.closest("tr");
    const id = tr?.dataset.id;
    const inputs = tr?.querySelectorAll("input") ?? [];

    fetch("rdv.php", {
      method: "POST",
      body: new URLSearchParams({
        action: "modifier",
        id,
        date: inputs[0]?.value,
        type: inputs[2]?.value
      })
    })
      .then(r => r.json())
      .then(res => {
        if (!res.success) {
          showToast(res.message, "error");
          return;
        }
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
   FILTRES + RECHERCHE pour les 2 tables
   ═══════════════════════════════════════════════════════════ */

let pageRdv = 1;
let pageCreneau = 1;
const ROWS = 4;

// Texte recherché ⊆ contenu d'une ligne (insensible à la casse, sans accents)
function normalizeText(s) {
  return String(s || "")
    .normalize("NFD").replace(/\p{Diacritic}/gu, "")
    .toLowerCase().trim();
}

function rowMatchesQuery(row, query) {
  if (!query) return true;
  return normalizeText(row.textContent).includes(normalizeText(query));
}

function rowMatchesStatus(row, statusValue) {
  if (!statusValue) return true;
  const badge = row.querySelector(".status-badge");
  if (!badge) return false;

  // Comparer par texte normalisé (insensible casse/accents) ET par classe CSS,
  // pour gérer les cas où la classe est différente du value du <option>
  const target = normalizeText(statusValue);
  const badgeText = normalizeText(badge.textContent);

  // Map des équivalences value-option → texte affiché possible
  const equivalents = {
    'reserve':    ['reserve', 'reserved', 'reservé'],
    'disponible': ['disponible', 'available', 'libre'],
    'confirme':   ['confirme', 'confirmed', 'confirmé', 'valide', 'validé'],
    'en-attente': ['en attente', 'en-attente', 'pending', 'en_attente'],
    'annule':     ['annule', 'annulé', 'cancelled', 'canceled']
  };

  // 1) match par texte du badge (le plus fiable)
  const candidates = equivalents[target] || [target];
  if (candidates.some(c => badgeText === normalizeText(c))) return true;

  // 2) fallback : match par classe CSS sur le badge ou sa ligne
  return badge.classList.contains(statusValue)
      || row.classList.contains(statusValue)
      || candidates.some(c => badge.classList.contains(c));
}

/* ─── Table RENDEZ-VOUS ──────────────────────── */
function appliquerFiltresRdv() {
  const tbody = document.getElementById("rdvTableBody");
  if (!tbody) return;

  const query = document.getElementById("searchRdv")?.value || "";
  const statut = document.getElementById("filterRdv")?.value || "";

  const allRows = Array.from(tbody.querySelectorAll("tr")).filter(r => r.dataset.id);
  const visibles = allRows.filter(r => rowMatchesQuery(r, query) && rowMatchesStatus(r, statut));

  // Cacher tout d'abord
  allRows.forEach(r => { r.style.display = "none"; });

  // Pagination sur les lignes visibles uniquement
  const total = visibles.length;
  const maxPage = Math.max(1, Math.ceil(total / ROWS));
  if (pageRdv > maxPage) pageRdv = maxPage;

  visibles.forEach((r, i) => {
    const start = (pageRdv - 1) * ROWS;
    const end = pageRdv * ROWS;
    r.style.display = i >= start && i < end ? "" : "none";
  });

  // Compteur
  const countEl = document.getElementById("rdvCount");
  if (countEl) {
    countEl.textContent = total === 0
      ? "Aucun rendez-vous ne correspond"
      : `Affichage de ${total} entr${total > 1 ? "ées" : "ée"}`;
  }

  // Boutons de pagination (s'ils existent — sinon on les ignore)
  document.querySelectorAll("#rdvSection .page-number, .page-number").forEach((btn, idx) => {
    btn.classList.toggle("active", idx + 1 === pageRdv);
  });
}

/* ─── Table CRENEAUX ─────────────────────────── */
function appliquerFiltresCreneau() {
  const tbody = document.getElementById("creneauTableBody");
  if (!tbody) return;

  const query = document.getElementById("searchCreneau")?.value || "";
  const statut = document.getElementById("filterCreneau")?.value || "";

  const allRows = Array.from(tbody.querySelectorAll("tr")).filter(r => r.dataset.id);
  const visibles = allRows.filter(r => rowMatchesQuery(r, query) && rowMatchesStatus(r, statut));

  allRows.forEach(r => { r.style.display = "none"; });

  const total = visibles.length;
  const maxPage = Math.max(1, Math.ceil(total / ROWS));
  if (pageCreneau > maxPage) pageCreneau = maxPage;

  visibles.forEach((r, i) => {
    const start = (pageCreneau - 1) * ROWS;
    const end = pageCreneau * ROWS;
    r.style.display = i >= start && i < end ? "" : "none";
  });

  const countEl = document.getElementById("creneauCount");
  if (countEl) {
    countEl.textContent = total === 0
      ? "Aucun créneau ne correspond"
      : `Affichage de ${total} entr${total > 1 ? "ées" : "ée"}`;
  }
}

// Compat avec l'ancien nom — appelé après les fetch
function mettreAJourTableau() {
  appliquerFiltresRdv();
  appliquerFiltresCreneau();
  mettreAJourStats();
}

/* ─── Statistiques (cartes en haut de page) ──── */
function mettreAJourStats() {
  // Compter directement à partir des lignes des tableaux
  const rdvRows = Array.from(document.querySelectorAll("#rdvTableBody tr")).filter(r => r.dataset.id);
  const creneauRows = Array.from(document.querySelectorAll("#creneauTableBody tr")).filter(r => r.dataset.id);

  let confirmes = 0;
  let enAttente = 0;
  rdvRows.forEach(row => {
    const badge = row.querySelector(".status-badge");
    if (!badge) return;
    if (badge.classList.contains("confirme")) confirmes++;
    else if (badge.classList.contains("en-attente")) enAttente++;
  });

  let dispos = 0;
  creneauRows.forEach(row => {
    const badge = row.querySelector(".status-badge");
    if (!badge) return;
    // les classes peuvent être "available" (anglais) ou "disponible" (français)
    if (badge.classList.contains("available") || badge.classList.contains("disponible")) {
      dispos++;
    }
  });

  // Mise à jour des chiffres principaux
  const setText = (id, value) => { const el = document.getElementById(id); if (el) el.textContent = value; };
  setText("rdvConfirmes",      confirmes);
  setText("rdvEnAttente",      enAttente);
  setText("creneauxDispo",     dispos);

  // Mise à jour des petits badges (en haut à droite de chaque carte)
  const total = rdvRows.length;
  setText("stat-badge-confirme",  total > 0 ? Math.round((confirmes / total) * 100) + "%" : "—");
  setText("stat-badge-attente",   total > 0 ? Math.round((enAttente / total) * 100) + "%" : "—");
  setText("stat-badge-creneaux",  creneauRows.length > 0 ? dispos + "/" + creneauRows.length : "—");
}

/* ─── Wiring des champs ──────────────────────── */
function brancherFiltres() {
  // RDV
  const searchRdv = document.getElementById("searchRdv");
  const filterRdv = document.getElementById("filterRdv");
  if (searchRdv) searchRdv.addEventListener("input",  () => { pageRdv = 1; appliquerFiltresRdv(); });
  if (filterRdv) filterRdv.addEventListener("change", () => { pageRdv = 1; appliquerFiltresRdv(); });

  // Créneaux
  const searchCre = document.getElementById("searchCreneau");
  const filterCre = document.getElementById("filterCreneau");
  if (searchCre) searchCre.addEventListener("input",  () => { pageCreneau = 1; appliquerFiltresCreneau(); });
  if (filterCre) filterCre.addEventListener("change", () => { pageCreneau = 1; appliquerFiltresCreneau(); });
}

function activerPagination() {
  const boutons = document.querySelectorAll(".page-number");
  boutons.forEach((btn, index) => {
    btn.addEventListener("click", function () {
      pageRdv = index + 1;
      appliquerFiltresRdv();
      boutons.forEach(b => b.classList.remove("active"));
      this.classList.add("active");
    });
  });
}

activerPagination();
brancherFiltres();

document.getElementById("dateDemande").min = todayStr();
document.getElementById("dateCreneau").min = todayStr();

chargerMedecinsDepuisBD().finally(() => {
  chargerCreneauxDepuisBD();
  chargerRdvDepuisBD();
});

bind("idMedecin", "change", chargerCreneauxDepuisBD);
function redirectUser() {
    const role = document.getElementById("roleSelect").value;

    if (role === "patient") {
        window.location.href = "front/index.html"; // Front Office
    } 
    else if (role === "medecin" || role === "secretaire") {
        window.location.href = "back/index.html"; // Back Office
    }
}
function redirectUser() {
    const role = document.getElementById("roleSelect").value;

    localStorage.setItem("role", role);

    if (role === "patient") {
        window.location.href = "front/index.html";
    } 
    else if (role === "medecin" || role === "secretaire") {
        window.location.href = "back/index.html";
    }
}
















































(function () {
  const ENDPOINT     = "alertes.php";
  const REFRESH_MS   = 30000;
  const NIVEAUX_LIB  = {
    critique:     "Critique",
    attention:    "Attention",
    prediction:   "Prédiction",
    optimisation: "Optimisation"
  };

  const $ = (id) => document.getElementById(id);
  const escapeHtml = (s) => String(s ?? "")
    .replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;")
    .replace(/"/g,"&quot;").replace(/'/g,"&#039;");

  /* ─── Score circulaire ───────────────────────────────────── */
  function setScore(value) {
    const v = Math.max(0, Math.min(100, Number(value) || 0));
    const deg = (v / 100) * 360;
    const ring = $("jnScore");
    const out  = $("jnScoreVal");
    if (ring) ring.style.setProperty("--score-deg", deg + "deg");
    if (out)  out.textContent = v;
    if (ring) {
      let color = "#22ef8b";
      if (v <= 70) color = "#ffb547";
      if (v <= 40) color = "#ff5a5a";
      ring.style.background = `conic-gradient(${color} 0deg, ${color} ${deg}deg, rgba(255,255,255,.18) ${deg}deg)`;
    }
  }

  /* ─── Rendu des alertes ──────────────────────────────────── */
  function renderAlertes(payload) {
    const grid = $("jnGrid");
    if (!grid) return;

    const c = payload.compteurs || { critique:0, attention:0, prediction:0, optimisation:0 };
    $("jnCntCritique").textContent   = c.critique;
    $("jnCntAttention").textContent  = c.attention;
    $("jnCntPrediction").textContent = c.prediction;
    $("jnCntOptim").textContent      = c.optimisation;

    setScore(payload.score);
    $("jnSub").textContent =
      `Dernière analyse à ${payload.horodate} · ${payload.total} alerte(s) active(s) · prochaine actualisation auto dans 30 s`;

    if (!payload.alertes || payload.alertes.length === 0) {
      grid.innerHTML = `
        <div class="jn-empty">
          <div class="check">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
              <path d="M5 13l4 4L19 7"/>
            </svg>
          </div>
          <div><strong>Aucune anomalie détectée.</strong></div>
          <div style="margin-top:3px;">Le jumeau numérique est en parfaite santé.</div>
        </div>`;
      return;
    }

    grid.innerHTML = payload.alertes.map((a, i) => {
      const clickable = !!(a.payload && a.payload.type);
      const payloadAttr = clickable
        ? `data-payload='${escapeHtml(JSON.stringify(a.payload))}'`
        : "";
      return `
      <div class="jn-card ${a.niveau}" style="animation-delay:${i * 60}ms">
        <div class="jn-card-top">
          <span class="jn-pill ${a.niveau}">${NIVEAUX_LIB[a.niveau] || a.niveau}</span>
          <span class="jn-cat">${escapeHtml(a.categorie)}</span>
        </div>
        <div class="jn-card-title">${escapeHtml(a.titre)}</div>
        <div class="jn-card-detail">${escapeHtml(a.detail)}</div>
        ${a.action ? `<div class="jn-card-action ${clickable ? "clickable" : ""}" ${payloadAttr}>
          <svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <path d="M5 12h14M13 5l7 7-7 7"/>
          </svg>
          ${escapeHtml(a.action)}
        </div>` : ``}
      </div>`;
    }).join("");

    /* Brancher les clics sur les actions cliquables */
    grid.querySelectorAll(".jn-card-action.clickable").forEach(el => {
      el.addEventListener("click", () => {
        try {
          const payload = JSON.parse(el.dataset.payload);
          /* Routeur des actions du Jumeau Numérique */
          switch (payload.type) {
            case "generer_creneaux":         openModaleGeneration(payload); break;
            case "marquer_a_confirmer":      openModaleConfirm(payload);    break;
            case "regenerer_factures":       openModaleFacture(payload);    break;
            case "supprimer_creneaux_morts": openModaleDelete(payload);     break;
            case "redistribuer_rdv":         openModaleRedist(payload);     break;
            case "voir_profil_medecin":      openModaleProfil(payload);     break;
            default: console.warn("Action inconnue", payload.type);
          }
        } catch (e) { console.error(e); }
      });
    });
  }

  function renderError(msg) {
    const grid = $("jnGrid");
    if (!grid) return;
    grid.innerHTML = `
      <div class="jn-empty" style="border-color:var(--red); color:var(--red);">
        <div class="check" style="background:var(--red-lt); color:var(--red);">
          <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="10"/><path d="M12 8v5M12 17h.01"/>
          </svg>
        </div>
        <div><strong>Impossible de charger les alertes</strong></div>
        <div style="margin-top:3px; opacity:.85;">${escapeHtml(msg)}</div>
      </div>`;
    $("jnSub").textContent = "Erreur de communication avec le serveur";
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
        if (btn) setTimeout(() => btn.classList.remove("spin"), 800);
      });
  }

  /* ═══════════════════════════════════════════════════════════
     MODALE — Génération de créneaux supplémentaires
     ═══════════════════════════════════════════════════════════ */

  let currentSpecialite = null;

  function openModaleGeneration(payload) {
    currentSpecialite = payload.specialite || "—";
    $("jnModalSub").textContent = `Spécialité : ${currentSpecialite}`;
    $("jnModalInfo").innerHTML  =
      `Le Jumeau Numérique a détecté un <strong>engorgement</strong> sur la spécialité ` +
      `<strong>« ${escapeHtml(currentSpecialite)} »</strong>. Sélectionnez un médecin et configurez ` +
      `une plage horaire pour générer plusieurs créneaux d'un coup.`;

    /* Date par défaut : demain */
    const demain = new Date();
    demain.setDate(demain.getDate() + 1);
    $("jnInputDate").value = demain.toISOString().split("T")[0];
    $("jnInputDate").min   = new Date().toISOString().split("T")[0];

    /* Charger la liste des médecins de cette spécialité */
    const select = $("jnSelectMedecin");
    select.innerHTML = '<option value="">— Chargement des médecins —</option>';

    fetch(`${ENDPOINT}?action=medecins_par_specialite&specialite=${encodeURIComponent(currentSpecialite)}`)
      .then(r => r.json())
      .then(res => {
        if (!res.success) throw new Error(res.message);
        if (!res.data || res.data.length === 0) {
          select.innerHTML = '<option value="">Aucun médecin trouvé pour cette spécialité</option>';
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

    $("jnModalBackdrop").classList.add("open");
    document.body.style.overflow = "hidden";
  }

  function closeModale() {
    $("jnModalBackdrop").classList.remove("open");
    document.body.style.overflow = "";
  }

  /* Helper générique : fermer n'importe quelle modale par son ID */
  function closeAnyModale(id) {
    const m = $(id);
    if (m) m.classList.remove("open");
    document.body.style.overflow = "";
  }
  function openAnyModale(id) {
    const m = $(id);
    if (m) m.classList.add("open");
    document.body.style.overflow = "hidden";
  }

  /* Helper de format date pour les listes */
  function fmtDate(s) {
    if (!s) return "—";
    const d = new Date(s);
    return d.toLocaleDateString("fr-FR", { day: "2-digit", month: "2-digit", year: "numeric" });
  }
  function fmtHeure(s) {
    return s ? s.substring(0, 5) : "—";
  }

  /* Tag CSS class selon le type de consultation */
  function tagClass(type) {
    const t = String(type || "").toLowerCase();
    if (t.includes("urgence"))     return "urgence";
    if (t.includes("suivi"))       return "suivi";
    if (t.includes("special"))     return "specialisee";
    return "generale";
  }

  /* ═══════════════════════════════════════════════════════════
     MODALE 2 : MARQUER À CONFIRMER (patient à risque)
     ═══════════════════════════════════════════════════════════ */
  function openModaleConfirm(payload) {
    $("jnConfirmSub").textContent = `Patient : ${payload.patient_nom} (${payload.id_patient})`;
    $("jnConfirmInfo").innerHTML =
      `Le patient <strong>${escapeHtml(payload.patient_nom)}</strong> a accumulé ` +
      `<strong>${payload.nb_annul} annulations</strong>. ` +
      `Sélectionnez les RDV à venir à marquer comme <em>« à confirmer »</em>. ` +
      `Le médecin/secrétaire devra contacter le patient 24h avant.`;

    $("jnConfirmList").innerHTML = `<div class="jn-list-empty">Chargement des RDV…</div>`;
    $("jnConfirmCount").textContent = "0";
    $("jnBtnConfirmAction").disabled = true;
    $("jnBtnConfirmLabel").textContent = "Marquer 0 RDV";

    fetch(`${ENDPOINT}?action=rdv_du_patient&patient=${encodeURIComponent(payload.id_patient)}`)
      .then(r => r.json())
      .then(res => {
        if (!res.success) throw new Error(res.message);
        const list = $("jnConfirmList");
        if (!res.data || res.data.length === 0) {
          list.innerHTML = `<div class="jn-list-empty">Aucun RDV à venir pour ce patient.</div>`;
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
                  Statut : ${escapeHtml(rdv.statut)}
                </div>
              </div>
              <span class="jn-list-tag ${tag}">${escapeHtml(rdv.type_consultation || "—")}</span>
            </label>`;
        }).join("");

        list.querySelectorAll('input[type="checkbox"]').forEach(cb =>
          cb.addEventListener("change", updateConfirmCount));
      })
      .catch(err => {
        $("jnConfirmList").innerHTML =
          `<div class="jn-list-empty" style="color:var(--red);">Erreur : ${escapeHtml(err.message)}</div>`;
      });

    openAnyModale("jnModalConfirmBackdrop");
  }

  function updateConfirmCount() {
    const checked = document.querySelectorAll('#jnConfirmList input:checked').length;
    $("jnConfirmCount").textContent = checked;
    $("jnBtnConfirmAction").disabled = checked === 0;
    $("jnBtnConfirmLabel").textContent = `Marquer ${checked} RDV`;
  }

  function executerMarquageConfirm() {
    const ids = Array.from(document.querySelectorAll('#jnConfirmList input:checked'))
                     .map(cb => cb.value);
    if (ids.length === 0) return;

    const btn = $("jnBtnConfirmAction");
    btn.disabled = true;
    $("jnBtnConfirmLabel").textContent = "Traitement…";

    const fd = new FormData();
    ids.forEach(id => fd.append("ids[]", id));

    fetch(`${ENDPOINT}?action=marquer_a_confirmer`, { method: "POST", body: fd })
      .then(r => r.json())
      .then(res => {
        if (!res.success) throw new Error(res.message);
        afficherToast(`✓ ${res.nb} RDV marqué(s) à confirmer`, "success");
        closeAnyModale("jnModalConfirmBackdrop");
        chargerAlertes();
        if (typeof chargerRdvDepuisBD === "function") chargerRdvDepuisBD();
      })
      .catch(err => {
        afficherToast("✗ " + (err.message || "Erreur"), "error");
        btn.disabled = false;
        updateConfirmCount();
      });
  }

  /* ═══════════════════════════════════════════════════════════
     MODALE 3 : RÉGÉNÉRATION DES FACTURES (RDV fantômes)
     ═══════════════════════════════════════════════════════════ */
  function openModaleFacture(payload) {
    $("jnFactureSub").textContent = `${payload.nb} facture(s) manquante(s) à régénérer`;
    $("jnFactureNbInfo").textContent = payload.nb;
    $("jnFactureNbList").textContent = `(${payload.nb})`;
    $("jnFactureNbTotal").textContent = payload.nb;

    /* Charger la liste des RDV concernés */
    $("jnFactureList").innerHTML = `<div class="jn-list-empty">Chargement…</div>`;

    fetch(`${ENDPOINT}?action=rdv_a_facturer`)
      .then(r => r.json())
      .then(res => {
        if (!res.success) throw new Error(res.message);
        const list = $("jnFactureList");
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
                <div class="jn-list-title">${escapeHtml(rdv.id_rdv)} · ${fmtDate(rdv.date_rdv)}</div>
                <div class="jn-list-meta">${escapeHtml(nom)}</div>
              </div>
              <span class="jn-list-tag ${tag}">${escapeHtml(rdv.type_consultation || "—")}</span>
            </div>`;
        }).join("");
      })
      .catch(err => {
        $("jnFactureList").innerHTML =
          `<div class="jn-list-empty" style="color:var(--red);">Erreur : ${escapeHtml(err.message)}</div>`;
      });

    recalculerCoutFacture();
    openAnyModale("jnModalFactureBackdrop");
  }

  function recalculerCoutFacture() {
    const m = parseFloat($("jnFactureMontant").value) || 0;
    const nb = parseInt($("jnFactureNbTotal").textContent, 10) || 0;
    const total = m * nb;
    $("jnFactureTotal").textContent = total.toFixed(0) + " DT";
  }

  function executerRegenerationFactures() {
    const m = parseFloat($("jnFactureMontant").value) || 0;
    if (m <= 0) {
      afficherToast("✗ Montant invalide", "error");
      return;
    }
    const btn = $("jnBtnFactureAction");
    btn.disabled = true;
    $("jnBtnFactureLabel").textContent = "Génération…";

    const fd = new FormData();
    fd.append("montant", m);

    fetch(`${ENDPOINT}?action=regenerer_factures`, { method: "POST", body: fd })
      .then(r => r.json())
      .then(res => {
        if (!res.success) throw new Error(res.message);
        afficherToast(`✓ ${res.crees} facture(s) générée(s) · Total ${res.total.toFixed(0)} DT`, "success");
        closeAnyModale("jnModalFactureBackdrop");
        chargerAlertes();
      })
      .catch(err => {
        afficherToast("✗ " + (err.message || "Erreur"), "error");
        btn.disabled = false;
        $("jnBtnFactureLabel").textContent = "Générer les factures";
      });
  }

  /* ═══════════════════════════════════════════════════════════
     MODALE 4 : SUPPRESSION CRÉNEAUX MORTS
     ═══════════════════════════════════════════════════════════ */
  function openModaleDelete(payload) {
    $("jnDeleteSub").textContent = `Heure ${payload.heure} · ${payload.nb_total} occurrences`;
    $("jnDeleteHeure").textContent = payload.heure;
    $("jnDeleteList").innerHTML = `<div class="jn-list-empty">Chargement…</div>`;
    $("jnDeleteCount").textContent = "0";
    $("jnBtnDeleteAction").disabled = true;
    $("jnBtnDeleteLabel").textContent = "Supprimer 0 créneau";

    fetch(`${ENDPOINT}?action=creneaux_morts&heure=${encodeURIComponent(payload.heure)}`)
      .then(r => r.json())
      .then(res => {
        if (!res.success) throw new Error(res.message);
        const list = $("jnDeleteList");
        if (!res.data || res.data.length === 0) {
          list.innerHTML = `<div class="jn-list-empty">Aucun créneau à supprimer.</div>`;
          return;
        }
        list.innerHTML = res.data.map(c => `
          <label class="jn-list-item">
            <input type="checkbox" value="${escapeHtml(c.id_creneau)}" checked />
            <div class="jn-list-main">
              <div class="jn-list-title">${escapeHtml(c.id_creneau)} · ${fmtDate(c.date_creneau)}</div>
              <div class="jn-list-meta">
                ${fmtHeure(c.heure_debut)} – ${fmtHeure(c.heure_fin)} ·
                ${escapeHtml((c.medecin || "—").trim() || "—")}
              </div>
            </div>
          </label>`).join("");

        list.querySelectorAll('input[type="checkbox"]').forEach(cb =>
          cb.addEventListener("change", updateDeleteCount));
        updateDeleteCount();
      })
      .catch(err => {
        $("jnDeleteList").innerHTML =
          `<div class="jn-list-empty" style="color:var(--red);">Erreur : ${escapeHtml(err.message)}</div>`;
      });

    openAnyModale("jnModalDeleteBackdrop");
  }

  function updateDeleteCount() {
    const checked = document.querySelectorAll('#jnDeleteList input:checked').length;
    $("jnDeleteCount").textContent = checked;
    $("jnBtnDeleteAction").disabled = checked === 0;
    $("jnBtnDeleteLabel").textContent = `Supprimer ${checked} créneau${checked > 1 ? "x" : ""}`;
  }

  function executerSuppressionCreneaux() {
    const ids = Array.from(document.querySelectorAll('#jnDeleteList input:checked'))
                     .map(cb => cb.value);
    if (ids.length === 0) return;

    const btn = $("jnBtnDeleteAction");
    btn.disabled = true;
    $("jnBtnDeleteLabel").textContent = "Suppression…";

    const fd = new FormData();
    ids.forEach(id => fd.append("ids[]", id));

    fetch(`${ENDPOINT}?action=supprimer_creneaux_morts`, { method: "POST", body: fd })
      .then(r => r.json())
      .then(res => {
        if (!res.success) throw new Error(res.message);
        afficherToast(`✓ ${res.nb} créneau(x) supprimé(s)`, "success");
        closeAnyModale("jnModalDeleteBackdrop");
        chargerAlertes();
        if (typeof chargerCreneauxDepuisBD === "function") chargerCreneauxDepuisBD();
      })
      .catch(err => {
        afficherToast("✗ " + (err.message || "Erreur"), "error");
        btn.disabled = false;
        updateDeleteCount();
      });
  }

  /* ═══════════════════════════════════════════════════════════
     MODALE 5 : REDISTRIBUTION RDV (médecin surchargé)
     ═══════════════════════════════════════════════════════════ */
  function openModaleRedist(payload) {
    /* La date arrive du PHP au format ISO YYYY-MM-DD (ex: "2026-05-05").
       On l'affiche au format français pour l'utilisateur, et on l'envoie
       telle quelle au prochain endpoint SQL. */
    const dateIso = payload.date;             // déjà au format SQL
    const dateFr  = fmtDate(payload.date);    // pour affichage humain

    $("jnRedistSub").textContent = `${payload.medecin_nom} · ${payload.nb_reserves} RDV le ${dateFr}`;
    $("jnRedistMedecin").textContent = payload.medecin_nom;
    $("jnRedistList").innerHTML = `<div class="jn-list-empty">Chargement…</div>`;
    $("jnRedistCount").textContent = "0";
    $("jnBtnRedistAction").disabled = true;
    $("jnBtnRedistLabel").textContent = "Reporter 0 RDV";

    fetch(`${ENDPOINT}?action=rdv_redistribuables&medecin=${encodeURIComponent(payload.id_medecin)}&date=${dateIso}`)
      .then(r => r.json())
      .then(res => {
        if (!res.success) throw new Error(res.message);
        const list = $("jnRedistList");
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
                <div class="jn-list-meta">${escapeHtml(nom)}</div>
              </div>
              <span class="jn-list-tag ${tag}">${escapeHtml(rdv.type_consultation || "—")}</span>
            </label>`;
        }).join("");

        list.querySelectorAll('input[type="checkbox"]').forEach(cb =>
          cb.addEventListener("change", updateRedistCount));
      })
      .catch(err => {
        $("jnRedistList").innerHTML =
          `<div class="jn-list-empty" style="color:var(--red);">Erreur : ${escapeHtml(err.message)}</div>`;
      });

    openAnyModale("jnModalRedistBackdrop");
  }

  function updateRedistCount() {
    const checked = document.querySelectorAll('#jnRedistList input:checked').length;
    $("jnRedistCount").textContent = checked;
    $("jnBtnRedistAction").disabled = checked === 0;
    $("jnBtnRedistLabel").textContent = `Reporter ${checked} RDV`;
  }

  function executerRedistribution() {
    const ids = Array.from(document.querySelectorAll('#jnRedistList input:checked'))
                     .map(cb => cb.value);
    if (ids.length === 0) return;

    const btn = $("jnBtnRedistAction");
    btn.disabled = true;
    $("jnBtnRedistLabel").textContent = "Report en cours…";

    const fd = new FormData();
    ids.forEach(id => fd.append("ids[]", id));

    fetch(`${ENDPOINT}?action=redistribuer_rdv`, { method: "POST", body: fd })
      .then(r => r.json())
      .then(res => {
        if (!res.success) throw new Error(res.message);
        afficherToast(`✓ ${res.nb_rdv} RDV reporté(s) · ${res.nb_creneaux} créneau(x) libéré(s)`, "success");
        closeAnyModale("jnModalRedistBackdrop");
        chargerAlertes();
        if (typeof chargerRdvDepuisBD === "function") chargerRdvDepuisBD();
        if (typeof chargerCreneauxDepuisBD === "function") chargerCreneauxDepuisBD();
      })
      .catch(err => {
        afficherToast("✗ " + (err.message || "Erreur"), "error");
        btn.disabled = false;
        updateRedistCount();
      });
  }

  /* ═══════════════════════════════════════════════════════════
     MODALE 6 : PROFIL DÉTAILLÉ (médecin sous-utilisé)
     ═══════════════════════════════════════════════════════════ */
  function openModaleProfil(payload) {
    $("jnProfilTitle").textContent = `Profil de ${payload.medecin_nom}`;
    $("jnProfilSub").textContent   = `Spécialité : ${payload.specialite || "—"}`;
    $("jnProfilBody").innerHTML = `
      <div class="jn-list-empty">
        <div style="display:flex; align-items:center; justify-content:center; gap:8px;">
          <div class="jn-spinner"></div>
          Analyse en cours…
        </div>
      </div>`;

    openAnyModale("jnModalProfilBackdrop");

    fetch(`${ENDPOINT}?action=profil_medecin&id=${encodeURIComponent(payload.id_medecin)}`)
      .then(r => r.json())
      .then(res => {
        if (!res.success) throw new Error(res.message);
        renderProfil(res, payload);
      })
      .catch(err => {
        $("jnProfilBody").innerHTML =
          `<div class="jn-list-empty" style="color:var(--red);">Erreur : ${escapeHtml(err.message)}</div>`;
      });
  }

  function renderProfil(data, payload) {
    const m  = data.medecin;
    const s  = data.stats;
    const ev = data.evolution || [];
    const cr = data.creneaux_libres || [];
    const sg = data.suggestions || [];

    /* Classe CSS du big number selon taux */
    const classTaux = s.taux < 20 ? 'bad' : (s.taux < 50 ? 'warn' : 'good');

    /* Mini bar chart : on prend la valeur max pour normaliser */
    const maxTaux = Math.max(...ev.map(e => e.taux), 100);
    const sparkHtml = ev.length === 0
      ? `<div style="color:var(--muted); font-size:.8rem; padding:14px 0;">Pas de données d'évolution.</div>`
      : `
        <div class="jn-spark">
          ${ev.map(e => `
            <div class="jn-spark-bar" style="height:${(e.taux / maxTaux) * 100}%;">
              <div class="jn-spark-tooltip">${e.periode} : ${e.taux}% (${e.reserves}/${e.total})</div>
            </div>`).join("")}
        </div>
        <div class="jn-spark-labels">
          ${ev.map(e => `<span>${e.periode}</span>`).join("")}
        </div>`;

    /* Liste créneaux libres */
    const crHtml = cr.length === 0
      ? `<div class="jn-list-empty">Aucun créneau libre dans les 7 prochains jours.</div>`
      : cr.map(c => `
          <div class="jn-list-item" style="cursor:default;">
            <div class="jn-list-main">
              <div class="jn-list-title">${escapeHtml(c.id_creneau)} · ${fmtDate(c.date_creneau)}</div>
              <div class="jn-list-meta">${fmtHeure(c.heure_debut)} – ${fmtHeure(c.heure_fin)}</div>
            </div>
          </div>`).join("");

    /* Suggestions */
    const icons = {
      'megaphone':     '<path d="M3 11l18-5v12L3 13"/><path d="M11.6 16.8a3 3 0 11-5.8-1.6"/>',
      'trending-down': '<polyline points="23 18 13.5 8.5 8.5 13.5 1 6"/><polyline points="17 18 23 18 23 12"/>',
      'scissors':      '<circle cx="6" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><line x1="20" y1="4" x2="8.12" y2="15.88"/><line x1="14.47" y1="14.48" x2="20" y2="20"/><line x1="8.12" y1="8.12" x2="12" y2="12"/>',
      'star':          '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>',
      'check':         '<path d="M5 13l4 4L19 7"/>'
    };
    const sgHtml = sg.map(s => `
      <div class="jn-suggestion">
        <div class="jn-suggestion-icon">
          <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
            ${icons[s.icone] || icons['check']}
          </svg>
        </div>
        <div class="jn-suggestion-text">
          <div class="jn-suggestion-title">${escapeHtml(s.titre)}</div>
          <div class="jn-suggestion-detail">${escapeHtml(s.detail)}</div>
        </div>
      </div>`).join("");

    $("jnProfilBody").innerHTML = `
      <div class="jn-modal-info">
        <strong>${escapeHtml((m.Prenom || '') + ' ' + (m.Nom || ''))}</strong>
        — ${escapeHtml(m.Service || '—')}
        <br>${escapeHtml(m.Email || '')} · ID : ${escapeHtml(m.id_user)}
      </div>

      <div class="jn-profil-grid">
        <div class="jn-stat-card">
          <span class="jn-stat-label">Taux d'occupation (30j)</span>
          <span class="jn-stat-big ${classTaux}">${s.taux}%</span>
          <span class="jn-stat-detail">${s.nb_reserves} réservés / ${s.nb_total} créneaux</span>
        </div>
        <div class="jn-stat-card">
          <span class="jn-stat-label">Créneaux libres restants</span>
          <span class="jn-stat-big">${s.nb_libres}</span>
          <span class="jn-stat-detail">Sur 30 derniers jours</span>
        </div>
      </div>

      <div class="jn-bar-compare">
        <div style="font-size:.75rem; color:var(--muted); margin-bottom:8px; font-weight:600; text-transform:uppercase; letter-spacing:.04em;">
          Comparaison avec la spécialité
        </div>
        <div class="jn-bar-row">
          <span class="jn-bar-label">${escapeHtml((m.Prenom || '').split(' ')[0])}</span>
          <div class="jn-bar-track"><div class="jn-bar-fill bad" style="width:${s.taux}%"></div></div>
          <span class="jn-bar-value">${s.taux}%</span>
        </div>
        <div class="jn-bar-row">
          <span class="jn-bar-label">Moyenne ${escapeHtml(m.Service || 'spé')}</span>
          <div class="jn-bar-track"><div class="jn-bar-fill good" style="width:${s.moyenne_spe}%"></div></div>
          <span class="jn-bar-value">${s.moyenne_spe}%</span>
        </div>
      </div>

      <div class="jn-section-title">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
          <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
        </svg>
        Évolution sur 30 jours
      </div>
      ${sparkHtml}

      <div class="jn-section-title">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
          <rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>
        </svg>
        Créneaux libres à venir (7 jours)
      </div>
      <div class="jn-list" style="max-height:160px;">
        ${crHtml}
      </div>

      <div class="jn-section-title">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
          <path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/>
        </svg>
        Recommandations du Jumeau Numérique
      </div>
      ${sgHtml}
    `;
  }

  /* Calculer combien de créneaux seront générés en direct */
  function recalculerApercu() {
    const debut = $("jnInputDebut").value;
    const fin   = $("jnInputFin").value;
    const duree = parseInt($("jnInputDuree").value, 10);
    const pD    = $("jnInputPauseDebut").value;
    const pF    = $("jnInputPauseFin").value;
    const med   = $("jnSelectMedecin").value;

    if (!debut || !fin || !duree || debut >= fin) {
      $("jnPreviewNum").textContent  = "0";
      $("jnPreviewText").innerHTML   = "Configurez la plage horaire pour voir l'aperçu.";
      $("jnBtnConfirm").disabled = true;
      return;
    }

    const toMin = (s) => { const [h,m] = s.split(":").map(Number); return h * 60 + m; };
    const debutM = toMin(debut), finM = toMin(fin);
    const pDM = pD ? toMin(pD) : null, pFM = pF ? toMin(pF) : null;

    let nb = 0, t = debutM;
    while (t + duree <= finM) {
      const dansPause = (pDM !== null && pFM !== null && t >= pDM && t < pFM);
      if (!dansPause) nb++;
      t += duree;
    }

    $("jnPreviewNum").textContent = nb;
    if (nb === 0) {
      $("jnPreviewText").innerHTML = "Aucun créneau ne sera généré avec cette configuration.";
      $("jnBtnConfirm").disabled = true;
    } else {
      $("jnPreviewText").innerHTML =
        `<strong>${nb} créneau(x)</strong> de ${duree} min seront générés` +
        (pD && pF ? ` (pause ${pD}–${pF} exclue)` : "") +
        (med ? `.` : `. <em>Sélectionnez un médecin pour activer le bouton.</em>`);
      $("jnBtnConfirm").disabled = !med;
    }
    $("jnBtnLabel").textContent = nb > 0
      ? `Générer ${nb} créneau${nb > 1 ? "x" : ""}`
      : "Générer les créneaux";
  }

  /* Soumission : appel POST vers l'API */
  function genererCreneaux() {
    const btn = $("jnBtnConfirm");
    btn.disabled = true;
    $("jnBtnLabel").textContent = "Génération en cours…";

    const formData = new FormData();
    formData.append("medecin",     $("jnSelectMedecin").value);
    formData.append("date",        $("jnInputDate").value);
    formData.append("debut",       $("jnInputDebut").value);
    formData.append("fin",         $("jnInputFin").value);
    formData.append("duree",       $("jnInputDuree").value);
    formData.append("pause_debut", $("jnInputPauseDebut").value);
    formData.append("pause_fin",   $("jnInputPauseFin").value);

    fetch(`${ENDPOINT}?action=generer_creneaux`, {
      method: "POST",
      body: formData
    })
      .then(r => r.json())
      .then(res => {
        if (!res.success) throw new Error(res.message || "Erreur inconnue");
        afficherToast(
          `✓ ${res.crees} créneau(x) créé(s)` +
          (res.ignores > 0 ? ` · ${res.ignores} ignoré(s) (chevauchement)` : ""),
          "success"
        );
        closeModale();
        chargerAlertes();   // refresh des alertes : l'engorgement va disparaître
        /* Si le tableau de créneaux est sur la même page, le rafraîchir aussi */
        if (typeof chargerCreneauxDepuisBD === "function") {
          chargerCreneauxDepuisBD();
        }
      })
      .catch(err => {
        afficherToast("✗ " + (err.message || "Erreur"), "error");
        btn.disabled = false;
        recalculerApercu();
      });
  }

  /* Toast simple compatible avec le système existant */
  function afficherToast(message, type) {
    if (typeof showToast === "function") {
      showToast(message, type);
      return;
    }
    /* Fallback si showToast n'existe pas */
    let cont = $("toast-container");
    if (!cont) {
      cont = document.createElement("div");
      cont.id = "toast-container";
      document.body.appendChild(cont);
    }
    const t = document.createElement("div");
    t.className = "toast " + (type || "");
    t.textContent = message;
    cont.appendChild(t);
    setTimeout(() => t.remove(), 3500);
  }

  /* ─── Initialisation ────────────────────────────────────────── */
  document.addEventListener("DOMContentLoaded", () => {
    chargerAlertes();
    setInterval(chargerAlertes, REFRESH_MS);

    const btn = $("jnRefresh");
    if (btn) btn.addEventListener("click", chargerAlertes);

    /* Modale : événements */
    $("jnModalClose").addEventListener("click", closeModale);
    $("jnBtnCancel").addEventListener("click", closeModale);
    $("jnModalBackdrop").addEventListener("click", (e) => {
      if (e.target === $("jnModalBackdrop")) closeModale();
    });
    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape") closeModale();
    });

    /* Recalcul auto de l'aperçu */
    ["jnSelectMedecin","jnInputDate","jnInputDebut","jnInputFin",
     "jnInputDuree","jnInputPauseDebut","jnInputPauseFin"].forEach(id => {
      const el = $(id);
      if (el) el.addEventListener("input", recalculerApercu);
      if (el) el.addEventListener("change", recalculerApercu);
    });

    $("jnBtnConfirm").addEventListener("click", genererCreneaux);

    /* ═══════════════════════════════════════════════════════
       Branchements des 4 nouvelles modales d'action
       ═══════════════════════════════════════════════════════ */

    /* Fermeture générique : tous les éléments avec data-close="..." */
    document.querySelectorAll("[data-close]").forEach(el => {
      el.addEventListener("click", () => closeAnyModale(el.dataset.close));
    });
    /* Click sur le backdrop (zone sombre) ferme la modale */
    ["jnModalConfirmBackdrop","jnModalFactureBackdrop",
     "jnModalDeleteBackdrop","jnModalRedistBackdrop","jnModalProfilBackdrop"].forEach(id => {
      const m = $(id);
      if (m) m.addEventListener("click", e => { if (e.target === m) closeAnyModale(id); });
    });
    /* Echap ferme toutes les modales */
    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape") {
        ["jnModalConfirmBackdrop","jnModalFactureBackdrop",
         "jnModalDeleteBackdrop","jnModalRedistBackdrop","jnModalProfilBackdrop"].forEach(closeAnyModale);
      }
    });

    /* ── Modale CONFIRMATION (patient à risque) ────────────── */
    $("jnConfirmAll").addEventListener("click", () => {
      document.querySelectorAll('#jnConfirmList input').forEach(cb => cb.checked = true);
      updateConfirmCount();
    });
    $("jnConfirmNone").addEventListener("click", () => {
      document.querySelectorAll('#jnConfirmList input').forEach(cb => cb.checked = false);
      updateConfirmCount();
    });
    $("jnBtnConfirmAction").addEventListener("click", executerMarquageConfirm);

    /* ── Modale FACTURE (RDV fantômes) ─────────────────────── */
    $("jnFactureMontant").addEventListener("input", recalculerCoutFacture);
    $("jnBtnFactureAction").addEventListener("click", executerRegenerationFactures);

    /* ── Modale SUPPRESSION (créneaux morts) ───────────────── */
    $("jnDeleteAll").addEventListener("click", () => {
      document.querySelectorAll('#jnDeleteList input').forEach(cb => cb.checked = true);
      updateDeleteCount();
    });
    $("jnDeleteNone").addEventListener("click", () => {
      document.querySelectorAll('#jnDeleteList input').forEach(cb => cb.checked = false);
      updateDeleteCount();
    });
    $("jnBtnDeleteAction").addEventListener("click", executerSuppressionCreneaux);

    /* ── Modale REDISTRIBUTION (médecin surchargé) ─────────── */
    $("jnRedistAll").addEventListener("click", () => {
      document.querySelectorAll('#jnRedistList input').forEach(cb => cb.checked = true);
      updateRedistCount();
    });
    $("jnRedistNone").addEventListener("click", () => {
      document.querySelectorAll('#jnRedistList input').forEach(cb => cb.checked = false);
      updateRedistCount();
    });
    $("jnBtnRedistAction").addEventListener("click", executerRedistribution);
  });
})();

