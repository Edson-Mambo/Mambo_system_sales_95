<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

function response($success, $message)
{
    echo json_encode([
        'success' => $success,
        'message' => $message
    ], JSON_UNESCAPED_UNICODE);
    exit;
}


/* =========================
   VERIFICAR LOGIN
========================= */
function verificarLogin()
{
    if (empty($_SESSION['usuario_id'])) {
        response(false, 'Não autenticado');
    }

    return true;
}


/* =========================
   VERIFICAR CAIXA
========================= */
function verificarCaixa()
{
    if (empty($_SESSION['usuario_id'])) {
        response(false, 'Sessão expirada');
    }

    return true;
}


/* =========================
   VERIFICAR ADMIN/SUPERVISOR
   usar somente quando precisar senha
========================= */
function verificarAutorizacaoSenha($senha)
{
    $senha = trim($senha);

    if ($senha === '') {
        response(false, 'Senha obrigatória');
    }

    $senha_correta = '1234';

    if (!hash_equals($senha_correta, $senha)) {
        response(false, 'Senha incorreta');
    }

    return true;
}