<?php

require_once __DIR__ . '/database.php';

session_start([
    'cookie_lifetime' => 0,
    'cookie_httponly' => true,
    'cookie_samesite' => 'Strict',
]);

function getCurrentUser(): ?array {
    if (!isset($_SESSION['user_id'])) return null;

    $pdo = getDB();


    $stmt = $pdo->prepare('SELECT last_activity FROM sessions WHERE id = ? AND user_id = ?');
    $stmt->execute([session_id(), $_SESSION['user_id']]);
    $session = $stmt->fetch();

    if (!$session) {
        logout();
        return null;
    }

    $lastActivity = strtotime($session['last_activity']);
    if ((time() - $lastActivity) > SESSION_TIMEOUT) {
        logout();
        return null;
    }


    $pdo->prepare('UPDATE sessions SET last_activity = NOW() WHERE id = ?')
        ->execute([session_id()]);
    $pdo->prepare('UPDATE users SET is_online = 1, last_seen = NOW() WHERE id = ?')
        ->execute([$_SESSION['user_id']]);

    $stmt = $pdo->prepare('SELECT id, username, role, color, is_online FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

function requireAuth(): array {
    $user = getCurrentUser();
    if (!$user) {
        if (isAjax()) {
            http_response_code(401);
            die(json_encode(['error' => 'No autenticado', 'redirect' => true]));
        }
        header('Location: index.php');
        exit;
    }
    return $user;
}

function requireAdmin(): array {
    $user = requireAuth();
    if ($user['role'] !== 'admin') {
        if (isAjax()) {
            http_response_code(403);
            die(json_encode(['error' => 'Acceso denegado']));
        }
        header('Location: chat.php');
        exit;
    }
    return $user;
}

function login(string $username, string $password): ?array {
    $pdo = getDB();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        return null;
    }

    $pdo->prepare('DELETE FROM sessions WHERE user_id = ? AND last_activity < DATE_SUB(NOW(), INTERVAL ? SECOND)')
        ->execute([$user['id'], SESSION_TIMEOUT]);


    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];

    $pdo->prepare('INSERT INTO sessions (id, user_id, last_activity) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE last_activity = NOW()')
        ->execute([session_id(), $user['id']]);

    $pdo->prepare('UPDATE users SET is_online = 1, last_seen = NOW() WHERE id = ?')
        ->execute([$user['id']]);

    return $user;
}

function logout(): void {
    $pdo = getDB();
    if (isset($_SESSION['user_id'])) {
        $pdo->prepare('DELETE FROM sessions WHERE id = ?')->execute([session_id()]);
        $pdo->prepare('UPDATE users SET is_online = 0, last_seen = NOW() WHERE id = ?')
            ->execute([$_SESSION['user_id']]);
    }
    session_destroy();
    unset($_SESSION);
}

function isAjax(): bool {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

function jsonResponse(mixed $data, int $code = 200): never {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(): void {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        jsonResponse(['error' => 'Token CSRF inválido'], 403);
    }
}
