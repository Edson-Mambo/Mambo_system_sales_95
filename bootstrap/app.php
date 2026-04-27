<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

$pdo = Database::conectar();

/* AUTH GLOBAL */
function auth() {
    if (empty($_SESSION['usuario_id'])) {
        header("Location: /public/login.php");
        exit;
    }
}

function role($permitidos = []) {
    $nivel = $_SESSION['nivel_acesso'] ?? '';

    if (!in_array($nivel, $permitidos)) {
        header("Location: /public/index.php");
        exit;
    }
}