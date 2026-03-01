<?php
require_once __DIR__ . '/../config/auth.php';
$user   = requireAuth();
$pdo    = getDB();
$action = $_GET['action'] ?? 'list';

if ($action === 'list') {
    $chatId = intval($_GET['chat_id'] ?? 0);
    if (!$chatId) jsonResponse(['error' => 'chat_id requerido'], 400);

    if ($user['role'] !== 'admin') {
        $check = $pdo->prepare('SELECT 1 FROM chat_members WHERE chat_id = ? AND user_id = ?');
        $check->execute([$chatId, $user['id']]);
        if (!$check->fetch()) jsonResponse(['error' => 'Acceso denegado'], 403);
    }

    $since = $_GET['since'] ?? '0000-01-01 00:00:00';

    $stmt = $pdo->prepare("
        SELECT m.id, m.chat_id, m.content, m.is_read, m.created_at,
               u.id AS user_id, u.username, u.color
        FROM messages m
        JOIN users u ON m.user_id = u.id
        WHERE m.chat_id = ? AND m.created_at > ?
        ORDER BY m.created_at ASC
        LIMIT 200
    ");
    $stmt->execute([$chatId, $since]);
    $msgs = $stmt->fetchAll();

    $pdo->prepare("UPDATE messages SET is_read = 1 WHERE chat_id = ? AND user_id != ? AND is_read = 0")
        ->execute([$chatId, $user['id']]);

    jsonResponse($msgs);
}

if ($action === 'send' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data    = json_decode(file_get_contents('php://input'), true);
    $chatId  = intval($data['chat_id'] ?? 0);
    $content = trim($data['content'] ?? '');

    if (!$chatId || empty($content)) jsonResponse(['error' => 'Datos incompletos'], 400);
    if (mb_strlen($content) > 4000)  jsonResponse(['error' => 'Mensaje demasiado largo'], 400);

    if ($user['role'] !== 'admin') {
        $check = $pdo->prepare('SELECT 1 FROM chat_members WHERE chat_id = ? AND user_id = ?');
        $check->execute([$chatId, $user['id']]);
        if (!$check->fetch()) jsonResponse(['error' => 'Acceso denegado'], 403);
    }

    $pdo->prepare('INSERT INTO messages (chat_id, user_id, content) VALUES (?, ?, ?)')
        ->execute([$chatId, $user['id'], $content]);

    $id = (int)$pdo->lastInsertId();
    $stmt = $pdo->prepare("
        SELECT m.id, m.chat_id, m.content, m.is_read, m.created_at,
               u.id AS user_id, u.username, u.color
        FROM messages m JOIN users u ON m.user_id = u.id
        WHERE m.id = ?
    ");
    $stmt->execute([$id]);
    jsonResponse($stmt->fetch());
}

jsonResponse(['error' => 'Acción no válida'], 400);
