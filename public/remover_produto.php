<?php
session_start();
header('Content-Type: application/json');

$codigo = $_POST['codigo'] ?? '';

if (!$codigo) {
    echo json_encode(['sucesso' => false, 'erro' => 'Código não informado.']);
    exit;
}

if (isset($_SESSION['carrinho'][$codigo])) {
    unset($_SESSION['carrinho'][$codigo]);
    echo json_encode(['sucesso' => true]);
} else {
    echo json_encode(['sucesso' => false, 'erro' => 'Produto não encontrado.']);
}
exit;
