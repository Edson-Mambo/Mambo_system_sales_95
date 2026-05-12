<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function verificarLogin()
{
    if (!isset($_SESSION['usuario_id'])) {

        header("Location: /client/auth/login.php");
        exit;
    }
}

function verificarCaixa()
{
    verificarLogin();

    if (($_SESSION['nivel_acesso'] ?? '') !== 'caixa') {
        die("Acesso negado");
    }
}