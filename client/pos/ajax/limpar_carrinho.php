<?php

session_start();
header('Content-Type: application/json');

try {

    // 🔥 apenas sessão (fonte real do carrinho)
    if (isset($_SESSION['carrinho'])) {
        unset($_SESSION['carrinho']);
    }

    echo json_encode([
        "success" => true,
        "message" => "Carrinho limpo com sucesso"
    ]);

} catch (Exception $e) {

    echo json_encode([
        "success" => false,
        "message" => "Erro ao limpar carrinho",
        "debug" => $e->getMessage()
    ]);
}