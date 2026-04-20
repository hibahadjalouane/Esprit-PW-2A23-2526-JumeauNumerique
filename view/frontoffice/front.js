let slots = [];
let appointments = [];

let selectedSlotId = null;
let selectedCalendarDate = null;

/* IMPORTANT:
   Remplace PAT-001 par l'id réel du patient connecté
*/
const CURRENT_PATIENT_ID = 'PAT-001';
/* ── Helpers ── */
function formatDate(d) {
  if (!d) return '';
  return new Date(d).toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  });
}

function statusLabel(s) {
  return {
    confirme: 'Confirmé',
    'en-attente': 'En attente',
    en_attente: 'En attente',
    annule: 'Annulé'
  }[s] || s;
}

function normalizeStatus(s) {
  if (!s) return '';
  return String(s).replace('_', '-').toLowerCase();
}

/* ── Validation ── */
const RE_NAME = /^[a-zA-ZÀ-ÿ\s'\-]{3,}$/;
const RE_EMAIL = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
const RE_PHONE = /^\+216\s?[0-9]{2}\s?[0-9]{3}\s?[0-9]{3}$/;

function setFieldState(inputId, hintId, isValid, okMsg, errMsg) {
  const el = document.getElementById(inputId);
  const h = document.getElementById(hintId);

  if (el) {
    el.classList.toggle('field-valid', isValid);
    el.classList.toggle('field-invalid', !isValid);
  }

  if (h) {
    h.textContent = isValid ? okMsg : errMsg;
    h.classList.toggle('ok', isValid);
    h.classList.toggle('err', !isValid);
  }

  return isValid;
}

function validateName() {
  const v = document.getElementById('patientName').value.trim();
  if (!v) {
    return setFieldState('patientName', 'hintName', false, '', 'Nom requis');
  }
  return setFieldState(
    'patientName',
    'hintName',
    RE_NAME.test(v),
    '✓ Nom valide',
    'Lettres uniquement, minimum 3 caractères'
  );
}

function validateEmail() {
  const v = document.getElementById('patientEmail').value.trim();
  if (!v) {
    return setFieldState('patientEmail', 'hintEmail', false, '', 'Email requis');
  }
  return setFieldState(
    'patientEmail',
    'hintEmail',
    RE_EMAIL.test(v),
    '✓ Email valide',
    'Format invalide — ex : nom@domaine.com'
  );
}

function validatePhone() {
  const v = document.getElementById('patientPhone').value.trim();
  if (!v) {
    return setFieldState('patientPhone', 'hintPhone', false, '', 'Téléphone requis');
  }
  return setFieldState(
    'patientPhone',
    'hintPhone',
    RE_PHONE.test(v),
    '✓ Numéro valide',
    'Format requis : +216 XX XXX XXX'
  );
}

function validateType() {
  const v = document.getElementById('consultationType').value;
  return setFieldState(
    'consultationType',
    'hintType',
    !!v,
    '✓ Type sélectionné',
    'Veuillez choisir un type de consultation'
  );
}

function validateSelectedSlot() {
  return setFieldState(
    'selectedSlot',
    'hintSlot',
    !!selectedSlotId,
    '✓ Créneau sélectionné',
    selectedCalendarDate
      ? 'Veuillez sélectionner un créneau horaire pour cette date'
      : 'Veuillez sélectionner un créneau'
  );
}

/* ── Realtime validation ── */
document.getElementById('patientName')?.addEventListener('input', validateName);
document.getElementById('patientEmail')?.addEventListener('input', validateEmail);
document.getElementById('patientPhone')?.addEventListener('input', validatePhone);
document.getElementById('consultationType')?.addEventListener('change', validateType);

/* ── API Loaders ── */
async function loadSlots() {
  try {
    const res = await fetch('http://localhost/versionnnnn%20f/view/frontoffice/creneauuuu.php?action=charger');
    const result = await res.json();

    if (!result.success) {
      throw new Error(result.message || 'Erreur chargement créneaux');
    }

    slots = result.data || [];
    refreshCalendarAvailability();
    filterSlots();
  } catch (e) {
    console.error('loadSlots:', e);
    showToast('Erreur chargement des créneaux', 'error');
    renderSlots([]);
  }
}

async function loadAppointments() {
  try {
    const res = await fetch(
      `http://localhost/versionnnnn%20f/view/frontoffice/rdv.php?action=charger&patient=${encodeURIComponent(CURRENT_PATIENT_ID)}`
    );
    const result = await res.json();

    if (!result.success) {
      throw new Error(result.message || 'Erreur chargement rendez-vous');
    }

    appointments = (result.data || []).map(a => ({
      id: a.id_rdv,
      date: a.date_rdv,
      time: `${String(a.heure_debut).slice(0, 5)} - ${String(a.heure_fin).slice(0, 5)}`,
      doctor: a.nom_medecin || '',
      type: a.type_consultation || '',
      status: normalizeStatus(a.statut)
    }));

    renderAppointments();
  } catch (e) {
    console.error('loadAppointments:', e);
    showToast('Erreur chargement des rendez-vous', 'error');
    renderAppointments([]);
  }
}

/* ── Calendar ── */
function refreshCalendarAvailability() {
  document.querySelectorAll('.cal-day[data-date]').forEach(day => {
    const date = day.dataset.date;

    const hasAvailable = slots.some(s => s.date === date && s.available);
    const hasReservedOnly = slots.some(s => s.date === date && !s.available);

    day.classList.remove('has-slots', 'reserved-day');

    if (hasAvailable) {
      day.classList.add('has-slots');
    } else if (hasReservedOnly) {
      day.classList.add('reserved-day');
    }

    if (selectedCalendarDate === date) {
      day.classList.add('selected-date');
    }
  });
}

function updateSelectedSlotField() {
  const input = document.getElementById('selectedSlot');
  const hint = document.getElementById('hintSlot');

  if (!input) return;

  if (selectedSlotId) {
    const slot = slots.find(s => String(s.id) === String(selectedSlotId));
    if (slot) {
      input.value = `${formatDate(slot.date)} - ${slot.time} (${slot.doctor})`;
      input.classList.remove('field-invalid');
      input.classList.add('field-valid');

      if (hint) {
        hint.textContent = '✓ Créneau sélectionné';
        hint.className = 'field-hint ok';
      }
      return;
    }
  }

  input.value = '';
  input.classList.remove('field-valid');
  input.classList.add('field-invalid');

  if (hint) {
    hint.textContent = selectedCalendarDate
      ? 'Veuillez sélectionner un créneau horaire pour cette date'
      : 'Veuillez sélectionner un créneau';
    hint.className = 'field-hint err';
  }
}

function renderSlots(list) {
  const grid = document.getElementById('slotsGrid');
  const data = list || slots;

  if (!grid) return;

  if (!data.length) {
    grid.innerHTML = `
      <div class="no-data" style="grid-column: 1 / -1;">
        Aucun créneau disponible pour cette date
      </div>
    `;
    return;
  }

  grid.innerHTML = data.map(s => `
    <div class="slot-card ${s.available ? '' : 'disabled'} ${String(selectedSlotId) === String(s.id) ? 'selected' : ''}"
         data-id="${s.id}"
         onclick="selectSlot('${s.id}')">
      <span class="slot-status-badge ${s.available ? 'badge-green' : 'badge-red'}">
        ${s.available ? 'Disponible' : 'Réservé'}
      </span>
      <div class="slot-date">${formatDate(s.date)}</div>
      <div class="slot-time">${s.time}</div>
      <div class="slot-doctor">
        <i class="fa-solid fa-user-doctor" style="font-size:11px;margin-right:4px"></i>${s.doctor || ''}
      </div>
      <div class="slot-type">${s.type || ''}</div>
    </div>
  `).join('');
}

function filterSlots() {
  const searchInput = document.getElementById('searchSlot');
  const q = searchInput ? searchInput.value.toLowerCase().trim() : '';

  // garder seulement les créneaux disponibles
  let filtered = slots.filter(s => s.available);

  // filtrer par date sélectionnée
  if (selectedCalendarDate) {
    filtered = filtered.filter(s => s.date === selectedCalendarDate);
  }

  // recherche
  if (q) {
    filtered = filtered.filter(s =>
      String(s.doctor || '').toLowerCase().includes(q) ||
      String(s.type || '').toLowerCase().includes(q) ||
      String(s.time || '').toLowerCase().includes(q) ||
      String(s.date || '').includes(q)
    );
  }

  renderSlots(filtered);
}
function renderSlotsInForm(date) {
  const container = document.getElementById('slotPickerInForm');
  const list = document.getElementById('slotPickerList');
  if (!container || !list) return;

const dateSlots = slots.filter(s => s.date === date && s.available);
  if (!date || !dateSlots.length) {
    container.style.display = 'none';
    return;
  }

  container.style.display = 'block';

  const dateLabel = new Date(date + 'T00:00:00').toLocaleDateString('fr-FR', {
    day: 'numeric', month: 'long', year: 'numeric'
  });

  list.innerHTML = dateSlots.map(s => {
    const avail = s.available;
    const isSel = String(selectedSlotId) === String(s.id);
    const borderColor = isSel ? '#2563eb' : '#e5e7eb';
    const bg = isSel ? '#eff6ff' : (avail ? '#fff' : '#f9fafb');
    const badgeBg = avail ? '#dcfce7' : '#fee2e2';
    const badgeColor = avail ? '#16a34a' : '#dc2626';
    const badgeText = isSel ? '✓ Sélectionné' : (avail ? 'Disponible' : 'Réservé');

    return '<div'
      + (avail ? ' onclick="pickSlotFromForm(\'' + s.id + '\')"' : '')
      + ' style="background:' + bg + ';border:1.5px solid ' + borderColor + ';border-radius:10px;padding:12px 14px;cursor:' + (avail ? 'pointer' : 'not-allowed') + ';opacity:' + (avail ? '1' : '.6') + ';box-shadow:' + (isSel ? '0 0 0 3px rgba(37,99,235,.18)' : '0 1px 4px rgba(0,0,0,.06)') + ';margin-bottom:0;position:relative;">'
      + '<span style="position:absolute;top:10px;right:12px;padding:3px 10px;border-radius:20px;font-size:.7rem;font-weight:600;background:' + badgeBg + ';color:' + badgeColor + ';">' + badgeText + '</span>'
      + '<div style="font-size:.72rem;color:#6b7280;margin-bottom:4px;">' + dateLabel + '</div>'
      + '<div style="font-size:1.1rem;font-weight:800;color:#111827;margin-bottom:6px;letter-spacing:.3px;">' + s.time + '</div>'
      + '<div style="font-size:.78rem;color:#2563eb;font-weight:600;display:flex;align-items:center;gap:5px;">'
      + '<i class="fa-solid fa-user-doctor" style="font-size:11px;"></i>'
      + (s.doctor || '')
      + '</div>'
      + (s.type ? '<div style="font-size:.73rem;color:#6b7280;margin-top:3px;">' + s.type + '</div>' : '')
      + '</div>';
  }).join('');
}
function pickSlotFromForm(id) {
  selectSlot(id);
  // Re-render to update selection highlight
  if (selectedCalendarDate) renderSlotsInForm(selectedCalendarDate);
}

function selectCalendarDate(date, el) {
  selectedCalendarDate = date;
  selectedSlotId = null;

  document.querySelectorAll('.cal-day[data-date]').forEach(day => {
    day.classList.remove('selected-date');
  });

  if (el) el.classList.add('selected-date');

  document.querySelectorAll('.slot-card').forEach(card => {
    card.classList.remove('selected');
  });

  filterSlots();
  renderSlotsInForm(date);
  updateSelectedSlotField();
  validateSelectedSlot();
  showToast('Date sélectionnée — choisissez un créneau horaire', 'success');
}

function selectSlot(id) {
  const slot = slots.find(s => String(s.id) === String(id));
  if (!slot || !slot.available) return;

  selectedSlotId = id;
  selectedCalendarDate = slot.date;

  document.querySelectorAll('.slot-card').forEach(c => c.classList.remove('selected'));
  const selectedCard = document.querySelector(`[data-id="${id}"]`);
  if (selectedCard) selectedCard.classList.add('selected');

  document.querySelectorAll('.cal-day[data-date]').forEach(day => {
    day.classList.remove('selected-date');
    if (day.dataset.date === slot.date) {
      day.classList.add('selected-date');
    }
  });

  updateSelectedSlotField();
  validateSelectedSlot();
  showToast('Créneau sélectionné', 'success');
}

document.getElementById('searchSlot')?.addEventListener('input', filterSlots);

document.querySelectorAll('.cal-day[data-date]').forEach(day => {
  day.addEventListener('click', function () {
    selectCalendarDate(this.dataset.date, this);
  });
});

/* ── Appointments table ── */
function renderAppointments(list) {
  const tbody = document.getElementById('rdvTableBody');
  const data = list || appointments;
  const count = document.getElementById('rdvCount');

  if (count) count.textContent = `${data.length} rendez-vous`;

  if (!tbody) return;

  tbody.innerHTML = data.length
    ? data.map(a => `
      <tr>
        <td class="id-cell">${a.id}</td>
        <td>${formatDate(a.date)}</td>
        <td>${a.time}</td>
        <td>${a.doctor}</td>
        <td>${a.type}</td>
        <td><span class="status-badge ${a.status}">${statusLabel(a.status)}</span></td>
        <td>
          <div class="actions">
            <div class="action-btn" title="Voir détails" onclick="showDetail('${a.id}')">
              <i class="fa-solid fa-eye"></i>
            </div>
            ${a.status !== 'annule'
              ? `<div class="action-btn cancel" title="Annuler" onclick="cancelAppointment('${a.id}')">
                   <i class="fa-solid fa-xmark"></i>
                 </div>`
              : ''}
          </div>
        </td>
      </tr>
    `).join('')
    : `<tr><td colspan="7" class="no-data">Aucun rendez-vous trouvé</td></tr>`;
}

/* ── Modal detail ── */
function showDetail(id) {
  const a = appointments.find(x => x.id === id);
  if (!a) return;

  let modal = document.getElementById('rdvDetailModal');

  if (!modal) {
    modal = document.createElement('div');
    modal.id = 'rdvDetailModal';
    modal.style.cssText = `
      position:fixed; inset:0; z-index:9999; background:rgba(0,0,0,.35);
      display:flex; align-items:center; justify-content:center;
    `;
    modal.innerHTML = `
      <div style="background:#fff;border-radius:12px;padding:22px;min-width:320px;max-width:420px;width:90%;
                  box-shadow:0 8px 32px rgba(0,0,0,.18);font-family:inherit">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
          <strong style="font-size:.95rem">Détails du rendez-vous</strong>
          <span style="cursor:pointer;font-size:18px;color:#6b7280;line-height:1"
                onclick="document.getElementById('rdvDetailModal').remove()">✕</span>
        </div>
        <div id="rdvDetailBody"></div>
      </div>
    `;
    modal.addEventListener('click', e => {
      if (e.target === modal) modal.remove();
    });
    document.body.appendChild(modal);
  }

  const rows = [
    ['ID', a.id],
    ['Date', formatDate(a.date)],
    ['Heure', a.time],
    ['Médecin', a.doctor],
    ['Type', a.type],
    ['Statut', statusLabel(a.status)]
  ];

  document.getElementById('rdvDetailBody').innerHTML = rows.map(([label, val]) => `
    <div style="display:flex;gap:12px;padding:8px 0;border-bottom:1px solid #dde2ef;font-size:.82rem">
      <span style="color:#6b7280;min-width:80px;font-weight:500">${label}</span>
      <span style="color:#111827">${val}</span>
    </div>
  `).join('');
}

/* ── Search / Filter appointments ── */
document.getElementById('searchRdv')?.addEventListener('input', function () {
  const q = this.value.toLowerCase();
  renderAppointments(appointments.filter(a =>
    String(a.id).toLowerCase().includes(q) ||
    String(a.doctor).toLowerCase().includes(q) ||
    String(a.type).toLowerCase().includes(q)
  ));
});

document.getElementById('filterRdv')?.addEventListener('change', function () {
  const v = this.value;
  renderAppointments(v ? appointments.filter(a => a.status === v) : appointments);
});

/* ── Booking ── */
function generateRdvId() {
  return 'RDV-' + Date.now();
}

document.getElementById('btnBook')?.addEventListener('click', async function () {
  const okName = validateName();
  const okEmail = validateEmail();
  const okPhone = validatePhone();
  const okType = validateType();
  const okSlot = validateSelectedSlot();

  if (!okName || !okEmail || !okPhone || !okType || !okSlot) {
    showToast('Corrigez les erreurs avant de continuer', 'error');
    return;
  }

  const selectedSlot = slots.find(s => String(s.id) === String(selectedSlotId));
  if (!selectedSlot) {
    showToast('Créneau introuvable', 'error');
    return;
  }

  const spinner = document.getElementById('bookSpinner');
  const icon = document.getElementById('bookIcon');
  const text = document.getElementById('bookText');

  spinner.style.display = 'block';
  icon.style.display = 'none';
  text.textContent = 'Traitement...';
  this.disabled = true;

  try {
    const formData = new URLSearchParams();
    formData.append('action', 'ajouter');
    formData.append('patient', CURRENT_PATIENT_ID);
    formData.append('date_demande', new Date().toISOString().slice(0, 10));
    formData.append('type', document.getElementById('consultationType').value);
    formData.append('medecin', selectedSlot.id_medecin);
    formData.append('creneau', selectedSlot.id);

    const response = await fetch('http://localhost/versionnnnn%20f/view/frontoffice/rdv.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: formData.toString()
    });

    const result = await response.json();

    if (!result.success) {
      showToast(result.message || 'Erreur réservation', 'error');
      return;
    }

    showToast('Réservation enregistrée avec succès', 'success');

    selectedSlotId = null;
    selectedCalendarDate = null;

    document.getElementById('selectedSlot').value = '';
    const searchSlot = document.getElementById('searchSlot');
    if (searchSlot) searchSlot.value = '';

    ['patientName', 'patientEmail', 'patientPhone', 'consultationType', 'notes'].forEach(id => {
      const el = document.getElementById(id);
      if (el) {
        el.value = '';
        el.classList.remove('field-valid', 'field-invalid');
      }
    });

    ['hintName', 'hintEmail', 'hintPhone', 'hintType', 'hintSlot'].forEach(id => {
      const el = document.getElementById(id);
      if (el) {
        el.textContent = '';
        el.className = 'field-hint';
      }
    });

    document.querySelectorAll('.slot-card').forEach(c => c.classList.remove('selected'));
    document.querySelectorAll('.cal-day[data-date]').forEach(c => c.classList.remove('selected-date'));

    await loadSlots();
    await loadAppointments();
    validateSelectedSlot();
  } catch (e) {
    console.error(e);
    showToast('Erreur serveur', 'error');
  } finally {
    spinner.style.display = 'none';
    icon.style.display = 'block';
    text.textContent = 'Confirmer la réservation';
    this.disabled = false;
  }
});

/* ── Cancel appointment ── */
async function cancelAppointment(id) {
  if (!confirm('Annuler ce rendez-vous ?')) return;

  try {
    const formData = new URLSearchParams();
    formData.append('action', 'annuler');
    formData.append('id', id);
    formData.append('patient', CURRENT_PATIENT_ID);

    const response = await fetch('http://localhost/versionnnnn%20f/view/frontoffice/rdv.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: formData.toString()
    });

    const result = await response.json();

    if (!result.success) {
      showToast(result.message || 'Erreur annulation', 'error');
      return;
    }

    showToast('Rendez-vous annulé', 'success');
    await loadSlots();
    await loadAppointments();
  } catch (e) {
    console.error(e);
    showToast('Erreur serveur', 'error');
  }
}

/* ── Toast ── */
function showToast(msg, type) {
  const c = document.getElementById('toast-container');
  if (!c) return;

  const t = document.createElement('div');
  t.className = `toast ${type}`;
  t.textContent = msg;
  c.appendChild(t);

  setTimeout(() => {
    t.style.opacity = '0';
    t.style.transform = 'translateY(10px)';
    t.style.transition = 'all .3s';
    setTimeout(() => t.remove(), 300);
  }, 3000);
}

/* ── Init ── */
loadSlots();
loadAppointments();
validateSelectedSlot();
