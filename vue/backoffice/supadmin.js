// ═══════════════════════════════════════════════════════════════════════════
//  supadmin.js  —  Gestion des utilisateurs (Super Admin id_role = 4)
//  + Guard session + Modal Ban avec alerte email/SMS
// ═══════════════════════════════════════════════════════════════════════════

const CRUD_USER_A = '../../controleur/backoffice/user_crud.php';

let allUsers    = [];
let currentPage = 1;
const PAGE_SIZE = 10;
let filterRole  = '';

const ROLE_LABELS  = { 1:'Patient', 2:'Admin', 3:'Médecin', 4:'Super Admin' };
const ROLE_CLASSES = { 1:'role-patient', 2:'role-admin', 3:'role-medecin', 4:'role-super' };

// ── GUARD SESSION ─────────────────────────────────────────────────────────────
async function guardSession() {
  try {
    const r = await fetch(CRUD_USER_A + '?action=checkSession');
    const j = await r.json();
    if (!j.success || !j.loggedIn) {
      window.location.replace('../frontoffice/login.html');
      return false;
    }
    return true;
  } catch(e) {
    // Fallback : vérifier localStorage
    if (!localStorage.getItem('jn_user_id') && !sessionStorage.getItem('jn_logged')) {
      // window.location.replace('../frontoffice/login.html');
      // Commenté pour ne pas bloquer si sessions PHP pas configurées
    }
    return true;
  }
}

document.addEventListener('DOMContentLoaded', async () => {
  await guardSession();
  loadStats();
  loadUsers();

  document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      filterRole  = btn.dataset.role || '';
      currentPage = 1;
      loadUsers();
    });
  });

  document.getElementById('userSearch').addEventListener('input', () => {
    currentPage = 1;
    renderTable();
  });

  // Injecter le modal Ban dans le DOM
  injectBanModal();
});

// ── INJECTER MODAL BAN ────────────────────────────────────────────────────────
function injectBanModal() {
  if (document.getElementById('banModal')) return;
  const html = `
  <div id="banModal" style="position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1000;display:none;align-items:center;justify-content:center">
    <div style="background:#fff;border-radius:16px;padding:28px;width:100%;max-width:460px;box-shadow:0 20px 60px rgba(0,0,0,.25);animation:modalIn .2s ease">
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px">
        <div id="banModalIcon" style="width:42px;height:42px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
          <svg id="banModalIconSvg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="22" height="22"></svg>
        </div>
        <div>
          <div id="banModalTitle" style="font-size:1.1rem;font-weight:700"></div>
          <div id="banModalSub" style="font-size:.82rem;color:#6b7280;margin-top:2px"></div>
        </div>
      </div>
      <div style="margin-bottom:14px">
        <label style="font-size:.72rem;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:.06em;display:block;margin-bottom:5px">Raison (optionnelle)</label>
        <textarea id="banRaison" rows="3" style="width:100%;border:1.5px solid #dde2ef;border-radius:8px;padding:9px 12px;font-family:inherit;font-size:.85rem;resize:none;outline:none;transition:border-color .2s" placeholder="Motif de suspension..."></textarea>
      </div>
      <div style="background:#f0f4fb;border-radius:10px;padding:14px;margin-bottom:16px">
        <div style="font-size:.78rem;font-weight:600;color:#374151;margin-bottom:10px">Notifications à l'utilisateur</div>
        <label style="display:flex;align-items:center;gap:10px;margin-bottom:8px;cursor:pointer;font-size:.84rem">
          <input type="checkbox" id="notifEmail" checked style="width:15px;height:15px;accent-color:#2563eb"/>
          <span>📧 Envoyer un email de notification</span>
        </label>
        <label style="display:flex;align-items:center;gap:10px;cursor:pointer;font-size:.84rem">
          <input type="checkbox" id="notifSMS" style="width:15px;height:15px;accent-color:#2563eb"/>
          <span>📱 Envoyer un SMS de notification</span>
        </label>
      </div>
      <div style="display:flex;gap:10px">
        <button onclick="closeBanModal()" style="flex:1;height:42px;border:1.5px solid #dde2ef;border-radius:8px;background:#fff;font-family:inherit;font-size:.85rem;font-weight:600;cursor:pointer">Annuler</button>
        <button id="banConfirmBtn" onclick="confirmBan()" style="flex:1;height:42px;border:none;border-radius:8px;font-family:inherit;font-size:.85rem;font-weight:600;color:#fff;cursor:pointer"></button>
      </div>
    </div>
  </div>
  <style>@keyframes modalIn{from{transform:scale(.95);opacity:0}to{transform:scale(1);opacity:1}}</style>`;
  document.body.insertAdjacentHTML('beforeend', html);
}

let banPending = { idUser: null, name: '', newStatut: '' };

function openBanModal(idUser, name, newStatut) {
  banPending = { idUser, name, newStatut };

  const isBan = newStatut === 'bloqué' || newStatut === 'bloque';
  const modal = document.getElementById('banModal');
  const icon  = document.getElementById('banModalIcon');
  const svg   = document.getElementById('banModalIconSvg');
  const title = document.getElementById('banModalTitle');
  const sub   = document.getElementById('banModalSub');
  const btn   = document.getElementById('banConfirmBtn');

  if (isBan) {
    icon.style.background = '#fee2e2'; svg.style.color = '#dc2626';
    svg.innerHTML = '<circle cx="12" cy="12" r="10"/><path d="M4.93 4.93l14.14 14.14"/>';
    title.textContent  = `Suspendre ${name}`;
    sub.textContent    = 'Le compte sera désactivé immédiatement.';
    btn.style.background = '#dc2626';
    btn.textContent    = 'Confirmer la suspension';
  } else {
    icon.style.background = '#dcfce7'; svg.style.color = '#16a34a';
    svg.innerHTML = '<path d="M5 13l4 4L19 7"/>';
    title.textContent  = `Réactiver ${name}`;
    sub.textContent    = 'L\'utilisateur pourra se connecter à nouveau.';
    btn.style.background = '#16a34a';
    btn.textContent    = 'Confirmer la réactivation';
  }

  document.getElementById('banRaison').value = '';
  document.getElementById('notifEmail').checked = true;
  document.getElementById('notifSMS').checked   = false;
  modal.style.display = 'flex';
}

function closeBanModal() {
  document.getElementById('banModal').style.display = 'none';
  banPending = { idUser: null, name: '', newStatut: '' };
}

async function confirmBan() {
  const { idUser, newStatut } = banPending;
  if (!idUser) return;

  const raison     = document.getElementById('banRaison').value.trim();
  const notifEmail = document.getElementById('notifEmail').checked ? '1' : '0';
  const notifSMS   = document.getElementById('notifSMS').checked   ? '1' : '0';

  const btn = document.getElementById('banConfirmBtn');
  btn.disabled = true; btn.textContent = 'Traitement...';

  const fd = new FormData();
  fd.append('action',       'changeStatutWithAlert');
  fd.append('id_user',      idUser);
  fd.append('statut',       newStatut);
  fd.append('raison',       raison || 'Aucune raison spécifiée.');
  fd.append('notify_email', notifEmail);
  fd.append('notify_sms',   notifSMS);

  try {
    const r = await fetch(CRUD_USER_A, { method:'POST', body:fd });
    const j = await r.json();
    closeBanModal();
    if (j.success) {
      showToast('✓ ' + j.message, 'success');
      loadStats(); loadUsers();
    } else {
      showToast(j.error || 'Erreur', 'error');
    }
  } catch(e) {
    closeBanModal();
    showToast('Erreur réseau', 'error');
  }
}

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
    if (j.success) { allUsers = j.data; renderTable(); }
  } catch (e) {}
}

// ── RENDER TABLE ──────────────────────────────────────────────────────────────
function renderTable() {
  const search = document.getElementById('userSearch').value.toLowerCase();
  const filtered = allUsers.filter(u =>
    !search ||
    String(u.id_user).includes(search) ||
    (u.nom    || '').toLowerCase().includes(search) ||
    (u.prenom || '').toLowerCase().includes(search) ||
    (u.email  || '').toLowerCase().includes(search)
  );

  const total = filtered.length;
  const pages = Math.max(1, Math.ceil(total / PAGE_SIZE));
  if (currentPage > pages) currentPage = pages;
  const slice = filtered.slice((currentPage-1)*PAGE_SIZE, currentPage*PAGE_SIZE);

  document.getElementById('userCount').textContent =
    `Affichage de ${(currentPage-1)*PAGE_SIZE+1}–${Math.min(currentPage*PAGE_SIZE,total)} sur ${total} utilisateur(s)`;
  document.getElementById('pageInfo').textContent  = `${currentPage} / ${pages}`;

  const tbody = document.getElementById('userTableBody');
  if (!slice.length) {
    tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:36px;color:var(--muted)">Aucun utilisateur trouvé.</td></tr>';
    return;
  }

  tbody.innerHTML = slice.map(u => {
    const name      = [u.prenom, u.nom].filter(Boolean).join(' ') || '—';
    const roleLbl   = ROLE_LABELS[u.id_role]  || 'Inconnu';
    const roleCls   = ROLE_CLASSES[u.id_role] || 'role-patient';
    const isBanned  = u.statut_cmpt === 'bloqué' || u.statut_cmpt === 'bloque';
    const statusCls = isBanned ? 'badge-bloque' : 'badge-actif';
    const statusLbl = isBanned ? 'Bloqué' : 'Actif';

    const roleOptions = [1,2,3,4].map(r =>
      `<option value="${r}" ${u.id_role == r ? 'selected':''}>${ROLE_LABELS[r]}</option>`
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
      <td><span class="role-badge ${roleCls}">${roleLbl}</span></td>
      <td class="id-cell">${esc(u.cin||'—')}</td>
      <td>${esc(u.service||'—')}</td>
      <td><span class="status-dot ${statusCls}">${statusLbl}</span></td>
      <td>
        <div class="actions-cell">
          <select class="role-select" onchange="changeRole(${u.id_user}, this.value)" title="Changer le rôle">
            ${roleOptions}
          </select>
          ${isBanned
            ? `<button class="icon-btn ok" onclick="openBanModal(${u.id_user},'${esc(name)}','actif')" title="Débloquer">
                 <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
               </button>`
            : `<button class="icon-btn warn" onclick="openBanModal(${u.id_user},'${esc(name)}','bloqué')" title="Bloquer">
                 <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M4.93 4.93l14.14 14.14"/></svg>
               </button>`
          }
          <button class="icon-btn" onclick="editUser(${u.id_user})" title="Modifier le profil">
  <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
</button>
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
  const search = document.getElementById('userSearch').value.toLowerCase();
  const total  = allUsers.filter(u => !search || (u.nom||'').toLowerCase().includes(search) || (u.email||'').includes(search)).length;
  if (currentPage < Math.ceil(total / PAGE_SIZE)) { currentPage++; renderTable(); }
}

// ── CHANGER RÔLE ──────────────────────────────────────────────────────────────
async function changeRole(idUser, newRole) {
  const fd = new FormData();
  fd.append('action',  'changeRole');
  fd.append('id_user', idUser);
  fd.append('id_role', newRole);
  try {
    const r = await fetch(CRUD_USER_A, {method:'POST',body:fd});
    const j = await r.json();
    if (j.success) { showToast(`Rôle → ${ROLE_LABELS[newRole]}`, 'success'); loadStats(); loadUsers(); }
    else showToast(j.error||'Erreur','error');
  } catch(e) { showToast('Erreur réseau','error'); }
}

// ── SUPPRIMER ─────────────────────────────────────────────────────────────────
async function deleteUser(idUser, name) {
  if (!confirm(`Supprimer définitivement "${name}" (#${idUser}) ?\nIrréversible.`)) return;
  const fd = new FormData();
  fd.append('action',  'deleteUser');
  fd.append('id_user', idUser);
  try {
    const r = await fetch(CRUD_USER_A, {method:'POST',body:fd});
    const j = await r.json();
    if (j.success) { showToast(j.message,'success'); loadStats(); loadUsers(); }
    else showToast(j.error||'Erreur','error');
  } catch(e) { showToast('Erreur réseau','error'); }
}

// ── DÉCONNEXION ───────────────────────────────────────────────────────────────
async function logout() {
  try {
    await fetch(CRUD_USER_A + '?action=logout');
  } catch(e) {}
  localStorage.removeItem('jn_user_id');
  localStorage.removeItem('jn_user_role');
  sessionStorage.removeItem('jn_logged');
  window.location.replace('../frontoffice/login.html');
}

// ── TOAST ─────────────────────────────────────────────────────────────────────
function showToast(msg, type='success') {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.className   = 'toast ' + type + ' show';
  setTimeout(() => { t.className = 'toast'; }, 3200);
}

function esc(s) {
  return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}
function editUser(idUser) {
  window.location.href = '../backoffice/profil.html?id_user=' + idUser;
}