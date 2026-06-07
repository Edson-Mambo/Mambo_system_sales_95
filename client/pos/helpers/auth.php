<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* =========================
   LOGIN OBRIGATÓRIO
========================= */
function verificarLogin()
{
    if (empty($_SESSION['usuario_id'])) {

        header("Location: /Mambo_system_sales_95/client/auth/login.php");
        exit;
    }
}

/* =========================
   PERMISSÃO CAIXA
========================= */
function verificarCaixa()
{
    verificarLogin();

    $nivel = strtolower(trim($_SESSION['nivel'] ?? ''));

    if ($nivel !== 'caixa') {

        // opcional: limpar sessão para segurança
        session_destroy();

        header("Location: /Mambo_system_sales_95/client/auth/login.php?erro=acesso");
        exit;
    }
}