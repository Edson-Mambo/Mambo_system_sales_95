<?php

header('Content-Type: application/json; charset=utf-8');

session_start();

$input = json_decode(file_get_contents("php://input"), true);

$id = (int)($input['id'] ?? 0);

if ($id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Produto inválido (ID vazio)'
    ]);
    exit;
}

if (!isset($_SESSION['carrinho'])) {
    $_SESSION['carrinho'] = [];
}

// 🔥 REMOÇÃO CONSISTENTE POR "id"
foreach ($_SESSION['carrinho'] as $index => $item) {

    if ((int)($item['id'] ?? 0) === $id) {

        unset($_SESSION['carrinho'][$index]);

        echo json_encode([
            'success' => true
        ]);
        exit;
    }
}

echo json_encode([
    'success' => false,
    'message' => 'Item não encontrado no carrinho'
]);