/* ═══════════════════════════════════════════════════════════
   AUREA — Patient Portal
   Connects to creneauuuu.php and rdv.php
   ═══════════════════════════════════════════════════════════ */

const API_BASE     = '.';
const CRENEAU_API  = `${API_BASE}/creneauuuu.php`;
const RDV_API      = `${API_BASE}/rdv.php`;

// TODO: wire from PHP session
const CURRENT_PATIENT_ID   = (window.CURRENT_PATIENT_ID   || 'PAT-001');
const CURRENT_PATIENT_NAME = (window.CURRENT_PATIENT_NAME || 'Elena Martinez');

/* ─── STATE ──────────────────────────────────────────────── */
let allDoctors = [];
let allSlots   = [];
let appointments = [];

let selectedSpecialty = null;
let selectedDoctorId  = null;
let selectedDate      = null;
let selectedSlotId    = null;

let currentStep   = 1;
let calendarYear  = new Date().getFullYear();
let calendarMonth = new Date().getMonth();

/* ─── HELPERS ────────────────────────────────────────────── */
const $ = (id) => document.getElementById(id);

function fmtDateFR(d) {
  if (!d) return '';
  return new Date(d + 'T00:00:00').toLocaleDateString('fr-FR', {
    day: 'numeric', month: 'long', year: 'numeric'
  });
}
function fmtShort(d) {
  if (!d) return '';
  return new Date(d + 'T00:00:00').toLocaleDateString('fr-FR', {
    day: '2-digit', month: 'short'
  });
}
function statusLabel(s) {
  return ({ confirme:'Confirmé', 'en-attente':'En attente', en_attente:'En attente', annule:'Annulé' })[s] || s;
}
function normalize(s) {
  return s ? String(s).replace('_','-').toLowerCase() : '';
}
function initials(name) {
  if (!name) return '?';
  return name.trim().split(/\s+/).map(w => w[0]).slice(0,2).join('').toUpperCase();
}
function todayISO() { return new Date().toISOString().slice(0,10); }

/* ═══════════════════════════════════════════════════════════
   THEME TOGGLE
   ═══════════════════════════════════════════════════════════ */
function initTheme() {
  const stored = localStorage.getItem('aurea-theme');
  const prefers = window.matchMedia('(prefers-color-scheme: dark)').matches;
  const theme = stored || (prefers ? 'dark' : 'light');
  document.documentElement.setAttribute('data-theme', theme);
}
$('themeToggle')?.addEventListener('click', () => {
  const cur = document.documentElement.getAttribute('data-theme');
  const next = cur === 'light' ? 'dark' : 'light';
  document.documentElement.setAttribute('data-theme', next);
  localStorage.setItem('aurea-theme', next);
});

/* ═══════════════════════════════════════════════════════════
   TOAST
   ═══════════════════════════════════════════════════════════ */
const ICONS = {
  success: 'check-circle-2',
  error:   'alert-circle',
  info:    'info'
};
function toast(msg, type = 'info') {
  const c = $('toast-container');
  if (!c) return;
  const t = document.createElement('div');
  t.className = `toast ${type}`;
  t.innerHTML = `
    <span class="ico-wrap"><i data-lucide="${ICONS[type]||'info'}" style="width:14px;height:14px"></i></span>
    <span>${msg}</span>`;
  c.appendChild(t);
  refreshIcons();
  setTimeout(() => {
    t.style.opacity = '0';
    t.style.transform = 'translateX(20px)';
    t.style.transition = 'all .3s';
    setTimeout(() => t.remove(), 300);
  }, 3500);
}

/* ═══════════════════════════════════════════════════════════
   ANIMATED COUNTER
   ═══════════════════════════════════════════════════════════ */
function animateCounter(el, target, duration = 1200) {
  if (!el) return;
  const start = parseFloat(el.dataset.counter || '0');
  const startTime = performance.now();
  const tick = (now) => {
    const t = Math.min((now - startTime) / duration, 1);
    const eased = 1 - Math.pow(1 - t, 3); // easeOutCubic
    const value = Math.round(start + (target - start) * eased);
    el.textContent = String(value).padStart(2, '0');
    if (t < 1) requestAnimationFrame(tick);
    else el.dataset.counter = target;
  };
  requestAnimationFrame(tick);
}

/* ═══════════════════════════════════════════════════════════
   SPARKLINES
   ═══════════════════════════════════════════════════════════ */
function buildSparkline(svgPathId, values) {
  const path = document.getElementById(svgPathId);
  if (!path) return;
  const parent = path.closest('svg');
  const fill = parent?.querySelector('path.fill');

  if (!values.length) {
    path.setAttribute('d', '');
    if (fill) fill.setAttribute('d', '');
    return;
  }

  const W = 100, H = 32;
  const max = Math.max(...values, 1);
  const min = Math.min(...values, 0);
  const range = max - min || 1;
  const step = values.length > 1 ? W / (values.length - 1) : W;

  const points = values.map((v, i) => {
    const x = i * step;
    const y = H - ((v - min) / range) * (H - 4) - 2;
    return [x, y];
  });

  // Smooth curve via catmull-rom-ish bezier
  let d = `M ${points[0][0]} ${points[0][1]}`;
  for (let i = 1; i < points.length; i++) {
    const [x1, y1] = points[i - 1];
    const [x2, y2] = points[i];
    const cx = (x1 + x2) / 2;
    d += ` Q ${cx} ${y1} ${(cx + x2) / 2} ${(y1 + y2) / 2} T ${x2} ${y2}`;
  }
  path.setAttribute('d', d);

  // Restart draw animation
  const len = path.getTotalLength?.() || 200;
  path.style.strokeDasharray = len;
  path.style.strokeDashoffset = len;
  path.getBoundingClientRect(); // reflow
  path.style.transition = 'stroke-dashoffset 1.4s cubic-bezier(.4,0,.2,1)';
  path.style.strokeDashoffset = 0;

  if (fill) {
    fill.setAttribute('d', d + ` L ${W} ${H} L 0 ${H} Z`);
  }
}

/* ═══════════════════════════════════════════════════════════
   API
   ═══════════════════════════════════════════════════════════ */
async function loadDoctors() {
  try {
    const r = await fetch(`${CRENEAU_API}?action=medecins`);
    const j = await r.json();
    if (!j.success) throw new Error(j.message);

    allDoctors = (j.data || []).map(d => ({
      id:      d.id_user,
      name:    (d.nom_complet || '').trim() || 'Médecin',
      service: (d.Service || '').trim() || 'Général',
      // Generated demo data — feel free to replace with real fields
      rating:  (4.5 + Math.random() * 0.5).toFixed(1),
      reviews: Math.floor(50 + Math.random() * 250),
      years:   Math.floor(5 + Math.random() * 20)
    }));
    renderSpecialties();
  } catch (e) {
    console.error('loadDoctors:', e);
    toast('Erreur chargement des médecins', 'error');
    $('specialtyList').innerHTML = `<div class="empty" style="grid-column:1/-1">
      <i data-lucide="alert-triangle" style="width:32px;height:32px"></i>
      <p class="empty-title">Connexion au serveur impossible</p>
    </div>`;
    refreshIcons();
  }
}

async function loadSlots() {
  try {
    const r = await fetch(`${CRENEAU_API}?action=charger`);
    const j = await r.json();
    if (!j.success) throw new Error(j.message);
    allSlots = j.data || [];
    if (currentStep === 3) {
      buildCalendar();
      renderSlotPills();
    }
  } catch (e) {
    console.error('loadSlots:', e);
    toast('Erreur chargement des créneaux', 'error');
  }
}

async function loadAppointments() {
  try {
    const r = await fetch(`${RDV_API}?action=charger&patient=${encodeURIComponent(CURRENT_PATIENT_ID)}`);
    const j = await r.json();
    if (!j.success) throw new Error(j.message);

    appointments = (j.data || []).map(a => ({
      id:     a.id_rdv,
      date:   a.date_rdv,
      time:   `${String(a.heure_debut||'').slice(0,5)} - ${String(a.heure_fin||'').slice(0,5)}`,
      doctor: a.nom_medecin || '',
      type:   a.type_consultation || '',
      status: normalize(a.statut)
    }));

    renderAppointments();
    updateStats();
  } catch (e) {
    console.error('loadAppointments:', e);
    toast('Erreur chargement des rendez-vous', 'error');
    $('appointmentsList').innerHTML = `<div class="empty">
      <i data-lucide="alert-triangle" style="width:32px;height:32px"></i>
      <p class="empty-title">Connexion impossible</p>
    </div>`;
    refreshIcons();
  }
}

/* ═══════════════════════════════════════════════════════════
   STATS + SPARKLINES
   ═══════════════════════════════════════════════════════════ */
function updateStats() {
  const total      = appointments.length;
  const confirmed  = appointments.filter(a => a.status === 'confirme').length;
  const pending    = appointments.filter(a => a.status === 'en-attente').length;

  animateCounter($('statTotal'),     total);
  animateCounter($('statConfirmed'), confirmed);
  animateCounter($('statPending'),   pending);

  // Build sparklines from appointment dates over last 12 weeks
  const series = (filterFn) => {
    const buckets = Array(12).fill(0);
    const now = new Date();
    appointments.filter(filterFn).forEach(a => {
      const d = new Date(a.date + 'T00:00:00');
      const diffWeeks = Math.floor((now - d) / (7 * 86400000));
      const idx = 11 - Math.max(0, Math.min(11, diffWeeks));
      buckets[idx]++;
    });
    // If everything is zero, show a gentle baseline so the line isn't flat
    return buckets.some(v => v > 0) ? buckets : [1,2,1,2,3,2,3,2,3,4,3,4];
  };

  buildSparkline('spark1', series(() => true));
  buildSparkline('spark2', series(a => a.status === 'confirme'));
  buildSparkline('spark3', series(a => a.status === 'en-attente'));
}

/* ═══════════════════════════════════════════════════════════
   STEPPER NAVIGATION
   ═══════════════════════════════════════════════════════════ */
function goToStep(step) {
  if (step < 1 || step > 4) return;

  // Validate before advancing
  if (step > currentStep) {
    if (currentStep === 1 && !selectedSpecialty) { toast('Choisissez une spécialité', 'error'); return; }
    if (currentStep === 2 && !selectedDoctorId)  { toast('Choisissez un médecin', 'error'); return; }
    if (currentStep === 3 && !selectedSlotId)    { toast('Choisissez un créneau', 'error'); return; }
  }

  const oldSlide = document.querySelector(`.slide[data-slide="${currentStep}"]`);
  const newSlide = document.querySelector(`.slide[data-slide="${step}"]`);
  if (!newSlide) return;

  // Out
  oldSlide.style.opacity = '0';
  oldSlide.style.transform = 'translateX(-20px)';

  setTimeout(() => {
    oldSlide.classList.add('hidden');
    oldSlide.style.transform = '';
    newSlide.classList.remove('hidden');
    newSlide.style.opacity = '0';
    newSlide.style.transform = 'translateX(20px)';
    requestAnimationFrame(() => {
      newSlide.style.transition = 'opacity .4s, transform .4s cubic-bezier(.4,0,.2,1)';
      newSlide.style.opacity = '1';
      newSlide.style.transform = 'translateX(0)';
    });
  }, 250);

  currentStep = step;
  updateStepperUI();
  updateSummaries();

  if (step === 3) {
    buildCalendar();
    renderSlotPills();
  }
  if (step === 4) {
    renderFinalSummary();
    validateBookingButton();
  }
  refreshIcons();
}

function updateStepperUI() {
  document.querySelectorAll('.step').forEach(s => {
    const n = parseInt(s.dataset.step);
    s.classList.remove('active', 'done');
    if (n < currentStep) s.classList.add('done');
    if (n === currentStep) s.classList.add('active');
  });
}

function updateSummaries() {
  // Step 1 summary on the same slide
  $('summary1').innerHTML = selectedSpecialty
    ? `<span class="summary-chip"><i data-lucide="check" style="width:11px;height:11px"></i><strong>${selectedSpecialty}</strong></span>`
    : '<span style="color:var(--text-3); font-size:.78rem">Sélectionnez une spécialité pour continuer</span>';

  const doc = allDoctors.find(d => String(d.id) === String(selectedDoctorId));
  $('summary2').innerHTML = `
    ${selectedSpecialty ? `<span class="summary-chip"><i data-lucide="stethoscope" style="width:11px;height:11px"></i>${selectedSpecialty}</span>` : ''}
    ${doc ? `<span class="summary-chip"><i data-lucide="user-round" style="width:11px;height:11px"></i><strong>${doc.name}</strong></span>` : ''}
  `;

  const slot = allSlots.find(s => String(s.id) === String(selectedSlotId));
  $('summary3').innerHTML = `
    ${doc ? `<span class="summary-chip"><i data-lucide="user-round" style="width:11px;height:11px"></i>${doc.name}</span>` : ''}
    ${slot ? `<span class="summary-chip"><i data-lucide="calendar" style="width:11px;height:11px"></i><strong>${fmtShort(slot.date)} · ${slot.time.split(' - ')[0]}</strong></span>` : ''}
  `;

  refreshIcons();
}

document.querySelectorAll('[data-next]').forEach(b => b.addEventListener('click', () => goToStep(currentStep + 1)));
document.querySelectorAll('[data-prev]').forEach(b => b.addEventListener('click', () => goToStep(currentStep - 1)));

// Click step in stepper to jump back (forward only with validation)
document.querySelectorAll('.step').forEach(s => {
  s.addEventListener('click', () => {
    const n = parseInt(s.dataset.step);
    if (n <= currentStep) goToStep(n);
  });
});

/* ═══════════════════════════════════════════════════════════
   STEP 1: SPECIALTIES
   ═══════════════════════════════════════════════════════════ */
const SPEC_ICONS = {
  'cardiologie':    'heart-pulse',
  'dermatologie':   'sparkles',
  'pédiatrie':      'baby',
  'pediatrie':      'baby',
  'gynécologie':    'flower-2',
  'gynecologie':    'flower-2',
  'neurologie':     'brain',
  'orthopédie':     'bone',
  'orthopedie':     'bone',
  'ophtalmologie':  'eye',
  'dentaire':       'smile',
  'dentisterie':    'smile',
  'général':        'stethoscope',
  'general':        'stethoscope',
  'généraliste':    'stethoscope',
  'généralemédecine':'stethoscope',
  'urologie':       'droplet',
  'orl':            'ear',
  'psychiatrie':    'brain',
  'psychologie':    'brain'
};
function specIcon(name) {
  const k = name.toLowerCase().normalize('NFD').replace(/\p{Diacritic}/gu,'').replace(/\s+/g,'');
  for (const [key, ic] of Object.entries(SPEC_ICONS)) {
    const nk = key.normalize('NFD').replace(/\p{Diacritic}/gu,'').replace(/\s+/g,'');
    if (k.includes(nk) || nk.includes(k)) return ic;
  }
  return 'stethoscope';
}

function renderSpecialties() {
  const list = $('specialtyList');
  if (!list) return;

  const specs = [...new Set(allDoctors.map(d => d.service).filter(Boolean))].sort();

  if (!specs.length) {
    list.innerHTML = `<div class="empty" style="grid-column:1/-1">
      <i data-lucide="search-x" style="width:32px;height:32px"></i>
      <p class="empty-title">Aucune spécialité disponible</p>
    </div>`;
    refreshIcons();
    return;
  }

  list.innerHTML = specs.map(spec => {
    const count = allDoctors.filter(d => d.service === spec).length;
    const isActive = selectedSpecialty === spec;
    return `
      <button class="spec-card ${isActive ? 'active' : ''}" data-spec="${spec}">
        <div class="spec-icon"><i data-lucide="${specIcon(spec)}" style="width:18px;height:18px"></i></div>
        <div class="spec-name">${spec}</div>
        <div class="spec-count">${String(count).padStart(2,'0')} médecin${count>1?'s':''}</div>
      </button>`;
  }).join('');

  list.querySelectorAll('.spec-card').forEach(card => {
    card.addEventListener('click', () => selectSpecialty(card.dataset.spec));
  });

  refreshIcons();
}

function selectSpecialty(spec) {
  selectedSpecialty = spec;
  selectedDoctorId  = null;
  selectedDate      = null;
  selectedSlotId    = null;
  renderSpecialties();
  renderDoctors();
  updateSummaries();
}

/* ═══════════════════════════════════════════════════════════
   STEP 2: DOCTORS
   ═══════════════════════════════════════════════════════════ */
function renderDoctors() {
  const list = $('doctorList');
  if (!list) return;

  if (!selectedSpecialty) {
    list.innerHTML = `<div class="empty" style="grid-column:1/-1">
      <i data-lucide="users" style="width:32px;height:32px"></i>
      <p class="empty-title">Choisissez d'abord une spécialité</p>
    </div>`;
    refreshIcons();
    return;
  }

  const docs = allDoctors.filter(d => d.service === selectedSpecialty);
  if (!docs.length) {
    list.innerHTML = `<div class="empty" style="grid-column:1/-1">
      <i data-lucide="user-x" style="width:32px;height:32px"></i>
      <p class="empty-title">Aucun médecin pour cette spécialité</p>
    </div>`;
    refreshIcons();
    return;
  }

  list.innerHTML = docs.map(d => {
    const dId = String(d.id);
    const slotCount = allSlots.filter(s => String(s.id_medecin) === dId && s.available).length;
    const next = allSlots.filter(s => String(s.id_medecin) === dId && s.available)
      .sort((a,b) => (a.date+a.heure_debut).localeCompare(b.date+b.heure_debut))[0];
    const isActive = String(selectedDoctorId) === dId;
    return `
      <div class="doc-card ${isActive ? 'active' : ''}" data-id="${d.id}">
        <div class="doc-head">
          <div class="doc-avatar">${initials(d.name)}</div>
          <div style="flex:1; min-width:0">
            <p class="doc-name">${d.name}</p>
            <p class="doc-spec">${d.service}</p>
          </div>
        </div>
        <div class="doc-meta">
          <div class="stars">
            <i data-lucide="star" style="width:12px;height:12px;fill:currentColor"></i>
            <span style="color:var(--text); font-weight: 700">${d.rating}</span>
          </div>
          <div>${d.reviews} avis</div>
          <div>${d.years} ans</div>
        </div>
        <div class="doc-availability ${slotCount === 0 ? 'none' : ''}">
          <i data-lucide="${slotCount > 0 ? 'check-circle-2' : 'clock'}" style="width:13px;height:13px"></i>
          ${slotCount > 0
            ? `${slotCount} créneau${slotCount>1?'x':''} · prochain ${fmtShort(next.date)}`
            : 'Aucune disponibilité'}
        </div>
      </div>`;
  }).join('');

  list.querySelectorAll('.doc-card').forEach(c => {
    c.addEventListener('click', () => selectDoctor(c.dataset.id));
  });

  refreshIcons();
}

function selectDoctor(id) {
  selectedDoctorId = id == null ? null : String(id);
  selectedDate = null;
  selectedSlotId = null;
  renderDoctors();
  updateSummaries();
}

/* ═══════════════════════════════════════════════════════════
   STEP 3: CALENDAR (HEATMAP) + SLOTS
   ═══════════════════════════════════════════════════════════ */
const MONTHS_FR = ['Janvier','Février','Mars','Avril','Mai','Juin',
                   'Juillet','Août','Septembre','Octobre','Novembre','Décembre'];

function slotsForDate(date) {
  const docId = selectedDoctorId == null ? null : String(selectedDoctorId);
  return allSlots.filter(s =>
    s.date === date &&
    (!docId || String(s.id_medecin) === docId)
  );
}

function buildCalendar() {
  const grid = $('calGrid');
  const label = $('calMonth');
  if (!grid || !label) return;

  label.innerHTML = `<em>${MONTHS_FR[calendarMonth]}</em> ${calendarYear}`;

  const firstDay = new Date(calendarYear, calendarMonth, 1);
  const daysInMonth = new Date(calendarYear, calendarMonth + 1, 0).getDate();
  let startDow = firstDay.getDay() - 1;
  if (startDow < 0) startDow = 6;

  const today = todayISO();
  const cells = [];

  const prevDays = new Date(calendarYear, calendarMonth, 0).getDate();
  for (let i = startDow - 1; i >= 0; i--) {
    cells.push(`<div class="cal-day outside">${prevDays - i}</div>`);
  }

  for (let d = 1; d <= daysInMonth; d++) {
    const date = `${calendarYear}-${String(calendarMonth+1).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
    const slots = slotsForDate(date);
    const available = slots.filter(s => s.available).length;
    const total = slots.length;
    const isPast = date < today;
    const isToday = date === today;
    const isSelected = date === selectedDate;

    let heat = 0;
    let cls = '';
    if (!isPast && available > 0) {
      if (available >= 6) heat = 4;
      else if (available >= 4) heat = 3;
      else if (available >= 2) heat = 2;
      else heat = 1;
    } else if (!isPast && total > 0 && available === 0) {
      cls = 'full';
    }
    if (isPast) cls += ' past';
    if (isToday) cls += ' today';
    if (isSelected) cls += ' selected';

    cells.push(`<div class="cal-day ${cls}"
                     ${heat ? `data-heat="${heat}"` : ''}
                     data-date="${date}"
                     data-count="${available}">
      ${d}
      ${available > 0 ? `<span class="cal-day-count">${available}</span>` : ''}
    </div>`);
  }

  while (cells.length % 7 !== 0) {
    const i = cells.length - daysInMonth - startDow + 1;
    cells.push(`<div class="cal-day outside">${i}</div>`);
  }

  grid.innerHTML = cells.join('');

  grid.querySelectorAll('.cal-day[data-heat]').forEach(el => {
    el.addEventListener('click', () => selectCalDate(el.dataset.date));
  });
}

function selectCalDate(date) {
  const slots = slotsForDate(date).filter(s => s.available);
  if (!slots.length) {
    toast('Aucun créneau pour cette date', 'info');
    return;
  }
  selectedDate   = date;
  selectedSlotId = null;
  buildCalendar();
  renderSlotPills();
}

function renderSlotPills() {
  const wrap = $('slotPills');
  const hint = $('slotsHint');
  if (!wrap) return;

  if (!selectedDoctorId) {
    wrap.innerHTML = '';
    hint.textContent = '— Sélectionnez d\'abord un médecin';
    return;
  }
  if (!selectedDate) {
    wrap.innerHTML = '';
    hint.textContent = '— Sélectionnez une date dans le calendrier';
    return;
  }

  const docId = String(selectedDoctorId);
  const slots = allSlots
    .filter(s => s.date === selectedDate && String(s.id_medecin) === docId)
    .sort((a,b) => (a.heure_debut||'').localeCompare(b.heure_debut||''));

  if (!slots.length) {
    wrap.innerHTML = '';
    hint.textContent = '— Aucun créneau';
    return;
  }

  const avail = slots.filter(s => s.available).length;
  hint.innerHTML = `— <strong style="color:var(--teal)">${avail} disponible${avail>1?'s':''}</strong> · ${fmtDateFR(selectedDate)}`;

  wrap.innerHTML = slots.map(s => {
    const h = String(s.heure_debut || '').slice(0,5);
    const isSel = String(s.id) === String(selectedSlotId);
    return `<button class="pill ${isSel ? 'active' : ''}" ${!s.available ? 'disabled' : ''} data-id="${s.id}">
      <i data-lucide="clock-4" style="width:12px;height:12px"></i>${h}
    </button>`;
  }).join('');

  wrap.querySelectorAll('.pill:not(:disabled)').forEach(p => {
    p.addEventListener('click', () => {
      selectedSlotId = p.dataset.id;
      renderSlotPills();
      updateSummaries();
    });
  });

  refreshIcons();
}

$('prevMonth')?.addEventListener('click', () => {
  calendarMonth--;
  if (calendarMonth < 0) { calendarMonth = 11; calendarYear--; }
  buildCalendar();
});
$('nextMonth')?.addEventListener('click', () => {
  calendarMonth++;
  if (calendarMonth > 11) { calendarMonth = 0; calendarYear++; }
  buildCalendar();
});

/* ═══════════════════════════════════════════════════════════
   STEP 4: CONFIRMATION
   ═══════════════════════════════════════════════════════════ */
function renderFinalSummary() {
  const doc = allDoctors.find(d => String(d.id) === String(selectedDoctorId));
  const slot = allSlots.find(s => String(s.id) === String(selectedSlotId));

  $('finalSummary').innerHTML = `
    <div style="display:flex; justify-content:space-between; align-items:center;">
      <span style="color:var(--text-3); font-size:.75rem; text-transform:uppercase; letter-spacing:.06em">Spécialité</span>
      <span style="font-weight:600">${selectedSpecialty || '—'}</span>
    </div>
    <div style="display:flex; justify-content:space-between; align-items:center;">
      <span style="color:var(--text-3); font-size:.75rem; text-transform:uppercase; letter-spacing:.06em">Médecin</span>
      <span style="font-weight:600">${doc?.name || '—'}</span>
    </div>
    <div style="display:flex; justify-content:space-between; align-items:center;">
      <span style="color:var(--text-3); font-size:.75rem; text-transform:uppercase; letter-spacing:.06em">Date</span>
      <span style="font-weight:600">${slot ? fmtDateFR(slot.date) : '—'}</span>
    </div>
    <div style="display:flex; justify-content:space-between; align-items:center;">
      <span style="color:var(--text-3); font-size:.75rem; text-transform:uppercase; letter-spacing:.06em">Horaire</span>
      <span style="font-weight:600; font-family:'JetBrains Mono', monospace">${slot?.time || '—'}</span>
    </div>
  `;
}

function validateBookingButton() {
  const ok = selectedSpecialty && selectedDoctorId && selectedSlotId && $('consultationType').value;
  $('btnBook').disabled = !ok;
  return ok;
}

$('consultationType')?.addEventListener('change', () => {
  const v = $('consultationType').value;
  const hint = $('hintType');
  $('consultationType').classList.toggle('invalid', !v);
  if (hint) {
    hint.textContent = v ? '✓ Type sélectionné' : 'Veuillez choisir un type';
    hint.className = `field-hint ${v ? 'ok' : 'err'}`;
  }
  validateBookingButton();
});

/* ═══════════════════════════════════════════════════════════
   BOOK (with confetti!)
   ═══════════════════════════════════════════════════════════ */
async function fireConfetti() {
  if (typeof confetti !== 'function') return;
  const colors = ['#0d9488', '#0f3d7a', '#14b8a6', '#1d5fb3', '#c89b3c'];
  // Burst
  confetti({
    particleCount: 80,
    spread: 70,
    origin: { y: 0.7 },
    colors,
    scalar: 1.1
  });
  setTimeout(() => {
    confetti({ particleCount: 50, angle: 60,  spread: 55, origin: { x: 0,   y: 0.7 }, colors });
    confetti({ particleCount: 50, angle: 120, spread: 55, origin: { x: 1,   y: 0.7 }, colors });
  }, 180);
}

$('btnBook')?.addEventListener('click', async function() {
  if (!validateBookingButton()) {
    toast('Complétez tous les champs requis', 'error');
    return;
  }
  const slot = allSlots.find(s => String(s.id) === String(selectedSlotId));
  if (!slot) { toast('Créneau introuvable', 'error'); return; }

  const spinner = $('bookSpinner');
  const icon = $('bookIcon');
  const text = $('bookText');
  spinner.style.display = 'inline-block';
  icon.style.display = 'none';
  text.textContent = 'Traitement...';
  this.disabled = true;

  try {
    const fd = new URLSearchParams();
    fd.append('action',       'ajouter');
    fd.append('patient',      CURRENT_PATIENT_ID);
    fd.append('date_demande', todayISO());
    fd.append('type',         $('consultationType').value);
    fd.append('medecin',      slot.id_medecin);
    fd.append('creneau',      slot.id);

    const r = await fetch(RDV_API, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: fd.toString()
    });
    const j = await r.json();

    if (!j.success) {
      const errs = {
        champs_manquants:        'Champs manquants',
        patient_introuvable:     'Patient introuvable',
        creneau_introuvable:     'Créneau introuvable',
        creneau_pris:            'Ce créneau vient d\'être réservé',
        creneau_medecin_mismatch:'Erreur de correspondance médecin/créneau'
      };
      toast(errs[j.message] || j.message || 'Erreur', 'error');
      return;
    }

    toast('Rendez-vous confirmé !', 'success');
    fireConfetti();

    // Reset booking
    selectedSpecialty = null;
    selectedDoctorId  = null;
    selectedDate      = null;
    selectedSlotId    = null;
    $('consultationType').value = '';
    $('notes').value = '';
    $('hintType').textContent = '';
    $('hintType').className = 'field-hint';

    setTimeout(() => goToStep(1), 600);

    await Promise.all([loadSlots(), loadAppointments()]);
    renderSpecialties();

  } catch (e) {
    console.error(e);
    toast('Erreur serveur', 'error');
  } finally {
    spinner.style.display = 'none';
    icon.style.display = '';
    text.textContent = 'Confirmer la réservation';
    validateBookingButton();
    refreshIcons();
  }
});

/* ═══════════════════════════════════════════════════════════
   APPOINTMENTS LIST
   ═══════════════════════════════════════════════════════════ */
function renderAppointments(list) {
  const c = $('appointmentsList');
  const data = list || appointments;
  if (!c) return;

  if (!data.length) {
    c.innerHTML = `<div class="empty">
      <i data-lucide="calendar-x" style="width:36px;height:36px"></i>
      <p class="empty-title">Aucun rendez-vous</p>
      <p style="font-size:.78rem; margin: .25rem 0 0">Réservez votre première consultation</p>
    </div>`;
    refreshIcons();
    return;
  }

  c.innerHTML = data.map(a => {
    const date = new Date(a.date + 'T00:00:00');
    const day = date.getDate();
    const month = date.toLocaleDateString('fr-FR', { month: 'short' }).replace('.', '');
    const isPast = a.date < todayISO();
    const canCancel = a.status !== 'annule' && a.status !== 'confirme' && !isPast;

    return `
      <div class="appt ${a.status}">
        <div class="appt-row">
          <div class="appt-info">
            <p class="appt-type">${a.type || 'Consultation'}</p>
            <p class="appt-doc">
              <i data-lucide="user-round" style="width:12px;height:12px"></i>
              ${a.doctor || '—'}
            </p>
          </div>
          <div class="appt-date-block">
            <div class="appt-day">${day}</div>
            <div class="appt-month">${month}</div>
            <div class="appt-time">${a.time}</div>
          </div>
        </div>
        <div class="appt-foot">
          <span class="badge ${a.status}">
            <span class="ddot"></span>
            ${statusLabel(a.status)}
          </span>
          <div class="appt-actions">
            <button class="ico-btn" title="Détails" onclick="showDetail('${a.id}')">
              <i data-lucide="eye" style="width:14px;height:14px"></i>
            </button>
            ${canCancel ? `<button class="ico-btn cancel" title="Annuler" onclick="cancelAppt('${a.id}')">
              <i data-lucide="x" style="width:14px;height:14px"></i>
            </button>` : ''}
          </div>
        </div>
      </div>`;
  }).join('');

  refreshIcons();
}

function showDetail(id) {
  const a = appointments.find(x => x.id === id);
  if (!a) return;

  const overlay = document.createElement('div');
  overlay.className = 'modal-overlay';
  overlay.innerHTML = `
    <div class="modal" role="dialog" aria-modal="true">
      <div class="modal-head">
        <h3 class="modal-title">Détails du rendez-vous</h3>
        <button class="ico-btn" onclick="this.closest('.modal-overlay').remove()">
          <i data-lucide="x" style="width:16px;height:16px"></i>
        </button>
      </div>
      <div class="modal-body">
        ${[
          ['ID',       a.id],
          ['Date',     fmtDateFR(a.date)],
          ['Horaire',  a.time],
          ['Médecin',  a.doctor || '—'],
          ['Type',     a.type || '—'],
          ['Statut',   `<span class="badge ${a.status}"><span class="ddot"></span>${statusLabel(a.status)}</span>`]
        ].map(([k,v]) => `
          <div class="modal-row">
            <span class="k">${k}</span>
            <span class="v">${v}</span>
          </div>`).join('')}
      </div>
    </div>`;
  overlay.addEventListener('click', e => { if (e.target === overlay) overlay.remove(); });
  document.body.appendChild(overlay);
  refreshIcons();
}
window.showDetail = showDetail;

async function cancelAppt(id) {
  if (!confirm('Annuler ce rendez-vous ?')) return;
  try {
    const fd = new URLSearchParams();
    fd.append('action', 'annuler');
    fd.append('id', id);
    fd.append('patient', CURRENT_PATIENT_ID);

    const r = await fetch(RDV_API, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: fd.toString()
    });
    const j = await r.json();
    if (!j.success) {
      const errs = {
        rdv_introuvable:    'RDV introuvable',
        acces_refuse:       'Accès refusé',
        rdv_deja_confirme:  'RDV déjà confirmé',
        rdv_deja_annule:    'RDV déjà annulé'
      };
      toast(errs[j.message] || j.message || 'Erreur', 'error');
      return;
    }
    toast('Rendez-vous annulé', 'success');
    await Promise.all([loadSlots(), loadAppointments()]);
  } catch (e) {
    console.error(e);
    toast('Erreur serveur', 'error');
  }
}
window.cancelAppt = cancelAppt;

/* ═══════════════════════════════════════════════════════════
   FILTERS
   ═══════════════════════════════════════════════════════════ */
$('searchRdv')?.addEventListener('input', function() {
  const q = this.value.toLowerCase();
  renderAppointments(appointments.filter(a =>
    String(a.id).toLowerCase().includes(q) ||
    String(a.doctor).toLowerCase().includes(q) ||
    String(a.type).toLowerCase().includes(q)
  ));
});
$('filterRdv')?.addEventListener('change', function() {
  const v = this.value;
  renderAppointments(v ? appointments.filter(a => normalize(a.status) === normalize(v)) : appointments);
});
$('refreshAppts')?.addEventListener('click', async () => {
  toast('Actualisation...', 'info');
  await Promise.all([loadSlots(), loadAppointments()]);
});

/* ═══════════════════════════════════════════════════════════
   ICONS HELPER
   ═══════════════════════════════════════════════════════════ */
let _iconRetryQueued = false;
function refreshIcons() {
  if (window.lucide && typeof window.lucide.createIcons === 'function') {
    try { window.lucide.createIcons(); } catch (e) { console.warn('lucide:', e); }
    return;
  }
  // Lucide not loaded yet — retry shortly
  if (!_iconRetryQueued) {
    _iconRetryQueued = true;
    const t = setInterval(() => {
      if (window.lucide && typeof window.lucide.createIcons === 'function') {
        clearInterval(t);
        _iconRetryQueued = false;
        try { window.lucide.createIcons(); } catch (e) { console.warn('lucide:', e); }
      }
    }, 100);
    setTimeout(() => { clearInterval(t); _iconRetryQueued = false; }, 5000);
  }
}

/* ═══════════════════════════════════════════════════════════
   INIT
   ═══════════════════════════════════════════════════════════ */
function init() {
  initTheme();

  // Header
  const firstName = CURRENT_PATIENT_NAME.split(' ')[0];
  $('firstName').textContent = firstName;
  $('patientFullName').textContent = CURRENT_PATIENT_NAME;
  $('patientHeaderName').textContent = firstName;
  $('avatarInitials').textContent = initials(CURRENT_PATIENT_NAME);

  // Today's date
  $('todayDate').textContent = new Date().toLocaleDateString('fr-FR', {
    weekday: 'long', day: 'numeric', month: 'long'
  });

  refreshIcons();
  buildCalendar();

  // Initial loads
  loadDoctors();
  loadSlots();
  loadAppointments();
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', init);
} else {
  init();
}
