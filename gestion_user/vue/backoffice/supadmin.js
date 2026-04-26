// ═══════════════════════════════════════════════════════════════════════════
//  supadmin.js  —  Gestion des utilisateurs (Super Admin id_role = 4)
//  PHP : ../../controleur/backoffice/user_crud.php
// ═══════════════════════════════════════════════════════════════════════════

const CRUD_USER_A = '../../controleur/backoffice/user_crud.php';

let allUsers    = [];
let currentPage = 1;
const PAGE_SIZE = 10;
let filterRole  = '';

const ROLE_LABELS = { 1: 'Patient', 2: 'Admin', 3: 'Médecin', 4: 'Super Admin' };
const ROLE_CLASSES = { 1: 'role-patient', 2: 'role-admin', 3: 'role-medecin', 4: 'role-super' };

document.addEventListener('DOMContentLoaded', () => {
  loadStats();
  loadUsers();

  // Filtres par onglet
  document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      filterRole = btn.dataset.role || '';
      currentPage = 1;
      loadUsers();
    });
  });

  // Recherche
  document.getElementById('userSearch').addEventListener('input', () => {
    currentPage = 1;
    renderTable();
  });
});

// ── STATS ─────────────────────────────────────────────────────────────────────
async function loadStats() {
  try {
    const r = await fetch(CRUD_USER_A + '?action=getStats');
    const j = await r.json();
    if (!j.success) return;
    const d = j.data;
    document.getElementById('statTotal').textContent    = d.total.toLocaleString('fr-FR');
    document.getElementById('statMedecins').textContent = d.medecins;
    document.getElementById('statPatients').textContent = d.patients;
    document.getElementById('statAdmins').textContent   = d.admins;
  } catch (e) {}
}

// ── CHARGER UTILISATEURS ──────────────────────────────────────────────────────
async function loadUsers() {
  const url = filterRole
    ? CRUD_USER_A + `?action=getAllUsers&role=${filterRole}`
    : CRUD_USER_A + '?action=getAllUsers';
  try {
    const r = await fetch(url);
    const j = await r.json();
    if (j.success) {
      allUsers = j.data;
      renderTable();
    }
  } catch (e) {}
}

// ── RENDER TABLE ──────────────────────────────────────────────────────────────
function renderTable() {
  const search = document.getElementById('userSearch').value.toLowerCase();

  const filtered = allUsers.filter(u =>
    !search ||
    String(u.id_user).includes(search) ||
    (u.nom      || '').toLowerCase().includes(search) ||
    (u.prenom   || '').toLowerCase().includes(search) ||
    (u.email    || '').toLowerCase().includes(search) ||
    (u.username || '').toLowerCase().includes(search)
  );

  const total = filtered.length;
  const pages = Math.max(1, Math.ceil(total / PAGE_SIZE));
  if (currentPage > pages) currentPage = pages;
  const slice = filtered.slice((currentPage - 1) * PAGE_SIZE, currentPage * PAGE_SIZE);

  document.getElementById('userCount').textContent = `Affichage de ${(currentPage-1)*PAGE_SIZE+1}–${Math.min(currentPage*PAGE_SIZE, total)} sur ${total} utilisateur(s)`;
  document.getElementById('pageInfo').textContent  = `${currentPage} / ${pages}`;

  const tbody = document.getElementById('userTableBody');
  if (!slice.length) {
    tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:36px;color:var(--muted)">Aucun utilisateur trouvé.</td></tr>';
    return;
  }

  tbody.innerHTML = slice.map(u => {
    const name     = [u.prenom, u.nom].filter(Boolean).join(' ') || '—';
    const roleLbl  = ROLE_LABELS[u.id_role]  || 'Inconnu';
    const roleCls  = ROLE_CLASSES[u.id_role] || 'role-patient';
    const statusCls = u.statut_cmpt === 'actif' ? 'badge-actif' : 'badge-bloque';
    const statusLbl = u.statut_cmpt === 'actif' ? 'Actif'       : 'Bloqué';

    // Options de rôle pour le select inline
    const roleOptions = [1,2,3,4].map(r =>
      `<option value="${r}" ${u.id_role == r ? 'selected' : ''}>${ROLE_LABELS[r]}</option>`
    ).join('');

    return `<tr>
      <td class="id-cell">${esc(u.id_user)}</td>
      <td>
        <div style="display:flex;align-items:center;gap:10px">
          <div class="user-avatar">${(u.prenom||'?')[0].toUpperCase()}</div>
          <div>
            <div style="font-weight:600;font-size:.82rem">${esc(name)}</div>
            <div style="font-size:.72rem;color:var(--muted)">${esc(u.email||'')}</div>
          </div>
        </div>
      </td>
      <td class="id-cell">${esc(u.username || '—')}</td>
      <td><span class="role-badge ${roleCls}">${roleLbl}</span></td>
      <td class="id-cell">${esc(u.cin||'—')}</td>
      <td><span class="status-dot ${statusCls}">${statusLbl}</span></td>
      <td>
        <div class="actions-cell">
          <select class="role-select" onchange="changeRole(${u.id_user}, this.value)" title="Changer le rôle">
            ${roleOptions}
          </select>
          ${u.statut_cmpt === 'actif'
            ? `<button class="icon-btn warn" onclick="toggleStatut(${u.id_user},'bloqué')" title="Bloquer">
                 <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M4.93 4.93l14.14 14.14"/></svg>
               </button>`
            : `<button class="icon-btn ok" onclick="toggleStatut(${u.id_user},'actif')" title="Débloquer">
                 <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
               </button>`
          }
          <button class="icon-btn del" onclick="deleteUser(${u.id_user}, '${esc(name)}')" title="Supprimer">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><path d="M10 11v6M14 11v6M9 6V4h6v2"/></svg>
          </button>
        </div>
      </td>
    </tr>`;
  }).join('');
}

function prevPage() { if (currentPage > 1) { currentPage--; renderTable(); } }
function nextPage() {
  const total = allUsers.filter(u => {
    const s = document.getElementById('userSearch').value.toLowerCase();
    return !s || (u.nom||'').toLowerCase().includes(s) || (u.email||'').includes(s);
  }).length;
  if (currentPage < Math.ceil(total / PAGE_SIZE)) { currentPage++; renderTable(); }
}

// ── CHANGER RÔLE ──────────────────────────────────────────────────────────────
async function changeRole(idUser, newRole) {
  const fd = new FormData();
  fd.append('action',   'changeRole');
  fd.append('id_user',  idUser);
  fd.append('id_role',  newRole);
  try {
    const r = await fetch(CRUD_USER_A, { method: 'POST', body: fd });
    const j = await r.json();
    if (j.success) {
      showToast(`Rôle mis à jour → ${ROLE_LABELS[newRole]}`, 'success');
      loadStats();
      loadUsers();
    } else showToast(j.error || 'Erreur', 'error');
  } catch (e) { showToast('Erreur réseau', 'error'); }
}

// ── CHANGER STATUT ────────────────────────────────────────────────────────────
async function toggleStatut(idUser, newStatut) {
  const label = newStatut === 'actif' ? 'débloquer' : 'bloquer';
  if (!confirm(`Confirmer : ${label} cet utilisateur ?`)) return;
  const fd = new FormData();
  fd.append('action',   'changeStatut');
  fd.append('id_user',  idUser);
  fd.append('statut',   newStatut);
  try {
    const r = await fetch(CRUD_USER_A, { method: 'POST', body: fd });
    const j = await r.json();
    if (j.success) { showToast(j.message, 'success'); loadUsers(); }
    else showToast(j.error || 'Erreur', 'error');
  } catch (e) { showToast('Erreur réseau', 'error'); }
}

// ── SUPPRIMER ─────────────────────────────────────────────────────────────────
async function deleteUser(idUser, name) {
  if (!confirm(`Supprimer définitivement l'utilisateur "${name}" (#${idUser}) ?\nCette action est irréversible.`)) return;
  const fd = new FormData();
  fd.append('action',  'deleteUser');
  fd.append('id_user', idUser);
  try {
    const r = await fetch(CRUD_USER_A, { method: 'POST', body: fd });
    const j = await r.json();
    if (j.success) { showToast(j.message, 'success'); loadStats(); loadUsers(); }
    else showToast(j.error || 'Erreur', 'error');
  } catch (e) { showToast('Erreur réseau', 'error'); }
}

// ── TOAST ─────────────────────────────────────────────────────────────────────
function showToast(msg, type = 'success') {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.className   = 'toast ' + type + ' show';
  setTimeout(() => { t.className = 'toast'; }, 3200);
}

function esc(s) {
  return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
