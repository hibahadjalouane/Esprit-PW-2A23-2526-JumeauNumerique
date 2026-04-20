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

let pageActuelle = 1;
const ROWS = 4;

function mettreAJourTableau() {
  const rows = Array.from(document.querySelectorAll("#rdvTableBody tr"));
  const dataRows = rows.filter(r => r.dataset.id);
  if (!dataRows.length) {
    const countEl = document.getElementById("rdvCount");
    if (countEl) countEl.textContent = "Affichage de 0 entrée";
    return;
  }
  dataRows.forEach((r, i) => {
    r.style.display = i >= (pageActuelle - 1) * ROWS && i < pageActuelle * ROWS ? "" : "none";
  });
  const countEl = document.getElementById("rdvCount");
  if (countEl) countEl.textContent = `Affichage de ${dataRows.length} entr${dataRows.length > 1 ? "ées" : "ée"}`;
}

function activerPagination() {
  const boutons = document.querySelectorAll(".page-number");
  boutons.forEach((btn, index) => {
    btn.addEventListener("click", function () {
      pageActuelle = index + 1;
      mettreAJourTableau();
      boutons.forEach(b => b.classList.remove("active"));
      this.classList.add("active");
    });
  });
}

activerPagination();

document.getElementById("dateDemande").value = todayStr();
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