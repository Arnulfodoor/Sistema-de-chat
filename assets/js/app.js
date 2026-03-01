let currentChatId = null;
let pollInterval  = null;
let lastMsgTs     = null;
let timeLeft      = SESSION_TIMEOUT; 
let timerInterval = null;

const $ = id => document.getElementById(id);

function toast(msg, type = 'success') {
  document.querySelectorAll('.toast').forEach(t => t.remove());
  const el = document.createElement('div');
  el.className = 'toast ' + type;
  el.textContent = (type === 'success' ? '✅ ' : '❌ ') + msg;
  document.body.appendChild(el);
  setTimeout(() => el.remove(), 3500);
}

function escHtml(s) {
  return String(s ?? '')
    .replace(/&/g, '&amp;').replace(/</g, '&lt;')
    .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function initials(name) {
  return String(name || '?').slice(0, 2).toUpperCase();
}

function fmtTime(ts) {
  if (!ts) return '';
  const d = new Date(ts.replace(' ', 'T') + 'Z');
  return d.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
}

function fmtDate(ts) {
  if (!ts) return '';
  const d = new Date(ts.replace(' ', 'T') + 'Z');
  return d.toLocaleDateString('es-ES', { weekday: 'long', day: '2-digit', month: 'long' });
}

function timeAgo(ts) {
  if (!ts) return '';
  const d    = new Date(ts.replace(' ', 'T') + 'Z');
  const diff = (Date.now() - d) / 1000;
  if (diff < 60)    return 'ahora';
  if (diff < 3600)  return Math.floor(diff / 60) + 'm';
  if (diff < 86400) return Math.floor(diff / 3600) + 'h';
  return d.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit' });
}


async function api(url, method = 'GET', body = null) {
  const opts = {
    method,
    headers: { 'X-Requested-With': 'XMLHttpRequest' },
  };
  if (body) {
    opts.headers['Content-Type'] = 'application/json';
    opts.body = JSON.stringify(body);
  }
  let res;
  try {
    res = await fetch(url, opts);
  } catch (e) {
    throw new Error('Error de red. Verifica tu conexión.');
  }
  if (res.status === 401) {
    window.location.href = 'index.php';
    return null;
  }
  const data = await res.json();
  if (!res.ok) throw new Error(data.error || 'Error desconocido');
  return data;
}

function resetTimer() {
  timeLeft = SESSION_TIMEOUT;
  updateTimerDisplay();
}

function updateTimerDisplay() {
  const el = $('timer-display');
  if (!el) return;
  const m = Math.floor(timeLeft / 60);
  const s = timeLeft % 60;
  el.textContent = `⏱️ ${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
  el.className   = timeLeft <= 120 ? 'timer-badge warning' : 'timer-badge';
}

['mousemove', 'keydown', 'click', 'scroll', 'touchstart'].forEach(evt =>
  document.addEventListener(evt, resetTimer, { passive: true })
);

timerInterval = setInterval(async () => {
  timeLeft--;
  updateTimerDisplay();
  if (timeLeft <= 0) {
    clearInterval(timerInterval);
    clearInterval(pollInterval);
    toast('Sesión cerrada por inactividad', 'error');
    setTimeout(() => { window.location.href = 'index.php'; }, 1500);
    return;
  }
  if (timeLeft % 60 === 0) {
    try { await api('api/users.php?action=ping'); } catch (e) {}
  }
}, 1000);

function switchView(view) {
  $('chat-view').style.display   = view === 'chat'  ? 'flex' : 'none';
  $('admin-panel').style.display = view === 'admin' ? 'flex' : 'none';
  $('tab-chat')?.classList.toggle('active',  view === 'chat');
  $('tab-admin')?.classList.toggle('active', view === 'admin');
  if (view === 'admin') loadAdminPanel();
}

async function loadChatList() {
  try {
    const chats = await api('api/chats.php?action=list');
    if (!chats) return;
    renderChatList(chats);
  } catch (e) {
    console.error('loadChatList:', e);
  }
}

function renderChatList(chats) {
  const list = $('chat-list');
  if (!chats.length) {
    list.innerHTML = '<p class="no-data">Sin conversaciones</p>';
    return;
  }
  list.innerHTML = '';
  chats.forEach(chat => {
    const isGroup   = chat.type === 'group';
    const others    = (chat.members || []).filter(m => String(m.id) !== String(CURRENT_USER.id));
    const other     = others[0];
    const chatColor = isGroup ? '#8b5cf6' : (other?.color || '#6366f1');
    const isOnline  = !isGroup && other?.is_online == 1;
    const preview   = chat.last_msg
      ? escHtml((chat.last_sender ? chat.last_sender + ': ' : '') + chat.last_msg.slice(0, 40))
      : '<em>Sin mensajes</em>';

    const div = document.createElement('div');
    div.className      = 'chat-item' + (currentChatId == chat.id ? ' active' : '');
    div.dataset.name   = chat.name.toLowerCase();
    div.dataset.chatId = String(chat.id);

    div.onclick = () => openChat(
      chat.id, chat.name, chat.type, chatColor, chat.members || [], isOnline
    );

    div.innerHTML = `
      <div class="avatar" style="background:${chatColor}">
        ${isGroup ? '👥' : initials(chat.name)}
      </div>
      ${isOnline ? '<div class="online-dot"></div>' : ''}
      <div class="chat-info">
        <div class="chat-name">
          ${escHtml(chat.name)}
          ${isGroup ? '<span class="group-tag">Grupo</span>' : ''}
        </div>
        <div class="chat-preview">${preview}</div>
      </div>
      <div>
        ${Number(chat.unread) > 0
          ? `<div class="unread-badge">${chat.unread}</div>`
          : `<div class="chat-ts">${timeAgo(chat.last_ts)}</div>`}
      </div>
    `;
    list.appendChild(div);
  });
}

function filterChats(q) {
  document.querySelectorAll('.chat-item').forEach(el => {
    el.style.display = (el.dataset.name || '').includes(q.toLowerCase()) ? '' : 'none';
  });
}

async function openChat(chatId, name, type, color, members, isOnline) {
  currentChatId = chatId;
  lastMsgTs     = null;

  $('messages-container').innerHTML = '';

  $('empty-chat').style.display  = 'none';
  $('active-chat').style.display = 'flex';

  const av = $('ch-avatar');
  av.textContent      = type === 'group' ? '👥' : initials(name);
  av.style.background = color;
  $('ch-name').textContent = name;

  const memberNames = (members || []).map(m => m.username).join(', ');
  $('ch-status').textContent = type === 'group'
    ? `${members.length} miembros: ${memberNames}`
    : (isOnline ? '🟢 En línea' : '⚫ Desconectado');

  const delBtn = $('del-chat-btn');
  if (delBtn) delBtn.style.display = CURRENT_USER.role === 'admin' ? '' : 'none';

  document.querySelectorAll('.chat-item').forEach(el => {
    el.classList.toggle('active', el.dataset.chatId == chatId);
  });

  await loadMessages(true);

  clearInterval(pollInterval);
  pollInterval = setInterval(async () => {
    await loadMessages(false);
    loadChatList();
  }, 2500);
}

async function loadMessages(scrollToBottom = false) {
  if (!currentChatId) return;
  try {
    const since = lastMsgTs || '2000-01-01 00:00:00';
    const msgs  = await api(
      `api/messages.php?action=list&chat_id=${currentChatId}&since=${encodeURIComponent(since)}`
    );
    if (!msgs) return;

    if (msgs.length === 0) {
      if (!lastMsgTs) {

        $('messages-container').innerHTML =
          '<div class="msg-date" style="margin:auto">No hay mensajes aún. ¡Escribe el primero!</div>';
      }
      return;
    }

    lastMsgTs = msgs[msgs.length - 1].created_at;
    appendMessages(msgs, scrollToBottom);
  } catch (e) {
    console.error('loadMessages:', e);
  }
}

function appendMessages(msgs, scrollToBottom) {
  const container   = $('messages-container');
  const atBottom    = container.scrollHeight - container.clientHeight - container.scrollTop < 80;

  if (container.innerHTML.includes('No hay mensajes aún')) {
    container.innerHTML = '';
  }

  const seps     = container.querySelectorAll('.msg-date');
  let   lastDate = seps.length ? seps[seps.length - 1].textContent : '';

  msgs.forEach(m => {

    if (container.querySelector(`[data-id="${m.id}"]`)) return;

    const d = fmtDate(m.created_at);
    if (d !== lastDate) {
      const sep = document.createElement('div');
      sep.className   = 'msg-date';
      sep.textContent = d;
      container.appendChild(sep);
      lastDate = d;
    }

    const isMe = String(m.user_id) === String(CURRENT_USER.id);
    const div  = document.createElement('div');
    div.className  = 'msg ' + (isMe ? 'me' : 'other');
    div.dataset.id = m.id;
    div.innerHTML  = `
      ${!isMe ? `<div class="msg-sender">${escHtml(m.username)}</div>` : ''}
      <div class="msg-bubble">${escHtml(m.content)}</div>
      <div class="msg-time">${fmtTime(m.created_at)}${isMe ? ' ✓' : ''}</div>
    `;
    container.appendChild(div);
  });

  if (scrollToBottom || atBottom) {
    container.scrollTop = container.scrollHeight;
  }
}

async function sendMessage() {
  const input   = $('msg-input');
  const content = input.value.trim();
  if (!content || !currentChatId) return;

  input.value        = '';
  input.style.height = 'auto';

  try {
    await api('api/messages.php?action=send', 'POST', {
      chat_id: currentChatId,
      content,
    });
    await loadMessages(true);
    loadChatList();
  } catch (e) {
    toast(e.message, 'error');
  }
}

function handleKey(e) {
  if (e.key === 'Enter' && !e.shiftKey) {
    e.preventDefault();
    sendMessage();
  }
}

function autoResize(el) {
  el.style.height = 'auto';
  el.style.height = Math.min(el.scrollHeight, 120) + 'px';
}

function deleteChatConfirm() {
  showModal(
    'Eliminar Chat',
    '¿Eliminar este chat y todos sus mensajes? Esta acción no se puede deshacer.',
    async () => {
      try {
        await api('api/chats.php?action=delete', 'POST', { id: currentChatId });
        clearInterval(pollInterval);
        currentChatId = null;
        lastMsgTs     = null;
        $('active-chat').style.display = 'none';
        $('empty-chat').style.display  = 'flex';
        loadChatList();
        toast('Chat eliminado');
      } catch (e) {
        toast(e.message, 'error');
      }
    }
  );
}

async function loadAdminPanel() {
  await Promise.all([loadStats(), loadUserTable(), loadChatAdminTable(), loadUserSelect()]);
}

async function loadStats() {
  try {
    const s = await api('api/users.php?action=stats');
    if (!s) return;
    $('admin-stats').innerHTML = `
      <div class="stat-card"><div class="stat-num">${s.total_users}</div><div class="stat-label">👤 Usuarios</div></div>
      <div class="stat-card"><div class="stat-num">${s.online_users}</div><div class="stat-label">🟢 En línea</div></div>
      <div class="stat-card"><div class="stat-num">${s.total_chats}</div><div class="stat-label">💬 Chats</div></div>
      <div class="stat-card"><div class="stat-num">${s.total_messages}</div><div class="stat-label">✉️ Mensajes</div></div>
    `;
  } catch (e) { console.error(e); }
}

async function loadUserTable() {
  try {
    const users = await api('api/users.php?action=list');
    if (!users) return;
    const tbody = users.map(u => `
      <tr>
        <td>
          <div style="display:flex;align-items:center;gap:10px">
            <div class="avatar" style="background:${escHtml(u.color)};width:28px;height:28px;font-size:11px">
              ${initials(u.username)}
            </div>
            <strong>${escHtml(u.username)}</strong>
          </div>
        </td>
        <td><span class="badge ${u.is_online ? 'badge-online' : 'badge-offline'}">
          ${u.is_online ? '🟢 Online' : '⚫ Offline'}
        </span></td>
        <td><span class="badge ${u.role === 'admin' ? 'badge-admin' : 'badge-user'}">${u.role}</span></td>
        <td>${(u.created_at || '').slice(0, 10) || '—'}</td>
        <td>
          ${u.role !== 'admin'
            ? `<button class="btn-sm btn-del" onclick="deleteUserConfirm(${u.id},'${escHtml(u.username)}')">🗑️ Borrar</button>`
            : '—'}
        </td>
      </tr>
    `).join('');
    $('user-table').innerHTML = `
      <table>
        <thead><tr><th>Usuario</th><th>Estado</th><th>Rol</th><th>Creado</th><th>Acciones</th></tr></thead>
        <tbody>${tbody || '<tr><td colspan="5" class="no-data">Sin usuarios</td></tr>'}</tbody>
      </table>`;
  } catch (e) { console.error(e); }
}

async function loadChatAdminTable() {
  try {
    const chats = await api('api/chats.php?action=list');
    if (!chats) return;
    const tbody = chats.map(c => `
      <tr>
        <td><strong>${escHtml(c.name)}</strong></td>
        <td>${c.type === 'group' ? '👥 Grupo' : '💬 Privado'}</td>
        <td>${(c.members || []).map(m => escHtml(m.username)).join(', ')}</td>
        <td>${c.msg_count || 0}</td>
        <td>
          <button class="btn-sm btn-del" onclick="deleteChatById(${c.id},'${escHtml(c.name)}')">
            🗑️ Borrar
          </button>
        </td>
      </tr>
    `).join('');
    $('chat-admin-table').innerHTML = `
      <table>
        <thead><tr><th>Nombre</th><th>Tipo</th><th>Miembros</th><th>Mensajes</th><th>Acciones</th></tr></thead>
        <tbody>${tbody || '<tr><td colspan="5" class="no-data">Sin chats</td></tr>'}</tbody>
      </table>`;
  } catch (e) { console.error(e); }
}

async function loadUserSelect() {
  try {
    const users = await api('api/users.php?action=selectable');
    if (!users) return;
    $('new-chat-members').innerHTML = users.map(u =>
      `<option value="${u.id}">${escHtml(u.username)}</option>`
    ).join('');
  } catch (e) { console.error(e); }
}

async function createUser() {
  const username = $('new-username').value.trim();
  const password = $('new-password').value.trim();
  $('user-error').textContent = '';
  if (!username || !password) {
    $('user-error').textContent = 'Completa todos los campos.';
    return;
  }
  try {
    await api('api/users.php?action=create', 'POST', { username, password });
    $('new-username').value = '';
    $('new-password').value = '';
    toast(`Usuario "${username}" creado ✓`);
    loadAdminPanel();
  } catch (e) {
    $('user-error').textContent = e.message;
  }
}

async function createChat() {
  const name    = $('new-chat-name').value.trim();
  const type    = $('new-chat-type').value;
  const members = Array.from($('new-chat-members').selectedOptions).map(o => parseInt(o.value));
  $('chat-error').textContent = '';
  if (!name)          { $('chat-error').textContent = 'Escribe un nombre para el chat.'; return; }
  if (!members.length){ $('chat-error').textContent = 'Selecciona al menos un miembro.'; return; }
  try {
    await api('api/chats.php?action=create', 'POST', { name, type, members });
    $('new-chat-name').value = '';
    $('new-chat-members').selectedIndex = -1;
    toast(`Chat "${name}" creado ✓`);
    loadAdminPanel();
    loadChatList();
  } catch (e) {
    $('chat-error').textContent = e.message;
  }
}

function deleteUserConfirm(id, username) {
  showModal(
    'Eliminar Usuario',
    `¿Eliminar al usuario "${username}"? Se borrarán todos sus chats y mensajes.`,
    async () => {
      try {
        await api('api/users.php?action=delete', 'POST', { id });
        toast(`Usuario "${username}" eliminado`);
        loadAdminPanel();
        loadChatList();
      } catch (e) { toast(e.message, 'error'); }
    }
  );
}

function deleteChatById(id, name) {
  showModal(
    'Eliminar Chat',
    `¿Eliminar el chat "${name}" y todos sus mensajes?`,
    async () => {
      try {
        await api('api/chats.php?action=delete', 'POST', { id });
        if (currentChatId == id) {
          clearInterval(pollInterval);
          currentChatId = null;
          lastMsgTs     = null;
          $('active-chat').style.display = 'none';
          $('empty-chat').style.display  = 'flex';
        }
        toast(`Chat "${name}" eliminado`);
        loadAdminPanel();
        loadChatList();
      } catch (e) { toast(e.message, 'error'); }
    }
  );
}

// ── MODAL ────────────────────────────────────────────────────
function showModal(title, msg, onConfirm) {
  $('modal-title').textContent = title;
  $('modal-msg').textContent   = msg;
  $('modal-confirm-btn').onclick = () => { closeModal(); onConfirm(); };
  $('modal').style.display = 'flex';
}
function closeModal() {
  $('modal').style.display = 'none';
}

// ── INIT ──────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  updateTimerDisplay();
  loadChatList();
  setInterval(loadChatList, 5000);
});
