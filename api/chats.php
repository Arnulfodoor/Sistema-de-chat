<?php

require_once __DIR__ . '/../config/auth.php';
$user = requireAuth();
$pdo  = getDB();

$action = $_GET['action'] ?? 'list';

if ($action === 'list') {
    if ($user['role'] === 'admin') {
        $stmt = $pdo->prepare("
            SELECT c.id, c.name, c.type,
                   (SELECT COUNT(*) FROM chat_members cm WHERE cm.chat_id = c.id) AS member_count,
                   (SELECT COUNT(*) FROM messages m WHERE m.chat_id = c.id) AS msg_count,
                   (SELECT m2.content FROM messages m2 WHERE m2.chat_id = c.id ORDER BY m2.created_at DESC LIMIT 1) AS last_msg,
                   (SELECT u2.username FROM messages m2 JOIN users u2 ON m2.user_id = u2.id WHERE m2.chat_id = c.id ORDER BY m2.created_at DESC LIMIT 1) AS last_sender,
                   (SELECT m2.created_at FROM messages m2 WHERE m2.chat_id = c.id ORDER BY m2.created_at DESC LIMIT 1) AS last_ts,
                   (SELECT COUNT(*) FROM messages m WHERE m.chat_id = c.id AND m.is_read = 0 AND m.user_id != ?) AS unread
            FROM chats c
            ORDER BY last_ts DESC
        ");
        $stmt->execute([$user['id']]);
    } else {
        $stmt = $pdo->prepare("
            SELECT c.id, c.name, c.type,
                   (SELECT COUNT(*) FROM chat_members cm WHERE cm.chat_id = c.id) AS member_count,
                   (SELECT COUNT(*) FROM messages m WHERE m.chat_id = c.id) AS msg_count,
                   (SELECT m2.content FROM messages m2 WHERE m2.chat_id = c.id ORDER BY m2.created_at DESC LIMIT 1) AS last_msg,
                   (SELECT u2.username FROM messages m2 JOIN users u2 ON m2.user_id = u2.id WHERE m2.chat_id = c.id ORDER BY m2.created_at DESC LIMIT 1) AS last_sender,
                   (SELECT m2.created_at FROM messages m2 WHERE m2.chat_id = c.id ORDER BY m2.created_at DESC LIMIT 1) AS last_ts,
                   (SELECT COUNT(*) FROM messages m WHERE m.chat_id = c.id AND m.is_read = 0 AND m.user_id != ?) AS unread
            FROM chats c
            JOIN chat_members cm ON c.id = cm.chat_id
            WHERE cm.user_id = ?
            ORDER BY last_ts DESC
        ");
        $stmt->execute([$user['id'], $user['id']]);
    }

    $chats = $stmt->fetchAll();

    foreach ($chats as &$chat) {
        $ms = $pdo->prepare("
            SELECT u.id, u.username, u.color, u.is_online
            FROM chat_members cm
            JOIN users u ON cm.user_id = u.id
            WHERE cm.chat_id = ?
        ");
        $ms->execute([$chat['id']]);
        $chat['members'] = $ms->fetchAll();
    }
    unset($chat);

    jsonResponse($chats);
}

if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireAdmin();
    $data    = json_decode(file_get_contents('php://input'), true);
    $name    = trim($data['name'] ?? '');
    $type    = $data['type'] === 'group' ? 'group' : 'direct';
    $members = array_map('intval', $data['members'] ?? []);

    if (empty($name))    jsonResponse(['error' => 'El nombre es obligatorio'], 400);
    if (empty($members)) jsonResponse(['error' => 'Selecciona al menos un miembro'], 400);

    $pdo->beginTransaction();
    try {
        $pdo->prepare('INSERT INTO chats (name, type, created_by) VALUES (?, ?, ?)')
            ->execute([$name, $type, $user['id']]);
        $chatId = (int)$pdo->lastInsertId();

        $ins = $pdo->prepare('INSERT IGNORE INTO chat_members (chat_id, user_id) VALUES (?, ?)');
        foreach ($members as $uid) {
            $ins->execute([$chatId, $uid]);
        }
        $pdo->commit();
        jsonResponse(['success' => true, 'id' => $chatId]);
    } catch (Exception $e) {
        $pdo->rollBack();
        jsonResponse(['error' => 'Error al crear el chat'], 500);
    }
}

if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireAdmin();
    $data   = json_decode(file_get_contents('php://input'), true);
    $chatId = intval($data['id'] ?? 0);
    if (!$chatId) jsonResponse(['error' => 'ID inválido'], 400);

    $pdo->prepare('DELETE FROM chats WHERE id = ?')->execute([$chatId]);
    jsonResponse(['success' => true]);
}

if ($action === 'members') {
    requireAdmin();
    $chatId = intval($_GET['chat_id'] ?? 0);
    $stmt = $pdo->prepare("
        SELECT u.id, u.username, u.color, u.is_online
        FROM chat_members cm JOIN users u ON cm.user_id = u.id
        WHERE cm.chat_id = ?
    ");
    $stmt->execute([$chatId]);
    jsonResponse($stmt->fetchAll());
}

jsonResponse(['error' => 'Acción no válida'], 400);
