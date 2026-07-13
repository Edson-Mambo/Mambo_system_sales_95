<?php

function abrirCaixaSessao($caixa_id, $abertura_id) {
    $_SESSION['caixa_id'] = $caixa_id;
    $_SESSION['abertura_id'] = $abertura_id;
}

function fecharCaixaSessao() {
    unset($_SESSION['caixa_id'], $_SESSION['abertura_id']);
}

function caixaAberto() {
    return !empty($_SESSION['caixa_id']) && !empty($_SESSION['abertura_id']);
}

/**
 * Verifica se o utilizador pode utilizar o caixa.
 */
function podeAcessarCaixa() {

    if (empty($_SESSION['usuario_id'])) {
        return false;
    }

    $nivel = strtolower(trim($_SESSION['nivel'] ?? ''));

    return in_array($nivel, [
        'admin',
        'administrador',
        'gerente',
        'supervisor',
        'caixa'
    ]);
}