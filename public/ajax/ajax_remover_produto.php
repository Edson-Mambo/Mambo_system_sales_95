<?php
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método inválido']);
    exit;
}

$codigo = $_POST['codigo'] ?? '';
if (!$codigo) {
    echo json_encode(['success' => false, 'message' => 'Código inválido']);
    exit;
}

if (isset($_SESSION['carrinho'][$codigo])) {
    unset($_SESSION['carrinho'][$codigo]);
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Produto não encontrado no carrinho']);
}
