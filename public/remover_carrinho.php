<?php
session_start();
header('Content-Type: application/json');

$codigo = $_POST['codigo'] ?? null;

if (!$codigo) {
    echo json_encode([
        'success' => false,
        'message' => 'Código inválido'
    ]);
    exit;
}

// 🔥 IMPORTANTE: garantir que carrinho existe
if (!isset($_SESSION['carrinho'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Carrinho não existe'
    ]);
    exit;
}

// 🔥 DEBUG (podes remover depois)
// echo json_encode($_SESSION['carrinho']); exit;

if (!isset($_SESSION['carrinho'][$codigo])) {
    echo json_encode([
        'success' => false,
        'message' => 'Produto não encontrado no carrinho'
    ]);
    exit;
}

// remove produto
unset($_SESSION['carrinho'][$codigo]);

echo json_encode([
    'success' => true
]);