<?php

require_once __DIR__ . '/../config/auth.php';
$user   = requireAuth();
$pdo    = getDB();
$action = $_GET['action'] ?? 'list';


if ($action === 'list') {
    requireAdmin();
    $stmt = $pdo->query("
        SELECT id, username, role, color, is_online, last_seen, created_at
        FROM users ORDER BY role ASC, username ASC
    ");
    jsonResponse($stmt->fetchAll());
}


if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireAdmin();
    $data     = json_decode(file_get_contents('php://input'), true);
    $username = trim($data['username'] ?? '');
    $password = $data['password'] ?? '';

    if (strlen($username) < 3)  jsonResponse(['error' => 'El usuario debe tener al menos 3 caracteres'], 400);
    if (strlen($password) < 4)  jsonResponse(['error' => 'La contraseña debe tener al menos 4 caracteres'], 400);
    if (!preg_match('/^[a-zA-Z0-9_\-\.]+$/', $username))
        jsonResponse(['error' => 'El usuario solo puede contener letras, números, _ - .'], 400);

    
    $check = $pdo->prepare('SELECT id FROM users WHERE username = ?');
    $check->execute([$username]);
    if ($check->fetch()) jsonResponse(['error' => 'El usuario ya existe'], 409);

    $colors = ['#6366f1','#8b5cf6','#ec4899','#14b8a6','#f59e0b','#10b981','#3b82f6','#f97316'];
    $color  = $colors[array_rand($colors)];
    $hash   = password_hash($password, PASSWORD_DEFAULT);

    $pdo->prepare('INSERT INTO users (username, password, role, color) VALUES (?, ?, "user", ?)')
        ->execute([$username, $hash, $color]);

    jsonResponse(['success' => true, 'id' => (int)$pdo->lastInsertId()]);
}

if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireAdmin();
    $data = json_decode(file_get_contents('php://input'), true);
    $uid  = intval($data['id'] ?? 0);
    if (!$uid) jsonResponse(['error' => 'ID inválido'], 400);

    $check = $pdo->prepare('SELECT role FROM users WHERE id = ?');
    $check->execute([$uid]);
    $target = $check->fetch();
    if (!$target)                   jsonResponse(['error' => 'Usuario no encontrado'], 404);
    if ($target['role'] === 'admin') jsonResponse(['error' => 'No puedes eliminar al administrador'], 403);

    $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$uid]);
    jsonResponse(['success' => true]);
}

if ($action === 'selectable') {
    requireAdmin();
    $stmt = $pdo->query("SELECT id, username, color FROM users WHERE role = 'user' ORDER BY username ASC");
    jsonResponse($stmt->fetchAll());
}

if ($action === 'stats') {
    requireAdmin();
    $stats = $pdo->query("
        SELECT
            (SELECT COUNT(*) FROM users) AS total_users,
            (SELECT COUNT(*) FROM users WHERE is_online = 1) AS online_users,
            (SELECT COUNT(*) FROM chats) AS total_chats,
            (SELECT COUNT(*) FROM messages) AS total_messages
    ")->fetch();
    jsonResponse($stats);
}

if ($action === 'ping') {
    jsonResponse(['ok' => true, 'user' => $user['username']]);
}

jsonResponse(['error' => 'Acción no válida'], 400);
