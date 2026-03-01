<?php
require_once __DIR__ . '/config/auth.php';
$user = requireAuth();
$csrf = csrfToken();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ChatApp</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>


<header class="topbar">
    <div class="topbar-left">
        <span class="topbar-logo">💬 ChatApp</span>
        <?php if ($user['role'] === 'admin'): ?>
        <div class="tabs">
            <button class="tab active" id="tab-chat" onclick="switchView('chat')">💬 Chats</button>
            <button class="tab" id="tab-admin" onclick="switchView('admin')">⚙️ Admin</button>
        </div>
        <?php endif; ?>
    </div>
    <div class="topbar-right">
        <div class="timer-badge" id="timer-display">⏱️ 15:00</div>
        <div class="user-badge">
            <div class="avatar" style="background:<?= htmlspecialchars($user['color']) ?>">
                <?= strtoupper(substr($user['username'], 0, 2)) ?>
            </div>
            <span><?= htmlspecialchars($user['username']) ?><?= $user['role'] === 'admin' ? ' 👑' : '' ?></span>
        </div>
        <a href="api/logout.php" class="logout-btn">Salir</a>
    </div>
</header>


<div id="chat-view" class="main-layout">

    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-title">Conversaciones</div>
            <div class="search-box">
                <input type="text" id="search-input" placeholder="Buscar..." oninput="filterChats(this.value)">
            </div>
        </div>
        <div class="sidebar-list" id="chat-list">
            <div class="loading-spinner">Cargando...</div>
        </div>
    </aside>


    <main class="chat-area" id="chat-area">
        <div class="empty-chat" id="empty-chat">
            <div class="empty-icon">💬</div>
            <p>Selecciona una conversación para comenzar</p>
        </div>
        <div id="active-chat" style="display:none;flex-direction:column;height:100%">
            <div class="chat-header">
                <div class="chat-header-info">
                    <div class="avatar" id="ch-avatar" style="background:#6366f1">?</div>
                    <div class="chat-header-text">
                        <h3 id="ch-name">—</h3>
                        <p id="ch-status">—</p>
                    </div>
                </div>
                <?php if ($user['role'] === 'admin'): ?>
                <button class="btn-sm btn-danger" id="del-chat-btn" onclick="deleteChatConfirm()" style="display:none">
                    🗑️ Borrar chat
                </button>
                <?php endif; ?>
            </div>
            <div class="chat-messages" id="messages-container"></div>
            <div class="chat-input-area">
                <textarea id="msg-input" class="msg-input" placeholder="Escribe un mensaje…" rows="1"
                          onkeydown="handleKey(event)" oninput="autoResize(this)"></textarea>
                <button class="send-btn" onclick="sendMessage()">➤</button>
            </div>
        </div>
    </main>
</div>


<div id="admin-panel" style="display:none;flex:1;overflow-y:auto;flex-direction:column">
    <div class="admin-inner">
    
        <div class="admin-grid" id="admin-stats"></div>

        <div class="admin-section">
            <h3>👤 Crear Usuario</h3>
            <div class="create-form">
                <input type="text" id="new-username" placeholder="Nombre de usuario">
                <input type="password" id="new-password" placeholder="Contraseña">
                <button class="btn-sm btn-primary" onclick="createUser()">+ Crear</button>
            </div>
            <p class="error-msg" id="user-error"></p>
        </div>


        <div class="admin-section">
            <h3>👥 Usuarios</h3>
            <div id="user-table"><div class="loading-spinner">Cargando…</div></div>
        </div>

     
        <div class="admin-section">
            <h3>💬 Crear Chat / Canal</h3>
            <div class="create-chat-form">
                <input type="text" id="new-chat-name" placeholder="Nombre del chat">
                <select id="new-chat-type">
                    <option value="direct">Privado (2 usuarios)</option>
                    <option value="group">Grupo</option>
                </select>
                <select id="new-chat-members" multiple style="height:90px"></select>
                <button class="btn-sm btn-primary" onclick="createChat()">+ Crear</button>
            </div>
            <p style="font-size:11px;color:var(--muted);margin-top:6px">Mantén Ctrl/Cmd para seleccionar múltiples usuarios</p>
            <p class="error-msg" id="chat-error"></p>
        </div>

  
        <div class="admin-section">
            <h3>💬 Chats Activos</h3>
            <div id="chat-admin-table"><div class="loading-spinner">Cargando…</div></div>
        </div>
    </div>
</div>


<div class="modal-overlay" id="modal" style="display:none" onclick="if(event.target===this)closeModal()">
    <div class="modal">
        <h3 id="modal-title">Confirmar</h3>
        <p id="modal-msg">¿Estás seguro?</p>
        <div class="modal-actions">
            <button class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
            <button class="btn btn-danger" id="modal-confirm-btn">Eliminar</button>
        </div>
    </div>
</div>

<script>
const CSRF = <?= json_encode($csrf) ?>;
const CURRENT_USER = <?= json_encode(['id' => $user['id'], 'username' => $user['username'], 'role' => $user['role']]) ?>;
const SESSION_TIMEOUT = <?= SESSION_TIMEOUT ?>;
</script>
<script src="assets/js/app.js"></script>
</body>
</html>
