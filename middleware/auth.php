<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* =========================
   VERIFICAR LOGIN
========================= */
function requireLogin() {

    if (!isset($_SESSION['usuario_id'])) {
        header("Location: login.php");
        exit;
    }
}

/* =========================
   VERIFICAR NÍVEL
========================= */
function requireRole($roles = []) {

    requireLogin();

    if (!isset($_SESSION['nivel_acesso'])) {
        header("Location: login.php");
        exit;
    }

    if (!in_array($_SESSION['nivel_acesso'], $roles)) {
        die("⛔ Acesso negado");
    }
}

/* =========================
   REDIRECIONAR APÓS LOGIN
========================= */
function redirectByRole() {

    if (!isset($_SESSION['nivel_acesso'])) return;

    switch ($_SESSION['nivel_acesso']) {

        case 'admin':
            header("Location: index_admin.php");
            break;

        case 'gerente':
            header("Location: index_gerente.php");
            break;

        case 'supervisor':
            header("Location: index_supervisor.php");
            break;

        case 'caixa':
            header("Location: venda.php");
            break;

        default:
            header("Location: login.php");
    }

    exit;
}