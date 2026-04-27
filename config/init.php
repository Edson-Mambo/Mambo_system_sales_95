<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/database.php';

$pdo = Database::conectar();

/* SEGURANÇA GLOBAL */
function checkAuth() {
    if (empty($_SESSION['usuario_id'])) {
        header("Location: /public/login.php");
        exit;
    }
}

function checkRole($roles = []) {
    $nivel = $_SESSION['nivel_acesso'] ?? '';

    if (!in_array($nivel, $roles)) {
        header("Location: /public/index.php");
        exit;
    }
}