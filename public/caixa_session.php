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