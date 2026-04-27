<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* =========================
   LOGIN OBRIGATÓRIO
========================= */
function requireLogin() {

    if (empty($_SESSION['usuario_id'])) {
        header("Location: login.php");
        exit;
    }
}

/* =========================
   PROTEGER POR ROLE
========================= */
function requireRole($roles = []) {

    requireLogin();

    $nivel = $_SESSION['nivel_acesso'] ?? null;

    if (!$nivel || !in_array($nivel, $roles)) {
        die("⛔ Acesso negado");
    }
}

/* =========================
   IMPORTANTE:
   ❌ NÃO usar redirect automático aqui
   (isso estava a quebrar o caixa)
========================= */
function redirectByRole() {

    if (empty($_SESSION['nivel_acesso'])) {
        header("Location: login.php");
        exit;
    }

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
            header("Location: ../public/abrir_caixa.php");
            break;

        default:
            header("Location: login.php");
    }

    exit;
}