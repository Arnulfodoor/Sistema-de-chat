<?php
require_once __DIR__ . '/config/auth.php';

$user = getCurrentUser();
if ($user) {
    header('Location: chat.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Completa todos los campos.';
    } else {
        $loggedUser = login($username, $password);
        if ($loggedUser) {
            header('Location: chat.php');
            exit;
        } else {
            $error = 'Usuario o contraseña incorrectos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ChatApp — Iniciar Sesión</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-page">
<div class="auth-screen">
    <div class="auth-box">
        <div class="auth-logo">
            <div class="auth-icon">💬</div>
            <h1>ChatApp</h1>
            <p>Inicia sesión para continuar</p>
        </div>
        <form method="POST" action="index.php">
            <div class="form-group">
                <label>Usuario</label>
                <input type="text" name="username" placeholder="Tu nombre de usuario"
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" autocomplete="username" required>
            </div>
            <div class="form-group">
                <label>Contraseña</label>
                <input type="password" name="password" placeholder="Tu contraseña" autocomplete="current-password" required>
            </div>
            <?php if ($error): ?>
                <p class="error-msg">⚠️ <?= htmlspecialchars($error) ?></p>
            <?php endif; ?>
            <button type="submit" class="btn btn-primary">Entrar →</button>
        </form>
        <div class="session-bar">⏱️ La sesión expira tras 15 min de inactividad</div>
    </div>
</div>
</body>
</html>
