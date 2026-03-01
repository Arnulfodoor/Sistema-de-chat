<?php
require_once __DIR__ . '/../config/auth.php';
logout();
header('Location: ../index.php');
exit;
